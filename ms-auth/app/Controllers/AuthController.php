<?php
namespace App\Controllers;

use App\Helpers\JsonResponse;
use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $identificador = trim($body['usuario'] ?? $body['email'] ?? '');
        $password = (string) ($body['password'] ?? '');

        if ($identificador === '' || $password === '') {
            return JsonResponse::error($response, 'Usuario y password son obligatorios', 422);
        }

        $usuario = Usuario::where('usuario', $identificador)
            ->orWhere('email', $identificador)
            ->first();

        if (!$usuario || !$this->passwordValido($password, (string) $usuario->password)) {
            return JsonResponse::error($response, 'Credenciales incorrectas', 401);
        }

        $token = bin2hex(random_bytes(32));
        $usuario->update([
            'token' => $token,
            'session_active' => 1,
        ]);

        return JsonResponse::ok($response, [
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'usuario' => $usuario->usuario,
                'email' => $usuario->email,
            ],
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $usuario = $this->usuarioDesdeToken($request);

        if ($usuario) {
            $usuario->update([
                'token' => null,
                'session_active' => 0,
            ]);
        }

        return JsonResponse::ok($response, ['message' => 'Sesion cerrada']);
    }

    public function validate(Request $request, Response $response): Response
    {
        $usuario = $this->usuarioDesdeToken($request);

        if (!$usuario) {
            return JsonResponse::error($response, 'Sesion invalida', 401);
        }

        return JsonResponse::ok($response, [
            'valid' => true,
            'usuario_id' => $usuario->id,
            'usuario' => $usuario->usuario,
            'email' => $usuario->email,
        ]);
    }

    private function usuarioDesdeToken(Request $request): ?Usuario
    {
        $token = $this->tokenDesdeRequest($request);

        if ($token === '') {
            return null;
        }

        return Usuario::where('token', $token)
            ->where('session_active', 1)
            ->first();
    }

    private function tokenDesdeRequest(Request $request): string
    {
        $header = $request->getHeaderLine('Authorization');

        if (stripos($header, 'Bearer ') === 0) {
            return trim(substr($header, 7));
        }

        return trim($header);
    }

private function passwordValido(string $password, string $hash): bool
{
    if (empty($hash)) {
        return false;
    }
    if (strpos($hash, '$2y$') === 0 || strpos($hash, '$2a$') === 0 || strpos($hash, '$2b$') === 0) {
        return password_verify($password, $hash);
    }
    return $password === $hash;
}
}
