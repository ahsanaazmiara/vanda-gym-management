<?php
/**
 * ============================================================
 * SESSION INIT - VANDA GYM CLASSIC ROOM
 * ============================================================
 * File ini WAJIB dipanggil sebelum output HTML apa pun.
 *
 * Gunakan:
 * require_once __DIR__ . '/session_init.php';
 *
 * Jangan gunakan session_start() lagi pada file lain
 * jika sudah memanggil file ini.
 * ============================================================
 */

// Lama session: 1 hari
$umur_session = 86400;

// ============================================================
// DETEKSI HTTPS
// ============================================================

$isHttps = false;

// HTTPS langsung dari server
if (
    isset($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== '' &&
    strtolower($_SERVER['HTTPS']) !== 'off'
) {
    $isHttps = true;
}

// HTTPS melalui reverse proxy / hosting
if (
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
) {
    $isHttps = true;
}

// Port HTTPS
if (
    isset($_SERVER['SERVER_PORT']) &&
    $_SERVER['SERVER_PORT'] == 443
) {
    $isHttps = true;
}


// ============================================================
// KONFIGURASI SESSION PHP
// ============================================================

// Session disimpan selama 1 hari
ini_set('session.gc_maxlifetime', $umur_session);

// Hanya gunakan cookie untuk session
ini_set('session.use_only_cookies', '1');

// Mencegah penggunaan session ID yang tidak valid
ini_set('session.use_strict_mode', '1');

// Cookie session tidak dapat dibaca JavaScript
ini_set('session.cookie_httponly', '1');

// Konfigurasi garbage collector
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');


// ============================================================
// MULAI SESSION
// ============================================================

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => $umur_session,

        // Cookie berlaku untuk seluruh website
        'path' => '/',

        // Aktif jika website HTTPS
        'secure' => $isHttps,

        // Mencegah JavaScript membaca cookie
        'httponly' => true,

        // Aman untuk navigasi normal/tab baru
        'samesite' => 'Lax'
    ]);

    session_start();
}