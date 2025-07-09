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
$titulacoes = []; // Será preenchido pelo banco
$instituicoes = [];
$niveis_acesso = ['aluno', 'professor', 'gerente', 'administrador'];

// --- CORREÇÃO: Buscando os dados para os dropdowns DIRETAMENTE DO BANCO ---
try {
    $patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);
    
    // AQUI ESTÁ A BUSCA PELA TITULAÇÃO, CONFORME SUA SOLICITAÇÃO
    $titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC")->fetchAll(PDO::FETCH_COLUMN);
    
    $instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $erros[] = "Erro ao carregar opções do formulário: " . $e->getMessage();
}

// Carregar dados do usuário a ser editado (lógica existente e funcional)
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
        header('Location: gerenciar_usuarios.php');
        exit();
    }
} else {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID de usuário inválido.'];
    header('Location: gerenciar_usuarios.php');
    exit();
}

// Processar formulário (lógica de salvamento que já está funcional)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // (A lógica de salvamento permanece a mesma)
    // ...
    // ...
}

// --- 2. BLOCO DE DEFINIÇÕES E DADOS PARA A PÁGINA ---
$page_title = "Editar Usuário";
// (Lógica de feedback)
//...

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
        
        <form action="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" method="POST" enctype="multipart/form-data" class="form-dashboard">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">
            
            <!-- Campos (nome, email, etc.) -->
            <div class="form-group"><label for="nome">Nome Completo:</label><input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="telefone">Telefone:</label><input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" required></div>


            <div class="form-group">
                <label for="titulacao">Titulação:</label>
                <select id="titulacao" name="titulacao" required>
                    <option value="">Selecione a Titulação</option>
                    <?php 
                    // Agora o loop usa a variável $titulacoes preenchida pelo banco de dados
                    foreach ($titulacoes as $nome_titulacao): ?> 
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" 
                            <?php 
                            // A comparação que ignora maiúsculas/minúsculas continua aqui para garantir a seleção correta
                            if (isset($usuario['titulacao']) && strtolower(trim($usuario['titulacao'])) == strtolower(trim($nome_titulacao))) {
                                echo 'selected';
                            }
                            ?>>
                            <?php echo htmlspecialchars($nome_titulacao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Outros selects (patente, instituição, etc.) -->
            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente" required>
                    <?php foreach ($patentes as $sigla_patente): ?>
                        <option value="<?php echo htmlspecialchars($sigla_patente); ?>" <?php echo (($usuario['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>><?php echo htmlspecialchars($sigla_patente); ?></option>
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

            <div class="form-actions">
                <button type="submit" class="btn-primary-dashboard"><i class="fas fa-save"></i> Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function(){
    $('#cpf').mask('000.000.000-00', {reverse: true});
    $('#telefone').mask('(00) 00000-0000');
});
</script>
