<?php
// gerenciar_avaliacoes.php - VERSÃO FINAL COM LÓGICA DE TEXTO (Ativa/Inativa)

// --- Configurações Iniciais e Permissões ---
$page_title = "Gerenciar Avaliações";
require_once __DIR__ . '/includes/conexao.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você não tem permissão.'];
    header('Location: index.php');
    exit();
}

// =====================================================================
// MANIPULADORES DE AÇÕES
// =====================================================================

// --- Ação para Mudar Situação via AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_situacao') {
    header('Content-Type: application/json');
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $situacao_atual = filter_input(INPUT_POST, 'situacao', FILTER_SANITIZE_STRING);

    if ($id && $situacao_atual) {
        // CORREÇÃO: A lógica volta a alternar entre os textos 'Ativa' e 'Inativa'
        $nova_situacao = ($situacao_atual === 'Ativa') ? 'Inativa' : 'Ativa';
        try {
            $stmt = $pdo->prepare("UPDATE avaliacao SET situacao = :nova_situacao WHERE id = :id");
            $stmt->execute([':nova_situacao' => $nova_situacao, ':id' => $id]);
            echo json_encode(['success' => true, 'nova_situacao' => $nova_situacao]);
        } catch (PDOException $e) {
            error_log("Erro ao mudar situação: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro no banco de dados.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    }
    exit();
}

// --- Ação para Excluir uma Avaliação ---
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    $id_para_excluir = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM avaliacao WHERE id = :id");
        $stmt->execute([':id' => $id_para_excluir]);
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Avaliação excluída com sucesso!'];
    } catch (PDOException $e) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao excluir avaliação.'];
        error_log("Erro ao excluir avaliação: " . $e->getMessage());
    }
    header('Location: gerenciar_avaliacoes.php');
    exit();
}

// --- Ação para Adicionar/Editar uma Avaliação (do Modal) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_SANITIZE_NUMBER_INT);
    $codigo = strtoupper(trim(filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_STRING)));
    $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
    $data_inicio = filter_input(INPUT_POST, 'data_inicio');
    $data_final = filter_input(INPUT_POST, 'data_final');
    $erros = [];
    if(strlen($codigo) != 5) $erros[] = "O código deve ter exatamente 5 caracteres.";
    if(empty($nome)) $erros[] = "O nome da avaliação é obrigatório.";
    if(empty($curso_id)) $erros[] = "É necessário selecionar um curso.";
    if (empty($erros)) {
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE avaliacao SET curso_id = :curso_id, codigo = :codigo, nome = :nome, data_inicio = :data_inicio, data_final = :data_final WHERE id = :id");
            } else {
                // CORREÇÃO: Insere 'Inativa' como texto padrão
                $stmt = $pdo->prepare("INSERT INTO avaliacao (curso_id, codigo, nome, data_inicio, data_final, situacao) VALUES (:curso_id, :codigo, :nome, :data_inicio, :data_final, 'Inativa')");
            }
            $params = [':curso_id' => $curso_id, ':codigo' => $codigo, ':nome' => $nome, ':data_inicio' => $data_inicio, ':data_final' => $data_final];
            if ($id) $params[':id'] = $id;
            $stmt->execute($params);
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Avaliação salva com sucesso!'];
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro: Já existe uma avaliação com este código.'];
            } else { $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao salvar a avaliação.']; error_log("Erro ao salvar avaliação: " . $e->getMessage()); }
        }
    } else { $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => implode('<br>', $erros)]; }
    header('Location: gerenciar_avaliacoes.php');
    exit();
}

// ... Restante do arquivo (lógica de carregamento de dados, HTML e JavaScript) ...
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);
$cursos = $pdo->query("SELECT id, sigla, nome FROM cursos ORDER BY sigla ASC")->fetchAll(PDO::FETCH_ASSOC);
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$limite_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;
$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$where_clause = '';
$parametros_sql = [];
if (!empty($termo_pesquisa)) {
    $where_clause = " WHERE (a.codigo LIKE :termo OR a.nome LIKE :termo OR c.nome LIKE :termo)";
    $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%';
}
$total_paginas = 0;
$avaliacoes = [];
try {
    $sql_total = "SELECT COUNT(a.id) FROM avaliacao a LEFT JOIN cursos c ON a.curso_id = c.id " . $where_clause;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($parametros_sql);
    $total_avaliacoes = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_avaliacoes / $limite_por_pagina);
    $sql = "SELECT a.*, c.nome AS curso_nome FROM avaliacao a LEFT JOIN cursos c ON a.curso_id = c.id " . $where_clause . " ORDER BY a.data_inicio DESC, a.nome ASC LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($parametros_sql as $key => &$val) $stmt->bindParam($key, $val);
    $stmt->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log("Erro ao carregar avaliações: " . $e->getMessage()); }
if ($is_ajax_request) { require_once __DIR__ . '/includes/templates/avaliacao_table_partial.php'; exit(); }
require_once __DIR__ . '/includes/templates/header_dashboard.php';
?>
<style>.select2-container--open { z-index: 1055 !important; }</style>
<?php
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>
<div class="main-content-dashboard">
    <header class="dashboard-header"><h1><?php echo $page_title; ?></h1></header>
    <div class="dashboard-section">
        <div class="section-header"><h2>Avaliações Cadastradas</h2><button class="btn-primary-dashboard" data-toggle="modal" data-target="#avaliacaoModal" id="addAvaliacaoBtn"><i class="fas fa-plus"></i> Adicionar Avaliação</button></div>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?> alert-dismissible fade show" role="alert"><?php echo htmlspecialchars($mensagem_feedback); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
        <?php endif; ?>
        <div class="search-box"><input type="text" id="searchInput" placeholder="Buscar por código, nome ou curso..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>"><i class="fas fa-search"></i></div>
        <div id="tableContainer"><?php require_once __DIR__ . '/includes/templates/avaliacao_table_partial.php'; ?></div>
    </div>
