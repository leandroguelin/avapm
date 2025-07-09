<?php
// htdocs/avapm/editar_usuario.php

// Iniciar a sessão para usar variáveis de sessão e verificar login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir o título da página
$page_title = "Editar Usuário";

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php'; // Caminho para o seu arquivo de conexão

// Níveis de acesso permitidos para esta página
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE']; 

// Redirecionar se o usuário NÃO estiver logado OU NÃO tiver um dos níveis de acesso permitidos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para acessar esta página.'
    ];
    header('Location: index.php'); // Redirecionar para a página de login ou dashboard
    exit();
}

// Incluir o cabeçalho do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
// Incluir a barra lateral do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

// Inicializar variáveis para o formulário
$usuario = null;
$mensagem_feedback = '';
$feedback_tipo = '';

// =====================================================================
// Lógica para carregar dados para os dropdowns (Patentes, Titulações, Instituições)
// =====================================================================
$patentes = [];
$titulacoes = [];
$instituicoes = [];
$niveis_acesso_display = ['Aluno', 'Professor', 'Gerente', 'Administrador']; // Para o dropdown de nível de acesso

try {
    // Carregar Patentes (puxando sigla, que é o que você quer salvar na coluna 'patente')
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);

    // Carregar Titulações (puxando o nome para salvar na coluna 'titulacao')
    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC"); 
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);

    // Carregar Instituições (para 'instituicao' e 'fonte_pagadora')
    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $_SESSION['mensagem_feedback'] = [ // Usando feedback para consistência
        'tipo' => 'danger',
        'texto' => "Erro ao carregar dados para os dropdowns: " . $e->getMessage()
    ];
    // Em ambiente de produção, apenas logar o erro detalhado: error_log("Erro PDO ao carregar dropdowns: " . $e->getMessage());
}


// --- Lógica para carregar os dados do usuário a ser editado ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_usuario = (int)$_GET['id'];

    try {
        // Seleciona todos os campos necessários do usuário para pré-preencher o formulário
        $stmt = $pdo->prepare("SELECT id, nome, email, rg, cpf, telefone, nivel_acesso, foto,
                                      patente, titulacao, instituicao, fonte_pagadora 
                               FROM usuario 
                               WHERE id = :id");
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se o usuário com o ID fornecido não for encontrado
        if (!$usuario) {
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Usuário não encontrado.'
            ];
            header('Location: gerenciar_usuarios.php'); // Redireciona de volta com erro
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erro ao carregar usuário para edição: " . $e->getMessage());
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => 'Erro ao carregar dados do usuário. Tente novamente.'
        ];
        header('Location: gerenciar_usuarios.php'); // Redireciona de volta com erro
        exit();
    }
} else {
    // Se o ID não foi passado ou é inválido
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'ID do usuário inválido para edição.'
    ];
    header('Location: gerenciar_usuarios.php'); // Redireciona de volta com erro
    exit();
}

