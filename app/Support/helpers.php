<?php

declare(strict_types=1);

use App\Auth;
use App\Database;

function db(): PDO
{
    return Database::connection();
}

function app_name(): string
{
    return 'Flour and Fire';
}

function asset(string $path): string
{
    return url($path);
}

function view_path(string $path): string
{
    return VIEW_PATH . '/' . ltrim($path, '/');
}

function url(string $path): string
{
    if ($path === '') {
        return '/';
    }

    return '/' . ltrim($path, '/');
}

function route(string $name, array $params = []): string
{
    return match ($name) {
        'home' => url('/'),
        'menu' => url('menu'),
        'photos' => url('photos'),
        'contact' => url('contact'),
        'cart' => url('cart'),
        'login' => url('login'),
        'register' => url('register'),
        'logout' => url('logout'),
        'account' => url('account'),
        'admin' => url('admin'),
        'product' => url('product/' . rawurlencode((string) ($params['slug'] ?? ''))),
        default => url($name),
    };
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function back(string $fallback = '/'): never
{
    redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = compact('type', 'message');
}

function consume_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function set_old_input(array $input): void
{
    $_SESSION['old_input'] = $input;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old_input'][$key] ?? $default);
}

function clear_old_input(): void
{
    unset($_SESSION['old_input']);
}

function current_user(): ?array
{
    return Auth::user();
}

function is_logged_in(): bool
{
    return Auth::check();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

function require_auth(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Сначала войдите в аккаунт.');
        redirect(route('login'));
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('danger', 'Доступ только для администратора.');
        redirect(route('home'));
    }
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['_csrf'] ?? '');

    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function format_price(float|int|string $amount): string
{
    return number_format((float) $amount, 0, '.', ' ') . ' ₽';
}

function guest_token(): string
{
    if (!isset($_SESSION['guest_token'])) {
        $_SESSION['guest_token'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['guest_token'];
}

function page_title(string $title): string
{
    return $title . ' | ' . app_name();
}

function save_uploaded_image(array $file, string $targetDir = 'uploads/products'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Не удалось загрузить изображение.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('Файл загрузки не найден.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string) $finfo->file($tmpName);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($extensions[$mimeType])) {
        throw new RuntimeException('Допустимы только изображения JPG, PNG, WEBP или GIF.');
    }

    $relativeDirectory = trim($targetDir, '/');
    $absoluteDirectory = PUBLIC_PATH . '/' . $relativeDirectory;

    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('Не удалось создать папку для загрузок.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    $relativePath = $relativeDirectory . '/' . $filename;
    $absolutePath = PUBLIC_PATH . '/' . $relativePath;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        throw new RuntimeException('Не удалось сохранить изображение.');
    }

    return $relativePath;
}
