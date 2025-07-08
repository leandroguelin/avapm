<?php
// redireciona_usuario.php - VERSÃO COM VERIFICAÇÃO EM MAIÚSCULAS

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado e tem um nível de acesso definido
if (isset($_SESSION['usuario_id']) && isset($_SESSION['nivel_acesso'])) {
    
    // Agora podemos usar a variável diretamente, pois sabemos que ela está padronizada
    require_once __DIR__ . '/includes/conexao.php'; // Inclui a conexão com o banco
    $nivel_acesso = $_SESSION['nivel_acesso'];

    // SUGESTÃO IMPLEMENTADA: Verificação com strings em maiúsculas
    if ($nivel_acesso === 'ADMINISTRADOR' || $nivel_acesso === 'GERENTE') {
        header('Location: dashboard.php');
        exit();
    } elseif ($nivel_acesso === 'PROFESSOR') {
        // Lógica para verificar se o professor tem todos os campos obrigatórios preenchidos
        $stmt = $pdo->prepare('SELECT nome, rg, email, patente, titulacao, instituicao, fonte_pagadora, nome_guerra, telefone FROM usuario WHERE id = :id');
        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $professor_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $campos_obrigatorios = ['nome', 'rg', 'email', 'patente', 'titulacao', 'instituicao', 'fonte_pagadora', 'nome_guerra', 'telefone'];
        $campos_faltando = false;

        foreach ($campos_obrigatorios as $campo) {
            // Verifica se o campo é nulo ou uma string vazia após remover espaços em branco
            if (empty($professor_data[$campo]) && $professor_data[$campo] !== null) {
                $campos_faltando = true;
                break;
            }
        }

        if ($campos_faltando) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'warning', 'texto' => 'Por favor, complete seus dados cadastrais para acessar o dashboard.'];
            header('Location: meu_perfil.php');
        } else {
            header('Location: dashboard_professor.php');
        }
        exit();
    } else {
        // Alunos e outros perfis vão para a página de perfil
        header('Location: meu_perfil.php');
        exit();
    }

} else {
    // Se a sessão não foi encontrada, volta para o login
    header('Location: login.php');
    exit();
}
?>