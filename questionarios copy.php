<?php
// gerenciar_questionario.php - VERSÃO REESTRUTURADA E MODERNA

// --- Configurações Iniciais ---
$page_title = "Gerenciar Perguntas do Questionário";
require_once __DIR__ . '/includes/conexao.php'; // A conexão já deve iniciar a sessão
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Lógica de Permissão (exemplo, ajuste se necessário)
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você não tem permissão para acessar esta página.'];
    header('Location: index.php');
    exit();
}

// Lógica para Excluir uma Pergunta
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    $pergunta_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM questionario WHERE id = :id");
        $stmt->execute([':id' => $pergunta_id]);
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Pergunta excluída com sucesso!'];
    } catch (PDOException $e) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao excluir a pergunta.'];
        error_log("Erro ao excluir pergunta: " . $e->getMessage());
    }
    header('Location: gerenciar_questionario.php');
    exit();
}

// Processar feedback da sessão
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);

// Lógica de Busca e Paginação
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$limite_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;
$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$where_clause = '';
$parametros_sql = [];
if (!empty($termo_pesquisa)) {
    $where_clause = " WHERE (pergunta LIKE :termo OR descricao LIKE :termo OR categoria LIKE :termo)";
    $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%';
}

// Buscar total de perguntas para paginação
try {
    $stmt_total = $pdo->prepare("SELECT COUNT(id) FROM questionario " . $where_clause);
    $stmt_total->execute($parametros_sql);
    $total_perguntas = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_perguntas / $limite_por_pagina);
} catch (PDOException $e) {
    error_log("Erro ao contar perguntas: " . $e->getMessage());
    $total_paginas = 0;
}

// Buscar perguntas para a página atual
$perguntas = [];
try {
    $sql = "SELECT id, pergunta, descricao, categoria FROM questionario " . $where_clause . " ORDER BY categoria, pergunta ASC LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($parametros_sql as $key => &$val) $stmt->bindParam($key, $val);
    $stmt->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao carregar perguntas: " . $e->getMessage());
}

// Se for requisição AJAX, carrega apenas a tabela e encerra
if ($is_ajax_request) {
    require_once __DIR__ . '/includes/templates/questionario_table_partial.php';
    exit();
}

// Se for carregamento normal, carrega a página inteira
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Perguntas Cadastradas</h2>
            <a href="adicionar_pergunta.php" class="btn-primary-dashboard">
                <i class="fas fa-plus"></i> Adicionar Pergunta
            </a>
        </div>

        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($mensagem_feedback); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Buscar por pergunta, categoria..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
            <i class="fas fa-search"></i>
        </div>

        <div id="tableContainer">
            <?php require_once __DIR__ . '/includes/templates/questionario_table_partial.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function fetchQuestions(searchTerm, page = 1) {
        $.ajax({
            url: `gerenciar_questionario.php?q=${encodeURIComponent(searchTerm)}&pagina=${page}`,
            type: 'GET',
            success: function(response) {
                $('#tableContainer').html(response);
            },
            error: function() {
                $('#tableContainer').html('<div class="alert alert-danger">Erro ao carregar os dados. Tente novamente.</div>');
            }
        });
    }

    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        searchTimeout = setTimeout(() => {
            fetchQuestions(searchTerm, 1);
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + `?q=${encodeURIComponent(searchTerm)}&pagina=1`;
            window.history.pushState({path: newUrl}, '', newUrl);
        }, 300);
    });

    $(document).on('click', '.pagination-link', function(e) {
        e.preventDefault();
        const url = new URL($(this).attr('href'), window.location.origin);
        const page = url.searchParams.get('pagina');
        const searchTerm = $('#searchInput').val();
        fetchQuestions(searchTerm, page);
        const newUrl = url.protocol + "//" + url.host + url.pathname + url.search;
        window.history.pushState({path: newUrl}, '', newUrl);
    });
});
</script>