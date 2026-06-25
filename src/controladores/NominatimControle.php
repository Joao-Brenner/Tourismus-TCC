<?php

namespace PADS\App\controladores;

use PADS\App\validacoes\ValidacaoNominatim;
use PADS\App\servicos\nominatimServico\NominatimServico;
use PADS\App\servicos\nominatimServico\NominatimRequisicao;
use PADS\App\entidades\Historico;
use PADS\App\entidades\Pesquisa;
use PADS\App\entidades\Coordenada;
use PADS\App\modelos\PesquisaDAO;

class NominatimControle
{
    private ValidacaoNominatim $validacao;
    private PesquisaDAO $repo;

    public function __construct() {
        $this->validacao = new ValidacaoNominatim();
        $this->repo = new PesquisaDAO();
    }

    public function processarNominatim(
        int $id_usuario,
        string $estado,
        string $pesquisa_original,
        string $query_hash,
        bool $veioHistorico
    ): void {
        $dados = [
            'id_usuario'        => $id_usuario,
            'estado'            => $estado,
            'pesquisa_original' => $pesquisa_original,
            'query_hash'        => $query_hash,
            'veioHistorico'     => $veioHistorico
        ];

        $pesquisaNormalizada = '';
        $estadoNormalizado   = '';
        $idUsuario           = 0;
        $queryHash           = '';
        
        if ($veioHistorico === false){
        $resultado = ValidacaoNominatim::validar($dados);

        if (!empty($resultado['erros'])) {
            $_SESSION['pesquisa_erros'] = $resultado['erros'];
            header("Location: index.php?rota=telaPrincipal");
            exit;
        }
        
        $dadosValidados = $resultado['dados_validados'] ??  [];
        $pesquisaNormalizada = $dadosValidados['pesquisa'] ?? '';
        $estadoNormalizado   = $dadosValidados['estado'] ?? '';
        $idUsuario           = $dadosValidados['id_usuario'] ?? 0;
        $queryHash           = $_SESSION['ultimaQueryHash'] ?? '';

        }else{

        $pesquisaNormalizada = $dados['pesquisa_original'] ?? '';
        $estadoNormalizado = $dados['estado'] ?? '';
        $queryHash = $dados['query_hash'] ?? '';
        $idUsuario = $dados['id_usuario'] ?? 0;
        }
 
        if ($pesquisaNormalizada === '' || $estadoNormalizado === '' || $queryHash === '' || $idUsuario === 0) {
            $_SESSION['pesquisa_erros'][] = "Falha ao obter parâmetros normalizados da pesquisa.";
            header("Location: index.php?rota=telaPrincipal");
            exit;
        }

        $pesquisa = new Pesquisa();
        $pesquisa->setPesquisaNormalizada($pesquisaNormalizada);
        $pesquisa->setEstadoNormalizado($estadoNormalizado);
        $pesquisa->setQueryHash($queryHash);

        $idPesquisa = $this->repo->verificarPesquisa($pesquisa);

        if ($idPesquisa !== null) {

        $historico = new Historico();
        $historico->setIdUsuario($idUsuario);
        $historico->setIdPesquisa($idPesquisa);

       $verificarHistoricoResultado =  $this->repo->verificarHistorico($historico);
       if (!$verificarHistoricoResultado) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o histórico.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

        $pesquisa->setId($idPesquisa);
        $verificarNominatimStatusResultado = $this->repo->verificarNominatimStatus($pesquisa);

            if (!$verificarNominatimStatusResultado) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status da pesquisa.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $status      = $verificarNominatimStatusResultado['nominatim_status']; 
            $aindaValido = (int)$verificarNominatimStatusResultado['ainda_valido']; 

            if ($status === 'VALIDO') {

                echo '<form id="processarOver" method="POST" action="index.php?rota=processar_overpass_alvo">';
                echo '<input type="hidden" name="idPesquisa" value="' . $pesquisa->getId() . '">';
                echo '</form>';
                echo '<script>document.getElementById("processarOver").submit();</script>';
                exit;


            } else {
                if ($aindaValido === 1) {
                    $_SESSION['pesquisa_erros'][] =
                        "A pesquisa não retornou resultado, tente outra. "
                        . "Somente daqui 24h os dados dessa pesquisa poderão ser atualizados. ";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;

                } else {
                    
                    try{
                $jsonBruto = NominatimRequisicao::requisicaoNominatim(
                        $pesquisaNormalizada,
                        $estadoNormalizado
                    );

                    $dadosPolidos = NominatimServico::extrairDados($jsonBruto);
                    error_log("extrairDados: " . json_encode($dadosPolidos, JSON_UNESCAPED_UNICODE));

                    if (isset($dadosPolidos['erro_tecnico'])) {
                    error_log("NominatimControle: erro_tecnico -> " . $dadosPolidos['erro_tecnico']);
                    $_SESSION['pesquisa_erros'][] = "Erro técnico ao consultar Nominatim: " . $dadosPolidos['erro_tecnico'];
                }
                            
                    $pesquisa = Pesquisa::criarDeArray($dadosPolidos);
                    if (
                        isset($dadosPolidos['lat'], $dadosPolidos['lon']) &&
                        is_numeric($dadosPolidos['lat']) &&
                        is_numeric($dadosPolidos['lon'])
                    ) {
                        $coord = new Coordenada((float)$dadosPolidos['lat'], (float)$dadosPolidos['lon']);
                        $pesquisa->setIndiceEspacialNominatim($coord);
                    }

                    if (isset($dadosPolidos['boundingbox']) && is_array($dadosPolidos['boundingbox'])) {
                        $pesquisa->setBoundingboxFromArray($dadosPolidos['boundingbox']);
                    }

                    $pesquisa->atualizarNominatimStatusAutomatico();
                    $pesquisa->setId($idPesquisa);
                    
                    $atualizarPesquisaResultado = $this->repo->atualizarPesquisa($pesquisa);

                    if (!$atualizarPesquisaResultado) {
                        $_SESSION['pesquisa_erros'][] = "Não foi possível atualizar a pesquisa.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                    }

                    $verificarNominatimStatusResultado2 = $this->repo->verificarNominatimStatus($pesquisa);
                    
                    if (!$verificarNominatimStatusResultado2) {
                        $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status da pesquisa.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                    }

                    $status= $verificarNominatimStatusResultado2['nominatim_status']; 

                    if ($status === 'VALIDO') {
                    echo '<form id="processarOver2" method="POST" action="index.php?rota=processar_overpass_alvo">';
                    echo '<input type="hidden" name="idPesquisa" value="' . $pesquisa->getId() . '">';
                    echo '</form>';
                    echo '<script>document.getElementById("processarOver2").submit();</script>';
                exit;
                
                    }else{
                        $_SESSION['pesquisa_erros'][] =
                                "A Nova pesquisa não retornou resultado, tente outra. "
                                . "Somente daqui 24h os dados dessa pesquisa poderão ser atualizados. ";
                                header("Location: index.php?rota=telaPrincipal");
                        exit;
                    }
                    } catch (\RuntimeException $e) {
            $_SESSION['pesquisa_erros'][] = "Erro de Configuração: " . $e->getMessage();
            header("Location: index.php?rota=telaPrincipal");
            exit;

            } catch (\InvalidArgumentException $e) {
                $_SESSION['pesquisa_erros'][] = "Erro de processamento da resposta do Nominatim: " . $e->getMessage();
                header("Location: index.php?rota=telaPrincipal");
                exit;

            } catch (\Exception $e) {
            $_SESSION['pesquisa_erros'][] = "Erro inesperado: " . $e->getMessage();
            header("Location: index.php?rota=telaPrincipal");
            exit;
            }

            }
}

        } else {

        
        try {

            $jsonBruto = NominatimRequisicao::requisicaoNominatim(
                $pesquisaNormalizada,
                $estadoNormalizado
            );


            $dadosPolidos = NominatimServico::extrairDados($jsonBruto);
        error_log("extrairDados: " . json_encode($dadosPolidos, JSON_UNESCAPED_UNICODE));

            

            if (isset($dadosPolidos['erro_tecnico'])) {
            error_log("NominatimControle: erro_tecnico -> " . $dadosPolidos['erro_tecnico']);
            $_SESSION['pesquisa_erros'][] = "Erro técnico ao consultar Nominatim: " . $dadosPolidos['erro_tecnico'];
}


            $pesquisa = Pesquisa::criarDeArray($dadosPolidos);
            $pesquisa->setPesquisaOriginal($pesquisa_original);
            $pesquisa->setPesquisaNormalizada($pesquisaNormalizada);
            $pesquisa->setEstadoNormalizado($estadoNormalizado);
            $pesquisa->setQueryHash($queryHash);
            if (
                isset($dadosPolidos['lat'], $dadosPolidos['lon']) &&
                is_numeric($dadosPolidos['lat']) &&
                is_numeric($dadosPolidos['lon'])
            ) {
                $coord = new Coordenada((float)$dadosPolidos['lat'], (float)$dadosPolidos['lon']);
                $pesquisa->setIndiceEspacialNominatim($coord);
            }

            if (isset($dadosPolidos['boundingbox']) && is_array($dadosPolidos['boundingbox'])) {
                $pesquisa->setBoundingboxFromArray($dadosPolidos['boundingbox']);
            }


            $pesquisa->atualizarNominatimStatusAutomatico();

            $historico = new Historico();
            $historico->setIdUsuario($idUsuario);

            $inserirPesquisaResultado = $this->repo->inserirPesquisa($pesquisa, $historico);

             if (!$inserirPesquisaResultado) {
                $_SESSION['pesquisa_erros'][] = "Erro na inserção da pesquisa.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $idPesquisa = $historico->getIdPesquisa();

            $pesquisa->setId($idPesquisa);

            $verificarNominatimStatusResultado3 = $this->repo->verificarNominatimStatus($pesquisa);


              if (!$verificarNominatimStatusResultado3) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status da pesquisa.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $status= $verificarNominatimStatusResultado3['nominatim_status']; 


            if ($status === 'VALIDO') {
               echo '<form id="processarOver3" method="POST" action="index.php?rota=processar_overpass_alvo">';
                echo '<input type="hidden" name="idPesquisa" value="' . $pesquisa->getId() . '">';
                echo '</form>';
                echo '<script>document.getElementById("processarOver3").submit();</script>';
                exit;
            }else{
                $_SESSION['pesquisa_erros'][] =
                        "A pesquisa não retornou resultado, tente outra. "
                        . "Somente daqui 24h os dados dessa pesquisa poderão ser atualizados. ";
                        header("Location: index.php?rota=telaPrincipal");
                exit;
            }
            
            
            } catch (\RuntimeException $e) {
            $_SESSION['pesquisa_erros'][] = "Erro de Configuração: " . $e->getMessage();
            header("Location: index.php?rota=telaPrincipal");
            exit;

            } catch (\InvalidArgumentException $e) {
                $_SESSION['pesquisa_erros'][] = "Erro de processamento da resposta do Nominatim: " . $e->getMessage();
                header("Location: index.php?rota=telaPrincipal");
                exit;

            } catch (\Exception $e) {
            $_SESSION['pesquisa_erros'][] = "Erro inesperado: " . $e->getMessage();
            header("Location: index.php?rota=telaPrincipal");
            exit;
            }
            
        }           
    }
}


?>