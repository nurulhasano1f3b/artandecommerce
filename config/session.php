<?php
/**
 * Session bootstrap — require this instead of calling session_start() directly.
 * Sets secure cookie flags before starting the session. (M-02)
 * The 'secure' flag is only enabled when the request is over HTTPS,
 * so local HTTP development still works.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
