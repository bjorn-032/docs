<?php header('Content-type: text/css'); ?>
@font-face {
    font-family: 'BricolageGrotesque';
    src: url('../fonts/BricolageGrotesque-VariableFont_opsz,wdth,wght.ttf') format('truetype');
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
}
:root {
    --colorTheme: #3838BA;
    --colorThemeLight: #4CAF50;
}
.dark {
    --background: #141414;
    --navigation: #262626;
    --sidebar: #1e1e1e;
    --text: #FFFFFF;
    --lighterText: #d5d5d5;
    --grayText: #bdbdbd;
    --coninfo: #303030;
    --border: #3c3c3c;
    --hover: rgba(255,255,255,0.06);
    --card: #1e1e1e;
    --input-bg: #2a2a2a;
}
.light {
    --background: #f5f5f5;
    --navigation: #ffffff;
    --sidebar: #fafafa;
    --text: #212121;
    --lighterText: #3c3c3c;
    --grayText: #757575;
    --coninfo: #eeeeee;
    --border: #e0e0e0;
    --hover: rgba(0,0,0,0.04);
    --card: #ffffff;
    --input-bg: #f5f5f5;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: "Poppins", sans-serif;
    background-color: var(--background);
    color: var(--text);
    overflow: hidden;
    height: 100vh;
}
a { color: inherit; text-decoration: none; }
button { cursor: pointer; font-family: inherit; }
h1, h2, h3,
.topbar-title,
.editor-title-input,
.doc-card-title,
.settings-section-title,
.library-header h1 {
    font-family: 'BricolageGrotesque', "Poppins", sans-serif;
}
button:focus { outline: 0; }

/* ── Top bar ── */
.topbar {
    height: 56px;
    background-color: var(--navigation);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 16px;
    gap: 12px;
    position: relative;
    z-index: 10;
}
.topbar-logo {
    height: 32px;
    width: auto;
}
.light .topbar-logo { filter: invert(1); }
.topbar-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
    flex: 1;
}
.topbar-btn {
    background: var(--colorTheme);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 14px;
    font-weight: 500;
    transition: opacity .15s;
}
.topbar-btn:hover { opacity: .85; }
.topbar-btn.secondary {
    background: var(--coninfo);
    color: var(--text);
}
.topbar-username {
    font-size: 14px;
    color: var(--grayText);
}
.topbar-icon-btn {
    background: none;
    border: none;
    color: var(--text);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    flex-shrink: 0;
}
.topbar-icon-btn:hover { background: var(--hover); }
.topbar-icon-btn i { font-size: 24px; }

/* ── Settings page ── */
.settings-body {
    max-width: 560px;
    margin: 40px auto;
    padding: 0 20px;
}
.settings-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
}
.settings-section-title {
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--grayText);
    margin-bottom: 20px;
}
.settings-row-label {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 14px;
}
.theme-options {
    display: flex;
    gap: 16px;
}
.theme-card {
    flex: 1;
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: 12px;
    cursor: pointer;
    text-align: center;
    transition: border-color .15s, box-shadow .15s;
}
.theme-card:hover { border-color: var(--colorTheme); }
.theme-card.active {
    border-color: var(--colorTheme);
    box-shadow: 0 0 0 3px rgba(56,56,186,.15);
}
.theme-preview {
    height: 80px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 10px;
    border: 1px solid rgba(0,0,0,.08);
}
.light-preview { background: #f0f0f5; color: #212121; }
.dark-preview  { background: #1e1e2e; color: #ffffff; }
.theme-card-label {
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: var(--text);
}
.theme-check {
    font-size: 16px;
    color: var(--colorTheme);
    display: none;
}
.theme-card.active .theme-check { display: inline-block; }

/* ── Library page ── */
.library-body {
    height: calc(100vh - 56px);
    overflow-y: auto;
    padding: 40px;
}
.library-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
}
.library-header h1 {
    font-size: 22px;
    font-weight: 600;
}
.doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}
.doc-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: box-shadow .15s, transform .1s;
    position: relative;
}
.doc-card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    transform: translateY(-2px);
}
.doc-card-preview {
    height: 140px;
    background: #1a1a2e;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #4a4a8a;
    user-select: none;
}
.light .doc-card-preview {
    background: #e8eaf6;
    color: #9fa8da;
}
.doc-card-body {
    padding: 12px 14px;
}
.doc-card-title {
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}
.doc-card-date {
    font-size: 12px;
    color: var(--grayText);
}
.doc-card-delete {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(220,53,69,.85);
    border: none;
    border-radius: 6px;
    color: white;
    width: 28px;
    height: 28px;
    font-size: 14px;
    display: none;
    align-items: center;
    justify-content: center;
}
.doc-card:hover .doc-card-delete { display: flex; }

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: var(--grayText);
}
.empty-state i { font-size: 64px; display: block; margin-bottom: 16px; }

