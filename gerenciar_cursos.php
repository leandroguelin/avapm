<?php
// htdocs/avapm2/gerenciar_cursos.php - VERSÃO FINAL COM EXCLUSÃO

// --- Configurações Iniciais ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$page_title = "Gerenciar Cursos";
require_once __DIR__ . '/includes/conexao.php';
$allowed_access_levels = ['Administrador', 'Gerente'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você não tem permissão para acessar esta página.'];
    header('Location: index.php');
    exit();
}

// =====================================================================
// LÓGICA PARA EXCLUIR UM CURSO (NOVO BLOCO ADICIONADO)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    $curso_id_para_excluir = (int)$_GET['id'];

    // Usamos uma transação para garantir que ambas as exclusões (grade e curso)
    // ocorram com sucesso. Se uma falhar, nenhuma é executada.
    $pdo->beginTransaction();

    try {
        // Passo 1: Excluir os registros da tabela 'filha' (grade_curso)
        $stmt_grade = $pdo->prepare("DELETE FROM grade_curso WHERE curso_id = :curso_id");
        $stmt_grade->execute([':curso_id' => $curso_id_para_excluir]);

        // Passo 2: Excluir o registro da tabela 'mãe' (cursos)
        $stmt_curso = $pdo->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt_curso->execute([':id' => $curso_id_para_excluir]);

        // Se tudo deu certo, confirma a transação
        $pdo->commit();

        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'success',
            'texto' => 'Curso e sua grade foram excluídos com sucesso!'
        ];

    } catch (PDOException $e) {
        // Se algo deu errado, desfaz tudo
        $pdo->rollBack();

        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => 'Erro ao excluir o curso. Tente novamente.'
        ];
        
        // Loga o erro real para o desenvolvedor
        error_log("Erro ao excluir curso ID {$curso_id_para_excluir}: " . $e->getMessage());
    }

    // Redireciona de volta para a página principal para mostrar a mensagem
    header('Location: gerenciar_cursos.php');
    exit();
}

// --- Processar feedback da sessão ---
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);


// =====================================================================
// CARREGAR DADOS PARA DROPDOWNS
// =====================================================================
$disciplinas = [];
$professores = [];
try {
    $stmt_disciplinas = $pdo->query("SELECT id, nome, sigla FROM disciplina ORDER BY sigla ASC");
    $disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);

    $stmt_professores = $pdo->prepare("
        SELECT id, TRIM(CONCAT_WS(' ', patente, rg, nome)) AS nome_formatado 
        FROM usuario 
        WHERE nivel_acesso != 'aluno' 
        ORDER BY nome ASC
    ");
    $stmt_professores->execute();
    $professores = $stmt_professores->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar dropdowns: " . $e->getMessage());
    $mensagem_feedback = "Aviso: Erro ao carregar opções.";
    $feedback_tipo = 'warning';
}

