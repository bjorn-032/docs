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
<title>Fireants Documents — Library</title>
<link rel="icon" href="logo_small_white.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="css/dark_mode.php">
<style>body.dark{background:#141414}body.light{background:#f5f5f5}</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
</head>
<body class="<?= $themeClass ?>">

<div class="topbar">
    <img src="/logo_small_white.png" class="topbar-logo" alt="logo">
    <span class="topbar-title">Fireants Documents</span>
    <button class="topbar-btn secondary" onclick="openImportModal()">
        <i class="ri-upload-2-line" style="vertical-align:middle;margin-right:4px"></i>Import
    </button>
    <button class="topbar-btn" id="newDocBtn" onclick="newDoc()">
        <i class="ri-add-line" style="vertical-align:middle;margin-right:4px"></i>New Document
    </button>
    <div class="user-avatar-wrap">
        <button class="user-avatar" onclick="toggleUserMenu(event)"><?= strtoupper(mb_substr($user['name'], 0, 1)) ?></button>
        <div class="user-menu" id="userMenu">
            <div class="user-menu-header"><?= htmlspecialchars($user['name']) ?></div>
            <a href="settings.php" class="user-menu-item"><i class="ri-settings-4-line"></i>Settings</a>
            <a href="auth/logout.php" class="user-menu-item"><i class="ri-logout-box-r-line"></i>Logout</a>
        </div>
    </div>
</div>

<div class="library-body">
    <div class="library-header">
        <h1>Your Documents</h1>
        <span id="docCount" style="font-size:13px;color:var(--grayText)"><?= count($docs) ?> document<?= count($docs) !== 1 ? 's' : '' ?></span>
        <div class="search-wrap">
            <i class="ri-search-line"></i>
            <input type="text" id="searchInput" placeholder="Search…" oninput="filterDocs(this.value)" autocomplete="off">
        </div>
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

<!-- Import modal -->
<div class="modal-overlay" id="importModal" onclick="handleModalClick(event)">
    <div class="modal-box">
        <div class="modal-title">Import Project</div>
        <div class="modal-desc">Upload a <strong>.zip</strong> archive or a <strong>folder</strong> to create a new multi-file project.</div>
        <div class="import-options">
            <button class="import-option-btn" onclick="document.getElementById('zipInput').click()">
                <i class="ri-file-zip-line"></i>
                <span>Upload .zip</span>
            </button>
            <button class="import-option-btn" onclick="document.getElementById('folderInput').click()">
                <i class="ri-folder-upload-line"></i>
                <span>Upload Folder</span>
            </button>
        </div>
        <input type="file" id="zipInput" accept=".zip" style="display:none" onchange="handleZipSelect(this)">
        <input type="file" id="folderInput" style="display:none" webkitdirectory onchange="handleFolderSelect(this)">
    </div>
</div>

<!-- Upload progress overlay -->
<div class="import-progress-overlay" id="importProgress">
    <div class="import-spinner"></div>
    <span id="importProgressMsg">Preparing upload…</span>
</div>

<script>
function openImportModal() {
    document.getElementById('importModal').classList.add('open');
}
function closeImportModal() {
    document.getElementById('importModal').classList.remove('open');
    document.getElementById('zipInput').value = '';
    document.getElementById('folderInput').value = '';
}
function handleModalClick(e) {
    if (e.target === document.getElementById('importModal')) closeImportModal();
}

function setProgress(msg) {
    document.getElementById('importProgressMsg').textContent = msg;
    document.getElementById('importProgress').classList.add('open');
}
function clearProgress() {
    document.getElementById('importProgress').classList.remove('open');
}

function handleZipSelect(input) {
    var file = input.files[0];
    if (!file) return;
    closeImportModal();
    var title = file.name.replace(/\.zip$/i, '');
    setProgress('Uploading…');
    var fd = new FormData();
    fd.append('zip', file, file.name);
    fd.append('title', title);
    uploadProject(fd);
}

function handleFolderSelect(input) {
    var files = Array.from(input.files);
    if (files.length === 0) return;
    closeImportModal();

    var folderName = files[0].webkitRelativePath.split('/')[0];
    setProgress('Zipping ' + files.length + ' file(s)…');

    var zip = new JSZip();
    files.forEach(function(f) { zip.file(f.webkitRelativePath, f); });

    zip.generateAsync({type: 'blob'}).then(function(blob) {
        setProgress('Uploading…');
        var fd = new FormData();
        fd.append('zip', blob, folderName + '.zip');
        fd.append('title', folderName);
        uploadProject(fd);
    });
}

function uploadProject(formData) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/upload_project.php', true);
    xhr.onload = function() {
        clearProgress();
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.ok) {
                window.location.href = 'editor.php?id=' + res.id;
            } else {
                alert('Import failed: ' + (res.error || 'Unknown error'));
            }
        } catch(e) {
            alert('Import failed: unexpected server response');
        }
    };
    xhr.onerror = function() {
        clearProgress();
        alert('Import failed: network error');
    };
    xhr.send(formData);
}

document.addEventListener('keydown', function(e) {
    var inp = document.getElementById('searchInput');
    if (!inp) return;
    if (e.key === 'Escape' && inp.value) { inp.value = ''; filterDocs(''); inp.blur(); }
    else if ((e.key === 'f' || e.key === 'F') && (e.ctrlKey || e.metaKey) && document.activeElement !== inp) {
        e.preventDefault(); inp.focus(); inp.select();
    }
});

function filterDocs(q) {
    q = q.trim().toLowerCase();
    var cards = document.querySelectorAll('.doc-card');
    var visible = 0;
    cards.forEach(function(card) {
        var title = card.querySelector('.doc-card-title').textContent.toLowerCase();
        var show = !q || title.includes(q);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    var total = cards.length;
    document.getElementById('docCount').textContent =
        (q ? visible + ' of ' + total : total) + ' document' + (total !== 1 ? 's' : '');
}

function newDoc() {
    var btn = document.getElementById('newDocBtn');
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

function toggleUserMenu(e) {
    e.stopPropagation();
    document.getElementById('userMenu').classList.toggle('open');
}
document.addEventListener('click', function() {
    var m = document.getElementById('userMenu');
    if (m) m.classList.remove('open');
});

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
