<?php
// htdocs/avapm/gerenciar_usuarios.php

// Define o título da página
$page_title = "Gerenciar Usuários";

// Inicia a sessão (se já não estiver iniciada)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// INCLUI A CONEXÃO COM O BANCO DE DADOS AQUI (SEMPRE!)
require_once __DIR__ . '/includes/conexao.php'; 

// Níveis de acesso permitidos para esta página
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE']; 

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
// Lógica para processar a exclusão de usuário (Se você tiver essa lógica aqui)
// =====================================================================
// Exemplo de lógica de exclusão, caso não tenha (adapte se for diferente)
if (isset($_GET['action']) && $_GET['action'] == 'excluir' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_usuario = (int)$_GET['id'];
    try {
        $stmt_delete = $pdo->prepare("DELETE FROM usuario WHERE id = :id");
        $stmt_delete->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        if ($stmt_delete->execute()) {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Usuário excluído com sucesso!'];
        } else {
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao excluir usuário.'];
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => "Erro no banco de dados: " . $e->getMessage()];
        error_log("Erro PDO em gerenciar_usuarios.php ao excluir: " . $e->getMessage());
    }
    header('Location: gerenciar_usuarios.php');
    exit();
}


// Lógica para detecção de requisição AJAX
$is_ajax_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Define a página atual para a sidebar destacar o link ativo (usado na sidebar)
$current_page = "Usuários";

// =====================================================================
// Configurações de Paginação
// =====================================================================
$limite_por_pagina = 10; // Quantos usuários por página
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;

// =====================================================================
// Lógica de Pesquisa
// =====================================================================
$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) : '';

$condicoes_sql = [];
$parametros_sql = [];

if (!empty($termo_pesquisa)) {
    $condicoes_sql[] = "(u.nome LIKE :termo OR 
                          u.email LIKE :termo OR 
                          u.rg LIKE :termo OR 
                          u.cpf LIKE :termo OR   
                          u.patente LIKE :termo OR     
                          u.titulacao LIKE :termo OR    
                          u.instituicao LIKE :termo OR   
                          u.fonte_pagadora LIKE :termo OR 
                          u.telefone LIKE :termo)"; 
    $parametros_sql[':termo'] = '%' . $termo_pesquisa . '%';
}

$where_clause = '';
if (!empty($condicoes_sql)) {
    $where_clause = " WHERE " . implode(" OR ", $condicoes_sql);
}

// =====================================================================
// Lógica para buscar o total de usuários (para paginação)
// =====================================================================
$total_usuarios = 0;
try {
    $stmt_total = $pdo->prepare("SELECT COUNT(u.id) FROM usuario u " . $where_clause);
    $stmt_total->execute($parametros_sql);
    $total_usuarios = $stmt_total->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao contar usuários: " . $e->getMessage());
}
$total_paginas = ceil($total_usuarios / $limite_por_pagina);