// Lógica de POST, Busca e Paginação
$curso_para_modal = null;
$show_modal_on_load = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_SANITIZE_NUMBER_INT);
    $sigla = trim(filter_input(INPUT_POST, 'sigla', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $nome_curso = trim(filter_input(INPUT_POST, 'nome_curso', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $data_inicio = filter_input(INPUT_POST, 'data_inicio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $data_fim = filter_input(INPUT_POST, 'data_fim', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $data_avaliacao = filter_input(INPUT_POST, 'data_avaliacao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $horas = filter_input(INPUT_POST, 'horas', FILTER_SANITIZE_NUMBER_INT);
    $disciplinas_grade = $_POST['disciplina_id'] ?? [];
    $professores_grade = $_POST['professor_id'] ?? [];
    $erros = [];
    if (empty($sigla)) $erros[] = 'A sigla do curso é obrigatória.';
    if (empty($nome_curso)) $erros[] = 'O nome do curso é obrigatório.';
    $data_inicio_db = DateTime::createFromFormat('d/m/Y', $data_inicio);
    $data_fim_db = DateTime::createFromFormat('d/m/Y', $data_fim);
    $data_avaliacao_db = DateTime::createFromFormat('d/m/Y', $data_avaliacao);
    if (!$data_inicio_db || $data_inicio_db->format('d/m/Y') !== $data_inicio) $erros[] = 'Formato de Data de Início inválido (use dd/mm/aaaa).';
    if (!$data_fim_db || $data_fim_db->format('d/m/Y') !== $data_fim) $erros[] = 'Formato de Data de Fim inválido (use dd/mm/aaaa).';
    if (!$data_avaliacao_db || $data_avaliacao_db->format('d/m/Y') !== $data_avaliacao) $erros[] = 'Formato de Data de Avaliação inválido (use dd/mm/aaaa).';
    if (empty($erros) && $data_inicio_db > $data_fim_db) $erros[] = 'A data de início não pode ser posterior à data de fim.';
    if (count($disciplinas_grade) !== count(array_unique($disciplinas_grade))) $erros[] = 'Não é possível adicionar a mesma disciplina múltiplas vezes.';
    if (empty($erros)) {
        $pdo->beginTransaction();
        try {
            if ($curso_id) { $stmt = $pdo->prepare("UPDATE cursos SET sigla = :sigla, nome = :nome_curso, data_inicio = :data_inicio, data_fim = :data_fim, data_avaliacao = :data_avaliacao, horas = :horas WHERE id = :id");
            } else { $stmt = $pdo->prepare("INSERT INTO cursos (sigla, nome, data_inicio, data_fim, data_avaliacao, horas) VALUES (:sigla, :nome_curso, :data_inicio, :data_fim, :data_avaliacao, :horas)");}
            $params = [':sigla' => $sigla,':nome_curso' => $nome_curso,':data_inicio' => $data_inicio_db->format('Y-m-d'),':data_fim' => $data_fim_db->format('Y-m-d'),':data_avaliacao' => $data_avaliacao_db->format('Y-m-d'),':horas' => $horas];
            if ($curso_id) { $params[':id'] = $curso_id; }
            $stmt->execute($params);
            if (!$curso_id) $curso_id = $pdo->lastInsertId();
            $stmt_delete_grade = $pdo->prepare("DELETE FROM grade_curso WHERE curso_id = :curso_id");
            $stmt_delete_grade->execute([':curso_id' => $curso_id]);
            if (!empty($disciplinas_grade)) {
                $stmt_insert_grade = $pdo->prepare("INSERT INTO grade_curso (curso_id, disciplina_id, usuario_id) VALUES (:curso_id, :disciplina_id, :professor_id)");
                foreach ($disciplinas_grade as $index => $disciplina_id) { if (!empty($disciplina_id) && !empty($professores_grade[$index])) { $stmt_insert_grade->execute([':curso_id' => $curso_id, ':disciplina_id' => $disciplina_id, ':professor_id' => $professores_grade[$index]]); } }
            }
            $pdo->commit();
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Curso salvo com sucesso!'];
            header('Location: gerenciar_cursos.php');
            exit();
        } catch (PDOException $e) { $pdo->rollBack(); $erros[] = 'Erro no banco de dados: Tente novamente.'; error_log("Erro PDO ao salvar curso: " . $e->getMessage()); }
    }
    $mensagem_feedback = implode('<br>', $erros);
    $feedback_tipo = 'danger';
    $show_modal_on_load = true;
    $curso_para_modal = ['id' => $curso_id, 'sigla' => $sigla, 'nome' => $nome_curso, 'data_inicio_formatada' => $data_inicio,'data_fim_formatada' => $data_fim, 'data_avaliacao_formatada' => $data_avaliacao, 'horas' => $horas, 'grade_curso' => []];
    foreach ($disciplinas_grade as $index => $disciplina_id) { $curso_para_modal['grade_curso'][] = ['disciplina_id' => $disciplina_id, 'usuario_id' => $professores_grade[$index]]; }
}
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$current_page = "Cursos";
$limite_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;
$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$where_clause = '';
$parametros_sql = [];
if (!empty($termo_pesquisa)) { $where_clause = " WHERE (sigla LIKE :termo OR nome LIKE :termo)"; $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%'; }
try {
    $stmt_total = $pdo->prepare("SELECT COUNT(id) FROM cursos " . $where_clause);
    $stmt_total->execute($parametros_sql);
    $total_cursos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_cursos / $limite_por_pagina);
    $sql = "SELECT id, sigla, nome, data_inicio, data_fim, data_avaliacao, horas FROM cursos" . $where_clause . " ORDER BY nome ASC LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($parametros_sql as $key => &$val) $stmt->bindParam($key, $val);
    $stmt->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cursos as &$curso) {
        $curso['data_inicio_formatada'] = (new DateTime($curso['data_inicio']))->format('d/m/Y');
        $curso['data_fim_formatada'] = (new DateTime($curso['data_fim']))->format('d/m/Y');
        $curso['data_avaliacao_formatada'] = (new DateTime($curso['data_avaliacao']))->format('d/m/Y');
        $stmt_grade = $pdo->prepare("SELECT gc.disciplina_id, gc.usuario_id FROM grade_curso gc JOIN disciplina d ON gc.disciplina_id = d.id WHERE gc.curso_id = :curso_id ORDER BY d.sigla ASC");
        $stmt_grade->execute([':curso_id' => $curso['id']]);
        $curso['grade_curso'] = $stmt_grade->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($curso);
} catch (PDOException $e) { error_log("Erro ao carregar cursos: " . $e->getMessage()); $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar a lista de cursos.']; $cursos = []; $total_paginas = 0; }
if ($is_ajax_request) { require_once __DIR__ . '/includes/templates/course_table_partial.php'; exit(); }

require_once __DIR__ . '/includes/templates/header_dashboard.php';
?>

<style>
    .modal-dialog.modal-lg { max-width: 1200px !important; }
    .select2-container--open { z-index: 1055 !important; }
</style>

<?php
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header"></header>
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Lista de Cursos</h2>
            <button class="btn-primary-dashboard" data-toggle="modal" data-target="#cursoModal" id="addCursoBtn"><i class="fas fa-plus-circle"></i> Adicionar Curso</button>
        </div>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem_feedback; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Pesquisar cursos..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
            <i class="fas fa-search"></i>
        </div>
        <div id="courseTableContainer">
            <?php require_once __DIR__ . '/includes/templates/course_table_partial.php'; ?>
        </div> 
    </div>
</div>

<div class="modal fade" id="cursoModal" tabindex="-1" role="dialog" aria-labelledby="cursoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cursoModalLabel">Novo Curso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="cursoForm" action="gerenciar_cursos.php" method="POST" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="curso_id" name="curso_id">
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="sigla">Sigla:</label><input type="text" class="form-control" id="sigla" name="sigla" placeholder="Ex: CFO_2025_1_A" required></div>
                        <div class="form-group col-md-6"><label for="nome_curso">Nome do Curso:</label><input type="text" class="form-control" id="nome_curso" name="nome_curso" placeholder="Ex: Curso de Formação de Oficiais" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label for="data_inicio">Início:</label><input type="text" class="form-control datepicker" id="data_inicio" name="data_inicio" placeholder="dd/mm/aaaa" required></div>
                        <div class="form-group col-md-4"><label for="data_fim">Fim:</label><input type="text" class="form-control datepicker" id="data_fim" name="data_fim" placeholder="dd/mm/aaaa" required></div>
                        <div class="form-group col-md-4"><label for="data_avaliacao">Avaliação:</label><input type="text" class="form-control datepicker" id="data_avaliacao" name="data_avaliacao" placeholder="dd/mm/aaaa" required></div>
                    </div>
                    <div class="form-group"><label for="horas">Carga Horária:</label><input type="number" class="form-control" id="horas" name="horas" placeholder="Ex: 120" min="1" required></div>
                    <hr>
                    <h4>Grade do Curso</h4>
                    <div id="gradeAulasContainer"></div>
                    <button type="button" class="btn btn-info btn-sm mt-3" id="addDisciplinaBtn"><i class="fas fa-plus-circle"></i> Adicionar Disciplina</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-primary-dashboard">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    const disciplinasData = <?php echo json_encode($disciplinas); ?>;
    const professoresData = <?php echo json_encode($professores); ?>;

    $(".datepicker").datepicker({ dateFormat: 'dd/mm/yy' });

    function initializeSelect2(element, placeholderText) {
        element.select2({
            placeholder: placeholderText,
            dropdownParent: $('#cursoModal .modal-content'),
            width: '100%'
        });
    }

    function addGradeItem(disciplinaId = '', professorId = '') {
        const container = $('#gradeAulasContainer');
        const disciplinasOptions = disciplinasData.map(d => `<option value="${d.id}">${d.sigla} - ${d.nome}</option>`).join('');
        const professoresOptions = professoresData.map(p => `<option value="${p.id}">${p.nome_formatado}</option>`).join('');
        
        const newRowHTML = `
            <div class="form-row grade-item mt-2 align-items-center">
                <div class="form-group col-md-5 mb-0">
                    <select class="form-control select2-searchable" name="disciplina_id[]" required><option></option>${disciplinasOptions}</select>
                </div>
                <div class="form-group col-md-6 mb-0">
                    <select class="form-control select2-searchable" name="professor_id[]" required><option></option>${professoresOptions}</select>
                </div>
                <div class="form-group col-md-1 mb-0 text-right">
                    <button type="button" class="btn btn-danger btn-sm remove-aula-btn" title="Remover Disciplina"><i class="fas fa-minus-circle"></i></button>
                </div>
            </div>`;
        
        const newRow = $(newRowHTML);
        
        const disciplinaSelect = newRow.find('select[name="disciplina_id[]"]');
        const professorSelect = newRow.find('select[name="professor_id[]"]');

        disciplinaSelect.val(disciplinaId);
        professorSelect.val(professorId);
        
        container.append(newRow);
        
        initializeSelect2(disciplinaSelect, 'Selecione a Disciplina');
        initializeSelect2(professorSelect, 'Selecione o Professor');
    }

    function resetCursoModal() {
        $('#gradeAulasContainer .select2-searchable').each(function() {
            if ($(this).data('select2')) { $(this).select2('destroy'); }
        });
        $('#gradeAulasContainer').empty();
        $('#curso_id').val('');
        $('#sigla').val('');
        $('#nome_curso').val('');
        $('#data_inicio').val('');
        $('#data_fim').val('');
        $('#data_avaliacao').val('');
        $('#horas').val('');
        $('#cursoForm')[0].reset();
        $('#cursoModalLabel').text('Novo Curso');
    }

    $('#addCursoBtn').on('click', function() {
        resetCursoModal();
        addGradeItem();
    });

    $(document).on('click', '.edit-curso-btn', function() {
        resetCursoModal();
        const cursoData = $(this).data('curso');
        
        $('#cursoModalLabel').text('Editar Curso');
        $('#curso_id').val(cursoData.id);
        $('#sigla').val(cursoData.sigla);
        $('#nome_curso').val(cursoData.nome);
        $('#data_inicio').val(cursoData.data_inicio_formatada);
        $('#data_fim').val(cursoData.data_fim_formatada);
        $('#data_avaliacao').val(cursoData.data_avaliacao_formatada);
        $('#horas').val(cursoData.horas);

        if (cursoData.grade_curso && cursoData.grade_curso.length > 0) {
            cursoData.grade_curso.forEach(aula => addGradeItem(aula.disciplina_id, aula.usuario_id));
        } else {
            addGradeItem();
        }
    });
    
    $('#addDisciplinaBtn').on('click', function() {
        addGradeItem();
    });

    $('#gradeAulasContainer').on('click', '.remove-aula-btn', function() {
        $(this).closest('.grade-item').find('.select2-searchable').each(function() {
            if ($(this).data('select2')) { $(this).select2('destroy'); }
        });
        $(this).closest('.grade-item').remove();
    });

    function fetchCourses(searchTerm, page = 1) {
        $.ajax({
            url: `gerenciar_cursos.php?q=${encodeURIComponent(searchTerm)}&pagina=${page}`, type: 'GET',
            success: function(response) { $('#courseTableContainer').html(response); },
            error: function() { $('#courseTableContainer').html('<tr><td colspan="5">Erro ao carregar os cursos.</td></tr>');}
        });
    }

    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        searchTimeout = setTimeout(() => {
            fetchCourses(searchTerm, 1);
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + `?q=${encodeURIComponent(searchTerm)}&pagina=1`;
            window.history.pushState({path: newUrl}, '', newUrl);
        }, 300);
    });

    $(document).on('click', '.pagination-link', function(e) {
        e.preventDefault();
        const url = new URL($(this).attr('href'), window.location.origin);
        fetchCourses($('#searchInput').val(), url.searchParams.get('pagina'));
    });

    <?php if ($show_modal_on_load): ?>
        const cursoData = <?php echo json_encode($curso_para_modal); ?>;
        if(cursoData.id) { $('#cursoModalLabel').text('Editar Curso'); }
        $('#curso_id').val(cursoData.id);
        $('#sigla').val(cursoData.sigla);
        $('#nome_curso').val(cursoData.nome);
        $('#data_inicio').val(cursoData.data_inicio_formatada);
        $('#data_fim').val(cursoData.data_fim_formatada);
        $('#data_avaliacao').val(cursoData.data_avaliacao_formatada);
        $('#horas').val(cursoData.horas);
        if (cursoData.grade_curso && cursoData.grade_curso.length > 0) {
            cursoData.grade_curso.forEach(aula => addGradeItem(aula.disciplina_id, aula.usuario_id));
        } else { addGradeItem(); }
        $('#cursoModal').modal('show');
    <?php endif; ?>
});
</script>