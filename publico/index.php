<?php
require_once __DIR__ . '/../vendor/autoload.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

use Dotenv\Dotenv;



try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();

    $dotenv->required([
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'NOMINATIM_URL_BUSCAR',
        'OVERPASS_URL_OFICIAL',
        'OPEN_STREET_MAP_TILES',
        'PDF_DIR',
        'USER_AGENT'
    ])->notEmpty();


} catch (\Throwable $e) {
    error_log("Falha ao carregar ou validar .env: " . $e->getMessage());
    die("Erro crítico: configuração inválida. Contate o administrador.");
}


use PADS\App\rotas\Router;


require_once __DIR__ . '/../src/rotas/Rotas.php';

$rota = $_POST['rota'] ?? ($_GET['rota'] ?? 'login');
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($rota, $method);
