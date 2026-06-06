<?php
use App\Controllers\AuthController;
use Slim\App;

return function (App $app) {
    $app->post('/api/login', [AuthController::class, 'login']);
    $app->post('/api/logout', [AuthController::class, 'logout']);
    $app->get('/api/validate', [AuthController::class, 'validate']);
};

