<?php
namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;

class JsonResponse
{
    public static function ok(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    public static function error(Response $response, string $message, int $status = 400): Response
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE));
        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}