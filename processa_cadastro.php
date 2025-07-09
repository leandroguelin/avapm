<?php
// processa_cadastro.php - Processa o formulário de cadastro (com validação de senha removida para depuração)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- 1. Obter Dados do Formulário ---
    $nivel_acesso = $_POST['nivel_acesso'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $rg = $_POST['rg'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $patente = $_POST['patente'] ?? '';
    $titulacao = $_POST['titulacao'] ?? '';
    $instituicao = $_POST['instituicao'] ?? '';
    $fonte_pagadora = $_POST['fonte_pagadora'] ?? '';
    $nome_guerra = $_POST['nome_guerra'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $erros = [];
    // Mantém os dados para pré-preencher em caso de erro
    $form_data = $_POST;
    unset($form_data['senha']);

    // --- 2. Validações ---

    // Validação básica (comum para todos)
    if (empty($nivel_acesso) || ($nivel_acesso !== 'ALUNO' && $nivel_acesso !== 'PROFESSOR')) {
        $erros[] = 'Nível de acesso inválido.';
    }
    if (empty($nome)) $erros[] = 'O nome é obrigatório.';
    if (empty($cpf)) $erros[] = 'O CPF é obrigatório.';
    if (empty($email)) $erros[] = 'O e-mail é obrigatório.';
    if (empty($senha)) $erros[] = 'A senha é obrigatória.';

    // Validação de Senha REMOVIDA PARA DEPURAÇÃO
    // if (strlen($senha) < 6) {
    //     $erros[] = 'A senha deve ter no mínimo 6 caracteres.';
    // }

    // Validação de E-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Formato de e-mail inválido.';
    } else {
        $stmt_email = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = :email');
        $stmt_email->execute([':email' => $email]);
        if ($stmt_email->fetchColumn() > 0) {
            $erros[] = 'Este e-mail já está cadastrado.';
        }
    }

    // Validação de CPF
    $cpf_numerico = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf_numerico) != 11) {
        $erros[] = 'CPF inválido. Deve conter 11 dígitos numéricos.';
    } else {
        $stmt_cpf = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE cpf = :cpf');
        $stmt_cpf->execute([':cpf' => $cpf_numerico]);
        if ($stmt_cpf->fetchColumn() > 0) {
            $erros[] = 'Este CPF já está cadastrado.';
        }
    }

    // Validações específicas para Professor
    if ($nivel_acesso === 'PROFESSOR') {
        if (empty($rg)) $erros[] = 'O RG é obrigatório para professores.';
        if (empty($telefone)) $erros[] = 'O Telefone é obrigatório para professores.';
        if (empty($patente)) $erros[] = 'A Patente é obrigatória para professores.';
        if (empty($titulacao)) $erros[] = 'A Titulação é obrigatória para professores.';
        if (empty($instituicao)) $erros[] = 'A Instituição é obrigatória para professores.';
        if (empty($fonte_pagadora)) $erros[] = 'A Fonte Pagadora é obrigatória para professores.';
    }
    
    // Limpa RG e Telefone para salvar no banco (mesmo que sejam nulos para alunos)
    $rg_cleaned = preg_replace('/\D/', '', $rg);
    $telefone_cleaned = preg_replace('/\D/', '', $telefone);


    // --- 3. Processar Resultados da Validação ---

    if (!empty($erros)) {
        // Se houver erros, redireciona de volta com a mensagem
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => implode('<br>', array_unique($erros))
        ];
        $_SESSION['form_data'] = $form_data;
        header('Location: cadastro.php');
        exit;

    } else {
        // Se não houver erros, insere no banco de dados
        $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $sql = 'INSERT INTO usuario (nome, cpf, email, senha, nivel_acesso, rg, patente, titulacao, instituicao, fonte_pagadora, nome_guerra, telefone)
                    VALUES (:nome, :cpf, :email, :senha, :nivel_acesso, :rg, :patente, :titulacao, :instituicao, :fonte_pagadora, :nome_guerra, :telefone)';

            $stmt_insert = $pdo->prepare($sql);

            $stmt_insert->bindParam(':nome', $nome);
            $stmt_insert->bindParam(':cpf', $cpf_numerico);
            $stmt_insert->bindParam(':email', $email);
            $stmt_insert->bindParam(':senha', $senha_hashed);
            $stmt_insert->bindParam(':nivel_acesso', $nivel_acesso);
            $stmt_insert->bindParam(':rg', $rg_cleaned);
            $stmt_insert->bindParam(':patente', $patente);
            $stmt_insert->bindParam(':titulacao', $titulacao);
            $stmt_insert->bindParam(':instituicao', $instituicao);
            $stmt_insert->bindParam(':fonte_pagadora', $fonte_pagadora);
            $stmt_insert->bindParam(':nome_guerra', $nome_guerra);
            $stmt_insert->bindParam(':telefone', $telefone_cleaned);

            if ($stmt_insert->execute()) {
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'success',
                    'texto' => 'Cadastro realizado com sucesso! Faça login para continuar.'
                ];
                header('Location: login.php');
                exit;
            } else {
                error_log('Erro na execução da query de inserção: ' . print_r($stmt_insert->errorInfo(), true));
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => 'danger',
                    'texto' => 'Ocorreu um erro ao salvar seu cadastro no banco de dados. Tente novamente.'
                ];
                $_SESSION['form_data'] = $form_data;
                header('Location: cadastro.php');
                exit;
            }

        } catch (PDOException $e) {
            error_log('Erro PDO ao inserir usuário: ' . $e->getMessage());
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Ocorreu um erro interno do servidor. Por favor, tente novamente mais tarde.'
            ];
            $_SESSION['form_data'] = $form_data;
            header('Location: cadastro.php');
            exit;
        }
    }

} else {
    // Se não for um POST, redireciona para a página de cadastro
    header('Location: cadastro.php');
    exit;
}
?>
