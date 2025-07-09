<?php
// htdocs/avapm/editar_usuario.php

// Iniciar a sessão para usar variáveis de sessão e verificar login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir o título da página
$page_title = "Editar Usuário";

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php'; 

// Níveis de acesso permitidos para esta página
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE']; 

// Redirecionar se o usuário NÃO estiver logado OU NÃO tiver um dos níveis de acesso permitidos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para acessar esta página.'
    ];
    header('Location: index.php');
    exit();
}

// Incluir o cabeçalho e a barra lateral do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

// Inicializar variáveis
$usuario = null;
$mensagem_feedback = '';
$feedback_tipo = '';

// =====================================================================
// Lógica para carregar dados para os dropdowns
// =====================================================================
$patentes = [];
$titulacoes = [];
$instituicoes = [];
// Array para o dropdown de nível de acesso (valores em minúsculo para consistência com o BD)
$niveis_acesso = ['aluno', 'professor', 'gerente', 'administrador']; 

try {
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);

    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC"); 
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);

    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => "Erro ao carregar opções do formulário: " . $e->getMessage()
    ];
}

// --- Lógica para carregar os dados do usuário a ser editado ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_usuario = (int)$_GET['id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id = :id");
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Usuário não encontrado.'];
            header('Location: gerenciar_usuarios.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar usuário para edição: " . $e->getMessage());
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar dados do usuário.'];
        header('Location: gerenciar_usuarios.php');
        exit();
    }
} else {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID do usuário inválido.'];
    header('Location: gerenciar_usuarios.php');
    exit();
}

// --- Lógica para processar o formulário de atualização (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize e valide os inputs
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rg = filter_input(INPUT_POST, 'rg', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    // CORREÇÃO: Obter o nível de acesso em minúsculas, como esperado pelo BD
    $nivel_acesso = filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING); 
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    $erros = [];
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido ou vazio.';
    if (empty($nivel_acesso)) $erros[] = 'Nível de acesso é obrigatório.';
    if (empty($rg)) $erros[] = 'O RG é obrigatório.';
    if (empty($cpf)) $erros[] = 'O CPF é obrigatório.';
    if (empty($telefone)) $erros[] = 'O telefone é obrigatório.';
    if (empty($patente)) $erros[] = 'A patente é obrigatória.';
    if (empty($titulacao)) $erros[] = 'A titulação é obrigatória.';
    if (empty($instituicao)) $erros[] = 'A instituição é obrigatória.';
    if (empty($fonte_pagadora)) $erros[] = 'A fonte pagadora é obrigatória.';
    
    $cpf_cleaned = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf_cleaned) !== 11) $erros[] = 'CPF inválido.';
    
    $telefone_cleaned = preg_replace('/\D/', '', $telefone);
    if (strlen($telefone_cleaned) < 10 || strlen($telefone_cleaned) > 11) $erros[] = 'Telefone inválido.';
    
    $rg_cleaned = preg_replace('/\D/', '', $rg);
    if (!ctype_digit($rg_cleaned)) $erros[] = 'RG deve conter apenas números.';

    if (!empty($senha)) {
        if (strlen($senha) < 6) $erros[] = 'A nova senha deve ter no mínimo 6 caracteres.';
        if ($senha !== $confirmar_senha) $erros[] = 'As senhas não coincidem.';
    }

    // Lógica de upload de nova foto
    $foto_perfil_bd = $usuario['foto'] ?? null;
    // CORREÇÃO: Usar 'foto_perfil_nova' para consistência
    if (isset($_FILES['foto_perfil_nova']) && $_FILES['foto_perfil_nova']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "imagens/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $imageFileType = strtolower(pathinfo($_FILES['foto_perfil_nova']['name'], PATHINFO_EXTENSION));
        $unique_name = uniqid('profile_') . '.' . $imageFileType;
        $target_file = $target_dir . $unique_name;
        
        $check = getimagesize($_FILES['foto_perfil_nova']['tmp_name']);
        if ($check === false) {
            $erros[] = "O arquivo não é uma imagem válida.";
        }
        if ($_FILES['foto_perfil_nova']['size'] > 5000000) {
            $erros[] = "A foto é muito grande (máx 5MB).";
        }
        if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            $erros[] = "Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
        }

        if (empty($erros) && move_uploaded_file($_FILES['foto_perfil_nova']['tmp_name'], $target_file)) {
            if (!empty($usuario['foto']) && file_exists($target_dir . $usuario['foto'])) {
                unlink($target_dir . $usuario['foto']);
            }
            $foto_perfil_bd = $unique_name;
        } else if (empty($erros)) {
             $erros[] = "Ocorreu um erro ao fazer o upload da sua foto.";
        }
    }

    if (empty($erros)) {
        try {
            $sql = "UPDATE usuario SET nome = :nome, email = :email, rg = :rg, cpf = :cpf, 
                    telefone = :telefone, nivel_acesso = :nivel_acesso, foto = :foto,
                    patente = :patente, titulacao = :titulacao, instituicao = :instituicao,
                    fonte_pagadora = :fonte_pagadora";
            
            if (!empty($senha)) $sql .= ", senha = :senha";
            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':rg', $rg_cleaned);
            $stmt->bindParam(':cpf', $cpf_cleaned);
            $stmt->bindParam(':telefone', $telefone_cleaned);
            // CORREÇÃO: O nível de acesso já está em minúsculo
            $stmt->bindParam(':nivel_acesso', $nivel_acesso);
            $stmt->bindParam(':foto', $foto_perfil_bd);
            $stmt->bindParam(':patente', $patente);
            $stmt->bindParam(':titulacao', $titulacao);
            $stmt->bindParam(':instituicao', $instituicao);
            $stmt->bindParam(':fonte_pagadora', $fonte_pagadora);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt->bindParam(':senha', $senha_hash);
            }

            if ($stmt->execute()) {
                $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Usuário atualizado com sucesso!'];
                header('Location: gerenciar_usuarios.php');
                exit();
            } else {
                $mensagem_feedback = 'Erro ao atualizar usuário.';
                $feedback_tipo = 'danger';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar usuário no DB: " . $e->getMessage());
            $mensagem_feedback = 'Erro no banco de dados: ' . $e->getMessage();
            $feedback_tipo = 'danger';
        }
    } else {
        $mensagem_feedback = implode('<br>', $erros);
        $feedback_tipo = 'danger';
        $usuario = array_merge($usuario, $_POST);
        $usuario['foto'] = $foto_perfil_bd;
    }
}

