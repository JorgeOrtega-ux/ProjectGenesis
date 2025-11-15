import asyncio
import websockets
import json
import logging
from aiohttp import web
import aiohttp 

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# --- ▼▼▼ INICIO DE MODIFICACIÓN: ESTRUCTURA DE DATOS ▼▼▼ ---
# CLIENTS_BY_USER_ID: Mapea user_id -> set(websockets)
#   Nos dice qué usuarios están conectados y con cuántos dispositivos.
CLIENTS_BY_USER_ID = {} 

# CLIENTS_BY_SESSION_ID: Mapea session_id -> (websocket, user_id)
#   Nos permite encontrar y eliminar rápidamente un websocket específico.
CLIENTS_BY_SESSION_ID = {}

# (CLIENTS_BY_COMMUNITY_ID eliminado)
# --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---


async def notify_backend_of_offline(user_id):
    """Notifica al backend PHP que un usuario se ha desconectado."""
    # Asegúrate de que esta URL sea correcta para tu servidor Apache/PHP
    url = "http://127.0.0.1/ProjectGenesis/api/presence_handler.php" 
    payload = {"user_id": user_id}
    try:
        async with aiohttp.ClientSession() as session:
            # Hacemos un POST con un timeout corto
            async with session.post(url, json=payload, timeout=2.0) as response:
                if response.status == 200:
                    logging.info(f"[HTTP-NOTIFY] Backend notificado: user_id={user_id} está offline (last_seen actualizado).")
                else:
                    logging.warning(f"[HTTP-NOTIFY] Error al notificar al backend (código {response.status}) para user_id={user_id}")
    except Exception as e:
        # Esto puede fallar si PHP no está corriendo, es normal en desarrollo
        logging.error(f"[HTTP-NOTIFY] Excepción al notificar al backend para user_id={user_id}: {e}")


async def broadcast_presence_update(user_id, status):
    """Notifica a TODOS los clientes conectados sobre un cambio de estado."""
    message = json.dumps({
        "type": "presence_update", 
        "user_id": user_id, 
        "status": status # "online" o "offline"
    })
    
    # Obtenemos todos los websockets de todas las sesiones
    all_websockets = [ws for ws, uid in CLIENTS_BY_SESSION_ID.values()]
    if all_websockets:
        # Enviamos el mensaje a todos en paralelo
        tasks = [ws.send(message) for ws in all_websockets]
        await asyncio.gather(*tasks, return_exceptions=True)

# --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (register_client) ▼▼▼ ---
async def register_client(websocket, user_id, session_id):
    """Añade un cliente a los diccionarios de seguimiento."""
    
    # 1. Comprobar si era la primera conexión de este usuario
    is_first_connection = user_id not in CLIENTS_BY_USER_ID or not CLIENTS_BY_USER_ID[user_id]

    # 2. Guardar por ID de sesión (para búsqueda rápida)
    CLIENTS_BY_SESSION_ID[session_id] = (websocket, user_id)
    
    # 3. Guardar por ID de usuario (para expulsiones y estado)
    if user_id not in CLIENTS_BY_USER_ID:
        CLIENTS_BY_USER_ID[user_id] = set()
    CLIENTS_BY_USER_ID[user_id].add(websocket)
    
    # 4. (Lógica de comunidad eliminada)
    
    logging.info(f"[WS] Cliente autenticado: user_id={user_id}, session_id={session_id[:5]}... Conexiones totales: {len(CLIENTS_BY_SESSION_ID)}")
    
    # 5. Si es la primera vez que se conecta, notificar a todos que está "online"
    if is_first_connection:
        logging.info(f"[WS] user_id={user_id} ahora está ONLINE.")
        await broadcast_presence_update(user_id, "online")
# --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (register_client) ▲▲▲ ---

# --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (unregister_client) ▼▼▼ ---
async def unregister_client(session_id):
    """Elimina un cliente de los diccionarios."""
    
    # 1. Eliminar por ID de sesión
    ws_tuple = CLIENTS_BY_SESSION_ID.pop(session_id, None)
    if not ws_tuple:
        return # Ya fue eliminado

    websocket, user_id = ws_tuple

    # 2. Eliminar de la lista de ID de usuario
    if user_id in CLIENTS_BY_USER_ID:
        CLIENTS_BY_USER_ID[user_id].remove(websocket)
        
        # 3. Comprobar si era la *última* conexión de este usuario
        if not CLIENTS_BY_USER_ID[user_id]:
            del CLIENTS_BY_USER_ID[user_id]
            # 4. Si era la última, notificar a todos que está "offline"
            logging.info(f"[WS] user_id={user_id} ahora está OFFLINE.")
            await broadcast_presence_update(user_id, "offline")
            
            # 5. Notificar al backend PHP para que actualice "last_seen"
            await notify_backend_of_offline(user_id)
            
    # 6. (Lógica de comunidad eliminada)
            
    logging.info(f"[WS] Cliente desconectado: session_id={session_id[:5]}... Conexiones totales: {len(CLIENTS_BY_SESSION_ID)}")
