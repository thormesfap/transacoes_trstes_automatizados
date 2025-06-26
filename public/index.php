<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Carrega as rotas do arquivo separado
(require __DIR__ . '/../routes/web.php')($app);

if (php_sapi_name() !== 'cli') {
    $app->run();
} else {
    return $app;
}
