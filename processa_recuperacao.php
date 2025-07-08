<?php
// processa_recuperacao.php - VERSÃO CORRIGIDA

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// =============================================================
// CORREÇÃO: ADICIONADA A LINHA QUE CARREGA AS BIBLIOTECAS DO COMPOSER
// =============================================================
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Formato de e-mail inválido.'];
        header('Location: recuperar_senha.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Gera um token seguro e um tempo de expiração (1 hora)
        $token = bin2hex(random_bytes(50));
        $expires_at = new DateTime('+1 hour');
        
        $stmt_update = $pdo->prepare("UPDATE usuario SET reset_token = :token, reset_token_expires_at = :expires WHERE id = :id");
        $stmt_update->execute([':token' => $token, ':expires' => $expires_at->format('Y-m-d H:i:s'), ':id' => $usuario['id']]);
        
        // Monta o link de recuperação
        // Garante que o caminho esteja correto, removendo barras extras se houver
        $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $base_path . "/resetar_senha.php?token=" . $token;

        // --- Configuração do Envio de E-mail com PHPMailer ---
        $mail = new PHPMailer(true);
        try {
            //Configurações do Servidor SMTP (EXEMPLO PARA GMAIL - SUBSTITUA PELAS SUAS)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';     // Servidor SMTP do seu provedor
            $mail->SMTPAuth   = true;
            $mail->Username   = 'seu_email@gmail.com';  // SEU USUÁRIO SMTP (SEU E-MAIL)
            $mail->Password   = 'sua_senha_de_app';   // SUA SENHA SMTP (ou senha de app do Gmail)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Remetente e Destinatário
            $mail->setFrom('nao-responda@avapm.com', 'Sistema AVAPM');
            $mail->addAddress($email);

            // Conteúdo do E-mail
            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha - Sistema AVAPM';
            $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha. Clique no link abaixo para criar uma nova senha:<br><br><a href='$reset_link' style='padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Redefinir Minha Senha</a><br><br>O link é válido por 1 hora.<br><br>Se você não solicitou isso, por favor, ignore este e-mail.<br><br>Atenciosamente,<br>Equipe AVAPM";
            $mail->AltBody = "Para redefinir sua senha, copie e cole este link no seu navegador: $reset_link";

            $mail->send();
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Se o e-mail estiver cadastrado, um link de recuperação foi enviado.'];
        } catch (Exception $e) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Não foi possível enviar o e-mail de recuperação.'];
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
        }
    } else {
        // Mesmo que o email não exista, mostramos uma mensagem genérica por segurança
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Se o e-mail estiver cadastrado, um link de recuperação foi enviado.'];
    }
    header('Location: recuperar_senha.php');
    exit();
}
?>