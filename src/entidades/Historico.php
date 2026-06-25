<?php

namespace PADS\App\entidades;
use PADS\App\entidades\Pesquisa;


class Historico
{

    private int $id_usuario = 0;
    private int $id_pesquisa = 0;
    private \DateTime $data_pesquisa;
    private ?Pesquisa $pesquisa = null; 


    public function getIdUsuario(): int { return $this->id_usuario; }
    public function setIdUsuario(int $id_usuario): void { $this->id_usuario = $id_usuario; }

    public function getIdPesquisa(): int { return $this->id_pesquisa; }
    public function setIdPesquisa(int $id_pesquisa): void { $this->id_pesquisa = $id_pesquisa; }

    public function getDataPesquisa(): \DateTime { return $this->data_pesquisa; }
    public function setDataPesquisa(\DateTime $data_pesquisa): void { $this->data_pesquisa = $data_pesquisa; }

    public function getPesquisa(): ?Pesquisa { return $this->pesquisa; }
    public function setPesquisa(Pesquisa $pesquisa): void { $this->pesquisa = $pesquisa; }

    public static function criarDeArray(array $dados): Historico
    {
        $h = new self();
        $h->id_usuario = (int)($dados['id_usuario'] ?? 0);
        $h->id_pesquisa = (int)($dados['id_pesquisa'] ?? 0);
        try {
                $h->data_pesquisa = isset($dados['data_pesquisa'])
                    ? new \DateTime($dados['data_pesquisa'])
                    : new \DateTime();
            } catch (\Exception $e) {
                $h->data_pesquisa = new \DateTime();
            }
            return $h;
    }
}

?>