<?php
// htdocs/avapm/minhas_disciplinas.php (Versão com Múltipla Adição e Edição)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você precisa estar logado.'];
    header('Location: index.php');
    exit();
}
$usuario_id_logado = $_SESSION['usuario_id'];

// --- Lógica para ADICIONAR MÚLTIPLAS disciplinas ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_disciplinas') {
    $disciplina_ids = $_POST['disciplina_ids'] ?? []; // Agora é um array
    $disponibilidade_input = filter_input(INPUT_POST, 'disponibilidade', FILTER_SANITIZE_STRING);
    $disponibilidade = ($disponibilidade_input === 'Sim') ? 'Sim' : 'Não';
    
    if (!empty($disciplina_ids) && is_array($disciplina_ids)) {
        $count_added = 0;
        $sql = "INSERT INTO minhas_disciplinas (usuario_id, disciplina_id, disponibilidade) 
                VALUES (:usuario_id, :disciplina_id, :disponibilidade)
                ON CONFLICT (usuario_id, disciplina_id) DO NOTHING;";
        $stmt = $pdo->prepare($sql);

        foreach ($disciplina_ids as $disciplina_id) {
            $stmt->execute([
                ':usuario_id' => $usuario_id_logado,
                ':disciplina_id' => (int)$disciplina_id,
                ':disponibilidade' => $disponibilidade
            ]);
            if ($stmt->rowCount() > 0) {
                $count_added++;
            }
        }
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => "$count_added disciplina(s) adicionada(s) com sucesso!"];
    } else {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Selecione pelo menos uma disciplina.'];
    }
    header('Location: minhas_disciplinas.php');
    exit();
}

// --- Lógica para EDITAR DISPONIBILIDADE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_disponibilidade') {
    $vinculo_id = filter_input(INPUT_POST, 'vinculo_id', FILTER_SANITIZE_NUMBER_INT);
    $disponibilidade = filter_input(INPUT_POST, 'disponibilidade_edit', FILTER_SANITIZE_STRING);

    if (!empty($vinculo_id) && in_array($disponibilidade, ['Sim', 'Não'])) {
        try {
            $sql = "UPDATE minhas_disciplinas SET disponibilidade = :disponibilidade WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':disponibilidade' => $disponibilidade,
                ':id' => $vinculo_id,
                ':usuario_id' => $usuario_id_logado
            ]);
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Disponibilidade atualizada com sucesso!'];
        } catch (PDOException $e) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao atualizar a disponibilidade.'];
        }
    } else {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Dados inválidos para a atualização.'];
    }
    header('Location: minhas_disciplinas.php');
    exit();
}

// --- Lógica para EXCLUIR disciplina ---
if (isset($_GET['action']) && $_GET['action'] == 'excluir' && isset($_GET['id'])) {
    $vinc_id_para_excluir = (int)$_GET['id'];
    try {
        $stmt_delete = $pdo->prepare("DELETE FROM minhas_disciplinas WHERE id = :id AND usuario_id = :usuario_id");
        $stmt_delete->execute([':id' => $vinc_id_para_excluir, ':usuario_id' => $usuario_id_logado]);
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Disciplina removida com sucesso!'];
    } catch (PDOException $e) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao remover disciplina.'];
    }
    header('Location: minhas_disciplinas.php');
    exit();
}

// Lógica para buscar dados (mesma de antes)
$minhas_disciplinas = [];
$disciplinas_disponiveis = [];
try {
    $sql_minhas = "SELECT md.id AS vinculo_id, d.sigla, d.nome AS nome_disciplina, md.disponibilidade FROM minhas_disciplinas md JOIN disciplina d ON md.disciplina_id = d.id WHERE md.usuario_id = :usuario_id ORDER BY d.nome ASC";
    $stmt_minhas = $pdo->prepare($sql_minhas);
    $stmt_minhas->execute([':usuario_id' => $usuario_id_logado]);
    $minhas_disciplinas = $stmt_minhas->fetchAll(PDO::FETCH_ASSOC);

    $sql_disponiveis = "SELECT id, sigla, nome FROM disciplina WHERE id NOT IN (SELECT disciplina_id FROM minhas_disciplinas WHERE usuario_id = :usuario_id) ORDER BY nome ASC";
    $stmt_disponiveis = $pdo->prepare($sql_disponiveis);
    $stmt_disponiveis->execute([':usuario_id' => $usuario_id_logado]);
    $disciplinas_disponiveis = $stmt_disponiveis->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* ... */ }

if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']);
}

