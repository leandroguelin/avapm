<?php // includes/templates/_view_passo2_academia.php 
$stmt = $pdo->prepare("SELECT id, pergunta, descricao FROM questionario WHERE categoria = 'Academia' ORDER BY id");
$stmt->execute();
$perguntas_academia = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    .rating-group { display: flex; justify-content: space-between; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
    .rating-group input[type="radio"] { display: none; }
    .rating-group label { padding: 10px; flex-grow: 1; text-align: center; cursor: pointer; background: #f8f9fa; transition: background 0.2s; }
    .rating-group input[type="radio"]:checked + label { background: #007bff; color: #fff; font-weight: bold; }
    .question-block { margin-bottom: 30px; }
    .question-block p { margin-bottom: 15px; }
    .justificativa-field { display: none; margin-top: 10px; } /* Esconde o campo de justificativa por padrão */
</style>
<div class="evaluation-header"><h1>Passo 2 de 3: Avaliação da Academia</h1><p>...</p></div><hr>
<form action="responder_avaliacao.php" method="POST">
    <input type="hidden" name="passo" value="2">
    <?php foreach ($perguntas_academia as $pergunta): ?>
    <div class="question-block">
        <h4><?php echo htmlspecialchars($pergunta['pergunta']); ?></h4>
        <p class="text-muted"><?php echo htmlspecialchars($pergunta['descricao']); ?></p>
        <div class="rating-group">
            <?php for ($i = 0; $i <= 10; $i++): ?>
            <input type="radio" class="rating-radio" id="q<?php echo $pergunta['id']; ?>_n<?php echo $i; ?>" name="respostas[<?php echo $pergunta['id']; ?>][nota]" value="<?php echo $i; ?>" required>
            <label for="q<?php echo $pergunta['id']; ?>_n<?php echo $i; ?>"><?php echo $i; ?></label>
            <?php endfor; ?>
        </div>
        <div class="justificativa-field" id="justificativa_q<?php echo $pergunta['id']; ?>">
            <label for="obs_q<?php echo $pergunta['id']; ?>">Justificativa (Obrigatório para notas menores que 5):</label>
            <textarea class="form-control" name="respostas[<?php echo $pergunta['id']; ?>][observacoes]" id="obs_q<?php echo $pergunta['id']; ?>" rows="2"></textarea>
        </div>
        <input type="hidden" name="respostas[<?php echo $pergunta['id']; ?>][texto_pergunta]" value="<?php echo htmlspecialchars($pergunta['pergunta']); ?>">
        <input type="hidden" name="respostas[<?php echo $pergunta['id']; ?>][categoria]" value="Academia">
        <input type="hidden" name="respostas[<?php echo $pergunta['id']; ?>][avaliado]" value="Academia">
    </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary mt-3">Próximo Passo</button>
</form>
<script>
// Script para mostrar/esconder campo de justificativa
document.querySelectorAll('.rating-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const questionId = this.name.match(/\[(.*?)\]/)[1];
        const justificativaDiv = document.getElementById('justificativa_q' + questionId);
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