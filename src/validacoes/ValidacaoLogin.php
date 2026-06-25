<?php
namespace PADS\App\validacoes;

use PADS\App\entidades\Usuario;

class ValidacaoLogin
{
    public static function validar(array $dados): array
    {
        $erros = [];

        $email = trim($dados['email'] ?? '');
        if ($email === '') {
            $erros[] = "O e-mail não pode estar vazio.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "E-mail inválido. Ex.: usuario@dominio.com";
        }
        
        $senha = $dados['senha'] ?? '';
        if ($senha === '') {
            $erros[] = "A senha não pode estar vazia.";
        }

        return $erros; 
    }
}
