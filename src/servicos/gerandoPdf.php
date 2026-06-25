<?php
namespace PADS\App\servicos;

use Dompdf\Dompdf;
use Dompdf\Options;

class GerandoPdf
{

    public function criarPdfComImagem(string $imagemBase64, array $roteiro, string $codigoMd5): string

    {
       
        $titulo = htmlspecialchars($roteiro['titulo']);
        $contador = 1;
        $diasHtml = '';
        foreach ($roteiro['dias'] as $dia) {
            
    $dataFormatada = '';
    if (!empty($dia['data'])) {
        try {
            $dt = new \DateTime($dia['data']);
            $dataFormatada = $dt->format('d/m/Y');
        } catch (\Exception $e) {
            $dataFormatada = htmlspecialchars($dia['data']); 
        }
    }

    $diasHtml .= '<h2>Dia: ' . $dataFormatada . '</h2>';

            $diasHtml .= '<ul>';
            
            foreach ($dia['pontos'] as $ponto) {
                $diasHtml .= '<li>';
                $diasHtml .= '<strong>' . $contador.'°)  '.  htmlspecialchars($ponto['nome']) . '</strong><br>';
                $diasHtml .= 'Estado: ' . htmlspecialchars($ponto['estado']) . '<br>';

                 if (!empty($ponto['endereco'])) {
                $diasHtml .= 'Endereço: ' . htmlspecialchars($ponto['endereco']) . '<br>';
                }
                if (!empty($ponto['horario_funcionamento'])) {
                $diasHtml .= 'Horário de Funcionamento: ' . htmlspecialchars($ponto['horario_funcionamento']) . '<br>';
                }
                 if (!empty($ponto['telefone'])) {
                $diasHtml .= 'Telefone: ' . htmlspecialchars($ponto['telefone']) . '<br>';
                 }
                  if (!empty($ponto['email'])) {
                $diasHtml .= 'Email: ' . htmlspecialchars($ponto['email']) . '<br>';
                  }
                  if (!empty($ponto['website'])) {
                $diasHtml .= 'Website: ' . htmlspecialchars($ponto['website']) . '<br>';
                  }

                $diasHtml .= 'Entrada: ' . htmlspecialchars($ponto['entrada']) . ' - Saída: ' . htmlspecialchars($ponto['saida']) . '<br>';

                if (!empty($ponto['notas'])) {
                    $diasHtml .= '<em class="notas">Notas: ' . nl2br(htmlspecialchars($ponto['notas'])) . '</em>';
                }
                $diasHtml .= '</li>';
                $contador++; 
            }
            $diasHtml .= '</ul>';
        }

        $html = '
        <html>
        <head>
            <style>
                body { font-family: DejaVu Sans, sans-serif; }
                h1 { text-align: center; color: #275cab; margin-bottom: 20px; }
                h2 { color: #333; margin-top: 15px; }
                .notas { display: block; white-space: pre-wrap; word-wrap: break-word; max-width: 100%; font-style: italic; }
                ul { list-style: none; padding: 0; }
                li { margin-bottom: 12px; padding: 8px; border-bottom: 1px solid #ddd; }
                .map { text-align: center; margin-bottom: 20px; }
                .map img { max-width: 600px; height: auto; border: 1px solid #ccc; border-radius: 8px; }
                .footer { position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; font-size: 10px; color: #555; }
                .footer a { text-decoration: none; color: #555; }
            </style>
        </head>
        <body>
            <h1>' . $titulo . '</h1>
            <div class="map">
                <img src="data:image/png;base64,' . $imagemBase64 . '" />
            </div>
            ' . $diasHtml . '
            <div class="footer">
                <a href="https://www.openstreetmap.org/copyright">© OpenStreetMap contributors</a>
            </div>
        </body>
        </html>
        ';

         $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        $pdfPath = "/PADS/visoes/pdfs/" . $codigoMd5 . ".pdf";
        file_put_contents("C:/xampp/htdocs" . $pdfPath, $pdfOutput);

        return $pdfPath;



    }

    public function editarPdf(string $imagemBase64, string $roteiroRaw, string $codigoMd5): string
{
    $roteiro = json_decode($roteiroRaw, true);

    $titulo = !empty($roteiro['titulo']) ? htmlspecialchars($roteiro['titulo']) : "(Roteiro sem título)";

    $contador = 1;
    $diasHtml = '';

    $dias = [];
    foreach (['novas','editadas','mantidas'] as $tipo) {
        if (!empty($roteiro[$tipo])) {
            foreach ($roteiro[$tipo] as $ponto) {
                $dia = $ponto['dia'] ?? '';
                if (!isset($dias[$dia])) {
                    $dias[$dia] = [];
                }
                $dias[$dia][] = $ponto;
            }
        }
    }

    ksort($dias);

    foreach ($dias as $dia => $pontos) {
        $dataFormatada = '';
        if (!empty($dia)) {
            try {
                $dt = new \DateTime($dia);
                $dataFormatada = $dt->format('d/m/Y');
            } catch (\Exception $e) {
                $dataFormatada = htmlspecialchars($dia);
            }
        }

        $diasHtml .= '<h2>Dia: ' . $dataFormatada . '</h2>';
        $diasHtml .= '<ul>';

        usort($pontos, function($a, $b) {
            return strcmp($a['entrada'] ?? '', $b['entrada'] ?? '');
        });

        foreach ($pontos as $ponto) {
            $diasHtml .= '<li>';
            $diasHtml .= '<strong>' . $contador . '°) ' . htmlspecialchars($ponto['nome']) . '</strong><br>';
            $diasHtml .= 'Estado: ' . htmlspecialchars($ponto['estado'] ?? '') . '<br>';

            if (!empty($ponto['endereco'])) {
                $diasHtml .= 'Endereço: ' . htmlspecialchars($ponto['endereco']) . '<br>';
            }
            if (!empty($ponto['horarioFuncionamento'])) {
                $diasHtml .= 'Horário de Funcionamento: ' . htmlspecialchars($ponto['horarioFuncionamento']) . '<br>';
            }
            if (!empty($ponto['telefone'])) {
                $diasHtml .= 'Telefone: ' . htmlspecialchars($ponto['telefone']) . '<br>';
            }
            if (!empty($ponto['email'])) {
                $diasHtml .= 'Email: ' . htmlspecialchars($ponto['email']) . '<br>';
            }
            if (!empty($ponto['website'])) {
                $diasHtml .= 'Website: ' . htmlspecialchars($ponto['website']) . '<br>';
            }

            $diasHtml .= 'Entrada: ' . htmlspecialchars($ponto['entrada'] ?? '') . ' - Saída: ' . htmlspecialchars($ponto['saida'] ?? '') . '<br>';

            if (!empty($ponto['observacoes'])) {
                $diasHtml .= '<em class="notas">Notas: ' . nl2br(htmlspecialchars($ponto['observacoes'])) . '</em>';
            }

            $diasHtml .= '</li>';
            $contador++;
        }

        $diasHtml .= '</ul>';
    }

    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; }
            h1 { text-align: center; color: #275cab; margin-bottom: 20px; }
            h2 { color: #333; margin-top: 15px; }
            .notas { display: block; white-space: pre-wrap; word-wrap: break-word; max-width: 100%; font-style: italic; }
            ul { list-style: none; padding: 0; }
            li { margin-bottom: 12px; padding: 8px; border-bottom: 1px solid #ddd; }
            .map { text-align: center; margin-bottom: 20px; }
            .map img { max-width: 600px; height: auto; border: 1px solid #ccc; border-radius: 8px; }
            .footer { position: fixed; bottom: 0; left: 0; width: 100%; text-align: center; font-size: 10px; color: #555; }
            .footer a { text-decoration: none; color: #555; }
        </style>
    </head>
    <body>
        <h1>' . $titulo . '</h1>
        <div class="map">
            <img src="data:image/png;base64,' . $imagemBase64 . '" />
        </div>
        ' . $diasHtml . '
        <div class="footer">
             <a href="https://www.openstreetmap.org/copyright">© OpenStreetMap contributors</a>
        </div>
    </body>
    </html>
    ';

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfOutput = $dompdf->output();

    $pdfPath = "/PADS/visoes/pdfs/" . $codigoMd5 . ".pdf";
    file_put_contents("C:/xampp/htdocs" . $pdfPath, $pdfOutput);

    return $pdfPath;
}

}

