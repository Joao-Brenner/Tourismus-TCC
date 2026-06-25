<?php
namespace PADS\App\servicos\nominatimServico;

class NominatimServico
{
     private static array $mapaEstados = [
        "MS" => ["mato grosso do sul", "ms"],
        "MT" => ["mato grosso", "mt"],
        "RS" => ["rio grande do sul", "rs"],
        "RN" => ["rio grande do norte", "rn"],
        "RJ" => ["rio de janeiro", "rj"],
        "DF" => ["distrito federal", "df"],
        "ES" => ["espirito santo", "es"],
        "MG" => ["minas gerais", "mg"],
        "SC" => ["santa catarina", "sc"],
        "SP" => ["sao paulo", "sp"],
        "AC" => ["acre", "ac"],
        "AL" => ["alagoas", "al"],
        "AP" => ["amapa", "ap"],
        "AM" => ["amazonas", "am"],
        "BA" => ["bahia", "ba"],
        "CE" => ["ceara", "ce"],
        "GO" => ["goias", "go"],
        "MA" => ["maranhao", "ma"],
        "PA" => ["para", "pa"],
        "PB" => ["paraiba", "pb"],
        "PE" => ["pernambuco", "pe"],
        "PI" => ["piaui", "pi"],
        "PR" => ["parana", "pr"],
        "RO" => ["rondonia", "ro"],
        "RR" => ["roraima", "rr"],
        "SE" => ["sergipe", "se"],
        "TO" => ["tocantins", "to"]
    ];

    private static array $stopwords = [
        "de","do","da","dos","das","em","no","na","a","o","as","os",
        "um","uma","uns","umas","por","pelo","pela","pelos","pelas",
        "e","ou","para","com","sem","cujo","cuja","cujos","cujas","que","sob","sobre",
        "rua","avenida","estrada","rodovia","travessa","alameda","bairro","quadra","lote",
        "principal","geral","vila","cidade","municipio","setor","largo","beco","via",
        "mercado","supermercado","shopping","posto","farmacia","hospital","delegacia",
        "rodoviaria","aeroporto","terminal","estacao","galeria","posto de gasolina",
        "hotel","restaurante","bar","lanchonete","padaria","pizzaria","churrascaria",
        "cafeteria","lojinha","quitanda","mercearia","conveniencia","espetaria","boteco","pub",
        "escola","faculdade","universidade","biblioteca","prefeitura","cartorio",
        "tribunal","forum","secretaria","ministerio","departamento","policia","bombeiros",
        "correios","delegacia","posto de saude",
        "igreja","templo","capela","mesquita","sinagoga","diocese","paroquia","catedral",
        "praia","praca","parque","campo","clube","reserva","jardim","canto","ponto",
        "balneario","quadra","ginasio",
        "teste","abc","123","xxx","lorem","ipsum","query","busca","pesquisa","local","lugar","nome"
    ];


public static function normalizarTexto(string $texto): string
{
    $t = str_replace(['_', ','], ' ', $texto);
    $t = mb_strtolower($t, 'UTF-8');

    $acentos = [
    'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
    'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
    'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
    'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
    'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
    'ç'=>'c'
];

    $t = strtr($t, $acentos);

    $t = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $t);
    $t = preg_replace('/\s+/u', ' ', $t);
    $t = trim($t);

    foreach (self::$mapaEstados as $lista) {
        foreach ($lista as $ref) {
            $parts = preg_split('/\s+/', $ref);
            $regex = '/\b' . implode('\s+', array_map(static function ($p) {
                return preg_quote($p, '/');
            }, $parts)) . '\b/u';

            $t = preg_replace($regex, '', $t);
            $t = preg_replace('/\s+/u', ' ', $t);
            $t = trim($t);
        }
    }

    return $t;
}


    public static function ehGenerica(string $texto): bool
    {
        $t = self::normalizarTexto($texto);
        if ($t === '') return true;
        $tokens = array_filter(explode(' ', $t));
        foreach ($tokens as $tok) {
            if (!in_array($tok, self::$stopwords, true)) {
                return false;
            }
        }
        return true;
    }

    public static function calcularSimilaridade(string $p1, string $p2): float
    {
        $t1 = array_filter(explode(' ', self::normalizarTexto($p1)));
        $t2 = array_filter(explode(' ', self::normalizarTexto($p2)));
        if (empty($t1) || empty($t2)) return 0.0;
        if (implode(' ', $t1) === implode(' ', $t2)) return 1.0;
        $s1 = array_unique($t1);
        $s2 = array_unique($t2);
        $inter = count(array_intersect($s1, $s2));
        $uniao = count(array_unique(array_merge($s1, $s2)));
        return $uniao > 0 ? $inter / $uniao : 0.0;
    }

    public static function sha256(string $str): string
    {
        return hash('sha256', $str);
    }


     public static function extrairDados($entrada): array
{

    if (is_string($entrada)) {
        $dados = json_decode($entrada, true);
        if ($dados === null) {
            error_log("extrairDados: JSON inválido recebido -> " . $entrada);
            throw new \InvalidArgumentException('JSON inválido em extrairDados');
        }
    } elseif (is_array($entrada)) {
        $dados = $entrada;
    } else {
        error_log("extrairDados: tipo inválido recebido (" . gettype($entrada) . ")");
        throw new \InvalidArgumentException('extrairDados aceita apenas string JSON ou array');
    }

    if (isset($dados['erro_tecnico'])) {
        $resultado = [
            'erro_tecnico' => $dados['erro_tecnico'],
        ];
        error_log("extrairDados: erro técnico detectado -> " . $dados['erro_tecnico']);
        return $resultado;
    }


   
        $lat = isset($dados['lat']) && is_numeric($dados['lat']) ? (float)$dados['lat'] : null;
        $lon = isset($dados['lon']) && is_numeric($dados['lon']) ? (float)$dados['lon'] : null;

            $bbox = null;
            if (isset($dados['boundingbox']) && is_array($dados['boundingbox']) && count($dados['boundingbox']) === 4) {
                $bbox = array_map('floatval', $dados['boundingbox']);
            }

        $resultado = [
    'osm_id'      => isset($dados['osm_id']) ? (int)$dados['osm_id'] : null,
    'osm_type'    => $dados['osm_type'] ?? null,
    'lat'         => $lat,
    'lon'         => $lon,
    'boundingbox' => $bbox,
];



    error_log("extrairDados: resultado -> " . json_encode($resultado));

    return $resultado;
}

}




