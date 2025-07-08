<?php
// htdocs/avapm/logout.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array(); // Limpa todas as variáveis da sessão

// Se a sessão for controlada por cookies, também apaga o cookie de sessão.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Destrói a sessão no servidor

$_SESSION['mensagem_feedback'] = [ // Esta mensagem é para uma NOVA sessão que é criada implicitamente
    'tipo' => 'success',
    'texto' => 'Você foi desconectado com sucesso.'
];

header("Location: login.php");
exit();
?>