<?php

namespace PADS\App\entidades;

class Coordenada
{
    private float $lat;
    private float $lng;
    private ?array $boundingbox = null; 

    public function __construct(float $lat, float $lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    public function getLat(): float { return $this->lat; }
    public function getLng(): float { return $this->lng; }

    public function toWKT(): string
    {
        return "POINT({$this->lng} {$this->lat})";
    }

    public static function fromWKT(string $wkt): ?Coordenada
    {
        try {
            $wkt = trim(str_replace(['POINT', '(', ')'], '', strtoupper($wkt)));
            if (empty($wkt)) return null;

            $parts = preg_split('/\s+/', $wkt);
            if (count($parts) < 2) return null;

            return new self((float)$parts[1], (float)$parts[0]);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setBoundingbox(float $latMin, float $latMax, float $lonMin, float $lonMax): void
    {
        $this->boundingbox = [
            'lat_min' => $latMin,
            'lat_max' => $latMax,
            'lon_min' => $lonMin,
            'lon_max' => $lonMax,
        ];
    }

    public function getBoundingbox(): ?array
    {
        return $this->boundingbox;
    }

    public function boundingboxToWKT(): string
    {
        if (empty($this->boundingbox)) {
            return 'POLYGON((0.0 0.0,0.0 0.0,0.0 0.0,0.0 0.0,0.0 0.0))';
        }

        $latMin = $this->boundingbox['lat_min'];
        $latMax = $this->boundingbox['lat_max'];
        $lonMin = $this->boundingbox['lon_min'];
        $lonMax = $this->boundingbox['lon_max'];

        return "POLYGON(($lonMin $latMin, $lonMin $latMax, $lonMax $latMax, $lonMax $latMin, $lonMin $latMin))";
    }

    public static function boundingboxFromArray(array $bbox): Coordenada
    {
        $c = new self(0.0, 0.0);
        $c->setBoundingbox($bbox[0], $bbox[1], $bbox[2], $bbox[3]);
        return $c;
    }

    public static function boundingboxFromWKT(string $wkt): ?array
    {
        try {
            $wkt = trim(str_replace(['POLYGON', '(', ')'], '', strtoupper($wkt)));
            if (empty($wkt)) return null;

            $coords = preg_split('/,/', $wkt);
            $points = [];
            foreach ($coords as $c) {
                $parts = preg_split('/\s+/', trim($c));
                if (count($parts) >= 2) {
                    $points[] = ['lon' => (float)$parts[0], 'lat' => (float)$parts[1]];
                }
            }

            if (empty($points)) return null;

            $lats = array_column($points, 'lat');
            $lons = array_column($points, 'lon');

            return [
                'lat_min' => min($lats),
                'lat_max' => max($lats),
                'lon_min' => min($lons),
                'lon_max' => max($lons),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
?>