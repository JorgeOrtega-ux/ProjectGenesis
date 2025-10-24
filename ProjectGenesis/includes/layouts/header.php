<?php
// --- NUEVO BLOQUE PHP ---
// Lógica para obtener la URL de la imagen de perfil

// Definimos una URL de avatar por defecto
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

// Obtenemos la URL de la sesión. Si no existe, usamos la de por defecto.
$profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;

// Asegurarnos de que no esté vacía (por si en la BD se guardó un NULL)
if (empty($profileImageUrl)) {
    $profileImageUrl = $defaultAvatar;
}

// --- MODIFICACIÓN: Obtener username para el ALT text ---
$usernameForAlt = $_SESSION['username'] ?? 'Usuario';

// --- ¡NUEVO BLOQUE PARA EL ROL! ---
// Obtenemos el rol de la sesión, si no existe, 'user' por defecto
$userRole = $_SESSION['role'] ?? 'user';
// --- FIN DEL NUEVO BLOQUE ---

?>
<div class="header">
    <div class="header-left">
        <div class="header-item">
            <div class="header-button" data-action="toggleModuleSurface">
                <span class="material-symbols-rounded">menu</span>
            </div>
        </div>
    </div>
    <div class="header-right">
        <div class="header-item">
            
            <div class="header-button header-profile" 
                 data-action="toggleModuleSelect"
                 data-role="<?php echo htmlspecialchars($userRole); ?>"> 
                 
                 <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                      alt="Avatar de <?php echo htmlspecialchars($usernameForAlt); ?>"
                      class="header-profile-image">
            </div>
            </div>
    </div>
    <div class="module-content module-select body-title disabled" data-module="moduleSelect">
        <div class="menu-content">
            <div class="menu-list">

                <div class="menu-link" data-action="toggleSectionSettingsProfile">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">settings</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Configuracion</span>
                    </div>
                </div>
                <div class="menu-link">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">help</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Ayuda y comentarios</span>
                    </div>
                </div>
                
                <div class="menu-link" data-action="logout">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">logout</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Cerrar sesion</span>
                    </div>
                    </div>
                </div>
        </div>
    </div>
</div>