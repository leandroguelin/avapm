<?php
// cadastro.php - Página de Cadastro de Novo Usuário (Dinâmico para Aluno/Professor)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Busca configurações para usar o logo e favicon dinâmicos (se aplicável para páginas públicas)
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configs = [];
}
$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png'; // Ajuste o caminho se necessário
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico'; // Ajuste o caminho se necessário

// Processa mensagens de feedback (ex: "Cadastro realizado com sucesso!")
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);

// --- Lógica para Obter o Tipo de Cadastro e Carregar Dados para Dropdowns (se for professor) ---
$tipo_cadastro = $_GET['tipo'] ?? 'aluno'; // 'aluno' por padrão

$patentes = [];
$titulacoes = [];
$instituicoes = [];

// Só busca os dados para dropdowns se o cadastro for de professor
if ($tipo_cadastro === 'professor') {
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
        $mensagem_feedback = "Erro ao carregar opções para o formulário: " . $e->getMessage();
        $feedback_tipo = 'danger';
        error_log("Erro PDO ao carregar dropdowns para cadastro.php: " . $e->getMessage());
        // Não redireciona, apenas exibe a mensagem de erro no formulário
    }
}

// Recupera dados do formulário da sessão em caso de erro anterior
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro<?php echo ($tipo_cadastro === 'professor' ? ' de Professor' : ' de Aluno'); ?> - AVAPM</title>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS consistente com as outras páginas públicas (adapte conforme seu CSS existente) */
        :root {
            --cor-primaria: #007bff;
            --cor-primaria-hover: #0056b3;
            --cor-texto-principal: #343a40;
            --cor-texto-secundario: #6c757d;
            --cor-fundo: #f4f7f6;
        }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-fundo);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cadastro-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px; /* Ajuste conforme necessário */
            margin: auto;
            text-align: center;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }
        h2 {
            font-weight: 700;
            color: var(--cor-texto-principal);
            margin-bottom: 30px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 15px; /* Espaço menor entre os campos */
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px; /* Espaço menor entre label e input */
            color: #555;
            font-size: 0.9rem; /* Fonte menor para labels */
        }
        .form-group input,
        .form-group select { /* Adicionado select */
            width: 100%;
            padding: 10px 12px; /* Padding menor */
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background-color: #fff; /* Garante fundo branco para selects */
        }
        .form-group input:focus,
        .form-group select:focus { /* Adicionado select:focus */
            outline: none;
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        .btn-cadastro {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: var(--cor-primaria);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .btn-cadastro:hover {
            background-color: var(--cor-primaria-hover);
        }
        .links-login {
            margin-top: 20px;
            font-size: 14px;
        }
        .links-login a {
            color: var(--cor-texto-secundario);
            text-decoration: none;
        }
        .links-login a:hover {
            color: var(--cor-primaria);
            text-decoration: underline;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
