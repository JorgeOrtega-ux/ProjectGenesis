# Explicación del Flujo de Cierre de Sesión

Este documento detalla los dos métodos de cierre de sesión implementados en el proyecto: el "Cierre Manual" (para un solo dispositivo) y el "Cierre en Todos los Dispositivos", incluyendo sus respectivas validaciones del lado del cliente y del servidor.

## 1. Cierre de Sesión Manual

Este es el flujo estándar cuando un usuario hace clic en el botón "Cerrar sesion" del menú principal. Es una acción de navegación (`GET`) protegida por un token CSRF.

### Lado del Cliente (JavaScript)

El proceso es manejado por `assets/js/main-controller.js`.

1.  **Captura del Evento:** El script detecta un clic en un elemento con `data-action="logout"`.
2.  **UX (Spinner):** Muestra un spinner de carga en el botón para dar retroalimentación visual.
3.  **Validación de UX (Conexión):**
    * Se ejecuta la función `checkNetwork()`.
    * Esta comprueba `navigator.onLine` para ver si el navegador cree que tiene conexión a internet.
    * **Si no hay conexión:** La promesa se rechaza, se muestra una alerta de error (ej. "No hay conexión a internet") y el proceso se detiene.
4.  **Preparación de Seguridad (Token CSRF):**
    * Si hay conexión, el script obtiene el **token CSRF** (Cross-Site Request Forgery) de la variable global `window.csrfToken`.
5.  **Acción (Redirección):**
    * El script redirige la página completa a la URL de cierre de sesión, adjuntando el token en la URL: `window.location.href = .../config/logout.php?csrf_token=...`.

### Lado del Servidor (PHP)

El navegador carga el archivo `config/logout.php`.

1.  **Validación de Seguridad (Token CSRF):**
    * El script incluye `config.php` para iniciar la sesión y obtener las funciones de validación.
    * Obtiene el token enviado por el cliente desde la URL (`$_GET['csrf_token']`).
    * Utiliza `validateCsrfToken()` para comparar (de forma segura) el token de la URL con el token almacenado en `$_SESSION['csrf_token']`.
    * **Si la validación falla:** El script se detiene con el mensaje "Error de seguridad. Token inválido.". Esto previene que un sitio malicioso fuerce el cierre de sesión del usuario (ataque CSRF).
2.  **Acción (Destrucción de Sesión):**
    * Si el token es válido, el script vacía el array `$_SESSION`.
    * Llama a `session_destroy()` para eliminar la sesión del servidor.
3.  **Redirección Final:** Redirige al usuario a la página de `/login`.

---

## 2. Cierre de Sesión en Todos los Dispositivos

Este es un proceso más complejo que invalida todas las sesiones activas (excepto la actual, momentáneamente) modificando un token central en la base de datos. Es una llamada `POST` a la API.

### Lado del Cliente (JavaScript)

El flujo comienza en `assets/js/settings-manager.js`.

1.  **Captura del Evento:** El script detecta un clic en el botón de confirmación `#logout-all-confirm` dentro del modal.
2.  **UX (Spinner):** Muestra un spinner en el botón de confirmación.
3.  **Llamada a la API:** Envía una solicitud `POST` al servidor usando la función `callSettingsApi` (definida en `api-service.js`) con la acción `action: 'logout-all-devices'`.
4.  **Validaciones (en `api-service.js`):**
    * **Seguridad (Token CSRF):** La función `_post` añade automáticamente el `window.csrfToken` a la solicitud `FormData` antes de enviarla.
    * **UX (Conexión):** La llamada `fetch` está dentro de un `try...catch`. Si falla (ej. sin WiFi), el bloque `catch` lo captura y devuelve un objeto de error (`{ success: false, message: 'No se pudo conectar...' }`).
5.  **Respuesta de la API:**
    * **Si falla (ej. sin WiFi):** `settings-manager.js` recibe el error y muestra una alerta (`window.showAlert`) con el mensaje devuelto por `api-service.js`.
    * **Si tiene éxito:** El script muestra una alerta "Cerrando todas las sesiones..." y luego ejecuta el flujo de **Cierre de Sesión Manual** (Paso 5 de ese flujo) para destruir la sesión *actual*.

### Lado del Servidor (PHP)

Este proceso tiene dos partes en el servidor:

#### Parte A: La solicitud de invalidación (en `api/settings_handler.php`)

Este script recibe la llamada `POST` del cliente.

1.  **Validación de Autenticación:** Comprueba que el usuario ha iniciado sesión (`isset($_SESSION['user_id'])`). Si no, devuelve un error "Acceso denegado".
2.  **Validación de Seguridad (Token CSRF):** Comprueba el `$_POST['csrf_token']` usando `validateCsrfToken()`. Si falla, devuelve un error de seguridad.
3.  **Acción (Invalidación Central):**
    * Genera un **nuevo token de autenticación** (`$newAuthToken`) usando `bin2hex(random_bytes(32))`.
    * Actualiza la base de datos: `UPDATE users SET auth_token = ? WHERE id = ?`. Esto hace que el token antiguo que tienen las demás sesiones sea inválido.
    * Actualiza la sesión *actual* del usuario (`$_SESSION['auth_token'] = $newAuthToken`) para que no se invalide a sí misma.
    * Devuelve `{ success: true }` al cliente.

#### Parte B: La expulsión (en `index.php` en *otros* dispositivos)

La próxima vez que cualquier *otro* dispositivo del usuario cargue una página (ej. `index.php`), ocurre lo siguiente:

1.  **Consulta de Datos:** El script `index.php` consulta la base de datos para obtener los datos más frescos del usuario, incluyendo el **`auth_token`** (que ahora es el *nuevo* token).
2.  **Validación de Autenticación:**
    * El script compara el token de la base de datos (`$dbAuthToken`) con el token almacenado en la sesión de *ese* dispositivo (`$sessionAuthToken`).
    * Como el dispositivo tiene el token *viejo* y la base de datos tiene el *nuevo*, la comprobación `hash_equals` falla.
3.  **Acción (Expulsión):**
    * El script interpreta esto como una sesión inválida.
    * Llama a `session_unset()` y `session_destroy()` para esa sesión específica.
    * Redirige a ese dispositivo a la página de `/login`.