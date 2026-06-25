<?php

namespace PADS\App\entidades;

class Usuario
{
    private int $id;
    private string $nome;
    private string $email;
    private string $senha;


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getSenha(): string
    {
        return $this->senha;
    }

    public function setSenhaHash(string $senha): void
    {
        $this->senha = password_hash($senha, PASSWORD_DEFAULT);
    }

    public function setSenha(string $senhaHash): void
    {
        $this->senha = $senhaHash;
    }

    public function verificarSenha(string $senhaDigitada): bool
    {
        return password_verify($senhaDigitada, $this->senha);
    }

    public static function confirmarNovaSenha(string $novaSenha, string $confirmarSenha): bool
    {
        return $novaSenha === $confirmarSenha;
    }

    public function isValido(): bool
    {
        return !empty($this->senha) && !empty($this->email);
    }

    public static function criarDeArray(array $dados): Usuario {
    $u = new Usuario();
    $u->setId($dados['id'] ?? $dados['id_usuario'] ?? 0);
    $u->setNome($dados['nome'] ?? '');
    $u->setEmail($dados['email'] ?? '');
    if (isset($dados['senha'])) {
        $u->setSenha($dados['senha']);
    }
    return $u;
}

}
?>