# --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (unregister_client) ▲▲▲ ---

# --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (ws_handler) ▼▼▼ ---
async def ws_handler(websocket):
    """Manejador principal de conexiones WebSocket."""
    session_id = None
    user_id = 0
    # (community_ids eliminada)
    try:
        # --- Esperar mensaje de autenticación ---
        message_json = await websocket.recv()
        data = json.loads(message_json)
        
        # 1. Leer los nuevos campos de autenticación
        if (data.get("type") == "auth" and 
            data.get("user_id") and 
            data.get("session_id")):
            
            user_id = int(data["user_id"])
            session_id = data["session_id"]
            # (community_ids eliminada)
            
            if user_id > 0:
                await register_client(websocket, user_id, session_id)
            else:
                raise Exception("ID de usuario no válido")
        else:
            raise Exception(f"Autenticación fallida. Faltan campos. Recibido: {data}")
        
        # Mantener la conexión abierta para escuchar
        async for message_json in websocket:
            try:
                data = json.loads(message_json)
                
                # --- Lógica de "Escribiendo..." (Solo para DMs) ---
                if data.get("type") == "typing_start" and data.get("recipient_id"):
                    recipient_id = int(data["recipient_id"])
                    websockets_to_notify = CLIENTS_BY_USER_ID.get(recipient_id)
                    if websockets_to_notify:
                        payload = json.dumps({"type": "typing_start", "sender_id": user_id})
                        tasks = [ws.send(payload) for ws in websockets_to_notify]
                        await asyncio.gather(*tasks, return_exceptions=True)
                        
                elif data.get("type") == "typing_stop" and data.get("recipient_id"):
                    recipient_id = int(data["recipient_id"])
                    websockets_to_notify = CLIENTS_BY_USER_ID.get(recipient_id)
                    if websockets_to_notify:
                        payload = json.dumps({"type": "typing_stop", "sender_id": user_id})
                        tasks = [ws.send(payload) for ws in websockets_to_notify]
                        await asyncio.gather(*tasks, return_exceptions=True)
                
                # --- ▼▼▼ INICIO DE BLOQUE AÑADIDO (admin_get_count) ▼▼▼ ---
                elif data.get("type") == "admin_get_count":
                    # Un admin (o cualquier cliente) solicita el conteo actual
                    count = len(CLIENTS_BY_SESSION_ID)
                    logging.info(f"[WS] Cliente user_id={user_id} solicitó conteo. Respondiendo: {count}")
                    # Responder solo a este cliente con un mensaje 'user_count'
                    # que socket-service.js ya sabe cómo manejar.
                    payload = json.dumps({"type": "user_count", "count": count})
                    await websocket.send(payload)
                # --- ▲▲▲ FIN DE BLOQUE AÑADIDO ▲▲▲ ---

            except Exception as e:
                logging.warning(f"[WS] Error al procesar mensaje de cliente (user_id={user_id}): {e}")

    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        if session_id:
            # unregister_client usará el session_id para encontrar el (ws, user_id)
            await unregister_client(session_id)
# --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (ws_handler) ▲▲▲ ---


async def http_handler_count(request):
    """Devuelve el conteo de usuarios (endpoint público)."""
    count = len(CLIENTS_BY_SESSION_ID)
    logging.info(f"[HTTP-COUNT] Solicitud de conteo recibida. Respondiendo: {count}")
    response_data = {"active_users": count}
    return web.json_response(response_data)

