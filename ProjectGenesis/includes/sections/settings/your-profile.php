<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-profile') ? 'active' : 'disabled'; ?>" data-section="settings-profile">
    <div class="settings-wrapper">
        
        <div class="settings-header-card">
            <h1 class="settings-title">Tu Perfil</h1>
            <p class="settings-description">
                Aquí podrás editar tu información de perfil, cambiar tu avatar y nombre de usuario.
            </p>
        </div>

        <?php
        // --- NUEVO BLOQUE PHP ---
        // Lógica para obtener la URL de la imagen de perfil
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) {
            $profileImageUrl = $defaultAvatar;
        }
        $usernameForAlt = $_SESSION['username'] ?? 'Usuario';
        $userRole = $_SESSION['role'] ?? 'user';
        // --- FIN DEL NUEVO BLOQUE ---
        ?>
        
        <div class="settings-card">
            <div class="settings-card-left">
                <div class="settings-avatar" data-role="<?php echo htmlspecialchars($userRole); ?>">
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                         alt="Avatar de <?php echo htmlspecialchars($usernameForAlt); ?>"
                         class="settings-avatar-image">
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Foto de perfil</h2>
                    <p class="settings-text-description">Esto ayudará a tus compañeros a reconocerte.</p>
                </div>
            </div>
            
            <div class="settings-card-right">
                <button type="button" class="settings-button">Subir foto</button>
            </div>
        </div>
        </div>
</div>