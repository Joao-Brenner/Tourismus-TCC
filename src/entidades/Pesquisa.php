<?php

namespace PADS\App\entidades;

class Pesquisa
{
    private int $id;
    private string $pesquisa_original;
    private string $pesquisa_normalizada;
    private string $estado_normalizado;
    private string $query_hash;

    private ?int $osm_id = null; 
    private ?string $osm_type = null;
    private ?Coordenada $indice_espacial_nominatim = null;
    private ?Coordenada $boundingbox = null;
    private \DateTime $validade;


    private string $nominatim_status;
    private string $overpass_status_alvo;
    private string $overpass_status_entorno;


    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getPesquisaOriginal(): string { return $this->pesquisa_original; }
    public function setPesquisaOriginal(string $pesquisa_original): void { $this->pesquisa_original = $pesquisa_original; }

    public function getPesquisaNormalizada(): string { return $this->pesquisa_normalizada; }
    public function setPesquisaNormalizada(string $pesquisa_normalizada): void { $this->pesquisa_normalizada = $pesquisa_normalizada; }

    public function getEstadoNormalizado(): string { return $this->estado_normalizado; }
    public function setEstadoNormalizado(string $estado_normalizado): void { $this->estado_normalizado = $estado_normalizado; }

    public function getValidade(): \DateTime { return $this->validade; }
    public function setValidade(\DateTime $validade): void { $this->validade = $validade; }

    public function getQueryHash(): string { return $this->query_hash; }
    public function setQueryHash(string $query_hash): void { $this->query_hash = $query_hash; }

    public function getOsmId(): ?int { return $this->osm_id; }
    public function setOsmId(?int $osm_id): void { $this->osm_id = $osm_id; }

    public function getOsmType(): ?string { return $this->osm_type; }
    public function setOsmType(?string $osm_type): void { $this->osm_type = $osm_type; }

    public function getIndiceEspacialNominatim(): ?Coordenada { return $this->indice_espacial_nominatim; }
    public function setIndiceEspacialNominatim(?Coordenada $coord): void
    {
        if ($coord === null) {
            $this->indice_espacial_nominatim = new Coordenada(0.0, 0.0);
        } else {
            $this->indice_espacial_nominatim = $coord;
        }
    }
    public function getIndiceEspacialWKT(): ?string { return $this->indice_espacial_nominatim?->toWKT(); }

    public function getBoundingboxWKT(): string
    {
        if ($this->boundingbox instanceof Coordenada) {
            return $this->boundingbox->boundingboxToWKT();
        }
        return 'POLYGON((0.0 0.0,0.0 0.0,0.0 0.0,0.0 0.0,0.0 0.0))';
    }

    public function setBoundingboxFromArray(?array $bbox): void
    {
        if (empty($bbox)) {
            $this->boundingbox = Coordenada::boundingboxFromArray([0.0,0.0,0.0,0.0]);
        } else {
            $this->boundingbox = Coordenada::boundingboxFromArray($bbox);
        }
    }


     public function setBoundingboxFromWKT(?string $wkt): void
    {
        $arr = Coordenada::boundingboxFromWKT($wkt ?? '');
        $this->boundingbox = $arr
            ? Coordenada::boundingboxFromArray([$arr['lat_min'], $arr['lat_max'], $arr['lon_min'], $arr['lon_max']])
            : Coordenada::boundingboxFromArray([0.0,0.0,0.0,0.0]);
    }


public function atualizarNominatimStatusAutomatico(): void
{
    $temDados = !empty($this->osm_id)
             && !empty($this->osm_type)
             && $this->indice_espacial_nominatim !== null
             && $this->indice_espacial_nominatim->getLat() != 0.0
             && $this->indice_espacial_nominatim->getLng() != 0.0;

    if ($temDados) {
        $this->nominatim_status = 'VALIDO';
    } else {
        $this->osm_id = null;
        $this->osm_type = null;
        $this->indice_espacial_nominatim = new Coordenada(0.0, 0.0);
        $this->boundingbox = Coordenada::boundingboxFromArray([0.0,0.0,0.0,0.0]);
        $this->nominatim_status = 'NULO';
    }
}

        public function setNominatimStatus(string $nominatim_status): void
        {
            $this->nominatim_status = strtoupper(trim($nominatim_status));
        }

        public function getNominatimStatus(): string
        {
            return $this->nominatim_status;
        }

        public function setOverpassStatusAlvo(string $overpass_status_alvo): void
        {
            $this->overpass_status_alvo = strtoupper(trim($overpass_status_alvo));
        }

        public function getOverpassStatusAlvo(): string
        {
            return $this->overpass_status_alvo;
        }

        public function setOverpassStatusEntorno(string $overpass_status_entorno): void
        {
            $this->overpass_status_entorno = strtoupper(trim($overpass_status_entorno));
        }

        public function getOverpassStatusEntorno(): string
        {
            return $this->overpass_status_entorno;
        }


    public function isPersistivel(): bool { return !empty($this->query_hash) && !empty($this->pesquisa_normalizada) && !empty($this->estado_normalizado);}

public static function criarDeArray(array $dados): Pesquisa
{
    $p = new self();
    $p->id = (int)($dados['id'] ?? 0);
    $p->pesquisa_original = $dados['pesquisa_original'] ?? '';
    $p->pesquisa_normalizada = $dados['pesquisa_normalizada'] ?? '';
    $p->estado_normalizado = $dados['estado_normalizado'] ?? '';

    try {
        $p->validade = isset($dados['validade'])
            ? new \DateTime($dados['validade'])
            : new \DateTime();
    } catch (\Exception $e) {
        $p->validade = new \DateTime();
    }

    $p->query_hash = $dados['query_hash'] ?? '';
    $p->osm_id = isset($dados['osm_id']) ? (int)$dados['osm_id'] : null;
    $p->osm_type = $dados['osm_type'] ?? null;

    if (!empty($dados['indice_espacial_nominatim'])) {
        if ($dados['indice_espacial_nominatim'] instanceof Coordenada) {
            $p->setIndiceEspacialNominatim($dados['indice_espacial_nominatim']);
        } else {
            $p->setIndiceEspacialNominatim(Coordenada::fromWKT((string)$dados['indice_espacial_nominatim']));
        }
    } else {
        $p->setIndiceEspacialNominatim(null);
    }

    if (!empty($dados['boundingbox'])) {
        if (is_array($dados['boundingbox'])) {
            $p->setBoundingboxFromArray($dados['boundingbox']);
        } else {
            $p->setBoundingboxFromWKT((string)$dados['boundingbox']);
        }
    } else {
        $p->setBoundingboxFromArray([0.0,0.0,0.0,0.0]);
    }

    $p->setNominatimStatus($dados['nominatim_status'] ?? '');
    $p->setOverpassStatusAlvo($dados['overpass_status_alvo'] ?? '');
    $p->setOverpassStatusEntorno($dados['overpass_status_entorno'] ?? '');

    return $p;
}



}
?>