if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']);
}
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>
                <?php if (!empty($usuario['foto']) && file_exists('imagens/profiles/' . $usuario['foto'])): ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de Perfil" class="img-thumbnail-small">
                <?php endif; ?>
                Editando: <?php echo htmlspecialchars($usuario['nome']); ?>
            </h2>
            <a href="gerenciar_usuarios.php" class="btn-secondary-dashboard"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>"><?php echo $mensagem_feedback; ?></div>
        <?php endif; ?>

        <form action="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" method="POST" enctype="multipart/form-data" class="form-dashboard">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">

            <div class="form-group"><label for="nome">Nome Completo:</label><input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="rg">RG:</label><input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>" placeholder="Apenas números" required></div>
            <div class="form-group"><label for="cpf">CPF:</label><input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" placeholder="Ex: 999.999.999-99" required></div>
            <div class="form-group"><label for="telefone">Telefone:</label><input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" placeholder="Ex: (99) 99999-9999" required></div>

            <div class="form-group">
                <label for="nivel_acesso">Nível de Acesso:</label>
                <select id="nivel_acesso" name="nivel_acesso" required>
                    <option value="">Selecione um nível</option>
                    <?php foreach ($niveis_acesso as $nivel): // CORREÇÃO: Loop no array de minúsculas ?>
                        <option value="<?php echo htmlspecialchars($nivel); ?>" 
                            <?php // CORREÇÃO: Comparação correta (ambos minúsculos)
                            echo (strtolower($usuario['nivel_acesso'] ?? '') == $nivel) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($nivel)); // Exibe com a primeira letra maiúscula ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente" required>
                    <option value="">Selecione a Patente</option>
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
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" <?php echo (($usuario['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nome_titulacao); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="instituicao">Instituição:</label>
                <select id="instituicao" name="instituicao" required>
                    <option value="">Selecione a Instituição</option>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($usuario['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sigla_instituicao); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fonte_pagadora">Fonte Pagadora:</label>
                <select id="fonte_pagadora" name="fonte_pagadora" required>
                    <option value="">Selecione uma Fonte Pagadora</option>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($usuario['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sigla_instituicao); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Foto de Perfil Atual:</label>
                <?php if (!empty($usuario['foto']) && file_exists('imagens/profiles/' . $usuario['foto'])): ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de Perfil" class="img-thumbnail">
                <?php else: ?>
                    <p>Nenhuma foto de perfil.</p>
                <?php endif; ?>
                <label for="foto_perfil_nova" style="margin-top: 10px;">Alterar Foto de Perfil:</label>
                <!-- CORREÇÃO: Nome do input alinhado com o PHP -->
                <input type="file" id="foto_perfil_nova" name="foto_perfil_nova">
                <small>Formatos: JPG, PNG, GIF. Máx: 5MB.</small>
            </div>

            <div class="form-group"><label for="senha">Nova Senha (deixe em branco para não alterar):</label><input type="password" id="senha" name="senha"><small>Mínimo 6 caracteres.</small></div>
            <div class="form-group"><label for="confirmar_senha">Confirmar Nova Senha:</label><input type="password" id="confirmar_senha" name="confirmar_senha"></div>

            <div class="form-actions"><button type="submit" class="btn-primary-dashboard"><i class="fas fa-save"></i> Salvar Alterações</button></div>
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