// =====================================================================
// Lógica para buscar os usuários para a página atual (com filtro e paginação)
// =====================================================================
$usuarios = [];
try {
    $sql = "SELECT id, nome, email, rg, cpf, telefone, nivel_acesso, foto,
                    patente, titulacao, instituicao, fonte_pagadora 
             FROM usuario u
             " . $where_clause . "
             ORDER BY nome ASC
             LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    foreach ($parametros_sql as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    unset($val); 

    $stmt->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erro ao carregar usuários: " . $e->getMessage() . "</p>";
    error_log("Erro ao carregar usuários: " . $e->getMessage());
}

// =====================================================================
// Renderização do HTML - Lógica Condicional para AJAX vs. Página Completa
// =====================================================================

// Se for uma requisição AJAX, APENAS inclua a parcial da tabela e saia
if ($is_ajax_request) {
    ob_start(); // Inicia o buffer para capturar o HTML da parcial
    require_once __DIR__ . '/includes/templates/user_table_partial.php'; 
    $html_content = ob_get_clean(); // Pega o HTML capturado
    echo $html_content; // Envia o HTML de volta para o JavaScript
    exit(); // Termina o script aqui para não enviar o resto da página
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
                // Verifica se a variável existe e se o arquivo da foto existe
                if (!empty($_SESSION['foto_perfil']) && file_exists('imagens/profiles/' . $_SESSION['foto_perfil'])) {
                    $foto_src = 'imagens/profiles/' . htmlspecialchars($_SESSION['foto_perfil']);
                } 
                ?>
                <?php if (!empty($foto_src)) : ?>
                    <img src="<?php echo $foto_src; ?>" alt="Avatar" style="max-width: 30px; height: auto; border-radius: 50%;">
                <?php else : ?>
                    <i class="fas fa-user-circle fa-lg"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Usuários Cadastrados</h2>
            <div class="action-buttons">
                <a href="adicionar_usuario.php" class="btn-primary-dashboard">
                    <i class="fas fa-user-plus"></i> Adicionar Usuário
                </a>
                <a href="export_data.php?type=users&format=excel&q=<?php echo urlencode($termo_pesquisa); ?>" class="btn-secondary-dashboard" title="Exportar para Excel">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
                <a href="export_data.php?type=users&format=pdf&q=<?php echo urlencode($termo_pesquisa); ?>" class="btn-secondary-dashboard" title="Exportar para PDF" target="_blank">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </a>
            </div>
        </div>
        
        <?php
        // Mensagens de feedback só são mostradas na carga inicial
        if (isset($_SESSION['mensagem_feedback'])) {
            $feedback = $_SESSION['mensagem_feedback'];
            echo '<div class="alert alert-' . htmlspecialchars($feedback['tipo']) . '">' . htmlspecialchars($feedback['texto']) . '</div>';
            unset($_SESSION['mensagem_feedback']);
        }
        ?>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Pesquisar usuários por Nome, Email, RG, CPF, Patente, Titulação, Instituição, Telefone..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
            <i class="fas fa-search"></i>
        </div>

        <div id="userTableContainer">
            <?php 
            // Para a requisição inicial, inclua a parcial da tabela aqui
            require_once __DIR__ . '/includes/templates/user_table_partial.php'; 
            ?>
        </div> 
    </div>

</div>

<?php
// Inclui o rodapé do dashboard (apenas para a carga completa da página)
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<!-- Modal para exibir disciplinas do professor -->
<div id="disciplinasModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="fecharModalDisciplinas()">&times;</span>
        <h2 id="modalProfessorNome"></h2>
        <div id="modalDisciplinasList">
            <!-- A lista de disciplinas será carregada aqui via JavaScript -->
        </div>
        <p id="modalDisciplinasStatus" style="text-align: center; font-style: italic;"></p>
    </div>
</div>

<style>
    /* Estilos básicos para a modal */
    .modal {
        display: none; /* Oculto por padrão */
        position: fixed; /* Fica no topo */
        z-index: 1; /* Fica acima de tudo */
        left: 0;
        top: 0;
        width: 100%; /* Largura total */
        height: 100%; /* Altura total */
        overflow: auto; /* Habilita scroll se necessário */
        background-color: rgba(0,0,0,0.4); /* Fundo escuro */
        justify-content: center; /* Centraliza horizontalmente com flex */
        align-items: center; /* Centraliza verticalmente com flex */
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto; /* Centraliza */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Pode ajustar a largura */
        max-width: 600px; /* Largura máxima */
        border-radius: 10px;
        position: relative; /* Necessário para posicionar o botão de fechar */
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .close-button {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        top: 10px;
        right: 20px;
    }

    .close-button:hover,
    .close-button:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    #modalDisciplinasList ul {
        list-style: none;
        padding: 0;
    }

    #modalDisciplinasList li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    #modalDisciplinasList li:last-child {
        border-bottom: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const userTableContainer = document.getElementById('userTableContainer');
    let searchTimeout;

    function fetchUsers(searchTerm, page = 1) {
        // Adiciona log para depuração
        console.log(`fetchUsers chamado com termo: "${searchTerm}", página: ${page}`);


        // CONSTRUIR A URL CORRETAMENTE
        const url = `gerenciar_usuarios.php?q=${encodeURIComponent(searchTerm)}&pagina=${page}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
            }
        })
        .then(response => { // Primeira promessa: receber a resposta
            console.log('Resposta da requisição AJAX recebida.'); // Log
            if (!response.ok) {
                throw new Error('Erro na requisição AJAX: ' + response.statusText);
            }
            // Continua para a próxima promessa, passando o texto (HTML)
            return response.text(); // Pega o HTML da resposta
        })
        .then(html => {
            userTableContainer.innerHTML = html; // Insere o HTML da tabela no container
            addDeleteConfirmationListeners(); // Re-adiciona listeners para novos botões

            // === NOVO: Atualiza os links dos botões de exportação ===
            const exportExcelLink = document.querySelector('a[href*="export_data.php?type=users&format=excel"]');
            const exportPdfLink = document.querySelector('a[href*="export_data.php?type=users&format=pdf"]');
            if (exportExcelLink) {
                const currentHref = new URL(exportExcelLink.href);
                currentHref.searchParams.set('q', searchTerm);
                exportExcelLink.href = currentHref.toString();
            }
            if (exportPdfLink) {
                const currentHref = new URL(exportPdfLink.href);
                currentHref.searchParams.set('q', searchTerm);
                exportPdfLink.href = currentHref.toString();
            }
            // ========================================================

            // Atualiza a URL na barra de endereços sem recarregar a página
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('q', searchTerm);
            // Certifica-se de que o parâmetro de página seja definido corretamente
            newUrl.searchParams.set('pagina', page);
            window.history.pushState({ path: newUrl.href }, '', newUrl.href);

        })
        .catch(error => {
            console.error('Erro ao buscar usuários:', error);
            // Exibe uma mensagem de erro na interface do usuário, se desejar
            // userTableContainer.innerHTML = '<p class="error-message" style="color: red;">Não foi possível carregar os dados. Tente novamente.</p>';
        });
    }

    searchInput.addEventListener('keyup', function() {
         console.log('Evento keyup no searchInput.'); // Log
        clearTimeout(searchTimeout); // Limpa qualquer timeout anterior
        const searchTerm = this.value;
        searchTimeout = setTimeout(() => {
            fetchUsers(searchTerm, 1); // Sempre volta para a página 1 ao pesquisar
        }, 300); // Pequeno atraso para evitar muitas requisições
    });

    // Delegar evento de clique para links de paginação dentro do container
    userTableContainer.addEventListener('click', function(event) {
         console.log('Clique dentro do userTableContainer.'); // Log
        // Verifica se o clique foi em um link com a classe 'pagination-link' ou em um de seus descendentes
        const paginationLink = event.target.closest('.pagination-link');
        if (paginationLink) {
             console.log('Link de paginação clicado.'); // Log
            event.preventDefault(); // Impede o comportamento padrão do link

            // Pega a URL do link clicado
            const url = new URL(paginationLink.href);
            // Extrai o parâmetro 'pagina' da URL
            const page = url.searchParams.get('pagina');
            // Usa o termo atual da caixa de pesquisa (para manter o filtro)
            const searchTerm = searchInput.value; // Usa o termo atual da caixa de pesquisa

            console.log(`Navegando para página: ${page}, com termo: "${searchTerm}"`); // Log

            fetchUsers(searchTerm, page);
        }
    });

    // Função para adicionar/re-adicionar listeners de confirmação de exclusão
    function addDeleteConfirmationListeners() {
        document.querySelectorAll('.btn-action.delete-btn').forEach(button => {
            // Remove o listener anterior para evitar duplicação (se já existia)
            button.removeEventListener('click', confirmDelete);
            // Adiciona o novo listener
            button.addEventListener('click', confirmDelete);
        });
    }

    // Função de confirmação para exclusão
    function confirmDelete(event) {
        if (!confirm('Tem certeza que deseja excluir este usuário? Esta ação é irreversível.')) {
            event.preventDefault(); // Impede a ação padrão se o usuário cancelar
            return false;
        }
        return true;
    }

    // Adiciona os listeners na carga inicial da página
    addDeleteConfirmationListeners();

    // ==============================================================
    // JavaScript para a Modal de Disciplinas do Professor
    // ==============================================================
    const disciplinasModal = document.getElementById('disciplinasModal');
    const modalProfessorNome = document.getElementById('modalProfessorNome');
    const modalDisciplinasList = document.getElementById('modalDisciplinasList');
    const modalDisciplinasStatus = document.getElementById('modalDisciplinasStatus');
    const closeButtonDisciplinas = disciplinasModal.querySelector('.close-button');

    // Event listener para os botões "Disciplinas" (delegação)
    userTableContainer.addEventListener('click', function(event) {
        const disciplinaButton = event.target.closest('.btn-ver-disciplinas');
        if (disciplinaButton) {
            event.preventDefault(); // Impede qualquer ação padrão

            const userId = disciplinaButton.getAttribute('data-user-id');
            const userName = disciplinaButton.getAttribute('data-user-name');

            // Exibe o nome do professor na modal
            modalProfessorNome.textContent = `Disciplinas ministradas por: ${userName}`;
            modalDisciplinasList.innerHTML = ''; // Limpa a lista anterior
            modalDisciplinasStatus.textContent = 'Carregando disciplinas...'; // Mensagem de carregamento
            disciplinasModal.style.display = 'flex'; // Exibe a modal (usando flex para centralizar)

            fetchDisciplinasProfessor(userId); // Chama a função para buscar as disciplinas via AJAX
        }

    });

    /**
     * Busca as disciplinas ministradas por um professor via AJAX e preenche a modal.
     * @param {number} userId - O ID do professor.
     */
    function fetchDisciplinasProfessor(userId) {
        // URL do endpoint que você criará (ou adaptará) para buscar as disciplinas
        const url = `/avapm/get_disciplinas_professor.php?user_id=${encodeURIComponent(userId)}`; 

        fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição AJAX para obter disciplinas: ' + response.statusText);
            }
             // Espera que o endpoint retorne JSON
            return response.json(); 
        })
        .then(data => {
            modalDisciplinasList.innerHTML = ''; // Limpa status de carregamento ou "sem disciplinas"
            modalDisciplinasStatus.textContent = ''; // Limpa status

            if (data.length > 0) {
                const ul = document.createElement('ul');
                data.forEach(disciplina => {
                    const li = document.createElement('li');
                    li.textContent = `${disciplina.sigla} - ${disciplina.nome}`; // Assume que a resposta JSON tem sigla e nome
                    ul.appendChild(li);
                });
                modalDisciplinasList.appendChild(ul);
            } else {
                modalDisciplinasStatus.textContent = 'Este professor não ministra nenhuma disciplina no momento.';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar disciplinas do professor:', error);
            modalDisciplinasStatus.textContent = 'Erro ao carregar disciplinas.';
        });
    }

    // Event listener para o botão de fechar a modal
    closeButtonDisciplinas.addEventListener('click', function() {
        disciplinasModal.style.display = 'none';
    });

    // Fechar modal clicando fora dela
    window.onclick = function(event) {
        if (event.target == disciplinasModal) {
            disciplinasModal.style.display = 'none';
        }
    };
});

        }
    };
});
</script>