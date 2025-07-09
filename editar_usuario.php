<?php
// htdocs/avapm/editar_usuario.php

// Iniciar a sessão e incluir dependências
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);


// --- Definições da Página ---
$page_title = "Editar Usuário";
$current_page = "Usuários";

// --- Inicialização de Variáveis ---
$usuario = null;
$mensagem_feedback = '';
$feedback_tipo = '';

// --- Carregar Dados para Dropdowns ---
$patentes = [];
$titulacoes = [];
$instituicoes = [];
// Níveis de acesso em MAIÚSCULAS para consistência com o banco de dados
$niveis_acesso_disponiveis = ['ALUNO', 'PROFESSOR', 'GERENTE', 'ADMINISTRADOR'];

try {
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);
    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC"); 
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);
    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => "Erro ao carregar opções do formulário."];
    error_log("Erro PDO ao carregar dropdowns para editar_usuario.php: " . $e->getMessage());
    header('Location: gerenciar_usuarios.php');
    exit();
}

// --- Carregar Dados do Usuário para Edição ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_usuario = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id = :id");
        $stmt->execute([':id' => $id_usuario]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Usuário não encontrado.'];
            header('Location: gerenciar_usuarios.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar dados do usuário.'];
        error_log("Erro ao carregar usuário para edição: " . $e->getMessage());
        header('Location: gerenciar_usuarios.php');
        exit();
    }
} else {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID de usuário inválido.'];
    header('Location: gerenciar_usuarios.php');
    exit();
}

// --- Processar Formulário de Atualização (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do POST
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rg = $_POST['rg'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $nivel_acesso = strtoupper(filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING));
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';

    // Validações...
    $erros = [];
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    if (empty($email)) $erros[] = 'Email é obrigatório.';
    if (!in_array($nivel_acesso, $niveis_acesso_disponiveis)) $erros[] = 'Nível de acesso inválido.';
    // Adicione outras validações conforme necessário
    
    // Se não houver erros, atualiza
    if (empty($erros)) {
        try {
            $sql = "UPDATE usuario SET nome = :nome, email = :email, rg = :rg, cpf = :cpf, telefone = :telefone, nivel_acesso = :nivel_acesso, patente = :patente, titulacao = :titulacao, instituicao = :instituicao, fonte_pagadora = :fonte_pagadora";
            if (!empty($senha)) {
                $sql .= ", senha = :senha";
            }
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind dos parâmetros
            $params = [
                ':nome' => $nome,
                ':email' => $email,
                ':rg' => preg_replace('/\D/', '', $rg),
                ':cpf' => preg_replace('/\D/', '', $cpf),
                ':telefone' => preg_replace('/\D/', '', $telefone),
                ':nivel_acesso' => $nivel_acesso,
                ':patente' => $patente,
                ':titulacao' => $titulacao,
                ':instituicao' => $instituicao,
                ':fonte_pagadora' => $fonte_pagadora,
                ':id' => $id
            ];

            if (!empty($senha)) {
                $params[':senha'] = password_hash($senha, PASSWORD_DEFAULT);
            }

            if ($stmt->execute($params)) {
                $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Usuário atualizado com sucesso!'];
                header('Location: gerenciar_usuarios.php');
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao atualizar usuário.'];
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
        }
    }
    
    // Se houver erros, prepara mensagens e recarrega os dados do POST no formulário
    $mensagem_feedback = implode('<br>', $erros);
    $feedback_tipo = 'danger';
    $usuario = array_merge($usuario, $_POST);
}

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Editando: <?php echo htmlspecialchars($usuario['nome']); ?></h2>
            <a href="gerenciar_usuarios.php" class="btn-secondary-dashboard"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>"><?php echo $mensagem_feedback; ?></div>
        <?php endif; ?>

        <form action="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" method="POST" class="form-dashboard">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
            
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso:</label>
                <select id="nivel_acesso" name="nivel_acesso" required>
                    <option value="">Selecione um nível</option>
                    <?php foreach ($niveis_acesso_disponiveis as $nivel): 
                        // Normaliza ambos para comparação
                        $nivel_usuario_db = strtoupper($usuario['nivel_acesso'] ?? '');
                        $selected = ($nivel_usuario_db == $nivel) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($nivel); ?>" <?php echo $selected; ?>>
                            <?php echo ucfirst(strtolower($nivel)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="titulacao">Titulação:</label>
                <select id="titulacao" name="titulacao" required>
                    <option value="">Selecione a Titulação</option>
                    <?php foreach ($titulacoes as $nome_titulacao): 
                        $selected = (($usuario['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($nome_titulacao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Outros campos (CPF, RG, etc.) -->
            
            <div class="form-group">
                <label for="senha">Nova Senha (deixe em branco para não alterar):</label>
                <input type="password" id="senha" name="senha">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-dashboard"><i class="fas fa-save"></i> Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
