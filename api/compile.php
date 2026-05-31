<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
requireAuthApi();

$content    = $_POST['content'] ?? '';
$id         = (int)($_POST['id'] ?? 0);
$filesJson  = $_POST['files'] ?? '';
$extraFiles = $filesJson ? json_decode($filesJson, true) : [];
$format     = ($_POST['format'] ?? 'svg') === 'pdf' ? 'pdf' : 'svg';

$entry = trim($_POST['entry'] ?? 'main.typ', '/');
if ($entry === '' || strpos($entry, '..') !== false) $entry = 'main.typ';

$imgDir = __DIR__ . "/../data/{$id}";

// Stable per-document dir so symlinks survive across compiles.
$tmpDir = "/tmp/typst_proj_{$id}";
$inFile = "$tmpDir/$entry";

if (!is_dir($tmpDir)) mkdir($tmpDir, 0700, true);
$inDir = dirname($inFile);
if ($inDir !== $tmpDir && !is_dir($inDir)) mkdir($inDir, 0700, true);

// Symlink project assets into the temp dir so Typst can find them.
// Use a callback filter to skip .git entirely (iter->next() only skips one item, not the subtree).
$font_exts = ['ttf','otf','woff','woff2','eot'];
$font_dirs = [$tmpDir => true];
if (is_dir($imgDir)) {
    $dirIter = new RecursiveDirectoryIterator($imgDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $filtered = new RecursiveCallbackFilterIterator($dirIter, function($f) {
        return $f->getFilename() !== '.git';
    });
    $iter = new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $rel     = substr($file->getPathname(), strlen($imgDir) + 1);
        $dest    = "$tmpDir/$rel";
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0700, true);
        if (!file_exists($dest) && !is_link($dest)) symlink($file->getPathname(), $dest);
        if (in_array(strtolower($file->getExtension()), $font_exts)) {
            $font_dirs[$destDir] = true;
        }
    }
}

if (is_array($extraFiles)) {
    foreach ($extraFiles as $f) {
        $name = trim($f['filename'] ?? '', '/');
        if ($name === '' || $name === $entry) continue;
        if (strpos($name, '..') !== false) continue;
        $dest    = "$tmpDir/$name";
        $destDir = dirname($dest);
        if (!is_dir($destDir)) mkdir($destDir, 0700, true);
        if (is_link($dest)) unlink($dest);
        file_put_contents($dest, $f['content'] ?? '');
    }
}

$font_path_args = implode('', array_map(function($d) {
    return ' --font-path ' . escapeshellarg($d);
}, array_keys($font_dirs)));

// ---- Persistent typst watch process (incremental compilation) ----
$formatFlag = $format === 'svg' ? ' --format svg' : '';
$watchOut   = $format === 'pdf' ? "$tmpDir/.watch.pdf" : "$tmpDir/.watch-{p}.svg";
$pidFile    = "$tmpDir/.watch_{$format}.pid";
$logFile    = "$tmpDir/.watch_{$format}.log";
$stateFile  = "$tmpDir/.watch_{$format}.state";
$stateVal   = $entry . '|' . $font_path_args;

$watchPid   = file_exists($pidFile) ? (int)trim(file_get_contents($pidFile)) : 0;
$watchAlive = $watchPid > 0 && file_exists("/proc/$watchPid");
$needRestart = !$watchAlive
    || !file_exists($stateFile)
    || trim(file_get_contents($stateFile)) !== $stateVal;

