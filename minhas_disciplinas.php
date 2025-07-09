<?php
// htdocs/avapm/minhas_disciplinas.php (Versão Melhorada)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você precisa estar logado.'];
    header('Location: index.php');
    exit();
}
$usuario_id_logado = $_SESSION['usuario_id'];

// --- Lógica para ADICIONAR disciplina ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_disciplina') {
    $disciplina_id = filter_input(INPUT_POST, 'disciplina_id', FILTER_SANITIZE_NUMBER_INT);
    // CORREÇÃO: O valor do radio virá como "Sim" ou "Não"
    $disponibilidade_input = filter_input(INPUT_POST, 'disponibilidade', FILTER_SANITIZE_STRING);
    $disponibilidade = ($disponibilidade_input === 'Sim') ? 'Sim' : 'Não'; // Garante que o valor seja "Sim" ou "Não"

    if (!empty($disciplina_id)) {
        try {
            $sql = "INSERT INTO minhas_disciplinas (usuario_id, disciplina_id, disponibilidade) 
                    VALUES (:usuario_id, :disciplina_id, :disponibilidade)
                    ON CONFLICT (usuario_id, disciplina_id) DO NOTHING;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuario_id_logado,
                ':disciplina_id' => $disciplina_id,
                ':disponibilidade' => $disponibilidade
            ]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Disciplina adicionada com sucesso!'];
            } else {
                $_SESSION['mensagem_feedback'] = ['tipo' => 'info', 'texto' => 'Você já possui esta disciplina.'];
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro no banco de dados.'];
        }
    } else {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Selecione uma disciplina.'];
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

// --- Lógica para BUSCAR as disciplinas do usuário e as disponíveis ---
$minhas_disciplinas = [];
$disciplinas_disponiveis = [];
try {
    // Busca as disciplinas que o usuário JÁ POSSUI
    $sql_minhas = "SELECT md.id AS vinculo_id, d.sigla, d.nome AS nome_disciplina, md.disponibilidade
                   FROM minhas_disciplinas md
                   JOIN disciplina d ON md.disciplina_id = d.id
                   WHERE md.usuario_id = :usuario_id
                   ORDER BY d.nome ASC";
    $stmt_minhas = $pdo->prepare($sql_minhas);
    $stmt_minhas->execute([':usuario_id' => $usuario_id_logado]);
    $minhas_disciplinas = $stmt_minhas->fetchAll(PDO::FETCH_ASSOC);

    // Busca as disciplinas que o usuário AINDA NÃO POSSUI
    $sql_disponiveis = "SELECT id, sigla, nome 
                        FROM disciplina 
                        WHERE id NOT IN (SELECT disciplina_id FROM minhas_disciplinas WHERE usuario_id = :usuario_id)
                        ORDER BY nome ASC";
    $stmt_disponiveis = $pdo->prepare($sql_disponiveis);
    $stmt_disponiveis->execute([':usuario_id' => $usuario_id_logado]);
    $disciplinas_disponiveis = $stmt_disponiveis->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_feedback = "Erro ao carregar dados: " . $e->getMessage();
    $feedback_tipo = 'danger';
}

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
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
    </header>
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Minha Lista de Disciplinas</h2>
            <button class="btn-primary-dashboard" data-toggle="modal" data-target="#addDisciplinaModal"><i class="fas fa-plus"></i> Adicionar Disciplina</button>
        </div>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>"><?php echo $mensagem_feedback; ?></div>
        <?php endif; ?>
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
                                <td>
                                    <span class="badge <?php echo ($disciplina['disponibilidade'] === 'Sim') ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo htmlspecialchars($disciplina['disponibilidade']); ?>
                                    </span>
                                </td>
                                <td><a href="minhas_disciplinas.php?action=excluir&id=<?php echo $disciplina['vinculo_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?');"><i class="fas fa-trash-alt"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Disciplina -->
<div class="modal fade" id="addDisciplinaModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Nova Disciplina</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
            </div>
            <form action="minhas_disciplinas.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_disciplina">
                    
                    <!-- MELHORIA: Campo de busca para o select -->
                    <div class="form-group">
                        <label for="busca-disciplina">Buscar Disciplina:</label>
                        <input type="text" id="busca-disciplina" class="form-control" placeholder="Digite para buscar...">
                    </div>
                    
                    <div class="form-group">
                        <label for="disciplina_id">Disciplina:</label>
                        <select class="form-control" id="disciplina_id" name="disciplina_id" required size="8">
                            <?php if (empty($disciplinas_disponiveis)): ?>
                                <option value="" disabled>Nenhuma disciplina nova disponível.</option>
                            <?php else: ?>
                                <?php foreach ($disciplinas_disponiveis as $disc): ?>
                                    <option value="<?php echo $disc['id']; ?>" data-nome="<?php echo htmlspecialchars(strtolower($disc['nome'] . ' ' . $disc['sigla'])); ?>">
                                        <?php echo htmlspecialchars($disc['sigla'] . ' - ' . $disc['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                     <!-- MELHORIA: Botões de rádio para Disponibilidade -->
                     <div class="form-group">
                        <label>Disponibilidade:</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="disponibilidade" id="disponibilidade_sim" value="Sim" checked>
                                <label class="form-check-label" for="disponibilidade_sim">Sim</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="disponibilidade" id="disponibilidade_nao" value="Não">
                                <label class="form-check-label" for="disponibilidade_nao">Não</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>
<!-- Adicionei jQuery completo para garantir a funcionalidade -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Lógica para o campo de busca de disciplinas
    $('#busca-disciplina').on('keyup', function() {
        let valorBusca = $(this).val().toLowerCase();
        $('#disciplina_id option').each(function() {
            let nomeDisciplina = $(this).data('nome');
            if (nomeDisciplina.includes(valorBusca)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Limpar busca ao fechar o modal
    $('#addDisciplinaModal').on('hidden.bs.modal', function () {
        $('#busca-disciplina').val('');
        $('#disciplina_id option').show();
        $(this).find('form')[0].reset();
        // Garante que a opção padrão do radio seja selecionada
        $('#disponibilidade_sim').prop('checked', true);
    });
});
</script>
<style>
/* Adicionando um estilo simples para os badges de disponibilidade */
.badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
.badge-success { color: #fff; background-color: #28a745; }
.badge-danger { color: #fff; background-color: #dc3545; }
</style>
