<?php
namespace App\Controllers;

use App\Models\Conductor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConductorController
{
    // Listar todos
    public function index(Request $request, Response $response)
    {
        $conductores = Conductor::all();
        $response->getBody()->write(json_encode($conductores));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Crear
    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        // Validaciones
        if (Conductor::where('documento', $data['documento'])->exists()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Documento duplicado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (Conductor::where('licencia_numero', $data['licencia_numero'])->exists()) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Licencia duplicada'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $conductor = Conductor::create($data);
        $response->getBody()->write(json_encode([
            'success' => true,
            'conductor' => $conductor
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    // Buscar por ID
    public function show(Request $request, Response $response, $args)
    {
        $conductor = Conductor::find($args['id']);
        if (!$conductor) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Conductor no encontrado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($conductor));
        return $response->withHeader('Content-Type', 'application/json');
    }
    // Actualizar
    public function update(Request $request, Response $response, $args)
    {
        $conductor = Conductor::find($args['id']);
        if (!$conductor) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Conductor no encontrado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $data = $request->getParsedBody();
        $conductor->update($data);
        $response->getBody()->write(json_encode([
            'success' => true,
            'conductor' => $conductor
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Cambiar estado
    public function cambiarEstado(Request $request, Response $response, $args)
    {
        $conductor = Conductor::find($args['id']);
        if (!$conductor) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Conductor no encontrado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = $request->getParsedBody();
        $conductor->estado = $data['estado'];
        $conductor->save();
        $response->getBody()->write(json_encode([
            'success' => true,
            'conductor' => $conductor
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Buscar por documento
    public function buscarDocumento(Request $request, Response $response, $args)
    {
        $conductor = Conductor::where('documento', $args['documento'])->first();
        if (!$conductor) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Conductor no encontrado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode($conductor));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Buscar por licencia
    public function buscarLicencia(Request $request, Response $response, $args)
    {
        $conductor = Conductor::where('licencia_numero', $args['licencia'])->first();
        if (!$conductor) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Conductor no encontrado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $response->getBody()->write(json_encode($conductor));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // Buscar por estado
    public function buscarEstado(Request $request, Response $response, $args)
    {
        $conductores = Conductor::where('estado', $args['estado'])->get();
        $response->getBody()->write(json_encode($conductores));
        return $response->withHeader('Content-Type', 'application/json');
    }
}