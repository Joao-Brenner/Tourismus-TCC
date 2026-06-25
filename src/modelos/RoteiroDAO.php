<?php
namespace PADS\App\modelos;

use PADS\App\configuracao\Conexao;
use PADS\App\entidades\Roteiro;
use PADS\App\entidades\Coordenada;
use PADS\App\entidades\PontosInteresse;
use PDO;
use PDOException;

class RoteiroDAO
{
    public function inserirRoteiro(Roteiro $roteiro, array $pontos): ?int
    {
        try {
            $pdo = Conexao::conectar();
            $pdo->beginTransaction();

            if ($this->verificarRoteiro($pdo, $roteiro)) {
                $pdo->rollBack();
                return -1; 
            }


            $sql = "INSERT INTO Roteiro (
                        titulo,
                        codigo,
                        centro,
                        id_usuario
                    ) VALUES (
                        ?, ?, ST_GeomFromText(?, 4326), ?
                    )";

            $stmt = $pdo->prepare($sql);
            $ok = $stmt->execute([
                $roteiro->getTitulo(),
                $roteiro->getCodigo(),
                $roteiro->getCentroWKT(),
                $roteiro->getIdUsuario()
            ]);

            if (!$ok) {
                $pdo->rollBack();
                return null;
            }

            $idRoteiro = (int)$pdo->lastInsertId();
            $roteiro->setIdRoteiro($idRoteiro);

            foreach ($pontos as $p) {
                $rPoi = new Roteiro();
                $rPoi->setIdRoteiro($idRoteiro);
                $rPoi->setIdPoi((int)$p['id_poi']);
                $rPoi->setDia(new \DateTime($p['dia']));
                $rPoi->setEntrada(new \DateTime($p['entrada']));
                $rPoi->setSaida(new \DateTime($p['saida']));
                $rPoi->setObservacoes($p['observacoes'] ?? null);

                $okPoi = $this->inserirRoteiroPoi($pdo, $rPoi);
                if ($okPoi === false) {
                    $pdo->rollBack();
                    return null;
                }
            }

            $pdo->commit();
            return $idRoteiro;

        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro ao cadastrar roteiro: " . $e->getMessage());
            return null;
        }
    }

    public function listarRoteiro(int $idUsuario): ?array
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
                id AS id_roteiro,
                titulo,
                data_r,
                codigo,
                ST_AsText(centro) AS centro
                FROM Roteiro 
            WHERE id_usuario = ?
            ORDER BY data_r DESC, id DESC;
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idUsuario]); 

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo->commit();


        $roteiros = [];
        foreach ($rows as $dados) {
            $roteiro = Roteiro::criarDeArrayRoteiro($dados);
            $roteiros[] = $roteiro;
        }


        return $roteiros;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao listar Roteiros por usuario: " . $e->getMessage());
        return null;
    }

}

public function listarEditarRoteiro(int $idRoteiro): ?array
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "
            SELECT 
                rp.id AS id_roteiro_poi,
                rp.id_poi,
                rp.dia,
                rp.entrada,
                rp.saida,
                rp.observacoes,
                pi.id,
                pi.nome,
                pi.estado,
                pi.endereco,
                pi.horario_funcionamento,
                pi.telefone,
                pi.email,
                pi.website,
                ST_AsText(pi.indice_espacial_overpass) AS indice_espacial_overpass
            FROM Roteiro_POI rp
            INNER JOIN Pontos_Interesse pi ON rp.id_poi = pi.id
            WHERE rp.id_roteiro = ?
            ORDER BY rp.dia, rp.entrada;
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idRoteiro]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo->commit();

        $resultado = [];
        foreach ($rows as $dados) {
            $rp = Roteiro::criarDeArrayRoteiroPoi($dados);
            $pi = PontosInteresse::criarDeArray($dados);

            $resultado[] = [
                'roteiroPoi' => $rp,
                'pontoInteresse' => $pi
            ];
        }

        return $resultado;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao listar dados para edicao de roteiro: " . $e->getMessage());
        return null;
    }
}

public function listarRoteiroEntorno(Coordenada $centro, array $idsPoiIgnorar): ?array
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($idsPoiIgnorar), '?'));

        $sql = "
            SELECT 
                id,
                nome,
                ST_AsText(indice_espacial_overpass) AS indice_espacial_overpass
            FROM Pontos_Interesse
            WHERE ST_Distance_Sphere(
                      indice_espacial_overpass,
                      ST_GeomFromText(?, 4326)
                  ) <= 2275
              " . (!empty($idsPoiIgnorar) ? "AND id NOT IN ($placeholders)" : "") . "
        ";

        $params = [$centro->toWKT()];
        if (!empty($idsPoiIgnorar)) {
            $params = array_merge($params, $idsPoiIgnorar);
        }

        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute($params);

        if (!$ok) {
            $pdo->rollBack();
            return null;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdo->commit();

        $resultado = [];
        foreach ($rows as $dados) {
            $pi = PontosInteresse::criarDeArray($dados);
            $resultado[] = $pi;
        }

        return $resultado;

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao listar pontos no entorno do roteiro: " . $e->getMessage());
        return null;
    }
}



