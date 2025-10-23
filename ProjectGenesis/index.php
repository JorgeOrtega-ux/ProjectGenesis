<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">
    <link rel="stylesheet" type="text/css" href="assets/css/styles.css">
    <title>ProjectGenesis</title>
</head>

<body>
    <div class="page-wrapper">
        <div class="main-content">
            <div class="general-content">
                <div class="general-content-top">
                    <?php include 'includes/layouts/header.php'; ?>
                </div>
                <div class="general-content-bottom">
                    <?php include 'includes/modules/module-surface.php'; ?>
                    <div class="general-content-scrolleable"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // --- VARIABLES DE CONFIGURACIÓN ---

            /** * (true) = Se pueden abrir varios módulos a la vez.
             * (false) = Al abrir un módulo, se cerrarán los demás.
             */
            let allowMultipleActiveModules = false;

            /**
             * (true) = Cierra todos los módulos activos al hacer clic fuera de ellos.
             * (false) = Los módulos permanecen abiertos.
             */
            let closeOnClickOutside = true;

            /**
             * (true) = Cierra todos los módulos activos al presionar la tecla 'Escape'.
             * (false) = La tecla 'Escape' no hace nada.
             */
            let closeOnEscape = true;

            // --- FIN DE CONFIGURACIÓN ---


            const actionButtons = document.querySelectorAll('[data-action]');

            // Función reutilizable para desactivar todos los módulos activos
            const deactivateAllModules = (exceptionModule = null) => {
                document.querySelectorAll('[data-module].active').forEach(activeModule => {
                    if (activeModule !== exceptionModule) {
                        activeModule.classList.add('disabled');
                        activeModule.classList.remove('active');
                    }
                });
            };

            // Lógica para los botones de acción (data-action)
            actionButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.stopPropagation(); // Evita que el clic se propague al 'document'
                    const action = this.getAttribute('data-action');

                    if (action.startsWith('toggle')) {
                        let moduleName = action.substring(6); // "toggleModuleSurface" -> "ModuleSurface"
                        moduleName = moduleName.charAt(0).toLowerCase() + moduleName.slice(1); // "ModuleSurface" -> "moduleSurface"

                        const module = document.querySelector(`[data-module="${moduleName}"]`);

                        if (module) {
                            const isOpening = module.classList.contains('disabled');

                            // 1. Lógica de Múltiples Módulos
                            if (isOpening && !allowMultipleActiveModules) {
                                // Cierra todos los demás antes de abrir este
                                deactivateAllModules(module);
                            }

                            // Alterna el módulo actual
                            module.classList.toggle('disabled');
                            module.classList.toggle('active');
                        }
                    }
                });
            });

            // 2. Lógica de Clicar Fuera (closeOnClickOutside)
            if (closeOnClickOutside) {
                document.addEventListener('click', function(event) {
                    // Comprueba si el clic fue FUERA de un módulo activo
                    // y también FUERA de un botón que abre un módulo
                    const clickedOnModule = event.target.closest('[data-module].active');
                    const clickedOnButton = event.target.closest('[data-action]');

                    if (!clickedOnModule && !clickedOnButton) {
                        deactivateAllModules();
                    }
                });
            }

            // 3. Lógica de Tecla Escape (closeOnEscape)
            if (closeOnEscape) {
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        deactivateAllModules();
                    }
                });
            }
        });
    </script>
</body>

</html>