<?php
class Auth {
    private const SESSION_KEY = 'kutpod_auth';

    public function login(string $user, string $password, KutPodAPI $api): bool {
        try {
            $token = $api->authenticate($user, $password);
            if (!empty($token)) {
                // Regenerar ID de sesión para evitar session fixation
                session_regenerate_id(true);
                $_SESSION[self::SESSION_KEY] = true;
                $_SESSION['api_token'] = $token;
                $_SESSION['login_time'] = time();
                return true;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
        }
        return false;
    }

    public function getToken(): ?string {
        return $_SESSION['api_token'] ?? null;
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public function isLoggedIn(): bool {
        if (empty($_SESSION[self::SESSION_KEY])) return false;
        // Session expires after 8 hours
        if (time() - ($_SESSION['login_time'] ?? 0) > 28800) {
            $this->logout();
            return false;
        }
        return true;
    }
}
