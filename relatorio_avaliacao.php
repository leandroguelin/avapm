<?php
// relatorio_avaliacao.php - VERSÃO FINAL COM FUNÇÃO RESTAURADA

require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { die("ID da avaliação inválido."); }
$avaliacao_id = (int)$_GET['id'];
$format = $_GET['format'] ?? 'html';


// =============================================================
// CORREÇÃO: A FUNÇÃO QUE ESTAVA FALTANDO FOI REINSERIDA AQUI
// =============================================================
function getQuickChartUrl($config, $width = 500, $height = 300) {
    $base_url = "https://quickchart.io/chart";
    // Adiciona uma cor de fundo branca para melhor visualização no PDF
    $config['backgroundColor'] = 'white';
    $config['width'] = $width;
    $config['height'] = $height;
    return $base_url . "?c=" . urlencode(json_encode($config));
}


try {
    // --- BUSCA DE DADOS ---
    $stmt_info = $pdo->prepare("SELECT a.*, c.nome as curso_nome, c.sigla as curso_sigla FROM avaliacao a JOIN cursos c ON a.curso_id = c.id WHERE a.id = :id");
    $stmt_info->execute([':id' => $avaliacao_id]);
    $avaliacao_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    if (!$avaliacao_info) { die("Avaliação não encontrada."); }

    $stmt_respostas = $pdo->prepare("SELECT * FROM respostas WHERE avaliacao_id = :id");
    $stmt_respostas->execute([':id' => $avaliacao_id]);
    $respostas = $stmt_respostas->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt_participantes = $pdo->prepare("SELECT COUNT(DISTINCT cpf_aluno) FROM respostas WHERE avaliacao_id = :id AND cpf_aluno != 'ANONIMO'");
    $stmt_participantes->execute([':id' => $avaliacao_id]);
    $total_participantes = $stmt_participantes->fetchColumn();

    // --- PROCESSAMENTO DE DADOS ---
    $stats = [];
    foreach ($respostas as $r) {
        $categoria = $r['categoria'];
        $avaliado_id = $r['avaliado'];
        $pergunta = $r['pergunta'];
        $resposta = (int)$r['resposta'];
        $observacao = $r['observacoes'];

        if (!isset($stats[$categoria][$avaliado_id])) {
            $stats[$categoria][$avaliado_id] = ['perguntas' => [], 'observacoes_gerais' => [], 'soma_geral' => 0, 'contagem_geral' => 0];
        }
        if (!isset($stats[$categoria][$avaliado_id]['perguntas'][$pergunta])) {
            $stats[$categoria][$avaliado_id]['perguntas'][$pergunta] = ['soma' => 0, 'contagem' => 0, 'observacoes' => []];
        }

        $stats[$categoria][$avaliado_id]['perguntas'][$pergunta]['soma'] += $resposta;
        $stats[$categoria][$avaliado_id]['perguntas'][$pergunta]['contagem']++;
        if (!empty($observacao)) {
            $stats[$categoria][$avaliado_id]['perguntas'][$pergunta]['observacoes'][] = $observacao;
        }
        $stats[$categoria][$avaliado_id]['soma_geral'] += $resposta;
        $stats[$categoria][$avaliado_id]['contagem_geral']++;
    }

    // --- CÁLCULO DE MÉDIAS E ENRIQUECIMENTO COM NOMES ---
    $academia_stats = [];
    if (!empty($stats['Academia']['Academia']['perguntas'])) {
        foreach ($stats['Academia']['Academia']['perguntas'] as $pergunta => $dados) {
            $academia_stats[$pergunta] = $dados;
            $academia_stats[$pergunta]['media'] = ($dados['contagem'] > 0) ? $dados['soma'] / $dados['contagem'] : 0;
        }
    }
    uasort($academia_stats, function($a, $b) { return ($b['media'] ?? 0) <=> ($a['media'] ?? 0); });
    
    $disciplina_stats = $stats['Disciplina'] ?? [];
    if (!empty($disciplina_stats)) {
        $stmt_nomes = $pdo->prepare("SELECT id, nome FROM disciplina WHERE id IN (".implode(',', array_keys($disciplina_stats)).")");
        $stmt_nomes->execute();
        $nomes_map = $stmt_nomes->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($disciplina_stats as $id => &$d_stats) {
            $d_stats['nome'] = $nomes_map[$id] ?? 'Disciplina Desconhecida';
            $d_stats['media'] = ($d_stats['contagem_geral'] > 0) ? $d_stats['soma_geral'] / $d_stats['contagem_geral'] : 0;
        } unset($d_stats);
    }
    uasort($disciplina_stats, function($a, $b) { return ($b['media'] ?? 0) <=> ($a['media'] ?? 0); });

    $professor_stats = $stats['Professor'] ?? [];
    if (!empty($professor_stats)) {
        $stmt_nomes = $pdo->prepare("SELECT id, nome FROM usuario WHERE id IN (".implode(',', array_keys($professor_stats)).")");
        $stmt_nomes->execute();
        $nomes_map = $stmt_nomes->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach($professor_stats as $id => &$p_stats) {
            $p_stats['nome'] = $nomes_map[$id] ?? 'Professor Desconhecido';
            $p_stats['media_geral'] = ($p_stats['contagem_geral'] > 0) ? $p_stats['soma_geral'] / $p_stats['contagem_geral'] : 0;
            foreach($p_stats['perguntas'] as &$perg_stats){
                $perg_stats['media'] = ($perg_stats['contagem'] > 0) ? $perg_stats['soma'] / $perg_stats['contagem'] : 0;
            }
        } unset($p_stats); unset($perg_stats);
    }
    uasort($professor_stats, function($a, $b) { return ($b['media_geral'] ?? 0) <=> ($a['media_geral'] ?? 0); });
    
    // --- PREPARA DADOS PARA GRÁFICOS E PDF ---
    $chart_urls = [];
    if (!empty($academia_stats)) {
        $config = ['type' => 'horizontalBar', 'data' => ['labels' => array_keys($academia_stats), 'datasets' => [['data' => array_column($academia_stats, 'media')]]], 'options' => ['scales' => ['xAxes' => [['ticks' => ['min' => 0, 'max' => 10]]]]]];
        $chart_urls['academia'] = getQuickChartUrl($config, 500, count($academia_stats) * 35 + 40);
    }
    if (!empty($disciplina_stats)) {
        $config = ['type' => 'horizontalBar', 'data' => ['labels' => array_column($disciplina_stats, 'nome'), 'datasets' => [['data' => array_column($disciplina_stats, 'media')]]], 'options' => ['scales' => ['xAxes' => [['ticks' => ['min' => 0, 'max' => 10]]]]]];
        $chart_urls['disciplinas'] = getQuickChartUrl($config, 500, count($disciplina_stats) * 35 + 40);
    }
    if (!empty($professor_stats)) {
        $config = ['type' => 'horizontalBar', 'data' => ['labels' => array_column($professor_stats, 'nome'), 'datasets' => [['data' => array_column($professor_stats, 'media_geral')]]], 'options' => ['scales' => ['xAxes' => [['ticks' => ['min' => 0, 'max' => 10]]]]]];
        $chart_urls['professores'] = getQuickChartUrl($config, 500, count($professor_stats) * 35 + 40);
    }

} catch (PDOException $e) { die("Erro ao processar dados da avaliação: " . $e->getMessage()); }

// --- RENDERIZAÇÃO ---
switch ($format) {
    case 'pdf':
        $is_pdf_export = true;
        ob_start();
        include __DIR__ . '/includes/templates/_view_relatorio_html.php';
        $html = ob_get_clean();
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("relatorio_".$avaliacao_info['codigo'].".pdf", ["Attachment" => false]);
        exit();
    default:
        $is_pdf_export = false; // Garante que a variável está definida para a view HTML
        include __DIR__ . '/includes/templates/_view_relatorio_html.php';
        break;
}
?>