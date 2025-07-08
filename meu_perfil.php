<?php
// htdocs/avapm2/meu_perfil.php

// --- Configurações de Depuração (APENAS PARA DESENVOLVIMENTO) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Início da Sessão PHP ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir o título da página
$page_title = "Meu Perfil";

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login ---
// Se o usuário não estiver logado, redireciona para a página de login.
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você precisa estar logado para acessar esta página.'
    ];
    header('Location: login.php');
    exit();
}

// Obtém o ID do usuário logado da sessão para garantir que ele só edite o próprio perfil
$usuario_id_logado = $_SESSION['usuario_id'];

// --- Inicializar variáveis para o formulário e mensagens de feedback ---
$usuario = null; // Armazenará os dados do usuário a serem exibidos no formulário
$mensagem_feedback = '';
$feedback_tipo = '';

// Verifica se há uma mensagem de feedback na sessão (após um redirecionamento, por exemplo)
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a mensagem após exibição
}

// =====================================================================
// Lógica para carregar dados para os dropdowns (Patentes, Titulações, Instituições)
// Estes dados são necessários para pré-popular os selects.
// =====================================================================
$patentes = [];
$titulacoes = [];
$instituicoes = [];

try {
    // Carregar Patentes (puxando sigla)
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);

    // Carregar Titulações (puxando o nome)
    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC"); 
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);

    // Carregar Instituições (para 'instituicao' e 'fonte_pagadora')
    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    // Em caso de erro ao carregar dropdowns, define uma mensagem e loga o erro
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => "Erro ao carregar opções para o formulário: " . $e->getMessage()
    ];
    error_log("Erro PDO ao carregar dropdowns para meu_perfil.php: " . $e->getMessage());
    // Redireciona para evitar que a página seja carregada sem os dados essenciais
    header('Location: dashboard.php'); 
    exit();
}

// --- Lógica para carregar os dados do PRÓPRIO usuário logado ---
// Isso garante que um usuário só pode ver/editar seus próprios dados.
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, rg, cpf, telefone, nivel_acesso, foto,
                                  patente, titulacao, instituicao, fonte_pagadora 
                           FROM usuario 
                           WHERE id = :id_logado");
    $stmt->bindParam(':id_logado', $usuario_id_logado, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o usuário logado não for encontrado (situação incomum, mas por segurança)
    if (!$usuario) {
        session_destroy(); // Destrói a sessão, pois o ID na sessão é inválido
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => 'Seu usuário não foi encontrado. Por favor, faça login novamente.'
        ];
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao carregar dados do perfil do usuário ID {$usuario_id_logado}: " . $e->getMessage());
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Ocorreu um erro ao carregar seu perfil. Tente novamente mais tarde.'
    ];
    header('Location: dashboard.php'); 
    exit();
}

