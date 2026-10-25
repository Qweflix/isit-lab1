<?php
use App\Controllers\HomeController;
use App\Controllers\TicketController;
use App\Controllers\CategoryController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/tickets', [TicketController::class, 'index']);
// Конкретный адрес обязательно выше параметризованного.
$router->get('/tickets/create', [TicketController::class, 'create']);
$router->post('/tickets', [TicketController::class, 'store']);
$router->get('/tickets/{id}', [TicketController::class, 'show']);
$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/categories/{id}', [CategoryController::class, 'show']);
