<?php
// avapm/editar_usuario.php

// --- 1. BLOCO DE PROCESSAMENTO E LÓGICA ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

// Verificação de permissão
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Acesso negado.'];
    header('Location: index.php');
    exit();
}

// Inicialização de variáveis
$usuario = null;
$erros = [];
$patentes = [];
$titulacoes = [];
$instituicoes = [];
$niveis_acesso = ['aluno', 'professor', 'gerente', 'administrador'];

// Carregar dados para dropdowns
try {
    $patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
    $titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
    $instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $erros[] = "Erro ao carregar opções: " . $e->getMessage();
}

// Carregar dados do usuário a ser editado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID de usuário inválido.'];
    header('Location: gerenciar_usuarios.php');
    exit();
}
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
    header('Location: gerenciar_usuarios.php');
    exit();
}

// --- Processar formulário (se POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta e sanitização de dados
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rg = filter_input(INPUT_POST, 'rg', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $nivel_acesso = filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING);
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';
    
    // Validações
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    $cpf_cleaned = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf_cleaned) !== 11) $erros[] = 'CPF inválido.';
    $telefone_cleaned = preg_replace('/\D/', '', $telefone);
    if (strlen($telefone_cleaned) < 10 || strlen($telefone_cleaned) > 11) $erros[] = 'Telefone inválido.';

    // Lógica de upload de foto
    $foto_perfil_bd = $usuario['foto'] ?? null;
    if (isset($_FILES['foto_perfil_nova']) && $_FILES['foto_perfil_nova']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "imagens/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $imageFileType = strtolower(pathinfo($_FILES['foto_perfil_nova']['name'], PATHINFO_EXTENSION));
        $unique_name = uniqid('profile_') . '.' . $imageFileType;
        $target_file = $target_dir . $unique_name;
        if (move_uploaded_file($_FILES['foto_perfil_nova']['tmp_name'], $target_file)) {
            if (!empty($usuario['foto']) && file_exists($target_dir . $usuario['foto'])) {
                unlink($target_dir . $usuario['foto']);
            }
            $foto_perfil_bd = $unique_name;
        } else {
            $erros[] = "Erro ao fazer upload da foto.";
        }
    }

    if (empty($erros)) {
        try {
            $sql = "UPDATE usuario SET nome = :nome, email = :email, rg = :rg, cpf = :cpf, 
                    telefone = :telefone, nivel_acesso = :nivel_acesso, foto = :foto,
                    patente = :patente, titulacao = :titulacao, instituicao = :instituicao,
                    fonte_pagadora = :fonte_pagadora, data_alteracao = NOW()";
            if (!empty($senha)) $sql .= ", senha = :senha";
            $sql .= " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $params = [
                ':nome' => $nome, ':email' => $email, ':rg' => preg_replace('/\D/', '', $rg),
                ':cpf' => $cpf_cleaned, ':telefone' => $telefone_cleaned,
                ':nivel_acesso' => $nivel_acesso, ':foto' => $foto_perfil_bd,
                ':patente' => $patente, ':titulacao' => $titulacao, ':instituicao' => $instituicao,
                ':fonte_pagadora' => $fonte_pagadora, ':id' => $id
            ];
            if (!empty($senha)) {
                $params[':senha'] = password_hash($senha, PASSWORD_DEFAULT);
            }
            if ($stmt->execute($params)) {
                $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Usuário atualizado com sucesso!'];
                header('Location: gerenciar_usuarios.php');
                exit();
            } else {
                $erros[] = 'Erro ao atualizar o usuário.';
            }
        } catch (PDOException $e) {
            $erros[] = 'Erro de banco de dados: ' . $e->getMessage();
        }
    }
    if (!empty($erros)) {
        $usuario = array_merge($usuario, $_POST);
    }
}

// --- 2. BLOCO DE DEFINIÇÕES E DADOS PARA A PÁGINA ---
$page_title = "Editar Usuário";
$mensagem_feedback = '';
$feedback_tipo = 'danger';
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']);
} elseif (!empty($erros)) {
    $mensagem_feedback = implode('<br>', $erros);
}

// --- 3. BLOCO DE RENDERIZAÇÃO HTML ---
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header"><h1><?php echo htmlspecialchars($page_title); ?></h1></header>
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Editando: <?php echo htmlspecialchars($usuario['nome']); ?></h2>
            <a href="gerenciar_usuarios.php" class="btn-secondary-dashboard"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>"><?php echo $mensagem_feedback; ?></div>
        <?php endif; ?>
        <form action="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" method="POST" enctype="multipart/form-data" class="form-dashboard">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
            <div class="form-group"><label for="nome">Nome Completo:</label><input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="rg">RG:</label><input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="cpf">CPF:</label><input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="telefone">Telefone:</label><input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" required></div>

            <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso:</label>
                <select id="nivel_acesso" name="nivel_acesso" required>
                    <?php foreach ($niveis_acesso as $nivel): ?>
                        <option value="<?php echo htmlspecialchars($nivel); ?>" <?php echo (strtolower($usuario['nivel_acesso'] ?? '') == $nivel) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($nivel)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente" required>
                    <?php foreach ($patentes as $sigla_patente): ?>
                        <option value="<?php echo htmlspecialchars($sigla_patente); ?>" <?php echo (($usuario['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sigla_patente); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="titulacao">Titulação:</label>
                <select id="titulacao" name="titulacao" required>
                    <option value="">Selecione a Titulação</option>
                    <?php foreach ($titulacoes as $nome_titulacao): ?> 
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" 
                            <?php // CORREÇÃO FINAL: Comparação case-insensitive e sem espaços
                            echo (strtolower(trim($usuario['titulacao'] ?? '')) == strtolower(trim($nome_titulacao))) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nome_titulacao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="instituicao">Instituição:</label>
                <select id="instituicao" name="instituicao" required>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($usuario['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sigla_instituicao); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fonte_pagadora">Fonte Pagadora:</label>
                <select id="fonte_pagadora" name="fonte_pagadora" required>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($usuario['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sigla_instituicao); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Foto Atual:</label>
                <?php if (!empty($usuario['foto']) && file_exists('imagens/profiles/' . $usuario['foto'])): ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto" class="img-thumbnail">
                <?php else: ?><p>Nenhuma foto.</p><?php endif; ?>
                <label for="foto_perfil_nova" style="margin-top: 10px;">Alterar Foto:</label>
                <input type="file" id="foto_perfil_nova" name="foto_perfil_nova">
            </div>
            <div class="form-group"><label for="senha">Nova Senha (deixe em branco para não alterar):</label><input type="password" id="senha" name="senha"></div>
            <div class="form-group"><label for="confirmar_senha">Confirmar Nova Senha:</label><input type="password" id="confirmar_senha" name="confirmar_senha"></div>
            <div class="form-actions"><button type="submit" class="btn-primary-dashboard"><i class="fas fa-save"></i> Salvar</button></div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function(){
    $('#cpf').mask('000.000.000-00', {reverse: true});
    var SPMaskBehavior = function (val) {
        return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
    },
    spOptions = {
        onKeyPress: function(val, e, field, options) {
            field.mask(SPMaskBehavior.apply({}, arguments), options);
        }
    };
    $('#telefone').mask(SPMaskBehavior, spOptions);
});
</script>