// --- Lógica para processar o formulário de atualização (quando enviado via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize e valide os inputs do formulário
    // Nota: O ID é do usuário logado, não vem do POST do formulário para segurança
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rg = $_POST['rg'] ?? ''; // Pega o valor bruto, será limpo depois
    $cpf = $_POST['cpf'] ?? ''; // Pega o valor bruto, será limpo depois
    $telefone = $_POST['telefone'] ?? ''; // Pega o valor bruto, será limpo depois
    // Nível de acesso NÃO é pego do POST, pois não pode ser alterado nesta página
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha_atual_digitada = $_POST['senha_atual'] ?? ''; // Senha atual do usuário para validação
    $nova_senha = $_POST['nova_senha'] ?? ''; // Nova senha (vazia se não for preenchida)
    $confirmar_nova_senha = $_POST['confirmar_nova_senha'] ?? '';

    // Array para armazenar erros de validação
    $erros = [];

    // --- Validações de Campos ---
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido ou vazio.';
    if (empty($rg)) $erros[] = 'O RG é obrigatório.';
    if (empty($cpf)) $erros[] = 'O CPF é obrigatório.';
    if (empty($telefone)) $erros[] = 'O telefone é obrigatório.';
    if (empty($patente)) $erros[] = 'A patente é obrigatória.';
    if (empty($titulacao)) $erros[] = 'A titulação é obrigatória.';
    if (empty($instituicao)) $erros[] = 'A instituição é obrigatória.';
    if (empty($fonte_pagadora)) $erros[] = 'A fonte pagadora é obrigatória.';

    // Limpar e validar RG, CPF, Telefone
    $rg_cleaned = preg_replace('/\D/', '', $rg);
    if (!empty($rg) && !ctype_digit($rg_cleaned)) {
        $erros[] = 'O RG deve conter apenas números.';
    }

    $cpf_cleaned = preg_replace('/\D/', '', $cpf);
    if (!empty($cpf) && strlen($cpf_cleaned) !== 11) {
        $erros[] = 'Formato de CPF inválido. O CPF deve conter 11 dígitos numéricos.';
    }
    
    $telefone_cleaned = preg_replace('/\D/', '', $telefone);
    if (!empty($telefone) && (strlen($telefone_cleaned) < 10 || strlen($telefone_cleaned) > 11)) {
        $erros[] = 'Formato de Telefone inválido. O Telefone deve conter 10 ou 11 dígitos (incluindo DDD).';
    }

    // Validação de senha atual (obrigatória para qualquer alteração)
    $stmt_check_senha = $pdo->prepare("SELECT senha FROM usuario WHERE id = :id");
    $stmt_check_senha->bindParam(':id', $usuario_id_logado);
    $stmt_check_senha->execute();
    $hash_senha_bd = $stmt_check_senha->fetchColumn();

    if (!password_verify($senha_atual_digitada, $hash_senha_bd)) {
        $erros[] = 'A senha atual informada está incorreta.';
    }

    // Validação de nova senha (somente se preenchida)
    $senha_para_atualizar_hash = null;
    if (!empty($nova_senha)) {
        if (strlen($nova_senha) < 6) {
            $erros[] = 'A nova senha deve ter no mínimo 6 caracteres.';
        }
        if ($nova_senha !== $confirmar_nova_senha) {
            $erros[] = 'A nova senha e a confirmação de senha não coincidem.';
        }
        if (empty($erros)) { // Só faz o hash se não houver erros na validação da nova senha
            $senha_para_atualizar_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        }
    }

    // --- Lógica para upload de nova foto de perfil ---
    $foto_perfil_bd = $usuario['foto'] ?? null; // Mantém a foto existente no DB por padrão

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "imagens/profiles/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $unique_name = uniqid('profile_') . '.' . $imageFileType;
        $target_file = $target_dir . $unique_name;
        $uploadOk = 1;

        $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
        if($check === false) { $erros[] = "O arquivo enviado não é uma imagem válida."; $uploadOk = 0; }
        if ($_FILES['foto_perfil']['size'] > 5000000) { $erros[] = "Desculpe, sua foto é muito grande (máximo 5MB)."; $uploadOk = 0; }
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $erros[] = "Desculpe, apenas arquivos JPG, JPEG, PNG e GIF são permitidos para fotos de perfil."; $uploadOk = 0;
        }

        if ($uploadOk == 1 && empty($erros)) { // Só tenta upload se não houver outros erros
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) { 
                if (!empty($usuario['foto']) && file_exists($target_dir . $usuario['foto'])) {
                    unlink($target_dir . $usuario['foto']); // Apaga a foto antiga
                }
                $foto_perfil_bd = $unique_name; // Atualiza o nome da foto para salvar no DB
            } else {
                $erros[] = "Ocorreu um erro desconhecido ao fazer o upload da sua foto.";
            }
        }
    }

    // Se não houver erros de validação, procede com a atualização no banco de dados
    if (empty($erros)) {
        try {
            $sql = "UPDATE usuario SET nome = :nome, email = :email, rg = :rg, cpf = :cpf, 
                            telefone = :telefone, foto = :foto, patente = :patente, 
                            titulacao = :titulacao, instituicao = :instituicao,
                            fonte_pagadora = :fonte_pagadora";
            
            if ($senha_para_atualizar_hash !== null) {
                $sql .= ", senha = :senha"; 
            }
            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':rg', $rg_cleaned); 
            $stmt->bindParam(':cpf', $cpf_cleaned);
            $stmt->bindParam(':telefone', $telefone_cleaned);
            $stmt->bindParam(':foto', $foto_perfil_bd);
            $stmt->bindParam(':patente', $patente);
            $stmt->bindParam(':titulacao', $titulacao);
            $stmt->bindParam(':instituicao', $instituicao);
            $stmt->bindParam(':fonte_pagadora', $fonte_pagadora);
            $stmt->bindParam(':id', $usuario_id_logado, PDO::PARAM_INT); // Usar o ID do usuário logado!
            
            if ($senha_para_atualizar_hash !== null) {
                $stmt->bindParam(':senha', $senha_para_atualizar_hash);
            }

            if ($stmt->execute()) {
                // Atualiza as informações da sessão para refletir as mudanças
                $_SESSION['nome_usuario'] = $nome;
                $_SESSION['email_usuario'] = $email;
                $_SESSION['foto_perfil'] = $foto_perfil_bd; // Atualiza a foto na sessão também

                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'success',
                    'texto' => 'Seus dados foram atualizados com sucesso!'
                ];
                header('Location: meu_perfil.php'); // Redireciona para recarregar a própria página
                exit();
            } else {
                $mensagem_feedback = 'Erro ao atualizar seu perfil no banco de dados. Tente novamente.';
                $feedback_tipo = 'danger';
            }
        } catch (PDOException $e) {
            error_log("Erro ao atualizar perfil no DB (ID: {$usuario_id_logado}): " . $e->getMessage());
            $mensagem_feedback = 'Erro interno do servidor ao atualizar seu perfil. Tente novamente mais tarde.';
            $feedback_tipo = 'danger';
        }
    } else {
        // Se houver erros de validação, prepara a mensagem de feedback
        $mensagem_feedback = implode('<br>', $erros);
        $feedback_tipo = 'danger';
        // Mescla os dados do POST de volta ao $usuario para pré-preencher o formulário
        $usuario = array_merge($usuario, $_POST); 
        $usuario['foto'] = $foto_perfil_bd; // Mantém a foto para o formulário
    }
}

