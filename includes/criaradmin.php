<?php
// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

echo "<h1>Criador de Usuário Administrador Inicial</h1>";
echo "<p>Este script irá criar um usuário administrador no banco de dados.</p>";
echo "<p><strong>ATENÇÃO:</strong> Execute este script apenas UMA VEZ para criar o usuário inicial. Após a execução, é recomendado deletá-lo ou movê-lo para fora da raiz do servidor web por segurança.</p>";
echo "<hr>";

// Dados do usuário administrador a ser criado
$nome_admin = "Administrador Master";
$email_admin = "admin@avapm.com"; // Use um e-mail real para o seu administrador
$senha_clara_admin = "admin123"; // Defina uma senha forte para seu administrador
$nivel_acesso_admin = "administrador";
$rg_admin = "1234567"; // RG opcional
$patente_id_admin = null; // ID da patente (se aplicável), pode ser NULL ou um valor válido
$titulacao_id_admin = null; // ID da titulação (se aplicável), pode ser NULL ou um valor válido
$instituicao_id_admin = null; // CORRIGIDO: ID da instituição (se aplicável), pode ser NULL ou um valor válido
$fonte_pagadora_id_admin = null; // ID da fonte pagadora (se aplicável), pode ser NULL ou um valor válido
$nome_guerra_admin = "AdminMaster"; // Nome de guerra opcional
$telefone_admin = "999999999"; // Telefone opcional
$foto_admin = "avatar_admin.png"; // Nome do arquivo da foto no diretório 'imagens/profiles/'

// As colunas data_criacao e data_alteracao serão preenchidas automaticamente pelo banco de dados
// ou manualmente pelo script. Usaremos o script para garantir.
$data_atual = date('Y-m-d H:i:s'); // Data e hora atual no formato YYYY-MM-DD HH:MM:SS

try {
    // Verificar se já existe um usuário com este email para evitar duplicidade
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = :email");
    $stmt_check->bindParam(':email', $email_admin);
    $stmt_check->execute();
    $count = $stmt_check->fetchColumn();

    if ($count > 0) {
        echo "<p style='color: orange;'><strong>Aviso:</strong> Já existe um usuário com o e-mail '{$email_admin}'.</p>";
        echo "<p>Para recriá-lo, exclua o registro manualmente no phpMyAdmin ou use outro e-mail.</p>";
    } else {
        // Criptografar a senha antes de armazenar no banco de dados
        $senha_hash = password_hash($senha_clara_admin, PASSWORD_DEFAULT);

        // Prepara a consulta SQL para inserir o novo usuário
        // ATENÇÃO: As colunas aqui devem CORRESPONDER EXATAMENTE às colunas da sua tabela 'usuario'
        $sql = "INSERT INTO usuario (nome, email, senha, nivel_acesso, rg, patente_id, titulacao_id, instituicao_id, fonte_pagadora_id, nome_guerra, telefone, foto, data_criacao, data_alteracao)
                VALUES (:nome, :email, :senha, :nivel_acesso, :rg, :patente_id, :titulacao_id, :instituicao_id, :fonte_pagadora_id, :nome_guerra, :telefone, :foto, :data_criacao, :data_alteracao)";

        $stmt = $pdo->prepare($sql);

        // Liga os parâmetros aos valores
        // ATENÇÃO: Os nomes dos parâmetros (:nome, :email, etc.) devem CORRESPONDER EXATAMENTE
        // aos placeholders na query SQL acima.
        $stmt->bindParam(':nome', $nome_admin);
        $stmt->bindParam(':email', $email_admin);
        $stmt->bindParam(':senha', $senha_hash); // Senha hasheada
        $stmt->bindParam(':nivel_acesso', $nivel_acesso_admin);
        $stmt->bindParam(':rg', $rg_admin);
        $stmt->bindParam(':patente_id', $patente_id_admin, PDO::PARAM_INT);
        $stmt->bindParam(':titulacao_id', $titulacao_id_admin, PDO::PARAM_INT);
        $stmt->bindParam(':instituicao_id', $instituicao_id_admin, PDO::PARAM_INT); // CORRIGIDO
        $stmt->bindParam(':fonte_pagadora_id', $fonte_pagadora_id_admin, PDO::PARAM_INT);
        $stmt->bindParam(':nome_guerra', $nome_guerra_admin);
        $stmt->bindParam(':telefone', $telefone_admin);
        $stmt->bindParam(':foto', $foto_admin);
        $stmt->bindParam(':data_criacao', $data_atual);
        $stmt->bindParam(':data_alteracao', $data_atual);

        // Executa a consulta
        if ($stmt->execute()) {
            echo "<p style='color: green;'><strong>Sucesso:</strong> Usuário administrador '{$nome_admin}' (e-mail: {$email_admin}) criado com sucesso!</p>";
            echo "<p>Você já pode tentar logar no sistema com este usuário.</p>";
        } else {
            echo "<p style='color: red;'><strong>Erro:</strong> Falha ao criar o usuário administrador.</p>";
            // Para depuração, mostra informações do erro do PDO. Remova em produção.
            echo "<pre>";
            print_r($stmt->errorInfo());
            echo "</pre>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Erro de Conexão/Banco de Dados:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Verifique se o banco de dados está rodando e se as configurações em <code>includes/conexao.php</code> estão corretas.</p>";
    // Em ambiente de desenvolvimento, útil para ver o rastreamento completo da exceção
    // echo "<p>Detalhes do erro técnico: <pre>" . $e->getTraceAsString() . "</pre></p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Voltar para a página de Login</a></p>";
?>