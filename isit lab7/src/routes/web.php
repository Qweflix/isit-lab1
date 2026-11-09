<?php
use App\Controllers\HomeController;
use App\Controllers\TicketController;
use App\Controllers\CategoryController;
$router->get('/', [HomeController::class, 'index']);
$router->get('/tickets', [TicketController::class, 'index']);
$router->get('/tickets/create', [TicketController::class, 'create']);
$router->post('/tickets', [TicketController::class, 'store']);
$router->get('/tickets/{id}', [TicketController::class, 'show']);
$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/categories/{id}', [CategoryController::class, 'show']);

// REST API: у коллекции GET/POST, у объекта GET/PUT/PATCH/DELETE.

use App\Controllers\Api\TicketApiController;
$router->get('/api/tickets', [TicketApiController::class, 'index']);
$router->post('/api/tickets', [TicketApiController::class, 'store']);
$router->get('/api/tickets/{id}', [TicketApiController::class, 'show']);
$router->put('/api/tickets/{id}', [TicketApiController::class, 'update']);
$router->patch('/api/tickets/{id}', [TicketApiController::class, 'update']);
$router->delete('/api/tickets/{id}', [TicketApiController::class, 'destroy']);

use App\Controllers\Api\CategoryApiController;
$router->get('/api/categories', [CategoryApiController::class, 'index']);
$router->post('/api/categories', [CategoryApiController::class, 'store']);
$router->get('/api/categories/{id}', [CategoryApiController::class, 'show']);
$router->put('/api/categories/{id}', [CategoryApiController::class, 'update']);
$router->patch('/api/categories/{id}', [CategoryApiController::class, 'update']);
$router->delete('/api/categories/{id}', [CategoryApiController::class, 'destroy']);
$router->get('/api/categories/{id}/tickets', [CategoryApiController::class, 'tickets']);

use App\Controllers\Api\StaffApiController;
$router->get('/api/staff', [StaffApiController::class, 'index']);
$router->post('/api/staff', [StaffApiController::class, 'store']);
$router->get('/api/staff/{id}', [StaffApiController::class, 'show']);
$router->put('/api/staff/{id}', [StaffApiController::class, 'update']);
$router->patch('/api/staff/{id}', [StaffApiController::class, 'update']);
$router->delete('/api/staff/{id}', [StaffApiController::class, 'destroy']);
