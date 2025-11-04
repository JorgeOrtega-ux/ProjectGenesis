import asyncio
import websockets
import json
import logging
from aiohttp import web

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# --- ▼▼▼ INICIO DE MODIFICACIÓN: ESTRUCTURA DE DATOS ▼▼▼ ---
# Ya no usamos un 'set' simple.
# Usamos diccionarios para rastrear conexiones por ID de usuario y por ID de sesión.
CLIENTS_BY_USER_ID = {}
CLIENTS_BY_SESSION_ID = {}
# --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---


# --- ▼▼▼ INICIO DE MODIFICACIÓN: MANEJADOR DE WEBSOCKET ▼▼▼ ---
async def broadcast_count():
    # Contar conexiones únicas por ID de sesión
    count = len(CLIENTS_BY_SESSION_ID) 
    message = json.dumps({"type": "user_count", "count": count})
    
    # Enviar a todos los websockets conectados
    all_websockets = CLIENTS_BY_SESSION_ID.values()
    if all_websockets:
        tasks = [ws.send(message) for ws in all_websockets]
        await asyncio.gather(*tasks, return_exceptions=True)

async def register_client(websocket, user_id, session_id):
    """Añade un cliente a los diccionarios de seguimiento."""
    # Guardar por ID de sesión (para búsqueda rápida)
    CLIENTS_BY_SESSION_ID[session_id] = websocket
    
    # Guardar por ID de usuario (para expulsiones)
    if user_id not in CLIENTS_BY_USER_ID:
        CLIENTS_BY_USER_ID[user_id] = set()
    CLIENTS_BY_USER_ID[user_id].add(websocket)
    
    logging.info(f"[WS] Cliente autenticado: user_id={user_id}, session_id={session_id[:5]}... Total: {len(CLIENTS_BY_SESSION_ID)}")
    await broadcast_count()

async def unregister_client(session_id):
    """Elimina un cliente de los diccionarios."""
    websocket = CLIENTS_BY_SESSION_ID.pop(session_id, None)
    if not websocket:
        return # Ya fue eliminado

    # Encontrar a qué usuario pertenecía
    user_id_to_remove = None
    for user_id, ws_set in CLIENTS_BY_USER_ID.items():
        if websocket in ws_set:
            ws_set.remove(websocket)
            if not ws_set: # Si el set está vacío, eliminar al usuario
                user_id_to_remove = user_id
            break
    
    if user_id_to_remove:
        del CLIENTS_BY_USER_ID[user_id_to_remove]
        
    logging.info(f"[WS] Cliente desconectado: session_id={session_id[:5]}... Total: {len(CLIENTS_BY_SESSION_ID)}")
    await broadcast_count()


async def ws_handler(websocket):
    """Manejador principal de conexiones WebSocket."""
    session_id = None
    try:
        # --- Esperar mensaje de autenticación ---
        message_json = await websocket.recv()
        data = json.loads(message_json)
        
        if data.get("type") == "auth" and data.get("user_id") and data.get("session_id"):
            user_id = int(data["user_id"])
            session_id = data["session_id"]
            
            if user_id > 0:
                await register_client(websocket, user_id, session_id)
            else:
                raise Exception("ID de usuario no válido")
        else:
            raise Exception("Autenticación fallida")
        
        # Mantener la conexión abierta para escuchar
        async for message in websocket:
            pass # No esperamos más mensajes, solo mantenemos la conexión

    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        if session_id:
            await unregister_client(session_id)
# --- ▲▲▲ FIN DE MODIFICACIÓN: MANEJADOR DE WEBSOCKET ▼▼▼ ---


# --- ▼▼▼ INICIO DE MODIFICACIÓN: MANEJADOR DE HTTP ▼▼▼ ---
async def http_handler_count(request):
    """Devuelve el conteo de usuarios (endpoint público)."""
    count = len(CLIENTS_BY_SESSION_ID)
    logging.info(f"[HTTP] Solicitud de conteo recibida. Respondiendo: {count}")
    response_data = {"active_users": count}
    return web.json_response(response_data)

