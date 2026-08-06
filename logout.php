<?php
require_once __DIR__ . '/session.php';

// Prevent browsers from caching protected pages so the back arrow
// cannot restore a logged-in view after logout.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');

// Regenerate the session id and wipe all data so any lingering
// session cannot be reused.
session_regenerate_id(true);
session_unset();
session_destroy();

// Start a fresh empty session so the cookie is cleanly invalidated.
session_start();
session_regenerate_id(true);

header('Location: login.html');
exit();
