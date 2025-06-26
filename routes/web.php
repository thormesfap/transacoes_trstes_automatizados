<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\TransacaoRepository;

require_once __DIR__ . '/../src/helpers.php';

return function ($app) {
    $app->post('/transacao', function (Request $request, Response $response) {
        $repo = new TransacaoRepository();
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $response->withStatus(400);
        }
        if (!isset($data['id'], $data['valor'], $data['dataHora'])) {
            return $response->withStatus(422);
        }
        $id = $data['id'];
        $valor = $data['valor'];
        $dataHora = $data['dataHora'];
        if (!isValidUuidV4($id) || !is_numeric($valor) || $valor < 0) {
            return $response->withStatus(422);
        }
        $dt = DateTime::createFromFormat(DateTime::ATOM, $dataHora);
        if (!$dt || $dt > new DateTime()) {
            return $response->withStatus(422);
        }
        if ($repo->buscarPorId($id)) {
            return $response->withStatus(422);
        }
        $repo->inserir($id, $valor, $dataHora);
        return $response->withStatus(201);
    });

    $app->get('/transacao/{id}', function (Request $request, Response $response, $args) {
        $repo = new TransacaoRepository();
        $transacao = $repo->buscarPorId($args['id']);
        if (!$transacao) {
            return $response->withStatus(404);
        }
        $response->getBody()->write(json_encode($transacao));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->delete('/transacao', function (Request $request, Response $response) {
        $repo = new TransacaoRepository();
        $repo->deletarTodas();
        return $response->withStatus(200);
    });

    $app->delete('/transacao/{id}', function (Request $request, Response $response, $args) {
        $repo = new TransacaoRepository();
        if (!$repo->buscarPorId($args['id'])) {
            return $response->withStatus(404);
        }
        $repo->deletarPorId($args['id']);
        return $response->withStatus(200);
    });

    $app->get('/estatistica', function (Request $request, Response $response) {
        $repo = new TransacaoRepository();
        $estat = $repo->estatisticas(60);
        $response->getBody()->write(json_encode($estat));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
