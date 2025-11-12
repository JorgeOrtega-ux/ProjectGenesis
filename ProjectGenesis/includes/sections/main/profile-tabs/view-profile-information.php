<?php
// FILE: includes/sections/main/profile-tabs/view-profile-information.php
// (NUEVO ARCHIVO)

// --- Estas variables vienen del 'view-profile.php' principal ---
// $profile (datos del perfil)
// $isOwnProfile (booleano)
?>

<div class="profile-main-content active" data-profile-tab-content="info">
                
    <div class="profile-info-layout">
        <div class="profile-info-menu">
            <h3>Información</h3>
            <button type="button" class="profile-info-button active" data-action="profile-info-tab-select" data-tab="general">
                Informacion general
            </button>
            <button type="button" class="profile-info-button" data-action="profile-info-tab-select" data-tab="employment">
                Empleo y formacion
            </button>
        </div>
        
        <div class="profile-info-content">
            
            <div data-info-tab="general" class="active">
                <div class="info-row">
                    <div class="info-row-label">Nombre de usuario</div>
                    <div class="info-row-value"><?php echo htmlspecialchars($profile['username']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-row-label">Correo electrónico</div>
                    <div class="info-row-value">
                        <?php 
                        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                        // $profile['is_email_public'] y $profile['email'] vienen de router.php
                        $isEmailPublic = (int)($profile['is_email_public'] ?? 0);
                        
                        if ($isOwnProfile) {
                            echo htmlspecialchars($_SESSION['email']);
                        } elseif ($isEmailPublic) {
                            echo htmlspecialchars($profile['email']);
                        } else {
                            echo "Información privada";
                        }
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                        ?>
                    </div>
                </div>
            </div>
            
            <div data-info-tab="employment">
                <div class="info-row">
                    <div class="info-row-label">Empleo</div>
                    <div class="info-row-value">Pendiente</div>
                </div>
                <div class="info-row">
                    <div class="info-row-label">Formación</div>
                    <div class="info-row-value">Pendiente</div>
                </div>
            </div>
            
        </div>
    </div>
    
</div>