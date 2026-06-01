<?php
require __DIR__ . '/auth/session.php';
$user = requireAuth();
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? '');

$isDark     = ($_COOKIE["darkmode"] ?? "1") !== "1";
$themeClass = $isDark ? "dark" : "light";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Docs</title>
<link rel="icon" href="/logo.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="/css/dark_mode.php">
<style>body.dark{background:#141414}body.light{background:#f5f5f5}</style>
</head>
<body class="<?= $themeClass ?> settings-page">

<div class="topbar">
    <button class="topbar-icon-btn" onclick="goBack()" title="Back">
        <i class="ri-arrow-left-s-line"></i>
    </button>
    <span class="topbar-title">Settings</span>
    <div class="user-avatar-wrap">
        <button class="user-avatar" onclick="toggleUserMenu(event)"><?= strtoupper(mb_substr($user['name'], 0, 1)) ?></button>
        <div class="user-menu" id="userMenu">
            <div class="user-menu-header"><?= htmlspecialchars($user['name']) ?></div>
            <a href="/auth/logout.php" class="user-menu-item"><i class="ri-logout-box-r-line"></i>Logout</a>
        </div>
    </div>
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

    <!-- Git: SSH Key -->
    <div class="settings-card">
        <div class="settings-section-title">Git - SSH Key</div>
        <p style="font-size:13px;color:var(--grayText);line-height:1.6;margin-bottom:16px">
            Add this public key to your GitHub or GitLab account to allow pushing from Docs.
        </p>
        <div class="git-pub-key-wrap" id="sshKeyWrap">
            <textarea id="sshPubKey" class="git-pub-key-box git-pub-key-blurred" rows="4" readonly spellcheck="false">Loading…</textarea>
            <div class="git-pub-key-overlay" id="sshKeyOverlay" onclick="revealPubKey()">
                <i class="ri-eye-line"></i> Click to reveal
            </div>
        </div>
        <div class="git-btn-row">
            <button class="git-secondary-btn" onclick="copyPubKey()"><i class="ri-file-copy-line"></i> Copy</button>
            <button class="git-secondary-btn" onclick="hidePubKey()"><i class="ri-eye-off-line"></i> Hide</button>
            <button class="git-secondary-btn" onclick="regenKey()"><i class="ri-refresh-line"></i> Regenerate</button>
        </div>
    </div>

    <!-- Git: Author -->
    <div class="settings-card">
        <div class="settings-section-title">Git - Author</div>
        <p style="font-size:13px;color:var(--grayText);line-height:1.6;margin-bottom:16px">
            Name and email used in commit messages.
        </p>
        <div style="display:flex;flex-direction:column;gap:14px">
            <div>
                <div class="settings-input-label">Name</div>
                <input type="text" id="gitAuthorName" class="settings-input" value="<?= htmlspecialchars($user['name']) ?>">
            </div>
            <div>
                <div class="settings-input-label">Email</div>
                <input type="email" id="gitAuthorEmail" class="settings-input" value="<?= $userEmail ?>">
            </div>
        </div>
        <div style="margin-top:16px;display:flex;align-items:center;gap:12px">
            <button class="settings-save-btn" onclick="saveGitAuthor()">Save</button>
            <span id="gitAuthorStatus" style="font-size:12px;color:var(--grayText)"></span>
        </div>
    </div>

</div>

<script>
function goBack() {
    if (document.referrer && document.referrer.indexOf(location.hostname) !== -1) {
        history.back();
    } else {
        location.href = '/';
    }
}

function toggleUserMenu(e) {
    e.stopPropagation();
    document.getElementById('userMenu').classList.toggle('open');
}
document.addEventListener('click', function() {
    var m = document.getElementById('userMenu');
    if (m) m.classList.remove('open');
});

function setTheme(isLight) {
    var val = isLight ? '1' : '0';
    var maxAge = 365 * 24 * 3600;
    document.cookie = 'darkmode=' + val + '; path=/; SameSite=Lax; max-age=' + maxAge;
    document.body.className = isLight ? 'light' : 'dark';
    document.getElementById('themeLight').classList.toggle('active', isLight);
    document.getElementById('themeDark').classList.toggle('active', !isLight);
}

// ── Git settings ──────────────────────────────────────────────────────────────
function loadSshKey() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/git_key_get.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res; try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (res.ok) document.getElementById('sshPubKey').value = res.public_key;
        else document.getElementById('sshPubKey').value = 'Error: ' + res.error;
    };
    xhr.send('');
}

function revealPubKey() {
    document.getElementById('sshPubKey').classList.remove('git-pub-key-blurred');
    document.getElementById('sshKeyOverlay').style.display = 'none';
}

function hidePubKey() {
    document.getElementById('sshPubKey').classList.add('git-pub-key-blurred');
    document.getElementById('sshKeyOverlay').style.display = '';
}

function copyPubKey() {
    var ta = document.getElementById('sshPubKey');
    navigator.clipboard.writeText(ta.value).then(function() {
        var btn = event.currentTarget;
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ri-check-line"></i> Copied';
        btn.style.color = 'var(--colorThemeLight)';
        setTimeout(function() { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
    });
}

function regenKey() {
    if (!confirm('Regenerate your SSH key? The old public key will stop working on any git hosts where it was added.')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/git_key_regen.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res; try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (res.ok) document.getElementById('sshPubKey').value = res.public_key;
        else alert('Error: ' + res.error);
    };
    xhr.send('');
}

function loadGitAuthor() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/git_settings_get.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res; try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (res.ok) {
            document.getElementById('gitAuthorName').value  = res.commit_name  || '';
            document.getElementById('gitAuthorEmail').value = res.commit_email || '';
        }
    };
    xhr.send('');
}

function saveGitAuthor() {
    var name  = document.getElementById('gitAuthorName').value.trim();
    var email = document.getElementById('gitAuthorEmail').value.trim();
    var status = document.getElementById('gitAuthorStatus');
    if (!name || !email) { status.textContent = 'Name and email required'; return; }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/git_settings_save.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res; try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        status.textContent = res.ok ? 'Saved.' : ('Error: ' + res.error);
        if (res.ok) setTimeout(function() { status.textContent = ''; }, 2500);
    };
    xhr.send('commit_name=' + encodeURIComponent(name) + '&commit_email=' + encodeURIComponent(email));
}

loadSshKey();
loadGitAuthor();
</script>
</body>
</html>