async def http_handler_get_online_users(request):
    """Devuelve una lista de IDs de usuarios actualmente conectados."""
    try:
        online_user_ids = list(CLIENTS_BY_USER_ID.keys())
        logging.info(f"[HTTP-ONLINE] Solicitud de usuarios en línea. Respondiendo: {len(online_user_ids)} usuarios.")
        return web.json_response({"status": "ok", "online_users": online_user_ids})
    except Exception as e:
        logging.error(f"[HTTP-ONLINE] Error al obtener usuarios en línea: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=500)

async def http_handler_kick(request):
    """Recibe una orden de expulsión desde PHP (endpoint interno)."""
    if request.method != 'POST':
        return web.Response(status=405) 

    try:
        data = await request.json()
        user_id = int(data.get("user_id"))
        exclude_session_id = data.get("exclude_session_id")

        if not user_id or not exclude_session_id:
            raise ValueError("Faltan user_id o exclude_session_id")
            
        logging.info(f"[HTTP-KICK] Recibida orden de expulsión para user_id={user_id}, excluyendo session_id={exclude_session_id[:5]}...")

        websockets_to_kick = CLIENTS_BY_USER_ID.get(user_id)
        if not websockets_to_kick:
            logging.info(f"[HTTP-KICK] No se encontraron conexiones activas para user_id={user_id}.")
            return web.json_response({"status": "ok", "kicked": 0})
        
        kick_message = json.dumps({"type": "force_logout"})
        tasks = []
        kicked_count = 0
        
        for ws in list(websockets_to_kick):
            current_session_id = None
            for sid, (w, uid) in CLIENTS_BY_SESSION_ID.items():
                if w == ws:
                    current_session_id = sid
                    break
            
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

async def http_handler_kick_bulk(request):
    """Recibe una orden de expulsión masiva desde PHP (para mantenimiento)."""
    if request.method != 'POST':
        return web.Response(status=405) 

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
    """Recibe una orden de actualización de estado desde PHP (endpoint interno)."""
    if request.method != 'POST':
        return web.Response(status=405) 

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


async def broadcast_messaging_status_update(status_payload_json):
    """Notifica a TODOS los clientes conectados sobre un cambio de estado del chat."""
    
    logging.info(f"[HTTP-BROADCAST] Retransmitiendo estado de mensajería a todos los clientes: {status_payload_json}")
    
    all_websockets = [ws for ws, uid in CLIENTS_BY_SESSION_ID.values()]
    if all_websockets:
        tasks = [ws.send(status_payload_json) for ws in all_websockets]
        await asyncio.gather(*tasks, return_exceptions=True)

async def http_handler_broadcast_messaging_status(request):
    """Recibe una orden de PHP y la retransmite a todos los clientes."""
    if request.method != 'POST':
        return web.Response(status=405)

    try:
        data = await request.json()
        status = data.get("status")
        if status not in ("enabled", "disabled"):
            raise ValueError("Estado no válido. Debe ser 'enabled' o 'disabled'.")
            
        logging.info(f"[HTTP-BROADCAST] Recibida orden de estado de mensajería: {status}")

        payload_to_broadcast = json.dumps({
            "type": "messaging_status_update",
            "status": status
        })
        
        asyncio.create_task(broadcast_messaging_status_update(payload_to_broadcast))

        return web.json_response({"status": "ok", "message": "Broadcast iniciado"})

    except Exception as e:
        logging.error(f"[HTTP-BROADCAST] Error al procesar broadcast: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)

async def http_handler_notify_user(request):
    """Recibe una notificación genérica (amistad, chat, etc.) y la reenvía al target_user_id."""
    if request.method != 'POST':
        return web.Response(status=405)

    try:
        data = await request.json()
        target_user_id = int(data.get("target_user_id"))
        payload_data = data.get("payload") 

        if not target_user_id or not payload_data:
            raise ValueError("Faltan target_user_id o payload")
            
        logging.info(f"[HTTP-NOTIFY] Recibida notificación para user_id={target_user_id} (Tipo: {payload_data.get('type')})")

        websockets_to_notify = CLIENTS_BY_USER_ID.get(target_user_id)
        if not websockets_to_notify:
            logging.info(f"[HTTP-NOTIFY] No se encontraron conexiones activas para user_id={target_user_id}.")
            return web.json_response({"status": "ok", "notified": 0})
        
        message_json = json.dumps(payload_data)
        
        tasks = [ws.send(message_json) for ws in list(websockets_to_notify)]
        notified_count = 0
        
        if tasks:
            await asyncio.gather(*tasks, return_exceptions=True)
            notified_count = len(tasks)

        return web.json_response({"status": "ok", "notified": notified_count})

    except Exception as e:
        logging.error(f"[HTTP-NOTIFY] Error al procesar notificación: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)

# --- ▼▼▼ INICIO DE MODIFICACIÓN (Función eliminada) ▼▼▼ ---
# (http_handler_notify_community eliminada)
# --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


async def run_ws_server():
    """Inicia y mantiene vivo el servidor WebSocket."""
    logging.info(f"[WS] Iniciando servidor WebSocket en ws://0.0.0.0:8765")
    async with websockets.serve(ws_handler, "0.0.0.0", 8765):
        await asyncio.Event().wait() 

async def run_http_server():
    """Inicia y mantiene vivo el servidor HTTP."""
    http_app = web.Application()
    
    http_app.router.add_get("/count", http_handler_count)
    http_app.router.add_get("/get-online-users", http_handler_get_online_users) 
    
    http_app.router.add_post("/kick", http_handler_kick) 
    http_app.router.add_post("/update-status", http_handler_update_status) 
    http_app.router.add_post("/kick-bulk", http_handler_kick_bulk)
    http_app.router.add_post("/notify-user", http_handler_notify_user)
    
    # --- ▼▼▼ INICIO DE MODIFICACIÓN (AÑADIR RUTAS) ▼▼▼ ---
    http_app.router.add_post("/broadcast-messaging-status", http_handler_broadcast_messaging_status)
    # (Ruta /notify-community eliminada)
    # --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
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


if __name__ == "__main__":
    try:
        asyncio.run(start_servers())
    except KeyboardInterrupt:
        logging.info("Servidor detenido por el usuario.")
    except Exception as e:
        logging.critical(f"Error fatal en el servidor: {e}")