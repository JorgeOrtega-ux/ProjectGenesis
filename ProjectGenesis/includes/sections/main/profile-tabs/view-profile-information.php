<?php
// FILE: includes/sections/main/profile-tabs/view-profile-information.php
// (NUEVO ARCHIVO)

// --- ▼▼▼ INICIO DE CORRECCIÓN ▼▼▼ ---
$profile = $viewProfileData;
// --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---

// --- Estas variables vienen del 'view-profile.php' principal ---
// $profile (datos del perfil)
// $isOwnProfile (booleano)

// --- ▼▼▼ INICIO DE MODIFICACIÓN (Opciones de Empleo/Educación) ▼▼▼ ---
// (Usamos los mismos mapas definidos en your-profile.php para mostrar el texto amigable)

// Opciones de Empleo
$employmentMap = [
    'none' => 'Sin empleo',
    'student' => 'Estudiante',
    'tech' => 'Tecnología / Desarrollo de Software',
    'health' => 'Salud / Medicina',
    'education' => 'Educación / Docencia',
    'industry' => 'Industria / Manufactura',
    'commerce' => 'Comercio / Ventas',
    'admin' => 'Administración / Oficina',
    'other' => 'Otro'
];

// Opciones de Formación
$educationMap = [
    'none' => 'Sin formación',
    'icn_valle_hermoso' => 'Universidad de Ingenierías y Ciencias del Noreste (ICN) – Campus Valle Hermoso',
    'uda_zaragoza_vh' => 'Universidad del Atlántico – Campus Valle Hermoso (Zaragoza)',
    'uda_juarez_vh' => 'Universidad del Atlántico – Campus Valle Hermoso (Juárez)',
    'unm_valle_hermoso' => 'Universidad del Noreste de México – Unidad Valle Hermoso',
    'uat_valle_hermoso' => 'Universidad Autónoma de Tamaulipas (UAT) – Unidad Académica Multidisciplinaria Valle Hermoso',
    'icn_matamoros' => 'Universidad de Ingenierías y Ciencias del Noreste (ICN)',
    'uih_matamoros' => 'Universidad de Integración Humanista',
    'fmisc_matamoros' => 'Facultad de Medicina e Ingeniería en Sistemas Computacionales Matamoros',
    'cin_matamoros' => 'Centro Universitario del Noreste (CIN)',
    'iom_matamoros' => 'Instituto Odontológico de Matamoros (IOM)',
    'uamm_matamoros' => 'Unidad Académica Multidisciplinaria Matamoros (UAMM)',
    'uane_americana_matamoros' => 'Universidad Americana del Noreste, Campus Matamoros',
    'uane_americanista_matamoros' => 'Universidad Americanista del Noreste (UANE), Campus Matamoros',
    'ut_matamoros' => 'Universidad Tamaulipeca, Campus Matamoros',
    'itm_matamoros' => 'Instituto Tecnológico de Matamoros',
    'upn_matamoros' => 'Universidad Pedagógica Nacional (UPN)',
    'uda_cardenas_matamoros' => 'Universidad del Atlántico, Campus Pedro Cárdenas',
    'uda_villar_matamoros' => 'Universidad del Atlántico, Campus Lauro Villar',
    'uda_logrono_matamoros' => 'Universidad del Atlántico, Campus Logroño',
    'unm_matamoros' => 'Universidad del Noreste de México, Unidad Matamoros',
    'normal_mainero_matamoros' => 'Escuela Normal Lic. J. Guadalupe Mainero',
    'lpca_matamoros' => 'Liceo Profesional de Comercio y Administración',
    'utm_matamoros' => 'Universidad Tecnológica de Matamoros (UTM)',
    'other' => 'Otra'
];

// Obtener los valores guardados
$employmentKey = $profile['employment'] ?? 'none';
$educationKey = $profile['education'] ?? 'none';

// Buscar el texto legible. Si no se encuentra, usar el default 'none'.
$employmentText = $employmentMap[$employmentKey] ?? $employmentMap['none'];
$educationText = $educationMap[$educationKey] ?? $educationMap['none'];

// Si el valor es 'none', mostramos el placeholder solicitado
$employmentDisplay = ($employmentKey === 'none') ? 'Sin empleo establecido' : $employmentText;
$educationDisplay = ($educationKey === 'none') ? 'Sin formación establecida' : $educationText;
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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
                        $isEmailPublic = (int)($profile['is_email_public'] ?? 0);
                        
                        if ($isOwnProfile) {
                            echo htmlspecialchars($_SESSION['email']);
                        } elseif ($isEmailPublic) {
                            echo htmlspecialchars($profile['email']);
                        } else {
                            echo "Información privada";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div data-info-tab="employment">
                <div class="info-row">
                    <div class="info-row-label">Empleo</div>
                    <div class="info-row-value">
                        <?php echo htmlspecialchars($employmentDisplay); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-row-label">Formación</div>
                    <div class="info-row-value">
                         <?php echo htmlspecialchars($educationDisplay); ?>
                    </div>
                </div>
            </div>
            </div>
    </div>
    
</div>