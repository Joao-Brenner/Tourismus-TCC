<?php
namespace PADS\App\validacoes;

use PADS\App\entidades\Usuario;
use PADS\App\modelos\UsuarioDAO;


class ValidacaoCadastro
{
    public static function validar(array $dados): array
    {
        $erros = [];

        $nome = trim($dados['nome'] ?? '');
        if ($nome === '') {
            $erros[] = "O nome não pode estar vazio.";
        } elseif (strlen($nome) < 3 || !preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿçÇ\s]{3,}$/u', $nome)) {
            $erros[] = "Nome inválido. Use apenas letras (com acentos/ç) e espaços, mínimo 3 caracteres.";
        }

        $email = trim($dados['email'] ?? '');
        if ($email === '') {
            $erros[] = "O e-mail não pode estar vazio.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "E-mail inválido. Ex.: usuario@dominio.com";
        } else {
            $usuarioTemp = new Usuario();
            $usuarioTemp->setEmail($email);

            $dao = new UsuarioDAO();
            if ($dao->existeEmail($usuarioTemp)) {
                $erros[] = "E-mail já cadastrado. Escolha outro.";
            }
        }


        $senha = $dados['senha'] ?? '';
        if ($senha === '') {
            $erros[] = "A senha não pode estar vazia.";
        } else {
            if (!preg_match('/.{8,}/', $senha)) {
                $erros[] = "Senha: mínimo 8 caracteres.";
            }
            if (!preg_match('/[A-Z]/', $senha)) {
                $erros[] = "Senha: ao menos uma letra maiúscula.";
            }
            if (!preg_match('/[a-z]/', $senha)) {
                $erros[] = "Senha: ao menos uma letra minúscula.";
            }
            if (!preg_match('/\d/', $senha)) {
                $erros[] = "Senha: ao menos um número.";
            }
            if (!preg_match('/[^a-zA-Z0-9]/', $senha)) {
            $erros[] = "Senha: ao menos um caractere especial.";
            }

            if (preg_match('/\p{Extended_Pictographic}/u', $senha)) {
            $erros[] = "Senha não pode conter emojis.";
        }
        }

        return $erros;
    }
}
