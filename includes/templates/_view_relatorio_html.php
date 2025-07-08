<?php
// includes/templates/_view_relatorio_html.php - VERSÃO FINAL COM TODAS AS CORREÇÕES

$is_pdf_export = $is_pdf_export ?? false;

function render_observacoes($observacoes_array) {
    if (!empty($observacoes_array)) {
        echo '<h6 class="mt-4 font-weight-bold">Observações e Justificativas:</h6>';
        // Usando um estilo mais limpo para as observações
        foreach ($observacoes_array as $obs) {
            echo "<blockquote><p class=\"mb-0\"><em>" . htmlspecialchars($obs) . "</em></p></blockquote>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório: <?php echo htmlspecialchars($avaliacao_info['nome']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <?php if (!$is_pdf_export): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <?php endif; ?>
    <style>
        body { font-family: sans-serif; background-color: #f8f9fa; }
        .report-container { max-width: 1400px; margin: 20px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 0 20px rgba(0,0,0,0.07); }
        .report-header { text-align: center; margin-bottom: 40px; }
        .report-header img { max-height: 80px; margin-bottom: 20px; }
        .report-header h2 { font-weight: 700; color: #343a40; }
        .section-title { text-align: center; font-size: 1.8rem; font-weight: 300; margin-top: 40px; margin-bottom: 30px; letter-spacing: 1px; color: #007bff; border-bottom: 1px solid #dee2e6; padding-bottom: 15px; }
        .chart-card { padding: 20px; text-align: center; border: 1px solid #eee; border-radius: 8px; height: 100%; }
        .chart-container { position: relative; width: 100%; }
        blockquote { background: #f1f3f5; border-left: 5px solid #ced4da; margin: 10px 0; padding: 15px; font-style: italic; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <img src="imagens/sistema/logo_exemplo.png" alt="Logo do Sistema">
            <h2><?php echo htmlspecialchars($avaliacao_info['nome']); ?></h2>
            <p class="lead"><strong>Curso:</strong> <?php echo htmlspecialchars($avaliacao_info['curso_nome']); ?> (<?php echo htmlspecialchars($avaliacao_info['curso_sigla']); ?>)</p>
            <p class="text-muted">Período: <?php echo (new DateTime($avaliacao_info['data_inicio']))->format('d/m/Y'); ?> a <?php echo (new DateTime($avaliacao_info['data_final']))->format('d/m/Y'); ?> | <strong>Total de Participantes:</strong> <?php echo $total_participantes; ?></p>
        </div>

        <h3 class="section-title">Gráficos de Desempenho</h3>
        <div class="row">
            <div class="col-12 mb-4">
                <div class="chart-card">
                    <h5 class="mb-3">Avaliação da Academia</h5>
                    <div class="chart-container" style="height: <?php echo max(200, count($academia_stats) * 40); ?>px;">
                        <?php if($is_pdf_export): ?><img src="<?php echo $chart_urls['academia']; ?>"><?php else: ?><canvas id="academiaChart"></canvas><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <h5 class="mb-3">Desempenho das Disciplinas</h5>
                    <div class="chart-container" style="height: <?php echo max(200, count($disciplina_stats) * 40); ?>px;">
                        <?php if($is_pdf_export): ?><img src="<?php echo $chart_urls['disciplinas']; ?>"><?php else: ?><canvas id="disciplinasChart"></canvas><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <h5 class="mb-3">Desempenho dos Professores</h5>
                    <div class="chart-container" style="height: <?php echo max(200, count($professor_stats) * 40); ?>px;">
                        <?php if($is_pdf_export): ?><img src="<?php echo $chart_urls['professores']; ?>"><?php else: ?><canvas id="professoresChart"></canvas><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="details-section">
            <h3 class="section-title">Análise Detalhada por Professor</h3>
            <?php if (empty($professor_stats)): ?>
                <div class="alert alert-secondary">Nenhuma avaliação de professor foi registrada.</div>
            <?php else: ?>
                <?php foreach($professor_stats as $professor): ?>
                    <div class="mb-4 p-3 border rounded">
                        <h4><?php echo htmlspecialchars($professor['nome']); ?></h4>
                        <p class="text-muted">Média Geral: <strong><?php echo number_format($professor['media_geral'], 2); ?></strong></p>
                        
                        <?php
                        $all_obs_prof = [];
                        if (isset($professor['perguntas']) && is_array($professor['perguntas'])) {
                            foreach($professor['perguntas'] as $stats) {
                                if(!empty($stats['observacoes'])) {
                                    $all_obs_prof = array_merge($all_obs_prof, $stats['observacoes']);
                                }
                            }
                        }
                        
                        // CORREÇÃO 1: A chamada para a função que exibe as observações foi restaurada
                        if (empty($all_obs_prof)) {
                            echo "<p>Nenhuma observação foi registrada para este professor.</p>";
                        } else {
                            render_observacoes($all_obs_prof);
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$is_pdf_export): ?>
    <script>
        // Registra o plugin de datalabels globalmente
        Chart.register(ChartDataLabels);

        function createHorizontalBarChart(canvasId, labels, data) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, max: 10, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false },
                        // Configuração para exibir os valores nas barras
                        datalabels: {
                            // CORREÇÃO 2: Cor do texto alterada para escura para melhor legibilidade
                            color: '#343a40',
                            // CORREÇÃO 3: Alinhamento ajustado para manter o texto dentro da barra
                            anchor: 'end',
                            align: 'end',
                            offset: -8, // Pequeno recuo da borda
                            padding: 0,
                            font: { weight: 'bold' },
                            formatter: function(value) {
                                return value.toFixed(2).replace('.', ','); // Formata com vírgula
                            }
                        }
                    }
                }
            });
        }
        createHorizontalBarChart('academiaChart', <?php echo json_encode(array_keys($academia_stats)); ?>, <?php echo json_encode(array_column($academia_stats, 'media')); ?>);
        createHorizontalBarChart('disciplinasChart', <?php echo json_encode(array_column($disciplina_stats, 'nome')); ?>, <?php echo json_encode(array_column($disciplina_stats, 'media')); ?>);
        createHorizontalBarChart('professoresChart', <?php echo json_encode(array_column($professor_stats, 'nome')); ?>, <?php echo json_encode(array_column($professor_stats, 'media_geral')); ?>);
    </script>
    <?php endif; ?>
</body>
</html>