<?php

namespace PADS\App\servicos\nominatimServico;

class NominatimRequisicao
{
    
    public static function requisicaoNominatim(
        string $pesquisaNormalizada,
        string $estadoNormalizado
    ): string {

        $baseUrl   =$_ENV['NOMINATIM_URL_BUSCAR'] ?? null;
        $userAgent = $_ENV['USER_AGENT'] ?? null;

if (empty($baseUrl) || empty($userAgent)) {
    throw new \RuntimeException("Configuração crítica ausente: NOMINATIM_URL_BUSCAR ou USER_AGENT.");
}


       $params = [
            'q' => $pesquisaNormalizada . ', ' . $estadoNormalizado,
            'countrycodes' => 'BR',
            'format' => 'jsonv2',
            'limit' => 1
        ];

        $url = $baseUrl . "?" . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept-Language: pt-BR,pt;q=0.9"
        ]);


        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($resposta === false || $httpCode !== 200) {
            error_log("NominatimRequisicao: Erro HTTP $httpCode - cURL Error: $error - URL: $url");
            return json_encode(['erro_tecnico' => "Falha na comunicação com o serviço de geocodificação ($httpCode)"]);
        }

        $dadosTraduzidos = json_decode($resposta, true);

        if (empty($dadosTraduzidos) || !is_array($dadosTraduzidos)) {
            return json_encode([
                'osm_id'      => null,
                'osm_type'    => null,
                'lat'         => null,
                'lon'         => null,
                'boundingbox' => null
            ]);
        }

   
        return json_encode($dadosTraduzidos[0]);

    }
}

?>