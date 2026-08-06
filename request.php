<?php
// helper to normalize request payloads (JSON or form-encoded)
function get_request_data() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (is_array($data)) return $data;
    if (!empty($_POST)) return $_POST;
    // parse plain url-encoded body if present
    $parsed = [];
    parse_str($input, $parsed);
    if (!empty($parsed)) return $parsed;
    return [];
}
