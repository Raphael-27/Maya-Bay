<?php

require_once __DIR__ . '/firebase_config.php';

class FirebaseAuth {

    // ── Petición cURL interna ──────────────────────────────────
    private static function post(string $endpoint, array $body): array {
        $url = FIREBASE_AUTH_URL . $endpoint . '?key=' . FIREBASE_API_KEY;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $response ?? [];
    }

    // ──────────────────────────────────────────────────────────
    // REGISTER — Crea usuario con email y contraseña
    // ──────────────────────────────────────────────────────────
    public static function register(string $email, string $password,
                                    string $displayName = ''): array
    {
        $res = self::post(':signUp', [
            'email'             => $email,
            'password'          => $password,
            'displayName'       => $displayName,
            'returnSecureToken' => true,
        ]);

        if (isset($res['error'])) {
            $msg = self::translateError($res['error']['message']);
            return ['success' => false, 'error' => $msg];
        }

        return [
            'success'      => true,
            'uid'          => $res['localId'],
            'email'        => $res['email'],
            'displayName'  => $res['displayName'] ?? '',
            'idToken'      => $res['idToken'],
            'refreshToken' => $res['refreshToken'],
        ];
    }

    // ──────────────────────────────────────────────────────────
    // LOGIN — Inicia sesión con email y contraseña
    // ──────────────────────────────────────────────────────────
    public static function login(string $email, string $password): array {
        $res = self::post(':signInWithPassword', [
            'email'             => $email,
            'password'          => $password,
            'returnSecureToken' => true,
        ]);

        if (isset($res['error'])) {
            return ['success' => false, 'error' => self::translateError($res['error']['message'])];
        }

        return [
            'success'      => true,
            'uid'          => $res['localId'],
            'email'        => $res['email'],
            'displayName'  => $res['displayName'] ?? explode('@', $email)[0],
            'idToken'      => $res['idToken'],
            'refreshToken' => $res['refreshToken'],
        ];
    }

    // ──────────────────────────────────────────────────────────
    // REFRESH TOKEN — Renueva el idToken (expira cada 1 hora)
    // ──────────────────────────────────────────────────────────
    public static function refreshToken(string $refreshToken): array {
        $url = 'https://securetoken.googleapis.com/v1/token?key=' . FIREBASE_API_KEY;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($res['error'])) {
            return ['success' => false, 'error' => $res['error']['message']];
        }
        return ['success' => true, 'idToken' => $res['id_token'], 'refreshToken' => $res['refresh_token']];
    }

    // ──────────────────────────────────────────────────────────
    // PASSWORD RESET — Envía correo de restablecimiento
    // ──────────────────────────────────────────────────────────
    public static function sendPasswordReset(string $email): array {
        $res = self::post(':sendOobCode', [
            'requestType' => 'PASSWORD_RESET',
            'email'       => $email,
        ]);
        return isset($res['error'])
            ? ['success' => false, 'error' => self::translateError($res['error']['message'])]
            : ['success' => true];
    }

    // ──────────────────────────────────────────────────────────
    // SESSION — Guarda / obtiene / destruye la sesión PHP
    // ──────────────────────────────────────────────────────────
    public static function saveSession(array $userData): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['firebase_user'] = [
            'uid'          => $userData['uid'],
            'email'        => $userData['email'],
            'displayName'  => $userData['displayName'],
            'idToken'      => $userData['idToken'],
            'refreshToken' => $userData['refreshToken'],
            'logged_at'    => time(),
        ];
    }

    public static function getSession(): ?array {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['firebase_user'] ?? null;
    }

    public static function requireLogin(string $redirect = 'index.php'): array {
        $user = self::getSession();
        if (!$user) {
            header('Location: ' . $redirect);
            exit;
        }
        // Si el token tiene más de 50 min, renovarlo
        if (time() - $user['logged_at'] > 3000) {
            $refreshed = self::refreshToken($user['refreshToken']);
            if ($refreshed['success']) {
                $user['idToken']  = $refreshed['idToken'];
                $user['logged_at'] = time();
                $_SESSION['firebase_user'] = $user;
            }
        }
        return $user;
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header('Location: index.php');
        exit;
    }

    // ── Traduce errores de Firebase al español ─────────────────
    private static function translateError(string $code): string {
        return match(true) {
            str_contains($code, 'EMAIL_NOT_FOUND')       => 'No existe una cuenta con este correo.',
            str_contains($code, 'INVALID_PASSWORD')      => 'Contraseña incorrecta.',
            str_contains($code, 'INVALID_EMAIL')         => 'El formato del correo no es válido.',
            str_contains($code, 'EMAIL_EXISTS')          => 'Este correo ya tiene una cuenta registrada.',
            str_contains($code, 'WEAK_PASSWORD')         => 'La contraseña debe tener al menos 6 caracteres.',
            str_contains($code, 'TOO_MANY_ATTEMPTS')     => 'Demasiados intentos. Espera unos minutos.',
            str_contains($code, 'USER_DISABLED')         => 'Esta cuenta ha sido deshabilitada.',
            str_contains($code, 'INVALID_LOGIN_CREDENTIALS') => 'Correo o contraseña incorrectos.',
            default => 'Error de autenticación: ' . $code,
        };
    }
}