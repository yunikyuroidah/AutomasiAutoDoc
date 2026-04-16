<?php
declare(strict_types=1);

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function read_json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);

    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        json_response([
            'error' => 'Invalid JSON payload',
        ], 400);
    }

    return is_array($decoded) ? $decoded : [];
}
