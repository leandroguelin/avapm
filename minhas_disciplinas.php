php
<?php
// htdocs/avapm/minhas_disciplinas.php

// Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Redirecionar para a página de login se não estiver logado
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você precisa estar logado para acessar esta página.'
    ];
    header('Location: index.php');
    exit();
}

// Obter o ID do usuário logado
$usuario_id_logado = $_SESSION['usuario_id'];

// Inicializa variáveis de feedback
$mensagem_feedback = '';
$feedback_tipo = '';

// =====================================================================
// Lógica para processar a adição de disciplina (POST)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_disciplina') {
    $disciplina_id = filter_input(INPUT_POST, 'disciplina_id', FILTER_SANITIZE_NUMBER_INT);
    $disponibilidade = trim(filter_input(INPUT_POST, 'disponibilidade', FILTER_SANITIZE_STRING));

    $erros = [];

    if (empty($disciplina_id)) {
        $erros[] = 'Selecione uma disciplina para adicionar.';
    }
    // Validação opcional para disponibilidade, se necessário
    // if (empty($disponibilidade)) {
    //     $erros[] = 'O campo disponibilidade é obrigatório.';
    // }

    if (empty($erros)) {
        try {
            // Prepara a query de inserção. ON CONFLICT faz nada se o par (usuario_id, disciplina_id) já existe.
            $sql = "INSERT INTO minhas_disciplinas (usuario_id, disciplina_id, disponibilidade) 
                    VALUES (:usuario_id, :disciplina_id, :disponibilidade)
                    ON CONFLICT (usuario_id, disciplina_id) DO NOTHING;";
                    
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':usuario_id', $usuario_id_logado, PDO::PARAM_INT);
            $stmt->bindParam(':disciplina_id', $disciplina_id, PDO::PARAM_INT);
            $stmt->bindParam(':disponibilidade', $disponibilidade);

            if ($stmt->execute()) {
                // Verifica se a inserção realmente ocorreu (não houve conflito)
                if ($stmt->rowCount() > 0) {
                    $_SESSION['mensagem_feedback'] = [
                        'tipo' => 'success',
                        'texto' => 'Disciplina adicionada com sucesso!'
                    ];
                } else {
                     $_SESSION['mensagem_feedback'] = [
                        'tipo' => 'info',
                        'texto' => 'Você já possui esta disciplina em sua lista.'
                    ];
                }
            } else {
                 $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'danger',
                    'texto' => 'Erro ao adicionar disciplina. Tente novamente.'
                ];
            }

        } catch (PDOException $e) {
            error_log("Erro no banco de dados ao adicionar disciplina do usuário: " . $e->getMessage());
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Erro no banco de dados ao adicionar disciplina.'
            ];
        }
    } else {
        // Se houver erros de validação
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => implode('<br>', $erros)
        ];
    }

    // Redireciona para a própria página para mostrar o feedback e evitar reenvio do formulário
    header('Location: minhas_disciplinas.php');
    exit();
}


// =====================================================================
// Lógica para processar a exclusão de disciplina (GET)
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] == 'excluir' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $vinc_id_para_excluir = (int)$_GET['id'];

    try {
        // Prepara e executa a query de exclusão
        // Garante que apenas vínculos do usuário logado possam ser excluídos
        $stmt_delete = $pdo->prepare("DELETE FROM minhas_disciplinas WHERE id = :id AND usuario_id = :usuario_id");
        $stmt_delete->bindParam(':id', $vinc_id_para_excluir, PDO::PARAM_INT);
        $stmt_delete->bindParam(':usuario_id', $usuario_id_logado, PDO::PARAM_INT);


        if ($stmt_delete->execute()) {
             if ($stmt_delete->rowCount() > 0) {
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'success',
                    'texto' => 'Disciplina removida da sua lista com sucesso!'
                ];
            } else {
                 $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'warning',
                    'texto' => 'Vínculo de disciplina não encontrado ou você não tem permissão para excluí-lo.'
                ];
            }
        } else {
             $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Erro ao remover disciplina. Tente novamente.'
            ];
        }
    } catch (PDOException $e) {
        error_log("Erro no banco de dados ao excluir vínculo de disciplina: " . $e->getMessage());
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => "Erro no banco de dados ao remover disciplina: " . $e->getMessage()
        ];
    }
    // Redireciona sempre após a tentativa de exclusão
    header('Location: minhas_disciplinas.php');
    exit();
}


// =====================================================================
// Lógica para buscar as disciplinas vinculadas ao usuário logado
// =====================================================================
$minhas_disciplinas = [];
try {
    $sql = "SELECT 
                md.id AS vinculo_id, 
                d.id AS disciplina_id,
                d.sigla, 
                d.nome AS nome_disciplina,
                md.disponibilidade
            FROM 
                minhas_disciplinas md
            JOIN 
                disciplina d ON md.disciplina_id = d.id
            WHERE 
                md.usuario_id = :usuario_id_logado
            ORDER BY 
                d.nome_disciplina ASC"; // Ou sigla ASC
                
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id_logado', $usuario_id_logado, PDO::PARAM_INT);
    $stmt->execute();
    $minhas_disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar minhas disciplinas: " . $e->getMessage());
    $mensagem_feedback = "Erro ao carregar suas disciplinas: " . $e->getMessage();
    $feedback_tipo = 'danger';
}

