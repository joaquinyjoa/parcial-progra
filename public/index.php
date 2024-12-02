<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Src\poo\UsuarioController;
use Src\poo\JugueteController;
use Src\poo\UsuarioTableMiddleware;
use Src\poo\MW;

$app = AppFactory::create();

// Definición de rutas
$app->get('/usuarios', [UsuarioController::class, 'getAll']);

$app->post('/usuarios', [UsuarioController::class, 'altaUsuario']);

$app->post('/juguetes', [JugueteController::class, 'add']);

$app->get('/juguetes', [JugueteController::class, 'getAll']);

$app->group('/toys', function($group) {
    // Ruta DELETE para borrar un juguete por su ID
    $group->delete('/{id_juguete}', [JugueteController::class, 'delete']);
    
    // Ruta POST para modificar un juguete por su ID
    $group->post('/modificar', [JugueteController::class, 'update']);
});

$app->post('/login', [UsuarioController::class, 'login'])
    ->add([MW::class, 'verificarExistenciaUsuario'])
    ->add([MW::class, 'verificarCamposVacios']);//es con add 

// Agregar esta ruta en tu archivo de rutas
$app->get('/login', [UsuarioController::class, 'verificarJWT']);

// Configuración del enrutador
$app->group('/tablas', function ($group) {
    // Ruta GET para mostrar la tabla de usuarios
    $group->get('/usuarios', UsuarioTableMiddleware::class . ':generateUserTable');
    $group->post('/usuarios', UsuarioTableMiddleware::class . ':generateUserTable');
    $group->get('/juguetes', [JugueteController::class, 'getAll'])
    ->add([MW::class, 'filtrarJuguetesIDsImpares']);
});

$app->run();
