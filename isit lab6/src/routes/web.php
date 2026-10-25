<?php
use App\Controllers\HomeController;
use App\Controllers\TaskController;
use App\Controllers\ProjectController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/tasks', [TaskController::class, 'index']);
// Конкретный адрес обязательно выше параметризованного.
$router->get('/tasks/create', [TaskController::class, 'create']);
$router->post('/tasks', [TaskController::class, 'store']);
$router->get('/tasks/{id}', [TaskController::class, 'show']);
$router->get('/projects', [ProjectController::class, 'index']);
$router->get('/projects/{id}', [ProjectController::class, 'show']);
