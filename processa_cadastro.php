php
<?php
// processa_cadastro.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';
// Caminho completo para o arquivo
const PROCESSA_CADASTRO_PATH = __DIR__ . '/processa_cadastro.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtém o tipo de cadastro da URL (default é 'aluno')
    $tipo_cadastro = $_GET['tipo'] ?? 'aluno';
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    $erros = [];

    // Validação básica
    if (empty($nome) || empty($cpf) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $erros[] = 'Por favor, preencha todos os campos obrigatórios.';
    }

    // Validação de E-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Formato de e-mail inválido.';
    }

    // Validação de Senha
    if ($senha !== $confirma_senha) {
        $erros[] = 'A senha e a confirmação de senha não coincidem.';
    }

    // =====================================================================
    // Lógica Específica para Cadastro de Professor ou Aluno
    // =====================================================================
    $rg = $patente = $titulacao = $instituicao = $fonte_pagadora = $nome_guerra = $telefone = null; // Inicializa campos específicos como null
    $cpf = $_POST['cpf'] ?? ''; // Obtém o CPF aqui para ter acesso a ele em ambos os fluxos

    // Remove caracteres não numéricos
    $cpf_numerico = preg_replace('/[^0-9]/', '', $cpf);

     // Adiciona os dados básicos e o CPF limpo ao form_data para pré-preenchimento
     $form_data = ['nome' => $nome, 'cpf' => $cpf_numerico, 'email' => $email];

    // Validação do CPF
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
    
    // Verifica se E-mail já existe
    $stmt_email = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE email = :email');
    $stmt_email->execute([':email' => $email]);
    if ($stmt_email->fetchColumn() > 0) {
        $erros[] = 'Este e-mail já está cadastrado.';
    }


    if ($tipo_cadastro === 'professor') {
        // Obtém campos adicionais para professor
        $rg = $_POST['rg'] ?? '';
        $patente = $_POST['patente'] ?? '';
        $titulacao = $_POST['titulacao'] ?? '';
        $instituicao = $_POST['instituicao'] ?? '';
        $fonte_pagadora = $_POST['fonte_pagadora'] ?? '';
        $nome_guerra = $_POST['nome_guerra'] ?? '';
        $telefone = $_POST['telefone'] ?? '';

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

        // Validação de formato para RG e Telefone (exemplo básico)
         $rg_cleaned = preg_replace('/\\D/', '', $rg);
         if (!empty($rg) && !ctype_digit($rg_cleaned)) $erros[] = 'O RG deve conter apenas números.';

         $telefone_cleaned = preg_replace('/\\D/', '', $telefone);
         if (!empty($telefone) && (strlen($telefone_cleaned) < 10 || strlen($telefone_cleaned) > 11)) $erros[] = 'Formato de Telefone inválido. O Telefone deve conter 10 ou 11 dígitos (incluindo DDD).';

        $nivel_acesso = 'PROFESSOR';

    } else {
        // Lógica para Cadastro de Aluno (Comportamento Padrão)
        $nivel_acesso = 'ALUNO';
    }

    // Processar resultados da validação
    if (!empty($erros)) {
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => implode('<br>', array_unique($erros)) // array_unique evita mensagens duplicadas
        ];
        // Preserva os dados do formulário (exceto senhas) para preencher no retorno
        $_SESSION['form_data'] = $form_data;

            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'success',
                'texto' => 'Cadastro realizado com sucesso! Faça login para continuar.'
            ];
            header('Location: login.php');
            exit();
    } else {
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