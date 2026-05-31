<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/session.php';
require __DIR__ . '/git_helpers.php';
$user = requireAuthApi();

$id       = (int)($_POST['id'] ?? 0);
$filename = $_POST['filename'] ?? '';

if ($filename === '' || strpos($filename, '..') !== false || ($filename[0] ?? '') === '/') {
    echo json_encode(['ok' => false, 'error' => 'Invalid filename']); exit;
}

$dir = getDocDir($id, $user);
if (!$dir) { echo json_encode(['ok' => false, 'error' => 'Not found']); exit; }

if (!is_dir($dir . '/.git')) {
    echo json_encode(['ok' => true, 'changes' => []]);
    exit;
}

// --unified=0 gives no context lines — only changed lines appear in the output.
[$code, $out] = runGit(['diff', 'HEAD', '--unified=0', '--', $filename], $dir);

$changes = parseDiff($out);
echo json_encode(['ok' => true, 'changes' => $changes]);

// ── Parse unified diff ────────────────────────────────────────────────────────
function parseDiff(string $diff): array {
    $result  = [];
    $newLine = 0;
    $inHunk  = false; // skip file headers (---, +++) before the first @@ line

    foreach (explode("\n", $diff) as $line) {
        if (substr($line, 0, 2) === '@@') {
            $inHunk = true;
            if (preg_match('/\+(\d+)(?:,(\d+))?/', $line, $m)) $newLine = (int)$m[1];
            continue;
        }
        if (!$inHunk) continue;
        if ($line === '' || $line[0] === '\\') continue;
        $ch = $line[0];

        if ($ch === '+') {
            $last = end($result);
            if ($last && $last['line'] === $newLine && $last['type'] === 'deleted') {
                // deletion immediately followed by addition at same position → modified
                $result[key($result)]['type'] = 'modified';
            } else {
                $result[] = ['type' => 'added', 'line' => $newLine];
            }
            $newLine++;
        } elseif ($ch === '-') {
            $last = end($result);
            if ($last && $last['line'] === $newLine && $last['type'] === 'added') {
                // addition immediately followed by deletion → modified
                $result[key($result)]['type'] = 'modified';
            } elseif (!$last || $last['line'] !== $newLine) {
                $result[] = ['type' => 'deleted', 'line' => $newLine];
            }
            // multiple consecutive deletions at the same position → keep one entry
        } elseif ($ch === ' ') {
            $newLine++;
        }
    }
    return $result;
}