// --- Lógica para processar o formulário de atualização (quando enviado via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize e valide os inputs do formulário
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    // Para RG, vamos pegar o valor bruto e validar apena se é numérico.
    $rg = $_POST['rg'] ?? ''; 
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    // Padronizar o nível de acesso vindo do POST para o formato correto ('Administrador', etc.)
    $nivel_acesso = ucwords(strtolower(trim(filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING)))); 
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_STRING);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_STRING);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_STRING);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? ''; // Nova senha (vazia se não for preenchida)
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Array para armazenar erros de validação
    $erros = [];
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido ou vazio.';
    if (empty($nivel_acesso)) $erros[] = 'Nível de acesso é obrigatório.';
    
    // Validação para os campos agora como OBRIGATÓRIOS (se essa é a intenção)
    if (empty($rg)) $erros[] = 'O RG é obrigatório.';
    if (empty($cpf)) $erros[] = 'O CPF é obrigatório.';
    if (empty($telefone)) $erros[] = 'O telefone é obrigatório.';
    if (empty($patente)) $erros[] = 'A patente é obrigatória.';
    if (empty($titulacao)) $erros[] = 'A titulação é obrigatória.';
    if (empty($instituicao)) $erros[] = 'A instituição é obrigatória.';
    if (empty($fonte_pagadora)) $erros[] = 'A fonte pagadora é obrigatória.';


    // Validação de CPF (11 dígitos numéricos)
    $cpf_cleaned = preg_replace('/\D/', '', $cpf); // Remove caracteres não numéricos
    if (!empty($cpf) && strlen($cpf_cleaned) !== 11) {
        $erros[] = 'Formato de CPF inválido. O CPF deve conter 11 dígitos numéricos.';
    }
    
    // Validação de RG: Apenas números, SEM RESTRIÇÃO DE COMPRIMENTO.
    $rg_cleaned = preg_replace('/\D/', '', $rg); // Remove caracteres não numéricos
    if (!empty($rg) && !ctype_digit($rg_cleaned)) { // ctype_digit verifica se a string contém apenas dígitos
        $erros[] = 'O RG deve conter apenas números.';
    }
    // A validação de comprimento (7 a 9 dígitos) FOI REMOVIDA AQUI.

    // Validação de Telefone (10 ou 11 dígitos numéricos)
    $telefone_cleaned = preg_replace('/\D/', '', $telefone);
    if (!empty($telefone) && (strlen($telefone_cleaned) < 10 || strlen($telefone_cleaned) > 11)) {
        $erros[] = 'Formato de Telefone inválido. O Telefone deve conter 10 ou 11 dígitos (incluindo DDD).';
    }


    // Validação de senha, somente se o campo "Nova Senha" foi preenchido
    if (!empty($senha)) {
        if (strlen($senha) < 6) {
            $erros[] = 'A nova senha deve ter no mínimo 6 caracteres.';
        }
        if ($senha !== $confirmar_senha) {
            $erros[] = 'A confirmação da nova senha não confere.';
        }
    }

    // Lógica para upload de nova foto de perfil
    $foto_perfil_bd = $usuario['foto'] ?? null; // Mantém a foto existente no DB por padrão

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "imagens/profiles/";
        // Cria a pasta se não existir, com permissões de escrita
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Permissão 0777 para desenvolvimento, ajustar em produção
        }

        $imageFileType = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        // Gera um nome único para o arquivo para evitar conflitos
        $unique_name = uniqid('profile_') . '.' . $imageFileType;
        $target_file = $target_dir . $unique_name;
        $uploadOk = 1;

        // Verifica se o arquivo é uma imagem real
        $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
        if($check === false) {
            $erros[] = "O arquivo enviado não é uma imagem válida.";
            $uploadOk = 0;
        }

        // Limita o tamanho do arquivo (5MB)
        if ($_FILES['foto_perfil']['size'] > 5000000) {
            $erros[] = "Desculpe, sua foto é muito grande (máximo 5MB).";
            $uploadOk = 0;
        }

        // Permite apenas certos formatos de imagem
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $erros[] = "Desculpe, apenas arquivos JPG, JPEG, PNG e GIF são permitidos para fotos de perfil.";
            $uploadOk = 0;
        }

        // Tenta fazer o upload se não houver erros até agora
        if ($uploadOk == 1) {
            // Caminho completo para mover o arquivo (corrigido a variável $caminho_completo_arquivo para $target_file)
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) { 
                // Se o upload foi bem-sucedido, apaga a foto antiga se existir
                if (!empty($usuario['foto']) && file_exists($target_dir . $usuario['foto'])) {
                    unlink($target_dir . $usuario['foto']);
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
            // Constrói a query SQL para atualização
            $sql = "UPDATE usuario SET nome = :nome, email = :email, rg = :rg, cpf = :cpf, 
                    telefone = :telefone, nivel_acesso = :nivel_acesso, foto = :foto,
                    patente = :patente, titulacao = :titulacao, instituicao = :instituicao,
                    fonte_pagadora = :fonte_pagadora";
            
            // Adiciona a atualização de senha APENAS se uma nova senha foi fornecida
            if (!empty($senha)) {
                $sql .= ", senha = :senha"; 
            }
            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            // Vincula os parâmetros à query SQL
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            // Salva apenas os dígitos do RG, CPF e Telefone (RG só dígitos, sem máscara)
            $stmt->bindParam(':rg', $rg_cleaned); 
            $stmt->bindParam(':cpf', $cpf_cleaned);
            $stmt->bindParam(':telefone', $telefone_cleaned);
            $stmt->bindParam(':nivel_acesso', $nivel_acesso); // Nível de acesso padronizado
            $stmt->bindParam(':foto', $foto_perfil_bd); // Nome da foto a ser salva no DB
            $stmt->bindParam(':patente', $patente);
            $stmt->bindParam(':titulacao', $titulacao);
            $stmt->bindParam(':instituicao', $instituicao);
            $stmt->bindParam(':fonte_pagadora', $fonte_pagadora);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            // Se uma nova senha foi fornecida, faz o hash e vincula
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt->bindParam(':senha', $senha_hash);
            }

            // Executa a query de atualização
            if ($stmt->execute()) {
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'success',
                    'texto' => 'Usuário atualizado com sucesso!'
                ];
                header('Location: gerenciar_usuarios.php'); // Redireciona para a página de gerenciamento
                exit();
            } else {
                $mensagem_feedback = 'Erro ao atualizar usuário no banco de dados. Tente novamente.';
                $feedback_tipo = 'danger';
            }
        } catch (PDOException $e) {
            // Captura e loga erros de banco de dados
            error_log("Erro ao atualizar usuário no DB: " . $e->getMessage());
            $mensagem_feedback = 'Erro interno do servidor ao atualizar usuário. Tente novamente mais tarde.';
            $feedback_tipo = 'danger';
        }
    } else {
        // Se houver erros de validação, prepara a mensagem de feedback
        $mensagem_feedback = implode('<br>', $erros);
        $feedback_tipo = 'danger';
        // Se houver erros, mescla os dados do POST de volta ao $usuario
        // para que o formulário seja pré-preenchido com o que o usuário digitou
        $usuario = array_merge($usuario, $_POST); 
        $usuario['foto'] = $foto_perfil_bd; // Mantém a foto atualizada ou existente para o formulário
    }
}