public function excluirRoteiro(Roteiro $roteiro): bool
{
        if ($roteiro->getIdRoteiro() <= 0 || $roteiro->getIdUsuario() <= 0 ) {
            return false;
        }

    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "DELETE FROM Roteiro
                WHERE id = ? AND id_usuario= ? ";
        $stmt = $pdo->prepare($sql);

        $ok =  $stmt->execute([
            $roteiro->getIdRoteiro(),
            $roteiro->getIdUsuario(),
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
        error_log("Erro ao excluir roteiro: " . $e->getMessage());
        return false;
    }
}

    public function atualizarTituloRoteiro(int $idRoteiro, string $novoTitulo): bool
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        if ($this->tituloMudou($pdo, $idRoteiro, $novoTitulo)) {
            $sqlUpdate = "UPDATE Roteiro SET titulo = ? WHERE id = ?";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $ok = $stmtUpdate->execute([$novoTitulo, $idRoteiro]);

            if (!$ok || $stmtUpdate->rowCount() === 0) {
                $pdo->rollBack();
                return false;
            }
            
        if (!$this->atualizarDataRoteiro($pdo, $idRoteiro)) {
            $pdo->rollBack();
            return false;
        }
        }

    
        $pdo->commit();
        return true;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar titulo do roteiro {$idRoteiro}: " . $e->getMessage());
        return false;
    }
}

public function excluirOcorrencias(int $idRoteiro, array $idsRemover): bool {
    try {

        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "DELETE FROM Roteiro_POI WHERE id = ? AND id_roteiro = ?";
        $stmt = $pdo->prepare($sql);
        foreach ($idsRemover as $idPoi) {
            $ok = $stmt->execute([$idPoi, $idRoteiro]);
            if (!$ok || $stmt->rowCount() === 0) {
                $pdo->rollBack();
                return false;
            }
        }

        if (!$this->atualizarDataRoteiro($pdo, $idRoteiro)) {
            $pdo->rollBack();
            return false;
        }


        $pdo->commit();
        return true;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Erro ao excluir ocorrencias: " . $e->getMessage());
        return false;
    }
}

public function atualizarOcorrencias(int $idRoteiro, array $ocorrenciasEditadas): bool {
    try {

        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "UPDATE Roteiro_POI
                   SET dia = ?, entrada = ?, saida = ?, observacoes = ?
                 WHERE id = ? AND id_roteiro = ?";
        $stmt = $pdo->prepare($sql);
        foreach ($ocorrenciasEditadas as $dados) {
            $r = Roteiro::criarDeArrayRoteiroPoi($dados);
            $ok =$stmt->execute([
                $r->getDia()->format('Y-m-d'),
                $r->getEntrada()->format('H:i:s'),
                $r->getSaida()->format('H:i:s'),
                $r->getObservacoes(),
                $r->getIdRoteiroPoi(),
                $idRoteiro
            ]);
            if (!$ok || $stmt->rowCount() === 0) {
                $pdo->rollBack();
                return false;
            }
        }

        if (!$this->atualizarDataRoteiro($pdo, $idRoteiro)) {
            $pdo->rollBack();
            return false;
        }


        $pdo->commit();
        return true;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Erro ao atualizar ocorrencias: " . $e->getMessage());
        return false;
    }
}

public function inserirNovasOcorrencias( int $idRoteiro, array $ocorrenciasNovas): bool {
    try {

        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "INSERT INTO Roteiro_POI (id_roteiro, id_poi, dia, entrada, saida, observacoes)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        foreach ($ocorrenciasNovas as $dados) {
            $r = Roteiro::criarDeArrayRoteiroPoi($dados);
            $ok = $stmt->execute([
                $idRoteiro,
                $r->getIdPoi(),
                $r->getDia()->format('Y-m-d'),
                $r->getEntrada()->format('H:i:s'),
                $r->getSaida()->format('H:i:s'),
                $r->getObservacoes()
            ]);
            if (!$ok) {
                $pdo->rollBack();
                return false;
            }
        }

        if (!$this->atualizarDataRoteiro($pdo, $idRoteiro)) {
            $pdo->rollBack();
            return false;
        }


        $pdo->commit();
        return true;
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Erro ao inserir novas ocorrencias: " . $e->getMessage());
        return false;
    }
}

    private function inserirRoteiroPoi(PDO $pdo, Roteiro $roteiro): bool
    {
        $sql = "INSERT INTO Roteiro_POI (
                    id_roteiro,
                    id_poi,
                    dia,
                    entrada,
                    saida,
                    observacoes
                ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            $roteiro->getIdRoteiro(),
            $roteiro->getIdPoi(),
            $roteiro->getDia()->format('Y-m-d'),
            $roteiro->getEntrada()->format('H:i:s'),
            $roteiro->getSaida()->format('H:i:s'),
            $roteiro->getObservacoes()
        ]);
    }

private function verificarRoteiro(PDO $pdo, Roteiro $roteiro): bool
{
    $sql = "SELECT 1
              FROM Roteiro
             WHERE id_usuario = ?
               AND (titulo = ? AND codigo = ?)
             LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $roteiro->getIdUsuario(),
        $roteiro->getTitulo(),
        $roteiro->getCodigo()
    ]);

    return $stmt->fetchColumn() !== false;
}

private function tituloMudou(PDO $pdo, int $idRoteiro, string $novoTitulo): bool
{
    $sql = "SELECT titulo FROM Roteiro WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idRoteiro]);
    $tituloAtual = $stmt->fetchColumn();

    return $tituloAtual !== false && $tituloAtual !== $novoTitulo;
}


private function atualizarDataRoteiro(PDO $pdo, int $idRoteiro): bool
{
    $sql = "UPDATE Roteiro 
               SET data_r = NOW() 
             WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$idRoteiro]);
}


}

