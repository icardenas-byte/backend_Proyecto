<?php
namespace App\Controllers;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = User::where('username', $data['username'])
                    ->where('password', $data['password'])
                    ->first();

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Credenciales invalidas'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->logged = 1;
        $user->session_active = 1;
        $user->save();
        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $token,
            'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function logout(Request $request, Response $response)
    {
        $headers = $request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $headers[0] ?? '');
        $user = User::where('token', $token)->first();
        if ($user) {
            $user->token = null;
            $user->logged = 0;
            $user->session_active = 0;
            $user->save();
        }
        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function validate(Request $request, Response $response)
    {
        $headers = $request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $headers[0] ?? '');
        $user = User::where('token', $token)
                    ->where('session_active', 1)
                    ->first();

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Sesion invalida'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
        $response->getBody()->write(json_encode([
            'success' => true,
            'user' => $user
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}