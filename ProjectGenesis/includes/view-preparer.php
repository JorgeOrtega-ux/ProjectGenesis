<?php
// FILE: includes/view-preparer.php

// Este archivo asume que las variables de sesión están listas (de bootstrapper.php)
// y que $currentPage, etc., están definidos (de router-guard.php).

// 1. Lógica de Tema
$themeClass = '';
if (isset($_SESSION['theme'])) {
    if ($_SESSION['theme'] === 'light') {
        $themeClass = 'light-theme';
    } elseif ($_SESSION['theme'] === 'dark') {
        $themeClass = 'dark-theme';
    }
}

// 2. Lógica de Idioma para el tag <html>
$langMap = [
    'es-latam' => 'es-419',
    'es-mx' => 'es-MX',
    'en-us' => 'en-US',
    'fr-fr' => 'fr-FR'
];

$currentLang = $_SESSION['language'] ?? 'en-us'; 
$htmlLang = $langMap[$currentLang] ?? 'en'; 

// 3. Lógica de Idioma para JavaScript
$jsLanguage = 'en-us'; 

if (isset($_SESSION['language'])) {
    $jsLanguage = $_SESSION['language'];
} else {
    $browserLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-us';
    // getPreferredLanguage() está disponible desde config.php
    $jsLanguage = getPreferredLanguage($browserLang); 
}

// Las variables $themeClass, $htmlLang, $jsLanguage
// están ahora disponibles para main-layout.php
?>