import asyncio
import websockets
# import threading  <-- Ya no se necesita
import json
import logging
# from http.server import BaseHTTPRequestHandler, HTTPServer  <-- Ya no se necesita

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# Set global para almacenar conexiones únicas
ACTIVE_CONNECTIONS = set()

# --- ▼▼▼ NUEVA FUNCIÓN PARA NOTIFICAR A TODOS ▼▼▼ ---
async def broadcast_count():
    """
    Envía el conteo actual de usuarios a todas las conexiones activas.
    """
    if ACTIVE_CONNECTIONS:  # Solo enviar si hay alguien conectado
        count = len(ACTIVE_CONNECTIONS)
        message = json.dumps({"type": "user_count", "count": count})
        # Prepara una lista de tareas de envío
        tasks = [ws.send(message) for ws in ACTIVE_CONNECTIONS]
        # Ejecuta todas las tareas de envío en paralelo
        await asyncio.gather(*tasks, return_exceptions=True)

async def ws_handler(websocket):
    """
    Maneja las conexiones WebSocket entrantes.
    """
    try:
        ACTIVE_CONNECTIONS.add(websocket)
        logging.info(f"[WS] Cliente conectado. Total: {len(ACTIVE_CONNECTIONS)}")
        await broadcast_count()  # <--- NOTIFICAR AL CONECTARSE

        # Mantener la conexión abierta
        async for message in websocket:
            pass
            
    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        ACTIVE_CONNECTIONS.remove(websocket)
        logging.info(f"[WS] Cliente desconectado. Total: {len(ACTIVE_CONNECTIONS)}")
        await broadcast_count()  # <--- NOTIFICAR AL DESCONECTARSE

async def start_ws_server():
    """
    Inicia el servidor WebSocket en el puerto 8765.
    """
    host = "127.0.0.1"
    port = 8765
    logging.info(f"[WS] Iniciando servidor WebSocket en ws://{host}:{port}")
    async with websockets.serve(ws_handler, host, port):
        await asyncio.Future()  # Correr indefinidamente

# --- Servidor HTTP (Reporta el conteo) ---
# --- TODO ESTE BLOQUE DE HTTP YA NO ES NECESARIO ---
# class CountHTTPRequestHandler(BaseHTTPRequestHandler):
#     ...
# def start_http_server():
#     ...

# --- Ejecución Principal ---

if __name__ == "__main__":
    # 1. Iniciar el servidor HTTP en un hilo daemon  <-- ELIMINADO
    # http_thread = threading.Thread(target=start_http_server, daemon=True)
    # http_thread.start()
    
    # 2. Iniciar el servidor WebSocket (asyncio) en el hilo principal
    try:
        asyncio.run(start_ws_server())
    except KeyboardInterrupt:
        logging.info("Servidor detenido por el usuario.")
    except Exception as e:
        logging.critical(f"Error fatal en el servidor WebSocket: {e}")