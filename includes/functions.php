<?php
/**
 * Funções Utilitárias Globais
 */

/**
 * Escapa texto para exibição segura em HTML (XSS prevention)
 */
function escape(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Calcula a distância entre duas coordenadas em metros usando a Fórmula de Haversine
 */
function calcularDistanciaHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371000; // Raio da Terra em metros

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Retorna resposta em formato JSON para requisições de API
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

