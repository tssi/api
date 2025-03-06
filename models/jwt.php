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
            "exp" => time() + 500 // Token expires in 5 mins add to master config
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
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Recalculate signature
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
        $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return hash_equals($validSignature, $base64Signature);
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
