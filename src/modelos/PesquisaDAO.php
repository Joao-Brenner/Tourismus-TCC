<?php

namespace PADS\App\modelos;

use PADS\App\configuracao\Conexao;   
use PADS\App\entidades\Historico;
use PADS\App\entidades\Pesquisa;
use PADS\App\entidades\Coordenada;

use PDO;
use PDOException;

class PesquisaDAO
{

    public function inserirPesquisa(Pesquisa $pesquisa, Historico $historico): ?int
    {
        if (!$pesquisa->isPersistivel()) {
            return null;
        }

        try {
            $pdo = Conexao::conectar();
            $pdo->beginTransaction();

            $sql = "INSERT INTO Pesquisa (
                        pesquisa_original,
                        pesquisa_normalizada,
                        estado_normalizado,
                        query_hash,
                        osm_id,
                        osm_type,
                        indice_espacial_nominatim,
                        boundingbox,
                        validade,
                        nominatim_status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, 
                        ST_GeomFromText(?, 4326), 
                        ST_GeomFromText(?, 4326), 
                        NOW() + INTERVAL 24 HOUR, 
                        ?
                    )";


            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                $pesquisa->getPesquisaOriginal(),
                $pesquisa->getPesquisaNormalizada(),
                $pesquisa->getEstadoNormalizado(),
                $pesquisa->getQueryHash(),
                $pesquisa->getOsmId(),
                $pesquisa->getOsmType(),
                $pesquisa->getIndiceEspacialWKT(),
                $pesquisa->getBoundingboxWKT(),
                $pesquisa->getNominatimStatus(),
            ]);

            if (!$ok) {
                $pdo->rollBack();
                return null;
            }

            $idPesquisa = (int)$pdo->lastInsertId();
            $historico->setIdPesquisa($idPesquisa);
           

            if ($historico->getIdPesquisa() <= 0 || $historico->getIdUsuario() <= 0 ) {
                return null;
            }

            $ok2 =$this->inserirHistorico($pdo, $historico->getIdUsuario(), $historico->getIdPesquisa());
            if ($ok2 === false) {
                $pdo->rollBack();
                return null;
            }

            $pdo->commit();
            return $idPesquisa;

        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro ao cadastrar pesquisa: " . $e->getMessage());
            return null;
        }
    }

    
     public function verificarPesquisa(
    Pesquisa $pesquisa
): ?int {
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "SELECT id
                    FROM Pesquisa
                    WHERE query_hash = ?
                       OR (pesquisa_normalizada = ? AND estado_normalizado = ?)";

        $stmt = $pdo->prepare($sql);
       $stmt->execute([
            $pesquisa->getQueryHash(),
            $pesquisa->getPesquisaNormalizada(),
            $pesquisa->getEstadoNormalizado()
        ]);

        $stmt->execute();
        $idPesquisa = $stmt->fetchColumn();

        if (!$idPesquisa || $idPesquisa <= 0) {
            return null;
        }

        return $idPesquisa;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao verificar pesquisa: " . $e->getMessage());
        return null;
    }
}

public function verificarHistorico(
    Historico $historico
): bool {
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "INSERT INTO Historico (id_usuario, id_pesquisa, data_pesquisa)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE data_pesquisa = NOW()";


        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $historico->getIdUsuario(),
            $historico->getIdPesquisa(),
        ]);

         $pdo->commit();
         return true;


    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao verificar historico: " . $e->getMessage());
        return false;
    }
}

    public function listarPorUsuario(int $idUsuario): array
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
                p.id,
                p.pesquisa_original,
                p.pesquisa_normalizada,
                p.estado_normalizado,
                h.data_pesquisa,
                p.query_hash,
                p.osm_id,
                p.osm_type,
                ST_AsText(p.indice_espacial_nominatim) AS indice_espacial_nominatim,
                ST_AsText(p.boundingbox)               AS boundingbox,
                p.validade,
                p.nominatim_status,
                p.overpass_status_alvo,
                p.overpass_status_entorno
                FROM Pesquisa p
            INNER JOIN Historico h 
                    ON h.id_pesquisa = p.id
            WHERE h.id_usuario = ?
            ORDER BY h.data_pesquisa DESC, p.id DESC;
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idUsuario]); 

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

         $pdo->commit();

        if (!$rows) {
            return [];
        }

        $pesquisas = [];
        foreach ($rows as $dados) {
            $pesquisa = Pesquisa::criarDeArray($dados);

            $historico = new Historico();
            $historico->setDataPesquisa(new \DateTime($dados['data_pesquisa']));
            $historico->setPesquisa($pesquisa);

            $pesquisas[] = $historico;
        }


        return $pesquisas;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao listar historico por usuário: " . $e->getMessage());
        return [];
    }

}

public function verificarNominatimStatus(
    Pesquisa $pesquisa
): ?array {
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "SELECT nominatim_status,
                       (validade > NOW()) AS ainda_valido
                  FROM Pesquisa
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pesquisa->getId(),
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        return [
            'nominatim_status' => $resultado['nominatim_status'],
            'ainda_valido'     => (int)$resultado['ainda_valido'] 
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao verificar status nominatim: " . $e->getMessage());
        return null;
    }
}

