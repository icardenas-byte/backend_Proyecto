<?php
namespace App\Middleware;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $headers = $request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $headers[0] ?? '');
        $user = User::where('token', $token)
                    ->where('session_active', 1)
                    ->first();

        if (!$user) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'No autorizado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $request = $request->withAttribute('user', $user);
        return $handler->handle($request);
    }
}