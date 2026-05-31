<?php
// Shared helpers for git API endpoints.

function _gitKeyDir(string $userSub): string {
    return __DIR__ . '/../ssh_keys/' . hash('sha256', $userSub);
}

function _gitKeyPath(string $userSub): string {
    return _gitKeyDir($userSub) . '/id_ed25519';
}

// Generate key if missing; returns public key content.
function ensureSshKey(string $userSub): string {
    $dir = _gitKeyDir($userSub);
    $key = _gitKeyPath($userSub);
    $pub = $key . '.pub';
    if (!file_exists($pub)) {
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        exec('ssh-keygen -t ed25519 -N "" -C ' . escapeshellarg('docs@fireants.dev') . ' -f ' . escapeshellarg($key) . ' 2>/dev/null');
        if (file_exists($key)) chmod($key, 0600);
    }
    return trim(file_get_contents($pub) ?: '');
}

// Returns the GIT_SSH_COMMAND env value for this user's key.
function gitSshCommand(string $userSub): string {
    $keyPath    = _gitKeyPath($userSub);
    $knownHosts = __DIR__ . '/../ssh_keys/known_hosts';
    // Paths are all under our controlled directory (hex hash) — no shell special chars.
    return "ssh -i $keyPath -o StrictHostKeyChecking=accept-new -o UserKnownHostsFile=$knownHosts -o BatchMode=yes";
}

// Run a git command in $cwd; returns [exitCode, stdout, stderr].
function runGit(array $args, string $cwd, array $extraEnv = []): array {
    $cmdParts = ['git'];
    foreach ($args as $a) $cmdParts[] = escapeshellarg($a);
    $cmd = implode(' ', $cmdParts);

    $env = array_merge([
        'HOME'                => '/tmp',
        'GIT_TERMINAL_PROMPT' => '0',
        'GIT_CONFIG_NOSYSTEM' => '1',
        'PATH'                => '/usr/bin:/bin:/usr/local/bin',
    ], $extraEnv);

    $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $proc = proc_open($cmd, $descriptors, $pipes, $cwd, $env);
    if (!is_resource($proc)) return [-1, '', 'Failed to start git process'];

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code   = proc_close($proc);
    return [$code, $stdout, $stderr];
}

// Verify document ownership; return data dir path or null.
function getDocDir(int $id, array $user): ?string {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) return null;
    $stmt = $db->prepare("SELECT id FROM typst_documents WHERE id=? AND owner=?");
    $stmt->bind_param("is", $id, $user['sub']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();
    if (!$row) return null;
    $dir = __DIR__ . '/../data/' . $id;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
}

// Get git user settings from DB, falling back to session user info.
function getGitAuthor(array $user): array {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $name  = $user['name'];
    $email = $user['email'] ?? '';
    if (!$db->connect_error) {
        $stmt = $db->prepare("SELECT commit_name, commit_email FROM git_user_settings WHERE user_sub=?");
        $stmt->bind_param("s", $user['sub']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->close();
        if ($row) {
            if ($row['commit_name'])  $name  = $row['commit_name'];
            if ($row['commit_email']) $email = $row['commit_email'];
        }
    }
    return ['name' => $name, 'email' => $email];
}
