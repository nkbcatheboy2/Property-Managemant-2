<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'property_management';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function setting(string $key, string $default = ''): string
{
    try {
        $stmt = db()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        return (string)($stmt->fetchColumn() ?: $default);
    } catch (Throwable $e) {
        return $default;
    }
}

function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function public_phone(): string { $phone = setting('portal_contact_phone', ''); return ($phone === '' || str_contains(strtoupper($phone), 'X')) ? '180018005001' : $phone; }
function public_email(): string { return setting('portal_contact_email', 'support@lda-portal.gov.in'); }
function redirect(string $path): never { header('Location: ' . $path); exit; }
