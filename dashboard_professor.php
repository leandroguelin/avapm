<?php
// dashboard_professor.php

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Permissão ---
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);


// --- Lógica de Visualização ---
// Se um admin ou gerente estiver visualizando o perfil de um professor específico
$professor_id_alvo = $_SESSION['usuario_id']; // Padrão: o próprio usuário logado
$is_viewing_other = false;
if (in_array(($_SESSION['nivel_acesso'] ?? ''), ['ADMINISTRADOR', 'GERENTE']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $professor_id_alvo = (int)$_GET['id'];
    $is_viewing_other = true;
}

// --- Definição de Variáveis para o Layout ---
$page_title = "Meu Desempenho";
$current_page = "Minhas Avaliações"; // Define a página atual para a sidebar
$nome_usuario_logado = $_SESSION['nome_usuario'];

if ($is_viewing_other) {
    // Busca o nome do professor alvo para exibir no título
    $stmt_nome = $pdo->prepare("SELECT nome FROM usuario WHERE id = :id");
    $stmt_nome->execute([':id' => $professor_id_alvo]);
    $nome_professor_alvo = $stmt_nome->fetchColumn();
    if ($nome_professor_alvo) {
        $page_title = "Desempenho de: " . htmlspecialchars($nome_professor_alvo);
    }
}

// =============================================================
// BUSCA E PROCESSAMENTO DE DADOS
// =============================================================
$resultados_por_curso = [];
try {
    // Esta consulta SQL busca todas as respostas dadas ao professor alvo,
    // calcula a média para cada pergunta e agrupa os resultados por curso.
    $stmt = $pdo->prepare("
        SELECT
            c.id AS curso_id,
            c.nome AS curso_nome,
            c.sigla AS curso_sigla,
            r.pergunta,
            AVG(r.resposta) AS media_pergunta
        FROM
            respostas r
        JOIN
            cursos c ON r.curso_sigla = c.sigla
        WHERE
            r.categoria = 'Professor'
            AND r.avaliado = :id_professor_alvo
        GROUP BY
            c.id, c.nome, c.sigla, r.pergunta
        ORDER BY
            c.nome ASC, r.pergunta ASC
    ");
    $stmt->execute([':id_professor_alvo' => $professor_id_alvo]);
    $resultados_query = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organiza os resultados em um array aninhado para facilitar a exibição
    foreach ($resultados_query as $row) {
        $curso_id = $row['curso_id'];
        if (!isset($resultados_por_curso[$curso_id])) {
            $resultados_por_curso[$curso_id] = [
                'curso_nome' => $row['curso_nome'],
                'curso_sigla' => $row['curso_sigla'],
                'perguntas' => []
            ];
        }
        $resultados_por_curso[$curso_id]['perguntas'][] = [
            'texto' => $row['pergunta'],
            'media' => $row['media_pergunta']
        ];
    }

} catch (PDOException $e) {
    // Em caso de erro, a página não quebra, apenas exibe uma mensagem.
    $erro_db = "Erro ao carregar os dados de avaliação: " . $e->getMessage();
    error_log($erro_db);
}


// Inclusão dos Templates do Dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

// Função auxiliar para definir a cor da barra de progresso com base na nota
function getProgressBarColor($nota) {
    if ($nota >= 8) return 'bg-success'; // Verde para notas altas
    if ($nota >= 5) return 'bg-warning'; // Amarelo para notas médias
    return 'bg-danger'; // Vermelho para notas baixas
}
?>

<div class="main-content-dashboard">
    <div class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if (!$is_viewing_other): ?>
            <p class="lead" style="color: #6c757d;">Aqui estão os resultados consolidados das suas avaliações, agrupados por curso.</p>
        <?php endif; ?>
    </div>

    <?php if (isset($erro_db)): ?>
        <div class="alert alert-danger">Não foi possível carregar os resultados. Tente novamente mais tarde.</div>
    <?php elseif (empty($resultados_por_curso)): ?>
        <div class="alert alert-info">Ainda não há avaliações de desempenho registradas para este professor.</div>
    <?php else: ?>
        <?php foreach ($resultados_por_curso as $curso): ?>
            <div class="dashboard-section">
                <h2>
                    <i class="fas fa-graduation-cap text-primary"></i> 
                    Curso: <?php echo htmlspecialchars($curso['curso_nome']); ?> 
                    (<?php echo htmlspecialchars($curso['curso_sigla']); ?>)
                </h2>
                
                <div class="list-group list-group-flush">
                    <?php foreach ($curso['perguntas'] as $pergunta): ?>
                        <div class="list-group-item">
                            <div class="mb-2"><strong><?php echo htmlspecialchars($pergunta['texto']); ?></strong></div>
                            <div class="d-flex align-items-center">
                                <div class="progress" style="height: 25px; flex-grow: 1;">
                                    <div class="progress-bar <?php echo getProgressBarColor($pergunta['media']); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo ($pergunta['media'] * 10); ?>%;" 
                                         aria-valuenow="<?php echo $pergunta['media']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="10">
                                    </div>
                                </div>
                                <strong class="ml-3" style="min-width: 50px; text-align: right; font-size: 1.1rem;"><?php echo number_format($pergunta['media'], 2); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
    .dashboard-section {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }
    .dashboard-section h2 {
        color: #343a40;
        margin-top: 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
    }
    .dashboard-section h2 i {
        margin-right: 12px;
    }
    .progress {
        background-color: #e9ecef;
        border-radius: .375rem;
    }
    .progress-bar {
        font-weight: bold;
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .list-group-item {
        border-color: #f0f0f0;
    }
</style>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
