<?php
// adicionar_usuario.php - Versão Corrigida

// --- Bloco de Processamento PHP ---
// Esta seção inteira é executada antes de qualquer HTML ser enviado.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);

$page_title = "Adicionar Novo Usuário";
$current_page = "Usuários";

// Inicialização de variáveis
$erros = [];
$nome_arquivo_foto = null;

// Carregar dados para dropdowns
try {
    $patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
    $titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
    $instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
    $niveis_acesso = ['ALUNO', 'PROFESSOR', 'GERENTE']; // Admin não pode ser criado por aqui
} catch (PDOException $e) {
    $erros[] = "Erro ao carregar opções do formulário: " . $e->getMessage();
}


// --- Lógica para processar o formulário (quando enviado via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitiza os dados
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha']; 
    $confirmar_senha = $_POST['confirmar_senha'];
    $nivel_acesso = filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING);
    $rg = preg_replace('/\D/', '', $_POST['rg'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $nome_guerra = filter_input(INPUT_POST, 'nome_guerra', FILTER_SANITIZE_STRING);

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($nivel_acesso) || empty($cpf)) {
        $erros[] = "Nome, Email, Senha, Nível de Acesso e CPF são obrigatórios.";
    }
    if ($senha !== $confirmar_senha) {
        $erros[] = "As senhas não coincidem.";
    }
    // Adicione outras validações aqui...

    // Se não houver erros, prossiga com a inserção
    if (empty($erros)) {
        try {
            // (Lógica de upload de foto aqui, se houver)

            $senha_hashed = password_hash($senha, PASSWORD_DEFAULT); 
            $sql = "INSERT INTO usuario (nome, email, senha, nivel_acesso, rg, cpf, patente, titulacao, instituicao, fonte_pagadora, nome_guerra, telefone, data_criacao)
                    VALUES (:nome, :email, :senha, :nivel_acesso, :rg, :cpf, :patente, :titulacao, :instituicao, :fonte_pagadora, :nome_guerra, :telefone, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senha_hashed,
                ':nivel_acesso' => $nivel_acesso,
                ':rg' => $rg,
                ':cpf' => $cpf,
                ':patente' => $patente,
                ':titulacao' => $titulacao,
                ':instituicao' => $instituicao,
                ':fonte_pagadora' => $fonte_pagadora,
                ':nome_guerra' => $nome_guerra,
                ':telefone' => $telefone
            ]);

            // Se a execução foi bem-sucedida, redireciona
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => "Usuário '{$nome}' adicionado com sucesso!"];
            header("Location: gerenciar_usuarios.php"); 
            exit(); // Termina o script aqui para o redirecionamento funcionar

        } catch (PDOException $e) {
            $erros[] = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}
// --- Fim do Bloco de Processamento PHP ---

// --- Bloco de Renderização HTML ---
// O HTML só começa a ser enviado para o navegador a partir daqui.
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
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
                            <?php echo ucfirst(strtolower($nivel)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Adicione os outros campos (cpf, rg, etc.) aqui, seguindo o mesmo padrão -->
            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="rg">RG:</label>
                <input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($_POST['rg'] ?? ''); ?>">
            </div>
             <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="foto">Foto de Perfil (Opcional):</label>
                <input type="file" id="foto" name="foto" accept="image/*">
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
        $('#cpf').mask('000.000.000-00');
        $('#telefone').mask('(00) 00000-0000');
    });
</script>
