<?php

namespace PADS\App\controladores;

use PADS\App\entidades\PontosInteresse;
use PADS\App\entidades\Pesquisa;
use PADS\App\entidades\Coordenada;
use PADS\App\modelos\PesquisaDAO;
use PADS\App\modelos\PontosInteresseDAO;
use PADS\App\servicos\overpassServico\OverpassServico;
use PADS\App\servicos\overpassServico\OverpassRequisicaoEntorno;


class OverpassControleEntorno
{
    private PontosInteresseDAO $repo;
    private PesquisaDAO $pes;

    public function __construct() {

     $this->repo = new PontosInteresseDAO();
     $this->pes = new PesquisaDAO();
    }
    
    public function processarOverpassEntorno(
        int $idUsuario,
        int $idPesquisa,
        int $idPontoInter,
        float $lat,
        float $lon,
        string $estado,

    ): void {
        $dados = [
            'idUsuario' => $idUsuario,
            'idPesquisa'  => $idPesquisa,
            'idPontoInter'  => $idPontoInter,
            'lat'  => $lat,
            'lon'  => $lon,
            'estado'  => $estado,

        ];

        if (!$idUsuario  || !$idPesquisa || !$idPontoInter || !$lat || !$lon || !$estado) {
            $_SESSION['pesquisa_erros'][] = "Falha ao obter parâmetros para o Overpass Entorno.";
            header("Location: index.php?rota=telaPrincipal");
            exit;
        }
        
        unset($_SESSION['idPesquisa']);
        $_SESSION['idPesquisa']=$idPesquisa;

        $quantidadeEntorno = 0;
        $constanteEntorno = 3;
        $pesquisa = new Pesquisa ();
        $pesquisa->setId($idPesquisa);
        $STATUS=null;
        $pontosinter = new PontosInteresse();
        $pontosinter->setId($idPontoInter);
        $coord = new Coordenada($lat, $lon);

        $pontosinter->setIndiceEspacialOverpass($coord);

        $listarAlvoResultado=$this->repo->listarAlvo($pontosinter, $pesquisa);

        if (!$listarAlvoResultado) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível listar os dados do ponto central/alvo.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

        $nomeAlvo = $listarAlvoResultado['nome'];
        $bbox = $listarAlvoResultado['boundingbox'];
            $bounds = [
                [$bbox['lon_min'], $bbox['lat_min']],
                [$bbox['lon_max'], $bbox['lat_max']]
            ];

        $quantidadeEntorno =  $this->repo->contarPontosNoEntorno($pontosinter);

        if($quantidadeEntorno === null){
            $_SESSION['pesquisa_erros'][] = "Erro ao contar pontos, no entorno do alvo, presentes no banco.";
                header("Location: index.php?rota=telaPrincipal");
            exit;
        }

        $quantidadeRequisição = $constanteEntorno - $quantidadeEntorno; 

         if($quantidadeEntorno >= 3){
                $STATUS ="VALIDO";
                
                $pesquisa->setOverpassStatusEntorno($STATUS);
                $atualizarOverpassStatusEntornoResultado1=$this->pes-> atualizarOverpassStatusEntorno($pesquisa); 

                if(!$atualizarOverpassStatusEntornoResultado1){
                        $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Entorno.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                }

            $listarPontosNoEntornoResultado1 = $this->repo->listarPontosNoEntorno($pontosinter, $idPontoInter);

            if(!$listarPontosNoEntornoResultado1){
                        $_SESSION['pesquisa_erros'][] = "Erro ao listar os dados do entorno.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                }

               $features = [];

                        $features[] = [
                            "type" => "Feature",
                            "bbox" => [
                                $bounds[0][0], 
                                $bounds[0][1], 
                                $bounds[1][0], 
                                $bounds[1][1]  
                            ],
                            "properties" => [
                                "id"   => $idPontoInter,
                                "Nome" => $nomeAlvo,
                                "Tipo" => "Alvo"
                            ],
                            "geometry" => [
                                "type" => "Point",
                                "coordinates" => [$lon, $lat] 
                            ]
                        ];

                        foreach ($listarPontosNoEntornoResultado1 as $pe) {
                            $features[] = [
                                "type" => "Feature",
                                "properties" => [
                                    "id"   => $pe->getId(),
                                    "Nome" => $pe->getNome(),
                                    "Tipo" => "Entorno"
                                ],
                                "geometry" => [
                                    "type" => "Point",
                                    "coordinates" => [
                                        $pe->getIndiceEspacialOverpass()?->getLng(),
                                        $pe->getIndiceEspacialOverpass()?->getLat()
                                    ]
                                ]
                            ];
                        }

                        $resposta = json_encode([
                            "type" => "FeatureCollection",
                            "features" => $features
                        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;
                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;


         }else{

        $verificarOverpassStatusEntornoResultado = $this->pes->verificarOverpassStatusEntorno($pesquisa); 
        
        if (!$verificarOverpassStatusEntornoResultado) {
                $_SESSION['pesquisa_erros'][] = "Não foi possível verificar o status do overpass entorno.";
                header("Location: index.php?rota=telaPrincipal");
                exit;
            }

            $overpassStatusEntorno= $verificarOverpassStatusEntornoResultado['overpass_status_entorno']; 
            $aindaValido = (int)$verificarOverpassStatusEntornoResultado['ainda_valido'];

               if ($overpassStatusEntorno === 'VALIDO' || $overpassStatusEntorno === 'NULO') {
                 if ($aindaValido === 1) {

            if($overpassStatusEntorno === 'VALIDO' && ($quantidadeEntorno <=2 && $quantidadeEntorno >=1)){

            $listarPontosNoEntornoResultado2 = $this->repo->listarPontosNoEntorno($pontosinter, $idPontoInter);

            if(!$listarPontosNoEntornoResultado2){
                    $_SESSION['pesquisa_erros'][] = "Erro ao listar os dados do entorno.";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
            }

             $features = [];

                        $features[] = [
                            "type" => "Feature",
                            "bbox" => [
                                $bounds[0][0], 
                                $bounds[0][1], 
                                $bounds[1][0], 
                                $bounds[1][1]  
                            ],
                            "properties" => [
                                "id"   => $idPontoInter,
                                "Nome" => $nomeAlvo,
                                "Tipo" => "Alvo"
                            ],
                            "geometry" => [
                                "type" => "Point",
                                "coordinates" => [$lon, $lat] 
                            ]
                        ];

                        foreach ($listarPontosNoEntornoResultado2 as $pe) {
                            $features[] = [
                                "type" => "Feature",
                                "properties" => [
                                    "id"   => $pe->getId(),
                                    "Nome" => $pe->getNome(),
                                    "Tipo" => "Entorno"
                                ],
                                "geometry" => [
                                    "type" => "Point",
                                    "coordinates" => [
                                        $pe->getIndiceEspacialOverpass()?->getLng(),
                                        $pe->getIndiceEspacialOverpass()?->getLat()
                                    ]
                                ]
                            ];
                        }

                        $resposta = json_encode([
                            "type" => "FeatureCollection",
                            "features" => $features
                        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;

                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;

                    }else{

                     $features = [];

                    $features[] = [
                        "type" => "Feature",
                        "bbox" => [
                            $bounds[0][0], 
                            $bounds[0][1], 
                            $bounds[1][0], 
                            $bounds[1][1]  
                        ],
                        "properties" => [
                            "id"   => $idPontoInter,
                            "Nome" => $nomeAlvo,
                            "Tipo" => "Alvo"
                        ],
                        "geometry" => [
                            "type" => "Point",
                            "coordinates" => [$lon, $lat] 
                        ]
                    ];

                    $resposta = json_encode([
                        "type" => "FeatureCollection",
                        "features" => $features
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;
                        $_SESSION['flash_erro'] = "Infelizmente Não Conseguimos Encontrar Sugestões no Entorno do Ponto Pesquisado";
                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;

                    }

                 }else{

                 try {    
                $jsonBruto = OverpassRequisicaoEntorno::requisicaoOverpassEntorno(
                $lat,
                $lon,
                $quantidadeRequisição
                );

                    $dadosPolidos = OverpassServico::extrairDadosOver($jsonBruto);
                    error_log("extrairDadosOver: " . json_encode($dadosPolidos, JSON_UNESCAPED_UNICODE));

                    if (isset($dadosPolidos[0]['erro_tecnico'])) {
                        error_log("OverpassControleEntorno: erro_tecnico -> " . $dadosPolidos[0]['erro_tecnico']);
                    }
                    
                    $idsInseridos = [];
                    foreach ($dadosPolidos as $dados) {
                    $pontosinter = PontosInteresse::criarDeArray($dados);
                    if (
                                isset($dados['lat'], $dados['lon']) &&
                                is_numeric($dados['lat']) &&
                                is_numeric($dados['lon'])
                            ) {
                                $coord = new Coordenada((float)$dados['lat'], (float)$dados['lon']);
                                $pontosinter->setIndiceEspacialOverpass($coord);
                            }

                            $pontosinter->setEstado($estado);
                            if ($pontosinter->isPersistivel()) {
                            $id =$this->repo->inserirPontosInteresse($pontosinter);
                                if ($id > 0) {
                                $idsInseridos[] = $id;
                            }
                    }
                }
                
                $atualizarValidadeResultado = $this->pes->atualizarValidade($pesquisa);

                if(!$atualizarValidadeResultado){
                     error_log("Erro ao atualizar a Validade após refazer a requisição para o overpass entorno.");
                    }

                if($overpassStatusEntorno === 'NULO' &&  (!empty($idsInseridos) || $quantidadeEntorno >= 1)){
                        $STATUS ="VALIDO";
                        $pesquisa->setOverpassStatusEntorno($STATUS);
                        $atualizarOverpassStatusEntornoResultado2=$this->pes-> atualizarOverpassStatusEntorno($pesquisa); 

                        if(!$atualizarOverpassStatusEntornoResultado2){
                                $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Entorno.";
                                header("Location: index.php?rota=telaPrincipal");
                                exit;
                        }

            $listarPontosNoEntornoResultado3 = $this->repo->listarPontosNoEntorno($pontosinter, $idPontoInter);

            if(!$listarPontosNoEntornoResultado3){
                    $_SESSION['pesquisa_erros'][] = "Erro ao listar os dados do entorno.";
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
            }

              $features = [];

                        $features[] = [
                            "type" => "Feature",
                            "bbox" => [
                                $bounds[0][0], 
                                $bounds[0][1], 
                                $bounds[1][0], 
                                $bounds[1][1]  
                            ],
                            "properties" => [
                                "id"   => $idPontoInter,
                                "Nome" => $nomeAlvo,
                                "Tipo" => "Alvo"
                            ],
                            "geometry" => [
                                "type" => "Point",
                                "coordinates" => [$lon, $lat] 
                            ]
                        ];

                        foreach ($listarPontosNoEntornoResultado3 as $pe) {
                            $features[] = [
                                "type" => "Feature",
                                "properties" => [
                                    "id"   => $pe->getId(),
                                    "Nome" => $pe->getNome(),
                                    "Tipo" => "Entorno"
                                ],
                                "geometry" => [
                                    "type" => "Point",
                                    "coordinates" => [
                                        $pe->getIndiceEspacialOverpass()?->getLng(),
                                        $pe->getIndiceEspacialOverpass()?->getLat()
                                    ]
                                ]
                            ];
                        }

                        $resposta = json_encode([
                            "type" => "FeatureCollection",
                            "features" => $features
                        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;

                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;


                        }else if ($overpassStatusEntorno === 'VALIDO'){

                    $listarPontosNoEntornoResultado4 = $this->repo->listarPontosNoEntorno($pontosinter, $idPontoInter);

                    if(!$listarPontosNoEntornoResultado4){
                                    $_SESSION['pesquisa_erros'][] = "Erro ao listar os dados do entorno.";
                                    header("Location: index.php?rota=telaPrincipal");
                                    exit;
                            }

             $features = [];

                        $features[] = [
                            "type" => "Feature",
                            "bbox" => [
                                $bounds[0][0], 
                                $bounds[0][1], 
                                $bounds[1][0], 
                                $bounds[1][1]  
                            ],
                            "properties" => [
                                "id"   => $idPontoInter,
                                "Nome" => $nomeAlvo,
                                "Tipo" => "Alvo"
                            ],
                            "geometry" => [
                                "type" => "Point",
                                "coordinates" => [$lon, $lat] 
                            ]
                        ];

                        foreach ($listarPontosNoEntornoResultado4 as $pe) {
                            $features[] = [
                                "type" => "Feature",
                                "properties" => [
                                    "id"   => $pe->getId(),
                                    "Nome" => $pe->getNome(),
                                    "Tipo" => "Entorno"
                                ],
                                "geometry" => [
                                    "type" => "Point",
                                    "coordinates" => [
                                        $pe->getIndiceEspacialOverpass()?->getLng(),
                                        $pe->getIndiceEspacialOverpass()?->getLat()
                                    ]
                                ]
                            ];
                        }

                        $resposta = json_encode([
                            "type" => "FeatureCollection",
                            "features" => $features
                        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;

                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;

                        }else if ($overpassStatusEntorno === 'NULO'){

                        $features = [];

                    $features[] = [
                        "type" => "Feature",
                        "bbox" => [
                            $bounds[0][0], 
                            $bounds[0][1], 
                            $bounds[1][0], 
                            $bounds[1][1]  
                        ],
                        "properties" => [
                            "id"   => $idPontoInter,
                            "Nome" => $nomeAlvo,
                            "Tipo" => "Alvo"
                        ],
                        "geometry" => [
                            "type" => "Point",
                            "coordinates" => [$lon, $lat] 
                        ]
                    ];

                    $resposta = json_encode([
                        "type" => "FeatureCollection",
                        "features" => $features
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;
                        $_SESSION['flash_erro'] = "Infelizmente Não Conseguimos Encontrar Sugestões no Entorno do Ponto Pesquisado";
                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;
                        }
                    

                        } catch (\RuntimeException $e) {
                    $_SESSION['pesquisa_erros'][] = "Erro de Configuração: " . $e->getMessage();
                    header("Location: index.php?rota=telaPrincipal");
                    exit;

                    } catch (\InvalidArgumentException $e) {
                        $_SESSION['pesquisa_erros'][] = "Erro de processamento da resposta do Overpass Entorno: " . $e->getMessage();
                        header("Location: index.php?rota=telaPrincipal");
                        exit;

                    } catch (\Exception $e) {
                    $_SESSION['pesquisa_erros'][] = "Erro inesperado: " . $e->getMessage();
                    header("Location: index.php?rota=telaPrincipal");
                    exit;
                    }
                }
                
               }else{


                     try {    
                $jsonBruto = OverpassRequisicaoEntorno::requisicaoOverpassEntorno(
                $lat,
                $lon,
                $quantidadeRequisição
                );

            $dadosPolidos = OverpassServico::extrairDadosOver($jsonBruto);
            error_log("extrairDadosOver: " . json_encode($dadosPolidos, JSON_UNESCAPED_UNICODE));

            if (isset($dadosPolidos[0]['erro_tecnico'])) {
                error_log("OverpassControleEntorno: erro_tecnico -> " . $dadosPolidos[0]['erro_tecnico']);
            }
            
            $idsInseridos = [];
            foreach ($dadosPolidos as $dados) {
            $pontosinter = PontosInteresse::criarDeArray($dados);
            if (
                        isset($dados['lat'], $dados['lon']) &&
                        is_numeric($dados['lat']) &&
                        is_numeric($dados['lon'])
                    ) {
                        $coord = new Coordenada((float)$dados['lat'], (float)$dados['lon']);
                        $pontosinter->setIndiceEspacialOverpass($coord);
                    }

                    $pontosinter->setEstado($estado);
                    if ($pontosinter->isPersistivel()) {
                       $id =$this->repo->inserirPontosInteresse($pontosinter);
                        if ($id > 0) {
                            $idsInseridos[] = $id;
                        }
            }
        }
          
        $atualizarValidadeResultado = $this->pes->atualizarValidade($pesquisa);

        if(!$atualizarValidadeResultado){
               error_log("Erro ao atualizar a Validade apos fazer a requisicao para o overpass entorno.");
            }

         if (!empty($idsInseridos) || $quantidadeEntorno >= 1) {
                        $STATUS ="VALIDO";
                        $pesquisa->setOverpassStatusEntorno($STATUS);
                        $atualizarOverpassStatusEntornoResultado3=$this->pes-> atualizarOverpassStatusEntorno($pesquisa); 

                        if(!$atualizarOverpassStatusEntornoResultado3){
                        $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Entorno.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                }

                 $listarPontosNoEntornoResultado5 = $this->repo->listarPontosNoEntorno($pontosinter, $idPontoInter);

                    if(!$listarPontosNoEntornoResultado5){
                                    $_SESSION['pesquisa_erros'][] = "Erro ao listar os dados do entorno.";
                                    header("Location: index.php?rota=telaPrincipal");
                                    exit;
                            }
                        
                         $features = [];

                        $features[] = [
                            "type" => "Feature",
                            "bbox" => [
                                $bounds[0][0], 
                                $bounds[0][1], 
                                $bounds[1][0], 
                                $bounds[1][1]  
                            ],
                            "properties" => [
                                "id"   => $idPontoInter,
                                "Nome" => $nomeAlvo,
                                "Tipo" => "Alvo"
                            ],
                            "geometry" => [
                                "type" => "Point",
                                "coordinates" => [$lon, $lat] 
                            ]
                        ];

                        foreach ($listarPontosNoEntornoResultado5 as $pe) {
                            $features[] = [
                                "type" => "Feature",
                                "properties" => [
                                    "id"   => $pe->getId(),
                                    "Nome" => $pe->getNome(),
                                    "Tipo" => "Entorno"
                                ],
                                "geometry" => [
                                    "type" => "Point",
                                    "coordinates" => [
                                        $pe->getIndiceEspacialOverpass()?->getLng(),
                                        $pe->getIndiceEspacialOverpass()?->getLat()
                                    ]
                                ]
                            ];
                        }

                        $resposta = json_encode([
                            "type" => "FeatureCollection",
                            "features" => $features
                        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;

                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;


            }else{
                 $STATUS ="NULO";
                $pesquisa->setOverpassStatusEntorno($STATUS);
                $atualizarOverpassStatusEntornoResultado4=$this->pes-> atualizarOverpassStatusEntorno($pesquisa); 

                if(!$atualizarOverpassStatusEntornoResultado4){
                        $_SESSION['pesquisa_erros'][] = "Erro ao atualizar o Status Overpass do Entorno.";
                        header("Location: index.php?rota=telaPrincipal");
                        exit;
                }

                $features = [];

                    $features[] = [
                        "type" => "Feature",
                        "bbox" => [
                            $bounds[0][0], 
                            $bounds[0][1], 
                            $bounds[1][0], 
                            $bounds[1][1]  
                        ],
                        "properties" => [
                            "id"   => $idPontoInter,
                            "Nome" => $nomeAlvo,
                            "Tipo" => "Alvo"
                        ],
                        "geometry" => [
                            "type" => "Point",
                            "coordinates" => [$lon, $lat] 
                        ]
                    ];

                    $resposta = json_encode([
                        "type" => "FeatureCollection",
                        "features" => $features
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                        $_SESSION['geojson_telaMapa'] = $resposta;
                        $_SESSION['flash_erro'] = "Infelizmente Não Conseguimos Encontrar Sugestões no Entorno do Ponto Pesquisado";
                        header("Location: index.php?rota=telaPrincipal&secao=telaMapa");
                        exit;

            }
            

                 } catch (\RuntimeException $e) {
            $_SESSION['pesquisa_erros'][] = "Erro de Configuração: " . $e->getMessage();
            header("Location: index.php?rota=telaPrincipal");
            exit;

            } catch (\InvalidArgumentException $e) {
                $_SESSION['pesquisa_erros'][] = "Erro de processamento da resposta do Overpass Entorno: " . $e->getMessage();
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
    
}
?>