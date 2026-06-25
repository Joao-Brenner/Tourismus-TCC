<?php

namespace PADS\App\controladores;

use PADS\App\modelos\PesquisaDAO;
use PADS\App\entidades\Historico;
use PADS\App\entidades\Pesquisa;
use PADS\App\entidades\Coordenada;

class HistoricoControle
{
    private PesquisaDAO $repo;

    public function __construct(PesquisaDAO $repo)
    {
        $this->repo = $repo;
    }

    public function processarHistorico(string $acao, array $params = []): void
    {
        switch ($acao) {
            case 'listarPorUsuario':
                $idUsuario = (int)($params['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));
                if ($idUsuario <= 0) {
                    $_SESSION['flash_erro'] = "Erro em reconher o Usuário.  Tente de novo.";
                    header("Location: index.php?rota=telaPrincipal&secao=historicoUsuario");
                    exit;
                }

                $historicos = $this->repo->listarPorUsuario($idUsuario);
                $_SESSION['historicos_usuario'] = array_map(function(Historico $h) {
                    $p = $h->getPesquisa(); 
                    return [
                        'id' => $p->getId(),
                        'pesquisa_original' => $p->getPesquisaOriginal(),
                        'pesquisa_normalizada' => $p->getPesquisaNormalizada(),
                        'estado_normalizado' => $p->getEstadoNormalizado(),
                        'query_hash' => $p->getQueryHash(),
                        'osm_id' => $p->getOsmId(),
                        'osm_type' => $p->getOsmType(),
                        'nominatim_status' => $p->getNominatimStatus(),
                        'overpass_status_alvo' => $p->getOverpassStatusAlvo(),
                        'overpass_status_entorno' => $p->getOverpassStatusEntorno(),
                        'lat' => $p->getIndiceEspacialNominatim()?->getLat(),
                        'lon' => $p->getIndiceEspacialNominatim()?->getLng(),
                        'boundingbox' => $p->getBoundingboxWKT(),
                        'data_pesquisa' => $h->getDataPesquisa()
                            ? $h->getDataPesquisa()->format('Y-m-d H:i:s')
                            : null,
                        'validade' => $p->getValidade()
                            ? $p->getValidade()->format('Y-m-d H:i:s')
                            : null,
                    ];
                }, $historicos);


                header("Location: index.php?rota=telaPrincipal&secao=historicoUsuario");
                exit;
                break;

            case 'excluirHistorico':
                $idPesquisa      = (int)($params['id'] ?? 0);
                $idUsuario          = (int)($params['id_usuario'] ?? ($_SESSION['usuario']['id_usuario'] ?? 0));

                if ($idPesquisa <= 0 || $idUsuario <= 0) {
                    $_SESSION['flash_erro'] = "Dados inválidos para exclusão de histórico.";
                    header("Location: index.php?rota=telaPrincipal&secao=historicoUsuario");
                    exit;
                }

                $historico = new Historico();
                $historico->setIdUsuario($idUsuario);
                $historico->setIdPesquisa($idPesquisa);

                $sucesso = $this->repo->excluirHistorico($historico);

                if ($sucesso) {
                $_SESSION['flash_sucesso'] = "Histórico excluído com sucesso.";
                 header("Location: index.php?rota=historicoUsuario");
                exit;
                } else {
                    $_SESSION['flash_erro'] = "Erro ao tentar excluir histórico!";
                    header("Location: index.php?rota=telaPrincipal&secao=historicoUsuario");
                    exit;
                }
                break;

            default:
                $_SESSION['flash_erro'] = "Ação de histórico inválida.";
                header("Location: index.php?rota=telaPrincipal&secao=historicoUsuario");
                exit;
        }
    }
}
