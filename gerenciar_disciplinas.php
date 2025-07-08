<?php
// htdocs/avapm/gerenciar_disciplinas.php

// Definir o título da página
$page_title = "Gerenciar Disciplinas";

// Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Níveis de acesso permitidos para esta página
$allowed_access_levels = ['Administrador', 'Gerente']; 

// Redirecionar se o usuário NÃO estiver logado OU NÃO tiver um dos níveis de acesso permitidos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para acessar esta página.'
    ];
    header('Location: index.php'); // Redirecionar para a página de login ou dashboard
    exit();
}

// =====================================================================
// Lógica para processar a exclusão de disciplina
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] == 'excluir' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_disciplina = (int)$_GET['id'];

    try {
        $pdo->beginTransaction(); // Inicia uma transação para garantir atomicidade
        
        // A lógica de verificação de vinculação com a tabela 'turma' FOI REMOVIDA
        // para atender à sua necessidade de não usar essa tabela.
        // A exclusão agora tentará ocorrer diretamente.

        // Prepara e executa a query de exclusão da disciplina
        $stmt_delete = $pdo->prepare("DELETE FROM disciplina WHERE id = :id");
        $stmt_delete->bindParam(':id', $id_disciplina, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            $pdo->commit(); // Confirma a transação
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'success',
                'texto' => 'Disciplina excluída com sucesso!'
            ];
        } else {
            $pdo->rollBack(); // Desfaz a transação em caso de falha na exclusão
            $_SESSION['mensagem_feedback'] = [
                'tipo' => 'danger',
                'texto' => 'Erro ao excluir disciplina. Tente novamente.'
            ];
        }
    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz a transação em caso de erro de PDO
        $_SESSION['mensagem_feedback'] = [
            'tipo' => 'danger',
            'texto' => "Erro no banco de dados ao excluir disciplina: " . $e->getMessage()
        ];
        error_log("Erro PDO em gerenciar_disciplinas.php ao excluir: " . $e->getMessage());
    }
    // Redireciona sempre após a tentativa de exclusão para evitar reenvio do formulário
    header('Location: gerenciar_disciplinas.php');
    exit();
}

// =====================================================================
// Lógica para detecção de requisição AJAX (para busca e paginação)
// =====================================================================
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// =====================================================================
// Configurações de Paginação
// =====================================================================
$limite_por_pagina = 10; // Quantas disciplinas por página
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// =====================================================================
// Lógica de Pesquisa
// =====================================================================
$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) : '';

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

// =====================================================================
// Lógica para buscar o total de disciplinas (para paginação)
// =====================================================================
$total_disciplinas = 0;
try {
    $stmt_total = $pdo->prepare("SELECT COUNT(id) FROM disciplina " . $where_clause);
    $stmt_total->execute($parametros_sql);
    $total_disciplinas = $stmt_total->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao contar disciplinas: " . $e->getMessage());
    // Se ocorrer um erro aqui, a paginação pode não ser exibida corretamente, mas a página não quebra.
}
$total_paginas = ceil($total_disciplinas / $limite_por_pagina);

// =====================================================================
// Lógica para buscar as disciplinas para a página atual (com filtro e paginação)
// =====================================================================
$disciplinas = [];
try {
    $sql = "SELECT id, sigla, nome, horas, ementa 
            FROM disciplina 
            " . $where_clause . "
            ORDER BY sigla ASC
            LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // Bind dos parâmetros de pesquisa
    foreach ($parametros_sql as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    unset($val); // Desreferencia a variável para evitar problemas futuros com loops

    // Bind dos parâmetros de paginação
    $stmt->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao carregar disciplinas: " . $e->getMessage());
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => "Erro ao carregar disciplinas: " . $e->getMessage()
    ];
}

// =====================================================================
// Recuperar e limpar mensagem de feedback da sessão
// =====================================================================
$mensagem_feedback = '';
$feedback_tipo = '';
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a sessão após exibir
}

// =====================================================================
// Renderização do HTML - Lógica Condicional para AJAX vs. Página Completa
// =====================================================================

// Se for uma requisição AJAX, APENAS inclua a parcial da tabela e saia
if ($is_ajax_request) {
    ob_start(); // Inicia o buffer de saída
    require_once __DIR__ . '/includes/templates/disciplina_table_partial.php';
    $html_content = ob_get_clean(); // Captura o conteúdo do buffer
    echo $html_content; // Imprime o HTML da tabela
    exit(); // Sai do script para não renderizar o restante da página
}