// =====================================================================
// Lógica para buscar todas as disciplinas disponíveis (para o modal de adição)
// =====================================================================
$disciplinas_disponiveis = [];
try {
    $sql_disponiveis = "SELECT 
                            id, 
                            sigla, 
                            nome 
                        FROM 
                            disciplina 
                        WHERE 
                            id NOT IN (SELECT disciplina_id FROM minhas_disciplinas WHERE usuario_id = :usuario_id_logado)
                        ORDER BY 
                            nome ASC";
                            
    $stmt_disponiveis = $pdo->prepare($sql_disponiveis);
    $stmt_disponiveis->bindParam(':usuario_id_logado', $usuario_id_logado, PDO::PARAM_INT);
    $stmt_disponiveis->execute();
    $disciplinas_disponiveis = $stmt_disponiveis->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar disciplinas disponíveis: " . $e->getMessage());
    // Pode exibir um aviso, mas não é crítico a ponto de parar a página
    // $mensagem_feedback .= "<br>Aviso: Não foi possível carregar a lista de disciplinas disponíveis.";
    // $feedback_tipo = $feedback_tipo == 'danger' ? 'danger' : 'warning';
}


// =====================================================================
// Recuperar e limpar mensagem de feedback da sessão
// =====================================================================
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a sessão após exibir
}

// --- Definição de Variáveis para o Layout ---
$page_title = "Minhas Disciplinas";
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário'; // Assume que $_SESSION['nome_usuario'] existe
$foto_perfil_usuario = $_SESSION['foto_perfil'] ?? null; // Assume que $_SESSION['foto_perfil'] existe
$current_page = "MinhasDisciplinas"; // Para destacar na sidebar (ajuste se o nome do item da sidebar for diferente)


// Incluir o cabeçalho do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
// Incluir a barra lateral do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
            <div class="user-avatar">
                <?php
                $foto_src = '';
                if (!empty($foto_perfil_usuario) && file_exists('imagens/profiles/' . $foto_perfil_usuario)) {
                    $foto_src = 'imagens/profiles/' . htmlspecialchars($foto_perfil_usuario);
                }
                ?>
                <?php if (!empty($foto_src)) : ?>
                    <img src="<?php echo $foto_src; ?>" alt="Avatar" style="max-width: 30px; height: auto; border-radius: 50%;">
                <?php else : ?>
                    <i class="fas fa-user-circle fa-lg"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Minha Lista de Disciplinas</h2>
            <!-- Botão para abrir o modal de adicionar disciplina -->
            <button class="btn-primary-dashboard" data-toggle="modal" data-target="#addDisciplinaModal">
                <i class="fas fa-plus"></i> Adicionar Disciplina
            </button>
        </div>

        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($mensagem_feedback); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>


        <?php if (empty($minhas_disciplinas)): ?>
            <div class="alert alert-info">Você ainda não adicionou nenhuma disciplina à sua lista. Use o botão "Adicionar Disciplina" acima.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Sigla</th>
                            <th>Nome da Disciplina</th>
                            <th>Disponibilidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($minhas_disciplinas as $disciplina): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($disciplina['sigla']); ?></td>
                                <td><?php echo htmlspecialchars($disciplina['nome_disciplina']); ?></td>
                                <td><?php echo htmlspecialchars($disciplina['disponibilidade']); ?></td>
                                <td>
                                    <!-- Botão para Editar (ainda não implementado, pode abrir um modal futuro) -->
                                    <button class="btn btn-sm btn-secondary" title="Editar Disponibilidade" disabled>
                                         <i class="fas fa-edit"></i> <!-- Ícone de editar -->
                                    </button>
                                    <!-- Botão para Excluir -->
                                    <a href="minhas_disciplinas.php?action=excluir&id=<?php echo $disciplina['vinculo_id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       title="Remover Disciplina da Lista"
                                       onclick="return confirm('Tem certeza que deseja remover a disciplina \'<?php echo htmlspecialchars($disciplina['nome_disciplina']); ?>\' da sua lista?');">
                                        <i class="fas fa-trash-alt"></i> <!-- Ícone de lixeira -->
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Modal para Adicionar Disciplina -->
<div class="modal fade" id="addDisciplinaModal" tabindex="-1" role="dialog" aria-labelledby="addDisciplinaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDisciplinaModalLabel">Adicionar Nova Disciplina</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="minhas_disciplinas.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_disciplina">
                    
                    <div class="form-group">
                        <label for="disciplina_id">Disciplina:</label>
                        <select class="form-control" id="disciplina_id" name="disciplina_id" required>
                            <option value="">Selecione uma disciplina</option>
                            <?php foreach ($disciplinas_disponiveis as $disc): ?>
                                <option value="<?php echo $disc['id']; ?>">
                                    <?php echo htmlspecialchars($disc['sigla'] . ' - ' . $disc['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                     <div class="form-group">
                        <label for="disponibilidade">Disponibilidade (Opcional):</label>
                        <input type="text" class="form-control" id="disponibilidade" name="disponibilidade" placeholder="Ex: Disponível, Indisponível">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-primary-dashboard">
                         <i class="fas fa-plus"></i> Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
// Incluir o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<!-- Scripts necessários para o modal e possivelmente Select2 se usado no futuro -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<!-- Se você usar Select2 para o dropdown de disciplinas, adicione os scripts aqui -->
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
<script>
$(document).ready(function() {
    // Inicializar Select2 se for usado
    // $('#disciplina_id').select2({
    //     dropdownParent: $('#addDisciplinaModal'), // Essencial para que o dropdown apareça acima do modal
    //     placeholder: 'Selecione uma disciplina',
    //     allowClear: true // Permite limpar a seleção
    // });

    // Limpar o formulário do modal ao fechá-lo
    $('#addDisciplinaModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        // Se usar Select2, resetar também:
        // $('#disciplina_id').val(null).trigger('change');
    });
});
</script>
