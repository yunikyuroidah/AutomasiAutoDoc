<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_auth(bool $asJson = false): void
{
    if (!isset($_SESSION['user_id'])) {
        if ($asJson) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthenticated']);
            exit;
        }

        header('Location: ../frontend/login.html?error=invalid');
        exit;
    }
}

function current_user_id(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

function current_username(): string
{
    return (string) ($_SESSION['username'] ?? 'operator');
}
