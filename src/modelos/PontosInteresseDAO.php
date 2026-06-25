<?php

namespace PADS\App\modelos;

use PADS\App\configuracao\Conexao;
use PADS\App\entidades\PontosInteresse;
use PADS\App\entidades\Coordenada;
use PADS\App\entidades\Pesquisa;
use PDO;
use PDOException; 

class PontosInteresseDAO
{

public function inserirPontosInteresse(PontosInteresse $pontosinter): ?int
    {

        try {
            $pdo = Conexao::conectar();
            $pdo->beginTransaction();

            $sql = "INSERT IGNORE INTO Pontos_Interesse (
                        osm_id,
                        osm_type,
                        nome,
                        estado,
                        email,
                        telefone,
                        website,
                        endereco,
                        horario_funcionamento,
                        indice_espacial_overpass
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                        ST_GeomFromText(?, 4326)
                    )";

            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                $pontosinter->getOsmId(),
                $pontosinter->getOsmType(),
                $pontosinter->getNome(),
                $pontosinter->getEstado(),
                $pontosinter->getEmail(),
                $pontosinter->getTelefone(),
                $pontosinter->getWebsite(),
                $pontosinter->getEndereco(),
                $pontosinter->getHorarioFuncionamento(),
                $pontosinter->getIndiceEspacialOverpassWKT(),
            ]);

            if (!$ok) {
                $pdo->rollBack();
                return null;
            }

            $idPontoInter = (int)$pdo->lastInsertId();
           
            if ($idPontoInter <= 0 ) {
                return null;
            }

            $pdo->commit();
            return $idPontoInter;

        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro ao cadastrar Pontos Interesse: " . $e->getMessage());
            return null;
        }
    }


public function verificarAlvo(PontosInteresse $pontosinter): ?PontosInteresse
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
            id,
            ST_AsText(indice_espacial_overpass) AS indice_espacial_overpass
            FROM Pontos_Interesse 
            WHERE osm_id = ? AND  osm_type= ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pontosinter->getOsmId(),
            $pontosinter->getOsmType(),
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        $pontosinter->setId($resultado['id']);

        if (!empty($resultado['indice_espacial_overpass'])) {
            $pontosinter->setIndiceEspacialOverpass(
                Coordenada::fromWKT($resultado['indice_espacial_overpass'])
            );
        } else {
            $pontosinter->setIndiceEspacialOverpass(null);
        }

        return $pontosinter;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro em verificar se alvo da pesquisa ja existe em Pontos Interesse: " . $e->getMessage());
        return null;
    }

}

public function listarPontosInteresse(PontosInteresse $pontosinter): ?PontosInteresse
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
            ST_AsText(indice_espacial_overpass) AS indice_espacial_overpass
            FROM Pontos_Interesse 
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pontosinter->getId(),
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        if (!empty($resultado['indice_espacial_overpass'])) {
            $pontosinter->setIndiceEspacialOverpass(
                Coordenada::fromWKT($resultado['indice_espacial_overpass'])
            );
        } else {
            $pontosinter->setIndiceEspacialOverpass(null);
        }

        return $pontosinter;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro em listar alvo da pesquisa em Pontos Interesse: " . $e->getMessage());
        return null;
    }

}


public function listarAlvo(PontosInteresse $pontosinter, Pesquisa $pesquisa): ?array
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
                pt.nome,
                ST_AsText(ps.boundingbox) AS boundingbox
            FROM Pontos_Interesse pt
            JOIN Pesquisa ps
            WHERE pt.id = ? AND ps.id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pontosinter->getId(),
            $pesquisa->getId()
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        $pontosinter->setNome($resultado['nome']);

        if (!empty($resultado['boundingbox'])) {
            $pesquisa->setBoundingboxFromWKT($resultado['boundingbox']);
        } else {
            $pesquisa->setBoundingboxFromArray([0.0,0.0,0.0,0.0]);
        }

        return [
            'nome' => $pontosinter->getNome(),
            'boundingbox' => Coordenada::boundingboxFromWKT($resultado['boundingbox'])

        ];


    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro em listar alvo da pesquisa: " . $e->getMessage());
        return null;
    }
}


public function contarPontosNoEntorno(PontosInteresse $pontosinter): ?int
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT COUNT(*) AS total_pontos
            FROM Pontos_Interesse
            WHERE ST_Distance_Sphere(
                      indice_espacial_overpass,
                      ST_GeomFromText(?, 4326)
                  ) <= 2200
              AND id <> ?
        ";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $pontosinter->getIndiceEspacialOverpassWKT(), 
            $pontosinter->getId(),                        
        ]);

        if (!$ok) {
            $pdo->rollBack();
            return null;
        }

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();
        return (int)$resultado['total_pontos'];

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao contar pontos no entorno: " . $e->getMessage());
        return null;
    }
}

public function listarPontosNoEntorno(PontosInteresse $pontosinter, int $idPontoInter): ?array
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT id, nome,  ST_AsText(indice_espacial_overpass) AS indice_espacial_overpass
            FROM Pontos_Interesse
            WHERE ST_Distance_Sphere(
                      indice_espacial_overpass,
                      ST_GeomFromText(?, 4326)
                  ) <= 2200
              AND  id <> ?
        ";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $pontosinter->getIndiceEspacialOverpassWKT(),
            $idPontoInter  
      ]);

        if (!$ok) {
            $pdo->rollBack();
            return null;
        }

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdo->commit();

        if (!$resultados) {
            return null;
        }

        $lista = [];
        foreach ($resultados as $resultado) {
            $pi = new PontosInteresse();
            $pi->setId((int)$resultado['id']);
            $pi->setNome($resultado['nome']);

            if (!empty($resultado['indice_espacial_overpass'])) {
                $pi->setIndiceEspacialOverpass(
                    Coordenada::fromWKT($resultado['indice_espacial_overpass'])
                );
            } else {
                $pi->setIndiceEspacialOverpass(null);
            }

            $lista[] = $pi;
        }

        return $lista;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao listar pontos no entorno: " . $e->getMessage());
        return null;
    }
}

public function buscarDetalhePorId(PontosInteresse $pontosinter): ?PontosInteresse
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT osm_id, osm_type, estado, email, telefone, website, endereco, horario_funcionamento
            FROM Pontos_Interesse
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([$pontosinter->getId()]);

        if (!$ok) {
            $pdo->rollBack();
            return null;
        }

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();

        if (!$resultado) {
            return null;
        }

        $pi = new PontosInteresse();
        $pi->setOsmId((int)$resultado['osm_id']);
        $pi->setOsmType($resultado['osm_type']);
        $pi->setEstado($resultado['estado']);
        $pi->setEmail($resultado['email']);
        $pi->setTelefone($resultado['telefone']);
        $pi->setWebsite($resultado['website']);
        $pi->setEndereco($resultado['endereco']);
        $pi->setHorarioFuncionamento($resultado['horario_funcionamento']);

        return $pi;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao buscar detalhe por id: " . $e->getMessage());
        return null;
    }
}

}