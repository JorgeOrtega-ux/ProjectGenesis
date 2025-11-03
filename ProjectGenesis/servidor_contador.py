import asyncio
import websockets
import threading
import json
import logging
from http.server import BaseHTTPRequestHandler, HTTPServer

# Configuración de logging
logging.basicConfig(level=logging.INFO, format='[%(asctime)s] [%(levelname)s] %(message)s')

# --- Servidor WebSocket (Maneja conexiones activas) ---

# Set global para almacenar conexiones únicas
ACTIVE_CONNECTIONS = set()

# --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
# La firma de la función ahora solo acepta 'websocket'
async def ws_handler(websocket):
# --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    """
    Maneja las conexiones WebSocket entrantes.
    Añade un cliente al set cuando se conecta y lo elimina cuando se desconecta.
    """
    ACTIVE_CONNECTIONS.add(websocket)
    logging.info(f"[WS] Cliente conectado. Total: {len(ACTIVE_CONNECTIONS)}")
    try:
        # Mantener la conexión abierta mientras el cliente esté conectado
        async for message in websocket:
            # Podrías implementar un sistema de 'ping/pong' aquí si es necesario
            pass
    except websockets.exceptions.ConnectionClosed:
        logging.info("[WS] Conexión cerrada (normal).")
    except Exception as e:
        logging.error(f"[WS] Error en la conexión: {e}")
    finally:
        # Asegurarse de eliminar la conexión del set al desconectar
        ACTIVE_CONNECTIONS.remove(websocket)
        logging.info(f"[WS] Cliente desconectado. Total: {len(ACTIVE_CONNECTIONS)}")

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

class CountHTTPRequestHandler(BaseHTTPRequestHandler):
    """
    Manejador HTTP simple. Responde solo a GET /count.
    """
    def do_GET(self):
        if self.path == '/count':
            try:
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                
                # Accede al set global para obtener el conteo
                count = len(ACTIVE_CONNECTIONS)
                response_data = json.dumps({"active_users": count})
                
                self.wfile.write(response_data.encode('utf-8'))
            except Exception as e:
                logging.error(f"[HTTP] Error al procesar /count: {e}")
                self.send_response(500)
                self.end_headers()
                self.wfile.write(b"Internal Server Error")
        else:
            self.send_response(404)
            self.end_headers()
            self.wfile.write(b"Not Found")

    def log_message(self, format, *args):
        # Silenciar los logs de solicitudes HTTP para no saturar la consola
        return

def start_http_server():
    """
    Inicia el servidor HTTP en un hilo separado en el puerto 8766.
    """
    host = "127.0.0.1"
    port = 8766
    
    try:
        httpd = HTTPServer((host, port), CountHTTPRequestHandler)
        logging.info(f"[HTTP] Iniciando servidor HTTP en http://{host}:{port}/count")
        httpd.serve_forever()
    except Exception as e:
        logging.critical(f"[HTTP] No se pudo iniciar el servidor HTTP: {e}")

# --- Ejecución Principal ---

if __name__ == "__main__":
    # 1. Iniciar el servidor HTTP en un hilo daemon
    # (daemon=True asegura que el hilo muera cuando el script principal termine)
    http_thread = threading.Thread(target=start_http_server, daemon=True)
    http_thread.start()
    
    # 2. Iniciar el servidor WebSocket (asyncio) en el hilo principal
    try:
        asyncio.run(start_ws_server())
    except KeyboardInterrupt:
        logging.info("Servidor detenido por el usuario.")
    except Exception as e:
        logging.critical(f"Error fatal en el servidor WebSocket: {e}")