<?php

// Inclui o arquivo de conexão com o banco de dados
// Isso torna o objeto $pdo de conexão disponível para este script
require_once 'includes/conexao.php';

echo "Iniciando a criação das tabelas no banco de dados 'avapm'...<br>";

// Array contendo os comandos SQL para criar cada tabela
$sqls = [
    // Tabela 'patente'
    "CREATE TABLE IF NOT EXISTS patente (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sigla VARCHAR(10) UNIQUE NOT NULL,
        nome VARCHAR(100) NOT NULL
    );",

    // Tabela 'titulacao'
    "CREATE TABLE IF NOT EXISTS titulacao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sigla VARCHAR(10) UNIQUE NOT NULL,
        nome VARCHAR(100) NOT NULL
    );",

    // Tabela 'instituicao' (renomeada de 'forca' para refletir o contexto de fonte pagadora/instituição de origem)
    "CREATE TABLE IF NOT EXISTS instituicao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sigla VARCHAR(10) UNIQUE NOT NULL,
        nome VARCHAR(100) NOT NULL
    );",

    // Tabela 'usuario'
    "CREATE TABLE IF NOT EXISTS usuario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        nivel_acesso ENUM('administrador', 'aluno', 'professor') NOT NULL,
        rg VARCHAR(20) UNIQUE,
        patente_id INT,
        titulacao_id INT,
        instituicao_id INT, -- Alterado de 'forca_id' para 'instituicao_id'
        nome_guerra VARCHAR(100),
        telefone VARCHAR(20),
        foto VARCHAR(255),
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patente_id) REFERENCES patente(id) ON DELETE SET NULL,
        FOREIGN KEY (titulacao_id) REFERENCES titulacao(id) ON DELETE SET NULL,
        FOREIGN KEY (instituicao_id) REFERENCES instituicao(id) ON DELETE SET NULL
    );",

    // Tabela 'disciplina'
    "CREATE TABLE IF NOT EXISTS disciplina (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sigla VARCHAR(20) UNIQUE NOT NULL,
        nome VARCHAR(255) NOT NULL,
        horas INT,
        ementa TEXT
    );",

    // Tabela 'cursos'
    "CREATE TABLE IF NOT EXISTS cursos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sigla VARCHAR(50) UNIQUE NOT NULL,
        nome VARCHAR(255) NOT NULL,
        data_inicio DATE,
        data_fim DATE,
        data_avaliacao DATE,
        horas INT
    );",

    // Tabela 'categoria_questionario'
    "CREATE TABLE IF NOT EXISTS categoria_questionario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) UNIQUE NOT NULL
    );",

    // Tabela 'questionario'
    "CREATE TABLE IF NOT EXISTS questionario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pergunta TEXT NOT NULL,
        descricao VARCHAR(255),
        categoria_id INT,
        FOREIGN KEY (categoria_id) REFERENCES categoria_questionario(id) ON DELETE SET NULL
    );",

    // Tabela 'grade_curso' (associa cursos, disciplinas e professores)
    "CREATE TABLE IF NOT EXISTS grade_curso (
        id INT AUTO_INCREMENT PRIMARY KEY,
        curso_id INT NOT NULL,
        disciplina_id INT NOT NULL,
        usuario_id INT, -- Referencia o professor que leciona a disciplina no curso
        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
        FOREIGN KEY (disciplina_id) REFERENCES disciplina(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE SET NULL,
        UNIQUE (curso_id, disciplina_id, usuario_id) -- Garante que uma disciplina é atribuída a um professor em um curso apenas uma vez
    );",

    // Tabela 'avaliacao' (Cabeçalho da avaliação, ligada a um curso específico)
    "CREATE TABLE IF NOT EXISTS avaliacao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        curso_id INT NOT NULL,
        data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
    );",

    // Tabela 'questoesavaliacao' (itens do questionário para cada avaliação)
    "CREATE TABLE IF NOT EXISTS questoesavaliacao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        questionario_id INT NOT NULL, -- Alterado de 'pergunta_id' para 'questionario_id' para ser mais claro
        avaliacao_id INT NOT NULL,
        FOREIGN KEY (questionario_id) REFERENCES questionario(id) ON DELETE CASCADE,
        FOREIGN KEY (avaliacao_id) REFERENCES avaliacao(id) ON DELETE CASCADE,
        UNIQUE (questionario_id, avaliacao_id) -- Garante que uma questão não se repete na mesma avaliação
    );"
];

// Executa cada comando SQL
foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "Tabela criada/verificada com sucesso: " . substr($sql, 17, strpos($sql, '(') - 17) . "<br>";
    } catch (PDOException $e) {
        echo "Erro ao criar tabela: " . $e->getMessage() . "<br>";
    }
}

echo "<br>Processo de criação de tabelas finalizado.";

?>