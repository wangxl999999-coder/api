<?php

namespace Core;

class JWT
{
    private static $config;

    private static function init()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/config.php';
        }
    }

    public static function encode($payload)
    {
        self::init();
        
        $header = [
            'typ' => 'JWT',
            'alg' => self::$config['jwt']['algorithm']
        ];

        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        $signature = self::sign($base64UrlHeader . '.' . $base64UrlPayload);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    public static function decode($token)
    {
        self::init();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = self::sign($base64UrlHeader . '.' . $base64UrlPayload);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function generateToken($userId, $type = 'access')
    {
        self::init();
        
        $expire = $type === 'refresh' 
            ? self::$config['jwt']['refresh_expire'] 
            : self::$config['jwt']['expire'];

        $payload = [
            'iss' => self::$config['app']['url'],
            'aud' => self::$config['app']['url'],
            'iat' => time(),
            'exp' => time() + $expire,
            'sub' => $userId,
            'type' => $type
        ];

        return self::encode($payload);
    }

    public static function generateTokens($userId)
    {
        return [
            'access_token' => self::generateToken($userId, 'access'),
            'refresh_token' => self::generateToken($userId, 'refresh'),
            'expires_in' => self::$config['jwt']['expire']
        ];
    }

    private static function sign($data)
    {
        self::init();
        
        $secret = self::$config['jwt']['secret'];
        $algorithm = self::$config['jwt']['algorithm'];

        switch ($algorithm) {
            case 'HS256':
                return hash_hmac('sha256', $data, $secret, true);
            case 'HS384':
                return hash_hmac('sha384', $data, $secret, true);
            case 'HS512':
                return hash_hmac('sha512', $data, $secret, true);
            default:
                throw new \Exception("不支持的算法: {$algorithm}");
        }
    }

    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        $padding = strlen($data) % 4;
        if ($padding !== 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
