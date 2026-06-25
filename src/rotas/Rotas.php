<?php
namespace PADS\App\rotas;

use PADS\App\controladores\UsuarioControle;
use PADS\App\modelos\UsuarioDAO;
use PADS\App\controladores\NominatimControle;
use PADS\App\controladores\HistoricoControle;
use PADS\App\modelos\PesquisaDAO;
use PADS\App\controladores\OverpassControleAlvo;
use PADS\App\controladores\OverpassControleEntorno;
use PADS\App\controladores\MapaControle;
use PADS\App\controladores\RoteiroControle;


class Router {
    private array $routesGet = [];
    private array $routesPost = [];

    public function addGet(string $key, callable $action): void {
        $this->routesGet[$key] = $action;
    }

    public function addPost(string $key, callable $action): void {
        $this->routesPost[$key] = $action;
    }

    public function dispatch(string $rota, string $method): void {

        if ($method === 'POST') {
            if (isset($this->routesPost[$rota])) {
                call_user_func($this->routesPost[$rota]);
            } elseif (isset($this->routesGet[$rota])) {
                $this->handleError(405, "Método incorreto. Esta rota aceita apenas GET.");
            } else {
                $this->handleError(404, "Rota não encontrada.");
            }
        } elseif ($method === 'GET') {
            if (isset($this->routesGet[$rota])) {
                call_user_func($this->routesGet[$rota]);
            } elseif (isset($this->routesPost[$rota])) {
                $this->handleError(405, "Método incorreto. Esta rota aceita apenas POST.");
            } else {
                $this->handleError(404, "Rota não encontrada.");
            }
        }
    }

    private function handleError(int $code, string $message): void {
    http_response_code($code);

    if (isset($_SESSION['flash_erro'])) {
        $_SESSION['flash_erro'] .= " | " . $message;
    } else {
        $_SESSION['flash_erro'] = $message;
    }

    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) && !empty($_SESSION['usuario'])) {
        $_SESSION['flash_erro'] .= " | Você já está logado!";
        header("Location: index.php?rota=telaPrincipal");
    } else {
        header("Location: index.php?rota=login");
    }
    exit;
}

}


$router = new Router();

$router->addGet('login', function () {
    include __DIR__ . '/../../visoes/login.php';
});

$router->addGet('cadastro', function () {
    include __DIR__ . '/../../visoes/cadastro.php';
});

$router->addGet('telaPrincipal', function () {
    include __DIR__ . '/../../visoes/telaPrincipal.php';
});

$router->addGet('edicaoUsuario', function () {
    header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
    exit;
});

$router->addGet('atualizacaoSenha', function () {
    include __DIR__ . '/../../visoes/atualizacaoSenha.php';
});

$router->addGet('telaMapa', function () {
    include __DIR__ . '/../../visoes/telaMapa.php';
});

$router->addGet('deslogar', function () {
    include __DIR__ . '/../servicos/Deslogar.php';
    exit;
});


$router->addGet('historicoUsuario', function () {

    $controle = new HistoricoControle(new PesquisaDAO());
    $controle->processarHistorico('listarPorUsuario', [
        'id_usuario' => (int)$_SESSION['usuario']['id_usuario']
    ]);
});


