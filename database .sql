
CREATE TABLE relatorios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    periodo VARCHAR(50) NOT NULL COMMENT 'Ex: Maio de 2025',
    supervisor VARCHAR(100) NOT NULL COMMENT 'Nome do supervisor de campo',
    regiao VARCHAR(100) NOT NULL COMMENT 'Região de cobertura',
    data_relatorio DATE NOT NULL COMMENT 'Data de criação do relatório',
    total_servicos INT(11) DEFAULT 0 COMMENT 'Contador automático de serviços',
    servicos_concluidos INT(11) DEFAULT 0 COMMENT 'Contador de serviços concluídos',
    servicos_andamento INT(11) DEFAULT 0 COMMENT 'Contador de serviços em andamento',
    observacoes_gerais TEXT COMMENT 'Observações gerais do período',
    status_relatorio ENUM('ativo', 'fechado', 'arquivado') DEFAULT 'ativo' COMMENT 'Status do relatório',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
    PRIMARY KEY (id),
    INDEX idx_periodo (periodo),
    INDEX idx_supervisor (supervisor),
    INDEX idx_data_relatorio (data_relatorio),
    INDEX idx_status (status_relatorio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela principal dos relatórios mensais';


CREATE TABLE tipos_servico (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL COMMENT 'Nome do tipo de serviço',
    descricao TEXT COMMENT 'Descrição detalhada do tipo',
    cor VARCHAR(7) DEFAULT '#3498db' COMMENT 'Cor hexadecimal para identificação',
    ativo BOOLEAN DEFAULT 1 COMMENT 'Se o tipo está ativo',
    ordem_exibicao INT(11) DEFAULT 0 COMMENT 'Ordem de exibição',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_nome (nome),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de serviços disponíveis';


CREATE TABLE servicos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    relatorio_id INT(11) NOT NULL COMMENT 'Referência ao relatório',
    titulo VARCHAR(200) NOT NULL COMMENT 'Título/descrição do serviço executado',
    status ENUM('concluido', 'andamento', 'pendente') DEFAULT 'pendente' COMMENT 'Status do serviço',
    equipe VARCHAR(50) DEFAULT NULL COMMENT 'Código/nome da equipe',
    tecnicos VARCHAR(200) DEFAULT NULL COMMENT 'Nomes dos técnicos responsáveis',
    data_execucao DATE DEFAULT NULL COMMENT 'Data de execução do serviço',
    local_servico VARCHAR(300) DEFAULT NULL COMMENT 'Endereço/local do serviço',
    

    tipo_reparo BOOLEAN DEFAULT 0 COMMENT 'Serviço de reparo',
    tipo_construcao BOOLEAN DEFAULT 0 COMMENT 'Construção de rede',
    tipo_ceo BOOLEAN DEFAULT 0 COMMENT 'Instalação de CEO',
    tipo_cto BOOLEAN DEFAULT 0 COMMENT 'Instalação de CTO',
    

    foto_1 VARCHAR(500) DEFAULT NULL COMMENT 'Primeira foto',
    foto_2 VARCHAR(500) DEFAULT NULL COMMENT 'Segunda foto',
    foto_3 VARCHAR(500) DEFAULT NULL COMMENT 'Terceira foto',
    foto_4 VARCHAR(500) DEFAULT NULL COMMENT 'Quarta foto',
    

    descricao_foto_1 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da primeira foto',
    descricao_foto_2 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da segunda foto',
    descricao_foto_3 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da terceira foto',
    descricao_foto_4 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da quarta foto',
    
    comentarios TEXT DEFAULT NULL COMMENT 'Observações e comentários técnicos',
    

    tempo_execucao INT(11) DEFAULT NULL COMMENT 'Tempo de execução em minutos',
    materiais_utilizados TEXT DEFAULT NULL COMMENT 'Lista de materiais utilizados',
    cliente_afetado VARCHAR(200) DEFAULT NULL COMMENT 'Cliente ou área afetada',
    numero_protocolo VARCHAR(50) DEFAULT NULL COMMENT 'Número de protocolo/chamado',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
    
    PRIMARY KEY (id),
    INDEX idx_relatorio_id (relatorio_id),
    INDEX idx_status (status),
    INDEX idx_data_execucao (data_execucao),
    INDEX idx_equipe (equipe),
    INDEX idx_tipos (tipo_reparo, tipo_construcao, tipo_ceo, tipo_cto),
    INDEX idx_protocolo (numero_protocolo),
    
    CONSTRAINT fk_servicos_relatorio 
        FOREIGN KEY (relatorio_id) 
        REFERENCES relatorios(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela dos serviços executados - versão 2.0';


CREATE TABLE fotos_servico (
    id INT(11) NOT NULL AUTO_INCREMENT,
    servico_id INT(11) NOT NULL COMMENT 'Referência ao serviço',
    arquivo VARCHAR(500) NOT NULL COMMENT 'Nome do arquivo da foto',
    descricao VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da foto',
    tipo_foto ENUM('antes', 'durante', 'depois', 'geral') DEFAULT 'geral' COMMENT 'Classificação da foto',
    ordem INT(11) DEFAULT 1 COMMENT 'Ordem de exibição (1-4)',
    tamanho_arquivo INT(11) DEFAULT NULL COMMENT 'Tamanho do arquivo em bytes',
    dimensoes VARCHAR(20) DEFAULT NULL COMMENT 'Dimensões da imagem (ex: 1920x1080)',
    upload_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data do upload',
    
    PRIMARY KEY (id),
    INDEX idx_servico_id (servico_id),
    INDEX idx_tipo_foto (tipo_foto),
    INDEX idx_ordem (ordem),
    
    CONSTRAINT fk_fotos_servico 
        FOREIGN KEY (servico_id) 
        REFERENCES servicos(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fotos dos serviços - organização melhorada';


CREATE TABLE equipes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL COMMENT 'Código da equipe (ex: Alfa-01)',
    nome VARCHAR(100) NOT NULL COMMENT 'Nome da equipe',
    supervisor_responsavel VARCHAR(100) DEFAULT NULL COMMENT 'Supervisor responsável',
    tecnicos_fixos TEXT DEFAULT NULL COMMENT 'Técnicos fixos da equipe (JSON)',
    especialidade ENUM('instalacao', 'manutencao', 'reparo', 'construcao', 'geral') DEFAULT 'geral',
    ativo BOOLEAN DEFAULT 1 COMMENT 'Se a equipe está ativa',
    regiao_atuacao VARCHAR(100) DEFAULT NULL COMMENT 'Região de atuação',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_codigo (codigo),
    INDEX idx_ativo (ativo),
    INDEX idx_especialidade (especialidade),
    INDEX idx_regiao (regiao_atuacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cadastro de equipes de campo';


CREATE TABLE tecnicos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL COMMENT 'Nome completo do técnico',
    codigo_funcionario VARCHAR(20) DEFAULT NULL COMMENT 'Código do funcionário',
    especialidades TEXT DEFAULT NULL COMMENT 'Especialidades do técnico (JSON)',
    telefone VARCHAR(20) DEFAULT NULL COMMENT 'Telefone de contato',
    email VARCHAR(100) DEFAULT NULL COMMENT 'Email do técnico',
    equipe_principal_id INT(11) DEFAULT NULL COMMENT 'Equipe principal',
    ativo BOOLEAN DEFAULT 1 COMMENT 'Se o técnico está ativo',
    data_admissao DATE DEFAULT NULL COMMENT 'Data de admissão',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    INDEX idx_nome (nome),
    INDEX idx_codigo (codigo_funcionario),
    INDEX idx_ativo (ativo),
    INDEX idx_equipe (equipe_principal_id),
    
    CONSTRAINT fk_tecnicos_equipe 
        FOREIGN KEY (equipe_principal_id) 
        REFERENCES equipes(id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cadastro de técnicos';


INSERT INTO tipos_servico (nome, descricao, cor, ordem_exibicao) VALUES
('Reparo', 'Serviços de reparo e manutenção corretiva', '#e74c3c', 1),
('Construção de Rede', 'Construção e expansão de infraestrutura', '#3498db', 2),
('Instalação de CEO', 'Instalação de Caixa de Emenda Óptica', '#f39c12', 3),
('Instalação de CTO', 'Instalação de Caixa Terminal Óptica', '#9b59b6', 4);

INSERT INTO equipes (codigo, nome, supervisor_responsavel, especialidade, regiao_atuacao) VALUES
('ALFA-01', 'Equipe Alfa 01', 'João Silva Santos', 'instalacao', 'Centro'),
('BETA-02', 'Equipe Beta 02', 'João Silva Santos', 'manutencao', 'Industrial'),
('GAMA-03', 'Equipe Gama 03', 'João Silva Santos', 'reparo', 'Residencial'),
('DELTA-04', 'Equipe Delta 04', 'João Silva Santos', 'construcao', 'Expansão');

INSERT INTO tecnicos (nome, codigo_funcionario, especialidades, equipe_principal_id) VALUES
('Carlos Lima', 'TEC001', '["instalacao", "reparo"]', 1),
('Pedro Santos', 'TEC002', '["instalacao", "ceo"]', 1),
('Ricardo Alves', 'TEC003', '["manutencao", "cto"]', 2),
('José Silva', 'TEC004', '["manutencao", "reparo"]', 2),
('Antonio Costa', 'TEC005', '["reparo", "construcao"]', 3),
('Marcos Reis', 'TEC006', '["reparo", "instalacao"]', 3),
('Fernando Oliveira', 'TEC007', '["construcao", "ceo"]', 4),
('Luiz Carlos', 'TEC008', '["construcao", "cto"]', 4);


INSERT INTO relatorios (periodo, supervisor, regiao, data_relatorio, observacoes_gerais) VALUES
('Junho de 2025', 'João Silva Santos', 'Lins - SP', '2025-06-11', 'Período com alta demanda de instalações residenciais e manutenções preventivas. Foco em expansão da rede para novos bairros.');

SET @relatorio_id = LAST_INSERT_ID();

INSERT INTO servicos (relatorio_id, titulo, status, equipe, tecnicos, data_execucao, local_servico, 
                     tipo_reparo, tipo_construcao, tipo_ceo, tipo_cto, 
                     comentarios, tempo_execucao, cliente_afetado, numero_protocolo) VALUES

(@relatorio_id, 'Instalação de Fibra Óptica - Residencial', 'concluido', 'ALFA-01', 'Carlos Lima, Pedro Santos', 
 '2025-06-01', 'Rua das Flores, 123 - Centro', 0, 0, 1, 0,
 'Instalação realizada conforme projeto. Cliente atendido no prazo estabelecido. Foram instalados 45 metros de cabo de fibra óptica, com emenda realizada no poste da rede principal. Teste de sinal executado com resultado dentro dos parâmetros técnicos.',
 120, 'João da Silva - CPF: 123.456.789-00', 'PROT-2025-001'),

(@relatorio_id, 'Manutenção Preventiva - Caixa de Emenda', 'concluido', 'BETA-02', 'Ricardo Alves, José Silva', 
 '2025-06-03', 'Av. Principal, Poste 45 - Industrial', 0, 0, 1, 0,
 'Manutenção preventiva realizada na CEO do poste 45. Encontrada infiltração de água na caixa, causando oxidação nos conectores. Realizada limpeza completa, substituição de conectores danificados e aplicação de nova vedação.',
 90, 'Área Industrial - Setor B', 'PROT-2025-002'),

(@relatorio_id, 'Reparo de Fibra Rompida', 'andamento', 'GAMA-03', 'Antonio Costa, Marcos Reis', 
 '2025-06-05', 'Rua São José, 567 - Vila Nova', 1, 0, 0, 0,
 'Cabo de fibra óptica rompido devido a trabalho de poda de árvore não autorizado. Foram afetados 12 clientes. Reparo iniciado no mesmo dia da ocorrência. Necessária substituição de 8 metros de cabo.',
 180, '12 clientes residenciais', 'PROT-2025-003'),

(@relatorio_id, 'Expansão de Rede - Novo Bairro', 'andamento', 'DELTA-04', 'Fernando Oliveira, Luiz Carlos', 
 '2025-06-07', 'Residencial Jardim das Palmeiras', 0, 1, 0, 1,
 'Início da expansão da rede de fibra óptica para atender novo loteamento. Instalação de 2 km de cabo backbone e 15 caixas de emenda. Projeto em fase de implantação com 60% de conclusão.',
 300, 'Novo loteamento - 200 lotes', 'PROT-2025-004'),

(@relatorio_id, 'Upgrade de Equipamento - Substituição CTO', 'concluido', 'ALFA-01', 'Carlos Lima, Pedro Santos, Ricardo Alves', 
 '2025-06-09', 'Central Técnica - Rua da Tecnologia, 100', 0, 0, 0, 1,
 'Substituição de CTO antiga por modelo mais moderno para aumentar capacidade de atendimento. Migração de 50 clientes para nova tecnologia. Processo realizado durante madrugada para minimizar impacto.',
 240, '50 clientes empresariais', 'PROT-2025-005');

DELIMITER $$
CREATE TRIGGER tr_servicos_insert_v2
AFTER INSERT ON servicos
FOR EACH ROW
BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'andamento'),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.relatorio_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER tr_servicos_update_v2
AFTER UPDATE ON servicos
FOR EACH ROW
BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'andamento'),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.relatorio_id;
END$$
DELIMITER ;


DELIMITER $$
CREATE TRIGGER tr_servicos_delete_v2
AFTER DELETE ON servicos
FOR EACH ROW
BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id AND status = 'andamento'),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.relatorio_id;
END$$
DELIMITER ;


CREATE VIEW vw_servicos_por_tipo AS
SELECT 
    r.id as relatorio_id,
    r.periodo,
    r.supervisor,
    SUM(s.tipo_reparo) as total_reparos,
    SUM(s.tipo_construcao) as total_construcao,
    SUM(s.tipo_ceo) as total_ceo,
    SUM(s.tipo_cto) as total_cto,
    COUNT(s.id) as total_servicos
FROM relatorios r
LEFT JOIN servicos s ON r.id = s.relatorio_id
GROUP BY r.id, r.periodo, r.supervisor;


CREATE VIEW vw_produtividade_equipes AS
SELECT 
    s.equipe,
    COUNT(s.id) as total_servicos,
    SUM(CASE WHEN s.status = 'concluido' THEN 1 ELSE 0 END) as servicos_concluidos,
    AVG(s.tempo_execucao) as tempo_medio_execucao,
    COUNT(DISTINCT s.relatorio_id) as relatorios_participou
FROM servicos s
WHERE s.equipe IS NOT NULL
GROUP BY s.equipe;


ALTER TABLE servicos ADD INDEX idx_relatorio_status (relatorio_id, status);
ALTER TABLE servicos ADD INDEX idx_data_equipe (data_execucao, equipe);
ALTER TABLE servicos ADD INDEX idx_tipos_servico (tipo_reparo, tipo_construcao, tipo_ceo, tipo_cto);


ALTER TABLE servicos ADD FULLTEXT INDEX ft_busca_servicos (titulo, comentarios, local_servico);


UPDATE relatorios r SET 
    total_servicos = (SELECT COUNT(*) FROM servicos s WHERE s.relatorio_id = r.id),
    servicos_concluidos = (SELECT COUNT(*) FROM servicos s WHERE s.relatorio_id = r.id AND s.status = 'concluido'),
    servicos_andamento = (SELECT COUNT(*) FROM servicos s WHERE s.relatorio_id = r.id AND s.status = 'andamento');

SHOW TABLES;


SELECT 'Relatórios criados:' as info, COUNT(*) as quantidade FROM relatorios
UNION ALL
SELECT 'Serviços cadastrados:', COUNT(*) FROM servicos
UNION ALL
SELECT 'Equipes ativas:', COUNT(*) FROM equipes WHERE ativo = 1
UNION ALL
SELECT 'Técnicos ativos:', COUNT(*) FROM tecnicos WHERE ativo = 1
UNION ALL
SELECT 'Tipos de serviço:', COUNT(*) FROM tipos_servico WHERE ativo = 1;
