<?php
// Este arquivo:
// 1. Inicia a sessão PHP.
// 2. Inclui a conexão com o banco de dados.
// 3. Verifica se o usuário está logado e, se não, redireciona para a página de login.
// 4. Inicia a estrutura HTML e inclui os assets CSS.
// 5. Abre a div principal do layout do dashboard.

// Inclui o arquivo de conexão com o banco de dados.
// '__DIR__' garante que o caminho seja sempre resolvido corretamente,
// independentemente de onde 'header_dashboard.php' é incluído.
// Ele sobe um nível (de 'templates') e depois acessa 'conexao.php'.
require_once __DIR__ . '/../conexao.php';

// Inicia a sessão PHP para poder armazenar e acessar dados do usuário logado.
// session_status() == PHP_SESSION_NONE verifica se a sessão ainda não foi iniciada.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =====================================================================
// Lógica de Verificação de Login para Proteger o Dashboard
// Redireciona o usuário para a página de login se não estiver autenticado.
// Agora verificamos 'usuario_id' diretamente na sessão, como definido no processa_login.php
// =====================================================================
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    // Se 'usuario_id' não existe ou está vazio, o usuário não está logado.
    // Redireciona para a página de login, que deve estar na raiz do projeto.
    // O caminho 'login.php' é relativo à URL que o usuário acessa.
    header("Location: login.php");
    exit(); // É crucial usar exit() após um redirecionamento.
}

// Obtém as informações do usuário logado da sessão para uso nas páginas do dashboard.
// Agora acessamos as variáveis diretamente da raiz de $_SESSION, como elas foram salvas.
$id_usuario_logado = $_SESSION['usuario_id'] ?? null;
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário Desconhecido';
$nivel_acesso_usuario = $_SESSION['nivel_acesso'] ?? 'aluno'; // Padrão 'aluno'
$foto_perfil_usuario = $_SESSION['foto_perfil'] ?? ''; // Padrão vazio

// Nota: O email não foi salvo na sessão no processa_login.php no código anterior.
// Se precisar dele, você teria que adicioná-lo lá: $_SESSION['email_usuario'] = $usuario['email'];
// Por enquanto, o removemos daqui para evitar avisos de 'Undefined array key'.
// $email_usuario_logado = $_SESSION['email_usuario'] ?? 'email@desconhecido.com';


// A variável $page_title pode ser definida em cada página específica do dashboard (ex: dashboard.php, meu_perfil.php)
// para personalizar o título da aba do navegador. Se não for definida, um título padrão será usado.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'AVA - Dashboard'; ?></title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <link rel="stylesheet" href="imagens/sistema/fontawesome/css/all.min.css"> 
    
    <link rel="stylesheet" href="css/style.css"> 
    
</head>
<body class="dashboard-page"> 
    <div class="dashboard-container">
    <?php
    // O restante do HTML será fechado no footer_dashboard.php.
    // Este fechamento de PHP é para o HTML começar.
    ?>
    <style>
/* --- ESTILOS PARA A NOVA SIDEBAR MODERNA --- */

:root {
    --sidebar-bg: #2c3e50; /* Cor de fundo escura */
    --sidebar-link-color: #ecf0f1; /* Cor do texto do link */
    --sidebar-link-hover-bg: #34495e; /* Fundo do link ao passar o mouse */
    --sidebar-link-active-bg: #2980b9; /* Fundo do link ativo */
    --sidebar-header-color: #bdc3c7; /* Cor do texto dos cabeçalhos de menu */
}

.sidebar {
    width: 260px;
    background: var(--sidebar-bg);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1000;
    transition: all 0.3s;
    color: var(--sidebar-link-color);
}

.sidebar-header {
    padding: 20px;
    background: rgba(0,0,0,0.2);
    text-align: center;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
    font-weight: 700;
    text-decoration: none;
}

.sidebar-logo img {
    width: 40px;
    height: 40px;
    margin-right: 10px;
}

.sidebar-nav ul {
    padding: 0;
    margin: 0;
    list-style: none;
}

.sidebar-item .sidebar-link {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: var(--sidebar-link-color);
    text-decoration: none;
    transition: background 0.3s ease;
    position: relative;
}

.sidebar-item .sidebar-link i {
    margin-right: 15px;
    width: 20px; /* Alinha os ícones */
    text-align: center;
}

.sidebar-item .sidebar-link:hover {
    background: var(--sidebar-link-hover-bg);
    border-left: 3px solid var(--sidebar-link-active-bg);
}

.sidebar-item .sidebar-link.active {
    background: var(--sidebar-link-active-bg);
    color: #fff;
    font-weight: 500;
    border-left: 3px solid #3498db;
}

.menu-header {
    padding: 15px 20px 5px 20px;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--sidebar-header-color);
    font-weight: 700;
    letter-spacing: 1px;
}

.submenu-arrow {
    margin-left: auto;
    transition: transform 0.3s ease;
}

/* Gira a seta quando o menu está expandido */
.sidebar-link[aria-expanded="true"] .submenu-arrow {
    transform: rotate(180deg);
}

.submenu {
    background: rgba(0,0,0,0.15);
    padding-left: 20px;
}

.submenu a {
    padding-left: 35px !important; /* Indentação dos sub-itens */
    font-size: 14px;
}

/* Posiciona o item de Sair no final */
.logout-item {
    position: absolute;
    bottom: 0;
    width: 100%;
    border-top: 1px solid var(--sidebar-link-hover-bg);
}
</style>