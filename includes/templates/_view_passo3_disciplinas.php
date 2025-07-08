<?php // includes/templates/_view_passo3_disciplinas.php
$stmt_curso = $pdo->prepare("SELECT curso_id FROM avaliacao WHERE id = :avaliacao_id");
$stmt_curso->execute([':avaliacao_id' => $avaliacao_id]);
$curso_id = $stmt_curso->fetchColumn();
$stmt_grade = $pdo->prepare("SELECT d.id as disciplina_id, d.nome as disciplina_nome, u.id as professor_id, u.nome as professor_nome, u.patente, u.foto FROM grade_curso gc JOIN disciplina d ON gc.disciplina_id = d.id JOIN usuario u ON gc.usuario_id = u.id WHERE gc.curso_id = :curso_id ORDER BY d.nome");
$stmt_grade->execute([':curso_id' => $curso_id]);
$grade_curso = $stmt_grade->fetchAll(PDO::FETCH_ASSOC);
$stmt_perguntas = $pdo->prepare("SELECT id, pergunta, descricao FROM questionario WHERE categoria = 'Professor' ORDER BY id");
$stmt_perguntas->execute();
$perguntas_professor = $stmt_perguntas->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    /* Estilos (copiados da view anterior para garantir consistência) */
    .professor-card { display: flex; align-items: center; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .professor-avatar { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 15px; }
    .discipline-header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
    .rating-group { display: flex; justify-content: space-between; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
    .rating-group input[type="radio"] { display: none; }
    .rating-group label { padding: 10px; flex-grow: 1; text-align: center; cursor: pointer; background: #f8f9fa; transition: background 0.2s; }
    .rating-group input[type="radio"]:checked + label { background: #007bff; color: #fff; font-weight: bold; }
    .question-block { margin-bottom: 30px; }
    .question-block p { margin-bottom: 15px; }
    .justificativa-field { display: none; margin-top: 10px; }
</style>
<div class="evaluation-header"><h1>Passo 3 de 3: Avaliação das Disciplinas e Professores</h1><p>...</p></div><hr>
<form action="responder_avaliacao.php" method="POST">
    <input type="hidden" name="passo" value="3">
    <?php foreach ($grade_curso as $item_grade): ?>
        <div class="discipline-block mb-5">
            <h2 class="discipline-header"><?php echo htmlspecialchars($item_grade['disciplina_nome']); ?></h2>
            <div class="question-block">
                <h4>Avaliação geral da disciplina</h4>
                <div class="rating-group">
                    <?php for ($i = 0; $i <= 10; $i++): ?>
                    <input type="radio" class="rating-radio" id="d<?php echo $item_grade['disciplina_id']; ?>_n<?php echo $i; ?>" name="respostas[d<?php echo $item_grade['disciplina_id']; ?>][nota]" value="<?php echo $i; ?>" required>
                    <label for="d<?php echo $item_grade['disciplina_id']; ?>_n<?php echo $i; ?>"><?php echo $i; ?></label>
                    <?php endfor; ?>
                </div>
                <div class="justificativa-field" id="justificativa_d<?php echo $item_grade['disciplina_id']; ?>">
                    <label for="obs_d<?php echo $item_grade['disciplina_id']; ?>">Justificativa:</label>
                    <textarea class="form-control" name="respostas[d<?php echo $item_grade['disciplina_id']; ?>][observacoes]" id="obs_d<?php echo $item_grade['disciplina_id']; ?>" rows="2"></textarea>
                </div>
                <input type="hidden" name="respostas[d<?php echo $item_grade['disciplina_id']; ?>][texto_pergunta]" value="AVALIAÇÃO GERAL DA DISCIPLINA">
                <input type="hidden" name="respostas[d<?php echo $item_grade['disciplina_id']; ?>][categoria]" value="Disciplina">
                <input type="hidden" name="respostas[d<?php echo $item_grade['disciplina_id']; ?>][avaliado]" value="<?php echo $item_grade['disciplina_id']; ?>">
            </div>
            <hr style="margin: 20px 0;">
            <div class="professor-card">
                <img src="imagens/profiles/<?php echo !empty($item_grade['foto']) ? htmlspecialchars($item_grade['foto']) : 'default.png'; ?>" alt="Foto" class="professor-avatar">
                <div><h4>Professor(a): <?php echo htmlspecialchars($item_grade['patente'] . ' ' . $item_grade['professor_nome']); ?></h4></div>
            </div>
            <?php foreach ($perguntas_professor as $pergunta): ?>
            <div class="question-block">
                <h5><?php echo htmlspecialchars($pergunta['pergunta']); ?></h5>
                <div class="rating-group">
                    <?php $unique_id = "p{$item_grade['professor_id']}_q{$pergunta['id']}"; ?>
                    <?php for ($i = 0; $i <= 10; $i++): ?>
                    <input type="radio" class="rating-radio" id="<?php echo $unique_id; ?>_n<?php echo $i; ?>" name="respostas[<?php echo $unique_id; ?>][nota]" value="<?php echo $i; ?>" required>
                    <label for="<?php echo $unique_id; ?>_n<?php echo $i; ?>"><?php echo $i; ?></label>
                    <?php endfor; ?>
                </div>
                <div class="justificativa-field" id="justificativa_<?php echo $unique_id; ?>">
                    <label for="obs_<?php echo $unique_id; ?>">Justificativa:</label>
                    <textarea class="form-control" name="respostas[<?php echo $unique_id; ?>][observacoes]" id="obs_<?php echo $unique_id; ?>" rows="2"></textarea>
                </div>
                <input type="hidden" name="respostas[<?php echo $unique_id; ?>][texto_pergunta]" value="<?php echo htmlspecialchars($pergunta['pergunta']); ?>">
                <input type="hidden" name="respostas[<?php echo $unique_id; ?>][categoria]" value="Professor">
                <input type="hidden" name="respostas[<?php echo $unique_id; ?>][avaliado]" value="<?php echo $item_grade['professor_id']; ?>">
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-success btn-lg mt-3">Finalizar e Enviar Avaliação</button>
</form>
<script>
// Script para mostrar/esconder campo de justificativa
document.querySelectorAll('.rating-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const questionId = this.name.match(/\[(.*?)\]/)[1];
        const justificativaDiv = document.getElementById('justificativa_' + questionId);
        if (!justificativaDiv) return; // Se não achar a div, sai
        const textarea = justificativaDiv.querySelector('textarea');
        if (parseInt(this.value) < 5) {
            justificativaDiv.style.display = 'block';
            textarea.required = true;
        } else {
            justificativaDiv.style.display = 'none';
            textarea.required = false;
        }
    });
});
</script>