/* ── Editor layout ── */
.editor-shell {
    display: flex;
    height: calc(100vh - 56px);
}
.editor-topbar {
    height: 56px;
    background-color: var(--navigation);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 12px;
    gap: 10px;
    position: relative;
    z-index: 10;
}
.editor-topbar-back {
    background: none;
    border: none;
    color: var(--text);
    font-size: 22px;
    display: flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 6px;
}
.editor-topbar-back:hover { background: var(--hover); }
.editor-topbar-back i { font-size: 24px; }
.editor-title-input {
    flex: 1;
    background: none;
    border: none;
    color: var(--text);
    font-size: 16px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 6px;
    min-width: 0;
}
.editor-title-input:focus {
    outline: none;
    background: var(--hover);
}
.save-indicator {
    font-size: 12px;
    color: var(--grayText);
    min-width: 60px;
    text-align: right;
}
.compile-btn {
    background: var(--colorThemeLight);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: opacity .15s;
}
.compile-btn:hover { opacity: .85; }
.compile-btn:disabled { opacity: .5; cursor: not-allowed; }
.compile-btn i { font-size: 18px; }
.img-lightbox-close i { font-size: 18px; }
.live-switch {
    display: flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--grayText);
    user-select: none;
    padding: 0 4px;
}
.live-switch input { display: none; }
.live-switch-track {
    width: 34px;
    height: 19px;
    background: var(--border);
    border-radius: 10px;
    position: relative;
    flex-shrink: 0;
    transition: background .2s;
}
.live-switch-track::after {
    content: '';
    position: absolute;
    width: 13px;
    height: 13px;
    background: #fff;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.live-switch input:checked ~ .live-switch-track { background: var(--colorThemeLight); }
.live-switch input:checked ~ .live-switch-track::after { transform: translateX(15px); }
.live-switch input:checked ~ .live-switch-label { color: var(--text); }

/* ── Activity bar (icon rail) ── */
.activity-bar {
    width: 44px;
    min-width: 44px;
    background: var(--navigation);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 8px;
    gap: 4px;
    flex-shrink: 0;
}
.activity-btn {
    width: 36px;
    height: 36px;
    background: none;
    border: none;
    color: var(--grayText);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .15s, color .15s;
}
.activity-btn i { font-size: 20px; }
.activity-btn:hover { background: var(--hover); color: var(--text); }
.activity-btn.active { color: var(--text); background: var(--hover); }

/* ── Side panel ── */
.side-panel {
    width: 220px;
    min-width: 220px;
    background: var(--sidebar);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: width .18s ease, min-width .18s ease;
    flex-shrink: 0;
}
.side-panel.collapsed { width: 0; min-width: 0; border-right: none; }
.panel-pane { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 12px 8px;
    flex-shrink: 0;
}
.panel-title {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--grayText);
}
.panel-icon-btn {
    background: none;
    border: none;
    color: var(--grayText);
    display: flex;
    align-items: center;
    border-radius: 4px;
    padding: 2px;
    cursor: pointer;
}
.panel-icon-btn:hover { background: var(--hover); color: var(--text); }
.panel-icon-btn i { font-size: 15px; }

