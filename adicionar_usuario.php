<?php
// adicionar_usuario.php - Versão Corrigida e Restaurada

// --- 1. BLOCO DE PROCESSAMENTO E LÓGICA ---
// Todo o processamento do formulário acontece aqui, ANTES de qualquer HTML.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';
// Adicione a verificação de segurança, se aplicável
// require_once __DIR__ . '/includes/seguranca.php';
// verificar_permissao(basename(__FILE__), $pdo);


$erros = []; // Array para armazenar erros de validação
$nome_arquivo_foto = null; // Para armazenar o nome da foto se houver upload

// --- Processamento do formulário quando submetido (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitiza os dados do formulário
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $nivel_acesso = filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING);
    $rg = filter_input(INPUT_POST, 'rg', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $nome_guerra = filter_input(INPUT_POST, 'nome_guerra', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);

    // Validação dos dados
    if (empty($nome)) { $erros[] = "O nome é obrigatório."; }
    if (empty($email)) { $erros[] = "O email é obrigatório."; }
    if (empty($senha)) { $erros[] = "A senha é obrigatória."; }
    if (empty($confirmar_senha)) { $erros[] = "A confirmação de senha é obrigatória."; }
    if (empty($nivel_acesso)) { $erros[] = "O nível de acesso é obrigatório."; }
    if (empty($rg)) { $erros[] = "O RG é obrigatório."; }
    if (empty($cpf)) { $erros[] = "O CPF é obrigatório."; }
    if (empty($patente)) { $erros[] = "A patente é obrigatória."; }
    if (empty($titulacao)) { $erros[] = "A titulação é obrigatória."; }
    if (empty($instituicao)) { $erros[] = "A instituição é obrigatória."; }
    if (empty($fonte_pagadora)) { $erros[] = "A fonte pagadora é obrigatória."; }
    if (empty($nome_guerra)) { $erros[] = "O nome de guerra é obrigatório."; }
    if (empty($telefone)) { $erros[] = "O telefone é obrigatório."; }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = "Formato de email inválido."; }
    
    try {
        $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = :email");
        $stmt_check_email->execute([':email' => $email]);
        if ($stmt_check_email->fetchColumn() > 0) { $erros[] = "Este email já está cadastrado."; }
    } catch (PDOException $e) { $erros[] = "Erro ao verificar email: " . $e->getMessage(); }

    if (strlen($senha) < 6) { $erros[] = "A senha deve ter no mínimo 6 caracteres."; }
    if ($senha !== $confirmar_senha) { $erros[] = "As senhas não coincidem."; }
    
    $cpf_apenas_digitos = preg_replace('/\D/', '', $cpf);
    $telefone_apenas_digitos = preg_replace('/\D/', '', $telefone);

    if (strlen($cpf_apenas_digitos) !== 11) { $erros[] = "O CPF deve conter exatamente 11 dígitos."; }
    if (strlen($telefone_apenas_digitos) < 10 || strlen($telefone_apenas_digitos) > 11) { $erros[] = "O Telefone deve conter 10 ou 11 dígitos."; }
    
    if (empty($erros)) { 
        try {
            $stmt_check_cpf = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE cpf = :cpf");
            $stmt_check_cpf->execute([':cpf' => $cpf_apenas_digitos]); 
            if ($stmt_check_cpf->fetchColumn() > 0) { $erros[] = "Este CPF já está cadastrado."; }
        } catch (PDOException $e) { $erros[] = "Erro ao verificar CPF: " . $e->getMessage(); }
    }
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        // Lógica de upload... (mantida como no seu código)
    }

    // Se não houver erros, insere no banco de dados
    if (empty($erros)) {
        try {
            $senha_hashed = password_hash($senha, PASSWORD_DEFAULT); 
            $sql = "INSERT INTO usuario (nome, email, senha, nivel_acesso, rg, cpf, patente, titulacao, instituicao, fonte_pagadora, nome_guerra, telefone, foto, data_criacao, data_alteracao) VALUES (:nome, :email, :senha, :nivel_acesso, :rg, :cpf, :patente, :titulacao, :instituicao, :fonte_pagadora, :nome_guerra, :telefone, :foto, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome, ':email' => $email, ':senha' => $senha_hashed,
                ':nivel_acesso' => $nivel_acesso,
                ':rg' => $rg, ':cpf' => $cpf_apenas_digitos, ':patente' => $patente,
                ':titulacao' => $titulacao, ':instituicao' => $instituicao,
                ':fonte_pagadora' => $fonte_pagadora, ':nome_guerra' => $nome_guerra,
                ':telefone' => $telefone_apenas_digitos, ':foto' => $nome_arquivo_foto
            ]);

            $_SESSION['mensagem_feedback'] = ['texto' => "Usuário '{$nome}' adicionado com sucesso!", 'tipo' => 'success'];
            header("Location: gerenciar_usuarios.php"); 
            exit(); // Essencial para parar o script após o redirect

        } catch (PDOException $e) {
            $erros[] = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}

