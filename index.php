<?php
require __DIR__ . '/auth/session.php';
$user = requireAuth();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die("DB error");

$stmt = $db->prepare("SELECT id, title, updated_at FROM typst_documents WHERE owner=? ORDER BY updated_at DESC");
$stmt->bind_param("s", $user['sub']);
$stmt->execute();
$res  = $stmt->get_result();
$docs = [];
while ($row = $res->fetch_assoc()) $docs[] = $row;
$stmt->close();
$db->close();

$isDark     = ($_COOKIE["darkmode"] ?? "1") !== "1";
$themeClass = $isDark ? "dark" : "light";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Typst Editor — Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="css/dark_mode.php">
</head>
<body class="<?= $themeClass ?>">

<div class="topbar">
    <img src="/logo_small_white.png" class="topbar-logo" alt="logo">
    <span class="topbar-title">Typst Editor</span>
    <button class="topbar-btn" onclick="newDoc()">
        <i class="ri-add-line" style="vertical-align:middle;margin-right:4px"></i>New Document
    </button>
    <span class="topbar-username"><?= htmlspecialchars($user['name']) ?></span>
    <a href="settings.php"><button class="topbar-icon-btn" title="Settings"><i class="ri-settings-4-line"></i></button></a>
    <a href="auth/logout.php">
        <button class="topbar-btn secondary" style="padding:6px 14px;font-size:13px">Logout</button>
    </a>
</div>

<div class="library-body">
    <div class="library-header">
        <h1>Your Documents</h1>
        <span style="font-size:13px;color:var(--grayText)"><?= count($docs) ?> document<?= count($docs) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($docs)): ?>
    <div class="empty-state">
        <i class="ri-file-text-line"></i>
        <p style="font-size:16px;margin-bottom:8px">No documents yet</p>
        <p style="font-size:14px">Click <strong>New Document</strong> to get started</p>
    </div>
    <?php else: ?>
    <div class="doc-grid">
        <?php foreach ($docs as $doc): ?>
        <div class="doc-card" onclick="openDoc(<?= (int)$doc['id'] ?>)">
            <div class="doc-card-preview">
                <i class="ri-file-text-line" style="font-size:56px"></i>
            </div>
            <div class="doc-card-body">
                <div class="doc-card-title"><?= htmlspecialchars($doc['title']) ?></div>
                <div class="doc-card-date"><?= date('M j, Y', strtotime($doc['updated_at'])) ?></div>
            </div>
            <button class="doc-card-delete" onclick="deleteDoc(event, <?= (int)$doc['id'] ?>)" title="Delete">
                <i class="ri-delete-bin-line" style="font-size:16px"></i>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function newDoc() {
    var btn = document.querySelector('.topbar-btn');
    btn.disabled = true;
    btn.textContent = 'Creating…';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/new.php', true);
    xhr.onload = function() {
        var res = JSON.parse(xhr.responseText);
        if (res.ok) {
            window.location.href = 'editor.php?id=' + res.id;
        } else {
            alert('Error: ' + res.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-add-line" style="vertical-align:middle;margin-right:4px"></i>New Document';
        }
    };
    xhr.send();
}

function openDoc(id) { window.location.href = 'editor.php?id=' + id; }

function deleteDoc(e, id) {
    e.stopPropagation();
    if (!confirm('Delete this document? This cannot be undone.')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/delete.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res = JSON.parse(xhr.responseText);
        if (res.ok) window.location.reload();
        else alert('Error: ' + res.error);
    };
    xhr.send('id=' + id);
}
</script>
</body>
</html>
