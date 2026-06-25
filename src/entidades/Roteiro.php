<?php
namespace PADS\App\entidades;

use PADS\App\entidades\Coordenada;

class Roteiro
{
    private int $id_roteiro;
    private \DateTime $data_r;
    private string $titulo;
    private string $codigo;
    private ?Coordenada $centro;
    private int $id_usuario;

    private int $id_poi;
    private \DateTime $dia;
    private \DateTime $entrada;
    private \DateTime $saida;
    private ?string $observacoes;
    private int $id_roteiro_poi;
    

    public function getIdRoteiro(): int {
        return $this->id_roteiro;
    }
    public function setIdRoteiro(int $id_roteiro): void {
        $this->id_roteiro = $id_roteiro;
    }

    
   public function getIdRoteiroPoi(): int {
    return $this->id_roteiro_poi;
    }
    public function setIdRoteiroPoi(int $id_roteiro_poi): void {
        $this->id_roteiro_poi = $id_roteiro_poi;
    }


    public function getDataR(): \DateTime {
        return $this->data_r;
    }
    public function setDataR(\DateTime $data_r): void {
        $this->data_r = $data_r;
    }

    public function getTitulo(): string {
        return $this->titulo;
    }
    public function setTitulo(string $titulo): void {
        $this->titulo = $titulo;
    }

    public function getCodigo(): string {
        return $this->codigo;
    }
    public function setCodigo(string $codigo): void {
        $this->codigo = $codigo;
    }

    public function setCodMD5(string $titulo, string $email, int $id_usuario): void
    {
        $titulo = preg_replace('/\s+/', '', trim($titulo));
        $email  = preg_replace('/\s+/', '', trim($email));
        $idStr  = preg_replace('/\s+/', '', trim((string)$id_usuario));

        $base = $titulo . '|' . $email . '|' . $idStr;

        $this->codigo = md5($base);
    }

    public function getCentro(): ?Coordenada {
         
    return $this->centro; 
        
    }
    public function setCentro(?Coordenada $coord): void
    {
        if ($coord === null) {
            $this->centro = new Coordenada(0.0, 0.0);
        } else {
            $this->centro = $coord;
        }
    }
    public function getCentroWKT(): ?string { 
    
    return $this->centro?->toWKT();
        
    }

    public function getIdUsuario(): int {
        return $this->id_usuario;
    }
    public function setIdUsuario(int $id_usuario): void {
        $this->id_usuario = $id_usuario;
    }

    public function getIdPoi(): int {
        return $this->id_poi;
    }
    public function setIdPoi(int $id_poi): void {
        $this->id_poi = $id_poi;
    }

    public function getDia(): \DateTime {
        return $this->dia;
    }
    public function setDia(\DateTime $dia): void {
        $this->dia = $dia;
    }

    public function getEntrada(): \DateTime {
        return $this->entrada;
    }
    public function setEntrada(\DateTime $entrada): void {
        $this->entrada = $entrada;
    }

    public function getSaida(): \DateTime {
        return $this->saida;
    }
    public function setSaida(\DateTime $saida): void {
        $this->saida = $saida;
    }

    public function getObservacoes(): ?string {
        return $this->observacoes;
    }

    public function setObservacoes(?string $observacoes): void {
        $this->observacoes = $observacoes;
    }

 public static function criarDeArrayRoteiro(array $dados): Roteiro
    {
        $r = new self();

        $r->setIdRoteiro((int)($dados['id_roteiro'] ?? 0));
        $r->setTitulo($dados['titulo'] ?? '');
        $r->setCodigo($dados['codigo'] ?? '');
        $r->setIdUsuario((int)($dados['id_usuario'] ?? 0));
       
        try {
            $r->setDataR(isset($dados['data_r'])
                ? new \DateTime($dados['data_r'])
                : new \DateTime());
        } catch (\Exception $e) {
            $r->setDataR(new \DateTime());
        }

        if (!empty($dados['centro'])) {
            if ($dados['centro'] instanceof Coordenada) {
                $r->setCentro($dados['centro']);
            } else {
                $r->setCentro(Coordenada::fromWKT((string)$dados['centro']));
            }
        } else {
            $r->setCentro(null);
        }

        return $r;
    }

public static function criarDeArrayRoteiroPoi(array $dados): Roteiro{
         $r = new self();

      $r->setIdRoteiroPoi((int)($dados['idRoteiroPoi'] ?? $dados['id_roteiro_poi'] ?? 0));
      $r->setIdRoteiro((int)($dados['id_roteiro'] ?? 0));
      $r->setIdPoi((int)($dados['idPoi'] ?? $dados['id_poi'] ?? 0));
      $r->setObservacoes($dados['observacoes'] ?? null);
  
        try {
            $r->setDia(isset($dados['dia'])
                ? new \DateTime($dados['dia'])
                : new \DateTime());
        } catch (\Exception $e) {
            $r->setDia(new \DateTime());
        }

        try {
            $r->setEntrada(isset($dados['entrada'])
                ? new \DateTime($dados['entrada'])
                : new \DateTime());
        } catch (\Exception $e) {
            $r->setEntrada(new \DateTime());
        }

        try {
            $r->setSaida(isset($dados['saida'])
                ? new \DateTime($dados['saida'])
                : new \DateTime());
        } catch (\Exception $e) {
            $r->setSaida(new \DateTime());
        }
          return $r;
}

}