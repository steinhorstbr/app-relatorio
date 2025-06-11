<?php
/**
 * ================================================
 * ARQUIVO: index.php (VERSÃO 2)
 * DESCRIÇÃO: Página principal atualizada
 * ================================================
 */

// Incluir configurações
require_once 'config.php';

// Inicializar variáveis
$success = '';
$error = '';

// ================================================
// PROCESSAR FORMULÁRIOS
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                // ================================
                // CRIAR RELATÓRIO
                // ================================
                case 'criar_relatorio':
                    $periodo = sanitizeString($_POST['periodo']);
                    $supervisor = sanitizeString($_POST['supervisor']);
                    $regiao = sanitizeString($_POST['regiao']);
                    $data_relatorio = $_POST['data_relatorio'];
                    $observacoes_gerais = sanitizeString($_POST['observacoes_gerais'] ?? '');
                    
                    // Validações
                    if (empty($periodo) || empty($supervisor) || empty($regiao) || empty($data_relatorio)) {
                        throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
                    }
                    
                    // Inserir no banco
                    $stmt = $pdo->prepare("INSERT INTO relatorios (periodo, supervisor, regiao, data_relatorio, observacoes_gerais) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$periodo, $supervisor, $regiao, $data_relatorio, $observacoes_gerais]);
                    
                    $relatorio_id = $pdo->lastInsertId();
                    $success = "Relatório criado com sucesso! ID: $relatorio_id";
                    
                    // Log da atividade
                    logActivity('CRIAR_RELATORIO', "Relatório $relatorio_id criado para período: $periodo");
                    break;
                
                // ================================
                // ADICIONAR SERVIÇO
                // ================================
                case 'adicionar_servico':
                    $relatorio_id = (int)$_POST['relatorio_id'];
                    $titulo = sanitizeString($_POST['titulo']);
                    $status = $_POST['status'];
                    
                    // Tipos de serviço (checkboxes)
                    $tipo_reparo = isset($_POST['tipo_reparo']) ? 1 : 0;
                    $tipo_construcao = isset($_POST['tipo_construcao']) ? 1 : 0;
                    $tipo_ceo = isset($_POST['tipo_ceo']) ? 1 : 0;
                    $tipo_cto = isset($_POST['tipo_cto']) ? 1 : 0;
                    
                    $equipe = sanitizeString($_POST['equipe']);
                    $tecnicos = sanitizeString($_POST['tecnicos']);
                    $data_execucao = $_POST['data_execucao'];
                    $local_servico = sanitizeString($_POST['local_servico']);
                    $comentarios = sanitizeString($_POST['comentarios']);
                    
                    // Descrições das fotos
                    $descricao_foto_1 = sanitizeString($_POST['descricao_foto_1'] ?? '');
                    $descricao_foto_2 = sanitizeString($_POST['descricao_foto_2'] ?? '');
                    $descricao_foto_3 = sanitizeString($_POST['descricao_foto_3'] ?? '');
                    $descricao_foto_4 = sanitizeString($_POST['descricao_foto_4'] ?? '');
                    
                    // Validações
                    if (empty($relatorio_id) || empty($titulo) || empty($status) || empty($equipe) || empty($tecnicos) || empty($data_execucao) || empty($local_servico) || empty($comentarios)) {
                        throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
                    }
                    
                    // Verificar se pelo menos um tipo foi selecionado
                    if (!$tipo_reparo && !$tipo_construcao && !$tipo_ceo && !$tipo_cto) {
                        throw new Exception('Selecione pelo menos um tipo de serviço.');
                    }
                    
                    // Verificar se relatório existe
                    $stmt = $pdo->prepare("SELECT id FROM relatorios WHERE id = ?");
                    $stmt->execute([$relatorio_id]);
                    if (!$stmt->fetch()) {
                        throw new Exception('Relatório não encontrado.');
                    }
                    
                    // Upload das 4 fotos
                    $fotos = ['', '', '', ''];
                    $foto_fields = ['foto_1', 'foto_2', 'foto_3', 'foto_4'];
                    
                    for ($i = 0; $i < 4; $i++) {
                        $field_name = $foto_fields[$i];
                        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
                            $result = uploadFile($_FILES[$field_name], $config);
                            if ($result['success']) {
                                $fotos[$i] = $result['filename'];
                            } else {
                                // Se houve erro, deletar fotos já enviadas
                                for ($j = 0; $j < $i; $j++) {
                                    if ($fotos[$j]) {
                                        deleteFile($fotos[$j], $config);
                                    }
                                }
                                throw new Exception("Erro no upload da " . ($i+1) . "ª foto: " . $result['error']);
                            }
                        }
                    }
                    
                    // Inserir serviço
                    $stmt = $pdo->prepare("
                        INSERT INTO servicos (
                            relatorio_id, titulo, status, 
                            tipo_reparo, tipo_construcao, tipo_ceo, tipo_cto,
                            equipe, tecnicos, data_execucao, local_servico, 
                            foto_1, foto_2, foto_3, foto_4,
                            descricao_foto_1, descricao_foto_2, descricao_foto_3, descricao_foto_4,
                            comentarios
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $relatorio_id, $titulo, $status,
                        $tipo_reparo, $tipo_construcao, $tipo_ceo, $tipo_cto,
                        $equipe, $tecnicos, $data_execucao, $local_servico,
                        $fotos[0], $fotos[1], $fotos[2], $fotos[3],
                        $descricao_foto_1, $descricao_foto_2, $descricao_foto_3, $descricao_foto_4,
                        $comentarios
                    ]);
                    
                    $servico_id = $pdo->lastInsertId();
                    $success = "Serviço adicionado com sucesso! ID: $servico_id";
                    
                    // Log da atividade
                    $tipos_selecionados = [];
                    if ($tipo_reparo) $tipos_selecionados[] = 'Reparo';
                    if ($tipo_construcao) $tipos_selecionados[] = 'Construção';
                    if ($tipo_ceo) $tipos_selecionados[] = 'CEO';
                    if ($tipo_cto) $tipos_selecionados[] = 'CTO';
                    
                    logActivity('ADICIONAR_SERVICO', "Serviço $servico_id adicionado - Tipos: " . implode(', ', $tipos_selecionados));
                    break;
                
                default:
                    throw new Exception('Ação não reconhecida.');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        logActivity('ERRO', $error);
    }
}

