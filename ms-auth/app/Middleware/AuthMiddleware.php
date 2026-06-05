<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Helpers\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $identificador = trim($body['usuario'] ?? $body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($identificador === '' || $password === '') {
            return JsonResponse::error($response, 'Usuario y contraseña son obligatorios', 422);
        }

        $user = Usuario::where('usuario', $identificador)
            ->orWhere('email', $identificador)
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
            return JsonResponse::error($response, 'Credenciales incorrectas', 401);
        }

        $token = bin2hex(random_bytes(32));
        $user->update([
            'token' => $token,
            'session_active' => 1,
        ]);

        return JsonResponse::ok($response, [
            'token' => $token,
            'usuario' => [
                'id' => $user->id,
                'usuario' => $user->usuario,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $user = $this->usuarioDesdeToken($request);
        if ($user) {
            $user->update(['token' => null, 'session_active' => 0]);
        }
        return JsonResponse::ok($response, ['message' => 'Sesión cerrada']);
    }

    public function validate(Request $request, Response $response): Response
    {
        $user = $this->usuarioDesdeToken($request);
        if (!$user) {
            return JsonResponse::error($response, 'Sesión inválida', 401);
        }
        return JsonResponse::ok($response, ['valid' => true, 'usuario_id' => $user->id]);
    }

    private function usuarioDesdeToken(Request $request): ?Usuario
    {
        $header = $request->getHeaderLine('Authorization');
        $token = trim(str_ireplace('Bearer', '', $header));
        if ($token === '') return null;

        return Usuario::where('token', $token)->where('session_active', 1)->first();
    }
}