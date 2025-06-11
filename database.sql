
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
    PRIMARY KEY (id),
    INDEX idx_periodo (periodo),
    INDEX idx_supervisor (supervisor),
    INDEX idx_data_relatorio (data_relatorio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela principal dos relatórios mensais';


CREATE TABLE servicos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    relatorio_id INT(11) NOT NULL COMMENT 'Referência ao relatório',
    titulo VARCHAR(200) NOT NULL COMMENT 'Título/tipo do serviço executado',
    status ENUM('concluido', 'andamento', 'pendente') DEFAULT 'pendente' COMMENT 'Status do serviço',
    

    tipo_reparo TINYINT(1) DEFAULT 0 COMMENT 'Serviço de reparo marcado',
    tipo_construcao TINYINT(1) DEFAULT 0 COMMENT 'Construção de rede marcado',
    tipo_ceo TINYINT(1) DEFAULT 0 COMMENT 'Instalação de CEO marcado',
    tipo_cto TINYINT(1) DEFAULT 0 COMMENT 'Instalação de CTO marcado',
    
   
    equipe VARCHAR(50) DEFAULT NULL COMMENT 'Código/nome da equipe',
    tecnicos VARCHAR(200) DEFAULT NULL COMMENT 'Nomes dos técnicos responsáveis',
    data_execucao DATE DEFAULT NULL COMMENT 'Data de execução do serviço',
    local_servico VARCHAR(300) DEFAULT NULL COMMENT 'Endereço/local do serviço',
  
    foto_1 VARCHAR(500) DEFAULT NULL COMMENT 'Primeira foto do serviço',
    foto_2 VARCHAR(500) DEFAULT NULL COMMENT 'Segunda foto do serviço',
    foto_3 VARCHAR(500) DEFAULT NULL COMMENT 'Terceira foto do serviço',
    foto_4 VARCHAR(500) DEFAULT NULL COMMENT 'Quarta foto do serviço',
   
    descricao_foto_1 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da primeira foto',
    descricao_foto_2 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da segunda foto',
    descricao_foto_3 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da terceira foto',
    descricao_foto_4 VARCHAR(200) DEFAULT NULL COMMENT 'Descrição da quarta foto',
    
 
    comentarios TEXT DEFAULT NULL COMMENT 'Observações e comentários técnicos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
    
    PRIMARY KEY (id),
    INDEX idx_relatorio_id (relatorio_id),
    INDEX idx_status (status),
    INDEX idx_data_execucao (data_execucao),
    INDEX idx_equipe (equipe),
    INDEX idx_tipos (tipo_reparo, tipo_construcao, tipo_ceo, tipo_cto),
    CONSTRAINT fk_servicos_relatorio 
        FOREIGN KEY (relatorio_id) 
        REFERENCES relatorios(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela dos serviços executados com novos campos';


DELIMITER $$
CREATE TRIGGER tr_servicos_insert_v2
AFTER INSERT ON servicos
FOR EACH ROW
BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'andamento')
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
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'andamento')
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
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id AND status = 'andamento')
    WHERE id = OLD.relatorio_id;
END$$
DELIMITER ;


INSERT INTO relatorios (periodo, supervisor, regiao, data_relatorio, observacoes_gerais) VALUES
('Junho de 2025', 'João Silva Santos', 'Lins - SP', '2025-06-11', 'Período com alta demanda de instalações e manutenções. Foco em melhorias da rede de fibra óptica.');


SET @relatorio_id = LAST_INSERT_ID();

INSERT INTO servicos (
    relatorio_id, titulo, status, tipo_reparo, tipo_construcao, tipo_ceo, tipo_cto,
    equipe, tecnicos, data_execucao, local_servico, 
    descricao_foto_1, descricao_foto_2, descricao_foto_3, descricao_foto_4,
    comentarios
) VALUES 
(
    @relatorio_id, 
    'Instalação Completa de CEO e CTO - Bairro Centro', 
    'concluido', 
    0, 1, 1, 1, -- Construção + CEO + CTO
    'Alfa-01', 
    'Carlos Lima, Pedro Santos', 
    '2025-06-01', 
    'Rua das Flores, 123 - Centro',
    'CEO instalada no poste principal', 
    'Passagem de cabos subterrâneos', 
    'CTO instalada na calçada', 
    'Teste de sinal finalizado',
    'Instalação completa realizada em 6 horas. CEO de 144 fibras instalada com sucesso. CTO de 8 portas funcionando perfeitamente. Todos os testes de sinal dentro dos parâmetros técnicos especificados.'
),
(
    @relatorio_id, 
    'Reparo Emergencial - Fibra Rompida', 
    'concluido', 
    1, 0, 0, 0, -- Apenas reparo
    'Beta-02', 
    'Ricardo Alves, José Silva', 
    '2025-06-03', 
    'Av. Principal, próximo ao poste 45',
    'Cabo rompido identificado', 
    'Emenda realizada com fusão', 
    'Teste de perda óptica', 
    'Cabo reinstalado e protegido',
    'Cabo de fibra rompido devido a acidente com veículo. Reparo realizado com emenda por fusão. Perda óptica medida: 0.15dB (dentro do aceitável). 15 clientes afetados, todos reestabelecidos em 4 horas.'
),
(
    @relatorio_id, 
    'Expansão de Rede - Residencial Jardim das Palmeiras', 
    'andamento', 
    0, 1, 1, 0, -- Construção + CEO
    'Gama-03', 
    'Antonio Costa, Marcos Reis, Fernando Oliveira', 
    '2025-06-05', 
    'Residencial Jardim das Palmeiras - Quadra A',
    'Início da obra de expansão', 
    'Instalação de dutos subterrâneos', 
    'Montagem da estrutura CEO', 
    NULL,
    'Projeto de expansão em andamento. 1,2km de cabos backbone instalados. 8 CEOs planejadas, 3 já instaladas. Previsão de conclusão: 20/06/2025. Obra sem intercorrências até o momento.'
);


UPDATE relatorios SET 
    total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = @relatorio_id),
    servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = @relatorio_id AND status = 'concluido'),
    servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = @relatorio_id AND status = 'andamento')
WHERE id = @relatorio_id;


SHOW TABLES;
DESCRIBE relatorios;
DESCRIBE servicos;


SELECT 
    r.id,
    r.periodo,
    r.supervisor,
    r.total_servicos,
    r.servicos_concluidos,
    r.servicos_andamento
FROM relatorios r;


SELECT 
    s.id,
    s.titulo,
    s.status,
    s.equipe,
    s.data_execucao,
    CASE 
        WHEN s.tipo_reparo = 1 THEN 'Reparo '
        ELSE ''
    END +
    CASE 
        WHEN s.tipo_construcao = 1 THEN 'Construção '
        ELSE ''
    END +
    CASE 
        WHEN s.tipo_ceo = 1 THEN 'CEO '
        ELSE ''
    END +
    CASE 
        WHEN s.tipo_cto = 1 THEN 'CTO '
        ELSE ''
    END AS tipos_servico
FROM servicos s
ORDER BY s.created_at DESC;


SELECT 
    'Reparos' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos
FROM servicos WHERE tipo_reparo = 1

UNION ALL

SELECT 
    'Construção de Rede' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos
FROM servicos WHERE tipo_construcao = 1

UNION ALL

SELECT 
    'Instalação CEO' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos
FROM servicos WHERE tipo_ceo = 1

UNION ALL

SELECT 
    'Instalação CTO' as tipo,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos
FROM servicos WHERE tipo_cto = 1;