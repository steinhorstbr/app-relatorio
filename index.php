<?php
/**
 * ================================================
 * ARQUIVO: index.php
 * DESCRIÇÃO: Página principal do sistema
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
                    $equipe = sanitizeString($_POST['equipe']);
                    $tecnicos = sanitizeString($_POST['tecnicos']);
                    $data_execucao = $_POST['data_execucao'];
                    $local_servico = sanitizeString($_POST['local_servico']);
                    $comentarios = sanitizeString($_POST['comentarios']);
                    
                    // Validações
                    if (empty($relatorio_id) || empty($titulo) || empty($status) || empty($equipe) || empty($tecnicos) || empty($data_execucao) || empty($local_servico) || empty($comentarios)) {
                        throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
                    }
                    
                    // Verificar se relatório existe
                    $stmt = $pdo->prepare("SELECT id FROM relatorios WHERE id = ?");
                    $stmt->execute([$relatorio_id]);
                    if (!$stmt->fetch()) {
                        throw new Exception('Relatório não encontrado.');
                    }
                    
                    // Upload das fotos
                    $foto_antes = '';
                    $foto_depois = '';
                    
                    if (isset($_FILES['foto_antes']) && $_FILES['foto_antes']['error'] === UPLOAD_ERR_OK) {
                        $result = uploadFile($_FILES['foto_antes'], $config);
                        if ($result['success']) {
                            $foto_antes = $result['filename'];
                        } else {
                            throw new Exception('Erro no upload da foto ANTES: ' . $result['error']);
                        }
                    }
                    
                    if (isset($_FILES['foto_depois']) && $_FILES['foto_depois']['error'] === UPLOAD_ERR_OK) {
                        $result = uploadFile($_FILES['foto_depois'], $config);
                        if ($result['success']) {
                            $foto_depois = $result['filename'];
                        } else {
                            // Se foto antes foi carregada mas depois falhou, deletar a primeira
                            if ($foto_antes) {
                                deleteFile($foto_antes, $config);
                            }
                            throw new Exception('Erro no upload da foto DEPOIS: ' . $result['error']);
                        }
                    }
                    
                    // Inserir serviço
                    $stmt = $pdo->prepare("INSERT INTO servicos (relatorio_id, titulo, status, equipe, tecnicos, data_execucao, local_servico, foto_antes, foto_depois, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$relatorio_id, $titulo, $status, $equipe, $tecnicos, $data_execucao, $local_servico, $foto_antes, $foto_depois, $comentarios]);
                    
                    $servico_id = $pdo->lastInsertId();
                    $success = "Serviço adicionado com sucesso! ID: $servico_id";
                    
                    // Log da atividade
                    logActivity('ADICIONAR_SERVICO', "Serviço $servico_id adicionado ao relatório $relatorio_id: $titulo");
                    break;
                
                default:
                    throw new Exception('Ação não reconhecida.');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // Log do erro
        logActivity('ERRO', $error);
    }
}

// ================================================
// BUSCAR DADOS PARA EXIBIÇÃO
// ================================================
try {
    // Buscar relatórios ordenados por data de criação
    $stmt = $pdo->prepare("SELECT * FROM relatorios ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $relatorios = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
    $relatorios = [];
}

// ================================================
// VERIFICAR SE FOI SOLICITADO PDF
// ================================================
if (isset($_GET['pdf']) && isset($_GET['id'])) {
    $relatorio_id = (int)$_GET['id'];
    
    try {
        // Verificar se relatório existe
        $stmt = $pdo->prepare("SELECT id FROM relatorios WHERE id = ?");
        $stmt->execute([$relatorio_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Relatório não encontrado");
        }
        
        // Redirecionar para o gerador de PDF
        header("Location: gerar_pdf_identico.php?id=" . $relatorio_id);
        exit;
        
    } catch (Exception $e) {
        $error = "Erro ao gerar PDF: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $config['site_name'] ?></title>
    <meta name="description" content="Sistema para gerenciar relatórios mensais de serviços de fibra óptica">
    <meta name="author" content="Sistema de Relatórios">
    
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

        .relatorios-list {
            display: grid;
            gap: 20px;
        }

        .relatorio-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .relatorio-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .relatorio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .relatorio-title {
            color: #2c3e50;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .relatorio-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            border-left: 3px solid #3498db;
        }

        .info-item strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 3px;
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

        .file-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }

        .file-upload.has-file {
            border-color: #27ae60;
            background: #d4edda;
            color: #155724;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .stat-item {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .relatorio-header {
                flex-direction: column;
                align-items: stretch;
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
            
            .relatorio-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📡 <?= $config['site_name'] ?></h1>
            <p>Gerencie relatórios mensais de serviços executados pelas equipes de campo</p>
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
                <h2>📋 Criar Novo Relatório</h2>
                <form method="POST" enctype="multipart/form-data" id="form-relatorio">
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
                            <input type="text" id="regiao" name="regiao" placeholder="Ex: Lins - SP" required>
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

                    <button type="submit" class="btn">Criar Relatório</button>
                </form>
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
                                    <?= htmlspecialchars($rel['periodo']) ?> - <?= htmlspecialchars($rel['supervisor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titulo">Título do Serviço *</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ex: Instalação de Fibra Óptica - Residencial" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select id="status" name="status" required>
                                <option value="">Selecione o status...</option>
                                <option value="concluido">Concluído</option>
                                <option value="andamento">Em Andamento</option>
                                <option value="pendente">Pendente</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="equipe">Equipe *</label>
                            <input type="text" id="equipe" name="equipe" placeholder="Ex: Alfa-01" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tecnicos">Técnicos *</label>
                            <input type="text" id="tecnicos" name="tecnicos" placeholder="Ex: Carlos Lima, Pedro Santos" required>
                        </div>
                        <div class="form-group">
                            <label for="data_execucao">Data de Execução *</label>
                            <input type="date" id="data_execucao" name="data_execucao" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="local_servico">Local do Serviço *</label>
                        <input type="text" id="local_servico" name="local_servico" placeholder="Ex: Rua das Flores, 123 - Centro" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="foto_antes">Foto Antes</label>
                            <div class="file-upload" id="upload-antes">
                                <input type="file" id="foto_antes" name="foto_antes" accept="image/*">
                                <p>📷 Clique para selecionar a foto ANTES do serviço</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="foto_depois">Foto Depois</label>
                            <div class="file-upload" id="upload-depois">
                                <input type="file" id="foto_depois" name="foto_depois" accept="image/*">
                                <p>📷 Clique para selecionar a foto DEPOIS do serviço</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comentarios">Comentários e Observações *</label>
                        <textarea id="comentarios" name="comentarios" rows="4" placeholder="Descreva detalhes do serviço executado, observações técnicas, materiais utilizados, etc..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Adicionar Serviço</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Lista de relatórios -->
            <div class="section">
                <h2>📊 Relatórios Criados</h2>
                <?php if (empty($relatorios)): ?>
                    <div class="empty-state">
                        <p>📝 Nenhum relatório criado ainda.</p>
                        <p>Comece criando seu primeiro relatório mensal acima.</p>
                    </div>
                <?php else: ?>
                    <div class="relatorios-list">
                        <?php foreach ($relatorios as $relatorio): ?>
                            <div class="relatorio-card">
                                <div class="relatorio-header">
                                    <div class="relatorio-title">
                                        📋 <?= htmlspecialchars($relatorio['periodo']) ?>
                                    </div>
                                    <div>
                                        <a href="?pdf=1&id=<?= $relatorio['id'] ?>" class="btn btn-success" title="Baixar PDF">
                                            📄 PDF
                                        </a>
                                        <a href="visualizar.php?id=<?= $relatorio['id'] ?>" class="btn" title="Visualizar relatório">
                                            👁️ Ver
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="relatorio-info">
                                    <div class="info-item">
                                        <strong>Supervisor:</strong>
                                        <?= htmlspecialchars($relatorio['supervisor']) ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Região:</strong>
                                        <?= htmlspecialchars($relatorio['regiao']) ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Data:</strong>
                                        <?= formatDate($relatorio['data_relatorio']) ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Criado em:</strong>
                                        <?= formatDateTime($relatorio['created_at']) ?>
                                    </div>
                                </div>

                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <strong><?= $relatorio['total_servicos'] ?></strong><br>
                                        Total de Serviços
                                    </div>
                                    <div class="stat-item">
                                        <strong><?= $relatorio['servicos_concluidos'] ?></strong><br>
                                        Concluídos
                                    </div>
                                    <div class="stat-item">
                                        <strong><?= $relatorio['servicos_andamento'] ?></strong><br>
                                        Em Andamento
                                    </div>
                                    <div class="stat-item">
                                        <strong><?= $relatorio['total_servicos'] > 0 ? round(($relatorio['servicos_concluidos'] / $relatorio['total_servicos']) * 100, 1) : 0 ?>%</strong><br>
                                        Taxa de Conclusão
                                    </div>
                                </div>

                                <?php if ($relatorio['observacoes_gerais']): ?>
                                <div style="margin-top: 15px; padding: 10px; background: #e8f4fd; border-radius: 5px; font-size: 0.9rem;">
                                    <strong>Observações:</strong> <?= nl2br(htmlspecialchars($relatorio['observacoes_gerais'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Loading indicator -->
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Processando...</p>
            </div>
        </div>
    </div>

    <script>
        // ================================================
        // JAVASCRIPT PARA MELHORAR UX
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

            // Melhorar UX dos uploads de arquivo
            setupFileUploads();

            // Validação de formulários
            setupFormValidation();

            // Loading states
            setupLoadingStates();
        });

        function setupFileUploads() {
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', function() {
                    const fileUpload = this.closest('.file-upload');
                    const fileName = this.files[0] ? this.files[0].name : '';
                    const p = fileUpload.querySelector('p');
                    
                    if (fileName) {
                        fileUpload.classList.add('has-file');
                        p.innerHTML = `✅ Arquivo selecionado:<br><strong>${fileName}</strong>`;
                    } else {
                        fileUpload.classList.remove('has-file');
                        const isAntes = this.id === 'foto_antes';
                        p.innerHTML = `📷 Clique para selecionar a foto ${isAntes ? 'ANTES' : 'DEPOIS'} do serviço`;
                    }
                });

                // Permitir drag and drop
                const fileUpload = input.closest('.file-upload');
                
                fileUpload.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.style.borderColor = '#3498db';
                    this.style.backgroundColor = '#f0f8ff';
                });

                fileUpload.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.style.borderColor = '#ddd';
                    this.style.backgroundColor = '';
                });

                fileUpload.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.borderColor = '#ddd';
                    this.style.backgroundColor = '';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        input.files = files;
                        input.dispatchEvent(new Event('change'));
                    }
                });

                // Click para abrir seletor
                fileUpload.addEventListener('click', function() {
                    input.click();
                });
            });
        }

        function setupFormValidation() {
            // Validar período (formato sugerido)
            const periodoInput = document.getElementById('periodo');
            if (periodoInput) {
                periodoInput.addEventListener('blur', function() {
                    const value = this.value.trim();
                    const regex = /^[A-Za-z]+ de \d{4}$/;
                    
                    if (value && !regex.test(value)) {
                        this.style.borderColor = '#e74c3c';
                        this.title = 'Formato sugerido: "Junho de 2025"';
                    } else {
                        this.style.borderColor = '#ddd';
                        this.title = '';
                    }
                });
            }

            // Validar campos obrigatórios em tempo real
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

        // Função para preview de imagens (opcional)
        function previewImage(input, targetId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(targetId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Confirmar antes de sair se há dados não salvos
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

        // Auto-save para textarea (opcional)
        const comentarios = document.getElementById('comentarios');
        if (comentarios) {
            const autoSaveKey = 'relatorio_comentarios_' + Date.now();
            
            // Carregar texto salvo
            const savedText = localStorage.getItem(autoSaveKey);
            if (savedText) {
                comentarios.value = savedText;
            }
            
            // Salvar automaticamente
            comentarios.addEventListener('input', function() {
                localStorage.setItem(autoSaveKey, this.value);
            });
            
            // Limpar ao enviar
            document.getElementById('form-servico').addEventListener('submit', function() {
                localStorage.removeItem(autoSaveKey);
            });
        }
    </script>
</body>
</html>