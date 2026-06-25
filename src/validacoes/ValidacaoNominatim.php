<?php
namespace PADS\App\validacoes;

use PADS\App\servicos\nominatimServico\NominatimServico;

class ValidacaoNominatim
{

    private const DELAY_STEPS = [5, 10, 15, 20];

    private static array $estadosValidos = [
        "acre","alagoas","amazonas","amapa","bahia","ceara","distrito_federal",
        "espirito_santo","goias","maranhao","minas_gerais","mato_grosso_do_sul",
        "mato_grosso","para","paraiba","pernambuco","piaui","parana","rio_de_janeiro",
        "rio_grande_do_norte","rondonia","roraima","rio_grande_do_sul","santa_catarina",
        "sergipe","sao_paulo","tocantins"
    ];

    public static function validar(array $dados): array
    {
        $resultado = [
            'erros' => [],
            'dados_validados' => []
        ];

        $agora = time();

        $idUsuario = (int)($dados['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            $resultado['erros'][] = "Usuário inválido para pesquisa.";
            return $resultado;
        }

        if (isset($_SESSION['pesquisa_expira']) && $agora < $_SESSION['pesquisa_expira']) {
            $restante = $_SESSION['pesquisa_expira'] - $agora;
            $resultado['erros'][] = "Aguarde {$restante}s antes de nova pesquisa.";
            return $resultado;
        }

        $estadoRaw = trim((string)($dados['estado'] ?? ''));
        if ($estadoRaw === '' || !in_array($estadoRaw, self::$estadosValidos, true)) {
            $resultado['erros'][] = "Selecione um estado válido.";
            return $resultado;
        }

        $pesquisa = trim((string)($dados['pesquisa_original'] ?? ''));
        if ($pesquisa === '') {
            $resultado['erros'][] = "A pesquisa não pode estar vazia.";
        } else {
            $len = mb_strlen($pesquisa, 'UTF-8');
            if ($len > 50) {
                $resultado['erros'][] = "A pesquisa não pode ultrapassar 50 caracteres.";
            }

            $letras = preg_match_all('/[a-zA-ZÀ-ÿ]/u', $pesquisa);
            if ($letras < 5) {
                $resultado['erros'][] = "A pesquisa deve conter pelo menos 5 letras.";
            }

            if (preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $pesquisa)) {
                $resultado['erros'][] = "A pesquisa não deve conter emojis.";
            }

            if (preg_match('/[^a-zA-ZÀ-ÿ0-9\s]/u', $pesquisa)) {
                $resultado['erros'][] = "A pesquisa não deve conter caracteres especiais.";
            }

            if (NominatimServico::ehGenerica($pesquisa)) {
                $resultado['erros'][] = "A pesquisa é genérica demais. Especifique melhor.";
            }
        }

        if (!empty($resultado['erros'])) {
            return $resultado;
        }

        $normalizedStr = NominatimServico::normalizarTexto($pesquisa);
        $estadoNormalizado = str_replace('_', ' ', mb_strtolower($estadoRaw, 'UTF-8'));

        $resultado['dados_validados'] = [
            'id_usuario' => $idUsuario,
            'estado'   => $estadoNormalizado,
            'pesquisa' => $normalizedStr
        ];

        $combinado = $estadoNormalizado . "|" . $normalizedStr;
        $currentHash = NominatimServico::sha256($combinado);

        $hashFront = $dados['query_hash'] ?? null;
        if ($hashFront && $hashFront !== $currentHash) {
            $resultado['erros'][] = "Inconsistência na assinatura da pesquisa.";
            return $resultado;
        }

        if ($currentHash === '' || $currentHash === null) {
            $resultado['erros'][] = "Falha ao gerar o identificador único da pesquisa (query_hash).";
            return $resultado;
        }

        $lastHash = $_SESSION['ultimaQueryHash'] ?? null;
        $ultimaPesquisa = $_SESSION['ultimaPesquisa'] ?? '';
        $ultimaPesquisaEstado = $_SESSION['ultimaPesquisaEstado'] ?? '';

        $similaridade = NominatimServico::calcularSimilaridade($normalizedStr, $ultimaPesquisa);

        if (($similaridade >= 0.8 && $estadoNormalizado === $ultimaPesquisaEstado) ||
            ($lastHash && $lastHash === $currentHash)) {
            $resultado['erros'][] = "Você já pesquisou algo muito semelhante no mesmo estado. Altere a pesquisa para continuar.";
            return $resultado;
        }

        $_SESSION['ultimaQueryHash'] = $currentHash;
        $_SESSION['ultimaPesquisa'] = $normalizedStr;
        $_SESSION['ultimaPesquisaEstado'] = $estadoNormalizado;

        $nivelAtual = $_SESSION['delayNivel'] ?? 5;
        if (!in_array($nivelAtual, self::DELAY_STEPS, true)) {
            $nivelAtual = 5;
        }

        $delayExpira = $_SESSION['delayExpira'] ?? 0;
        if ($delayExpira && $agora >= $delayExpira) {
            $nivelAtual = 5;
        } else {
            $ultimoFimBloqueio = $_SESSION['ultimoFimBloqueio'] ?? 0;
            $foiRapido = ($ultimoFimBloqueio && ($agora - $ultimoFimBloqueio) <= 10);

            $idx = array_search($nivelAtual, self::DELAY_STEPS, true);
            if ($idx === false) { $idx = 0; }

            if ($foiRapido) {
                $nivelAtual = self::DELAY_STEPS[min($idx + 1, count(self::DELAY_STEPS) - 1)];
            } else {
                $nivelAtual = self::DELAY_STEPS[max($idx - 1, 0)];
            }
        }

        $_SESSION['delayNivel'] = $nivelAtual;
        $_SESSION['delayExpira'] = $agora + 60;
        $_SESSION['ultimoTempoPesquisa'] = $agora;
        $_SESSION['pesquisa_expira'] = $agora + $nivelAtual;
        $_SESSION['ultimoFimBloqueio'] = $_SESSION['pesquisa_expira'];

        return $resultado;
    }
}
