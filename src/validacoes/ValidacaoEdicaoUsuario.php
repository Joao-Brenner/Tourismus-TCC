<?php
namespace PADS\App\validacoes;

use PADS\App\entidades\Usuario;
use PADS\App\modelos\UsuarioDAO;

class ValidacaoEdicaoUsuario
{
    public static function validar(array $dados): array
    {
        $erros = [];

        $idUsuario = (int)($dados['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            $erros[] = "Usuário inválido para edição.";
            return $erros;
        }

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
                $usuarioTemp->setId((int)$dados['id_usuario']); 
                $usuarioTemp->setEmail($email);

                $dao = new UsuarioDAO();
                if ($dao->existeEmailParaOutroUsuario($usuarioTemp)) {
                    $erros[] = "E-mail já cadastrado por outro usuário. Escolha outro.";
                }
            }

            return $erros;

    }
}
