<?php // includes/templates/_view_passo1_identificacao.php ?>
<div class="evaluation-header">
    <h1>Instruções da Avaliação Institucional</h1>
    <p>Bem-vindo(a) à Avaliação Institucional. Seu feedback é anônimo e confidencial, essencial para a melhoria contínua dos nossos cursos e da academia. As respostas serão usadas para identificar pontos fortes e áreas que necessitam de aprimoramento.</p>
    <p><strong>A sua participação é muito importante!</strong></p>
</div>
<hr>
<form action="responder_avaliacao.php" method="POST">
    <input type="hidden" name="passo" value="1">
    <h3>Identificação (Opcional)</h3>
    <p>Se desejar, você pode se identificar. Respostas identificadas podem nos ajudar a entender melhor contextos específicos, mas não é obrigatório.</p>
    <?php if (isset($_SESSION['erro_passo1'])) { echo '<div class="alert alert-danger">' . $_SESSION['erro_passo1'] . '</div>'; unset($_SESSION['erro_passo1']); } ?>
    <div class="form-check my-3">
        <input class="form-check-input" type="checkbox" name="anonimo" id="anonimoCheck">
        <label class="form-check-label" for="anonimoCheck">Desejo realizar a avaliação de forma anônima.</label>
    </div>
    <div id="identificacaoFields">
        <div class="form-group">
            <label for="cpf">CPF</label>
            <input type="text" class="form-control" name="cpf" id="cpf" placeholder="000.000.000-00">
        </div>
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" class="form-control" name="nome" id="nome" placeholder="Seu nome completo">
        </div>
        <div class="form-group">
            <label for="contato">Telefone</label>
            <input type="text" class="form-control" name="contato" id="contato" placeholder="(00) 00000-0000">
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Iniciar Avaliação</button>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function() {
    // MELHORIA: Aplicando as máscaras de formatação
    $('#cpf').mask('000.000.000-00');
    $('#contato').mask('(00) 00000-0000');

    const anonimoCheck = $('#anonimoCheck');
    const identificacaoFields = $('#identificacaoFields');
    const requiredInputs = identificacaoFields.find('input');

    anonimoCheck.on('change', function() {
        if (this.checked) {
            identificacaoFields.hide();
            requiredInputs.prop('required', false);
        } else {
            identificacaoFields.show();
            requiredInputs.prop('required', true);
        }
    }).trigger('change');
});
</script>