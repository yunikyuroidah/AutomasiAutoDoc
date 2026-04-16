<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config.php';

const ATTEMPT_FILE = __DIR__ . '/storage/login_attempts.json';
const ATTEMPT_LIMIT = 5;
const LOCK_DURATION = 3 * 24 * 60 * 60; // 3 days in seconds

function loadAttemptLedger(): array
{
    if (!file_exists(ATTEMPT_FILE)) {
        return [];
    }

    $raw = file_get_contents(ATTEMPT_FILE);
    $decoded = json_decode($raw ?: '[]', true);

    return is_array($decoded) ? $decoded : [];
}

function persistAttemptLedger(array $ledger): void
{
    $dir = dirname(ATTEMPT_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    file_put_contents(
        ATTEMPT_FILE,
        json_encode($ledger, JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function getDeviceKey(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    return hash('sha256', $ip . '|' . $agent);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../frontend/login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!isset($_POST['remember'])) {
    header('Location: ../frontend/login.html?error=confirm');
    exit;
}

if ($username === '' || $password === '') {
    header('Location: ../frontend/login.html?error=invalid');
    exit;
}

$attemptLedger = loadAttemptLedger();
$deviceKey = getDeviceKey();
$now = time();

if (isset($attemptLedger[$deviceKey])) {
    $blockedUntil = (int) ($attemptLedger[$deviceKey]['blocked_until'] ?? 0);
    if ($blockedUntil > $now) {
        header('Location: ../frontend/login.html?error=blocked');
        exit;
    }

    if ($blockedUntil !== 0 && $blockedUntil <= $now) {
        unset($attemptLedger[$deviceKey]);
        persistAttemptLedger($attemptLedger);
    }
}

try {
    $db = get_db_connection();

    $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc();
    $stmt->close();
    $db->close();

    $incomingHash = hash('sha256', $password);

    if ($user && hash_equals($user['password'], $incomingHash)) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];

        if (isset($attemptLedger[$deviceKey])) {
            unset($attemptLedger[$deviceKey]);
            persistAttemptLedger($attemptLedger);
        }

        header('Location: ../frontend/workspace.php');
        exit;
    }

    $entry = $attemptLedger[$deviceKey] ?? ['count' => 0, 'blocked_until' => 0];
    $entry['count'] = (int) ($entry['count'] ?? 0) + 1;
    $entry['last_attempt'] = $now;

    if ($entry['count'] >= ATTEMPT_LIMIT) {
        $entry['blocked_until'] = $now + LOCK_DURATION;
    }

    $attemptLedger[$deviceKey] = $entry;
    persistAttemptLedger($attemptLedger);

    $redirectParam = isset($entry['blocked_until']) && $entry['blocked_until'] > $now ? 'blocked' : 'invalid';
    header('Location: ../frontend/login.html?error=' . $redirectParam);
    exit;
} catch (Throwable $th) {
    error_log('[LOGIN] ' . $th->getMessage());
    header('Location: ../frontend/login.html?error=server');
    exit;
}
