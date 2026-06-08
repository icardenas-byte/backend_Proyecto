<?php
namespace App\Middleware;

use App\Helpers\JsonResponse;
use App\Models\Usuario;
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

        $usuario = Usuario::where('token', $token)
            ->where('sesion_activa', 1)
            ->first();

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