// Se houver mensagem de feedback na sessão (vindo de um redirecionamento, como após atualização)
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a mensagem após exibir
}

// Neste ponto, $usuario sempre estará definido, pois o script teria sido interrompido
// se o ID fosse inválido ou o usuário não existisse.
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? 'Usuário'); ?></span>
            <div class="user-avatar">
                <?php 
                $foto_src = '';
                if (!empty($_SESSION['foto_perfil']) && file_exists('imagens/profiles/' . $_SESSION['foto_perfil'])) {
                    $foto_src = 'imagens/profiles/' . htmlspecialchars($_SESSION['foto_perfil']);
                } 
                ?>
                <?php if (!empty($foto_src)) : ?>
                    <img src="<?php echo $foto_src; ?>" alt="Avatar do Usuário Logado" style="max-width: 30px; height: auto; border-radius: 50%;">
                <?php else : ?>
                    <i class="fas fa-user-circle fa-lg"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>
                <?php if (!empty($usuario['foto']) && file_exists('imagens/profiles/' . $usuario['foto'])): ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($usuario['foto']); ?>" alt="Foto de Perfil" style="max-width: 50px; height: auto; vertical-align: middle; margin-right: 10px; border-radius: 5px;">
                <?php endif; ?>
                <?php echo htmlspecialchars($usuario['nome']); ?>
            </h2>
            <a href="gerenciar_usuarios.php" class="btn-secondary-dashboard">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <form action="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" method="POST" enctype="multipart/form-data" class="form-dashboard">
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
                <label for="nivel_acesso">Nível de Acesso:</label>
                <select id="nivel_acesso" name="nivel_acesso" required>
                    <option value="">Selecione um nível</option>
                    <?php foreach ($niveis_acesso_display as $nivel_opt): // Usar a lista do array PHP para o select ?>
                        <option value="<?php echo htmlspecialchars($nivel_opt); ?>" 
                            <?php echo (($usuario['nivel_acesso'] ?? '') == $nivel_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nivel_opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="patente">Patente:</label>
                <select id="patente" name="patente" required>
                    <option value="">Selecione a Patente</option>
                    <?php foreach ($patentes as $sigla_patente): ?>
                        <option value="<?php echo htmlspecialchars($sigla_patente); ?>" <?php echo (($usuario['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo htmlspecialchars($nome_titulacao); ?>" <?php echo (($usuario['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($usuario['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
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
                        <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>" <?php echo (($usuario['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
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

            <div class="form-group">
                <label for="senha">Nova Senha (deixe em branco para não alterar):</label>
                <input type="password" id="senha" name="senha">
                <small>Mínimo 6 caracteres.</small>
            </div>

            <div class="form-group">
                <label for="confirmar_senha">Confirmar Nova Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha">
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

            // Removida a máscara do RG. O input de RG agora aceita qualquer caractere no front-end,
            // mas a validação PHP no back-end ainda garantirá que sejam apenas números.
        });
    } else {
        console.warn("jQuery não está carregado. As máscaras de input podem não funcionar.");
    }
</script>