if ($needRestart) {
    // Kill old process before writing, so it doesn't race with the new one.
    if ($watchAlive) shell_exec("kill $watchPid 2>/dev/null");

    // Ensure entry file is a real file, not a symlink to the data dir.
    if (is_link($inFile)) unlink($inFile);
    file_put_contents($inFile, $content);

    // Fresh log; poll from the start so the initial compile IS our compile (no wasted second compile).
    file_put_contents($logFile, '');
    $logPos = 0;

    $watchCmd = escapeshellcmd('/bin/typst') . " watch{$formatFlag} "
              . escapeshellarg($inFile) . ' ' . escapeshellarg($watchOut)
              . $font_path_args;
    $watchPid = (int)trim(shell_exec('nohup ' . $watchCmd . ' >' . escapeshellarg($logFile) . ' 2>&1 & echo $!'));
    file_put_contents($pidFile, (string)$watchPid);
    file_put_contents($stateFile, $stateVal);
} else {
    // Capture log position BEFORE writing to avoid a race where typst compiles
    // before we read the position.
    $logPos = file_exists($logFile) ? filesize($logFile) : 0;

    // Ensure entry file is a real file (symlink scan may have created one on fresh tmpdir).
    if (is_link($inFile)) unlink($inFile);
    file_put_contents($inFile, $content);
}

// Poll log for compile result. First compile (restart) may download packages so allow 30s;
// incremental compiles are typically <100ms so 10s is ample.
$timeout  = $needRestart ? 30.0 : 10.0;
$success  = null; // true = ok, false = errors, null = timeout
$errorMsg = '';
$deadline = microtime(true) + $timeout;
while (microtime(true) < $deadline) {
    usleep(25000); // 25ms
    if (!file_exists($logFile)) continue;
    // Read only the new tail — avoids loading the entire (ever-growing) log.
    $newLog = file_get_contents($logFile, false, null, $logPos);
    if ($newLog === '' || $newLog === false) continue;
    if (strpos($newLog, 'compiled successfully') !== false
        || strpos($newLog, 'compiled with warnings') !== false) {
        $success = true;
        break;
    }
    if (strpos($newLog, 'compiled with errors') !== false) {
        usleep(150000); // let the full error details flush
        $newLog = file_get_contents($logFile, false, null, $logPos);
        if (preg_match('/compiled with errors\s*\n(.*)/si', $newLog, $m)) {
            $errorMsg = trim($m[1]);
        }
        $success = false;
        break;
    }
}

if ($success === null) {
    // Timed out — restart watch if it crashed so next request is clean.
    if (!file_exists("/proc/$watchPid")) {
        file_put_contents($logFile, '');
        $watchCmd = escapeshellcmd('/bin/typst') . " watch{$formatFlag} "
                  . escapeshellarg($inFile) . ' ' . escapeshellarg($watchOut)
                  . $font_path_args;
        $watchPid = (int)trim(shell_exec('nohup ' . $watchCmd . ' >' . escapeshellarg($logFile) . ' 2>&1 & echo $!'));
        file_put_contents($pidFile, (string)$watchPid);
        file_put_contents($stateFile, $stateVal);
    }
    echo json_encode(['ok' => false, 'error' => "Compilation timed out after {$timeout}s"]);
    exit;
}

if (!$success) {
    echo json_encode(['ok' => false, 'error' => $errorMsg ?: 'Compilation failed']);
    exit;
}

if ($format === 'pdf') {
    $outPath = "$tmpDir/.watch.pdf";
    if (!file_exists($outPath)) {
        echo json_encode(['ok' => false, 'error' => 'Watch output file missing']);
        exit;
    }
    echo json_encode(['ok' => true, 'pdf' => base64_encode(file_get_contents($outPath))]);
} else {
    $svgFiles = glob("$tmpDir/.watch-*.svg") ?: [];
    if (!$svgFiles) {
        echo json_encode(['ok' => false, 'error' => 'No SVG output found']);
        exit;
    }
    natsort($svgFiles);
    $pages = array_map(function($f) {
        $svg = file_get_contents($f);
        $svg = preg_replace('/&(?![a-zA-Z][a-zA-Z0-9]*;|#[0-9]+;|#x[0-9a-fA-F]+;)/', '&amp;', $svg);
        return $svg;
    }, array_values($svgFiles));
    echo json_encode(['ok' => true, 'pages' => $pages], JSON_UNESCAPED_UNICODE);
}
