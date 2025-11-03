import asyncio
import websockets
import json
import logging
from aiohttp import web

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# Set global para almacenar conexiones únicas
ACTIVE_CONNECTIONS = set()

# --- MANEJADOR DE WEBSOCKET (Sin cambios) ---
async def broadcast_count():
    if ACTIVE_CONNECTIONS:
        count = len(ACTIVE_CONNECTIONS)
        message = json.dumps({"type": "user_count", "count": count})
        tasks = [ws.send(message) for ws in ACTIVE_CONNECTIONS]
        await asyncio.gather(*tasks, return_exceptions=True)

async def ws_handler(websocket):
    try:
        ACTIVE_CONNECTIONS.add(websocket)
        logging.info(f"[WS] Cliente conectado. Total: {len(ACTIVE_CONNECTIONS)}")
        await broadcast_count()

        async for message in websocket:
            pass
            
    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        ACTIVE_CONNECTIONS.remove(websocket)
        logging.info(f"[WS] Cliente desconectado. Total: {len(ACTIVE_CONNECTIONS)}")
        await broadcast_count()

# --- MANEJADOR DE HTTP (Sin cambios) ---
async def http_handler(request):
    count = len(ACTIVE_CONNECTIONS)
    logging.info(f"[HTTP] Solicitud de conteo recibida. Respondiendo: {count}")
    response_data = {"active_users": count}
    return web.json_response(response_data)

# --- ▼▼▼ INICIO DE LA CORRECCIÓN ▼▼▼ ---

async def run_ws_server():
    """
    Inicia y mantiene vivo el servidor WebSocket.
    """
    logging.info(f"[WS] Iniciando servidor WebSocket en ws://127.0.0.1:8765")
    async with websockets.serve(ws_handler, "127.0.0.1", 8765):
        await asyncio.Event().wait()  # Correr indefinidamente

async def run_http_server():
    """
    Inicia y mantiene vivo el servidor HTTP.
    """
    http_app = web.Application()
    http_app.router.add_get("/count", http_handler)
    http_runner = web.AppRunner(http_app)
    await http_runner.setup()
    http_site = web.TCPSite(http_runner, "127.0.0.1", 8766)
    logging.info(f"[HTTP] Iniciando servidor HTTP en http://127.0.0.1:8766")
    await http_site.start()
    await asyncio.Event().wait() # Correr indefinidamente

async def start_servers():
    """
    Inicia ambos servidores (WS y HTTP) en paralelo.
    """
    ws_task = asyncio.create_task(run_ws_server())
    http_task = asyncio.create_task(run_http_server())
    
    await asyncio.gather(ws_task, http_task)

# --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

# --- Ejecución Principal (Modificada) ---
if __name__ == "__main__":
    try:
        asyncio.run(start_servers())
    except KeyboardInterrupt:
        logging.info("Servidor detenido por el usuario.")
    except Exception as e:
        logging.critical(f"Error fatal en el servidor: {e}")