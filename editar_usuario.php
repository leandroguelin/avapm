<?php
// editar_usuario.php - Painel para Administradores Editarem Usuários

// --- Inicialização e Segurança ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);

// --- Definições da Página ---
$page_title = "Editar Usuário";
$current_page = "Usuários";
$mensagem_feedback = '';
$feedback_tipo = '';

// --- Lógica de Carregamento de Dados ---
$id_usuario_para_editar = $_GET['id'] ?? null;
if (!is_numeric($id_usuario_para_editar)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID de usuário inválido.'];
    header('Location: gerenciar_usuarios.php');
    exit();
}

$id_admin_logado = $_SESSION['usuario_id'];

// Carregar dados para os dropdowns
try {
    $patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
    $titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
    $instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Tratar erro de carregamento de dropdowns
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar opções do formulário.'];
    error_log("Erro PDO em editar_usuario.php: " . $e->getMessage());
    header('Location: gerenciar_usuarios.php');
    exit();
}

// Lógica de Níveis de Acesso para o Dropdown (Regra de Segurança)
$niveis_acesso_disponiveis = ['ALUNO', 'PROFESSOR', 'GERENTE'];
// Um admin só pode ver a opção "Administrador" se estiver editando a si mesmo.
if ($id_admin_logado == $id_usuario_para_editar) {
    $niveis_acesso_disponiveis[] = 'ADMINISTRADOR';
}


// --- Lógica para Processar o Formulário (quando enviado via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do POST
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rg = preg_replace('/\D/', '', $_POST['rg'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $nivel_acesso = strtoupper(filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING));
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $nome_guerra = filter_input(INPUT_POST, 'nome_guerra', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';
    
    // Validações
    $erros = [];
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    if (empty($email)) $erros[] = 'Email é obrigatório.';
    if (!in_array($nivel_acesso, $niveis_acesso_disponiveis)) {
        $erros[] = 'Tentativa de atribuir um nível de acesso não permitido.';
    }
    // Adicione outras validações aqui conforme necessário

    if (empty($erros)) {
        try {
            $sql = "UPDATE usuario SET nome = :nome, email = :email, rg = :rg, cpf = :cpf, telefone = :telefone, nivel_acesso = :nivel_acesso, patente = :patente, titulacao = :titulacao, instituicao = :instituicao, fonte_pagadora = :fonte_pagadora, nome_guerra = :nome_guerra";
            if (!empty($senha)) {
                $sql .= ", senha = :senha";
            }
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            $params = [
                ':nome' => $nome, ':email' => $email, ':rg' => $rg, ':cpf' => $cpf, 
                ':telefone' => $telefone, ':nivel_acesso' => $nivel_acesso, ':patente' => $patente,
                ':titulacao' => $titulacao, ':instituicao' => $instituicao, 
                ':fonte_pagadora' => $fonte_pagadora, ':nome_guerra' => $nome_guerra, ':id' => $id
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
            $mensagem_feedback = 'Erro ao atualizar usuário.';
            $feedback_tipo = 'danger';
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
        }
    } else {
        $mensagem_feedback = implode('<br>', $erros);
        $feedback_tipo = 'danger';
    }
}

// --- Carregar Dados do Usuário para Exibição no Formulário ---
try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id = :id");
    $stmt->execute([':id' => $id_usuario_para_editar]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        throw new Exception("Usuário não encontrado.");
    }
    // Se o POST falhou, preenche o formulário com os dados enviados
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($erros)) {
        $usuario = array_merge($usuario, $_POST);
    }
} catch (Exception $e) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => $e->getMessage()];
    header('Location: gerenciar_usuarios.php');
    exit();
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
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="rg">RG:</label>
                <input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="nome_guerra">Nome de Guerra:</label>
                <input type="text" id="nome_guerra" name="nome_guerra" value="<?php echo htmlspecialchars($usuario['nome_guerra'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso:</label>
                <select id="nivel_acesso" name="nivel_acesso" required>
                    <?php foreach ($niveis_acesso_disponiveis as $nivel): 
                        $selected = (strtoupper($usuario['nivel_acesso'] ?? '') == $nivel) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $nivel; ?>" <?php echo $selected; ?>>
                            <?php echo ucfirst(strtolower($nivel)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente">
                    <option value="">Nenhuma</option>
                    <?php foreach ($patentes as $p):
                        $selected = (($usuario['patente'] ?? '') == $p) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($p)."' $selected>".htmlspecialchars($p)."</option>";
                    endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="titulacao">Titulação:</label>
                <select id="titulacao" name="titulacao">
                    <option value="">Nenhuma</option>
                    <?php foreach ($titulacoes as $t):
                        $selected = (($usuario['titulacao'] ?? '') == $t) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($t)."' $selected>".htmlspecialchars($t)."</option>";
                    endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="instituicao">Instituição:</label>
                 <select id="instituicao" name="instituicao">
                    <option value="">Nenhuma</option>
                    <?php foreach ($instituicoes as $i):
                        $selected = (($usuario['instituicao'] ?? '') == $i) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($i)."' $selected>".htmlspecialchars($i)."</option>";
                    endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fonte_pagadora">Fonte Pagadora:</label>
                 <select id="fonte_pagadora" name="fonte_pagadora">
                    <option value="">Nenhuma</option>
                    <?php foreach ($instituicoes as $i):
                        $selected = (($usuario['fonte_pagadora'] ?? '') == $i) ? 'selected' : '';
                        echo "<option value='".htmlspecialchars($i)."' $selected>".htmlspecialchars($i)."</option>";
                    endforeach; ?>
                </select>
            </div>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    // Seu script de máscaras para CPF e Telefone aqui
    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00');
        $('#telefone').mask('(00) 00000-0000');
    });
</script>
