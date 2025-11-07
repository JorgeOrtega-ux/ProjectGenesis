# RUTA: socket_server.py
# (CÓDIGO MODIFICADO PARA CHAT POR GRUPOS)

import asyncio
import websockets
import json
import logging
from aiohttp import web

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# --- ▼▼▼ ESTRUCTURAS DE DATOS MODIFICADAS ▼▼▼ ---

# Almacena los metadatos de una conexión
# { websocket_obj: {"user_id": 123, "session_id": "abc...", "current_group_uuid": "uuid..."} }
CLIENTS = {}

# Mapea un user_id a un set de sus websockets (para presencia)
# { 123: {websocket1, websocket2} }
CLIENTS_BY_USER_ID = {}

# Mapea un group_uuid a un set de websockets (para chat)
# { "uuid...": {websocket1, websocket3} }
CLIENTS_BY_GROUP_ID = {}

# --- ▲▲▲ ESTRUCTURAS DE DATOS MODIFICADAS ▲▲▲ ---


async def broadcast_user_status(user_id, status, exclude_ws=None):
    """Anuncia el cambio de estado de un usuario a todos los clientes."""
    if not user_id:
        return
        
    message = json.dumps({"type": "user_status", "user_id": user_id, "status": status})
    logging.info(f"[WS-STATUS] Transmitiendo: user_id={user_id} está {status}")
    
    tasks = []
    for ws in CLIENTS:
        if ws != exclude_ws:
            tasks.append(ws.send(message))
            
    if tasks:
        await asyncio.gather(*tasks, return_exceptions=True)


async def broadcast_count():
    """Transmite el conteo total."""
    count = len(CLIENTS) 
    message = json.dumps({"type": "user_count", "count": count})
    
    tasks = []
    for ws in CLIENTS:
        tasks.append(ws.send(message))

    if tasks:
        await asyncio.gather(*tasks, return_exceptions=True)


async def register_client(websocket, user_id, session_id):
    """Añade un cliente y anuncia su estado."""
    
    # 1. Almacenar metadatos del cliente
    CLIENTS[websocket] = {
        "user_id": user_id,
        "session_id": session_id,
        "current_group_uuid": None
    }
    
    # 2. Comprobar si es la primera conexión de este usuario
    is_first_connection = user_id not in CLIENTS_BY_USER_ID or not CLIENTS_BY_USER_ID[user_id]
    
    # 3. Registrar en el pool de presencia
    if user_id not in CLIENTS_BY_USER_ID:
        CLIENTS_BY_USER_ID[user_id] = set()
    CLIENTS_BY_USER_ID[user_id].add(websocket)
    
    logging.info(f"[WS] Cliente autenticado: user_id={user_id}, session_id={session_id[:5]}... Total: {len(CLIENTS)}")

    # 4. Enviar al nuevo cliente la lista de todos los que ya están conectados
    try:
        online_ids = [uid for uid, ws_set in CLIENTS_BY_USER_ID.items() if ws_set]
        presence_message = json.dumps({"type": "presence_list", "user_ids": online_ids})
        await websocket.send(presence_message)
    except Exception as e:
        logging.error(f"[WS-PRESENCE] Error al enviar lista de presencia a {session_id[:5]}...: {e}")

    # 5. Si era su primera conexión, anunciar a todos que está "online"
    if is_first_connection:
        await broadcast_user_status(user_id, "online", exclude_ws=websocket)

    # 6. Transmitir el nuevo conteo total
    await broadcast_count()


