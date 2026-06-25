<?php
namespace PADS\App\controladores;
use PADS\App\servicos\GerandoPdf;
use PADS\App\modelos\RoteiroDAO;
use PADS\App\entidades\Roteiro;
use PADS\App\modelos\PesquisaDAO;
use PADS\App\entidades\Coordenada;


class RoteiroControle
{
    private RoteiroDAO $repo;
    private PesquisaDAO $pes;

    public function __construct() {

     $this->repo = new RoteiroDAO();
     $this->pes = new PesquisaDAO();
    }

    public function processarRoteiro(string $acao, string $roteiroRaw, int $idUsuario, string $emailUsuario, int $idPesquisa, ?array $imagemFile, int $idRoteiro, $codigo,  $titulo, $centro): void
    {
        $roteiro = json_decode($roteiroRaw, true);

        if (empty($acao)) {
            $this->responderErro("Ação não informada.", $acao );
        }
        
        if ($idUsuario <= 0) {
            $this->responderErro("Usuário inválido ou não autenticado.", $acao);
        }

        if (empty($emailUsuario)) {
            $this->responderErro("Email de usuário inválido.", $acao);
        }

        if($acao === 'cadastrarRoteiro' ||  $acao === 'editarRoteiro' ) {

        if(!$roteiro){
            $this->responderErro("Roteiro vazio.", $acao);
        }

        if (!$imagemFile || $imagemFile['error'] !== UPLOAD_ERR_OK) {
            $this->responderErro("Imagem não enviada ou inválida.", $acao);
        }
    }

        if($acao === 'excluirRoteiro' ||  $acao === 'listarEditarRoteiro' ||  $acao === 'editarRoteiro') {
            if ($idRoteiro <= 0 || empty($codigo)) {
            $this->responderErro("Roteiro Inválido", $acao);
        }
        }

  switch ($acao) {

        case 'cadastrarRoteiro':

        if ($idPesquisa <= 0) {
            $this->responderErro("ID de pesquisa inválido.", $acao);
        }

          if (empty($roteiro['titulo'])) {
            $this->responderErro("Roteiro sem título.", $acao);
        }
        
        if (empty($roteiro['dias']) || !is_array($roteiro['dias'])) {
            $this->responderErro("Roteiro deve conter ao menos um dia.", $acao);
        }


    $wktCentro = $this->pes->listarParaRoteiro($idPesquisa);

    if ($wktCentro === null) {
        $this->responderErro("Não foi possível obter o centro da pesquisa.", $acao);
    }

    $rBase = new Roteiro();
    $rBase->setIdUsuario($idUsuario);
    $rBase->setTitulo($roteiro['titulo']);
    $rBase->setCodMD5($roteiro['titulo'], $emailUsuario, $idUsuario);
        

    $rBase->setCentro(Coordenada::fromWKT($wktCentro));

    $pontosParaInserir = [];
    foreach ($roteiro['dias'] as $diaIndex => $dia) {
        $dataDia = $dia['data'];
        if (empty($dataDia)) {
            $this->responderErro("Dia faltando no índice {$diaIndex}.", $acao);
        }
        if (empty($dia['pontos']) || !is_array($dia['pontos'])) {
            $this->responderErro("Dia {$dataDia} não contém pontos válidos.", $acao);
        }

        foreach ($dia['pontos'] as $pIndex => $ponto) {
            $idPoi = (int)($ponto['id']);
            if ($idPoi <= 0) {
                $this->responderErro("POI inválido no dia {$dataDia}, ponto {$pIndex}.", $acao);
            }

            $entradaStr = $ponto['entrada'];
            $saidaStr   = $ponto['saida'];
            if (empty($entradaStr) || empty($saidaStr)) {
                $this->responderErro("Horários ausentes para POI {$idPoi} no dia {$dataDia}." , $acao);
            }

            $pontosParaInserir[] = [
                'id_poi' => $idPoi,
                'dia' => $dataDia,
                'entrada' => $entradaStr,
                'saida' => $saidaStr,
                'observacoes' => $ponto['notas'] ?? null
            ];
        }
    }

    $idRoteiro = $this->repo->inserirRoteiro($rBase, $pontosParaInserir);

    if ($idRoteiro === null) {
        $this->responderErro("Falha ao inserir roteiro e seus pontos.", $acao);
    }else if($idRoteiro === -1){
    $this->responderErro("O Usuário já tem um roteiro com este Título.", $acao);
    
    }
    $rBase->setIdRoteiro($idRoteiro);

    $conteudo = file_get_contents($imagemFile['tmp_name']);
    $imagemBase64 = base64_encode($conteudo);

    $pdfService = new GerandoPdf();
    $pdfPath = $pdfService->criarPdfComImagem($imagemBase64, $roteiro, $rBase->getCodigo());

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'     => 'ok',
        'acao'       => $acao,
        'id_usuario' => $idUsuario,
        'idPesquisa' => $idPesquisa,
        'roteiro'    => $roteiro,
        'pdfUrl'     => $pdfPath,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
    break;

    case "listarRoteiro":

            $roteiros = $this->repo->listarRoteiro($idUsuario);

                if($roteiros === null){
                $_SESSION['flash_erro']= 'Erro em Buscar Roteiros do Usuário';
                header("Location: index.php?rota=telaPrincipal");
                exit;
                }

            $_SESSION['roteiros'] = array_map(function(Roteiro $r) {
                return [
                    'id_roteiro'   => $r->getIdRoteiro(),
                    'titulo'       => $r->getTitulo(),
                    'codigo'       => $r->getCodigo(),
                    'id_usuario'   => $r->getIdUsuario(),
                    'data_r' => $r->getDataR()
                                        ? $r->getDataR()->format('Y-m-d H:i:s')
                                        : null,
                    'centro'       => $r->getCentro()
                                        ? $r->getCentro()->toWKT()
                                        : null,
                ];
            }, $roteiros);

            header("Location: index.php?rota=telaPrincipal&secao=historicoRoteiro");
            exit;
        break;

         case "excluirRoteiro":

                $roteiro = new Roteiro();
                $roteiro->setIdRoteiro($idRoteiro);
                $roteiro->setIdUsuario($idUsuario);

                $sucesso = $this->repo->excluirRoteiro($roteiro);

                if ($sucesso) {

                    $pdfDir =  $_ENV['PDF_DIR']; 
                    $pdfPath = rtrim($pdfDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $codigo . ".pdf";
                    if (file_exists($pdfPath)) {
                        unlink($pdfPath);
                    }

                $_SESSION['flash_sucesso'] = "Roteiro excluído com sucesso.";
             echo '
                <form id="procesRot" action="index.php?rota=processar_roteiro" method="post">
                <input type="hidden" name="acao" value="listarRoteiro">
                <input type="hidden" name="id_roteiro" value="' . $roteiro->getIdRoteiro() . '">
                </form>
                <script>
                document.getElementById("procesRot").submit();
                </script>
                ';
                exit;

                } else {
                    $this->responderErro("Erro ao tentar excluir Roteiro!", $acao);
                    exit;
                    
                }
                break;

 case "listarEditarRoteiro":
    unset($_SESSION['geojson_telaMapa']);


    $resultado = $this->repo->listarEditarRoteiro($idRoteiro);

    if (!$resultado){
      $this->responderErro("Erro em Buscar Dados do Roteiro para Edição!", $acao);
}

    $coordCentro = null;
    if (!empty($centro)) {
        $coordCentro = Coordenada::fromWKT($centro);
    } else{
        $this->responderErro("Roteiro Inválido para Edição!", $acao);
    }

    $featureRoteiro = [
        "type" => "Feature",
        "geometry" => [
            "type" => "Point",
            "coordinates" => [
                $coordCentro?->getLng(),
                $coordCentro?->getLat()
            ]
        ],
        "properties" => [
            "tipo" => "Roteiro",
            "id_roteiro" => $idRoteiro,
            "titulo" => $titulo,
            "codigo" => $codigo
        ]
    ];

    $agrupados = [];
    foreach ($resultado as $linha) {
        $rp = $linha['roteiroPoi'];
        $pi = $linha['pontoInteresse'];

        $idPoi = $pi->getId();
        if (!isset($agrupados[$idPoi])) {
            $agrupados[$idPoi] = [
                "type" => "Feature",
                "geometry" => [
                    "type" => "Point",
                    "coordinates" => [
                        $pi->getIndiceEspacialOverpass()?->getLng(),
                        $pi->getIndiceEspacialOverpass()?->getLat()
                    ]
                ],
                "properties" => [
                    "tipo" => "RoteiroPOI",
                    "id" => $pi->getId(),
                    "nome" => $pi->getNome(),
                    "estado" => $pi->getEstado(),
                    "endereco" => $pi->getEndereco(),
                    "horario_funcionamento" => $pi->getHorarioFuncionamento(),
                    "telefone" => $pi->getTelefone(),
                    "email" => $pi->getEmail(),
                    "website" => $pi->getWebsite(),
                    "ocorrencias" => []
                ]
            ];
        }

        $agrupados[$idPoi]["properties"]["ocorrencias"][] = [
            "id_roteiro_poi" => $rp->getIdRoteiroPoi(),
            "dia" => $rp->getDia()->format("Y-m-d"),
            "entrada" => $rp->getEntrada()->format("H:i"),
            "saida" => $rp->getSaida()->format("H:i"),
            "observacoes" => $rp->getObservacoes()
        ];
    }

    $idsPoiExistentes = array_keys($agrupados);

    $entorno = $this->repo->listarRoteiroEntorno($coordCentro, $idsPoiExistentes);

    if ($entorno === null){
      $this->responderErro("Erro em Buscar Dados do Roteiro para Edição! 2", $acao);
}

    $featuresEntorno = [];
    foreach ($entorno as $pi) {
        $featuresEntorno[] = [
            "type" => "Feature",
            "geometry" => [
                "type" => "Point",
                "coordinates" => [
                    $pi->getIndiceEspacialOverpass()?->getLng(),
                    $pi->getIndiceEspacialOverpass()?->getLat()
                ]
            ],
            "properties" => [
                "tipo" => "Entorno",
                "id" => $pi->getId(),
                "nome" => $pi->getNome()
            ]
        ];
    }

    $geojson = [
        "type" => "FeatureCollection",
        "features" => array_merge([$featureRoteiro], array_values($agrupados), $featuresEntorno)
    ];

    $_SESSION['geojson_telaMapa'] = json_encode(
        $geojson,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

     header("Location: index.php?rota=telaPrincipal&secao=editarRoteiroMapa");
    exit;
    break;

     case "editarRoteiro":

    
        $okTitulo = $this->repo->atualizarTituloRoteiro($idRoteiro, $titulo);
        if (!$okTitulo) {
            $this->responderErro("Falha ao atualizar título.", $acao);
    }

    if (!empty($roteiro['removidas'])) {
        $okRemovidas = $this->repo->excluirOcorrencias($idRoteiro, $roteiro['removidas']);
        if (!$okRemovidas) {
            $this->responderErro("Falha ao excluir ocorrências do roteiro.", $acao);
        }
    }

    if (!empty($roteiro['editadas'])) {
        $okEditadas = $this->repo->atualizarOcorrencias($idRoteiro, $roteiro['editadas']);
        if (!$okEditadas) {
            $this->responderErro("Falha ao atualizar ocorrências do roteiro.", $acao);
        }
    }

    if (!empty($roteiro['novas'])) {
        $okNovas = $this->repo->inserirNovasOcorrencias($idRoteiro, $roteiro['novas']);
        if (!$okNovas) {
            $this->responderErro("Falha ao inserir novas ocorrências no roteiro.", $acao);
        }
    }

   
    $conteudo = file_get_contents($imagemFile['tmp_name']);
    $imagemBase64 = base64_encode($conteudo);

    $pdfService = new GerandoPdf();
    $pdfPath = $pdfService->editarPdf($imagemBase64, $roteiroRaw, $codigo);


    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'     => 'ok',
        'acao'       => $acao,
        'id_usuario' => $idUsuario,
        'idPesquisa' => $idPesquisa,
        'id_roteiro' => $idRoteiro,
        'codigo'     => $codigo,
        'titulo'     => $titulo,
        'pdfUrl'     => $pdfPath,
        'mensagem'   => 'Fluxo de edição concluído com sucesso.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
    break;



            default:
                $this->responderErro("Ação desconhecida: $acao");
        }
    }

    private function responderErro(string $mensagem, ?string $acao = null): void
{
    if (in_array($acao, ['cadastrarRoteiro', 'editarRoteiro'], true)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'   => 'erro',
            'mensagem' => $mensagem
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $_SESSION['flash_erro'] = $mensagem;
    header("Location: index.php?rota=telaPrincipal&secao=historicoRoteiro");
    exit;
}

}

?>