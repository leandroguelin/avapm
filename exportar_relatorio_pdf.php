php
<?php
// exportar_relatorio_pdf.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/fpdf/fpdf.php'; // Inclui a biblioteca FPDF

// --- Verificação de Login e Nível de Acesso ---
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
$user_level = $_SESSION['nivel_acesso'] ?? '';

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    // Se o usuário não estiver logado ou não tiver o nível correto,
    // ele é enviado para o \"porteiro\" decidir o que fazer.
    header('Location: redireciona_usuario.php');
    exit();
}

// --- Obter ID da Avaliação da URL ---
$avaliacao_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($avaliacao_id <= 0) {
    // Redireciona de volta para a página de relatórios se o ID for inválido
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID da avaliação inválido para exportação.'];
    header('Location: relatorios.php');
    exit();
}

// --- Buscar Dados da Avaliação e Respostas ---
$avaliacao = null;
$respostas = [];
try {
    // Busca os dados básicos da avaliação
    $stmt_avaliacao = $pdo->prepare('SELECT a.nome, c.nome as curso_nome, a.data_inicio, a.data_final FROM avaliacao a JOIN cursos c ON a.curso_id = c.id WHERE a.id = :id');
    $stmt_avaliacao->execute([':id' => $avaliacao_id]);
    $avaliacao = $stmt_avaliacao->fetch(PDO::FETCH_ASSOC);

    if ($avaliacao) {
        // Busca os detalhes das respostas para esta avaliação
        // Adapte esta consulta para buscar 'pergunta', nome do 'professor', 'resposta' e 'observacao'
        // Assumindo uma estrutura onde `respostas` tem colunas `avaliacao_id`, `pergunta`, `avaliado` (id do professor), `resposta`, `observacao`
        // e que 'avaliado' se junta com a tabela 'usuario' para obter o nome do professor.
        $stmt_respostas = $pdo->prepare('
            SELECT
                r.pergunta,
                u.nome as professor_nome,
                r.resposta,
                r.observacao
            FROM
                respostas r
            JOIN
                usuario u ON r.avaliado = u.id
            WHERE
                r.avaliacao_id = :avaliacao_id
            ORDER BY
                u.nome ASC, r.pergunta ASC
        ');
        $stmt_respostas->execute([':avaliacao_id' => $avaliacao_id]);
        $respostas = $stmt_respostas->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Loga o erro e redireciona com mensagem de erro
    error_log('Erro ao buscar dados para PDF: ' . $e->getMessage());
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar dados da avaliação para PDF.'];
    header('Location: relatorios.php');
    exit();
}

if (!$avaliacao) {
    // Redireciona se a avaliação não foi encontrada
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Avaliação não encontrada.'];
    header('Location: relatorios.php');
    exit();
}

// --- Gerar o PDF com FPDF ---
$pdf = new FPDF();
$pdf->AddPage();

// Título do Relatório
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, utf8_decode('Relatório de Avaliação'), 0, 1, 'C');
$pdf->Ln(10);

// Detalhes da Avaliação
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, utf8_decode('Avaliação: ') . utf8_decode($avaliacao['nome']), 0, 1);
$pdf->Cell(0, 7, utf8_decode('Curso: ') . utf8_decode($avaliacao['curso_nome']), 0, 1);
$pdf->Cell(0, 7, utf8_decode('Período: ') . date('d/m/Y', strtotime($avaliacao['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($avaliacao['data_final'])), 0, 1);
$pdf->Ln(10);

// Tabela de Respostas
if (!empty($respostas)) {
    $pdf->SetFont('Arial', 'B', 10);
    // Cabeçalho da Tabela
    $pdf->Cell(70, 7, utf8_decode('Pergunta'), 1, 0, 'C');
    $pdf->Cell(50, 7, utf8_decode('Professor'), 1, 0, 'C');
    $pdf->Cell(20, 7, utf8_decode('Resp.'), 1, 0, 'C');
    $pdf->Cell(50, 7, utf8_decode('Observação'), 1, 1, 'C');

    $pdf->SetFont('Arial', '', 10);
    // Dados da Tabela
    foreach ($respostas as $resposta) {
        // Adaptação para MultiCell se o conteúdo for muito longo
        $pergunta = utf8_decode($resposta['pergunta']);
        $professor = utf8_decode($resposta['professor_nome']);
        $resp = $resposta['resposta'];
        $observacao = utf8_decode($resposta['observacao'] ?? ''); // Trata caso observação seja NULL

        // Altura da linha dinâmica baseada no conteúdo mais alto
        $cellHeight = 7; // Altura base
        $perguntaHeight = $pdf->getStringWidth($pergunta) > 70 ? (ceil($pdf->getStringWidth($pergunta)/70) * 5) : 5;
         $observacaoHeight = $pdf->getStringWidth($observacao) > 50 ? (ceil($pdf->getStringWidth($observacao)/50) * 5) : 5;
 $professorHeight = $pdf->getStringWidth($professor) > 50 ? (ceil($pdf->getStringWidth($professor)/50) * 5) : 5;
 $cellHeight = max($cellHeight, $perguntaHeight, $observacaoHeight, $professorHeight);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->MultiCell(70, $cellHeight/($perguntaHeight/5), $pergunta, 1, 'L', false);
        $pdf->SetXY($x + 70, $y);
        $pdf->MultiCell(50, $cellHeight/($pdf->getStringWidth($professor)/50), $professor, 1, 'L', false);
         $pdf->SetXY($x + 120, $y);
        $pdf->Cell(20, $cellHeight, $resp, 1, 0, 'C');
         $pdf->SetXY($x + 140, $y);
        $pdf->MultiCell(50, $cellHeight/($observacaoHeight/5), $observacao, 1, 'L', false);


        $pdf->SetXY($x + 190, $y); // Mover para o final da linha (opcional, MultiCell já faz)
        $pdf->Ln($cellHeight); // Avança para a próxima linha
    }
} else {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, utf8_decode('Nenhuma resposta encontrada para esta avaliação.'), 0, 1, 'C');
}


// --- Saída do PDF ---
$pdf_filename = 'relatorio_avaliacao_' . $avaliacao_id . '.pdf';
$pdf->Output($pdf_filename, 'D'); // 'D' força o download do arquivo

exit();
?>