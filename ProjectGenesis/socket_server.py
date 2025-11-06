# RUTA: socket_server.py
# (CÓDIGO COMPLETO CORREGIDO)

import asyncio
import websockets
import json
import logging
from aiohttp import web

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# Estructura de datos
CLIENTS_BY_USER_ID = {}
CLIENTS_BY_SESSION_ID = {}


async def broadcast_user_status(user_id, status):
    """Anuncia el cambio de estado de un usuario a todos los clientes."""
    if not user_id:
        return
        
    message = json.dumps({"type": "user_status", "user_id": user_id, "status": status})
    logging.info(f"[WS-STATUS] Transmitiendo: user_id={user_id} está {status}")
    
    all_websockets = CLIENTS_BY_SESSION_ID.values()
    if all_websockets:
        tasks = [ws.send(message) for ws in all_websockets]
        await asyncio.gather(*tasks, return_exceptions=True)


async def broadcast_count():
    """Transmite el conteo total."""
    count = len(CLIENTS_BY_SESSION_ID) 
    message = json.dumps({"type": "user_count", "count": count})
    
    all_websockets = CLIENTS_BY_SESSION_ID.values()
    if all_websockets:
        tasks = [ws.send(message) for ws in all_websockets]
        await asyncio.gather(*tasks, return_exceptions=True)


async def register_client(websocket, user_id, session_id):
    """Añade un cliente y anuncia su estado."""
    
    # 1. Enviar al nuevo cliente la lista de todos los que ya están conectados
    try:
        # Obtenemos los IDs de usuario (claves) de los que tienen sets de websockets no vacíos
        online_ids = [uid for uid, ws_set in CLIENTS_BY_USER_ID.items() if ws_set]
        presence_message = json.dumps({"type": "presence_list", "user_ids": online_ids})
        await websocket.send(presence_message)
    except Exception as e:
        # --- ▼▼▼ LÍNEA CORREGIDA ▼▼▼ ---
        logging.error(f"[WS-PRESENCE] Error al enviar lista de presencia a {session_id[:5]}...: {e}")
        # --- ▲▲▲ LÍNEA CORREGIDA ▲▲▲ ---

    # 2. Comprobar si es la primera conexión de este usuario
    is_first_connection = user_id not in CLIENTS_BY_USER_ID or not CLIENTS_BY_USER_ID[user_id]
    
    # 3. Registrar al cliente (lógica existente)
    CLIENTS_BY_SESSION_ID[session_id] = websocket
    if user_id not in CLIENTS_BY_USER_ID:
        CLIENTS_BY_USER_ID[user_id] = set()
    CLIENTS_BY_USER_ID[user_id].add(websocket)
    
    # --- ▼▼▼ LÍNEA CORREGIDA ▼▼▼ ---
    logging.info(f"[WS] Cliente autenticado: user_id={user_id}, session_id={session_id[:5]}... Total: {len(CLIENTS_BY_SESSION_ID)}")
    # --- ▲▲▲ LÍNEA CORREGIDA ▲▲▲ ---

    # 4. Si era su primera conexión, anunciar a todos que está "online"
    if is_first_connection:
        await broadcast_user_status(user_id, "online")

    # 5. Transmitir el nuevo conteo total
    await broadcast_count()


async def unregister_client(session_id):
    """Elimina un cliente y anuncia su estado si es necesario."""
    websocket = CLIENTS_BY_SESSION_ID.pop(session_id, None)
    if not websocket:
        return 

    user_id_to_remove = None
    was_last_connection = False

    # Encontrar a qué usuario pertenecía
    for user_id, ws_set in CLIENTS_BY_USER_ID.items():
        if websocket in ws_set:
            ws_set.remove(websocket)
            user_id_to_remove = user_id
            if not ws_set: # Si el set de este usuario quedó vacío
                was_last_connection = True
            break
    
    if user_id_to_remove and was_last_connection:
        # Si era la última conexión, eliminar al usuario del dict principal
        del CLIENTS_BY_USER_ID[user_id_to_remove]
        
    # --- ▼▼▼ LÍNEA CORREGIDA ▼▼▼ ---
    logging.info(f"[WS] Cliente desconectado: session_id={session_id[:5]}... Total: {len(CLIENTS_BY_SESSION_ID)}")
    # --- ▲▲▲ LÍNEA CORREGIDA ▲▲▲ ---
    
    # Si era la última conexión, anunciar a todos que está "offline"
    if was_last_connection:
        await broadcast_user_status(user_id_to_remove, "offline")

    # Transmitir el nuevo conteo total
    await broadcast_count()


async def ws_handler(websocket):
    """Manejador principal de conexiones WebSocket."""
    session_id = None
    try:
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
        
        async for message in websocket:
            pass 

    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        if session_id:
            await unregister_client(session_id)