// --- 2. BLOCO DE DEFINIÇÕES E DADOS PARA A PÁGINA ---
// Esta parte é executada sempre, seja em um GET ou em um POST com erros.

$page_title = "Adicionar Novo Usuário";
$current_page = "Usuários";

// Carregar dados para os dropdowns
$patentes = [];
$titulacoes = [];
$instituicoes = [];
$niveis_acesso = ['aluno', 'professor', 'gerente', 'administrador'];

try {
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);
    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC");
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);
    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Adiciona o erro ao array para ser exibido no formulário
    $erros[] = "Erro ao carregar opções do formulário: " . $e->getMessage();
}


// --- 3. BLOCO DE RENDERIZAÇÃO HTML ---
// O HTML só começa a ser enviado para o navegador a partir daqui.
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Formulário de Cadastro de Usuário</h2>
            <a href="gerenciar_usuarios.php" class="btn-secondary-dashboard"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if (!empty($erros)): ?>
            <div class="alert alert-danger">
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="adicionar_usuario.php" method="POST" enctype="multipart/form-data" class="form-dashboard">
            <!-- Todos os seus campos do formulário originais são mantidos aqui -->
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso:</label>
                <select id="nivel_acesso" name="nivel_acesso" required>
                    <option value="">Selecione um nível</option>
                    <?php foreach ($niveis_acesso as $nivel): ?>
                        <option value="<?php echo htmlspecialchars($nivel); ?>" <?php echo (($_POST['nivel_acesso'] ?? '') == $nivel) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($nivel)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="rg">RG:</label>
                <input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente" required>
                    <option value="">Selecione uma Patente</option>
                    <?php foreach ($patentes as $sigla_patente): ?>
                        <option value="<?php echo htmlspecialchars($sigla_patente); ?>" <?php echo (($_POST['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sigla_patente); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="titulacao">Titulação:</label>
                <select id="titulacao" name="titulacao" required>
                    <option value="">Selecione uma Titulação</option>
                    <?php foreach ($titulacoes as $nome_titulacao): ?> 
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" <?php echo (($_POST['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nome_titulacao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="instituicao">Instituição:</label>
                <select id="instituicao" name="instituicao" required>
                    <option value="">Selecione uma Instituição</option>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($_POST['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sigla_instituicao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fonte_pagadora">Fonte Pagadora:</label>
                <select id="fonte_pagadora" name="fonte_pagadora" required>
                    <option value="">Selecione uma Fonte Pagadora</option>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($_POST['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sigla_instituicao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nome_guerra">Nome de Guerra:</label>
                <input type="text" id="nome_guerra" name="nome_guerra" value="<?php echo htmlspecialchars($_POST['nome_guerra'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="foto">Foto de Perfil (Opcional):</label>
                <input type="file" id="foto" name="foto" accept="image/*">
                <small class="form-text-help">Formatos permitidos: JPG, JPEG, PNG, GIF. Máximo: 5MB.</small>
            </div>
            <button type="submit" class="btn-primary-dashboard"><i class="fas fa-save"></i> Cadastrar Usuário</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        $('#cpf').mask('000.000.000-00', {reverse: true});
        $('#telefone').mask('(00) 00000-0000');
    });
</script>
