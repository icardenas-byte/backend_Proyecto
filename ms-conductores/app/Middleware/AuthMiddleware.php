<?php
namespace App\Middleware;

use App\Helpers\JsonResponse;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->tokenDesdeRequest($request);

        if ($token === '') {
            return JsonResponse::error(new Response(), 'Token requerido', 401);
        }

        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=logistica_auth;charset=utf8', 'root', '');
            $stmt = $pdo->prepare('SELECT id, usuario, correo, rol FROM usuarios WHERE token = ? AND sesion_activa = 1 LIMIT 1');
            $stmt->execute([$token]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return JsonResponse::error(new Response(), 'No se pudo validar la sesion', 500);
        }

        if (!$usuario) {
            return JsonResponse::error(new Response(), 'Sesion invalida', 401);
        }

        return $handler->handle($request->withAttribute('usuario', $usuario));
    }

    private function tokenDesdeRequest(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine('Authorization');

        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }

        return trim($header);
    }
}
