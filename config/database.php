<?php

date_default_timezone_set('Asia/Jakarta');

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=pacefire_db;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('PaceFire database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database tidak dapat digunakan. Jalankan database/schema.sql terlebih dahulu.');
}