public function atualizarPesquisa(Pesquisa $pesquisa): bool
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "UPDATE Pesquisa
                   SET osm_id = ?,
                       osm_type = ?,
                       indice_espacial_nominatim = ST_GeomFromText(?, 4326),
                       boundingbox = ST_GeomFromText(?, 4326),
                       validade = NOW() + INTERVAL 24 HOUR,
                       nominatim_status = ?
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);

        $ok = $stmt->execute([
            $pesquisa->getOsmId(),
            $pesquisa->getOsmType(),
            $pesquisa->getIndiceEspacialWKT(), 
            $pesquisa->getBoundingboxWKT(),    
            $pesquisa->getNominatimStatus(),
            $pesquisa->getId()
        ]);

        if (!$ok) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();    

        return true;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar pesquisa: " . $e->getMessage());
        return false;
    }
}


public function excluirHistorico(Historico $historico): bool
{

        if ($historico->getIdPesquisa() <= 0 && $historico->getIdUsuario() <= 0 ) {
            return false;
        }


    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "DELETE FROM Historico
                WHERE id_usuario = ? AND id_pesquisa=? ";
        $stmt = $pdo->prepare($sql);

        $ok =  $stmt->execute([
            $historico->getIdUsuario(),
            $historico->getIdPesquisa(),
        ]);

        if (!$ok || $stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;

    } catch (\PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao excluir pesquisa: " . $e->getMessage());
        return false;
    }
}

public function verificarOverpassStatusAlvo(
    Pesquisa $pesquisa
): ?array {
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "SELECT overpass_status_alvo,
                       (validade > NOW()) AS ainda_valido
                  FROM Pesquisa
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pesquisa->getId(),
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        return [
            'overpass_status_alvo' => $resultado['overpass_status_alvo'],
            'ainda_valido'     => (int)$resultado['ainda_valido'] 
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao verificar status da requisicao alvo do overpass: " . $e->getMessage());
        return null;
    }
}

 public function listarParaOverpass(Pesquisa $pesquisa): ?Pesquisa
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
                estado_normalizado,
                osm_id,
                osm_type
            FROM Pesquisa 
            WHERE id = ? ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$pesquisa->getId()]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        $pesquisa->setEstadoNormalizado($resultado['estado_normalizado']);
        $pesquisa->setOsmId((int)$resultado['osm_id']);
        $pesquisa->setOsmType($resultado['osm_type']);

        return $pesquisa;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao listar pesquisa para trazer os dados importantes para o overpass: " . $e->getMessage());
        return null;
    }

}

public function atualizarOverpassStatusAlvo(Pesquisa $pesquisa): bool
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "UPDATE Pesquisa
                   SET overpass_status_alvo=  ?
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);

        $ok = $stmt->execute([
            $pesquisa->getOverpassStatusAlvo(),
            $pesquisa->getId()
        ]);

        if (!$ok) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();    

        return true;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar o overpass status alvo da pesquisa: " . $e->getMessage());
        return false;
    }
}

public function verificarOverpassStatusEntorno(
    Pesquisa $pesquisa
): ?array {
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "SELECT overpass_status_entorno,
                       (validade > NOW()) AS ainda_valido
                  FROM Pesquisa
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $pesquisa->getId(),
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        return [
            'overpass_status_entorno' => $resultado['overpass_status_entorno'],
            'ainda_valido'     => (int)$resultado['ainda_valido'] 
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao verificar status da requisicao entorno do overpass: " . $e->getMessage());
        return null;
    }
}

public function atualizarOverpassStatusEntorno(Pesquisa $pesquisa): bool
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "UPDATE Pesquisa
                   SET overpass_status_entorno=  ?
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);

        $ok = $stmt->execute([
            $pesquisa->getOverpassStatusEntorno(),
            $pesquisa->getId()
        ]);

        if (!$ok) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();    

        return true;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar overpass status entorno da pesquisa: " . $e->getMessage());
        return false;
    }
}

public function atualizarValidade(Pesquisa $pesquisa): bool
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "UPDATE Pesquisa
                   SET 
                       validade = NOW() + INTERVAL 24 HOUR
                 WHERE id = ?";

        $stmt = $pdo->prepare($sql);

        $ok = $stmt->execute([
            $pesquisa->getId()
        ]);

        if (!$ok) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();    

        return true;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar validade: " . $e->getMessage());
        return false;
    }
}

public function listarParaRoteiro(int $idPesquisa): ?string
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT ST_AsText(indice_espacial_nominatim) AS indice_espacial_nominatim
            FROM Pesquisa
            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idPesquisa]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$resultado) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();

        return $resultado['indice_espacial_nominatim'];

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao buscar indice espacial para roteiro: " . $e->getMessage());
        return null;
    }
}

    private function inserirHistorico(PDO $pdo, int $idUsuario, int $idPesquisa): bool
    {

    $sql = "INSERT INTO Historico (id_usuario, id_pesquisa) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);

            if (!$stmt->execute([$idUsuario, $idPesquisa])) {
                return false; 
            }

    return true;
    }


}