// ================================================
// BUSCAR DADOS PARA EXIBIÇÃO
// ================================================
try {
    // Buscar relatórios ordenados por data de criação (apenas os 5 mais recentes para o dropdown)
    $stmt = $pdo->prepare("SELECT * FROM relatorios ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $relatorios = $stmt->fetchAll();
    
    // Contar total de relatórios
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM relatorios");
    $stmt->execute();
    $total_relatorios = $stmt->fetch()['total'];
    
    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_servicos,
            SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos,
            SUM(CASE WHEN status = 'andamento' THEN 1 ELSE 0 END) as andamento,
            SUM(tipo_reparo) as reparos,
            SUM(tipo_construcao) as construcoes,
            SUM(tipo_ceo) as ceos,
            SUM(tipo_cto) as ctos
        FROM servicos
    ");
    $stmt->execute();
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
    $relatorios = [];
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['site_name'] ?> - V2</title>
    <meta name="description" content="Sistema para gerenciar relatórios mensais de serviços de fibra óptica">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.1) 10px,
                rgba(255, 255, 255, 0.1) 20px
            );
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            position: relative;
            z-index: 1;
            opacity: 0.9;
        }

        /* Estatísticas no cabeçalho */
        .header-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 10px;
            border-radius: 10px;
            text-align: center;
            backdrop-filter: blur(5px);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        .content {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        /* Checkboxes para tipos de serviço */
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .checkbox-item:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #3498db;
            cursor: pointer;
        }

        .checkbox-item.checked {
            border-color: #3498db;
            background: #f0f8ff;
        }

        .checkbox-item label {
            cursor: pointer;
            font-weight: normal;
            margin: 0;
            font-size: 0.95rem;
        }

        /* Área de fotos redesenhada */
        .photos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }

        .photo-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }

        .photo-upload:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }

        .photo-upload.has-file {
            border-color: #27ae60;
            background: #d4edda;
            color: #155724;
        }

        .photo-upload input[type="file"] {
            display: none;
        }

        .photo-upload-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }

        /* Dropdown para relatórios */
        .dropdown-container {
            position: relative;
            margin-bottom: 30px;
        }

        .dropdown-toggle {
            width: 100%;
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .dropdown-content {
            display: none;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            max-height: 400px;
            overflow-y: auto;
        }

        .dropdown-content.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .relatorio-item {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }

        .relatorio-item:hover {
            background: #f8f9fa;
        }

        .relatorio-item:last-child {
            border-bottom: none;
        }

        .relatorio-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .relatorio-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .relatorio-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .relatorio-actions .btn {
            font-size: 0.85rem;
            padding: 8px 15px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        /* Link para página de relatórios */
        .page-link {
            display: inline-block;
            background: linear-gradient(135deg, #a29bfe, #6c5ce7);
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
            }
            
            .photos-grid {
                grid-template-columns: 1fr;
            }
            
            .checkbox-group {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .section {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .header-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📡 <?= $config['site_name'] ?> v2.0</h1>
            <p>Sistema avançado para gerenciar relatórios de serviços de fibra óptica</p>
            
            <?php if (!empty($stats)): ?>
            <div class="header-stats">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_servicos'] ?? 0 ?></span>
                    <div class="stat-label">Total Serviços</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['concluidos'] ?? 0 ?></span>
                    <div class="stat-label">Concluídos</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['reparos'] ?? 0 ?></span>
                    <div class="stat-label">Reparos</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['construcoes'] ?? 0 ?></span>
                    <div class="stat-label">Construções</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['ceos'] ?? 0 ?></span>
                    <div class="stat-label">CEOs</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['ctos'] ?? 0 ?></span>
                    <div class="stat-label">CTOs</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="content">
            <!-- Alertas -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulário para criar relatório -->
<div class="section">
    <div class="dropdown-container">
        <button type="button" class="dropdown-toggle" onclick="toggleCreateReportDropdown()">
            📋 Criar Novo Relatório
            <span id="create-report-arrow">▼</span>
        </button>
        <div class="dropdown-content" id="create-report-content">
            <form method="POST" id="form-relatorio">
                    <input type="hidden" name="action" value="criar_relatorio">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="periodo">Período *</label>
                            <input type="text" id="periodo" name="periodo" placeholder="Ex: Junho de 2025" required>
                        </div>
                        <div class="form-group">
                            <label for="supervisor">Supervisor *</label>
                            <input type="text" id="supervisor" name="supervisor" placeholder="Nome do supervisor de campo" required>
                        </div>
                    </div>

                    <div class="form-row">
<div class="form-group">
    <label for="regiao">Região *</label>
    <select id="regiao" name="regiao" required>
        <option value="">Selecione a região...</option>
        <option value="Alcinópolis - MS">📍 Alcinópolis - MS</option>
        <option value="Costa Rica - MS">📍 Costa Rica - MS</option>
        <option value="Figueirão - MS">📍 Figueirão - MS</option>
        <option value="Backbone">🌐 Backbone</option>
        <option value="Externos">🔧 Externos</option>
    </select>
</div>
                        <div class="form-group">
                            <label for="data_relatorio">Data do Relatório *</label>
                            <input type="date" id="data_relatorio" name="data_relatorio" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="observacoes_gerais">Observações Gerais</label>
                        <textarea id="observacoes_gerais" name="observacoes_gerais" rows="3" placeholder="Observações gerais sobre o período..."></textarea>
                    </div>

<button type="submit" class="btn">📋 Criar Relatório</button>
            </form>
        </div>
    </div>
</div>

            <!-- Formulário para adicionar serviço -->
            <?php if (!empty($relatorios)): ?>
            <div class="section">
                <h2>🔧 Adicionar Serviço</h2>
                <form method="POST" enctype="multipart/form-data" id="form-servico">
                    <input type="hidden" name="action" value="adicionar_servico">
                    
                    <div class="form-group">
                        <label for="relatorio_id">Selecionar Relatório *</label>
                        <select id="relatorio_id" name="relatorio_id" required>
                            <option value="">Escolha um relatório...</option>
                            <?php foreach ($relatorios as $rel): ?>
                                <option value="<?= $rel['id'] ?>">
                                    <?= htmlspecialchars($rel['periodo']) ?> - <?= htmlspecialchars($rel['regiao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titulo">Título do Serviço *</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ex: Instalação de CEO - Rua das Flores" required>
                    </div>

                    <!-- CHECKBOXES para tipos de serviço -->
                    <div class="form-group">
                        <label>Tipos de Serviço * (selecione pelo menos um)</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="tipo_reparo" name="tipo_reparo" value="1">
                                <label for="tipo_reparo">🔧 Reparo</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="tipo_construcao" name="tipo_construcao" value="1">
                                <label for="tipo_construcao">🏗️ Construção de Rede</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="tipo_ceo" name="tipo_ceo" value="1">
                                <label for="tipo_ceo">📡 Instalação de CEO</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="tipo_cto" name="tipo_cto" value="1">
                                <label for="tipo_cto">📦 Instalação de CTO</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="">Selecione o status...</option>
                                <option value="concluido">✅ Concluído</option>
                                <option value="andamento">⏳ Em Andamento</option>
                                <option value="pendente">⏸️ Pendente</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="equipe">Equipe *</label>
                            <select id="equipe" name="equipe" required>
                                <option value="">Selecione a equipe...</option>
                                <option value="Infraestrutura">🏗️ Infraestrutura</option>
                                <option value="Equipe FTTH">📡 Equipe FTTH</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="data_execucao">Data de Execução *</label>
                            <input type="date" id="data_execucao" name="data_execucao" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tecnicos">Técnicos *</label>
                            <input type="text" id="tecnicos" name="tecnicos" placeholder="Ex: Carlos Lima, Pedro Santos" required>
                        </div>
                        <div class="form-group">
                            <label for="local_servico">Local do Serviço *</label>
                            <input type="text" id="local_servico" name="local_servico" placeholder="Ex: Rua das Flores, 123 - Centro" required>
                        </div>
                    </div>

                    <!-- 4 FOTOS com descrições -->
                    <div class="form-group">
                        <label>📷 Fotos do Serviço (opcional - até 4 fotos)</label>
                        <div class="photos-grid">
                            <div>
                                <div class="photo-upload" data-photo="1">
                                    <input type="file" id="foto_1" name="foto_1" accept="image/*">
                                    <span class="photo-upload-icon">📷</span>
                                    <p>Clique para selecionar a 1ª foto</p>
                                </div>
                                <input type="text" name="descricao_foto_1" placeholder="Descrição da 1ª foto (opcional)" style="margin-top: 10px;">
                            </div>
                            
                            <div>
                                <div class="photo-upload" data-photo="2">
                                    <input type="file" id="foto_2" name="foto_2" accept="image/*">
                                    <span class="photo-upload-icon">📷</span>
                                    <p>Clique para selecionar a 2ª foto</p>
                                </div>
                                <input type="text" name="descricao_foto_2" placeholder="Descrição da 2ª foto (opcional)" style="margin-top: 10px;">
                            </div>
                            
                            <div>
                                <div class="photo-upload" data-photo="3">
                                    <input type="file" id="foto_3" name="foto_3" accept="image/*">
                                    <span class="photo-upload-icon">📷</span>
                                    <p>Clique para selecionar a 3ª foto</p>
                                </div>
                                <input type="text" name="descricao_foto_3" placeholder="Descrição da 3ª foto (opcional)" style="margin-top: 10px;">
                            </div>
                            
                            <div>
                                <div class="photo-upload" data-photo="4">
                                    <input type="file" id="foto_4" name="foto_4" accept="image/*">
                                    <span class="photo-upload-icon">📷</span>
                                    <p>Clique para selecionar a 4ª foto</p>
                                </div>
                                <input type="text" name="descricao_foto_4" placeholder="Descrição da 4ª foto (opcional)" style="margin-top: 10px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comentarios">Comentários e Observações *</label>
                        <textarea id="comentarios" name="comentarios" rows="4" placeholder="Descreva detalhes do serviço executado, observações técnicas, materiais utilizados, etc..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">🔧 Adicionar Serviço</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Dropdown com relatórios criados -->
            <?php if (!empty($relatorios)): ?>
            <div class="section">
                <div class="dropdown-container">
                    <button type="button" class="dropdown-toggle" onclick="toggleDropdown()">
                        📊 Relatórios Criados (<?= $total_relatorios ?>) 
                        <span id="dropdown-arrow">▼</span>
                    </button>
                    <div class="dropdown-content" id="dropdown-content">
                        <?php foreach ($relatorios as $relatorio): ?>
                        <div class="relatorio-item">
                            <div class="relatorio-title">
                                📋 <?= htmlspecialchars($relatorio['periodo']) ?>
                            </div>
                            <div class="relatorio-info">
                                <span><strong>Supervisor:</strong> <?= htmlspecialchars($relatorio['supervisor']) ?></span>
                                <span><strong>Região:</strong> <?= htmlspecialchars($relatorio['regiao']) ?></span>
                                <span><strong>Data:</strong> <?= formatDate($relatorio['data_relatorio']) ?></span>
                                <span><strong>Serviços:</strong> <?= $relatorio['total_servicos'] ?> total</span>
                            </div>
                            <div class="relatorio-actions">
                                <a href="visualizar.php?id=<?= $relatorio['id'] ?>" class="btn" title="Visualizar relatório">
                                    👁️ Visualizar
                                </a>
                                <a href="gerar_pdf_simples.php?id=<?= $relatorio['id'] ?>" class="btn btn-success" title="Baixar PDF" target="_blank">
                                    📄 PDF
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($total_relatorios > 10): ?>
                        <div class="relatorio-item" style="text-align: center; background: #f0f8ff;">
                            <a href="relatorios.php" class="page-link">
                                📋 Ver Todos os Relatórios (<?= $total_relatorios ?>)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="section">
                <div class="empty-state">
                    <h3>📝 Nenhum relatório criado ainda</h3>
                    <p>Comece criando seu primeiro relatório mensal acima.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Loading indicator -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Processando...</p>
            </div>
        </div>
    </div>

<script>
        // ================================================
        // JAVASCRIPT PARA FUNCIONALIDADES
        // ================================================

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-preencher datas atuais
            const today = new Date();
            const todayString = today.toISOString().split('T')[0];
            
            const dataRelatorio = document.getElementById('data_relatorio');
            const dataExecucao = document.getElementById('data_execucao');
            
            if (dataRelatorio && !dataRelatorio.value) {
                dataRelatorio.value = todayString;
            }
            if (dataExecucao && !dataExecucao.value) {
                dataExecucao.value = todayString;
            }

            // Setup das funcionalidades
            setupCheckboxes();
            setupFileUploads();
            setupFormValidation();
            setupLoadingStates();
        });

        // Gerenciar checkboxes de tipos de serviço
        function setupCheckboxes() {
            const checkboxItems = document.querySelectorAll('.checkbox-item');
            
            checkboxItems.forEach(item => {
                const checkbox = item.querySelector('input[type="checkbox"]');
                
                item.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'INPUT') {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
                
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        item.classList.add('checked');
                    } else {
                        item.classList.remove('checked');
                    }
                });
            });
        }

        // Gerenciar uploads de fotos
        function setupFileUploads() {
            for (let i = 1; i <= 4; i++) {
                const input = document.getElementById(`foto_${i}`);
                const uploadDiv = document.querySelector(`[data-photo="${i}"]`);
                
                if (input && uploadDiv) {
                    uploadDiv.addEventListener('click', () => input.click());
                    
                    input.addEventListener('change', function() {
                        const fileName = this.files[0] ? this.files[0].name : '';
                        const p = uploadDiv.querySelector('p');
                        
                        if (fileName) {
                            uploadDiv.classList.add('has-file');
                            p.innerHTML = `✅ ${fileName}`;
                        } else {
                            uploadDiv.classList.remove('has-file');
                            p.innerHTML = `Clique para selecionar a ${i}ª foto`;
                        }
                    });
                }
            }
        }

        // Validação de formulários
        function setupFormValidation() {
            // Validar se pelo menos um tipo foi selecionado
            const formServico = document.getElementById('form-servico');
            if (formServico) {
                formServico.addEventListener('submit', function(e) {
                    const checkboxes = document.querySelectorAll('input[name^="tipo_"]:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('⚠️ Selecione pelo menos um tipo de serviço!');
                        return false;
                    }
                });
            }

            // Validação em tempo real
            document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
                field.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.style.borderColor = '#e74c3c';
                    } else {
                        this.style.borderColor = '#27ae60';
                    }
                });

                field.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '#27ae60';
                    }
                });
            });
        }

        // Estados de loading
        function setupLoadingStates() {
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const loading = document.getElementById('loading');
                    
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '⏳ Processando...';
                    }
                    
                    if (loading) {
                        loading.style.display = 'block';
                    }
                });
            });
        }

        // Toggle dropdown de relatórios criados
        function toggleDropdown() {
            const content = document.getElementById('dropdown-content');
            const arrow = document.getElementById('dropdown-arrow');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                arrow.textContent = '▼';
            } else {
                content.classList.add('show');
                arrow.textContent = '▲';
            }
        }

        // Toggle dropdown de criar relatório
        function toggleCreateReportDropdown() {
            const content = document.getElementById('create-report-content');
            const arrow = document.getElementById('create-report-arrow');

            if (content.classList.contains('show')) {
                content.classList.remove('show');
                arrow.textContent = '▼';
            } else {
                content.classList.add('show');
                arrow.textContent = '▲';
            }
        }

        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', function(e) {
            // Dropdown de relatórios criados
            const reportsDropdown = document.querySelector('.section:last-of-type .dropdown-container');
            if (reportsDropdown && !reportsDropdown.contains(e.target)) {
                const content = document.getElementById('dropdown-content');
                const arrow = document.getElementById('dropdown-arrow');
                if (content && content.classList.contains('show')) {
                    content.classList.remove('show');
                    arrow.textContent = '▼';
                }
            }

            // Dropdown de criar relatório
            const createDropdown = document.querySelector('.section:first-of-type .dropdown-container');
            if (createDropdown && !createDropdown.contains(e.target)) {
                const content = document.getElementById('create-report-content');
                const arrow = document.getElementById('create-report-arrow');
                if (content && content.classList.contains('show')) {
                    content.classList.remove('show');
                    arrow.textContent = '▼';
                }
            }
        });

        // Auto-save para comentários
        const comentarios = document.getElementById('comentarios');
        if (comentarios) {
            const autoSaveKey = 'relatorio_comentarios_autosave';
            
            // Carregar texto salvo
            const savedText = localStorage.getItem(autoSaveKey);
            if (savedText && !comentarios.value) {
                comentarios.value = savedText;
            }
            
            // Salvar automaticamente
            comentarios.addEventListener('input', function() {
                localStorage.setItem(autoSaveKey, this.value);
            });
            
            // Limpar ao enviar
            const formServico = document.getElementById('form-servico');
            if (formServico) {
                formServico.addEventListener('submit', function() {
                    localStorage.removeItem(autoSaveKey);
                });
            }
        }

        // Notificação de mudanças não salvas
        let formChanged = false;
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('change', function() {
                formChanged = true;
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
            }
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                formChanged = false;
            });
        });
    </script>
</body>
</html>