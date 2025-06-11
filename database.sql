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
    equipe VARCHAR(50) DEFAULT NULL COMMENT 'Código/nome da equipe',
    tecnicos VARCHAR(200) DEFAULT NULL COMMENT 'Nomes dos técnicos responsáveis',
    data_execucao DATE DEFAULT NULL COMMENT 'Data de execução do serviço',
    local_servico VARCHAR(300) DEFAULT NULL COMMENT 'Endereço/local do serviço',
    foto_antes VARCHAR(500) DEFAULT NULL COMMENT 'Nome do arquivo da foto antes',
    foto_depois VARCHAR(500) DEFAULT NULL COMMENT 'Nome do arquivo da foto depois',
    comentarios TEXT DEFAULT NULL COMMENT 'Observações e comentários técnicos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
    PRIMARY KEY (id),
    INDEX idx_relatorio_id (relatorio_id),
    INDEX idx_status (status),
    INDEX idx_data_execucao (data_execucao),
    INDEX idx_equipe (equipe),
    CONSTRAINT fk_servicos_relatorio 
        FOREIGN KEY (relatorio_id) 
        REFERENCES relatorios(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela dos serviços executados';


INSERT INTO relatorios (periodo, supervisor, regiao, data_relatorio, observacoes_gerais) VALUES
('Junho de 2025', 'João Silva Santos', 'Lins - SP', '2025-06-11', 'Período com alta demanda de instalações residenciais e manutenções preventivas.');

-- Obter o ID do relatório inserido
SET @relatorio_id = LAST_INSERT_ID();

-- Inserir serviços de exemplo
INSERT INTO servicos (relatorio_id, titulo, status, equipe, tecnicos, data_execucao, local_servico, comentarios) VALUES
(@relatorio_id, 'Instalação de Fibra Óptica - Residencial', 'concluido', 'Alfa-01', 'Carlos Lima, Pedro Santos', '2025-06-01', 'Rua das Flores, 123 - Centro', 'Instalação realizada conforme projeto. Cliente atendido no prazo estabelecido. Foram instalados 45 metros de cabo de fibra óptica, com emenda realizada no poste da rede principal. Teste de sinal executado com resultado dentro dos parâmetros técnicos.'),

(@relatorio_id, 'Manutenção Preventiva - Caixa de Emenda', 'concluido', 'Beta-02', 'Ricardo Alves, José Silva', '2025-06-03', 'Av. Principal, Poste 45 - Industrial', 'Manutenção preventiva realizada na CEO (Caixa de Emenda Óptica) do poste 45. Encontrada infiltração de água na caixa, causando oxidação nos conectores. Realizada limpeza completa, substituição de conectores danificados e aplicação de nova vedação.'),

(@relatorio_id, 'Reparo de Fibra Rompida', 'andamento', 'Gama-03', 'Antonio Costa, Marcos Reis', '2025-06-05', 'Rua São José, 567 - Vila Nova', 'Cabo de fibra óptica rompido devido a trabalho de poda de árvore não autorizado. Foram afetados 12 clientes. Reparo iniciado no mesmo dia da ocorrência. Necessária substituição de 8 metros de cabo. Previsão de conclusão: 12/06/2025.'),

(@relatorio_id, 'Expansão de Rede - Novo Bairro', 'andamento', 'Delta-04', 'Fernando Oliveira, Luiz Carlos', '2025-06-07', 'Residencial Jardim das Palmeiras', 'Início da expansão da rede de fibra óptica para atender novo loteamento. Instalação de 2 km de cabo backbone e 15 caixas de emenda. Projeto em fase de implantação com 60% de conclusão.'),

(@relatorio_id, 'Upgrade de Equipamento - OLT', 'concluido', 'Alfa-01', 'Carlos Lima, Pedro Santos, Ricardo Alves', '2025-06-09', 'Central Técnica - Rua da Tecnologia, 100', 'Upgrade realizado no equipamento OLT para aumentar capacidade de atendimento. Migração de 200 clientes para nova tecnologia GPON. Processo realizado durante madrugada para minimizar impacto nos clientes.');

-- Atualizar contadores do relatório
UPDATE relatorios SET 
    total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = @relatorio_id),
    servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = @relatorio_id AND status = 'concluido'),
    servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = @relatorio_id AND status = 'andamento')
WHERE id = @relatorio_id;

DELIMITER $$
CREATE TRIGGER tr_servicos_insert
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

-- Trigger para UPDATE
DELIMITER $$
CREATE TRIGGER tr_servicos_update
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

-- Trigger para DELETE
DELIMITER $$
CREATE TRIGGER tr_servicos_delete
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


SHOW TABLES;
DESCRIBE relatorios;
DESCRIBE servicos;

-- Verificar dados inseridos
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
    s.data_execucao
FROM servicos s;