async def unregister_client(websocket):
    """Elimina un cliente y anuncia su estado si es necesario."""
    client_data = CLIENTS.pop(websocket, None)
    if not client_data:
        return 

    user_id = client_data.get("user_id")
    session_id = client_data.get("session_id", "unknown")
    current_group_uuid = client_data.get("current_group_uuid")
    
    was_last_connection = False

    # 1. Eliminar de la pool de presencia
    if user_id and user_id in CLIENTS_BY_USER_ID:
        ws_set = CLIENTS_BY_USER_ID[user_id]
        if websocket in ws_set:
            ws_set.remove(websocket)
            if not ws_set: # Si el set de este usuario quedó vacío
                was_last_connection = True
                del CLIENTS_BY_USER_ID[user_id]

    # 2. Eliminar de la pool de grupos
    if current_group_uuid and current_group_uuid in CLIENTS_BY_GROUP_ID:
        group_set = CLIENTS_BY_GROUP_ID[current_group_uuid]
        if websocket in group_set:
            group_set.remove(websocket)
            if not group_set:
                del CLIENTS_BY_GROUP_ID[current_group_uuid]
    
    logging.info(f"[WS] Cliente desconectado: session_id={session_id[:5]}... Total: {len(CLIENTS)}")
    
    # 3. Si era la última conexión, anunciar a todos que está "offline"
    if was_last_connection:
        await broadcast_user_status(user_id, "offline")

    # 4. Transmitir el nuevo conteo total
    await broadcast_count()


# --- ▼▼▼ NUEVA FUNCIÓN ▼▼▼ ---
async def handle_join_group(websocket, group_uuid):
    """Mueve un websocket a una nueva sala de chat."""
    client_data = CLIENTS.get(websocket)
    if not client_data:
        return

    old_group_uuid = client_data.get("current_group_uuid")
    user_id = client_data.get("user_id")

    # 1. Salir del grupo anterior (si existe)
    if old_group_uuid and old_group_uuid in CLIENTS_BY_GROUP_ID:
        group_set = CLIENTS_BY_GROUP_ID[old_group_uuid]
        if websocket in group_set:
            group_set.remove(websocket)
            if not group_set:
                del CLIENTS_BY_GROUP_ID[old_group_uuid]
        logging.info(f"[WS-CHAT] user_id={user_id} salió del grupo {old_group_uuid[:6]}...")

    # 2. Unirse al nuevo grupo (si no es nulo)
    if group_uuid:
        if group_uuid not in CLIENTS_BY_GROUP_ID:
            CLIENTS_BY_GROUP_ID[group_uuid] = set()
        CLIENTS_BY_GROUP_ID[group_uuid].add(websocket)
        logging.info(f"[WS-CHAT] user_id={user_id} se unió al grupo {group_uuid[:6]}...")

    # 3. Actualizar el estado del cliente
    client_data["current_group_uuid"] = group_uuid

# --- ▲▲▲ NUEVA FUNCIÓN ▲▲▲ ---


async def message_handler(websocket, message_json):
    """Procesa mensajes JSON entrantes del cliente."""
    try:
        data = json.loads(message_json)
        msg_type = data.get("type")

        if msg_type == "auth" and data.get("user_id") and data.get("session_id"):
            user_id = int(data["user_id"])
            session_id = data["session_id"]
            
            if user_id > 0:
                await register_client(websocket, user_id, session_id)
            else:
                raise Exception("ID de usuario no válido")
        
        elif msg_type == "join_group":
            group_uuid = data.get("group_uuid") # Puede ser None (para salir)
            await handle_join_group(websocket, group_uuid)

        # (Se pueden añadir más tipos, como "typing_start", "typing_stop")
            
    except json.JSONDecodeError:
        logging.warning("[WS] Mensaje JSON malformado recibido.")
    except Exception as e:
        logging.error(f"[WS] Error procesando mensaje: {e}")
        # Forzar desconexión si la autenticación falla
        if "Autenticación fallida" in str(e):
            await websocket.close()


async def ws_handler(websocket):
    """Manejador principal de conexiones WebSocket."""
    try:
        async for message in websocket:
            await message_handler(websocket, message)

    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        await unregister_client(websocket)


#
# --- MANEJADORES HTTP ---
#

