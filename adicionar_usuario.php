<?php
// Define o título da página
$page_title = "Adicionar Novo Usuário";

// Inclui o cabeçalho do dashboard (que já faz a verificação de login e inicia a sessão)
require_once __DIR__ . '/includes/templates/header_dashboard.php';

// Inclui a barra lateral (sidebar) do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

// Define a página atual para a sidebar destacar o link ativo
$current_page = "Usuários"; // Para manter o menu 'Usuários' ativo

// =====================================================================
// Lógica para carregar dados para os dropdowns (Patentes, Titulações, Instituições)
// =====================================================================
$patentes = [];
$titulacoes = []; // Agora vai puxar o nome
$instituicoes = [];
$niveis_acesso = ['aluno', 'professor', 'gerente', 'administrador']; // Níveis de acesso fixos ou do banco

try {
    // Carregar Patentes (ainda puxando sigla, se for o que você quer salvar na coluna 'patente')
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);

    // Carregar Titulações (AGORA PUXANDO O NOME)
    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC"); // Alterado para 'nome'
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);

    // Carregar Instituições (para 'instituicao' e 'fonte_pagadora')
    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $_SESSION['mensagem_erro'] = "Erro ao carregar dados para os dropdowns: " . $e->getMessage();
    // Em ambiente de produção, apenas logar o erro detalhado: error_log("Erro PDO ao carregar dropdowns: " . $e->getMessage());
}

