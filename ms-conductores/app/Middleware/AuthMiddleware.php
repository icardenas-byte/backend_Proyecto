<?php
namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as Capsule;
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
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'database'  => 'logistica_auth',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $user = \Illuminate\Database\Capsule\Manager::table('users')
                    ->where('token', $token)
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
        return $handler->handle($request);
    }
}