</div>
<div class="modal fade" id="avaliacaoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="avaliacaoForm" action="gerenciar_avaliacoes.php" method="POST">
                <div class="modal-header"><h5 class="modal-title" id="avaliacaoModalLabel">Adicionar Avaliação</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    <input type="hidden" name="form_action" value="save"><input type="hidden" id="id" name="id">
                    <div class="form-group"><label for="codigo">Código (5 caracteres)</label><input type="text" class="form-control" id="codigo" name="codigo" required maxlength="5" minlength="5" style="text-transform:uppercase"></div>
                    <div class="form-group"><label for="nome">Nome da Avaliação</label><input type="text" class="form-control" id="nome" name="nome" required></div>
                    <div class="form-group"><label for="curso_id">Curso</label><select class="form-control" id="curso_id" name="curso_id" required><option value=""></option><?php foreach ($cursos as $curso): ?><option value="<?php echo $curso['id']; ?>"><?php echo htmlspecialchars($curso['sigla'] . ' - ' . $curso['nome']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="data_inicio">Início</label><input type="datetime-local" class="form-control" id="data_inicio" name="data_inicio" required></div>
                        <div class="form-group col-md-6"><label for="data_final">Fim</label><input type="datetime-local" class="form-control" id="data_final" name="data_final" required></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn-primary-dashboard">Salvar</button></div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#curso_id').select2({ placeholder: 'Selecione um curso', dropdownParent: $('#avaliacaoModal .modal-content'), dropdownAutoWidth: true, width: '100%'});
    function generateRandomCode(length) { const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; let result = ''; for (let i = 0; i < length; i++) { result += chars.charAt(Math.floor(Math.random() * chars.length)); } return result; }
    function formatarDataParaInput(date) { const y = date.getFullYear(); const m = String(date.getMonth() + 1).padStart(2, '0'); const d = String(date.getDate()).padStart(2, '0'); return `${y}-${m}-${d}T00:00`;}
    $('#addAvaliacaoBtn').on('click', function() {
        $('#avaliacaoForm')[0].reset(); $('#id').val(''); $('#avaliacaoModalLabel').text('Adicionar Avaliação'); $('#curso_id').val(null).trigger('change');
        $('#codigo').val(generateRandomCode(5));
        const dataInicio = new Date(); const dataFinal = new Date(); dataFinal.setMonth(dataFinal.getMonth() + 3);
        $('#data_inicio').val(formatarDataParaInput(dataInicio)); $('#data_final').val(formatarDataParaInput(dataFinal));
    });
    $(document).on('click', '.edit-avaliacao-btn', function() {
        const data = $(this).data('avaliacao'); $('#avaliacaoForm')[0].reset(); $('#avaliacaoModalLabel').text('Editar Avaliação');
        $('#id').val(data.id); $('#codigo').val(data.codigo); $('#nome').val(data.nome); $('#curso_id').val(data.curso_id).trigger('change');
        $('#data_inicio').val(data.data_inicio ? data.data_inicio.replace(' ', 'T') : ''); $('#data_final').val(data.data_final ? data.data_final.replace(' ', 'T') : '');
    });
    $(document).on('click', '.toggle-situacao-btn', function() {
        const button = $(this); const id = button.data('id'); const situacaoAtual = button.data('situacao');
        button.prop('disabled', true);
        $.ajax({
            url: 'gerenciar_avaliacoes.php', type: 'POST', data: { action: 'toggle_situacao', id: id, situacao: situacaoAtual }, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // CORREÇÃO: A lógica agora compara com o texto 'Ativa'
                    const isAtiva = (response.nova_situacao === 'Ativa');
                    button.data('situacao', response.nova_situacao);
                    button.removeClass('btn-success btn-secondary').addClass(isAtiva ? 'btn-success' : 'btn-secondary');
                    button.find('i').removeClass('fa-toggle-on fa-toggle-off').addClass(isAtiva ? 'fa-toggle-on' : 'fa-toggle-off');
                    button.find('.status-text').text(response.nova_situacao);
                    button.attr('title', isAtiva ? 'Ativa (Clique para desativar)' : 'Inativa (Clique para ativar)');
                } else { alert('Erro ao mudar a situação.'); }
            },
            error: function() { alert('Erro de comunicação.'); },
            complete: function() { button.prop('disabled', false); }
        });
    });
    function fetchData(searchTerm, page = 1) { $.ajax({ url: `gerenciar_avaliacoes.php?q=${encodeURIComponent(searchTerm)}&pagina=${page}`, type: 'GET', success: function(response) { $('#tableContainer').html(response); }, error: function() { $('#tableContainer').html('<div class="alert alert-danger">Erro ao carregar dados.</div>');} }); }
    let searchTimeout;
    $('#searchInput').on('keyup', function() { clearTimeout(searchTimeout); const searchTerm = $(this).val(); searchTimeout = setTimeout(() => { fetchData(searchTerm, 1); }, 300); });
    $(document).on('click', '.pagination-link', function(e) { e.preventDefault(); const url = new URL($(this).attr('href'), window.location.origin); fetchData($('#searchInput').val(), url.searchParams.get('pagina')); });
});
</script>