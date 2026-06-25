<?php

namespace PADS\App\controladores;

use PADS\App\entidades\PontosInteresse;
use PADS\App\entidades\Pesquisa;
use PADS\App\entidades\Coordenada;
use PADS\App\entidades\Historico;
use PADS\App\modelos\PesquisaDAO;
use PADS\App\modelos\PontosInteresseDAO;
use PADS\App\servicos\overpassServico\OverpassServico;
use PADS\App\servicos\overpassServico\OverpassRequisicaoAlvo;


class OverpassControleAlvo
{
    private PontosInteresseDAO $repo;
    private PesquisaDAO $pes;

    public function __construct() {

     $this->repo = new PontosInteresseDAO();
     $this->pes = new PesquisaDAO();
    }
    
    public function processarOverpassAlvo(
        int $idUsuario,
        int $idPesquisa,
    ): void {
        $dados = [
            'idUsuario' => $idUsuario,
            'idPesquisa'  => $idPesquisa,
        ];

        if (!$idUsuario  || !$idPesquisa) {
            $_SESSION['pesquisa_erros'][] = "Falha ao obter parâmetros para o Overpass Alvo.";
            header("Location: index.php?rota=telaPrincipal");
            exit;
        }

        $historico = new Historico();
        $historico->setIdUsuario($idUsuario);
        $historico->setIdPesquisa($idPesquisa);

        $verificarHistoricoResultado= $this->pes->verificarHistorico($historico);
            
        $pesquisa = new Pesquisa();        
        $pesquisa->setId($idPesquisa);

        $dadosParaRequisicoesOver = $this->pes->listarParaOverpass($pesquisa); 
        if (!$dadosParaRequisicoesOver) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível trazer os dados de Pesquisa para as requisições Overpass.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

        $osmId   = $dadosParaRequisicoesOver->getOsmId();
        $osmType = $dadosParaRequisicoesOver->getOsmType();
        $estado  = $dadosParaRequisicoesOver->getEstadoNormalizado();

        if (!$osmId  || !$osmType) {
            $_SESSION['pesquisa_erros'][] = "Falha ao obter parâmetros para a Requisição Overpass Alvo.";
            header("Location: index.php?rota=telaPrincipal");
            exit;
        }

        $pontosinter = new PontosInteresse();
        $pontosinter->setOsmId($osmId);
        $pontosinter->setOsmType($osmType);

        $verificarAlvoResultado = $this->repo->verificarAlvo($pontosinter); 
        $idPontoInter = 0;
        $lat = null;
        $lon = null;

          if ($verificarAlvoResultado) {
                $idPontoInter = $verificarAlvoResultado->getId();
                $lat = $verificarAlvoResultado->getIndiceEspacialOverpass()->getLat();
                $lon = $verificarAlvoResultado->getIndiceEspacialOverpass()->getLng();            
            }
         
                
        $verificarOverpassStatusAlvoResultado = $this->pes->verificarOverpassStatusAlvo($pesquisa); 
        
        if (!$verificarOverpassStatusAlvoResultado) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status do overpass alvo.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $overpass_status_alvo= $verificarOverpassStatusAlvoResultado['overpass_status_alvo']; 
            $aindaValido = (int)$verificarOverpassStatusAlvoResultado['ainda_valido'];

            if ($overpass_status_alvo === 'VALIDO') {

                echo '<form id="processarOver1" method="POST" action="index.php?rota=processar_overpass_entorno">';
                        echo '<input type="hidden" name="idPesquisa" value="' . $idPesquisa . '">';
                        echo '<input type="hidden" name="idPontoInter" value="' . $idPontoInter . '">';
                        echo '<input type="hidden" name="lat" value="' . $lat . '">';
                        echo '<input type="hidden" name="lon" value="' . $lon. '">';
                        echo '<input type="hidden" name="estado" value="' . $estado. '">';
                        echo '</form>';
                        echo '<script>document.getElementById("processarOver1").submit();</script>';
                        exit;

            }else if ($overpass_status_alvo === 'NULO') {

                 if ($aindaValido === 1) {
                    $_SESSION['pesquisa_erros'][] =
                        "A pesquisa não retornou resultado para o overpass alvo, tente outra."
                        . "Somente daqui 24h os dados dessa pesquisa poderão ser atualizados.";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;

                 }else{

                    try {    

                
                        $jsonBruto = OverpassRequisicaoAlvo::requisicaoOverpassAlvo(
                        $osmId,
                        $osmType
                        );
  
                    $dadosPolidos = OverpassServico::extrairDadosOver($jsonBruto);
                    error_log("extrairDadosOver: " . json_encode($dadosPolidos, JSON_UNESCAPED_UNICODE));

                    

                if (isset($dadosPolidos[0]['erro_tecnico'])) {
                error_log("OverpassControleAlvo: erro_tecnico -> " . $dadosPolidos[0]['erro_tecnico']);
                $_SESSION['pesquisa_erros'][] = "Erro técnico ao consultar overpass para alvo: " . $dadosPolidos[0]['erro_tecnico'];
            }

                    
                    $pontosinter = PontosInteresse::criarDeArray($dadosPolidos);
                    if (
                        isset($dadosPolidos['lat'], $dadosPolidos['lon']) &&
                        is_numeric($dadosPolidos['lat']) &&
                        is_numeric($dadosPolidos['lon'])
                    ) {
                        $coord = new Coordenada((float)$dadosPolidos['lat'], (float)$dadosPolidos['lon']);
                        $pontosinter->setIndiceEspacialOverpass($coord);
                    }

                    $pontosinter->setEstado($estado);

                    $persitivel= $pontosinter->isPersistivel();

                    if ($persitivel) {

                        $idPontoInter = $this->repo->inserirPontosInteresse($pontosinter);
                        if (!$idPontoInter) {
                        $_SESSION['pesquisa_erros'][] = "Erro na inserção de Pontos Interesse Nessa Nova Tentativa.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                    }
                    }

                    $atualizarValidadeResultado = $this->pes->atualizarValidade($pesquisa);

                    if(!$atualizarValidadeResultado){
                            error_log("Erro ao atualizar a Validade apos fazer a requisicao para o overpass.");
                    }

                    $overpassStatusAlvo=$pontosinter->atualizarOverpassStatusAlvoAutomatico();            
                    $pesquisa->setOverpassStatusAlvo($overpassStatusAlvo);

                    $atualizarOverpassStatusAlvoResultado2 = $this->pes->atualizarOverpassStatusAlvo($pesquisa);

                    if(!$atualizarOverpassStatusAlvoResultado2){
                            $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Alvo Nessa Nova Tentativa.";
                            header("Location: index.php?rota=telaPrincipal");
                            exit;
                    }
                    
                    $verificarOverpassStatusAlvoResultado2= $this->pes->verificarOverpassStatusAlvo($pesquisa); 

                    if (!$verificarOverpassStatusAlvoResultado2) {
                        $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status do overpass alvo nessa Nova Tentativa.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                    }

                    $overpassStatusAlvo= $verificarOverpassStatusAlvoResultado2['overpass_status_alvo']; 

                    if ($overpassStatusAlvo === 'VALIDO') {
                    
                    $pontosinter->setId($idPontoInter);
                    $listarPontosInteresseResultado = $this->repo->listarPontosInteresse($pontosinter);

                    
                if (!$listarPontosInteresseResultado) {
                        $_SESSION['pesquisa_erros'][] = "Não foi possível listar Pontos Interesse nessa nova requisição ao overpass alvo.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                    }

                    $lat = $listarPontosInteresseResultado->getIndiceEspacialOverpass()->getLat();
                    $lon = $listarPontosInteresseResultado->getIndiceEspacialOverpass()->getLng(); 
                        
                     echo '<form id="processarOver2" method="POST" action="index.php?rota=processar_overpass_entorno">';
                        echo '<input type="hidden" name="idPesquisa" value="' . $idPesquisa . '">';
                        echo '<input type="hidden" name="idPontoInter" value="' . $idPontoInter . '">';
                        echo '<input type="hidden" name="lat" value="' . $lat . '">';
                        echo '<input type="hidden" name="lon" value="' . $lon. '">';
                        echo '<input type="hidden" name="estado" value="' . $estado. '">';
                        echo '</form>';
                        echo '<script>setTimeout(function() {
                        document.getElementById("processarOver2").submit();
                        }, 75000); 
                        </script>';                        
                        exit;
                
                    }else if ($overpassStatusAlvo === 'NULO') {
                            $_SESSION['pesquisa_erros'][] =
                                "A Nova pesquisa não retornou resultado para o overpass alvo, tente outra"
                                . "Somente daqui 24h os dados dessa pesquisa poderão ser atualizados. ";
                            header("Location: index.php?rota=telaPrincipal");
                            exit;
                    }
                    

                    } catch (\RuntimeException $e) {
                    $_SESSION['pesquisa_erros'][] = "Erro de Configuração: " . $e->getMessage();
                    header("Location: index.php?rota=telaPrincipal");
                    exit;

                    } catch (\InvalidArgumentException $e) {
                        $_SESSION['pesquisa_erros'][] = "Erro de processamento da resposta do Overpass Nessa Nova Tentativa: " . $e->getMessage();
                        header("Location: index.php?rota=telaPrincipal");
                        exit;

                    } catch (\Exception $e) {
                    $_SESSION['pesquisa_erros'][] = "Erro inesperado: " . $e->getMessage();
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
                    }
                 }

            }else{

           if ($verificarAlvoResultado) {
            $VALIDO = 'VALIDO';
            $pesquisa->setOverpassStatusAlvo($VALIDO);
            $atualizarOverpassStatusAlvoResultado=$this->pes->atualizarOverpassStatusAlvo($pesquisa);

            if(!$atualizarOverpassStatusAlvoResultado){
                     $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Alvo, baseado em algo já existente em Pontos Interesse para ele.";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
            }

                echo '<form id="processarOver3" method="POST" action="index.php?rota=processar_overpass_entorno">';
                        echo '<input type="hidden" name="idPesquisa" value="' . $idPesquisa . '">';
                        echo '<input type="hidden" name="idPontoInter" value="' . $idPontoInter . '">';
                        echo '<input type="hidden" name="lat" value="' . $lat . '">';
                        echo '<input type="hidden" name="lon" value="' . $lon. '">';
                        echo '<input type="hidden" name="estado" value="' . $estado. '">';
                        echo '</form>';
                        echo '<script>document.getElementById("processarOver3").submit();</script>';
                        exit;

            }
            

            try {    

                $jsonBruto = OverpassRequisicaoAlvo::requisicaoOverpassAlvo(
                $osmId,
                $osmType
                );

            $dadosPolidos = OverpassServico::extrairDadosOver($jsonBruto);
            error_log("extrairDadosOver: " . json_encode($dadosPolidos, JSON_UNESCAPED_UNICODE));


           if (isset($dadosPolidos[0]['erro_tecnico'])) {
                error_log("OverpassControleAlvo: erro_tecnico -> " . $dadosPolidos[0]['erro_tecnico']);
                $_SESSION['pesquisa_erros'][] = "Erro técnico ao consultar overpass para alvo: " . $dadosPolidos[0]['erro_tecnico'];
            }

            
            $pontosinter = PontosInteresse::criarDeArray($dadosPolidos);
            if (
                isset($dadosPolidos['lat'], $dadosPolidos['lon']) &&
                is_numeric($dadosPolidos['lat']) &&
                is_numeric($dadosPolidos['lon'])
            ) {
                $coord = new Coordenada((float)$dadosPolidos['lat'], (float)$dadosPolidos['lon']);
                $pontosinter->setIndiceEspacialOverpass($coord);
            }

            $pontosinter->setEstado($estado);

            $persitivel= $pontosinter->isPersistivel();

            if ($persitivel) {

                $idPontoInter = $this->repo->inserirPontosInteresse($pontosinter);
                if (!$idPontoInter) {
                $_SESSION['pesquisa_erros'][] = "Erro na inserção de Pontos Interesse.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }
            }

            $overpassStatusAlvo=$pontosinter->atualizarOverpassStatusAlvoAutomatico();            
            $pesquisa->setOverpassStatusAlvo($overpassStatusAlvo);

            $atualizarOverpassStatusAlvoResultado2 = $this->pes->atualizarOverpassStatusAlvo($pesquisa);


             if(!$atualizarOverpassStatusAlvoResultado2){
                     $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Alvo.";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
            }
             
            $verificarOverpassStatusAlvoResultado2= $this->pes->verificarOverpassStatusAlvo($pesquisa); 

             if (!$verificarOverpassStatusAlvoResultado2) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status do overpass alvo.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $overpassStatusAlvo= $verificarOverpassStatusAlvoResultado2['overpass_status_alvo']; 

            if ($overpassStatusAlvo === 'VALIDO') {
             
            $pontosinter->setId($idPontoInter);
            $listarPontosInteresseResultado = $this->repo->listarPontosInteresse($pontosinter);

            
          if (!$listarPontosInteresseResultado) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível listar Pontos Interesse.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $lat = $listarPontosInteresseResultado->getIndiceEspacialOverpass()->getLat();
            $lon = $listarPontosInteresseResultado->getIndiceEspacialOverpass()->getLng(); 
                
           echo '<form id="processarOver4" method="POST" action="index.php?rota=processar_overpass_entorno">';
                        echo '<input type="hidden" name="idPesquisa" value="' . $idPesquisa . '">';
                        echo '<input type="hidden" name="idPontoInter" value="' . $idPontoInter . '">';
                        echo '<input type="hidden" name="lat" value="' . $lat . '">';
                        echo '<input type="hidden" name="lon" value="' . $lon. '">';
                        echo '<input type="hidden" name="estado" value="' . $estado. '">';
                        echo '</form>';
                        echo '<script>setTimeout(function() {
                        document.getElementById("processarOver4").submit();
                        }, 75000); 
                        </script>';
                        exit;


                        
            }else if ($overpassStatusAlvo === 'NULO') {
                    $_SESSION['pesquisa_erros'][] =
                        "A pesquisa não retornou resultado para o overpass alvo, tente outra."
                        . "Somente daqui 24h os dados dessa pesquisa poderão ser atualizados. ";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
            }
            

             } catch (\RuntimeException $e) {
            $_SESSION['pesquisa_erros'][] = "Erro de Configuração: " . $e->getMessage();
            header("Location: index.php?rota=telaPrincipal");
            exit;

            } catch (\InvalidArgumentException $e) {
                $_SESSION['pesquisa_erros'][] = "Erro de processamento da resposta do Overpass: " . $e->getMessage();
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