// Se NÃO for uma requisição AJAX (carga inicial da página), renderize a página completa
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? 'Usuário'); ?></span>
            <div class="user-avatar">
                <?php 
                $foto_src = '';
                if (!empty($_SESSION['foto_perfil']) && file_exists('imagens/profiles/' . $_SESSION['foto_perfil'])) {
                    $foto_src = 'imagens/profiles/' . htmlspecialchars($_SESSION['foto_perfil']);
                } 
                ?>
                <?php if (!empty($foto_src)) : ?>
                    <img src="<?php echo $foto_src; ?>" alt="Avatar do Usuário Logado" style="max-width: 30px; height: auto; border-radius: 50%;">
                <?php else : ?>
                    <i class="fas fa-user-circle fa-lg"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Lista de Disciplinas</h2>
            <a href="adicionar_disciplina.php" class="btn-primary-dashboard">
                <i class="fas fa-plus"></i> Adicionar Nova Disciplina
            </a>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Pesquisar disciplinas por Sigla, Nome, Ementa..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
            <i class="fas fa-search"></i>
        </div>

        <div id="disciplinaTableContainer">
            <?php
            // Inclui a tabela parcial na carga inicial da página
            require_once __DIR__ . '/includes/templates/disciplina_table_partial.php';
            ?>
        </div>
    </div>
</div>

<div id="ementaModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="fecharModalEmenta()">&times;</span>
        <h2 id="modalTitle"></h2>
        <p id="modalEmenta"></p>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const disciplinaTableContainer = document.getElementById('disciplinaTableContainer');
        const ementaModal = document.getElementById('ementaModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalEmenta = document.getElementById('modalEmenta');
        let searchTimeout;

        /**
         * Realiza a busca de disciplinas via AJAX e atualiza a tabela.
         * @param {string} searchTerm - O termo de pesquisa.
         * @param {number} page - O número da página.
         */
        function fetchDisciplinas(searchTerm, page = 1) {
            const url = `gerenciar_disciplinas.php?q=${encodeURIComponent(searchTerm)}&pagina=${page}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição AJAX: ' + response.statusText);
                }
                return response.text();
            })
            .then(html => {
                disciplinaTableContainer.innerHTML = html; // Atualiza o conteúdo da tabela
                
                // Atualiza a URL no histórico do navegador sem recarregar a página
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('q', searchTerm);
                newUrl.searchParams.set('pagina', page);
                window.history.pushState({
                    path: newUrl.href
                }, '', newUrl.href);
            })
            .catch(error => {
                console.error('Erro ao buscar disciplinas:', error);
                // Opcional: mostrar uma mensagem de erro ao usuário aqui
            });
        }

        // Event listener para a barra de pesquisa (digitação)
        searchInput.addEventListener('keyup', function() {
            clearTimeout(searchTimeout); // Limpa o timeout anterior
            const searchTerm = this.value;
            searchTimeout = setTimeout(() => {
                fetchDisciplinas(searchTerm, 1); // Realiza a busca após 300ms de inatividade
            }, 300);
        });

        // Event listener para os links de paginação (delegation)
        disciplinaTableContainer.addEventListener('click', function(event) {
            console.log('Clique detectado na tabela de disciplinas.');
            const paginationLink = event.target.closest('.pagination-link');
            if (paginationLink) {
                event.preventDefault(); // Impede o comportamento padrão do link

                const url = new URL(paginationLink.href);
                const page = url.searchParams.get('pagina');
                const searchTerm = searchInput.value;

                console.log('Link de paginação clicado. Buscando página:', page, 'Termo de pesquisa:', searchTerm);

                fetchDisciplinas(searchTerm, page); // Busca a nova página
            }
        });
        /**
         * Abre a modal de visualização da ementa.
         * Esta função é chamada diretamente via `onclick` no HTML do botão.
         * @param {string} nomeDisciplina - O nome da disciplina.
         * @param {string} ementaCompleta - O texto completo da ementa.
         */
        window.abrirModalEmenta = function(nomeDisciplina, ementaCompleta) {
            modalTitle.textContent = 'Ementa da Disciplina: ' + nomeDisciplina;
            modalEmenta.textContent = ementaCompleta;
            ementaModal.style.display = 'flex'; // Exibe a modal usando flexbox para centralização
        };

        /**
         * Fecha a modal de visualização da ementa.
         * Esta função é chamada via `onclick` do botão fechar ou do clique fora da modal.
         */
        window.fecharModalEmenta = function() {
            ementaModal.style.display = 'none'; // Esconde a modal
        };

        // Fechar modal clicando fora dela
        window.onclick = function(event) {
            if (event.target == ementaModal) {
                fecharModalEmenta();
            }
        };

        /**
         * Confirma a exclusão de uma disciplina e, se confirmado, redireciona para o PHP.
         * Esta função é chamada diretamente via `onclick` no HTML do botão de exclusão.
         * @param {number} idDisciplina - O ID da disciplina a ser excluída.
         * @param {string} nomeDisciplina - O nome da disciplina (para a mensagem de confirmação).
         */
        window.confirmarExclusao = function(idDisciplina, nomeDisciplina) {
            // Mensagem de confirmação atualizada, sem referência à "turma"
            if (confirm(`Tem certeza que deseja excluir a disciplina "${nomeDisciplina}" (ID: ${idDisciplina})?\n\nEsta ação é irreversível e a disciplina será removida permanentemente do sistema.`)) {
                // Se o usuário confirmar, redireciona para o PHP processar a exclusão
                window.location.href = `gerenciar_disciplinas.php?action=excluir&id=${idDisciplina}`;
            }
        };
    });
</script>