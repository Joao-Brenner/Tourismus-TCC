<?php

namespace PADS\App\servicos\overpassServico;

class OverpassRequisicaoEntorno
{
    
    public static function requisicaoOverpassEntorno(
        float $lat,
        float $lon,
        int $quantidadeRequisição
    ): string {

        $baseUrl   =$_ENV['OVERPASS_URL_OFICIAL'] ?? null;
        $userAgent = $_ENV['USER_AGENT'] ?? null;

        if (empty($baseUrl) || empty($userAgent)) {
            throw new \RuntimeException(
                "Configuração crítica ausente: OVERPASS_URL_OFICIAL ou USER_AGENT."
            );
        }

        $query = "[out:json][timeout:35][maxsize:268435456];        
                  nwr[tourism=attraction][name](around:2000,$lat,$lon);
                  out $quantidadeRequisição tags center;";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $baseUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['data' => $query]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_TIMEOUT        => 55,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_ENCODING       => '',
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_HTTPHEADER     => [
                "Accept-Language: pt-BR,pt;q=0.9",
                "Accept: application/json"
            ]
        ]);

        $resposta = curl_exec($ch);
        $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        error_log("cURL request headers OverpassEntorno: " . $requestHeaders);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($resposta === false || $httpCode !== 200) {
            error_log("Overpass erro HTTP $httpCode - cURL: $error");
            return json_encode([
                'erro_tecnico' => "Falha na comunicacao com o Overpass ($httpCode)"
            ]);
        }

        $json = json_decode($resposta, true);

        if (
            !isset($json['elements']) ||
            !is_array($json['elements']) ||
            count($json['elements']) === 0
        ) {
            return json_encode([
                'elements' => []
            ]);
        }

        return json_encode($json);
    }
}
