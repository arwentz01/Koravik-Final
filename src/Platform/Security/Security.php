<?php

declare(strict_types=1);

namespace Koravik\Platform\Security;

use PDO;

final class Security
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) (\env('SESSION_NAME', 'koravik_session') ?? 'koravik_session'));
        session_set_cookie_params([
            'httponly' => true,
            'secure' => filter_var(\env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOL),
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::startSession();
        return is_string($token) && isset($_SESSION['csrf']) && hash_equals((string) $_SESSION['csrf'], $token);
    }

    public static function attempt(PDO $pdo, string $email, string $password): bool
    {
        $statement = $pdo->prepare(
            'SELECT a.id, a.display_name, a.role, c.password_hash
             FROM platform_accounts a
             JOIN auth_credentials c ON c.account_id = a.id
             WHERE a.email = :email AND a.status = "active"
             LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $account = $statement->fetch();

        if (!$account || !password_verify($password, (string) $account['password_hash'])) {
            return false;
        }

        self::startSession();
        session_regenerate_id(true);
        $_SESSION['account'] = [
            'id' => (string) $account['id'],
            'display_name' => (string) $account['display_name'],
            'role' => (string) $account['role'],
        ];
        return true;
    }

    public static function account(): ?array
    {
        self::startSession();
        return isset($_SESSION['account']) && is_array($_SESSION['account']) ? $_SESSION['account'] : null;
    }

    public static function requireAccount(): array
    {
        $account = self::account();
        if ($account === null) {
            header('Location: /login', true, 302);
            exit;
        }
        return $account;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
