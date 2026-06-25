<?php

namespace PADS\App\modelos;

use PADS\App\configuracao\Conexao;
use PADS\App\entidades\Usuario;
use PDO;
use PDOException;

class UsuarioDAO
{
  public function cadastrarUsuario(Usuario $usuario): ?int
{
    if (!$usuario->isValido()) {
        return null;
    }

    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "INSERT INTO Usuario (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $usuario->getNome(), PDO::PARAM_STR);
        $stmt->bindValue(2, $usuario->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(3, $usuario->getSenha(), PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $pdo->rollBack();
            return null;
        }

        $idUsuario = (int)$pdo->lastInsertId();

        $pdo->commit();
        return $idUsuario;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro no Cadastro: " . $e->getMessage());
        return null;
    }
}


public function existeEmail(Usuario $usuario): bool
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "SELECT COUNT(*) FROM Usuario WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $usuario->getEmail(), PDO::PARAM_STR);
        $stmt->execute();

        $count = (int)$stmt->fetchColumn();

        $pdo->commit();

        return $count > 0;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao Verificar Email: " . $e->getMessage());
        return false;
    }
}

public function existeEmailParaOutroUsuario(Usuario $usuario): bool
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "SELECT COUNT(*) 
                  FROM Usuario 
                 WHERE email = ? 
                   AND id <> ?";   
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $usuario->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(2, $usuario->getId(), PDO::PARAM_INT);
        $stmt->execute();

        $count = (int)$stmt->fetchColumn();

        $pdo->commit();

        return $count > 0;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao Verificar Email: " . $e->getMessage());
        return false;
    }
}


public function listarUsuarioPorId(int $id): ?Usuario
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "SELECT * FROM Usuario WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $id, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $pdo->rollBack();
            return null;
        }

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            $pdo->rollBack();
            return null;
        }

        $usuario = Usuario::criarDeArray($dados);

        $pdo->commit();
        return $usuario;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao buscar usuario: " . $e->getMessage());
        return null;
    }
}

public function autenticarUsuario(string $email, string $senhaDigitada): ?Usuario
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "SELECT * FROM Usuario WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $email, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            $pdo->rollBack();
            return null;
        }

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            $pdo->rollBack();
            return null;
        }

        $usuario = Usuario::criarDeArray($dados);

        if (!$usuario->verificarSenha($senhaDigitada)) {
            $pdo->rollBack();
            return null;
        }

        if (!$usuario->isValido()) {
            $pdo->rollBack();
            return null;
        }

        $pdo->commit();
        return $usuario;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro no login: " . $e->getMessage());
        return null;
    }
}


public function excluirUsuario(int $idUsuario): bool
{
    try {
        $pdo = Conexao::conectar();
        $pdo->beginTransaction();

        $sql = "DELETE FROM Usuario WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $idUsuario, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro na Exclusao: " . $e->getMessage());
        return false;
    }
}

public function atualizarPerfil(Usuario $usuario): bool
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE Usuario 
                   SET nome = ?, 
                       email = ? 
                 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $usuario->getNome(), PDO::PARAM_STR);
        $stmt->bindValue(2, $usuario->getEmail(), PDO::PARAM_STR);
        $stmt->bindValue(3, $usuario->getId(), PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar perfil: " . $e->getMessage());
        return false;
    }
}

public function verificarSenhaAtual(int $idUsuario, string $senhaDigitada): bool
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "SELECT senha FROM Usuario WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        if (!$dados) {
            return false;
        }

        $usuario = new Usuario();
        $usuario->setId($idUsuario);
        $usuario->setSenha($dados['senha']); 

        return $usuario->verificarSenha($senhaDigitada);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao verificar senha atual: " . $e->getMessage());
        return false;
    }
}

public function atualizarSenha(int $idUsuario, string $novaSenhaHash): bool
{
    $pdo = Conexao::conectar();

    try {
        $pdo->beginTransaction();

        $sql = "UPDATE Usuario SET senha = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $novaSenhaHash, PDO::PARAM_STR);
        $stmt->bindValue(2, $idUsuario, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            $pdo->rollBack();
            return false;
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao atualizar senha: " . $e->getMessage());
        return false;
    }
}


}