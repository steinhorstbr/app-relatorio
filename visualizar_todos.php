<?php
/**
 * ================================================
 * ARQUIVO: visualizar_todos.php
 * DESCRIÇÃO: Página para visualizar todos os relatórios
 * ================================================
 */

// Incluir configurações
require_once 'config.php';

// Parâmetros de paginação e filtros
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$search = isset($_GET['search']) ? sanitizeString($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$order_by = isset($_GET['order']) ? $_GET['order'] : 'created_at';
$order_dir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';

try {
    // Montar query base
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(periodo LIKE ? OR supervisor LIKE ? OR regiao LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status_relatorio = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Buscar total de registros
    $count_sql = "SELECT COUNT(*) FROM relatorios $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Buscar relatórios com paginação
    $valid_columns = ['id', 'periodo', 'supervisor', 'regiao', 'data_relatorio', 'created_at', 'total_servicos'];
    if (!in_array($order_by, $valid_columns)) {
        $order_by = 'created_at';
    }
    
    $sql = "SELECT * FROM relatorios $where_clause ORDER BY $order_by $order_dir LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $relatorios = $stmt->fetchAll();
    
    // Buscar estatísticas gerais
    $stats_sql = "SELECT 
        COUNT(*) as total_relatorios,
        SUM(total_servicos) as total_servicos_geral,
        SUM(servicos_concluidos) as total_concluidos_geral,
        SUM(servicos_andamento) as total_andamento_geral,
        AVG(CASE WHEN total_servicos > 0 THEN (servicos_concluidos / total_servicos) * 100 ELSE 0 END) as taxa_conclusao_media
    FROM relatorios $where_clause";
    
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    $error = "Erro ao carregar relatórios: " . $e->getMessage();
    $relatorios = [];
    $total_pages = 0;
    $stats = ['total_relatorios' => 0, 'total_servicos_geral' => 0, 'total_concluidos_geral' => 0, 'total_andamento_geral' => 0, 'taxa_conclusao_media' => 0];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos os Relatórios - <?= $config['site_name'] ?></title>
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
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header-actions {
            margin-top: 20px;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .content {
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }

        .filters h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 200px 200px auto;
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
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #3498db;
            outline: none;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #34495e;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            position: relative;
        }

        .table th a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .table th a:hover {
            color: #74b9ff;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-ativo {
            background: #d4edda;
            color: #155724;
        }

        .status-fechado {
            background: #f8d7da;
            color: #721c24;
        }

        .status-arquivado {
            background: #e2e3e5;
            color: #383d41;
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #3498db;
            color: white;
        }

        .btn-pdf {
            background: #e74c3c;
            color: white;
        }

        .btn-edit {
            background: #f39c12;
            color: white;
        }

        .btn-small:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: bold;
        }

        .pagination a:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .pagination .disabled {
            opacity: 0.5;
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

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .table {
                min-width: 800px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .content {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
                gap: 3px;
            }
        }

        .sort-icon {
            margin-left: 5px;
            opacity: 0.7;
        }

        .highlight {
            background: #fff3cd !important;
            animation: fadeHighlight 2s ease-out;
        }

        @keyframes fadeHighlight {
            from { background: #fff3cd !important; }
            to { background: transparent; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Todos os Relatórios</h1>
            <p>Visualizar e gerenciar todos os relatórios de fibra óptica</p>
            <div class="header-actions">
                <a href="index_novo.php" class="btn btn-secondary">⬅️ Voltar ao Início</a>
                <a href="index_novo.php?form=relatorio" class="btn">➕ Novo Relatório</a>
            </div>
        </div>

        <div class="content">
            <!-- Estatísticas Gerais -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($stats['total_relatorios']) ?></span>
                    <div class="stat-label">Total de Relatórios</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($stats['total_servicos_geral']) ?></span>
                    <div class="stat-label">Total de Serviços</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($stats['total_concluidos_geral']) ?></span>
                    <div class="stat-label">Serviços Concluídos</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($stats['total_andamento_geral']) ?></span>
                    <div class="stat-label">Em Andamento</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= number_format($stats['taxa_conclusao_media'], 1) ?>%</span>
                    <div class="stat-label">Taxa Média de Conclusão</div>
                </div>
            </div>

            <!-- Filtros de Busca -->
            <div class="filters">
                <h3>🔍 Filtros e Busca</h3>
                <form method="GET" id="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search">Buscar</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Buscar por período, supervisor ou região...">
                        </div>
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">Todos os Status</option>
                                <option value="ativo" <?= $status_filter === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="fechado" <?= $status_filter === 'fechado' ? 'selected' : '' ?>>Fechado</option>
                                <option value="arquivado" <?= $status_filter === 'arquivado' ? 'selected' : '' ?>>Arquivado</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="order">Ordenar por</label>
                            <select id="order" name="order">
                                <option value="created_at" <?= $order_by === 'created_at' ? 'selected' : '' ?>>Data de Criação</option>
                                <option value="data_relatorio" <?= $order_by === 'data_relatorio' ? 'selected' : '' ?>>Data do Relatório</option>
                                <option value="periodo" <?= $order_by === 'periodo' ? 'selected' : '' ?>>Período</option>
                                <option value="supervisor" <?= $order_by === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                                <option value="total_servicos" <?= $order_by === 'total_servicos' ? 'selected' : '' ?>>Total de Serviços</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="btn">🔍 Filtrar</button>
                        </div>
                    </div>
                    <input type="hidden" name="dir" value="<?= $order_dir ?>">
                </form>
            </div>

            <!-- Tabela de Relatórios -->
            <?php if (empty($relatorios)): ?>
                <div class="empty-state">
                    <h3>📋 Nenhum Relatório Encontrado</h3>
                    <p>Não foram encontrados relatórios com os filtros aplicados.</p>
                    <?php if (empty($search) && empty($status_filter)): ?>
                        <a href="index_novo.php?form=relatorio" class="btn" style="margin-top: 20px;">
                            ➕ Criar Primeiro Relatório
                        </a>
                    <?php else: ?>
                        <a href="?" class="btn btn-secondary" style="margin-top: 20px;">
                            🗑️ Limpar Filtros
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['order' => 'id', 'dir' => $order_by === 'id' && $order_dir === 'ASC' ? 'desc' : 'asc'])) ?>">
                                        ID
                                        <?php if ($order_by === 'id'): ?>
                                            <span class="sort-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['order' => 'periodo', 'dir' => $order_by === 'periodo' && $order_dir === 'ASC' ? 'desc' : 'asc'])) ?>">
                                        Período
                                        <?php if ($order_by === 'periodo'): ?>
                                            <span class="sort-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['order' => 'supervisor', 'dir' => $order_by === 'supervisor' && $order_dir === 'ASC' ? 'desc' : 'asc'])) ?>">
                                        Supervisor
                                        <?php if ($order_by === 'supervisor'): ?>
                                            <span class="sort-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Região</th>
                                <th>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['order' => 'data_relatorio', 'dir' => $order_by === 'data_relatorio' && $order_dir === 'ASC' ? 'desc' : 'asc'])) ?>">
                                        Data
                                        <?php if ($order_by === 'data_relatorio'): ?>
                                            <span class="sort-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['order' => 'total_servicos', 'dir' => $order_by === 'total_servicos' && $order_dir === 'ASC' ? 'desc' : 'asc'])) ?>">
                                        Serviços
                                        <?php if ($order_by === 'total_servicos'): ?>
                                            <span class="sort-icon"><?= $order_dir === 'ASC' ? '▲' : '▼' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Progresso</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorios as $relatorio): ?>
                                <tr <?= isset($_GET['highlight']) && $_GET['highlight'] == $relatorio['id'] ? 'class="highlight"' : '' ?>>
                                    <td><strong>#<?= $relatorio['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($relatorio['periodo']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($relatorio['supervisor']) ?></td>
                                    <td><?= htmlspecialchars($relatorio['regiao']) ?></td>
                                    <td><?= formatDate($relatorio['data_relatorio']) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 10px; align-items: center;">
                                            <div>
                                                <strong><?= $relatorio['total_servicos'] ?></strong> total<br>
                                                <small style="color: #27ae60;">✅ <?= $relatorio['servicos_concluidos'] ?> concluídos</small><br>
                                                <small style="color: #f39c12;">⏳ <?= $relatorio['servicos_andamento'] ?> em andamento</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $progresso = $relatorio['total_servicos'] > 0 ? 
                                            ($relatorio['servicos_concluidos'] / $relatorio['total_servicos']) * 100 : 0;
                                        ?>
                                        <div><?= number_format($progresso, 1) ?>%</div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= $progresso ?>%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $relatorio['status_relatorio'] ?? 'ativo' ?>">
                                            <?= ucfirst($relatorio['status_relatorio'] ?? 'ativo') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="visualizar.php?id=<?= $relatorio['id'] ?>" class="btn-small btn-view" title="Visualizar">
                                                👁️
                                            </a>
                                            <a href="gerar_pdf_identico.php?id=<?= $relatorio['id'] ?>" class="btn-small btn-pdf" title="Baixar PDF" target="_blank">
                                                📄
                                            </a>
                                            <a href="index_novo.php?form=servico&relatorio=<?= $relatorio['id'] ?>" class="btn-small btn-edit" title="Adicionar Serviço">
                                                ➕
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php
                        $query_params = $_GET;
                        
                        // Primeira página
                        if ($page > 1): 
                            $query_params['page'] = 1;
                        ?>
                            <a href="?<?= http_build_query($query_params) ?>" title="Primeira página">⏮️</a>
                        <?php endif; ?>
                        
                        <?php
                        // Página anterior
                        if ($page > 1): 
                            $query_params['page'] = $page - 1;
                        ?>
                            <a href="?<?= http_build_query($query_params) ?>" title="Página anterior">◀️</a>
                        <?php else: ?>
                            <span class="disabled">◀️</span>
                        <?php endif; ?>

                        <?php
                        // Páginas próximas
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <span>...</span>
                        <?php endif;
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                            $query_params['page'] = $i;
                            if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query($query_params) ?>"><?= $i ?></a>
                            <?php endif;
                        endfor;
                        
                        if ($end_page < $total_pages): ?>
                            <span>...</span>
                        <?php endif; ?>

                        <?php
                        // Próxima página
                        if ($page < $total_pages): 
                            $query_params['page'] = $page + 1;
                        ?>
                            <a href="?<?= http_build_query($query_params) ?>" title="Próxima página">▶️</a>
                        <?php else: ?>
                            <span class="disabled">▶️</span>
                        <?php endif; ?>
                        
                        <?php
                        // Última página
                        if ($page < $total_pages): 
                            $query_params['page'] = $total_pages;
                        ?>
                            <a href="?<?= http_build_query($query_params) ?>" title="Última página">⏭️</a>
                        <?php endif; ?>
                        
                        <div style="margin-left: 20px; color: #666; font-size: 14px;">
                            Página <?= $page ?> de <?= $total_pages ?> (<?= number_format($total_records) ?> registros)
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit do formulário ao alterar filtros
        document.getElementById('search').addEventListener('input', debounce(function() {
            document.getElementById('filter-form').submit();
        }, 500));

        document.getElementById('status').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });

        document.getElementById('order').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });

        // Função debounce para evitar muitas requisições
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Animação das barras de progresso
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, index * 100);
            });
        });

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'f':
                        e.preventDefault();
                        document.getElementById('search').focus();
                        break;
                    case 'n':
                        e.preventDefault();
                        window.location.href = 'index_novo.php?form=relatorio';
                        break;
                }
            }
        });

        // Confirmar ações importantes
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Tem certeza que deseja excluir este relatório? Esta ação não pode ser desfeita.')) {
                    e.preventDefault();
                }
            });
        });

        // Feedback visual para ações
        document.querySelectorAll('.btn-small').forEach(btn => {
            btn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '⏳';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 1000);
            });
        });

        // Informações de debug
        console.log('Página de relatórios carregada');
        console.log('Total de relatórios:', <?= count($relatorios) ?>);
        console.log('Página atual:', <?= $page ?>);
        console.log('Total de páginas:', <?= $total_pages ?>);
    </script>
</body>
</html>