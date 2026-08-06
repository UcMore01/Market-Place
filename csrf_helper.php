<?php
require_once __DIR__ . '/session.php';

/**
 * Validate CSRF token from JSON body or form POST.
 * Call this at the top of any state-changing POST endpoint.
 *
 * @param string|null $token Optional token, e.g. when php://input was already consumed.
 */
function require_csrf_token($token = null) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if ($token === null) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $token = $data['csrf_token'] ?? null;
            }
        } else {
            $token = $_POST['csrf_token'] ?? null;
        }
    }

    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (!$token || !validate_csrf_token($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing CSRF token.']);
        exit;
    }
}