$page_title = "Minhas Disciplinas";
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header"><h1><?php echo $page_title; ?></h1></header>
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Minha Lista de Disciplinas</h2>
            <button class="btn-primary-dashboard" data-toggle="modal" data-target="#addDisciplinaModal"><i class="fas fa-plus"></i> Adicionar Disciplinas</button>
        </div>
        <?php if (!empty($mensagem_feedback)): ?><div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>"><?php echo $mensagem_feedback; ?></div><?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Sigla</th><th>Nome</th><th>Disponibilidade</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if (empty($minhas_disciplinas)): ?>
                        <tr><td colspan="4" class="text-center">Você ainda não adicionou nenhuma disciplina.</td></tr>
                    <?php else: ?>
                        <?php foreach ($minhas_disciplinas as $disciplina): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($disciplina['sigla']); ?></td>
                                <td><?php echo htmlspecialchars($disciplina['nome_disciplina']); ?></td>
                                <td><span class="badge <?php echo ($disciplina['disponibilidade'] === 'Sim') ? 'badge-success' : 'badge-danger'; ?>"><?php echo htmlspecialchars($disciplina['disponibilidade']); ?></span></td>
                                <td>
                                    <!-- Botão de Edição -->
                                    <button class="btn btn-sm btn-secondary edit-btn" data-toggle="modal" data-target="#editDisponibilidadeModal" data-vinculo-id="<?php echo $disciplina['vinculo_id']; ?>" data-disponibilidade="<?php echo $disciplina['disponibilidade']; ?>" title="Editar Disponibilidade"><i class="fas fa-edit"></i></button>
                                    <!-- Botão de Exclusão -->
                                    <a href="minhas_disciplinas.php?action=excluir&id=<?php echo $disciplina['vinculo_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');" title="Remover Disciplina"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para ADICIONAR Disciplinas -->
<div class="modal fade" id="addDisciplinaModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Adicionar Novas Disciplinas</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form action="minhas_disciplinas.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="add_disciplinas">
            <div class="form-group"><label for="busca-disciplina">Buscar Disciplina:</label><input type="text" id="busca-disciplina" class="form-control" placeholder="Digite para buscar..."></div>
            <div class="form-group"><label for="disciplina_ids">Disciplina(s): (Segure Ctrl/Cmd para selecionar várias)</label>
                <select class="form-control" id="disciplina_ids" name="disciplina_ids[]" required size="8" multiple>
                    <?php foreach ($disciplinas_disponiveis as $disc): ?>
                        <option value="<?php echo $disc['id']; ?>" data-nome="<?php echo htmlspecialchars(strtolower($disc['nome'] . ' ' . $disc['sigla'])); ?>"><?php echo htmlspecialchars($disc['sigla'] . ' - ' . $disc['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Disponibilidade para todas as selecionadas:</label>
                <div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="disponibilidade" id="disponibilidade_sim" value="Sim" checked><label class="form-check-label" for="disponibilidade_sim">Sim</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="disponibilidade" id="disponibilidade_nao" value="Não"><label class="form-check-label" for="disponibilidade_nao">Não</label></div>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Adicionar Selecionadas</button></div>
    </form>
</div></div></div>

<!-- Modal para EDITAR Disponibilidade -->
<div class="modal fade" id="editDisponibilidadeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Editar Disponibilidade</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form action="minhas_disciplinas.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="edit_disponibilidade">
            <input type="hidden" name="vinculo_id" id="edit_vinculo_id">
            <p>Alterar a disponibilidade para a disciplina selecionada.</p>
            <div class="form-group"><label>Disponibilidade:</label>
                <div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="disponibilidade_edit" id="disponibilidade_edit_sim" value="Sim"><label class="form-check-label" for="disponibilidade_edit_sim">Sim</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="disponibilidade_edit" id="disponibilidade_edit_nao" value="Não"><label class="form-check-label" for="disponibilidade_edit_nao">Não</label></div>
                </div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar Alteração</button></div>
    </form>
</div></div></div>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    // JS para busca no modal de ADIÇÃO
    $('#busca-disciplina').on('keyup', function() { /* ... (código de busca existente) ... */ });

    // JS para popular o modal de EDIÇÃO
    $('#editDisponibilidadeModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var vinculoId = button.data('vinculo-id');
        var disponibilidade = button.data('disponibilidade');
        
        var modal = $(this);
        modal.find('#edit_vinculo_id').val(vinculoId);
        if (disponibilidade === 'Sim') {
            modal.find('#disponibilidade_edit_sim').prop('checked', true);
        } else {
            modal.find('#disponibilidade_edit_nao').prop('checked', true);
        }
    });
});
</script>
<style>
.badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; border-radius: .25rem; }
.badge-success { color: #fff; background-color: #28a745; }
.badge-danger { color: #fff; background-color: #dc3545; }
.table .btn-sm { margin: 0 2px; }
</style>
