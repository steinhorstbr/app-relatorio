<?php
/**
 * ================================================
 * ARQUIVO: relatorios.php
 * DESCRIÇÃO: Página para listar todos os relatórios
 * ================================================
 */

// Incluir configurações
require_once 'config.php';

// Parâmetros de paginação e filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';
$supervisor_filter = isset($_GET['supervisor']) ? sanitizeString($_GET['supervisor']) : '';
$regiao_filter = isset($_GET['regiao']) ? sanitizeString($_GET['regiao']) : '';

try {
    // Construir WHERE clause
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(periodo LIKE ? OR supervisor LIKE ? OR regiao LIKE ? OR observacoes_gerais LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($supervisor_filter)) {
        $where_conditions[] = "supervisor = ?";
        $params[] = $supervisor_filter;
    }
    
    if (!empty($regiao_filter)) {
        $where_conditions[] = "regiao = ?";
        $params[] = $regiao_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Contar total de relatórios
    $count_sql = "SELECT COUNT(*) as total FROM relatorios $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_relatorios = $stmt->fetch()['total'];
    
    // Buscar relatórios com paginação
    $sql = "SELECT * FROM relatorios $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $relatorios = $stmt->fetchAll();
    
    // Calcular número de páginas
    $total_pages = ceil($total_relatorios / $per_page);
    
    // Buscar supervisores únicos para filtro
    $stmt = $pdo->prepare("SELECT DISTINCT supervisor FROM relatorios ORDER BY supervisor");
    $stmt->execute();
    $supervisores = $stmt->fetchAll();
    
    // Buscar regiões únicas para filtro
    $stmt = $pdo->prepare("SELECT DISTINCT regiao FROM relatorios ORDER BY regiao");
    $stmt->execute();
    $regioes = $stmt->fetchAll();
    
    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT r.id) as total_relatorios,
            COALESCE(SUM(r.total_servicos), 0) as total_servicos,
            COALESCE(SUM(r.servicos_concluidos), 0) as servicos_concluidos,
            COALESCE(SUM(r.servicos_andamento), 0) as servicos_andamento,
            COUNT(DISTINCT r.supervisor) as total_supervisores,
            COUNT(DISTINCT r.regiao) as total_regioes
        FROM relatorios r
        $where_clause
    ");
    $stmt->execute(array_slice($params, 0, count($params) - 2)); // Remove LIMIT e OFFSET params
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
    <title>Todos os Relatórios - <?= $config['site_name'] ?></title>
    <meta name="description" content="Lista completa de todos os relatórios de fibra óptica">
    
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
            max-width: 1400px;
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

        .navigation {
            background: #f8f9fa;
            padding: 15px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Filtros */
        .filters {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }

        .filters h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #3498db;
            outline: none;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
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

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Grid de relatórios */
        .relatorios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .relatorio-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .relatorio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 20px;
            position: relative;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .card-subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .card-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        .card-body {
            padding: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid #3498db;
        }

        .info-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-box {
            text-align: center;
            padding: 10px;
            background: #ecf0f1;
            border-radius: 8px;
        }

        .stat-box-number {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-box-label {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-top: 2px;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .observacoes-preview {
            background: #e8f4fd;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #2c3e50;
            border-left: 3px solid #3498db;
            max-height: 60px;
            overflow: hidden;
            position: relative;
        }

        .observacoes-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(transparent, #e8f4fd);
        }

        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: #f8f9fa;
            color: #3498db;
            border: 2px solid #e0e0e0;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: #3498db;
            color: white;
            border: 2px solid #3498db;
        }

        .pagination .disabled {
            background: #f0f0f0;
            color: #ccc;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .results-info {
            background: #e8f4fd;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-text {
            color: #2c3e50;
            font-weight: 500;
        }

        .results-actions {
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .relatorios-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .navigation {
                flex-direction: column;
                align-items: stretch;
            }
            
            .card-actions {
                flex-direction: column;
            }
            
            .card-actions .btn {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .filters {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Todos os Relatórios</h1>
            
            <?php if (!empty($stats)): ?>
            <div class="header-stats">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_relatorios'] ?></span>
                    <div class="stat-label">Relatórios</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_servicos'] ?></span>
                    <div class="stat-label">Serviços</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['servicos_concluidos'] ?></span>
                    <div class="stat-label">Concluídos</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_supervisores'] ?></span>
                    <div class="stat-label">Supervisores</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_regioes'] ?></span>
                    <div class="stat-label">Regiões</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_servicos'] > 0 ? round(($stats['servicos_concluidos'] / $stats['total_servicos']) * 100, 1) : 0 ?>%</span>
                    <div class="stat-label">Taxa Conclusão</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="navigation">
            <div class="breadcrumb">
                <a href="index.php">🏠 Início</a>
                <span>></span>
                <span>📊 Todos os Relatórios</span>
            </div>
            <div>
                <a href="index.php" class="btn">➕ Novo Relatório</a>
            </div>
        </div>

        <div class="content">
            <!-- Filtros -->
            <form method="GET" class="filters">
                <h3>🔍 Filtros de Busca</h3>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Buscar por texto:</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Digite período, supervisor, região ou observações...">
                    </div>
                    <div class="filter-group">
                        <label for="supervisor">Supervisor:</label>
                        <select id="supervisor" name="supervisor">
                            <option value="">Todos os supervisores</option>
                            <?php foreach ($supervisores as $sup): ?>
                                <option value="<?= htmlspecialchars($sup['supervisor']) ?>" <?= $supervisor_filter === $sup['supervisor'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sup['supervisor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="regiao">Região:</label>
                        <select id="regiao" name="regiao">
                            <option value="">Todas as regiões</option>
                            <?php foreach ($regioes as $reg): ?>
                                <option value="<?= htmlspecialchars($reg['regiao']) ?>" <?= $regiao_filter === $reg['regiao'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($reg['regiao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn">🔍 Filtrar</button>
                        <?php if (!empty($search) || !empty($supervisor_filter) || !empty($regiao_filter)): ?>
                            <a href="relatorios.php" class="btn btn-secondary" style="margin-top: 5px;">🔄 Limpar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Informações dos resultados -->
            <?php if ($total_relatorios > 0): ?>
            <div class="results-info">
                <div class="results-text">
                    📋 Exibindo <?= count($relatorios) ?> de <?= $total_relatorios ?> relatórios
                    <?php if ($total_pages > 1): ?>
                        (Página <?= $page ?> de <?= $total_pages ?>)
                    <?php endif; ?>
                </div>
                <div class="results-actions">
                    <button onclick="exportarDados()" class="btn btn-warning btn-small">📊 Exportar</button>
                    <button onclick="imprimirPagina()" class="btn btn-secondary btn-small">🖨️ Imprimir</button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Grid de relatórios -->
            <?php if (empty($relatorios)): ?>
                <div class="empty-state">
                    <h3>📝 Nenhum relatório encontrado</h3>
                    <p>
                        <?php if (!empty($search) || !empty($supervisor_filter) || !empty($regiao_filter)): ?>
                            Tente ajustar os filtros de busca ou <a href="relatorios.php">visualizar todos os relatórios</a>.
                        <?php else: ?>
                            Ainda não há relatórios criados. <a href="index.php">Criar primeiro relatório</a>.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="relatorios-grid">
                    <?php foreach ($relatorios as $relatorio): ?>
                    <div class="relatorio-card">
                        <div class="card-header">
                            <div class="card-title">
                                📋 <?= htmlspecialchars($relatorio['periodo']) ?>
                            </div>
                            <div class="card-subtitle">
                                ID: #<?= $relatorio['id'] ?>
                            </div>
                            <div class="card-badge">
                                <?= formatDate($relatorio['data_relatorio']) ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Supervisor</div>
                                    <div class="info-value"><?= htmlspecialchars($relatorio['supervisor']) ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Região</div>
                                    <div class="info-value"><?= htmlspecialchars($relatorio['regiao']) ?></div>
                                </div>
                            </div>

                            <div class="stats-row">
                                <div class="stat-box">
                                    <div class="stat-box-number"><?= $relatorio['total_servicos'] ?></div>
                                    <div class="stat-box-label">Total</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-box-number"><?= $relatorio['servicos_concluidos'] ?></div>
                                    <div class="stat-box-label">Concluídos</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-box-number"><?= $relatorio['servicos_andamento'] ?></div>
                                    <div class="stat-box-label">Andamento</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-box-number"><?= $relatorio['total_servicos'] > 0 ? round(($relatorio['servicos_concluidos'] / $relatorio['total_servicos']) * 100) : 0 ?>%</div>
                                    <div class="stat-box-label">Taxa</div>
                                </div>
                            </div>

                            <?php if ($relatorio['observacoes_gerais']): ?>
                            <div class="observacoes-preview">
                                <strong>Observações:</strong> <?= htmlspecialchars(substr($relatorio['observacoes_gerais'], 0, 100)) ?><?= strlen($relatorio['observacoes_gerais']) > 100 ? '...' : '' ?>
                            </div>
                            <?php endif; ?>

                            <div class="card-actions">
                                <a href="visualizar.php?id=<?= $relatorio['id'] ?>" class="btn btn-small">
                                    👁️ Visualizar
                                </a>
                                <a href="gerar_pdf_simples.php?id=<?= $relatorio['id'] ?>" class="btn btn-success btn-small" target="_blank">
                                    📄 PDF
                                </a>
                            </div>
                            
                            <div style="text-align: right; margin-top: 15px; font-size: 0.8rem; color: #999;">
                                Criado: <?= formatDateTime($relatorio['created_at']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($supervisor_filter) ? '&supervisor=' . urlencode($supervisor_filter) : '' ?><?= !empty($regiao_filter) ? '&regiao=' . urlencode($regiao_filter) : '' ?>" title="Primeira página">⏮️</a>
                    <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($supervisor_filter) ? '&supervisor=' . urlencode($supervisor_filter) : '' ?><?= !empty($regiao_filter) ? '&regiao=' . urlencode($regiao_filter) : '' ?>" title="Página anterior">⏪</a>
                <?php else: ?>
                    <span class="disabled">⏮️</span>
                    <span class="disabled">⏪</span>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($supervisor_filter) ? '&supervisor=' . urlencode($supervisor_filter) : '' ?><?= !empty($regiao_filter) ? '&regiao=' . urlencode($regiao_filter) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($supervisor_filter) ? '&supervisor=' . urlencode($supervisor_filter) : '' ?><?= !empty($regiao_filter) ? '&regiao=' . urlencode($regiao_filter) : '' ?>" title="Próxima página">⏩</a>
                    <a href="?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($supervisor_filter) ? '&supervisor=' . urlencode($supervisor_filter) : '' ?><?= !empty($regiao_filter) ? '&regiao=' . urlencode($regiao_filter) : '' ?>" title="Última página">⏭️</a>
                <?php else: ?>
                    <span class="disabled">⏩</span>
                    <span class="disabled">⏭️</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ================================================
        // JAVASCRIPT PARA FUNCIONALIDADES
        // ================================================

        // Função para exportar dados
        function exportarDados() {
            const dados = {
                relatorios: <?= json_encode($relatorios, JSON_UNESCAPED_UNICODE) ?>,
                estatisticas: <?= json_encode($stats, JSON_UNESCAPED_UNICODE) ?>,
                filtros: {
                    search: '<?= htmlspecialchars($search) ?>',
                    supervisor: '<?= htmlspecialchars($supervisor_filter) ?>',
                    regiao: '<?= htmlspecialchars($regiao_filter) ?>'
                },
                exported_at: new Date().toISOString(),
                total_pages: <?= $total_pages ?>,
                current_page: <?= $page ?>
            };
            
            const dataStr = JSON.stringify(dados, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = 'relatorios_export_' + new Date().toISOString().split('T')[0] + '.json';
            link.click();
        }

        // Função para imprimir página
        function imprimirPagina() {
            window.print();
        }

        // Auto-submit do formulário de filtros quando mudança é detectada
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.filters form');
            const inputs = form.querySelectorAll('select');
            
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Pequeno delay para melhor UX
                    setTimeout(() => {
                        form.submit();
                    }, 300);
                });
            });

            // Busca em tempo real para o campo de texto
            const searchInput = document.getElementById('search');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        form.submit();
                    }
                }, 800);
            });

            // Animações de entrada
            animateCards();
        });

        // Animações para os cards
        function animateCards() {
            const cards = document.querySelectorAll('.relatorio-card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            }, { threshold: 0.1 });

            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
        }

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + F para focar no campo de busca
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
            
            // Esc para limpar filtros
            if (e.key === 'Escape') {
                const hasFilters = '<?= !empty($search) || !empty($supervisor_filter) || !empty($regiao_filter) ? "true" : "false" ?>' === 'true';
                if (hasFilters) {
                    if (confirm('Limpar todos os filtros?')) {
                        window.location.href = 'relatorios.php';
                    }
                }
            }
        });

        // Função para destacar texto da busca nos resultados
        function highlightSearchText() {
            const searchTerm = '<?= htmlspecialchars($search) ?>';
            if (searchTerm.length >= 3) {
                const cards = document.querySelectorAll('.relatorio-card');
                cards.forEach(card => {
                    const textElements = card.querySelectorAll('.card-title, .info-value, .observacoes-preview');
                    textElements.forEach(element => {
                        const text = element.textContent;
                        const regex = new RegExp(`(${searchTerm})`, 'gi');
                        if (regex.test(text)) {
                            element.innerHTML = text.replace(regex, '<mark style="background: #ffeb3b; padding: 2px 4px; border-radius: 3px;">$1</mark>');
                        }
                    });
                });
            }
        }

        // Executar highlight após o carregamento da página
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(highlightSearchText, 500);
        });

        // Função para mostrar/ocultar observações completas
        function toggleObservacoes(relatorioId) {
            const element = document.getElementById(`obs-${relatorioId}`);
            if (element) {
                element.classList.toggle('expanded');
            }
        }

        // Adicionar tooltips informativos
        document.addEventListener('DOMContentLoaded', function() {
            const statBoxes = document.querySelectorAll('.stat-box');
            statBoxes.forEach(box => {
                const label = box.querySelector('.stat-box-label').textContent;
                box.title = `Clique para ver detalhes sobre ${label}`;
            });
        });

        // Log de atividade da página
        console.log('Página de relatórios carregada');
        console.log('Total de relatórios:', <?= $total_relatorios ?>);
        console.log('Página atual:', <?= $page ?> + ' de ' + <?= $total_pages ?>);
        console.log('Filtros ativos:', {
            search: '<?= htmlspecialchars($search) ?>',
            supervisor: '<?= htmlspecialchars($supervisor_filter) ?>',
            regiao: '<?= htmlspecialchars($regiao_filter) ?>'
        });
    </script>
</body>
</html>