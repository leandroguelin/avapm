php
<?php
// processa_cadastro.php - Processa o formulário de cadastro (dinâmico para Aluno e Professor)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtém o nível de acesso selecionado pelo usuário
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'ALUNO'; // Default para ALUNO se não for enviado

    // Obtém os dados básicos do formulário
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    $erros = [];
    $form_data = ['nome' => $nome, 'cpf' => $cpf, 'email' => $email]; // Para pré-preencher o formulário em caso de erro

    // Validação básica (comum para ambos os tipos)
    if (empty($nome)) $erros[] = 'O nome é obrigatório.';
    if (empty($cpf)) $erros[] = 'O CPF é obrigatório.';
    if (empty($email)) $erros[] = 'O e-mail é obrigatório.';
    if (empty($senha)) $erros[] = 'A senha é obrigatória.';
    if (empty($confirma_senha)) $erros[] = 'A confirmação de senha é obrigatória.';


    // Validação de E-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Formato de e-mail inválido.';
    } else {
        // Verifica se E-mail já existe (para ambos os tipos)
        $stmt_email = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = :email');
        $stmt_email->execute([':email' => $email]);
        if ($stmt_email->fetchColumn() > 0) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    // Validação de Senha
    echo "<pre>Debug Senha:\n";
    var_dump($senha);
    echo "Debug Confirma Senha:\n";
    var_dump($confirma_senha);
    echo "</pre>";
    if ($senha !== $confirma_senha) {
        $erros[] = 'A senha e a confirmação de senha não coincidem.';
    }
     if (strlen($senha) < 6) { // Exemplo de validação de força de senha
    $cpf_numerico = preg_replace('/[^0-9]/', '', $cpf);
    $form_data['cpf'] = $cpf_numerico; // Salva o CPF limpo no form_data
    if (strlen($cpf_numerico) != 11) {
        $erros[] = 'CPF inválido. Deve conter 11 dígitos numéricos.';
    } else {
        // Verifica se CPF já existe (para ambos os tipos)
        $stmt_cpf = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE cpf = :cpf');
        $stmt_cpf->execute([':cpf' => $cpf_numerico]);
        if ($stmt_cpf->fetchColumn() > 0) {
            $erros[] = 'Este CPF já está cadastrado.';
        }
    }

    // =====================================================================
    // Lógica Específica para Cadastro de Professor
    // =====================================================================
    $rg = $patente = $titulacao = $instituicao = $fonte_pagadora = $nome_guerra = $telefone = null; // Inicializa como null

    if ($nivel_acesso === 'PROFESSOR') {

        // Obtém campos adicionais para professor
        $rg = $_POST['rg'] ?? '';
        $patente = $_POST['patente'] ?? '';
        $titulacao = $_POST['titulacao'] ?? '';
        $instituicao = $_POST['instituicao'] ?? '';
        $fonte_pagadora = $_POST['fonte_pagadora'] ?? '';
        $nome_guerra = $_POST['nome_guerra'] ?? ''; // Certifique-se de que este campo existe no seu formulário e DB
        $telefone = $_POST['telefone'] ?? ''; // Certifique-se de que este campo existe no seu formulário e DB

         // Adiciona os campos específicos de professor ao form_data
         $form_data['rg'] = $rg;
         $form_data['patente'] = $patente;
         $form_data['titulacao'] = $titulacao;
         $form_data['instituicao'] = $instituicao;
         $form_data['fonte_pagadora'] = $fonte_pagadora;
         $form_data['nome_guerra'] = $nome_guerra;
         $form_data['telefone'] = $telefone;

        // Validação de campos obrigatórios para Professor
        if (empty($rg)) $erros[] = 'O RG é obrigatório para professores.';
        if (empty($patente)) $erros[] = 'A Patente é obrigatória para professores.';
        if (empty($titulacao)) $erros[] = 'A Titulação é obrigatória para professores.';
        if (empty($instituicao)) $erros[] = 'A Instituição é obrigatória para professores.';
        if (empty($fonte_pagadora)) $erros[] = 'A Fonte Pagadora é obrigatória para professores.';
        if (empty($telefone)) $erros[] = 'O Telefone é obrigatório para professores.';
        // Nome de Guerra pode ser opcional dependendo da sua regra de negócio
        // if (empty($nome_guerra)) $erros[] = 'O Nome de Guerra é obrigatório para professores.';

        // Validação de formato para RG e Telefone (exemplo básico)
         $rg_cleaned = preg_replace('/\\D/', '', $rg);
         if (!empty($rg) && !ctype_digit($rg_cleaned)) {
             $erros[] = 'O RG deve conter apenas números.';
         } else {
              $form_data['rg'] = $rg_cleaned; // Salva o RG limpo no form_data
         }


         $telefone_cleaned = preg_replace('/[^0-9]/', '', $telefone);
         if (!empty($telefone) && (strlen($telefone_cleaned) < 10 || strlen($telefone_cleaned) > 11)) {
             $erros[] = 'Formato de Telefone inválido. O Telefone deve conter 10 ou 11 dígitos (incluindo DDD).';
         } else {
             $form_data['telefone'] = $telefone_cleaned; // Salva o Telefone limpo no form_data
         }
        $nivel_acesso = 'PROFESSOR';

    } else {
        // =====================================================================
        // Lógica para Cadastro de Aluno (Comportamento Padrão)
        // =====================================================================
        $nivel_acesso = 'ALUNO';
        // Para Alunos, os campos específicos de professor são null por padrão
    }


    // Processar resultados da validação
    if (!empty($erros)) {
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => implode('<br>', array_unique($erros)) // array_unique evita mensagens duplicadas
        ];
        // Salva todos os dados do formulário no form_data da sessão (incluindo campos de professor se aplicável)
        $_SESSION['form_data'] = $form_data;

        header('Location: cadastro.php' . ($tipo_cadastro === 'professor' ? '?tipo=professor' : '')); // Redireciona de volta para a página de cadastro correta
        exit;
    } else {
        // Validação bem-sucedida, inserir no banco de dados
        $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

        try {
            // Prepara a query de inserção (inclui todos os campos, mesmo que sejam NULL para Alunos)
            $sql = 'INSERT INTO usuario (nome, cpf, email, senha, nivel_acesso, rg, patente, titulacao, instituicao, fonte_pagadora, nome_guerra, telefone)
                    VALUES (:nome, :cpf, :email, :senha, :nivel_acesso, :rg, :patente, :titulacao, :instituicao, :fonte_pagadora, :nome_guerra, :telefone)';

            $stmt_insert = $pdo->prepare($sql);

            $stmt_insert->bindParam(':nome', $nome);
            $stmt_insert->bindParam(':cpf', $cpf_numerico); // Usa o CPF numérico limpo
            $stmt_insert->bindParam(':email', $email);
            $stmt_insert->bindParam(':senha', $senha_hashed);
            $stmt_insert->bindParam(':nivel_acesso', $nivel_acesso);
            $stmt_insert->bindParam(':rg', $rg_cleaned); // Usa o RG numérico limpo
            $stmt_insert->bindParam(':patente', $patente);
            $stmt_insert->bindParam(':titulacao', $titulacao);
            $stmt_insert->bindParam(':instituicao', $instituicao);
            $stmt_insert->bindParam(':fonte_pagadora', $fonte_pagadora);
            $stmt_insert->bindParam(':nome_guerra', $nome_guerra);
            $stmt_insert->bindParam(':telefone', $telefone_cleaned); // Usa o telefone numérico limpo

            if ($stmt_insert->execute()) {
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'success',
                    'texto' => 'Cadastro realizado com sucesso! Faça login para continuar.'
                ];
                header('Location: login.php'); // Redireciona para a página de login após o cadastro
                exit;
            } else {
                // Erro na execução da query (pode ser duplicidade não pega pelas validações, erro de schema, etc.)
                 error_log('Erro na execução da query de inserção: ' . print_r($stmt_insert->errorInfo(), true)); // Loga detalhes do erro da query
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'danger',
                    'texto' => 'Ocorreu um erro ao salvar seu cadastro no banco de dados. Tente novamente.'
                ];
                $_SESSION['form_data'] = $form_data;
                header('Location: cadastro.php'); // Redireciona de volta para a página de cadastro
                exit();
            }

        } catch (PDOException $e) {
            // Erro no banco de dados (conexão, preparo da query, etc.)
            error_log('Erro PDO ao inserir usuário: ' . $e->getMessage()); // Loga o erro PDO
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Ocorreu um erro interno do servidor ao processar seu cadastro. Tente novamente mais tarde.'
            ];
            $_SESSION['form_data'] = $form_data;
             header('Location: cadastro.php'); // Redireciona de volta para a página de cadastro
            exit;
        }
} else {
    // Acesso direto ao script sem POST
    header('Location: cadastro.php'); // Redireciona para a página de cadastro
    exit;
}
?>
