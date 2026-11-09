<?php
use App\Controllers\Api\{AuthController, TicketApiController, CategoryApiController, StaffApiController};
use App\Controllers\HomeController;
$router->get('/', [HomeController::class,'index']);
$router->post('/api/auth/login', [AuthController::class,'login']);
$router->get('/api/auth/me', [AuthController::class,'me']);
$router->post('/api/auth/logout', [AuthController::class,'logout']);
foreach (['tickets'=>TicketApiController::class,'categories'=>CategoryApiController::class,'staff'=>StaffApiController::class] as $resource=>$controller) {
    $router->get('/api/'.$resource,[$controller,'index']);
    $router->post('/api/'.$resource,[$controller,'store']);
    $router->get('/api/'.$resource.'/{id}',[$controller,'show']);
    $router->put('/api/'.$resource.'/{id}',[$controller,'update']);
    $router->patch('/api/'.$resource.'/{id}',[$controller,'update']);
    $router->delete('/api/'.$resource.'/{id}',[$controller,'destroy']);
}
$router->get('/api/categories/{id}/tickets',[CategoryApiController::class,'tickets']);