/* ── File list ── */
.file-list { overflow-y: auto; flex: 1; }
.file-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    font-size: 12px;
    cursor: pointer;
    color: var(--lighterText);
    position: relative;
    white-space: nowrap;
}
.file-item:hover, .file-item.active { background: var(--hover); color: var(--text); }
.file-item > i { font-size: 15px; color: var(--grayText); flex-shrink: 0; }
.file-item.active > i { color: var(--colorTheme); }
.file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; }
.file-del {
    display: none;
    background: none;
    border: none;
    color: var(--grayText);
    cursor: pointer;
    padding: 0 2px;
    border-radius: 3px;
    flex-shrink: 0;
    align-items: center;
}
.file-del > i { font-size: 14px; }
.file-item:hover .file-del { display: flex; }
.file-del:hover { color: #e53935; }
.folder-item { cursor: pointer; user-select: none; }
.folder-item:hover { background: var(--hover); color: var(--text); }
.folder-chevron { font-size: 16px !important; color: var(--grayText); flex-shrink: 0; }
.folder-icon { font-size: 15px !important; color: #ffd54f; flex-shrink: 0; }
/* Drag and drop */
.file-item[draggable="true"] { cursor: grab; }
.file-item.dragging { opacity: .4; }
.file-item.drop-target, .folder-item.drop-target {
    background: rgba(56,56,186,.18) !important;
    outline: 1px dashed var(--colorTheme);
    outline-offset: -1px;
}

/* ── Image lightbox ── */
.img-lightbox {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.7);
    z-index: 500;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}
.img-lightbox.open { display: flex; }
.img-lightbox-box {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.5);
}
.img-lightbox-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    font-size: 13px;
    font-weight: 500;
    color: var(--text);
    flex-shrink: 0;
}
.img-lightbox-close {
    background: none;
    border: none;
    color: var(--grayText);
    display: flex;
    align-items: center;
    border-radius: 6px;
    padding: 2px;
    cursor: pointer;
}
.img-lightbox-close:hover { background: var(--hover); color: var(--text); }
.img-lightbox-body {
    overflow: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    min-height: 100px;
}
.img-lightbox-body img {
    max-width: 80vw;
    max-height: calc(90vh - 60px);
    object-fit: contain;
    border-radius: 4px;
    display: block;
}

.file-section-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--grayText);
    padding: 8px 12px 4px;
    opacity: 0.7;
}

/* ── Sidebar info (stats panel) ── */
.sidebar-section {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}
.sidebar-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--grayText);
    margin-bottom: 10px;
}
.sidebar-info-row {
    font-size: 12px;
    color: var(--lighterText);
    line-height: 24px;
    display: flex;
    justify-content: space-between;
}
.sidebar-info-row span:last-child { color: var(--grayText); }

/* ── Editor pane ── */
.editor-pane {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}
.CodeMirror {
    height: 100%;
    font-size: 14px;
    font-family: "JetBrains Mono", "Fira Code", "Cascadia Code", monospace;
    line-height: 1.7;
}
.CodeMirror-scroll { height: 100%; }

/* ── Drag handle ── */
.drag-handle {
    width: 5px;
    background: var(--border);
    cursor: col-resize;
    flex-shrink: 0;
    transition: background .15s;
}
.drag-handle:hover { background: var(--colorTheme); }

/* ── Preview pane ── */
.preview-pane {
    width: 45%;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    background: var(--sidebar);
    overflow: hidden;
}
.preview-toolbar {
    height: 36px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 12px;
    font-size: 12px;
    color: var(--grayText);
    gap: 8px;
    flex-shrink: 0;
}
.preview-iframe-wrap {
    flex: 1;
    overflow: hidden;
    position: relative;
}
.live-progress {
    height: 2px;
    flex-shrink: 0;
    background: var(--colorThemeLight);
    transform-origin: left;
    display: none;
}
.live-progress.running {
    display: block;
    animation: live-progress-anim 1.4s ease-in-out infinite;
}
@keyframes live-progress-anim {
    0%   { transform: scaleX(0.05); opacity: 1; }
    70%  { transform: scaleX(0.85); opacity: 1; }
    100% { transform: scaleX(1);    opacity: 0; }
}
.preview-iframe-wrap iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
.preview-placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--grayText);
    gap: 12px;
}
.preview-placeholder i { font-size: 48px; }
.preview-error {
    height: 100%;
    overflow-y: auto;
    padding: 16px;
}
.preview-error pre {
    background: #1a0000;
    color: #ff6b6b;
    padding: 16px;
    border-radius: 8px;
    font-size: 12px;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.6;
    border: 1px solid #3d0000;
}
