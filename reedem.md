Documentación: Sistema de Chat (column-reverse)
Este documento explica cómo funciona el sistema de carga de mensajes del chat en ProjectGenesis. El objetivo principal de este sistema es evitar el "salto" del scroll al cargar la página, asegurando que el usuario siempre aterrice en el mensaje más reciente sin parpadeos.

Para lograr esto, usamos una combinación de flex-direction: column-reverse en CSS y una lógica de PHP/JavaScript específica.

💡 El Concepto Clave: column-reverse
La magia del sistema reside en flex-direction: column-reverse;.

Orden del HTML (Fuente): Hacemos que PHP imprima los mensajes en el HTML desde el más nuevo hasta el más antiguo.

Orden Visual (CSS): column-reverse voltea este orden visualmente. El primer elemento del HTML (1. Mensaje Nuevo) se muestra abajo, y el último (3. Mensaje Antiguo) se muestra arriba.

El Beneficio: El navegador considera que el "inicio" del scroll (scrollTop = 0) es el fondo del chat. Al cargar la página, el navegador se posiciona automáticamente en scrollTop = 0, mostrando los mensajes más nuevos sin necesidad de ningún script de JavaScript que haga "scroll al fondo".

🔄 El Flujo de Carga (Paso a Paso)
Aquí se explica cómo funciona cada parte del sistema.

1. Carga Inicial (PHP)
Archivos: includes/sections/main/home.php

Consulta SQL: Se piden los 50 mensajes más recientes usando ORDER BY m.created_at DESC LIMIT 50.

Procesamiento PHP: ¡No se usa array_reverse! Los mensajes se imprimen en el foreach en el mismo orden que vienen de la base de datos (Nuevo -> Antiguo).

Atributos de Datos: El contenedor principal del chat (#chat-history-container) guarda dos datos clave:

data-oldest-message-id: El ID del mensaje más antiguo cargado (el último del LIMIT 50).

data-has-more-history: Se pone en true si la carga inicial trajo 50 mensajes (asumiendo que hay más).

2. Mensajes en Vivo (JavaScript)
Archivos: assets/js/modules/chat-manager.js (Función: renderIncomingMessage)

Lógica: Cuando llega un mensaje nuevo por WebSocket, se crea la burbuja de chat.

Acción DOM: Se usa chatHistory.prepend(bubble);.

¿Por qué prepend? Porque en column-reverse, "pre-poner" (añadir al inicio del HTML) hace que el elemento aparezca visualmente al fondo del chat.

Scroll: Si el usuario ya estaba en el fondo (scrollTop < 100), se re-ajusta scrollTop = 0 para mantenerlo abajo.

3. Carga de Historial (Lazy Loading)
Esta es la parte más compleja y la que acabamos de corregir.

Archivos: chat-manager.js (Funciones: initChatManager, loadMoreHistory) y api/chat_handler.php (Acción: load-history)

Disparador (Trigger): En initChatManager, un addEventListener('scroll', ...) monitorea el contenedor.

Detección (El Bug Corregido): Se activa la carga cuando el scroll llega al tope visual.

En column-reverse, el tope visual (mensajes antiguos) está al final de la barra de scroll.

La condición correcta es: chatHistory.scrollTop >= (chatHistory.scrollHeight - chatHistory.clientHeight - 200).

Llamada a la API:

Se llama a api/chat_handler.php con action: 'load-history'.

Se envía el group_uuid y el before_id (que es el oldestMessageId que guardamos).

Lógica de API (chat_handler.php):

La API busca mensajes WHERE m.id < ? (mensajes antes de ese ID) y ORDER BY m.created_at DESC.

Devuelve los 20 mensajes siguientes (de nuevo, en orden Nuevo -> Antiguo).

Actualización del DOM (El "Truco" del Scroll):

Guardar Posición: Se guardan oldScrollHeight y oldScrollTop antes de tocar el DOM.

Añadir Mensajes: Se usa chatHistory.appendChild(bubble); para cada mensaje antiguo.

¿Por qué appendChild? Porque "a-penar" (añadir al final del HTML) hace que los elementos aparezcan visualmente en el tope del chat (column-reverse).

Restaurar Posición: Se calcula la heightAdded (la altura de los nuevos mensajes) y se re-ajusta el scroll: chatHistory.scrollTop = oldScrollTop + heightAdded;. Esto mantiene al usuario viendo el mismo mensaje que tenía en pantalla, evitando el "salto".