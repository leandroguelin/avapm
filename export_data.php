<?php
// htdocs/avapm/export_data.php

// Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Níveis de acesso permitidos para esta exportação
$allowed_access_levels = ['Administrador', 'Gerente']; 

// Redirecionar se o usuário NÃO estiver logado OU NÃO tiver um dos níveis de acesso permitidos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para exportar dados.'
    ];
    header('Location: index.php');
    exit();
}

// Obter parâmetros da URL
$data_type = isset($_GET['type']) ? filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) : null;
$export_format = isset($_GET['format']) ? filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING) : null;
$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) : '';

// Validação básica dos parâmetros
if (empty($data_type) || empty($export_format)) {
    die("Parâmetros de exportação incompletos.");
}

// Array para armazenar os dados a serem exportados
$data_to_export = [];
$headers = [];
$filename_prefix = 'dados';

try {
    switch ($data_type) {
        case 'users':
            $filename_prefix = 'usuarios';
            $headers = ['ID', 'Nome', 'Email', 'RG', 'CPF', 'Telefone', 'Nível de Acesso', 'Patente', 'Titulação', 'Instituição', 'Fonte Pagadora'];
            
            $condicoes_sql = [];
            $parametros_sql = [];

            if (!empty($termo_pesquisa)) {
                $condicoes_sql[] = "(nome LIKE :termo OR email LIKE :termo OR rg LIKE :termo OR cpf LIKE :termo OR patente LIKE :termo OR titulacao LIKE :termo OR instituicao LIKE :termo OR fonte_pagadora LIKE :termo OR telefone LIKE :termo)"; 
                $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%';
            }

            $where_clause = '';
            if (!empty($condicoes_sql)) {
                $where_clause = " WHERE " . implode(" OR ", $condicoes_sql);
            }

            $sql = "SELECT id, nome, email, rg, cpf, telefone, nivel_acesso, 
                            patente, titulacao, instituicao, fonte_pagadora 
                     FROM usuario " . $where_clause . " ORDER BY nome ASC";
            
            $stmt = $pdo->prepare($sql);
            foreach ($parametros_sql as $key => &$val) {
                $stmt->bindParam($key, $val);
            }
            unset($val);
            $stmt->execute();
            $data_to_export = $stmt->fetchAll(PDO::FETCH_ASSOC);

            break;
        case 'disciplinas':
            $filename_prefix = 'disciplinas';
            $headers = ['ID', 'Sigla', 'Nome', 'Horas', 'Ementa'];
            
            $condicoes_sql = [];
            $parametros_sql = [];

            if (!empty($termo_pesquisa)) {
                $condicoes_sql[] = "(sigla LIKE :termo OR nome LIKE :termo OR ementa LIKE :termo)";
                $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%';
            }

            $where_clause = '';
            if (!empty($condicoes_sql)) {
                $where_clause = " WHERE " . implode(" OR ", $condicoes_sql);
            }

            $sql = "SELECT id, sigla, nome, horas, ementa 
                     FROM disciplina " . $where_clause . " ORDER BY sigla ASC";
            
            $stmt = $pdo->prepare($sql);
            foreach ($parametros_sql as $key => &$val) {
                $stmt->bindParam($key, $val);
            }
            unset($val);
            $stmt->execute();
            $data_to_export = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Adicione aqui a lógica para outras entidades (e.g., 'turmas', 'cursos')
            // Crie um novo `case` para cada tipo de dado que você quiser exportar
            break;
        default:
            die("Tipo de dado não suportado para exportação.");
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para exportação: " . $e->getMessage());
    die("Erro no banco de dados ao preparar exportação.");
}

// =====================================================================
// Lógica para exportar no formato solicitado
// =====================================================================
switch ($export_format) {
    case 'excel':
        exportToExcel($data_to_export, $headers, $filename_prefix);
        break;
    case 'pdf':
        exportToPdf($data_to_export, $headers, $filename_prefix);
        break;
    default:
        die("Formato de exportação não suportado.");
}

exit();

// =====================================================================
// Funções de Exportação (coloque-as neste mesmo arquivo ou em um 'utils.php')
// =====================================================================

/**
 * Exporta dados para um arquivo CSV (compatível com Excel).
 * @param array $data O array de dados associativos.
 * @param array $headers Os cabeçalhos das colunas.
 * @param string $filename_prefix O prefixo do nome do arquivo.
 */
function exportToExcel($data, $headers, $filename_prefix) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='. $filename_prefix . '_' . date('Ymd_His') .'.csv');

    $output = fopen('php://output', 'w');

    // Adicionar a BOM (Byte Order Mark) para garantir que caracteres especiais sejam exibidos corretamente no Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Escreve os cabeçalhos
    fputcsv($output, $headers);

    // Escreve os dados
    foreach ($data as $row) {
        $row_values = [];
        foreach ($headers as $header) {
            // Mapeia o cabeçalho para a chave da coluna no array $row
            // Adapte esta lógica se as chaves do seu array $row não forem iguais aos seus headers
            // Ex: se $headers['ID'] e $row['id'], você precisa de um mapeamento
            // Por simplicidade, assumimos que os headers correspondem às chaves em $data_to_export
            switch ($header) {
                case 'ID': $row_values[] = $row['id']; break;
                case 'Nome': $row_values[] = $row['nome']; break;
                case 'Email': $row_values[] = $row['email']; break;
                case 'RG': $row_values[] = $row['rg']; break;
                case 'CPF': $row_values[] = $row['cpf']; break;
                case 'Telefone': $row_values[] = $row['telefone']; break;
                case 'Nível de Acesso': $row_values[] = $row['nivel_acesso']; break;
                case 'Patente': $row_values[] = $row['patente'] ?? ''; break; // Null coalesce para campos opcionais
                case 'Titulação': $row_values[] = $row['titulacao'] ?? ''; break;
                case 'Instituição': $row_values[] = $row['instituicao'] ?? ''; break;
                case 'Fonte Pagadora': $row_values[] = $row['fonte_pagadora'] ?? ''; break;
                case 'Sigla': $row_values[] = $row['sigla'] ?? ''; break;
                case 'Horas': $row_values[] = $row['horas'] ?? ''; break;
                case 'Ementa': $row_values[] = $row['ementa'] ?? ''; break;
                // Adicione mais casos aqui para outros tipos de dados/colunas
                default: $row_values[] = ''; // Ou trate de outra forma
            }
        }
        fputcsv($output, $row_values);
    }

    fclose($output);
}

