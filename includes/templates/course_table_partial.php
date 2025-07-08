<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>

<?php
// htdocs/avapm2/includes/templates/course_table_partial.php

// Este arquivo é incluído por gerenciar_cursos.php
// Ele assume que as variáveis $cursos, $pagina_atual, $total_paginas, $termo_pesquisa
// já estão definidas no escopo de gerenciar_cursos.php

// Garante que a variável $cursos está definida, mesmo que vazia
if (!isset($cursos)) {
    $cursos = [];
}

// MELHORIA: As tags <link> e <script> foram removidas daqui.
// Elas devem ser carregadas apenas uma vez no footer ou header principal
// para evitar carregamentos duplicados em requisições AJAX.
?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Sigla</th>
                <th>Nome do Curso</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Avaliação</th>
                <th class="text-center">Horas</th>
                <th class="text-center" style="width: 180px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cursos)): ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhum curso encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($curso['sigla']); ?></td>
                        <td><?php echo htmlspecialchars($curso['nome']); ?></td>
                        <td><?php echo htmlspecialchars($curso['data_inicio_formatada']); ?></td>
                        <td><?php echo htmlspecialchars($curso['data_fim_formatada']); ?></td>
                        <td><?php echo htmlspecialchars($curso['data_avaliacao_formatada']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($curso['horas']); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info edit-curso-btn"
                                data-toggle="modal" data-target="#cursoModal"
                                data-curso='<?php echo htmlspecialchars(json_encode($curso), ENT_QUOTES, 'UTF-8'); ?>' title="Editar Curso">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="gerenciar_cursos.php?action=excluir&id=<?php echo $curso['id']; ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Tem certeza que deseja excluir o curso \'<?php echo addslashes(htmlspecialchars($curso['nome'])); ?>\'?\n\nEsta ação é irreversível e removerá também toda a sua grade de disciplinas.');"
                                title="Excluir Curso">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <!-- <a href="deletar_curso.php?id=<?php echo $curso['id']; ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Tem certeza que deseja excluir o curso \'<?php echo addslashes(htmlspecialchars($curso['nome'])); ?>\'?\nEsta ação é irreversível.');"
                                title="Excluir Curso">
                                <i class="fas fa-trash-alt"></i>
                            </a> -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($total_paginas) && $total_paginas > 1): ?>
    <nav aria-label="Paginação de cursos">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link pagination-link" href="?pagina=<?php echo $pagina_atual - 1; ?>&q=<?php echo urlencode($termo_pesquisa); ?>">Anterior</a>
            </li>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                    <a class="page-link pagination-link" href="?pagina=<?php echo $i; ?>&q=<?php echo urlencode($termo_pesquisa); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">
                <a class="page-link pagination-link" href="?pagina=<?php echo $pagina_atual + 1; ?>&q=<?php echo urlencode($termo_pesquisa); ?>">Próxima</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>