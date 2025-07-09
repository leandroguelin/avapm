CREATE TABLE public.avaliacao (
	id serial4 NOT NULL,
	curso_id int4 NOT NULL,
	codigo varchar(5) DEFAULT NULL::character varying NULL,
	nome varchar(100) DEFAULT NULL::character varying NULL,
	data_inicio date NULL,
	data_final date NULL,
	situacao varchar(11) DEFAULT NULL::character varying NULL,
	CONSTRAINT avaliacao_pkey PRIMARY KEY (id)
);


-- public.categoria_questionario definição

-- Drop table

-- DROP TABLE public.categoria_questionario;

CREATE TABLE public.categoria_questionario (
	id serial4 NOT NULL,
	nome varchar(100) NOT NULL,
	CONSTRAINT categoria_questionario_nome_key UNIQUE (nome),
	CONSTRAINT categoria_questionario_pkey PRIMARY KEY (id)
);


-- public.configuracoes definição

-- Drop table

-- DROP TABLE public.configuracoes;

CREATE TABLE public.configuracoes (
	id serial4 NOT NULL,
	chave varchar(50) NOT NULL,
	valor text NULL,
	CONSTRAINT configuracoes_chave_key UNIQUE (chave),
	CONSTRAINT configuracoes_pkey PRIMARY KEY (id)
);


-- public.cursos definição

-- Drop table

-- DROP TABLE public.cursos;

CREATE TABLE public.cursos (
	id serial4 NOT NULL,
	sigla varchar(50) NOT NULL,
	nome varchar(255) NOT NULL,
	data_inicio date NULL,
	data_fim date NULL,
	data_avaliacao date NULL,
	horas int4 NULL,
	CONSTRAINT cursos_pkey PRIMARY KEY (id),
	CONSTRAINT cursos_sigla_key UNIQUE (sigla)
);


-- public.disciplina definição

-- Drop table

-- DROP TABLE public.disciplina;

CREATE TABLE public.disciplina (
	id serial4 NOT NULL,
	sigla varchar(20) NOT NULL,
	nome varchar(255) NOT NULL,
	horas int4 NULL,
	ementa text NULL,
	CONSTRAINT disciplina_pkey PRIMARY KEY (id),
	CONSTRAINT disciplina_sigla_key UNIQUE (sigla)
);


-- public.grade_curso definição

-- Drop table

-- DROP TABLE public.grade_curso;

CREATE TABLE public.grade_curso (
	id serial4 NOT NULL,
	curso_id int4 NOT NULL,
	disciplina_id int4 NOT NULL,
	usuario_id int4 NULL,
	CONSTRAINT grade_curso_pkey PRIMARY KEY (id),
	CONSTRAINT unique_grade_curso UNIQUE (curso_id, disciplina_id, usuario_id)
);


-- public.instituicao definição

-- Drop table

-- DROP TABLE public.instituicao;

CREATE TABLE public.instituicao (
	id serial4 NOT NULL,
	sigla varchar(10) NOT NULL,
	nome varchar(100) NOT NULL,
	CONSTRAINT instituicao_pkey PRIMARY KEY (id),
	CONSTRAINT instituicao_sigla_key UNIQUE (sigla)
);


-- public.log_logins definição

-- Drop table

-- DROP TABLE public.log_logins;

CREATE TABLE public.log_logins (
	id serial4 NOT NULL,
	usuario_id int4 NOT NULL,
	data_login timestamp NOT NULL,
	ip varchar(45) NOT NULL,
	tipo_dispositivo varchar(255) DEFAULT NULL::character varying NULL,
	localizacao varchar(255) DEFAULT NULL::character varying NULL,
	CONSTRAINT log_logins_pkey PRIMARY KEY (id)
);


-- public.minhas_disciplinas definição

-- Drop table

-- DROP TABLE public.minhas_disciplinas;

CREATE TABLE public.minhas_disciplinas (
	id bigserial NOT NULL,
	usuario_id int4 NOT NULL,
	disciplina_id int4 NOT NULL,
	disponibilidade varchar(50) DEFAULT NULL::character varying NULL,
	CONSTRAINT minhas_disciplinas_pkey PRIMARY KEY (id),
	CONSTRAINT unique_minhas_disciplinas UNIQUE (usuario_id, disciplina_id)
);


-- public.pagina_permissoes definição

-- Drop table

