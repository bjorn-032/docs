<?php
require __DIR__ . '/auth/session.php';
$user = requireAuth();

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die("DB error");

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT id, title, content FROM typst_documents WHERE id=? AND owner=?");
$stmt->bind_param("is", $id, $user['sub']);
$stmt->execute();
$res = $stmt->get_result();
$doc = $res->fetch_assoc();
$stmt->close();
$db->close();

if (!$doc) { header("Location: index.php"); exit; }

$isDark = ($_COOKIE["darkmode"] ?? "1") !== "1";
$themeClass = $isDark ? "dark" : "light";
$cmTheme = $isDark ? "dracula" : "default";
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($doc['title']) ?> — Typst Editor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=JetBrains+Mono&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="css/dark_mode.php">
<style>body.dark{background:#141414}body.light{background:#f5f5f5}</style>
<!-- CodeMirror 5 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/addon/hint/show-hint.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/addon/comment/comment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/addon/hint/show-hint.min.js"></script>
<style>
html, body { height: 100%; overflow: hidden; }
/* Autocomplete hint widget */
.CodeMirror-hints {
    background: #1e1e2e;
    border: 1px solid #3c3c3c;
    border-radius: 6px;
    font-family: "JetBrains Mono", monospace;
    font-size: 13px;
    padding: 4px 0;
    box-shadow: 0 4px 16px rgba(0,0,0,.5);
    z-index: 100;
}
.CodeMirror-hint {
    color: #d5d5d5;
    padding: 3px 12px;
    border-radius: 0;
    cursor: pointer;
    white-space: nowrap;
}
.CodeMirror-hint-active {
    background: #3838BA !important;
    color: #fff !important;
}
.hint-typst { color: #50fa7b; }
.hint-cite  { color: #8be9fd; }
.light .CodeMirror-hints { background: #fafafa; border-color: #e0e0e0; box-shadow: 0 4px 16px rgba(0,0,0,.12); }
.light .CodeMirror-hint  { color: #212121; }
.light .hint-typst { color: #2e7d32; }
.light .hint-cite  { color: #0277bd; }
.editor-shell { height: calc(100vh - 56px); display: flex; }
.editor-pane { flex: 1; min-width: 0; display: flex; flex-direction: column; overflow: hidden; }
.editor-pane .CodeMirror { flex: 1; height: 100%; font-size: 14px; line-height: 1.7; font-family: "JetBrains Mono", monospace; }
/* Dracula overrides to match our dark shell */
.dark .CodeMirror { background: #1e1e2e !important; }
.light .CodeMirror { background: #fafafa; color: #212121; }
/* Typst token colours (dracula palette) */
.cm-typst-heading   { color: #ff79c6; font-weight: bold; }
.cm-typst-bold      { color: #f1fa8c; font-weight: bold; }
.cm-typst-italic    { color: #8be9fd; font-style: italic; }
.cm-typst-hash      { color: #ff79c6; }
.cm-typst-keyword   { color: #ff79c6; }
.cm-typst-fn        { color: #50fa7b; }
.cm-typst-string    { color: #f1fa8c; }
.cm-typst-number    { color: #bd93f9; }
.cm-typst-comment   { color: #6272a4; font-style: italic; }
.cm-typst-math      { color: #ffb86c; }
.cm-typst-mathkw    { color: #ff79c6; }
.cm-typst-mathop    { color: #ff79c6; }
.cm-typst-raw       { color: #50fa7b; }
.cm-typst-label     { color: #bd93f9; }
.cm-typst-ref       { color: #8be9fd; }
.cm-typst-listmark  { color: #ff79c6; }
.cm-typst-escape    { color: #ff79c6; }
.cm-typst-url       { color: #8be9fd; text-decoration: underline; }
.cm-typst-operator  { color: #ff79c6; }
/* Light theme token colours */
.light .cm-typst-heading  { color: #6200ea; }
.light .cm-typst-bold     { color: #e65100; font-weight: bold; }
.light .cm-typst-italic   { color: #0277bd; font-style: italic; }
.light .cm-typst-hash     { color: #c2185b; }
.light .cm-typst-keyword  { color: #c2185b; }
.light .cm-typst-fn       { color: #2e7d32; }
.light .cm-typst-string   { color: #c62828; }
.light .cm-typst-number   { color: #6200ea; }
.light .cm-typst-comment  { color: #9e9e9e; font-style: italic; }
.light .cm-typst-math     { color: #e65100; }
.light .cm-typst-mathkw   { color: #c2185b; }
.light .cm-typst-mathop   { color: #c2185b; }
.light .cm-typst-raw      { color: #2e7d32; }
.light .cm-typst-label    { color: #6200ea; }
.light .cm-typst-ref      { color: #0277bd; }
.light .cm-typst-listmark { color: #c2185b; }
.light .cm-typst-escape   { color: #c2185b; }
.light .cm-typst-url      { color: #0277bd; }
.light .cm-typst-operator { color: #c2185b; }
</style>
</head>
<body class="<?= $themeClass ?>">

<!-- Top bar -->
<div class="editor-topbar">
    <button class="editor-topbar-back" onclick="window.location.href='index.php'" title="Back to library">
        <i class="ri-arrow-left-s-line"></i>
    </button>
    <input type="text" class="editor-title-input" id="docTitle"
           value="<?= htmlspecialchars($doc['title']) ?>"
           onblur="saveDoc()" placeholder="Document title">
    <span class="save-indicator" id="saveIndicator"></span>
    <button class="compile-btn" id="compileBtn" onclick="compileDoc()">
        <i class="ri-play-fill"></i>Compile
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

<!-- Editor shell -->
<div class="editor-shell">

    <!-- Activity bar -->
    <div class="activity-bar">
        <button class="activity-btn active" id="actFiles" onclick="switchPanel('files')" title="Files">
            <i class="ri-folder-open-line"></i>
        </button>
        <button class="activity-btn" id="actStats" onclick="switchPanel('stats')" title="Document stats">
            <i class="ri-bar-chart-2-line"></i>
        </button>
        <button class="activity-btn" id="actDocSettings" onclick="switchPanel('docSettings')" style="margin-top:auto" title="Document settings">
            <i class="ri-settings-4-line"></i>
        </button>
    </div>

    <!-- Side panel -->
    <div class="side-panel" id="sidePanel">

        <!-- Files panel -->
        <div class="panel-pane" id="panelFiles">
            <div class="panel-header">
                <span class="panel-title">Files</span>
                <div style="display:flex;gap:2px">
                    <button class="panel-icon-btn" onclick="addFile()" title="New file">
                        <i class="ri-add-line"></i>
                    </button>
                    <button class="panel-icon-btn" onclick="addFolder()" title="New folder">
                        <i class="ri-folder-add-line"></i>
                    </button>
                    <button class="panel-icon-btn" onclick="document.getElementById('fileUploadInput').click()" title="Upload files">
                        <i class="ri-upload-line"></i>
                    </button>
                    <button class="panel-icon-btn" onclick="document.getElementById('folderUploadInput').click()" title="Upload folder">
                        <i class="ri-folder-upload-line"></i>
                    </button>
                    <input type="file" id="fileUploadInput" accept=".typ,.bib,.md,.png,.jpg,.jpeg,.gif,.webp,.svg" style="display:none" multiple onchange="handleFileUpload(this)">
                    <input type="file" id="folderUploadInput" style="display:none" webkitdirectory multiple onchange="handleFileUpload(this)">
                </div>
            </div>
            <div class="file-list" id="fileList"></div>
        </div>

        <!-- Doc settings panel -->
        <div class="panel-pane" id="panelDocSettings" style="display:none">
            <div class="panel-header">
                <span class="panel-title">Document Settings</span>
            </div>
            <div style="padding:12px 14px;display:flex;flex-direction:column;gap:20px">
                <div>
                    <div class="sidebar-label" style="margin-bottom:8px">Entry file</div>
                    <select id="entryFileSelect" class="doc-settings-select" onchange="setEntryFile(this.value)"></select>
                    <p style="font-size:11px;color:var(--grayText);margin-top:8px;line-height:1.5">The .typ file the compiler starts from.</p>
                </div>
                <div>
                    <div class="sidebar-label" style="margin-bottom:10px">Live compile</div>
                    <label class="live-switch">
                        <input type="checkbox" id="liveSwitchInput">
                        <span class="live-switch-track"></span>
                        <span class="live-switch-label">Compile on every change</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Stats panel -->
        <div class="panel-pane" id="panelStats" style="display:none">
            <div class="sidebar-section" style="border-top:none">
                <div class="sidebar-label">Document</div>
                <div class="sidebar-info-row">
                    <span>ID</span><span id="sidebarId"><?= $doc['id'] ?></span>
                </div>
                <div class="sidebar-info-row">
                    <span>Last saved</span><span id="sidebarSaved">—</span>
                </div>
                <div class="sidebar-info-row">
                    <span>Characters</span><span id="sidebarChars">0</span>
                </div>
                <div class="sidebar-info-row">
                    <span>Lines</span><span id="sidebarLines">0</span>
                </div>
                <div class="sidebar-info-row">
                    <span>Words</span><span id="sidebarWords">0</span>
                </div>
            </div>
            <div class="sidebar-section" id="selectionSection">
                <div class="sidebar-label">Selection</div>
                <div class="sidebar-info-row">
                    <span>Words</span><span id="sidebarSelWords">0</span>
                </div>
                <div class="sidebar-info-row">
                    <span>Characters</span><span id="sidebarSelChars">0</span>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-label">Compile</div>
                <div class="sidebar-info-row">
                    <span>Status</span><span id="sidebarStatus">—</span>
                </div>
                <div class="sidebar-info-row">
                    <span>Time</span><span id="sidebarTime">—</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Code editor -->
    <div class="editor-pane" id="editorPane">
        <textarea id="codeEditor"><?= htmlspecialchars($doc['content'] ?? '') ?></textarea>
    </div>

    <!-- Drag handle -->
    <div class="drag-handle" id="dragHandle"></div>

    <!-- Preview pane -->
    <div class="preview-pane" id="previewPane">
        <div class="preview-toolbar">
            <i class="ri-file-pdf-line" style="font-size:16px"></i>
            <span id="previewLabel">No output yet — click Compile</span>
        </div>
        <div class="live-progress" id="liveProgress"></div>
        <div class="preview-iframe-wrap" id="previewWrap">
            <div class="preview-placeholder" id="previewPlaceholder">
                <i class="ri-file-pdf-line"></i>
                <span style="font-size:13px">Press Compile to generate PDF</span>
            </div>
        </div>
    </div>
</div>

<!-- Image preview lightbox -->
<div class="img-lightbox" id="imgLightbox" onclick="closeLightbox(event)">
    <div class="img-lightbox-box">
        <div class="img-lightbox-header">
            <span id="imgLightboxName"></span>
            <button class="img-lightbox-close" onclick="closeLightbox()">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div class="img-lightbox-body">
            <img id="imgLightboxImg" src="" alt="">
        </div>
    </div>
</div>

<script>
// ── Typst CodeMirror 5 mode ──────────────────────────────────────────────────
CodeMirror.defineMode("typst", function() {

    var MARKUP  = 0;
    var CODE    = 1;
    var MATH    = 2;
    var RAW     = 3;

    var codeKeywords = /^(let|set|show|if|else|for|while|return|import|include|in|not|and|or|none|auto|true|false|break|continue|func|context|ref)\b/;
    var mathSymbols  = /^(alpha|beta|gamma|delta|epsilon|zeta|eta|theta|iota|kappa|lambda|mu|nu|xi|pi|rho|sigma|tau|upsilon|phi|chi|psi|omega|Alpha|Beta|Gamma|Delta|Epsilon|Zeta|Eta|Theta|Iota|Kappa|Lambda|Mu|Nu|Xi|Pi|Rho|Sigma|Tau|Upsilon|Phi|Chi|Psi|Omega|sum|prod|integral|oint|partial|nabla|infty|aleph|forall|exists|in|notin|subset|supset|subseteq|supseteq|cup|cap|setminus|emptyset|cdot|times|div|pm|mp|leq|geq|neq|approx|sim|equiv|cong|perp|parallel|angle|therefore|because|lim|max|min|sup|inf|sin|cos|tan|cot|sec|csc|arcsin|arccos|arctan|exp|log|ln|sqrt|abs|norm|floor|ceil|round|vec|mat|cases|bold|italic|upright|mono|cal|frak|bb|sans)\b/;

    function tokenMarkup(stream, state) {
        // Block comment
        if (stream.match("/*")) {
            state.blockComment = true;
            return tokenBlockComment(stream, state, MARKUP);
        }
        // Line comment
        if (stream.match("//")) {
            stream.skipToEnd();
            return "typst-comment";
        }
        // Raw block ```
        if (stream.match(/^```/)) {
            stream.match(/^[a-zA-Z]*/); // language tag
            state.rawClose = "```";
            state.prevMode = MARKUP;
            state.mode = RAW;
            return "typst-raw";
        }
        // Raw inline `
        if (stream.match(/^`[^`]*`/)) return "typst-raw";
        // Heading (must be at start of line)
        if (stream.sol() && stream.match(/^={1,6} /)) return "typst-heading";
        // Horizontal rule
        if (stream.sol() && stream.match(/^---\s*$/)) return "typst-heading";
        // List markers at line start
        if (stream.sol()) {
            if (stream.match(/^- /)) return "typst-listmark";
            if (stream.match(/^\+ /)) return "typst-listmark";
            if (stream.match(/^\/ /)) return "typst-listmark";
        }
        // Math $...$
        if (stream.peek() === '$') {
            stream.next();
            state.mode = MATH;
            return "typst-math";
        }
        // Code hash #
        if (stream.peek() === '#') {
            stream.next();
            state.mode = CODE;
            state.codeDepth = 0;
            state.bracketDepth = 0;
            return "typst-hash";
        }
        // Label <label-name>
        if (stream.match(/^<[a-zA-Z_][a-zA-Z0-9_\-]*>/)) return "typst-label";
        // Reference @ref
        if (stream.match(/^@[a-zA-Z_][a-zA-Z0-9_\-:]*/)) return "typst-ref";
        // Bold *...*  (simple single-line)
        if (stream.peek() === '*') {
            stream.next();
            if (stream.skipTo('*')) { stream.next(); return "typst-bold"; }
            return "typst-bold";
        }
        // Italic _..._
        if (stream.peek() === '_') {
            stream.next();
            if (stream.skipTo('_')) { stream.next(); return "typst-italic"; }
            return "typst-italic";
        }
        // Escape
        if (stream.peek() === '\\') {
            stream.next();
            stream.next();
            return "typst-escape";
        }
        // URL
        if (stream.match(/^https?:\/\/[^\s\]>)]+/)) return "typst-url";
        stream.next();
        return null;
    }

    function tokenCode(stream, state) {
        // Block comment
        if (stream.match("/*")) {
            state.blockComment = true;
            return tokenBlockComment(stream, state, CODE);
        }
        // Line comment
        if (stream.match("//")) {
            stream.skipToEnd();
            return "typst-comment";
        }
        // String
        if (stream.peek() === '"') {
            stream.next();
            while (!stream.eol()) {
                var ch = stream.next();
                if (ch === '\\') stream.next();
                else if (ch === '"') break;
            }
            return "typst-string";
        }
        // Raw `...`
        if (stream.match(/^`[^`]*`/)) return "typst-raw";
        // Number (with optional units like pt, em, cm, mm, %)
        if (stream.match(/^-?[0-9]+\.?[0-9]*(pt|em|cm|mm|in|%|fr|deg|rad|sp)?/)) return "typst-number";
        // Keywords
        if (stream.match(codeKeywords)) return "typst-keyword";
        // Label <label>
        if (stream.match(/^<[a-zA-Z_][a-zA-Z0-9_\-]*>/)) return "typst-label";
        // Identifier — check if followed by ( (function call)
        var word = stream.match(/^[a-zA-Z_][a-zA-Z0-9_\-]*/);
        if (word) {
            if (stream.peek() === '(') return "typst-fn";
            return "variable";
        }
        // Bracket tracking to know when code mode ends
        var ch = stream.peek();
        if (ch === '{') {
            stream.next(); state.codeDepth++;
            // entering a code block explicitly
            return "bracket";
        }
        if (ch === '}') {
            stream.next();
            if (state.codeDepth > 0) { state.codeDepth--; return "bracket"; }
            // No open braces: end of #{ ... } — back to markup
            state.mode = MARKUP;
            return "bracket";
        }
        if (ch === '[') {
            // content block — back to markup until matching ]
            stream.next();
            state.bracketDepth++;
            state.mode = MARKUP;
            state.inContentBlock = true;
            return "bracket";
        }
        if (ch === ']' && state.inContentBlock) {
            stream.next();
            state.bracketDepth--;
            if (state.bracketDepth <= 0) state.inContentBlock = false;
            state.mode = MARKUP;
            return "bracket";
        }
        if (ch === '(' || ch === ')') { stream.next(); return "bracket"; }
        // Operators
        if (stream.match(/^(==|!=|<=|>=|=>|->|\+|-|\*|\/|=|<|>|:|,|;|\.\.\.?)/)) return "typst-operator";
        // End of code expression on whitespace/newline when no open braces
        if (state.codeDepth === 0 && (stream.peek() === ' ' || stream.peek() === '\n' || stream.eol())) {
            // only exit if we were in a bare # expression (not inside brackets)
            if (!state.inContentBlock) state.mode = MARKUP;
        }
        stream.next();
        return null;
    }

    function tokenMath(stream, state) {
        // Closing $
        if (stream.peek() === '$') {
            stream.next();
            state.mode = MARKUP;
            return "typst-math";
        }
        // Comment
        if (stream.match("//")) { stream.skipToEnd(); return "typst-comment"; }
        // String
        if (stream.peek() === '"') {
            stream.next();
            while (!stream.eol()) {
                var ch = stream.next();
                if (ch === '\\') stream.next();
                else if (ch === '"') break;
            }
            return "typst-string";
        }
        // Number
        if (stream.match(/^-?[0-9]+\.?[0-9]*/)) return "typst-number";
        // Named symbols / functions
        if (stream.match(mathSymbols)) {
            if (stream.peek() === '(') return "typst-fn";
            return "typst-mathkw";
        }
        // Operators including ^ _ for superscript/subscript
        if (stream.match(/^[\^_\+\-\*\/=<>!&|,;:\[\](){}]/)) return "typst-mathop";
        stream.next();
        return "typst-math";
    }

    function tokenBlockComment(stream, state, returnTo) {
        while (!stream.eol()) {
            if (stream.match("*/")) {
                state.blockComment = false;
                state.mode = returnTo;
                break;
            }
            stream.next();
        }
        return "typst-comment";
    }

    function tokenRaw(stream, state) {
        if (stream.match(state.rawClose)) {
            state.mode = state.prevMode;
            return "typst-raw";
        }
        stream.skipToEnd();
        return "typst-raw";
    }

    return {
        startState: function() {
            return {
                mode: MARKUP,
                codeDepth: 0,
                bracketDepth: 0,
                inContentBlock: false,
                blockComment: false,
                rawClose: null,
                prevMode: MARKUP
            };
        },
        token: function(stream, state) {
            if (state.blockComment) return tokenBlockComment(stream, state, state.mode);
            if (state.mode === RAW)    return tokenRaw(stream, state);
            if (state.mode === MATH)   return tokenMath(stream, state);
            if (state.mode === CODE)   return tokenCode(stream, state);
            return tokenMarkup(stream, state);
        },
        // Ensure multi-line tokens (raw blocks) are re-evaluated correctly
        blankLine: function(state) {
            if (state.mode === RAW) { /* stay in raw */ }
        }
    };
});

CodeMirror.defineMIME("text/x-typst", "typst");
CodeMirror.extendMode("typst", { lineComment: "//" });

// ── Autocomplete ──────────────────────────────────────────────────────────────
var TYPST_BUILTINS = [
    // Structure & layout
    'align','bibliography','block','box','cite','colbreak','columns',
    'footnote','grid','h','heading','hide','line','linebreak','page',
    'pagebreak','par','parbreak','place','repeat','rotate','scale',
    'skew','stack','v',
    // Text
    'emph','highlight','link','lorem','lower','overline','raw',
    'smallcaps','strike','strong','sub','super','text','underline','upper',
    // Figures & media
    'circle','ellipse','figure','image','line','path','polygon','rect',
    'square','table',
    // Math
    'cancel','cases','equation','mat','vec',
    // Lists
    'enum','list','terms',
    // References & labels
    'label','numbering','outline','ref',
    // Data & IO
    'csv','json','plugin','read','toml','xml','yaml',
    // Introspection
    'counter','here','locate','measure','query','state',
    // Utility & logic
    'assert','calc','context','panic','repr','selector','style','type',
    // Colors
    'cmyk','color','gradient','luma','oklab','oklch','pattern','rgb',
    'stroke',
    // Control flow / declarations (keyword-like but used after #)
    'for','if','else','import','include','let','return','set','show',
    'while',
    // Constants
    'auto','none','true','false',
];
TYPST_BUILTINS.sort();

function typstHint(cm) {
    var cur = cm.getCursor();
    var before = cm.getLine(cur.line).slice(0, cur.ch);
    var m = before.match(/#([a-zA-Z]*)$/);
    if (!m) return;
    var prefix = m[1].toLowerCase();
    var from = CodeMirror.Pos(cur.line, cur.ch - m[1].length);
    var list = TYPST_BUILTINS
        .filter(function(b) { return b.startsWith(prefix); })
        .map(function(b) {
            return {
                text: b,
                displayText: '#' + b,
                className: 'hint-typst'
            };
        });
    return { list: list, from: from, to: cur };
}

function prefetchBibFiles() {
    extraFiles.forEach(function(f) {
        if (fileCache[f.id] !== undefined) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/file_get.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res;
            try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
            if (res.ok) fileCache[f.id] = res.content;
        };
        xhr.send('id=' + f.id + '&document_id=' + DOC_ID);
    });
}

function parseBibKeys(content, keys) {
    var re = /@(\w+)\s*\{([^,\s]+)\s*,/g, m;
    while ((m = re.exec(content)) !== null) {
        var type = m[1].toLowerCase();
        if (type !== 'string' && type !== 'preamble' && type !== 'comment')
            keys.push(m[2]);
    }
}

function getCitationKeys() {
    var keys = [];
    // Include the currently-open .bib file from the live editor content
    if (activeFile.id !== null && /\.bib$/i.test(activeFile.filename))
        parseBibKeys(editor.getValue(), keys);
    extraFiles.forEach(function(f) {
        if (!/\.bib$/i.test(f.filename)) return;
        if (f.id === activeFile.id) return;
        parseBibKeys(fileCache[f.id] || '', keys);
    });
    return keys;
}

function citationHint(cm) {
    var cur = cm.getCursor();
    var before = cm.getLine(cur.line).slice(0, cur.ch);
    var m = before.match(/@([a-zA-Z0-9._:-]*)$/) ||
            before.match(/#cite\s*\(\s*<([a-zA-Z0-9._:-]*)$/);
    if (!m) return;
    var prefix = m[1].toLowerCase();
    var from = CodeMirror.Pos(cur.line, cur.ch - m[1].length);
    var keys = getCitationKeys();
    var list = keys
        .filter(function(k) { return k.toLowerCase().startsWith(prefix); })
        .map(function(k) { return { text: k, className: 'hint-cite' }; });
    if (!list.length) return;
    return { list: list, from: from, to: cur };
}

// ── Editor setup ─────────────────────────────────────────────────────────────
var editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
    mode: "typst",
    theme: "<?= $cmTheme ?>",
    lineNumbers: true,
    lineWrapping: true,
    autofocus: true,
    tabSize: 2,
    indentWithTabs: false,
    matchBrackets: true,
    autoCloseBrackets: true,
    extraKeys: {
        "Ctrl-S": function() { saveCurrentFile(); compileDoc(); },
        "Ctrl-Enter": function() { compileDoc(); },
        "Ctrl-/": "toggleComment",
        "Tab": function(cm) {
            if (cm.state.completionActive) {
                cm.state.completionActive.pick();
            } else {
                var spaces = Array(cm.getOption("tabSize") + 1).join(" ");
                cm.replaceSelection(spaces);
            }
        }
    }
});

// ── State ─────────────────────────────────────────────────────────────────────
var DOC_ID              = <?= (int)$doc['id'] ?>;
var saveTimer           = null;
var liveTimer           = null;
var isSaving            = false;
var isDirty             = false;
var lastCompiledContent = null;
var liveMode            = localStorage.getItem('typst_live') === '1';
var entryFile           = localStorage.getItem('typst_entry_' + DOC_ID) || 'main.typ';

// Multi-file state
var activeFile       = { id: null, filename: 'main.typ' }; // null = main doc
var fileCache        = {};        // fileId → content string
var extraFiles       = [];        // [{id, filename}] from API
var projectImages    = [];        // [filename] from uploads dir
var collapsedFolders = new Set(); // folder paths that are collapsed
var localFolders     = new Set(); // empty folders created this session
var initialLoadDone  = false;
var draggedFile      = null;      // {id, filename} being dragged
var draggedFolder    = null;      // {path} being dragged
var draggedImage     = null;      // {filename} being dragged
var panelVisible     = true;
var activePanel      = 'files';

var liveSwitchInput = document.getElementById('liveSwitchInput');
liveSwitchInput.checked = liveMode;
liveSwitchInput.addEventListener('change', function() {
    liveMode = this.checked;
    localStorage.setItem('typst_live', liveMode ? '1' : '0');
});

// ── Panel switching ───────────────────────────────────────────────────────────
function switchPanel(name) {
    var panel = document.getElementById('sidePanel');
    if (name === activePanel && panelVisible) {
        panel.classList.add('collapsed');
        panelVisible = false;
    } else {
        document.getElementById('panelFiles').style.display = name === 'files' ? 'flex' : 'none';
        document.getElementById('panelStats').style.display = name === 'stats' ? 'flex' : 'none';
        document.getElementById('panelDocSettings').style.display = name === 'docSettings' ? 'flex' : 'none';
        panel.classList.remove('collapsed');
        panelVisible = true;
        activePanel = name;
        if (name === 'docSettings') renderDocSettings();
    }
    document.getElementById('actFiles').classList.toggle('active', activePanel === 'files' && panelVisible);
    document.getElementById('actStats').classList.toggle('active', activePanel === 'stats' && panelVisible);
    document.getElementById('actDocSettings').classList.toggle('active', activePanel === 'docSettings' && panelVisible);
    editor.refresh();
}

function setEntryFile(val) {
    entryFile = val;
    localStorage.setItem('typst_entry_' + DOC_ID, val);
}

function renderDocSettings() {
    var select = document.getElementById('entryFileSelect');
    var typFiles = ['main.typ'].concat(
        extraFiles
            .filter(function(f) { return /\.typ$/i.test(f.filename); })
            .map(function(f) { return f.filename; })
    );
    // ensure entryFile is still valid, otherwise reset
    if (typFiles.indexOf(entryFile) === -1) { entryFile = 'main.typ'; localStorage.removeItem('typst_entry_' + DOC_ID); }
    select.innerHTML = '';
    typFiles.forEach(function(name) {
        var opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        if (name === entryFile) opt.selected = true;
        select.appendChild(opt);
    });
}

// ── File list ─────────────────────────────────────────────────────────────────
function loadFileList() {
    renderFileList(); // always show main.typ immediately
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/file_list.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) { renderFileList(); return; }
        extraFiles = res.ok ? res.files : [];
        if (!initialLoadDone) {
            initialLoadDone = true;
            extraFiles.forEach(function(f) {
                var parts = f.filename.split('/');
                for (var i = 1; i < parts.length; i++) {
                    collapsedFolders.add(parts.slice(0, i).join('/'));
                }
            });
        }
        renderFileList();
        prefetchBibFiles();
    };
    xhr.onerror = function() { renderFileList(); };
    xhr.send('document_id=' + DOC_ID);
}

function buildFileTree(files) {
    var root = { dirs: {}, files: [] };
    // Materialise locally-created empty folders
    localFolders.forEach(function(folderPath) {
        var parts = folderPath.split('/');
        var node = root;
        parts.forEach(function(p) {
            if (!node.dirs[p]) node.dirs[p] = { dirs: {}, files: [] };
            node = node.dirs[p];
        });
    });
    files.forEach(function(f) {
        var parts = f.filename.split('/');
        var node = root;
        for (var i = 0; i < parts.length - 1; i++) {
            var d = parts[i];
            if (!node.dirs[d]) node.dirs[d] = { dirs: {}, files: [] };
            node = node.dirs[d];
        }
        node.files.push(f);
    });
    return root;
}

function renderFileTree(node, pathPrefix, depth, list) {
    Object.keys(node.dirs).sort().forEach(function(dir) {
        var fullPath = pathPrefix ? pathPrefix + '/' + dir : dir;
        list.appendChild(makeFolderItem(dir, fullPath, depth));
        if (!collapsedFolders.has(fullPath)) {
            renderFileTree(node.dirs[dir], fullPath, depth + 1, list);
        }
    });
    node.files.slice().sort(function(a, b) {
        return a.filename.localeCompare(b.filename);
    }).forEach(function(f) {
        if (f.isImage) {
            list.appendChild(makeImageItem(f.filename, depth));
        } else {
            list.appendChild(makeFileItem(f.id, f.filename, activeFile.id === f.id, depth));
        }
    });
}

function renderFileList() {
    var list = document.getElementById('fileList');
    list.innerHTML = '';
    list.appendChild(makeFileItem(null, 'main.typ', activeFile.id === null, 0));
    var allItems = extraFiles.concat(
        projectImages.map(function(name) { return { id: null, filename: name, isImage: true }; })
    );
    renderFileTree(buildFileTree(allItems), '', 0, list);
    // Root drop zone — drop here to move file/folder out of any folder
    list.ondragover = function(e) {
        if ((!draggedFile || !draggedFile.id) && !draggedFolder && !draggedImage) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };
    list.ondrop = function(e) {
        if (draggedFile && draggedFile.id) {
            if (draggedFile.filename.indexOf('/') === -1) return;
            var basename = draggedFile.filename.split('/').pop();
            moveFile(draggedFile, basename);
            draggedFile = null;
        } else if (draggedFolder) {
            if (draggedFolder.path.indexOf('/') === -1) return;
            var folderName = draggedFolder.path.split('/').pop();
            moveFolder(draggedFolder.path, folderName);
            draggedFolder = null;
        } else if (draggedImage) {
            if (draggedImage.filename.indexOf('/') === -1) return; // already at root
            var imgBasename = draggedImage.filename.split('/').pop();
            moveImage(draggedImage.filename, imgBasename);
            draggedImage = null;
        }
    };
}

function makeFolderItem(name, fullPath, depth) {
    var collapsed = collapsedFolders.has(fullPath);
    var div = document.createElement('div');
    div.className = 'file-item folder-item';
    div.style.paddingLeft = (12 + depth * 16) + 'px';
    div.innerHTML =
        '<i class="' + (collapsed ? 'ri-arrow-right-s-line' : 'ri-arrow-down-s-line') + ' folder-chevron"></i>' +
        '<i class="' + (collapsed ? 'ri-folder-line' : 'ri-folder-open-line') + ' folder-icon"></i>' +
        '<span class="file-name">' + escHtml(name) + '</span>';
    div.onclick = function(e) { if (!e._wasDrag && !e.target.closest('.file-del') && e.detail < 2) toggleFolder(fullPath); };
    div.addEventListener('dblclick', function(e) {
        e.stopPropagation();
        var parentDir = fullPath.lastIndexOf('/') >= 0 ? fullPath.slice(0, fullPath.lastIndexOf('/') + 1) : '';
        var base = fullPath.split('/').pop();
        var newName = prompt('Rename folder:', base);
        if (!newName || newName === base) return;
        moveFolder(fullPath, parentDir + newName);
    });
    var del = document.createElement('button');
    del.className = 'file-del';
    del.title = 'Delete folder';
    del.innerHTML = '<i class="ri-close-line"></i>';
    del.onclick = function(e) { e.stopPropagation(); deleteFolder(fullPath); };
    div.appendChild(del);
    // Drag source
    div.draggable = true;
    div.addEventListener('dragstart', function(e) {
        draggedFolder = { path: fullPath };
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', fullPath);
        setTimeout(function() { div.classList.add('dragging'); }, 0);
    });
    div.addEventListener('dragend', function() {
        div.classList.remove('dragging');
        draggedFolder = null;
    });
    // Drop target
    div.addEventListener('dragover', function(e) {
        var hasFile   = draggedFile && draggedFile.id;
        var hasFolder = draggedFolder && draggedFolder.path !== fullPath &&
                        !fullPath.startsWith(draggedFolder.path + '/');
        var hasImage  = !!draggedImage;
        if (!hasFile && !hasFolder && !hasImage) return;
        e.preventDefault();
        e.stopPropagation();
        e.dataTransfer.dropEffect = 'move';
        div.classList.add('drop-target');
    });
    div.addEventListener('dragleave', function(e) {
        if (!div.contains(e.relatedTarget)) div.classList.remove('drop-target');
    });
    div.addEventListener('drop', function(e) {
        e.stopPropagation();
        div.classList.remove('drop-target');
        if (draggedFile && draggedFile.id) {
            var basename = draggedFile.filename.split('/').pop();
            moveFile(draggedFile, fullPath + '/' + basename);
            draggedFile = null;
        } else if (draggedFolder && draggedFolder.path !== fullPath &&
                   !fullPath.startsWith(draggedFolder.path + '/')) {
            var folderName = draggedFolder.path.split('/').pop();
            moveFolder(draggedFolder.path, fullPath + '/' + folderName);
            draggedFolder = null;
        } else if (draggedImage) {
            var imgBasename = draggedImage.filename.split('/').pop();
            moveImage(draggedImage.filename, fullPath + '/' + imgBasename);
            draggedImage = null;
        }
    });
    return div;
}

function toggleFolder(path) {
    if (collapsedFolders.has(path)) collapsedFolders.delete(path);
    else collapsedFolders.add(path);
    renderFileList();
}

function makeFileItem(id, filename, isActive, depth) {
    depth = depth || 0;
    var displayName = filename.split('/').pop();
    var div = document.createElement('div');
    div.className = 'file-item' + (isActive ? ' active' : '');
    div.style.paddingLeft = (12 + depth * 16) + 'px';
    div.innerHTML =
        '<i class="' + (id === null ? 'ri-file-text-line' : 'ri-file-line') + '"></i>' +
        '<span class="file-name">' + escHtml(displayName) + '</span>';
    div.onclick = function(e) {
        if (e.target.closest('.file-del')) return;
        if (e.detail >= 2) return;
        switchFile({ id: id, filename: filename });
    };
    if (id !== null) {
        div.addEventListener('dblclick', function(e) {
            e.stopPropagation();
            var dir = filename.lastIndexOf('/') >= 0 ? filename.slice(0, filename.lastIndexOf('/') + 1) : '';
            var base = filename.split('/').pop();
            var newName = prompt('Rename:', base);
            if (!newName || newName === base) return;
            moveFile({ id: id, filename: filename }, dir + newName);
        });
    }
    // Drag source (not for main.typ)
    if (id !== null) {
        div.draggable = true;
        div.addEventListener('dragstart', function(e) {
            draggedFile = { id: id, filename: filename };
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', filename);
            setTimeout(function() { div.classList.add('dragging'); }, 0);
        });
        div.addEventListener('dragend', function() {
            div.classList.remove('dragging');
            draggedFile = null;
        });
        var del = document.createElement('button');
        del.className = 'file-del';
        del.title = 'Delete file';
        del.innerHTML = '<i class="ri-close-line"></i>';
        del.onclick = function(e) { e.stopPropagation(); deleteFile(id); };
        div.appendChild(del);
    }
    return div;
}

function moveFile(file, newFilename) {
    if (file.filename === newFilename) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/file_rename.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (!res.ok) { alert(res.error || 'Move failed'); return; }
        // Update local state
        var entry = extraFiles.find(function(f) { return f.id === file.id; });
        if (entry) entry.filename = res.filename;
        if (activeFile.id === file.id) activeFile.filename = res.filename;
        renderFileList();
    };
    xhr.send('id=' + file.id + '&document_id=' + DOC_ID + '&filename=' + encodeURIComponent(newFilename));
}

function moveFolder(oldPath, newPath) {
    if (oldPath === newPath) return;
    var toMove = extraFiles.filter(function(f) {
        return f.filename === oldPath || f.filename.startsWith(oldPath + '/');
    });
    // Update localFolders bookkeeping
    var updatedLocal = [];
    localFolders.forEach(function(p) {
        if (p === oldPath || p.startsWith(oldPath + '/')) {
            updatedLocal.push([p, newPath + p.slice(oldPath.length)]);
        }
    });
    updatedLocal.forEach(function(pair) { localFolders.delete(pair[0]); localFolders.add(pair[1]); });
    if (!toMove.length) { renderFileList(); return; }
    var pending = toMove.length;
    toMove.forEach(function(file) {
        var newFilename = newPath + file.filename.slice(oldPath.length);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/file_rename.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            var res;
            try { res = JSON.parse(xhr.responseText); } catch(e) {}
            if (res && res.ok) {
                file.filename = res.filename;
                if (activeFile.id === file.id) activeFile.filename = res.filename;
            }
            if (--pending === 0) renderFileList();
        };
        xhr.onerror = function() { if (--pending === 0) renderFileList(); };
        xhr.send('id=' + file.id + '&document_id=' + DOC_ID + '&filename=' + encodeURIComponent(newFilename));
    });
}

function deleteFolder(path) {
    var filesToDelete  = extraFiles.filter(function(f) {
        return f.filename === path || f.filename.startsWith(path + '/');
    });
    var imagesToDelete = projectImages.filter(function(name) {
        return name === path || name.startsWith(path + '/');
    });
    var total = filesToDelete.length + imagesToDelete.length;
    var label = path + (total ? ' (' + total + ' item' + (total !== 1 ? 's' : '') + ')' : '');
    if (!confirm('Delete folder "' + label + '"? This cannot be undone.')) return;

    if (activeFile.id !== null && (activeFile.filename === path || activeFile.filename.startsWith(path + '/'))) {
        switchFile({ id: null, filename: 'main.typ' });
    }
    localFolders.delete(path);
    collapsedFolders.delete(path);

    if (!total) { renderFileList(); return; }
    var pending = total;
    function onDone() { if (--pending === 0) { loadImageList(); renderFileList(); } }

    filesToDelete.forEach(function(file) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/file_delete.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            delete fileCache[file.id];
            extraFiles = extraFiles.filter(function(f) { return f.id !== file.id; });
            onDone();
        };
        xhr.onerror = onDone;
        xhr.send('id=' + file.id + '&document_id=' + DOC_ID);
    });

    imagesToDelete.forEach(function(name) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/image_delete.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = onDone;
        xhr.onerror = onDone;
        xhr.send('document_id=' + DOC_ID + '&filename=' + encodeURIComponent(name));
    });
}

function addFolder() {
    var name = prompt('New folder name:');
    if (!name) return;
    name = name.trim().replace(/[^a-zA-Z0-9._\-\/]/g, '_').replace(/^\/+|\/+$/g, '');
    if (!name) return;
    localFolders.add(name);
    collapsedFolders.delete(name); // ensure it's visible
    renderFileList();
}

function switchFile(file) {
    // Flush current editor content to cache
    if (activeFile.id === null) {
        // main file: nothing to cache (main content is always in editor when active)
    } else {
        fileCache[activeFile.id] = editor.getValue();
    }
    // Save current file immediately
    saveCurrentFile();

    activeFile = file;

    if (file.id === null) {
        // Switching to main file — content is already in fileCache or we just read it
        // The main file content is tracked separately: restore from mainContent
        editor.setValue(mainContent);
    } else {
        if (fileCache[file.id] !== undefined) {
            editor.setValue(fileCache[file.id]);
        } else {
            // Fetch from server
            editor.setValue('');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/file_get.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                var res;
                try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
                if (res.ok) {
                    fileCache[file.id] = res.content;
                    if (activeFile.id === file.id) editor.setValue(res.content);
                }
            };
            xhr.send('id=' + file.id + '&document_id=' + DOC_ID);
        }
    }
    renderFileList();
    updateStats();
    updateCompileBtn();
}

// mainContent tracks the in-memory main file content
var mainContent = <?= json_encode($doc['content'] ?? '') ?>;

function addFile() {
    var name = prompt('New file path (e.g. chapter1.typ or chapters/intro.typ):');
    if (!name) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/file_new.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (res.ok) {
            fileCache[res.id] = '';
            loadFileList();
            switchFile({ id: res.id, filename: res.filename });
        } else {
            alert(res.error || 'Error creating file');
        }
    };
    xhr.send('document_id=' + DOC_ID + '&filename=' + encodeURIComponent(name));
}

function deleteFile(id) {
    var f = extraFiles.find(function(x) { return x.id == id; });
    if (!confirm('Delete ' + (f ? f.filename : 'this file') + '?')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/file_delete.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        delete fileCache[id];
        if (activeFile.id === id) switchFile({ id: null, filename: 'main.typ' });
        loadFileList();
    };
    xhr.send('id=' + id + '&document_id=' + DOC_ID);
}

// ── Image management ──────────────────────────────────────────────────────────
function makeImageItem(filename, depth) {
    depth = depth || 0;
    var displayName = filename.split('/').pop();
    var div = document.createElement('div');
    div.className = 'file-item';
    div.style.paddingLeft = (12 + depth * 16) + 'px';
    var isFont = /\.(ttf|otf|woff2?|eot)$/i.test(filename);
    div.innerHTML =
        '<i class="' + (isFont ? 'ri-font-size' : 'ri-image-line') + '" style="color:' + (isFont ? '#ce93d8' : '#f48fb1') + '"></i>' +
        '<span class="file-name">' + escHtml(displayName) + '</span>';
    div.onclick = function(e) {
        if (e.target.closest('.file-del')) return;
        if (e.detail >= 2) return;
        if (!isFont) openLightbox(filename);
    };
    div.addEventListener('dblclick', function(e) {
        e.stopPropagation();
        var dir = filename.lastIndexOf('/') >= 0 ? filename.slice(0, filename.lastIndexOf('/') + 1) : '';
        var base = filename.split('/').pop();
        var newName = prompt('Rename:', base);
        if (!newName || newName === base) return;
        moveImage(filename, dir + newName);
    });
    div.draggable = true;
    div.addEventListener('dragstart', function(e) {
        draggedImage = { filename: filename };
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', filename);
        setTimeout(function() { div.classList.add('dragging'); }, 0);
    });
    div.addEventListener('dragend', function() {
        div.classList.remove('dragging');
        draggedImage = null;
    });
    var del = document.createElement('button');
    del.className = 'file-del';
    del.title = 'Delete image';
    del.innerHTML = '<i class="ri-close-line"></i>';
    del.onclick = function(e) { e.stopPropagation(); deleteImage(filename); };
    div.appendChild(del);
    return div;
}

function openLightbox(filename) {
    var lb = document.getElementById('imgLightbox');
    var img = document.getElementById('imgLightboxImg');
    document.getElementById('imgLightboxName').textContent = filename;
    img.src = 'api/image_serve.php?document_id=' + DOC_ID + '&filename=' + encodeURIComponent(filename);
    lb.classList.add('open');
    document.addEventListener('keydown', lightboxEscHandler);
}

function closeLightbox(e) {
    if (e && e.target !== document.getElementById('imgLightbox') &&
        !e.target.closest('.img-lightbox-close')) return;
    document.getElementById('imgLightbox').classList.remove('open');
    document.getElementById('imgLightboxImg').src = '';
    document.removeEventListener('keydown', lightboxEscHandler);
}

function lightboxEscHandler(e) {
    if (e.key === 'Escape') closeLightbox();
}

function loadImageList() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/image_list.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        projectImages = res.ok ? res.images : [];
        renderFileList();
    };
    xhr.send('document_id=' + DOC_ID);
}

function uploadImage(file) {
    if (projectImages.indexOf(file.name) !== -1) {
        if (!confirm('"' + file.name + '" already exists. Overwrite it?')) return;
    }
    var formData = new FormData();
    formData.append('document_id', DOC_ID);
    formData.append('file', file);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/image_upload.php', true);
    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (res.ok) { loadImageList(); } else { alert(res.error || 'Upload failed'); }
    };
    xhr.send(formData);
}

function deleteImage(filename) {
    if (!confirm('Delete "' + filename + '"?')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/image_delete.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() { loadImageList(); };
    xhr.send('document_id=' + DOC_ID + '&filename=' + encodeURIComponent(filename));
}

function moveImage(oldFilename, newFilename) {
    if (oldFilename === newFilename) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/image_move.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
        if (!res.ok) { alert(res.error || 'Move failed'); return; }
        var idx = projectImages.indexOf(oldFilename);
        if (idx !== -1) projectImages[idx] = res.filename;
        renderFileList();
    };
    xhr.send('document_id=' + DOC_ID +
        '&old_filename=' + encodeURIComponent(oldFilename) +
        '&new_filename=' + encodeURIComponent(newFilename));
}

function handleFileUpload(input) {
    var files = Array.from(input.files);
    if (!files.length) return;
    input.value = '';

    var hasRelPaths = files.some(function(f) { return !!f.webkitRelativePath; });

    if (files.length === 1 && !hasRelPaths) {
        handleSingleFileUpload(files[0], files[0].name);
        return;
    }

    // Multi-file or folder upload
    var items = files.map(function(file) {
        var filename = hasRelPaths && file.webkitRelativePath
            ? file.webkitRelativePath
            : file.name;
        return { file: file, filename: filename };
    }).filter(function(item) { return !!item.filename; });

    if (!items.length) return;
    setIndicator('Uploading ' + items.length + ' files…');

    var pending = items.length;
    var needFileRefresh = false;
    var needImageRefresh = false;

    function onDone(isImg) {
        if (isImg) needImageRefresh = true; else needFileRefresh = true;
        if (--pending === 0) {
            if (needFileRefresh)  loadFileList();
            if (needImageRefresh) loadImageList();
            setIndicator('Uploaded');
        }
    }

    items.forEach(function(item) {
        if (/\.(png|jpe?g|gif|webp|svg|pdf|ttf|otf|woff2?|eot)$/i.test(item.filename)) {
            var fd = new FormData();
            fd.append('document_id', DOC_ID);
            fd.append('file', item.file, item.filename.split('/').pop());
            fd.append('path', item.filename);
            var x = new XMLHttpRequest();
            x.open('POST', 'api/image_upload.php', true);
            x.onload = function() { onDone(true); };
            x.onerror = function() { onDone(true); };
            x.send(fd);
        } else {
            var cf = item.filename;
            var reader = new FileReader();
            reader.onload = function(e) { batchSaveTextFile(cf, e.target.result, function() { onDone(false); }); };
            reader.onerror = function() { onDone(false); };
            reader.readAsText(item.file);
        }
    });
}

function handleSingleFileUpload(file, filename) {
    if (/\.(png|jpe?g|gif|webp|svg|ttf|otf|woff2?|eot)$/i.test(filename)) {
        uploadImage(file);
        return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
        var content = e.target.result;
        if (filename === 'main.typ') {
            if (!confirm('"main.typ" already exists. Overwrite it?')) return;
            mainContent = content;
            if (activeFile.id === null) editor.setValue(content);
            saveDoc();
        } else {
            var existing = extraFiles.find(function(f) { return f.filename === filename; });
            if (existing) {
                if (!confirm('"' + filename + '" already exists. Overwrite it?')) return;
                fileCache[existing.id] = content;
                saveExtraFile(existing.id, content);
                if (activeFile.id === existing.id) editor.setValue(content);
            } else {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'api/file_new.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    var res;
                    try { res = JSON.parse(xhr.responseText); } catch(e) { return; }
                    if (res.ok) {
                        fileCache[res.id] = content;
                        saveExtraFile(res.id, content);
                        loadFileList();
                    } else {
                        alert(res.error || 'Error uploading file');
                    }
                };
                xhr.send('document_id=' + DOC_ID + '&filename=' + encodeURIComponent(filename));
            }
        }
    };
    reader.readAsText(file);
}

function batchSaveTextFile(filename, content, onComplete) {
    if (filename === 'main.typ') {
        mainContent = content;
        if (activeFile.id === null) editor.setValue(content);
        var title = document.getElementById('docTitle').value || 'Untitled Document';
        var x1 = new XMLHttpRequest();
        x1.open('POST', 'api/save.php', true);
        x1.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x1.onload = function() { onComplete(); };
        x1.onerror = function() { onComplete(); };
        x1.send('id=' + DOC_ID + '&title=' + encodeURIComponent(title) + '&content=' + encodeURIComponent(content));
        return;
    }
    var existing = extraFiles.find(function(f) { return f.filename === filename; });
    if (existing) {
        fileCache[existing.id] = content;
        if (activeFile.id === existing.id) editor.setValue(content);
        var x2 = new XMLHttpRequest();
        x2.open('POST', 'api/file_save.php', true);
        x2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x2.onload = function() { onComplete(); };
        x2.onerror = function() { onComplete(); };
        x2.send('id=' + existing.id + '&document_id=' + DOC_ID + '&content=' + encodeURIComponent(content));
    } else {
        var x3 = new XMLHttpRequest();
        x3.open('POST', 'api/file_new.php', true);
        x3.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x3.onload = function() {
            var res;
            try { res = JSON.parse(x3.responseText); } catch(e) { onComplete(); return; }
            if (!res.ok) { onComplete(); return; }
            fileCache[res.id] = content;
            var x4 = new XMLHttpRequest();
            x4.open('POST', 'api/file_save.php', true);
            x4.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            x4.onload = function() { onComplete(); };
            x4.onerror = function() { onComplete(); };
            x4.send('id=' + res.id + '&document_id=' + DOC_ID + '&content=' + encodeURIComponent(content));
        };
        x3.onerror = function() { onComplete(); };
        x3.send('document_id=' + DOC_ID + '&filename=' + encodeURIComponent(filename));
    }
}

// ── Sidebar stats ─────────────────────────────────────────────────────────────
function stripTypstMarkup(src) {
    // Raw blocks (```...```) and inline code (`...`) — not prose
    src = src.replace(/```[\s\S]*?```/g, ' ');
    src = src.replace(/`[^`\n]+`/g, ' ');
    // Block and line comments
    src = src.replace(/\/\*[\s\S]*?\*\//g, ' ');
    src = src.replace(/\/\/[^\n]*/g, '');
    // Math ($$ ... $$ and $ ... $)
    src = src.replace(/\$\$[\s\S]*?\$\$/g, ' ');
    src = src.replace(/\$[^$\n]+\$/g, ' ');
    // Code blocks #{ ... }
    src = src.replace(/#\{[^}]*\}/g, ' ');
    // Full-line code directives
    src = src.replace(/^[ \t]*#(let|import|include|show|set|if|else|for|while|return|break|continue)\b[^\n]*/gm, '');
    // #func(args)[content] → keep content; #func(args) or bare #word → remove
    src = src.replace(/#[\w.]+(?:\([^)]*\))?\[([^\]]*)\]/g, '$1');
    src = src.replace(/#[\w.]+(?:\([^)]*\))?/g, '');
    // Heading markers at line start (= == ===)
    src = src.replace(/^=+\s*/gm, '');
    // List/enum markers at line start
    src = src.replace(/^[ \t]*[-+]\s+/gm, '');
    src = src.replace(/^[ \t]*\d+\.\s+/gm, '');
    // @references and <labels>
    src = src.replace(/@[\w:-]+/g, '');
    src = src.replace(/<[\w:-]+>/g, '');
    // Leftover content-block brackets
    src = src.replace(/[[\]]/g, ' ');
    // Bold/italic markers (* _ **)
    src = src.replace(/[*_]+/g, ' ');
    // URLs
    src = src.replace(/https?:\/\/\S+/g, ' ');
    return src;
}

function updateStats() {
    var val = editor.getValue();
    var prose = stripTypstMarkup(val);
    var words = prose.trim() ? prose.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length : 0;
    var chars = prose.replace(/\s/g, '').length;
    document.getElementById('sidebarChars').textContent = chars;
    document.getElementById('sidebarLines').textContent = editor.lineCount();
    document.getElementById('sidebarWords').textContent = words;
    updateSelectionStats();
}

function updateSelectionStats() {
    var sel = editor.getSelection();
    var words = 0, chars = 0;
    if (sel) {
        var prose = stripTypstMarkup(sel);
        words = prose.trim() ? prose.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length : 0;
        chars = prose.replace(/\s/g, '').length;
    }
    document.getElementById('sidebarSelWords').textContent = words;
    document.getElementById('sidebarSelChars').textContent = chars;
}
editor.on('change', function() {
    if (activeFile.id === null) mainContent = editor.getValue();
    updateStats();
    isDirty = true;
    clearTimeout(saveTimer);
    clearTimeout(liveTimer);
    setIndicator('…');
    saveTimer = setTimeout(saveCurrentFile, 2000);
    if (liveMode) {
        liveTimer = setTimeout(function() {
            var content = editor.getValue();
            if (!document.getElementById('compileBtn').disabled && content !== lastCompiledContent) {
                compileDoc();
            }
        }, 1500);
    }
});
editor.on('cursorActivity', updateSelectionStats);
updateStats();
loadFileList();
loadImageList();
setTimeout(compileDoc, 300);

// ── Autocomplete trigger ──────────────────────────────────────────────────────
editor.on('inputRead', function(cm, change) {
    if (change.origin !== '+input') return;
    if (cm.state.completionActive) return;
    var cur = cm.getCursor();
    var before = cm.getLine(cur.line).slice(0, cur.ch);
    if (/#[a-zA-Z]+$/.test(before)) {
        CodeMirror.showHint(cm, typstHint, { completeSingle: false });
    } else if (/@[a-zA-Z0-9._:-]*$/.test(before) || /#cite\s*\(\s*<[a-zA-Z0-9._:-]*$/.test(before)) {
        var keys = getCitationKeys();
        if (keys.length) CodeMirror.showHint(cm, citationHint, { completeSingle: false });
    }
});

// ── Save ──────────────────────────────────────────────────────────────────────
function setIndicator(msg) {
    document.getElementById('saveIndicator').textContent = msg;
}

function saveCurrentFile() {
    if (activeFile.id === null) {
        saveDoc();
    } else {
        saveExtraFile(activeFile.id, editor.getValue());
    }
}

function saveDoc() {
    clearTimeout(saveTimer);
    if (isSaving) return;
    isSaving = true;
    setIndicator('Saving…');
    var title   = document.getElementById('docTitle').value || 'Untitled Document';
    var content = (activeFile.id === null) ? editor.getValue() : mainContent;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/save.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        isSaving = false;
        isDirty = false;
        var now = new Date();
        setIndicator('Saved ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0'));
        document.getElementById('sidebarSaved').textContent =
            now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    };
    xhr.onerror = function() { isSaving = false; setIndicator('Error'); };
    xhr.send('id=' + DOC_ID + '&title=' + encodeURIComponent(title) + '&content=' + encodeURIComponent(content));
}

function saveExtraFile(id, content) {
    fileCache[id] = content;
    setIndicator('Saving…');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/file_save.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        isDirty = false;
        var now = new Date();
        setIndicator('Saved ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0'));
        document.getElementById('sidebarSaved').textContent =
            now.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    };
    xhr.onerror = function() { setIndicator('Error'); };
    xhr.send('id=' + id + '&document_id=' + DOC_ID + '&content=' + encodeURIComponent(content));
}

// ── Compile ───────────────────────────────────────────────────────────────────
function updateCompileBtn() {
    var btn = document.getElementById('compileBtn');
    var isMd = /\.md$/i.test(activeFile.filename);
    btn.disabled = isMd;
    btn.title = isMd ? 'Cannot compile Markdown files' : '';
}

function compileDoc() {
    if (/\.md$/i.test(activeFile.filename)) return;

    // Fetch any extra files not yet in cache before compiling
    var missing = extraFiles.filter(function(f) { return fileCache[f.id] === undefined; });
    if (missing.length > 0) {
        var btn = document.getElementById('compileBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-refresh-line" style="animation:spin 1s linear infinite"></i>Loading…';
        var pending = missing.length;
        missing.forEach(function(f) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/file_get.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                var res; try { res = JSON.parse(xhr.responseText); } catch(e) {}
                fileCache[f.id] = (res && res.ok) ? res.content : '';
                if (--pending === 0) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-play-fill"></i>Compile';
                    compileDoc();
                }
            };
            xhr.onerror = function() {
                fileCache[f.id] = '';
                if (--pending === 0) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ri-play-fill"></i>Compile';
                    compileDoc();
                }
            };
            xhr.send('id=' + f.id + '&document_id=' + DOC_ID);
        });
        return;
    }
    var btn = document.getElementById('compileBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-refresh-line" style="animation:spin 1s linear infinite"></i>Compiling…';
    document.getElementById('sidebarStatus').textContent = 'Compiling…';
    document.getElementById('liveProgress').classList.add('running');
    var t0 = Date.now();

    // Flush current editor content before compiling
    if (activeFile.id === null) {
        mainContent = editor.getValue();
    } else {
        fileCache[activeFile.id] = editor.getValue();
    }
    lastCompiledContent = editor.getValue();

    // Build extra files array from cache
    var allFiles = [];
    extraFiles.forEach(function(f) {
        var content = fileCache[f.id] !== undefined ? fileCache[f.id] : '';
        allFiles.push({ filename: f.filename, content: content });
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/compile.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-play-fill"></i>Compile';
        document.getElementById('liveProgress').classList.remove('running');
        var elapsed = ((Date.now() - t0) / 1000).toFixed(2) + 's';
        document.getElementById('sidebarTime').textContent = elapsed;
        var res;
        try { res = JSON.parse(xhr.responseText); } catch(e) {
            showError("Invalid server response:\n" + xhr.responseText);
            return;
        }
        if (res.ok) {
            document.getElementById('sidebarStatus').textContent = '✓ OK';
            document.getElementById('previewLabel').textContent = 'PDF output (' + elapsed + ')';
            showPDF(res.pdf);
        } else {
            document.getElementById('sidebarStatus').textContent = '✗ Error';
            document.getElementById('previewLabel').textContent = 'Compile error';
            showError(res.error);
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-play-fill"></i>Compile';
        document.getElementById('liveProgress').classList.remove('running');
        showError("Network error");
    };
    var payload_content, payload_files;
    if (entryFile === 'main.typ') {
        payload_content = mainContent;
        payload_files   = allFiles;
    } else {
        var entryObj = extraFiles.find(function(f) { return f.filename === entryFile; });
        if (!entryObj) {
            payload_content = mainContent;
            payload_files   = allFiles;
        } else {
            payload_content = fileCache[entryObj.id] !== undefined ? fileCache[entryObj.id] : '';
            payload_files   = [{filename: 'main.typ', content: mainContent}].concat(
                allFiles.filter(function(f) { return f.filename !== entryFile; })
            );
        }
    }
    var body = 'id=' + DOC_ID + '&content=' + encodeURIComponent(payload_content) + '&entry=' + encodeURIComponent(entryFile);
    if (payload_files.length > 0) body += '&files=' + encodeURIComponent(JSON.stringify(payload_files));
    xhr.send(body);
}

function showPDF(b64) {
    var binary = atob(b64);
    var bytes  = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
    var blob = new Blob([bytes], {type: 'application/pdf'});
    var url  = URL.createObjectURL(blob);

    var wrap = document.getElementById('previewWrap');
    var iframe = wrap.querySelector('iframe');
    if (!iframe) {
        wrap.innerHTML = '';
        iframe = document.createElement('iframe');
        iframe.style.cssText = 'width:100%;height:100%;border:none;display:block;';
        wrap.appendChild(iframe);
    } else if (iframe._blobUrl) {
        URL.revokeObjectURL(iframe._blobUrl);
    }
    iframe._blobUrl = url;
    iframe.src = url;
}

function showError(msg) {
    var wrap = document.getElementById('previewWrap');
    var errDiv = wrap.querySelector('.preview-error');
    if (!errDiv) {
        wrap.innerHTML = '';
        errDiv = document.createElement('div');
        errDiv.className = 'preview-error';
        wrap.appendChild(errDiv);
    }
    errDiv.innerHTML = '<pre>' + escHtml(msg) + '</pre>';
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Drag handle for resizing preview ─────────────────────────────────────────
(function() {
    var handle = document.getElementById('dragHandle');
    var preview = document.getElementById('previewPane');
    var dragging = false;
    var startX, startW;
    handle.addEventListener('mousedown', function(e) {
        dragging = true;
        startX = e.clientX;
        startW = preview.offsetWidth;
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        var delta = startX - e.clientX;
        var newW  = Math.max(150, Math.min(startW + delta, window.innerWidth - 300));
        preview.style.width = newW + 'px';
    });
    document.addEventListener('mouseup', function() {
        if (dragging) {
            dragging = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
            editor.refresh();
        }
    });
})();

// ── Spinner keyframe ──────────────────────────────────────────────────────────
var style = document.createElement('style');
style.textContent = '@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }';
document.head.appendChild(style);

// Warn on unload if dirty
window.addEventListener('beforeunload', function(e) {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});

function toggleUserMenu(e) {
    e.stopPropagation();
    document.getElementById('userMenu').classList.toggle('open');
}
document.addEventListener('click', function() {
    var m = document.getElementById('userMenu');
    if (m) m.classList.remove('open');
});
</script>
</body>
</html>
