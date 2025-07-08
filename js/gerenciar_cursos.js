// htdocs/avapm2/js/gerenciar_cursos.js

// Garante que o código só é executado quando o DOM estiver completamente carregado
$(document).ready(function() {

    // Dados de disciplinas e professores do PHP, assumindo que foram definidos globalmente na página
    // Ex: <script>const disciplinasData = [...]; const professoresData = [...];</script>
    // Se eles não estiverem definidos globalmente, este script precisará ser movido para o PHP ou carregar via AJAX.
    // Para fins desta reescrita, assumimos que 'disciplinasData' e 'professoresData' estão disponíveis.

    // --- Datepicker initialization ---
    // Aplica o datepicker a todos os campos com a classe 'datepicker'
    $(".datepicker").datepicker({
        dateFormat: 'dd/mm/yy',
        dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
        dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
        dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
        monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        nextText: 'Próximo',
        prevText: 'Anterior'
    });

    // --- Funções Auxiliares ---

    // Função para limpar e resetar o formulário do modal
    function resetCursoModal() {
        $('#curso_id').val('');
        $('#sigla').val('');
        $('#nome_curso').val('');
        $('#data_inicio').val('');
        $('#data_fim').val('');
        $('#data_avaliacao').val('');
        $('#horas').val('');
        $('#gradeAulasContainer').empty(); // Limpa as aulas da grade
        $('#cursoModalLabel').text('Adicionar Novo Curso');
        $('#cursoForm').removeClass('was-validated'); // Remove classes de validação do Bootstrap
    }

    // Função para adicionar um item de grade (dropdowns de disciplina e professor)
    function addGradeItem(disciplinaId = '', professorId = '') {
        const container = $('#gradeAulasContainer');

        const disciplinasOptions = disciplinasData.map(d =>
            `<option value="${d.id}" ${d.id == disciplinaId ? 'selected' : ''}>${d.nome} (${d.sigla})</option>`
        ).join('');

        const professoresOptions = professoresData.map(p =>
            `<option value="${p.id}" ${p.id == professorId ? 'selected' : ''}>${p.nome}</option>`
        ).join('');

        const newRow = `
            <div class="form-row form-inline grade-item mt-2 p-2 border rounded bg-light">
                <div class="form-group col-md-5">
                    <select class="form-control w-100" name="disciplina_id[]" required>
                        <option value="">Selecione a Disciplina</option>
                        ${disciplinasOptions}
                    </select>
                </div>
                <div class="form-group col-md-5">
                    <select class="form-control w-100" name="professor_id[]" required>
                        <option value="">Selecione o Professor</option>
                        ${professoresOptions}
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <button type="button" class="btn btn-danger btn-sm remove-aula-btn"><i class="fas fa-minus-circle"></i></button>
                </div>
            </div>
        `;
        container.append(newRow);
    }

    // Função para carregar a tabela de cursos via AJAX
    function loadCourseTable(searchTerm = '', page = 1) {
        $.ajax({
            url: 'gerenciar_cursos.php',
            type: 'GET',
            data: {
                q: searchTerm,
                pagina: page,
                ajax: 1 // Sinaliza que é uma requisição AJAX para a parte do PHP
            },
            success: function(response) {
                $('#courseTableContainer').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Erro na requisição AJAX: " + error);
                // Opcional: exibir uma mensagem de erro para o usuário
            }
        });
    }

    // --- Event Listeners ---

    // Adicionar Nova Aula (Disciplina + Professor) no modal
    $('#addAulaBtn').on('click', function() {
        addGradeItem();
    });

    // Remover Aula do modal (usa delegação de evento para elementos dinâmicos)
    $('#gradeAulasContainer').on('click', '.remove-aula-btn', function() {
        $(this).closest('.grade-item').remove();
    });

    // Abrir Modal de Adição (resetar formulário)
    $('#addCursoBtn').on('click', function() {
        resetCursoModal();
        $('#cursoModalLabel').text('Adicionar Novo Curso');
        addGradeItem(); // Adiciona uma linha de grade vazia por padrão para novo curso
    });

    // Resetar formulário ao fechar o modal
    $('#cursoModal').on('hidden.bs.modal', function() {
        resetCursoModal();
    });

    // Abrir e Preencher Modal em Modo Edição (usa delegação de evento para elementos dinâmicos)
    $(document).on('click', '.edit-curso-btn', function() {
        resetCursoModal(); // Limpa o modal antes de preencher com novos dados

        // Pega os dados do atributo data-curso. jQuery decodifica o JSON automaticamente.
        const cursoData = $(this).data('curso');

        $('#cursoModalLabel').text('Editar Curso'); // Altera o título do modal
        $('#curso_id').val(cursoData.id);
        $('#sigla').val(cursoData.sigla);
        $('#nome_curso').val(cursoData.nome);
        // Formate as datas para o padrão do datepicker se necessário (PHP já deve ter feito)
        $('#data_inicio').val(cursoData.data_inicio_formatada);
        $('#data_fim').val(cursoData.data_fim_formatada);
        $('#data_avaliacao').val(cursoData.data_avaliacao_formatada);
        $('#horas').val(cursoData.horas);

        // Preencher a grade do curso
        if (cursoData.grade_curso && cursoData.grade_curso.length > 0) {
            cursoData.grade_curso.forEach(function(aula) {
                addGradeItem(aula.disciplina_id, aula.usuario_id);
            });
        } else {
            addGradeItem(); // Se o curso não tiver grade, adicione uma linha vazia para começar
        }

        $('#cursoModal').modal('show'); // Abre o modal
    });

    // Lógica para pesquisa dinâmica (AJAX)
    $('#searchInput').on('keyup', function() {
        const searchTerm = $(this).val();
        loadCourseTable(searchTerm, 1); // Volta para a primeira página ao pesquisar
    });

    // Lógica para paginação dinâmica (AJAX)
    $(document).on('click', '.pagination-link', function(e) {
        e.preventDefault(); // Impede o comportamento padrão do link
        const url = new URL($(this).attr('href'));
        const newPage = url.searchParams.get('pagina');
        const currentSearchTerm = $('#searchInput').val(); // Pega o termo de pesquisa atual

        loadCourseTable(currentSearchTerm, newPage);
    });

    // Lógica para reabrir o modal em caso de erro de POST (com dados do PHP)
    // Verifica se a variável global 'cursoParaModalData' foi definida pelo PHP
    if (typeof cursoParaModalData !== 'undefined' && cursoParaModalData) {
        resetCursoModal(); // Limpa antes de preencher

        $('#cursoModalLabel').text(cursoParaModalData.id ? "Editar Curso" : "Adicionar Novo Curso");
        $('#curso_id').val(cursoParaModalData.id || "");
        $('#sigla').val(cursoParaModalData.sigla || "");
        $('#nome_curso').val(cursoParaModalData.nome || "");
        $('#data_inicio').val(cursoParaModalData.data_inicio_formatada || "");
        $('#data_fim').val(cursoParaModalData.data_fim_formatada || "");
        $('#data_avaliacao').val(cursoParaModalData.data_avaliacao_formatada || "");
        $('#horas').val(cursoParaModalData.horas || "");

        if (cursoParaModalData.grade_curso && cursoParaModalData.grade_curso.length > 0) {
            cursoParaModalData.grade_curso.forEach(function(aula) {
                addGradeItem(aula.disciplina_id, aula.usuario_id);
            });
        } else {
            addGradeItem(); // Adiciona uma linha vazia se não houver grade
        }

        $('#cursoModal').modal('show');
        $('#cursoForm').addClass('was-validated'); // Adiciona a classe 'was-validated' para exibir mensagens de erro do Bootstrap
    }
});