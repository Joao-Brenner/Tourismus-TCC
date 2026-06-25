<?php

namespace PADS\App\validacoes;

use PADS\App\entidades\Usuario;

class ValidacaoAtualizacaoSenha
{
    public static function validarSenhaAtual(array $dados): array
    {
        $erros = [];

        $id = (int)($dados['id_usuario'] ?? 0);
        if ($id <= 0) {
            $erros[] = "Usuário inválido.";
            return $erros;
        }

        $senhaAtual = isset($dados['senha_atual']) ? trim($dados['senha_atual']) : '';
        if ($senhaAtual === '') {
            $erros[] = "A senha atual não pode estar vazia.";
        }

        return $erros;
    }

    public static function validar(array $dados): array
    {
        $erros = [];

        $id = (int)($dados['id_usuario'] ?? 0);
        if ($id <= 0) {
            $erros[] = "Usuário inválido.";
            return $erros;
        }

        $senhaAtual = isset($dados['senha_atual']) ? trim($dados['senha_atual']) : '';
        if ($senhaAtual === '') {
            $erros[] = "A senha atual não pode estar vazia.";
        }

        $novaSenha = isset($dados['nova_senha']) ? $dados['nova_senha'] : '';
        $novaSenhaTrim = trim($novaSenha);

        if ($novaSenhaTrim === '') {
            $erros[] = "A nova senha não pode estar vazia.";
        } else {
            if (!preg_match('/.{8,}/u', $novaSenha)) {
                $erros[] = "Nova senha: mínimo 8 caracteres.";
            }
            if (!preg_match('/[A-Z]/u', $novaSenha)) {
                $erros[] = "Nova senha: ao menos uma letra maiúscula.";
            }
            if (!preg_match('/[a-z]/u', $novaSenha)) {
                $erros[] = "Nova senha: ao menos uma letra minúscula.";
            }
            if (!preg_match('/\d/u', $novaSenha)) {
                $erros[] = "Nova senha: ao menos um número.";
            }
            if (!preg_match('/[^a-zA-Z0-9]/u', $novaSenha)) {
                $erros[] = "Nova senha: ao menos um caractere especial.";
            }
            if (preg_match('/\p{Extended_Pictographic}/u', $novaSenha)) {
                $erros[] = "Nova senha não pode conter emojis.";
            }
        }

        $confirmarSenha = isset($dados['confirmar_senha']) ? $dados['confirmar_senha'] : '';
        $confirmarTrim = trim($confirmarSenha);

        if ($confirmarTrim === '') {
            $erros[] = "A confirmação da senha não pode estar vazia.";
        } else {
            if ($novaSenhaTrim !== '' && !Usuario::confirmarNovaSenha($novaSenha, $confirmarSenha)) {
                $erros[] = "A confirmação deve ser igual à nova senha.";
            }
        }

        return $erros;
    }
}