// Incluir o cabeçalho do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
// Incluir a barra lateral do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1>Meu Perfil</h1>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>
                <?php if (!empty($usuario['foto']) && file_exists('imagens/profiles/' . $usuario['foto'])): ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de Perfil" style="max-width: 50px; height: auto; vertical-align: middle; margin-right: 10px; border-radius: 5px;">
                <?php endif; ?>
                 <?php echo htmlspecialchars($usuario['nome']); ?>
            </h2>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <form action="meu_perfil.php" method="POST" enctype="multipart/form-data" class="form-dashboard">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="rg">RG:</label>
                <input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($usuario['rg'] ?? ''); ?>" placeholder="Apenas números" required>
            </div>

            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($usuario['cpf'] ?? ''); ?>" placeholder="Ex: 999.999.999-99" required>
            </div>

            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" placeholder="Ex: (99) 99999-9999" required>
            </div>

            <div class="form-group">
                <label for="nivel_acesso_display">Nível de Acesso:</label>
                <input type="text" id="nivel_acesso_display" 
                       value="<?php echo htmlspecialchars($usuario['nivel_acesso'] ?? 'N/A'); ?>" disabled class="form-control-disabled">
                </div>

            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente" required>
                    <option value="">Selecione a Patente</option>
                    <?php foreach ($patentes as $sigla_patente): ?>
                        <option value="<?php echo htmlspecialchars($sigla_patente); ?>" 
                            <?php echo (($usuario['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sigla_patente); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="titulacao">Titulação:</label>
                <select id="titulacao" name="titulacao" required>
                    <option value="">Selecione a Titulação</option>
                    <?php foreach ($titulacoes as $nome_titulacao): ?> 
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" 
                            <?php echo (($usuario['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nome_titulacao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="instituicao">Instituição:</label>
                <select id="instituicao" name="instituicao" required>
                    <option value="">Selecione a Instituição</option>
                    <?php foreach ($instituicoes as $sigla_instituicao): ?>
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" 
                            <?php echo (($usuario['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" 
                            <?php echo (($usuario['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sigla_instituicao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="foto_perfil">Foto de Perfil Atual:</label>
                <?php if (!empty($usuario['foto']) && file_exists('imagens/profiles/' . $usuario['foto'])): ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de Perfil" style="max-width: 150px; height: auto; display: block; margin-bottom: 10px; border-radius: 5px;">
                <?php else: ?>
                    <p>Nenhuma foto de perfil.</p>
                <?php endif; ?>
                <label for="foto_perfil_nova">Alterar Foto de Perfil:</label>
                <input type="file" id="foto_perfil_nova" name="foto_perfil">
                <small>Formatos: JPG, JPEG, PNG, GIF. Máx: 5MB.</small>
            </div>

            <div class="form-group password-section">
                <h3>Alterar Senha (opcional)</h3>
                <label for="senha_atual">Senha Atual (obrigatório para salvar alterações):</label>
                <input type="password" id="senha_atual" name="senha_atual" placeholder="Digite sua senha atual" required>
            </div>

            <div class="form-group">
                <label for="nova_senha">Nova Senha:</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para não alterar">
                <small>Mínimo 6 caracteres.</small>
            </div>

            <div class="form-group">
                <label for="confirmar_nova_senha">Confirmar Nova Senha:</label>
                <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" placeholder="Repita a nova senha">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary-dashboard">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
                <button type="reset" class="btn-secondary-dashboard">
                    <i class="fas fa-undo"></i> Limpar Campos
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Incluir o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function(){
            $('#cpf').mask('000.000.000-00', {reverse: true});
            
            // Máscara para Telefone (10 ou 11 dígitos): (00) 00000-0000 ou (00) 0000-0000
            var SPMaskBehavior = function (val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            },
            spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };
            $('#telefone').mask(SPMaskBehavior, spOptions);

            // RG não tem máscara aqui, pois você especificou que a validação é apenas numérica no backend.
            // Para garantir que o usuário digite apenas números no RG, você pode adicionar um atributo pattern="[0-9]*"
            // no HTML do input de RG, ou um listener JavaScript para filtrar não-dígitos.
            $('#rg').on('input', function() {
                this.value = this.value.replace(/\D/g, ''); // Garante apenas números
            });
        });
    } else {
        console.warn("jQuery não está carregado. As máscaras de input podem não funcionar.");
    }
</script>