async def http_handler_kick(request):
    """
    Recibe una orden de expulsión desde PHP (endpoint interno).
    Espera un JSON: {"user_id": 123, "exclude_session_id": "abc..."}
    """
    if request.method != 'POST':
        return web.Response(status=405) # Solo permitir POST

    try:
        data = await request.json()
        user_id = int(data.get("user_id"))
        exclude_session_id = data.get("exclude_session_id")

        if not user_id or not exclude_session_id:
            raise ValueError("Faltan user_id o exclude_session_id")
            
        logging.info(f"[HTTP-KICK] Recibida orden de expulsión para user_id={user_id}, excluyendo session_id={exclude_session_id[:5]}...")

        # Buscar las conexiones de este usuario
        websockets_to_kick = CLIENTS_BY_USER_ID.get(user_id)
        if not websockets_to_kick:
            logging.info(f"[HTTP-KICK] No se encontraron conexiones activas para user_id={user_id}.")
            return web.json_response({"status": "ok", "kicked": 0})
        
        # Preparar el mensaje de expulsión
        kick_message = json.dumps({"type": "force_logout"})
        tasks = []
        kicked_count = 0
        
        # Iterar sobre una copia del set (por si se modifica)
        for ws in list(websockets_to_kick):
            # Encontrar el session_id de este websocket
            current_session_id = None
            for sid, w in CLIENTS_BY_SESSION_ID.items():
                if w == ws:
                    current_session_id = sid
                    break
            
            # ¡No expulsar a la sesión que inició la acción!
            if current_session_id and current_session_id != exclude_session_id:
                logging.info(f"[HTTP-KICK] Enviando orden de expulsión a session_id={current_session_id[:5]}...")
                tasks.append(ws.send(kick_message))
                kicked_count += 1
            else:
                logging.info(f"[HTTP-KICK] Omitiendo expulsión para la sesión activa (session_id={current_session_id[:5]}...)")

        if tasks:
            await asyncio.gather(*tasks, return_exceptions=True)

        return web.json_response({"status": "ok", "kicked": kicked_count})

    except Exception as e:
        logging.error(f"[HTTP-KICK] Error al procesar expulsión: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)
# --- ▲▲▲ FIN DE MODIFICACIÓN: MANEJADOR DE HTTP ▼▼▼ ---


# --- ▼▼▼ INICIO DE MODIFICACIÓN: INICIO DE SERVIDORES ▼▼▼ ---
async def run_ws_server():
    """Inicia y mantiene vivo el servidor WebSocket."""
    logging.info(f"[WS] Iniciando servidor WebSocket en ws://0.0.0.0:8765")
    async with websockets.serve(ws_handler, "0.0.0.0", 8765):
        await asyncio.Event().wait() 

async def run_http_server():
    """Inicia y mantiene vivo el servidor HTTP."""
    http_app = web.Application()
    # Endpoint público para el contador
    http_app.router.add_get("/count", http_handler_count)
    # Endpoint interno para la expulsión (solo POST)
    http_app.router.add_post("/kick", http_handler_kick) 
    
    http_runner = web.AppRunner(http_app)
    await http_runner.setup()
    http_site = web.TCPSite(http_runner, "0.0.0.0", 8766) # Escuchar en 0.0.0.0
    logging.info(f"[HTTP] Iniciando servidor HTTP en http://0.0.0.0:8766")
    await http_site.start()
    await asyncio.Event().wait() 

async def start_servers():
    """Inicia ambos servidores (WS y HTTP) en paralelo."""
    ws_task = asyncio.create_task(run_ws_server())
    http_task = asyncio.create_task(run_http_server())
    await asyncio.gather(ws_task, http_task)
# --- ▲▲▲ FIN DE MODIFICACIÓN: INICIO DE SERVIDORES ▼▼▼ ---


if __name__ == "__main__":
    try:
        asyncio.run(start_servers())
    except KeyboardInterrupt:
        logging.info("Servidor detenido por el usuario.")
    except Exception as e:
        logging.critical(f"Error fatal en el servidor: {e}")