<?php
// session_init.php — require file ini di baris PALING ATAS
// login.php, dashboard.php, dan halaman admin lain, SEBELUM session_start() manapun.

$umur_session = 86400; // 1 hari

ini_set('session.gc_maxlifetime', $umur_session);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

session_set_cookie_params([
    'lifetime' => $umur_session,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}