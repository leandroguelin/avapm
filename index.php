<?php
// index.php - VERSÃO PÚBLICA E SIMPLIFICADA

require_once __DIR__ . '/includes/conexao.php';

// Busca as configurações do banco de dados para logo, favicon e texto.
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configs = []; 
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}

$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png';
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico';
$texto_index = $configs['texto_index'] ?? 'Bem-vindo ao sistema de avaliação. Sua participação é fundamental.';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVAPM - Avaliação Institucional</title>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS da versão anterior (moderno e responsivo) */
        :root { --cor-primaria: #007bff; --cor-primaria-hover: #0056b3; --cor-texto-principal: #343a40; --cor-texto-secundario: #6c757d; --cor-fundo: #f4f7f6; }
        html, body { height: 100%; margin: 0; font-family: 'Poppins', sans-serif; background-color: var(--cor-fundo); }
        .main-container { display: flex; flex-direction: column; justify-content: space-between; min-height: 100vh; }
        .header-public { padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
        .logo-link { display: flex; align-items: center; text-decoration: none; color: var(--cor-texto-principal); font-weight: 700; font-size: 1.5rem; }
        .logo-link img { max-height: 50px; margin-right: 15px; }
        .nav-link { text-decoration: none; color: var(--cor-texto-principal); font-weight: 500; padding: 0.5rem 1rem; border: 1px solid transparent; border-radius: 5px; transition: all 0.3s ease; }
        .nav-link:hover { color: var(--cor-primaria); border-color: var(--cor-primaria); }
        .hero-section { display: flex; flex-grow: 1; align-items: center; justify-content: center; text-align: center; padding: 2rem; }
        .hero-content { max-width: 700px; }
        .hero-title { font-size: 2.5rem; font-weight: 700; color: var(--cor-texto-principal); line-height: 1.2; margin-bottom: 1rem; }
        .hero-subtitle { font-size: 1.1rem; color: var(--cor-texto-secundario); margin-bottom: 2.5rem; line-height: 1.6; }
        .hero-button { display: inline-block; background-color: var(--cor-primaria); color: #fff; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); }
        .hero-button:hover { background-color: var(--cor-primaria-hover); transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0, 123, 255, 0.3); }
        .hero-button i { margin-left: 10px; transition: transform 0.3s ease; }
        .hero-button:hover i { transform: translateX(5px); }
        @media (max-width: 768px) { .hero-title { font-size: 2rem; } .hero-subtitle { font-size: 1rem; } .header-public { flex-direction: column; gap: 1rem; } }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="header-public">
            <a href="index.php" class="logo-link">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo do Sistema">
                <span>AVA</span>
            </a>
            <nav>
                <a href="login.php" class="nav-link">Acesso Restrito</a>
            </nav>
        </header>

        <main class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Transformando sua avaliação em crescimento institucional.</h1>
                <p class="hero-subtitle"><?php echo nl2br(htmlspecialchars($texto_index)); ?></p>
                <a href="inserir_codigo_avaliacao.php" class="hero-button">
                    Iniciar Avaliação <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </main>
    </div>
</body>
</html>