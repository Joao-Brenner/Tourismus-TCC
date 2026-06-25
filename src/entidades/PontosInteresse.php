<?php

namespace PADS\App\entidades;
use PADS\App\entidades\Coordenada;

class PontosInteresse
{
    private int $id = 0;
    private ?int $osm_id = null; 
    private ?string $osm_type = null;
    private string $nome = '';
    private ?string $estado = '';
    private string $email= '';
    private string $telefone= '';
    private string $website= '';
    private string $endereco= '';
    private string $horario_funcionamento= '';
    private ?Coordenada $indice_espacial_overpass = null;

    public function getId(): int 
    { 
        return $this->id; 
        }

    public function setId(int $id): void 
    { 
        $this->id = $id; 
        }

    public function getOsmId(): ?int 
    { 
        return $this->osm_id; 
    }

    public function setOsmId(?int $osm_id): void 
    { 
        $this->osm_id = $osm_id; 
    }


    public function getOsmType(): ?string 
    { 
        return $this->osm_type;
    }

    public function setOsmType(?string $osm_type): void 
    { 
        $this->osm_type = $osm_type; 
    }    

      public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getEstado(): ?string 
    { 
        return $this->estado;
    }

    public function setEstado(?string $estado): void
     {
         $this->estado = $estado ?? '';
    }

     public function getEmail(): ?string 
    {
        return $this->email;
    }

    public function setEmail(?string  $email): void
    {
        $this->email = $email ?? '';
    }

     public function getTelefone(): ?string 
    {
        return $this->telefone;
    }

    public function setTelefone(?string  $telefone): void
    {
        $this->telefone = $telefone ?? '';
    }

      public function getWebsite(): ?string 
    {
        return $this->website;
    }

    public function setWebsite(?string  $website): void
    {
        $this->website = $website ?? '';
    }

     public function getEndereco(): ?string 
    {
        return $this->endereco;
    }

    public function setEndereco(?string  $endereco): void
    {
        $this->endereco = $endereco ?? '';
    }

    public function getHorarioFuncionamento(): ?string 
    {
        return $this->horario_funcionamento;
    }

    public function setHorarioFuncionamento(?string  $horario_funcionamento): void
    {
        $this->horario_funcionamento = $horario_funcionamento ?? '';
    }

    public function getIndiceEspacialOverpass(): ?Coordenada 
    { 
        return $this->indice_espacial_overpass; 
    }

    public function setIndiceEspacialOverpass(?Coordenada $coord): void
    {
        if ($coord === null) {
            $this->indice_espacial_overpass = new Coordenada(0.0, 0.0);
        } else {
            $this->indice_espacial_overpass = $coord;
        }
    }

    public function getIndiceEspacialOverpassWKT(): ?string 
    { 
        return $this->indice_espacial_overpass?->toWKT();
    }

public function atualizarOverpassStatusAlvoAutomatico(): string
{
    $temDados = !empty($this->osm_id)
             && !empty($this->osm_type)
             && !empty($this->nome)
             && $this->indice_espacial_overpass !== null
             && $this->indice_espacial_overpass->getLat() != 0.0
             && $this->indice_espacial_overpass->getLng() != 0.0;

    if ($temDados) {
        $this->overpass_status_alvo = 'VALIDO';
    } else {
        $this->overpass_status_alvo = 'NULO';
    }
    return $this->overpass_status_alvo;
}

    public function isPersistivel(): bool { return !empty($this->osm_id) && !empty($this->osm_type) && !empty($this->nome) && !empty( $this->indice_espacial_overpass->getLat()) && !empty( $this->indice_espacial_overpass->getLng());}

public static function criarDeArray(array $dados): PontosInteresse
{
    $pi = new self();
    $pi->id = (int)($dados['id'] ?? 0);
    $pi->osm_id = isset($dados['osm_id']) ? (int)$dados['osm_id'] : null;
    $pi->osm_type = $dados['osm_type'] ?? null;
    $pi->nome = $dados['nome'] ?? '';
    $pi->estado = $dados['estado'] ?? '';
    $pi->email = $dados['email'] ?? '';
    $pi->telefone = $dados['telefone'] ?? '';
    $pi->website = $dados['website'] ?? '';
    $pi->endereco = $dados['endereco'] ?? '';
    $pi->horario_funcionamento = $dados['horario_funcionamento'] ?? '';


if (!empty($dados['indice_espacial_overpass'])) {
        if ($dados['indice_espacial_overpass'] instanceof Coordenada) {
            $pi->setIndiceEspacialOverpass($dados['indice_espacial_overpass']);
        } else {
            $pi->setIndiceEspacialOverpass(Coordenada::fromWKT((string)$dados['indice_espacial_overpass']));
        }
    } else {
        $pi->setIndiceEspacialOverpass(null);
    }

    return $pi;


}

}


?>