#
# --- MANEJADORES HTTP (SIN CAMBIOS EN SINTAXIS DE LOGS) ---
#
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
            
        # --- ▼▼▼ LÍNEA CORREGIDA ▼▼▼ ---
        logging.info(f"[HTTP-KICK] Recibida orden de expulsión para user_id={user_id}, excluyendo session_id={exclude_session_id[:5]}...")
        # --- ▲▲▲ LÍNEA CORREGIDA ▲▲▲ ---

        websockets_to_kick = CLIENTS_BY_USER_ID.get(user_id)
        if not websockets_to_kick:
            logging.info(f"[HTTP-KICK] No se encontraron conexiones activas para user_id={user_id}.")
            return web.json_response({"status": "ok", "kicked": 0})
        
        kick_message = json.dumps({"type": "force_logout"})
        tasks = []
        kicked_count = 0
        
        for ws in list(websockets_to_kick):
            current_session_id = None
            for sid, w in CLIENTS_BY_SESSION_ID.items():
                if w == ws:
                    current_session_id = sid
                    break
            
            if current_session_id and current_session_id != exclude_session_id:
                # --- ▼▼▼ LÍNEA CORREGIDA ▼▼▼ ---
                logging.info(f"[HTTP-KICK] Enviando orden de expulsión a session_id={current_session_id[:5]}...")
                # --- ▲▲▲ LÍNEA CORREGIDA ▲▲▲ ---
                tasks.append(ws.send(kick_message))
                kicked_count += 1
            else:
                # --- ▼▼▼ LÍNEA CORREGIDA ▼▼▼ ---
                logging.info(f"[HTTP-KICK] Omitiendo expulsión para la sesión activa (session_id={current_session_id[:5]}...)")
                # --- ▲▲▲ LÍNEA CORREGIDA ▲▲▲ ---

        if tasks:
            await asyncio.gather(*tasks, return_exceptions=True)

        return web.json_response({"status": "ok", "kicked": kicked_count})

    except Exception as e:
        logging.error(f"[HTTP-KICK] Error al procesar expulsión: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)

async def http_handler_kick_bulk(request):
    """
    Recibe una orden de expulsión masiva desde PHP (para mantenimiento).
    Espera un JSON: {"user_ids": [1, 2, 3...]}
    """
    if request.method != 'POST':
        return web.Response(status=405) # Solo permitir POST

    try:
        data = await request.json()
        user_ids = data.get("user_ids")

        if not isinstance(user_ids, list):
            raise ValueError("Falta 'user_ids' o no es una lista")
            
        logging.info(f"[HTTP-KICK-BULK] Recibida orden de expulsión masiva para {len(user_ids)} IDs de usuario.")

        websockets_to_kick = set()
        
        for user_id in user_ids:
            ws_set = CLIENTS_BY_USER_ID.get(int(user_id))
            if ws_set:
                websockets_to_kick.update(ws_set)

        if not websockets_to_kick:
            logging.info(f"[HTTP-KICK-BULK] No se encontraron conexiones activas para los IDs proporcionados.")
            return web.json_response({"status": "ok", "kicked": 0})
        
        kick_message = json.dumps({"type": "force_logout"})
        tasks = [ws.send(kick_message) for ws in websockets_to_kick]
        
        if tasks:
            await asyncio.gather(*tasks, return_exceptions=True)

        logging.info(f"[HTTP-KICK-BULK] Se enviaron {len(tasks)} órdenes de expulsión.")
        return web.json_response({"status": "ok", "kicked": len(tasks)})

    except Exception as e:
        logging.error(f"[HTTP-KICK-BULK] Error al procesar expulsión masiva: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)

async def http_handler_update_status(request):
    """
    Recibe una orden de actualización de estado desde PHP (endpoint interno).
    Espera un JSON: {"user_id": 123, "status": "suspended"}
    """
    if request.method != 'POST':
        return web.Response(status=405) # Solo permitir POST

    try:
        data = await request.json()
        user_id = int(data.get("user_id"))
        new_status = data.get("status") # "suspended", "deleted"

        if not user_id or not new_status:
            raise ValueError("Faltan user_id o status")
            
        logging.info(f"[HTTP-STATUS] Recibida orden de estado para user_id={user_id}, nuevo estado={new_status}")

        websockets_to_notify = CLIENTS_BY_USER_ID.get(user_id)
        if not websockets_to_notify:
            logging.info(f"[HTTP-STATUS] No se encontraron conexiones activas para user_id={user_id}.")
            return web.json_response({"status": "ok", "notified": 0})
        
        status_message = json.dumps({"type": "account_status_update", "status": new_status})
        tasks = []
        notified_count = 0
        
        for ws in list(websockets_to_notify):
            logging.info(f"[HTTP-STATUS] Enviando actualización a una sesión de user_id={user_id}")
            tasks.append(ws.send(status_message))
            notified_count += 1

        if tasks:
            await asyncio.gather(*tasks, return_exceptions=True)

        return web.json_response({"status": "ok", "notified": notified_count})

    except Exception as e:
        logging.error(f"[HTTP-STATUS] Error al procesar actualización de estado: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)


# --- Servidores (SIN CAMBIOS) ---
async def run_ws_server():
    """Inicia y mantiene vivo el servidor WebSocket."""
    logging.info(f"[WS] Iniciando servidor WebSocket en ws://0.0.0.0:8765")
    async with websockets.serve(ws_handler, "0.0.0.0", 8765):
        await asyncio.Event().wait() 

async def run_http_server():
    """Inicia y mantiene vivo el servidor HTTP."""
    http_app = web.Application()
    http_app.router.add_get("/count", http_handler_count)
    http_app.router.add_post("/kick", http_handler_kick) 
    http_app.router.add_post("/update-status", http_handler_update_status) 
    http_app.router.add_post("/kick-bulk", http_handler_kick_bulk)
    
    http_runner = web.AppRunner(http_app)
    await http_runner.setup()
    http_site = web.TCPSite(http_runner, "0.0.0.0", 8766) 
    logging.info(f"[HTTP] Iniciando servidor HTTP en http://0.0.0.0:8766")
    await http_site.start()
    await asyncio.Event().wait() 

async def start_servers():
    """Inicia ambos servidores (WS y HTTP) en paralelo."""
    ws_task = asyncio.create_task(run_ws_server())
    http_task = asyncio.create_task(run_http_server())
    await asyncio.gather(ws_task, http_task)


if __name__ == "__main__":
    try:
        asyncio.run(start_servers())
    except KeyboardInterrupt:
        logging.info("Servidor detenido por el usuario.")
    except Exception as e:
        logging.critical(f"Error fatal en el servidor: {e}")