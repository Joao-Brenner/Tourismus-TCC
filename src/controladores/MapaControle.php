<?php
namespace PADS\App\controladores;

use PADS\App\entidades\PontosInteresse;
use PADS\App\modelos\PontosInteresseDAO;

class MapaControle
{
    private PontosInteresseDAO $repo;

    public function __construct() {
        $this->repo = new PontosInteresseDAO();
    }

    public function processarMapa(array $params = []): void
    {
        
        $id = (int)($params['id'] ?? 0);
        
         if (!$id){
        echo json_encode([
                'success' => false,
                'mensagem' => 'Falha ao obter parâmetros para os Detalhes'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit; 
         } 

        $pi = new PontosInteresse();
        $pi->setId($id);

        $detalhes = $this->repo->buscarDetalhePorId($pi);

        header('Content-Type: application/json; charset=utf-8');

        if ($detalhes) {
            echo json_encode([
                'success' => true,
                'dados' => [
                    'osmId' => $detalhes->getOsmId(),
                    'osmType' => $detalhes->getOsmType(),
                    'estado' => $detalhes->getEstado(),
                    'email' => $detalhes->getEmail(),
                    'telefone' => $detalhes->getTelefone(),
                    'website' => $detalhes->getWebsite(),
                    'endereco' => $detalhes->getEndereco(),
                    'horarioFuncionamento' => $detalhes->getHorarioFuncionamento()
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'mensagem' => 'POI não encontrado'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
    }
}
