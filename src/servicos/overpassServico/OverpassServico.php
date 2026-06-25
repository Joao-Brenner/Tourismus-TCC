<?php
namespace PADS\App\servicos\overpassServico;

class OverpassServico
{

private static function normalizarHorarioFuncionamento(string $horario): string
{

    $diasMap = [
        'Mo' => 'Seg',
        'Tu' => 'Ter',
        'We' => 'Qua',
        'Th' => 'Qui',
        'Fr' => 'Sex',
        'Sa' => 'Sáb',
        'Su' => 'Dom',
        'PH' => 'Feriados',
        'SH' => 'Férias escolares',
    ];

    $especialMap = [
        'off' => 'Fechado',
        '24/7' => 'Aberto 24h',
        'Jan' => 'Jan',
        'Feb' => 'Fev',
        'Mar' => 'Mar',
        'Apr' => 'Abr',
        'May' => 'Mai',
        'Jun' => 'Jun',
        'Jul' => 'Jul',
        'Aug' => 'Ago',
        'Sep' => 'Set',
        'Oct' => 'Out',
        'Nov' => 'Nov',
        'Dec' => 'Dez',
    ];

    $blocos = explode(';', $horario);
    $resultado = [];

    foreach ($blocos as $bloco) {
        $bloco = trim($bloco);
        if ($bloco === '') continue;

        $partes = explode(' ', $bloco, 2);
        $dias = $partes[0] ?? '';
        $horas = $partes[1] ?? '';

        foreach ($diasMap as $en => $pt) {
            $dias = str_replace($en, $pt, $dias);
        }

        foreach ($especialMap as $en => $pt) {
            $dias = str_replace($en, $pt, $dias);
            $horas = str_replace($en, $pt, $horas);
        }

        $resultado[] = trim($dias . ' ' . $horas);
    }

    return implode('; ', $resultado);
}


private static function normalizarEndereco(
    ?string $cidade,
    ?string $suburb,
    ?string $rua,
    ?string $housename,
    ?string $numero
): ?string {
    $partes = [];

    if ($cidade) {
        $partes[] = $cidade;
    }

    $temLocalizacao = false;

    if ($suburb) {
        $partes[] = $suburb;
        $temLocalizacao = true;
    }
    if ($rua) {
        $partes[] = $rua;
        $temLocalizacao = true;
    }
    if ($housename) {
        $partes[] = $housename;
        $temLocalizacao = true;
    }

    if ($numero && $temLocalizacao) {
        $partes[] = $numero;
    }

    if (empty($partes)) {
        return null;
    }

    return implode(', ', $partes);
}


public static function extrairDadosOver($entrada): array
{

    if (is_string($entrada)) {
        $dados = json_decode($entrada, true);
        if ($dados === null) {
            error_log("extrairDadosOver: JSON inválido recebido -> " . $entrada);
            throw new \InvalidArgumentException('JSON inválido em extrairDadosOver');
        }
    } elseif (is_array($entrada)) {
        $dados = $entrada;
    } else {
        error_log("extrairDadosOver: tipo inválido recebido (" . gettype($entrada) . ")");
        throw new \InvalidArgumentException('extrairDadosOver aceita apenas string JSON ou array');
    }

    if (isset($dados['erro_tecnico'])) {
         error_log("extrairDadosOver: erro técnico detectado -> " . $dados['erro_tecnico']);
        return [['erro_tecnico' => $dados['erro_tecnico']]];

    }

    if (empty($dados['elements'])) {
        return [];
    }

    $resultados = [];


    foreach ($dados['elements'] as $element) {
    $osm_id   = isset($element['id']) ? (int)$element['id'] : null;
    $osm_type = $element['type'] ?? null;

    $lat = null;
    $lon = null;
    if (isset($element['center'])) {
        $lat = isset($element['center']['lat']) && is_numeric($element['center']['lat']) ? (float)$element['center']['lat'] : null;
        $lon = isset($element['center']['lon']) && is_numeric($element['center']['lon']) ? (float)$element['center']['lon'] : null;
    } else {
        $lat = isset($element['lat']) && is_numeric($element['lat']) ? (float)$element['lat'] : null;
        $lon = isset($element['lon']) && is_numeric($element['lon']) ? (float)$element['lon'] : null;
    }

    $tags = $element['tags'] ?? [];

    $nome = $tags['short_name'] ?? ($tags['alt_name'] ?? ($tags['name'] ?? null));

    $email    = $tags['email']    ?? null;
    $telefone = $tags['phone']    ?? null;
    $website  = $tags['website']  ?? null;

    $horario_funcionamento = isset($tags['opening_hours']) 
        ? self::normalizarHorarioFuncionamento($tags['opening_hours']) 
        : null;

    $endereco = null;
    if (
        isset($tags['addr:city']) ||
        isset($tags['addr:suburb']) ||
        isset($tags['addr:street']) ||
        isset($tags['addr:housename']) ||
        isset($tags['addr:housenumber'])
    ) {
        $endereco = self::normalizarEndereco(
            $tags['addr:city']      ?? null,
            $tags['addr:suburb']    ?? null,
            $tags['addr:street']    ?? null,
            $tags['addr:housename'] ?? null,
            $tags['addr:housenumber'] ?? null
        );
    }

    $resultados[] = [
        'osm_id'               => $osm_id,
        'osm_type'             => $osm_type,
        'lat'                  => $lat,
        'lon'                  => $lon,
        'nome'                 => $nome,
        'email'                => $email,
        'telefone'             => $telefone,
        'website'              => $website,
        'horario_funcionamento'=> $horario_funcionamento,
        'endereco'             => $endereco,
    ];
     }

    if (count($resultados) === 1) {
        return $resultados[0];
    }

    error_log("extrairDadosOver: resultado -> " . json_encode($resultados));

    return $resultados;
}

}
?>