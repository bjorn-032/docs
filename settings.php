<?php
require __DIR__ . '/auth/session.php';
$user = requireAuth();

$isDark     = ($_COOKIE["darkmode"] ?? "1") !== "1";
$themeClass = $isDark ? "dark" : "light";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings — Typst Editor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="css/dark_mode.php">
</head>
<body class="<?= $themeClass ?>">

<div class="topbar">
    <button class="topbar-icon-btn" onclick="goBack()" title="Back">
        <i class="ri-arrow-left-s-line"></i>
    </button>
    <span class="topbar-title">Settings</span>
    <span class="topbar-username"><?= htmlspecialchars($user['name']) ?></span>
</div>

<div class="settings-body">

    <div class="settings-card">
        <div class="settings-section-title">Appearance</div>
        <div class="settings-row-label">Theme</div>
        <div class="theme-options">
            <div class="theme-card <?= !$isDark ? 'active' : '' ?>" id="themeLight" onclick="setTheme(true)">
                <div class="theme-preview light-preview">Aa</div>
                <div class="theme-card-label">
                    <i class="ri-checkbox-circle-fill theme-check"></i>
                    Light
                </div>
            </div>
            <div class="theme-card <?= $isDark ? 'active' : '' ?>" id="themeDark" onclick="setTheme(false)">
                <div class="theme-preview dark-preview">Aa</div>
                <div class="theme-card-label">
                    <i class="ri-checkbox-circle-fill theme-check"></i>
                    Dark
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function goBack() {
    if (document.referrer && document.referrer.indexOf(location.hostname) !== -1) {
        history.back();
    } else {
        location.href = 'index.php';
    }
}

function setTheme(isLight) {
    var val = isLight ? '1' : '0';
    var maxAge = 365 * 24 * 3600;
    document.cookie = 'darkmode=' + val + '; path=/; SameSite=Lax; max-age=' + maxAge;
    document.body.className = isLight ? 'light' : 'dark';
    document.getElementById('themeLight').classList.toggle('active', isLight);
    document.getElementById('themeDark').classList.toggle('active', !isLight);
}
</script>
</body>
</html>
