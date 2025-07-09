<?php
// dashboard.php - Painel de Controle Principal (Versão Melhorada)

// --- Inicialização e Segurança ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);

// --- Definições da Página ---
$page_title = "Painel - Visão Geral";
$current_page = "Painel";

// --- Busca de Dados para o Dashboard ---
$stats = [
    'usuarios' => 0,
    'avaliacoes' => 0,
    'disciplinas' => 0,
    'cursos' => 0
];
$usuarios_por_nivel = [];
$mensagem_erro = null;

try {
    // Busca as estatísticas principais
    $stats['usuarios'] = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $stats['avaliacoes'] = $pdo->query("SELECT COUNT(id) FROM avaliacao")->fetchColumn();
    $stats['disciplinas'] = $pdo->query("SELECT COUNT(id) FROM disciplina")->fetchColumn();
    $stats['cursos'] = $pdo->query("SELECT COUNT(id) FROM cursos")->fetchColumn();

    // Busca dados para o gráfico de usuários por nível de acesso
    $stmt_niveis = $pdo->query("SELECT nivel_acesso, COUNT(*) as total FROM usuario GROUP BY nivel_acesso ORDER BY nivel_acesso");
    $usuarios_por_nivel = $stmt_niveis->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro de Banco de Dados: " . $e->getMessage();
    error_log("Erro no Dashboard: " . $e->getMessage());
}

// Prepara dados para o Chart.js
$nivel_labels = [];
$nivel_data = [];
if (!empty($usuarios_por_nivel)) {
    foreach ($usuarios_por_nivel as $nivel) {
        // Formata o nome para exibição (ex: 'aluno' vira 'Aluno')
        $nivel_labels[] = ucfirst($nivel['nivel_acesso']); 
        $nivel_data[] = $nivel['total'];
    }
}
$nivel_labels_json = json_encode($nivel_labels);
$nivel_data_json = json_encode($nivel_data);

// --- Inclusão dos Templates de Layout ---
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
    <?php else: ?>
        <!-- Seção de Cartões de Estatísticas -->
        <section class="dashboard-cards">
            <div class="card">
                <div class="card-icon"><i class="fas fa-users"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Usuários</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['usuarios']); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Avaliações</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['avaliacoes']); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-book"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Disciplinas</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['disciplinas']); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Cursos</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['cursos']); ?></div>
                </div>
            </div>
        </section>

        <!-- Seção de Gráficos e Outras Informações -->
        <section class="dashboard-main-content">
            <div class="chart-container">
                <h3>Distribuição de Usuários por Nível</h3>
                <canvas id="userAccessChart"></canvas>
            </div>
            <div class="recent-activity">
                <h3>Atividade Recente (Exemplo)</h3>
                <ul>
                    <li><i class="fas fa-user-plus"></i> Novo usuário 'Carlos' cadastrado.</li>
                    <li><i class="fas fa-file-signature"></i> Avaliação 'Prova Final de Cálculo' foi iniciada.</li>
                    <li><i class="fas fa-book-reader"></i> Disciplina 'Física Quântica' adicionada ao curso de Física.</li>
                    <li><i class="fas fa-times-circle"></i> Avaliação 'Teste Surpresa' foi encerrada.</li>
                </ul>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- Inclui a biblioteca Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('userAccessChart').getContext('2d');
    
    // Gradiente para a cor das barras
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(75, 192, 192, 0.8)');
    gradient.addColorStop(1, 'rgba(75, 192, 192, 0.2)');

    const userAccessChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo $nivel_labels_json; ?>,
            datasets: [{
                label: 'Número de Usuários',
                data: <?php echo $nivel_data_json; ?>,
                backgroundColor: gradient,
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                hoverBackgroundColor: 'rgba(75, 192, 192, 1)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        // Garante que os ticks sejam apenas números inteiros
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Oculta a legenda, já que o título do gráfico é suficiente
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 12
                    },
                    callbacks: {
                        label: function(context) {
                            return ` ${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
