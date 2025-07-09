php
<?php
// processa_cadastro.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    $erros = [];

    // Validação básica
    if (empty($nome) || empty($cpf) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $erros[] = 'Todos os campos são obrigatórios.';
    }

    // Validação de E-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Formato de e-mail inválido.';
    }

    // Validação de Senha
    if ($senha !== $confirma_senha) {
        $erros[] = 'A senha e a confirmação de senha não coincidem.';
    }

    // Validação do CPF (formato básico, você pode adicionar uma validação mais complexa se necessário)
    // Remove caracteres não numéricos
    $cpf_numerico = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf_numerico) != 11) {
        $erros[] = 'CPF inválido. Deve conter 11 dígitos numéricos.';
    } else {
        // Verifica se CPF já existe
        $stmt_cpf = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE cpf = :cpf');
        $stmt_cpf->execute([':cpf' => $cpf_numerico]);
        if ($stmt_cpf->fetchColumn() > 0) {
            $erros[] = 'Este CPF já está cadastrado.';
        }
    }

    // Verifica se E-mail já existe
    $stmt_email = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = :email');
    $stmt_email->execute([':email' => $email]);
    if ($stmt_email->fetchColumn() > 0) {
        $erros[] = 'Este e-mail já está cadastrado.';
    }

    // Processar resultados da validação
    if (!empty($erros)) {
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => implode('<br>', $erros)
        ];
        // Preserva os dados do formulário (exceto senhas) para preencher no retorno
        $_SESSION['form_data'] = ['nome' => $nome, 'cpf' => $cpf, 'email' => $email];
        header('Location: cadastro.php');
        exit();
    } else {
        // Validação bem-sucedida, inserir no banco de dados
        $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $stmt_insert = $pdo->prepare('INSERT INTO usuario (nome, cpf, email, senha, nivel_acesso) VALUES (:nome, :cpf, :email, :senha, :nivel_acesso)');
// Define um nível de acesso padrão para novos cadastros, por exemplo, 'Aluno'
            $nivel_acesso_padrao = 'Aluno'; 
            $stmt_insert->execute([
                ':nome' => $nome,
                ':cpf' => $cpf_numerico, // Salva o CPF numérico
                ':email' => $email,
                ':senha' => $senha_hashed,
                ':nivel_acesso' => $nivel_acesso_padrao // Usa o nível de acesso padrão
            ]);

            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'success',
                'texto' => 'Cadastro realizado com sucesso! Faça login para continuar.'
            ];
            header('Location: login.php');
            exit();

        } catch (PDOException $e) {
            // Erro no banco de dados
            error_log('Erro ao inserir usuário: ' . $e->getMessage()); // Loga o erro
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Ocorreu um erro ao processar seu cadastro. Tente novamente mais tarde.'
            ];
            $_SESSION['form_data'] = ['nome' => $nome, 'cpf' => $cpf, 'email' => $email];
            header('Location: cadastro.php');
            exit();
        }
    }

} else {
    // Acesso direto ao script sem POST
    header('Location: cadastro.php');
    exit();
}
?>