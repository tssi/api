<?php
class Jwt extends ApiAppModel {
    var $useTable = false; // No database table needed

    function generateJWT($studentId, $secretKey) {
        // Header
        $header = json_encode(["alg" => "HS256", "typ" => "JWT"]);
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        // Payload
        $payload = json_encode([
            "student_id" => $studentId,
            "exp" => time() + 300 // Token expires in 5 mins add to master config
        ]);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        // Signature
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    function verifyJWT($token, $secretKey) {
        $parts = explode(".", $token);
        if (count($parts) !== 3) {
            return false; // Invalid token structure
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        // Decode Base64 (URL-safe variant)
        $header = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $base64Header)), true);
        $payload = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $base64Payload)), true);
        $signature = base64_decode(str_replace(array('-', '_'), array('+', '/'), $base64Signature));

        if (!$header || !$payload) {
            return false; // Invalid JSON structure
        }

        // Recalculate signature
        $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);

        // Verify signature (no `hash_equals()` in PHP 5)
        if ($signature !== $expectedSignature) {
            return false;
        }

        // Check expiration time (if present)
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }

        return $payload; // Return decoded payload if valid
    }


    function decodeJWT($token) {
        $parts = explode(".", $token);
        if (count($parts) !== 3) {
            return false;
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }

        return $payload;
    }
}
?>