-- DROP TABLE public.pagina_permissoes;

CREATE TABLE public.pagina_permissoes (
	id serial4 NOT NULL,
	nome_pagina varchar(255) NOT NULL,
	niveis_acesso_permitidos text NULL,
	CONSTRAINT pagina_permissoes_nome_pagina_key UNIQUE (nome_pagina),
	CONSTRAINT pagina_permissoes_pkey PRIMARY KEY (id)
);


-- public.patente definição

-- Drop table

-- DROP TABLE public.patente;

CREATE TABLE public.patente (
	id serial4 NOT NULL,
	sigla varchar(10) NOT NULL,
	nome varchar(100) NOT NULL,
	CONSTRAINT patente_pkey PRIMARY KEY (id),
	CONSTRAINT patente_sigla_key UNIQUE (sigla)
);


-- public.questionario definição

-- Drop table

-- DROP TABLE public.questionario;

CREATE TABLE public.questionario (
	id serial4 NOT NULL,
	pergunta text NOT NULL,
	descricao varchar(255) DEFAULT NULL::character varying NULL,
	categoria text NULL,
	CONSTRAINT questionario_pkey PRIMARY KEY (id)
);


-- public.questoesavaliacao definição

-- Drop table

-- DROP TABLE public.questoesavaliacao;

CREATE TABLE public.questoesavaliacao (
	id serial4 NOT NULL,
	questionario_id int4 NOT NULL,
	avaliacao_id int4 NOT NULL,
	CONSTRAINT questoesavaliacao_pkey PRIMARY KEY (id),
	CONSTRAINT unique_questoes_avaliacao UNIQUE (questionario_id, avaliacao_id)
);


-- public.respostas definição

-- Drop table

-- DROP TABLE public.respostas;

CREATE TABLE public.respostas (
	id serial4 NOT NULL,
	"data" timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
	curso_sigla varchar(50) NOT NULL,
	avaliacao_id int4 NOT NULL,
	pergunta text NOT NULL,
	resposta text NULL,
	categoria varchar(50) DEFAULT NULL::character varying NULL,
	avaliado varchar(255) DEFAULT NULL::character varying NULL,
	observacoes text NULL,
	cpf_aluno varchar(14) DEFAULT NULL::character varying NULL,
	nome_aluno varchar(255) DEFAULT NULL::character varying NULL,
	contato varchar(255) DEFAULT NULL::character varying NULL,
	CONSTRAINT respostas_pkey PRIMARY KEY (id)
);


-- public.titulacao definição

-- Drop table

-- DROP TABLE public.titulacao;

CREATE TABLE public.titulacao (
	id serial4 NOT NULL,
	sigla varchar(10) NOT NULL,
	nome varchar(100) NOT NULL,
	CONSTRAINT titulacao_pkey PRIMARY KEY (id),
	CONSTRAINT titulacao_sigla_key UNIQUE (sigla)
);


-- public.usuario definição

-- Drop table

-- DROP TABLE public.usuario;

CREATE TABLE public.usuario (
	id serial4 NOT NULL,
	nome varchar(255) NOT NULL,
	email varchar(255) DEFAULT NULL::character varying NULL,
	senha varchar(255) NOT NULL,
	nivel_acesso varchar DEFAULT Aluno NOT NULL,
	rg varchar(20) DEFAULT NULL::character varying NULL,
	cpf varchar(11) NOT NULL,
	patente text NULL,
	titulacao text NULL,
	instituicao text NULL,
	fonte_pagadora text NULL,
	nome_guerra varchar(100) DEFAULT NULL::character varying NULL,
	telefone varchar(20) DEFAULT NULL::character varying NULL,
	foto varchar(255) DEFAULT NULL::character varying NULL,
	data_criacao timestamp DEFAULT CURRENT_TIMESTAMP NULL,
	data_alteracao timestamp DEFAULT CURRENT_TIMESTAMP NULL,
	reset_token varchar(255) DEFAULT NULL::character varying NULL,
	reset_token_expires_at timestamp NULL,
	",-- phpMyAdmin SQL Dump" varchar(128) NULL
	"id,""nome"",""email"",""senha"",""nivel_acesso"",""rg"",""cpf"",""patente"",""" varchar(512) NULL,
	CONSTRAINT usuario_cpf_key UNIQUE (cpf),
	CONSTRAINT usuario_pkey PRIMARY KEY (id)
);