/**
 * Exporta dados para um arquivo PDF usando FPDF.
 * Requer a biblioteca FPDF.
 * @param array $data O array de dados associativos.
 * @param array $headers Os cabeçalhos das colunas.
 * @param string $filename_prefix O prefixo do nome do arquivo.
 */
function exportToPdf($data, $headers, $filename_prefix) {
    // === REQUER FPDF ===
    // Baixe FPDF de http://www.fpdf.org/ e coloque a pasta 'fpdf' em /includes/
    require_once __DIR__ . '/includes/fpdf/fpdf.php'; 

    class CustomPDF extends FPDF {
        private $col_widths;
        private $headers;

        function __construct($orientation = 'P', $unit = 'mm', $size = 'A4', $headers = []) {
            parent::__construct($orientation, $unit, $size);
            $this->headers = $headers;
            $this->SetAutoPageBreak(true, 15); // Auto quebra de página com margem inferior de 15mm
        }

        // Cabeçalho
        function Header() {
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(0, 10, 'Relatório de ' . ucfirst(str_replace('_', ' ', $this->headers[0] ?? 'Dados')), 0, 1, 'C'); // Título dinâmico
            $this->Ln(5);

            // Cabeçalho da tabela
            $this->SetFont('Arial', 'B', 8);
            $this->SetFillColor(200, 220, 255); // Azul claro
            $this->SetTextColor(0);
            $this->SetDrawColor(128, 128, 128);
            $this->SetLineWidth(.3);
            
            // Define larguras das colunas de forma mais genérica ou com base no tipo
            $this->col_widths = [];
            // Larguras fixas para usuários, pode ser ajustado ou feito dinamicamente
            if ($this->headers[0] === 'ID' && in_array('Nível de Acesso', $this->headers)) { // Ex: headers de usuário
                $this->col_widths = [10, 45, 55, 20, 20, 20, 20, 20, 20, 20, 20]; // Ajuste conforme necessário
                $this->headers = ['ID', 'Nome', 'Email', 'RG', 'CPF', 'Telefone', 'Nível', 'Patente', 'Titulação', 'Instituição', 'Fonte Pagadora']; // Para caber melhor
            } elseif ($this->headers[0] === 'ID' && in_array('Sigla', $this->headers)) { // Ex: headers de disciplina
                $this->col_widths = [10, 20, 60, 15, 85]; // Ajuste conforme necessário
                $this->headers = ['ID', 'Sigla', 'Nome', 'Horas', 'Ementa'];
            } else { // Fallback genérico
                $num_cols = count($this->headers);
                $col_width = (210 - 20) / $num_cols; // Largura da página - margens / num colunas
                for($i=0; $i<$num_cols; $i++) $this->col_widths[] = $col_width;
            }
            
            for ($i = 0; $i < count($this->headers); $i++) {
                $this->Cell($this->col_widths[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $this->headers[$i]), 1, 0, 'C', true);
            }
            $this->Ln();
        }

        // Rodapé
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        }

        // Tabela de dados
        function BasicTable($data) {
            $this->SetFont('Arial', '', 7); // Fonte menor para o corpo da tabela
            $this->SetFillColor(240, 240, 240); // Cor de fundo para linhas alternadas
            $this->SetTextColor(0);
            $this->SetDrawColor(190, 190, 190);
            $fill = false;
            
            foreach ($data as $row) {
                // Mapeia os dados do array para a ordem e largura corretas das colunas
                // Esta parte é crucial para a reutilização
                $row_data_for_pdf = [];
                switch ($this->headers[0]) { // Verifica o tipo de dado pelo primeiro header
                    case 'ID': // Presume que é usuário ou disciplina
                        if (in_array('Nível', $this->headers)) { // É usuário
                            $row_data_for_pdf = [
                                $row['id'],
                                $row['nome'],
                                $row['email'],
                                $row['rg'],
                                $row['cpf'],
                                $row['telefone'],
                                $row['nivel_acesso'],
                                $row['patente'] ?? '',
                                $row['titulacao'] ?? '',
                                $row['instituicao'] ?? '',
                                $row['fonte_pagadora'] ?? ''
                            ];
                        } elseif (in_array('Sigla', $this->headers)) { // É disciplina
                             $row_data_for_pdf = [
                                $row['id'],
                                $row['sigla'],
                                $row['nome'],
                                $row['horas'],
                                // Limita ementa para não estourar a célula. Use MultiCell se precisar de texto longo.
                                substr($row['ementa'], 0, 50) . (strlen($row['ementa']) > 50 ? '...' : '') 
                            ];
                        }
                        break;
                    // Adicione mais casos para outros tipos de dados que você pode exportar
                }

                if (empty($row_data_for_pdf)) {
                    // Fallback se o tipo de dado não for reconhecido, apenas para evitar erro
                    foreach($row as $value) {
                        $row_data_for_pdf[] = $value;
                    }
                }

                for ($i = 0; $i < count($row_data_for_pdf); $i++) {
                    $this->Cell($this->col_widths[$i], 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row_data_for_pdf[$i]), 'LR', 0, 'L', $fill);
                }
                $this->Ln();
                $fill = !$fill;
            }
            $this->Cell(array_sum($this->col_widths), 0, '', 'T');
        }
    }

    $pdf = new CustomPDF('L', 'mm', 'A4', $headers); // 'L' para paisagem para caber mais colunas
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->BasicTable($data);

    $pdf->Output('I', $filename_prefix . '_' . date('Ymd_His') . '.pdf'); // 'I' para exibir no navegador, 'D' para download
}