# --- ▼▼▼ NUEVO ENDPOINT HTTP /broadcast ▼▼▼ ---
async def http_handler_broadcast(request):
    """
    Recibe un mensaje de chat desde PHP y lo transmite al grupo correcto.
    Espera JSON: {"group_uuid": "...", "message_payload": "{...}"}
    """
    if request.method != 'POST':
        return web.Response(status=405)

    try:
        data = await request.json()
        group_uuid = data.get("group_uuid")
        message_payload_str = data.get("message_payload") # Esto es un STRING JSON

        if not group_uuid or not message_payload_str:
            raise ValueError("Faltan group_uuid o message_payload")
            
        logging.info(f"[HTTP-BROADCAST] Recibida orden de transmitir al grupo {group_uuid[:6]}...")
        
        # Re-validar el JSON interno
        try:
            json.loads(message_payload_str)
        except Exception:
             raise ValueError("message_payload no es un JSON válido")

        websockets_to_send = CLIENTS_BY_GROUP_ID.get(group_uuid, set())
        
        if not websockets_to_send:
            logging.info(f"[HTTP-BROADCAST] No hay clientes en el grupo {group_uuid[:6]}. No se envía nada.")
            return web.json_response({"status": "ok", "sent_to": 0})

        tasks = [ws.send(message_payload_str) for ws in websockets_to_send]
        
        if tasks:
            await asyncio.gather(*tasks, return_exceptions=True)

        logging.info(f"[HTTP-BROADCAST] Mensaje enviado a {len(tasks)} clientes en el grupo {group_uuid[:6]}.")
        return web.json_response({"status": "ok", "sent_to": len(tasks)})

    except Exception as e:
        logging.error(f"[HTTP-BROADCAST] Error al procesar broadcast: {e}")
        return web.json_response({"status": "error", "message": str(e)}, status=400)
# --- ▲▲▲ NUEVO ENDPOINT HTTP /broadcast ▲▲▲ ---


async def http_handler_count(request):
    """Devuelve el conteo de usuarios (endpoint público)."""
    count = len(CLIENTS)
    logging.info(f"[HTTP-COUNT] Solicitud de conteo recibida. Respondiendo: {count}")
    response_data = {"active_users": count}
    return web.json_response(response_data)

async def http_handler_kick(request):
    """
    Recibe una orden de expulsión desde PHP (endpoint interno).
    Espera un JSON: {"user_id": 123, "exclude_session_id": "abc..."}
    """
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
            client_data = CLIENTS.get(ws)
            current_session_id = client_data.get("session_id") if client_data else None
            
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
    """
    Recibe una orden de expulsión masiva desde PHP (para mantenimiento).
    Espera un JSON: {"user_ids": [1, 2, 3...]}
    """
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
    """
    Recibe una orden de actualización de estado desde PHP (endpoint interno).
    Espera un JSON: {"user_id": 123, "status": "suspended"}
    """
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


# --- Servidores ---
async def run_ws_server():
    """Inicia y mantiene vivo el servidor WebSocket."""
    logging.info(f"[WS] Iniciando servidor WebSocket en ws://0.0.0.0:8765")
    async with websockets.serve(ws_handler, "0.0.0.0", 8765, max_size=2**20): # Límite de 1MB por mensaje
        await asyncio.Event().wait() 

async def run_http_server():
    """Inicia y mantiene vivo el servidor HTTP."""
    http_app = web.Application()
    http_app.router.add_get("/count", http_handler_count)
    http_app.router.add_post("/kick", http_handler_kick) 
    http_app.router.add_post("/update-status", http_handler_update_status) 
    http_app.router.add_post("/kick-bulk", http_handler_kick_bulk)
    # --- ▼▼▼ NUEVA RUTA HTTP ▼▼▼ ---
    http_app.router.add_post("/broadcast", http_handler_broadcast)
    # --- ▲▲▲ NUEVA RUTA HTTP ▲▲▲ ---
    
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