$router->addGet('abrir_pdf', function () {
    $codigo = $_GET['codigo'] ?? '';
    if (empty($codigo)) {
        http_response_code(400);
        echo "Código inválido.";
        exit;
    }

    $pdfDir = $_ENV['PDF_DIR'];
    $pdfPath = rtrim($pdfDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $codigo . ".pdf";

    if (!file_exists($pdfPath)) {
        http_response_code(404);
        echo "PDF não encontrado.";
        exit;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($pdfPath) . '"');
    readfile($pdfPath);
    exit;
});


$router->addPost('processar_usuario', function () {
    $controle = new UsuarioControle(new UsuarioDAO());

    $id    = (int)($_POST['id'] ?? $_POST['id_usuario'] ?? 0);
    $nome  = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $senhaAtual     = $_POST['senha_atual'] ?? '';
    $novaSenha      = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $acao  = $_POST['acao'] ?? '';

    $controle->processarUsuario(
        $id,
        $nome,
        $email,
        $senha,
        $acao,
        $novaSenha,
        $confirmarSenha,
        $senhaAtual
    );
});

$router->addPost('processar_pesquisa', function () {
    $controle = new NominatimControle();

    $id_usuario        = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $estado            = $_POST['estado'] ?? $_POST['estado_normalizado'] ?? '';
    $pesquisa_original = $_POST['input_pesquisa'] ?? $_POST['pesquisa_normalizada'] ?? '';
    $query_hash        = $_POST['query_hash'] ?? '';
    $veioHistorico = isset($_POST['veioHistorico']) && $_POST['veioHistorico'] === 'true';


    $controle->processarNominatim(
        $id_usuario,
        $estado,
        $pesquisa_original,
        $query_hash,
        $veioHistorico
    );
});

$router->addPost('processar_overpass_alvo', function () {
    $controle = new OverpassControleAlvo();

    $idUsuario= (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $idPesquisa = (int)($_POST['idPesquisa'] ?? $_POST['id'] ?? 0);

    $controle->processarOverpassAlvo(
        $idUsuario,
        $idPesquisa
    );
});

$router->addPost('processar_overpass_entorno', function () {
    $controle = new OverpassControleEntorno();

    $idUsuario= (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $idPesquisa = (int)($_POST['idPesquisa'] ?? 0);
    $idPontoInter = (int)($_POST['idPontoInter'] ?? 0);
    $lat = (float)($_POST['lat'] ?? 0.0);
    $lon = (float)($_POST['lon'] ?? 0.0);
    $estado = (string)($_POST['estado'] ?? '');


    $controle->processarOverpassEntorno(
        $idUsuario,
        $idPesquisa,
        $idPontoInter,
        $lat,
        $lon,
        $estado
    );
});

$router->addPost('processar_historico', function () {
    $controle = new HistoricoControle(new PesquisaDAO());

    $id                 = (int)($_POST['id'] ?? 0);
    $idUsuario          = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $pesquisaOriginal   = $_POST['pesquisa_original'] ?? '';
    $pesquisaNormalizada= $_POST['pesquisa_normalizada'] ?? '';
    $estadoNormalizado  = $_POST['estado_normalizado'] ?? '';
    $dataPesquisa       = $_POST['data_pesquisa'] ?? '';
    $queryHash          = $_POST['query_hash'] ?? '';
    $osmId              = isset($_POST['osm_id']) ? (int)$_POST['osm_id'] : null;
    $osmType            = $_POST['osm_type'] ?? '';

    $nominatimStatus    = $_POST['nominatim_status'] ?? '';
    $overpassStatusAlvo = $_POST['overpass_status_alvo'] ?? '';
    $overpassStatusEntorno = $_POST['overpass_status_entorno'] ?? '';

    $lat                = $_POST['lat'] ?? '';
    $lon                = $_POST['lon'] ?? '';
    $boundingbox        = $_POST['boundingbox'] ?? '';
    $validade           = $_POST['validade'] ?? '';
    $acao                = $_POST['acao'] ?? '';

    $controle->processarHistorico($acao, [
    'id' => $id,
    'id_usuario' => $idUsuario,
    'pesquisa_original' => $pesquisaOriginal,
    'pesquisa_normalizada' => $pesquisaNormalizada,
    'estado_normalizado' => $estadoNormalizado,
    'data_pesquisa' => $dataPesquisa,
    'query_hash' => $queryHash,
    'osm_id' => $osmId,
    'osm_type' => $osmType,

    'nominatim_status' => $nominatimStatus,
    'overpass_status_alvo' => $overpassStatusAlvo,
    'overpass_status_entorno' => $overpassStatusEntorno,

    'lat' => $lat,
    'lon' => $lon,
    'boundingbox' => $boundingbox,
    'validade' => $validade,

    ]);

});


$router->addPost('processar_mapa', function () {
    $controle = new MapaControle();

    $idUsuario          = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $id                 = (int)($_POST['id'] ?? $_POST['id_poi'] ?? 0);
    $osmId              = isset($_POST['osm_id']) ? (int)$_POST['osm_id'] : null;
    $osmType            = $_POST['osm_type'] ?? '';
    $nome               = $_POST['nome'] ?? '';
    $estado             = $_POST['estado'] ?? '';
    $email              = $_POST['email'] ?? '';
    $telefone           = $_POST['telefone'] ?? '';
    $endereco           = $_POST['endereco'] ?? '';
    $horarioFuncionamento = $_POST['horario_funcionamento'] ?? '';
    $lat                = $_POST['lat'] ?? '';
    $lon                = $_POST['lon'] ?? '';
    $bbox               = $_POST['bbox'] ?? '';

    $controle->processarMapa([
        'id_usuario' => $idUsuario,
        'id' => $id,
        'osm_id' => $osmId,
        'osm_type' => $osmType,
        'nome' => $nome,
        'estado' => $estado,
        'email' => $email,
        'telefone' => $telefone,
        'endereco' => $endereco,
        'horario_funcionamento' => $horarioFuncionamento,
        'lat' => $lat,
        'lon' => $lon,
        'bbox' => $bbox,
    ]);

});


$router->addPost('processar_roteiro', function () {
    $controle = new RoteiroControle();

    $acao       = $_POST['acao'] ?? '';
    $roteiroRaw = $_POST['roteiro'] ?? '';
    $idUsuario  = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $emailUsuario =(string) trim($_SESSION['usuario']['email']) ?? '';
    $idPesquisa  = (int)($_SESSION['idPesquisa'] ?? 0);
    $idRoteiro  = (int)($_POST['id_roteiro'] ?? 0);
    $codigo  = $_POST['codigo'] ?? '';
    $titulo  = $_POST['titulo'] ?? ''; 
    $centro  = $_POST['centro'] ?? '';

    $imagemFile = $_FILES['imagem'] ?? null;

    $controle->processarRoteiro($acao, $roteiroRaw, $idUsuario, $emailUsuario, $idPesquisa, $imagemFile, $idRoteiro, $codigo, $titulo, $centro);
});





