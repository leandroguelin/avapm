<?php
// gerenciar_questionario.php - VERSÃO CORRIGIDA

// --- Configurações Iniciais e Segurança ---
$page_title = "Gerenciar Perguntas do Questionário";
require_once __DIR__ . '/includes/conexao.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Acesso negado.'];
    header('Location: index.php');
    exit();
}

// Lógica de Exclusão
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    $pergunta_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM questionario WHERE id = :id");
        $stmt->execute([':id' => $pergunta_id]);
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Pergunta excluída com sucesso!'];
    } catch (PDOException $e) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao excluir a pergunta.'];
    }
    header('Location: gerenciar_questionario.php');
    exit();
}

// Feedback e Lógica de Busca/Paginação
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);

$limite_por_pagina = 10;
$pagina_atual = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($pagina_atual - 1) * $limite_por_pagina;
$termo_pesquisa = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

$where_clause = '';
$parametros_sql = [];
if (!empty($termo_pesquisa)) {
    $where_clause = " WHERE (pergunta LIKE :termo OR descricao LIKE :termo OR categoria LIKE :termo)";
    $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%';
}

try {
    $stmt_total = $pdo->prepare("SELECT COUNT(id) FROM questionario " . $where_clause);
    $stmt_total->execute($parametros_sql);
    $total_perguntas = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_perguntas / $limite_por_pagina);
} catch (PDOException $e) { $total_paginas = 0; }

$perguntas = [];
try {
    $sql = "SELECT id, pergunta, descricao, categoria FROM questionario " . $where_clause . " ORDER BY categoria, pergunta ASC LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($parametros_sql as $key => &$val) $stmt->bindParam($key, $val);
    $stmt->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* ... */ }

// Se for requisição AJAX, carrega apenas a tabela e encerra
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    require __DIR__ . '/includes/templates/questionario_table_partial.php';
    exit();
}

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header"><h1><?php echo $page_title; ?></h1></header>
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Perguntas Cadastradas</h2>
            <a href="adicionar_pergunta.php" class="btn-primary-dashboard"><i class="fas fa-plus"></i> Adicionar Pergunta</a>
        </div>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>"><?php echo htmlspecialchars($mensagem_feedback); ?></div>
        <?php endif; ?>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Buscar por pergunta, categoria..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div id="tableContainer">
            <?php require __DIR__ . '/includes/templates/questionario_table_partial.php'; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function fetchQuestions(searchTerm, page) {
        const url = `gerenciar_questionario.php?q=${encodeURIComponent(searchTerm)}&pagina=${page}`;
        $.ajax({
            url: url,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            },
            success: function(response) {
                $('#tableContainer').html(response);
                // Atualiza a URL na barra de endereço do navegador
                window.history.pushState({path: url}, '', url);
            },
            error: function() {
                $('#tableContainer').html('<div class="alert alert-danger">Erro ao carregar os dados.</div>');
            }
        });
    }

    // Ação para o campo de busca
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        searchTimeout = setTimeout(() => {
            fetchQuestions(searchTerm, 1); // Sempre volta para a página 1 ao buscar
        }, 300);
    });

    // CORREÇÃO: Ação para os links de paginação
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        const url = new URL($(this).attr('href'), window.location.origin);
        const page = url.searchParams.get('pagina') || '1'; // Pega o número da página do link
        const searchTerm = $('#searchInput').val();
        
        fetchQuestions(searchTerm, page);
    });
});
</script>
