<?php 

namespace PADS\App\controladores;

use PADS\App\modelos\UsuarioDAO;
use PADS\App\entidades\Usuario;
use PADS\App\validacoes\ValidacaoCadastro;
use PADS\App\validacoes\ValidacaoLogin;
use PADS\App\validacoes\ValidacaoEdicaoUsuario;
use PADS\App\validacoes\ValidacaoAtualizacaoSenha;


class UsuarioControle
{
    private UsuarioDAO $repo;

    public function __construct(UsuarioDAO $repo)
    {
        $this->repo = $repo;
    }

public function processarUsuario(
    int $id,
    string $nome,
    string $email,
    string $senha,           
    string $acao,
    string $novaSenha = '',
    string $confirmarSenha = '',
    string $senhaAtual = ''  
): void {    

    switch ($acao) {
        case "cadastrarUsuario":
            
            $dados = [
                'id_usuario' => $id,
                'nome'       => $nome,
                'email'      => $email,
                'senha'      => $senha,
            ];

            $erros = ValidacaoCadastro::validar($dados);

            if (!empty($erros)) {
                $_SESSION['cadastro_erros'] = $erros;
                $_SESSION['cadastro_dados'] = $dados;
                header("Location: index.php?rota=cadastro");
                exit;
            }

            $usuario = new Usuario();
            $usuario->setId($id);
            $usuario->setNome($nome);
            $usuario->setEmail($email);
            $usuario->setSenhaHash($senha);


            $idUsuario = $this->repo->cadastrarUsuario($usuario);

            if (!$idUsuario) {
                $_SESSION['cadastro_erros'] = ["Falha ao cadastrar. Tente novamente mais tarde."];
                $_SESSION['cadastro_dados'] = $dados;
                header("Location: index.php?rota=cadastro");
                exit;
            }

            unset($_SESSION['login_erros'], $_SESSION['login_dados'], $_SESSION['cadastro_erros'], $_SESSION['cadastro_dados'], $_SESSION['usuario']);

            $usuarioBanco = $this->repo->listarUsuarioPorId($idUsuario);

            session_regenerate_id(true);

            $_SESSION['usuario'] = [
                'id_usuario' => $usuarioBanco->getId(),
                'nome'       => $usuarioBanco->getNome(),
                'email'      => $usuarioBanco->getEmail(),
            ];

            $_SESSION['flash_sucesso'] = "Cadastro realizado com sucesso!";
            header("Location: index.php?rota=telaPrincipal");
            exit;
            break;

        case "logar":

            $dados = [
                    'email'      => $email,
                    'senha'      => $senha,
                ];

            $erros = ValidacaoLogin::validar($dados);

            if (!empty($erros)) {
                $_SESSION['login_erros'] = $erros;
                $_SESSION['login_dados'] = $dados;
                header("Location: index.php?rota=login");
                exit;
            }

            $usuarioBanco = $this->repo->autenticarUsuario($email, $senha);

            if (!$usuarioBanco) {
                $_SESSION['login_erros'] = ["Email ou senha inválidos."];
                $_SESSION['login_dados'] = ['email' => $email];
                header("Location: index.php?rota=login");
                exit;
            }

            unset($_SESSION['login_erros'], $_SESSION['login_dados'], $_SESSION['cadastro_erros'], $_SESSION['cadastro_dados'], $_SESSION['usuario']);

            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id_usuario' => $usuarioBanco->getId(),
                'nome'       => $usuarioBanco->getNome(),
                'email'      => $usuarioBanco->getEmail(),
            ];

            $_SESSION['flash_sucesso'] = "Login realizado com sucesso!";
            header("Location: index.php?rota=telaPrincipal");
            exit;
            break;

                case 'excluirUsuario':
                    
                if ($id <= 0) {
                    $_SESSION['flash_erro'] = "ID de usuário inválido para exclusão.";
                    header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
                    exit;
                }

                $sucesso = $this->repo->excluirUsuario($id);

                if ($sucesso) {
                    header("Location: index.php?rota=deslogar");
                    exit;
                } else {
                    $_SESSION['flash_erro'] = "Erro ao tentar excluir usuário!";
                    header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
                    exit;
                }
                break;

                case 'atualizarPerfil':

                    $dados = [
                            'id_usuario' => $id,
                            'nome'       => $nome,
                            'email'      => $email,
                        ];

                        $erros = ValidacaoEdicaoUsuario::validar($dados);

                        if (!empty($erros)) {
                            $_SESSION['edicao_erros'] = $erros;
                            header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
                            exit;
                        }

                    $usuario = new Usuario();
                    $usuario->setId($id);
                    $usuario->setNome($nome);
                    $usuario->setEmail($email);

                    $sucesso = $this->repo->atualizarPerfil($usuario);

                    if ($sucesso) {
                        $usuarioBanco = $this->repo->listarUsuarioPorId($usuario->getId());

                        $_SESSION['usuario'] = [
                            'id_usuario' => $usuarioBanco->getId(),
                            'nome'       => $usuarioBanco->getNome(),
                            'email'      => $usuarioBanco->getEmail(),
                        ];

                        $_SESSION['flash_sucesso'] = "Perfil atualizado com sucesso!";
                    header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
                        exit;
                    } else {
                        $_SESSION['flash_erro'] = "Erro ao atualizar perfil!";
                    header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
                        exit;
                    }
                    break;

                case "validarSenhaAtual":
                    $dados = [
                        'id_usuario'  => $id,
                        'senha_atual' => $senhaAtual,
                    ];

                    $erros = ValidacaoAtualizacaoSenha::validarSenhaAtual($dados);

                    if (!empty($erros)) {
                        $_SESSION['atualizacao_erros'] = $erros;
                        header("Location: index.php?rota=telaPrincipal&secao=atualizacaoSenha");
                        exit;
                    }

                    $valido = $this->repo->verificarSenhaAtual($id, $senhaAtual);

                    if ($valido) {
                        $_SESSION['flash_sucesso'] = "Senha atual confirmada!";
                        $_SESSION['senha_atual_digitada'] = $senhaAtual;
                        header("Location: index.php?rota=telaPrincipal&secao=atualizacaoSenha&validado=1");
                        exit;
                    } else {
                        $_SESSION['atualizacao_erros'] = ["Senha atual incorreta."];
                        header("Location: index.php?rota=telaPrincipal&secao=atualizacaoSenha");
                        exit;
                    }
                    break;

                case "atualizarSenha":
                    $dados = [
                        'id_usuario'      => $id,
                        'senha_atual'     => $senhaAtual,     
                        'nova_senha'      => $novaSenha,      
                        'confirmar_senha' => $confirmarSenha, 
                    ];

                    $erros = ValidacaoAtualizacaoSenha::validar($dados);

                    if (!empty($erros)) {
                        $_SESSION['atualizacao_erros'] = $erros;
                        $_SESSION['senha_atual_digitada'] = $senhaAtual;
                        header("Location: index.php?rota=telaPrincipal&secao=atualizacaoSenha&validado=1");
                        exit;
                    }

                    $usuario = new Usuario();
                    $usuario->setId($id);
                    $usuario->setSenhaHash($novaSenha);

                    $sucesso = $this->repo->atualizarSenha($usuario->getId(), $usuario->getSenha());

                    if ($sucesso) {
                        $_SESSION['flash_sucesso'] = "Senha atualizada com sucesso!";
                        header("Location: index.php?rota=telaPrincipal&secao=edicaoUsuario");
                        exit;
                    } else {
                        $_SESSION['atualizacao_erros'] = ["Erro ao atualizar senha. Tente novamente."];
                         $_SESSION['senha_atual_digitada'] = $senhaAtual;
                        header("Location: index.php?rota=telaPrincipal&secao=atualizacaoSenha&validado=1");
                        exit;
                    }
                    break;

                }     
        }
    }