// =====================================================================
// Processamento do formulário quando submetido (POST)
// =====================================================================
$erros = []; // Array para armazenar erros de validação
$sucesso = false;
$nome_arquivo_foto = null; // Para armazenar o nome da foto se houver upload

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Coleta e sanitiza os dados do formulário
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha']; 
    $confirmar_senha = $_POST['confirmar_senha'];
    $nivel_acesso = filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING);
    $rg = filter_input(INPUT_POST, 'rg', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING); // Titulação agora recebe o nome
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $nome_guerra = filter_input(INPUT_POST, 'nome_guerra', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);

    // 2. Validação dos dados
    // TODOS OS CAMPOS AGORA SÃO OBRIGATÓRIOS (EXCETO FOTO)
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
    
    // Verificação de email único
    try {
        $stmt_check_email = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = :email");
        $stmt_check_email->execute([':email' => $email]);
        if ($stmt_check_email->fetchColumn() > 0) {
            $erros[] = "Este email já está cadastrado.";
        }
    } catch (PDOException $e) {
        $erros[] = "Erro ao verificar email: " . $e->getMessage();
    }

    if (strlen($senha) < 6) { $erros[] = "A senha deve ter no mínimo 6 caracteres."; }
    if ($senha !== $confirmar_senha) { $erros[] = "As senhas não coincidem."; }
    if (!in_array($nivel_acesso, $niveis_acesso)) { $erros[] = "Nível de acesso inválido."; }

    // Validação para CPF e Telefone (11 dígitos)
    $cpf_apenas_digitos = preg_replace('/\D/', '', $cpf);
    $telefone_apenas_digitos = preg_replace('/\D/', '', $telefone);

    if (strlen($cpf_apenas_digitos) !== 11) { 
        $erros[] = "O CPF deve conter exatamente 11 dígitos.";
    }
    
    if (strlen($telefone_apenas_digitos) !== 11) { 
        $erros[] = "O Telefone deve conter exatamente 11 dígitos (incluindo DDD).";
    }

    // Validação para CPF ÚNICO (se não houver outros erros graves)
    if (empty($erros)) { 
        try {
            $stmt_check_cpf = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE cpf = :cpf_apenas_digitos");
            $stmt_check_cpf->execute([':cpf_apenas_digitos' => $cpf_apenas_digitos]); 
            if ($stmt_check_cpf->fetchColumn() > 0) {
                $erros[] = "Este CPF já está cadastrado.";
            }
        } catch (PDOException $e) {
            $erros[] = "Erro ao verificar CPF: " . $e->getMessage();
            error_log("Erro PDO ao verificar CPF duplicado: " . $e->getMessage());
        }
    }

    // 3. Processamento do upload de foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $diretorio_uploads = 'imagens/profiles/'; 
        if (!is_dir($diretorio_uploads)) {
            mkdir($diretorio_uploads, 0777, true); 
        }

        $nome_original = basename($_FILES['foto']['name']);
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($extensao, $tipos_permitidos)) {
            $erros[] = "Tipo de arquivo de foto não permitido. Apenas JPG, JPEG, PNG, GIF.";
        } elseif ($_FILES['foto']['size'] > 5 * 1024 * 1024) { 
            $erros[] = "A foto é muito grande. Tamanho máximo é 5MB.";
        } else {
            $nome_arquivo_foto = uniqid('profile_') . '.' . $extensao;
            $caminho_completo_arquivo = $diretorio_uploads . $nome_arquivo_foto;

            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_completo_arquivo)) {
                $erros[] = "Erro ao mover o arquivo de foto.";
                $nome_arquivo_foto = null; 
            }
        }
    }

    // 4. Se não houver erros, insere no banco de dados
    if (empty($erros)) {
        try {
            $senha_hashed = password_hash($senha, PASSWORD_DEFAULT); 

            $cpf_salvar = $cpf_apenas_digitos; // Salva o CPF apenas com dígitos
            $telefone_salvar = $telefone_apenas_digitos; // Salva o Telefone apenas com dígitos

            $sql = "INSERT INTO usuario (nome, email, senha, nivel_acesso, rg, cpf, patente, titulacao, instituicao, fonte_pagadora, nome_guerra, telefone, foto, data_criacao, data_alteracao)
                    VALUES (:nome, :email, :senha, :nivel_acesso, :rg, :cpf, :patente, :titulacao, :instituicao, :fonte_pagadora, :nome_guerra, :telefone, :foto, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha_hashed);
            $stmt->bindParam(':nivel_acesso', $nivel_acesso);
            $stmt->bindParam(':rg', $rg);
            $stmt->bindParam(':cpf', $cpf_salvar); 
            $stmt->bindParam(':patente', $patente);
            $stmt->bindParam(':titulacao', $titulacao); 
            $stmt->bindParam(':instituicao', $instituicao);
            $stmt->bindParam(':fonte_pagadora', $fonte_pagadora);
            $stmt->bindParam(':nome_guerra', $nome_guerra);
            $stmt->bindParam(':telefone', $telefone_salvar); 
            $stmt->bindParam(':foto', $nome_arquivo_foto); 

            if ($stmt->execute()) {
                $_SESSION['mensagem_feedback'] = ['texto' => "Usuário '{$nome}' adicionado com sucesso!", 'tipo' => 'sucesso'];
                header("Location: gerenciar_usuarios.php"); 
                exit();
            } else {
                $erros[] = "Erro ao adicionar usuário no banco de dados. Tente novamente.";
            }

        } catch (PDOException $e) {
            $erros[] = "Erro no banco de dados: " . $e->getMessage();
            error_log("Erro PDO ao adicionar usuário: " . $e->getMessage());
        }
    }
}
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
            <div class="user-avatar">
                <?php if (!empty($foto_perfil_usuario) && file_exists('imagens/profiles/' . $foto_perfil_usuario)) : ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($foto_perfil_usuario); ?>" alt="Avatar">
                <?php else : ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Formulário de Cadastro de Usuário</h2>
            <a href="gerenciar_usuarios.php" class="btn-secondary-dashboard">
                <i class="fas fa-arrow-left"></i> Voltar para Usuários
            </a>
        </div>

        <?php 
        // Exibe mensagens de erro
        if (!empty($erros)) {
            echo '<div class="alert alert-danger">';
            foreach ($erros as $erro) {
                echo '<p>' . htmlspecialchars($erro) . '</p>';
            }
            echo '</div>';
        }
        // Mensagens de feedback de operações anteriores (se houver, mas não deve haver aqui após POST)
        if (isset($_SESSION['mensagem_feedback'])) {
            $feedback = $_SESSION['mensagem_feedback'];
            echo '<div class="alert alert-' . htmlspecialchars($feedback['tipo']) . '">' . htmlspecialchars($feedback['texto']) . '</div>';
            unset($_SESSION['mensagem_feedback']);
        }
        ?>

        <form action="adicionar_usuario.php" method="POST" enctype="multipart/form-data" class="modern-form">
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

            <button type="submit" class="btn-primary-dashboard">
                <i class="fas fa-save"></i> Cadastrar Usuário
            </button>
        </form>
    </div>

</div>

<?php
// Inclui o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script>
    $(document).ready(function(){
        // Máscara para CPF: 000.000.000-00
        $('#cpf').mask('000.000.000-00', {reverse: true});

        // Máscara para Telefone (11 dígitos): (00) 00000-0000
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