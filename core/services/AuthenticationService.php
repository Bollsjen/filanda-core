<?php

namespace Core\services;

use Core\models\AuthUser;

class AuthenticationService {
    private const SESSION_USER_KEY = 'auth_user_id';
    private const SESSION_USER_DATA = 'auth_user_data';
    private const REMEMBER_TOKEN_COOKIE = 'remember_token';
    private const SESSION_LIFETIME = 3600; // 1 hour
    
    public static function startSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => true, // Set to false in development if not using HTTPS
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
                'use_only_cookies' => true,
                'cookie_lifetime' => 0, // Until browser closes
            ]);
        }
        
        // Check session expiration
        if (isset($_SESSION['LAST_ACTIVITY']) && 
            (time() - $_SESSION['LAST_ACTIVITY'] > self::SESSION_LIFETIME)) {
            self::logout();
        }
        
        $_SESSION['LAST_ACTIVITY'] = time();
    }
    
    public static function login(AuthUser $user, bool $rememberMe = false): void {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Store user info in session
        $_SESSION[self::SESSION_USER_KEY] = $user->id;
        $_SESSION[self::SESSION_USER_DATA] = [
            'id' => $user->id,
            'email' => $user->email,
            'roles' => $user->roles,
        ];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Handle "Remember Me"
        if ($rememberMe) {
            self::createRememberToken($user->id);
        }
    }
    
    public static function isAuthenticated(): bool {
        self::startSecureSession();
        
        // Check session
        if (isset($_SESSION[self::SESSION_USER_KEY])) {
            return true;
        }
        
        // Check remember me token
        if (isset($_COOKIE[self::REMEMBER_TOKEN_COOKIE])) {
            return self::validateRememberToken();
        }
        
        return false;
    }
    
    public static function getCurrentUser(): ?AuthUser {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        $userData = $_SESSION[self::SESSION_USER_DATA] ?? null;
        
        if (!$userData) {
            return null;
        }
        
        // Reconstruct AuthUser from session data
        $user = new AuthUser();
        $user->id = $userData['id'];
        $user->email = $userData['email'];
        $user->roles = $userData['roles'];
        
        return $user;
    }
    
    public static function hasRole(string $role): bool {
        $user = self::getCurrentUser();
        
        if (!$user || !isset($user->roles)) {
            return false;
        }
        
        return in_array($role, $user->roles);
    }
    
    public static function logout(): void {
        self::startSecureSession();
        
        // Remove remember me token
        self::deleteRememberToken();
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    private static function createRememberToken(int $userId): void {
        // Generate secure random token
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        
        // Store hashed token in database with user ID and expiration
        // You'll need to create a remember_tokens table:
        // id, user_id, token_hash, expires_at
        $expiration = time() + (30 * 24 * 60 * 60); // 30 days
        
        // TODO: Store in database
        // $db->insert('remember_tokens', [
        //     'user_id' => $userId,
        //     'token_hash' => $hashedToken,
        //     'expires_at' => $expiration
        // ]);
        
        // Set cookie with plain token (NOT the hash)
        setcookie(
            self::REMEMBER_TOKEN_COOKIE,
            $token,
            $expiration,
            '/',
            '',
            true, // Secure - HTTPS only
            true  // HttpOnly
        );
    }
    
    private static function validateRememberToken(): bool {
        $token = $_COOKIE[self::REMEMBER_TOKEN_COOKIE] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $hashedToken = hash('sha256', $token);
        
        // TODO: Query database for token
        // $tokenData = $db->query(
        //     'SELECT user_id, expires_at FROM remember_tokens 
        //      WHERE token_hash = ? AND expires_at > ?',
        //     [$hashedToken, time()]
        // );
        
        // For now, returning false - implement database lookup
        // if ($tokenData) {
        //     // Load user and create session
        //     $user = // Load user from database using $tokenData['user_id']
        //     self::login($user, false);
        //     return true;
        // }
        
        return false;
    }
    
    private static function deleteRememberToken(): void {
        if (isset($_COOKIE[self::REMEMBER_TOKEN_COOKIE])) {
            $token = $_COOKIE[self::REMEMBER_TOKEN_COOKIE];
            $hashedToken = hash('sha256', $token);
            
            // TODO: Delete from database
            // $db->delete('remember_tokens', ['token_hash' => $hashedToken]);
            
            // Delete cookie
            setcookie(self::REMEMBER_TOKEN_COOKIE, '', time() - 3600, '/', '', true, true);
        }
    }
}