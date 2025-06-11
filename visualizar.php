<?php
/**
 * ================================================
 * ARQUIVO: visualizar.php (VERSÃO 2)
 * DESCRIÇÃO: Visualização detalhada dos relatórios atualizada
 * ================================================
 */

// Incluir configurações
require_once 'config.php';

// Verificar se foi passado ID do relatório
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$relatorio_id = (int)$_GET['id'];

try {
    // Buscar dados do relatório
    $stmt = $pdo->prepare("SELECT * FROM relatorios WHERE id = ?");
    $stmt->execute([$relatorio_id]);
    $relatorio = $stmt->fetch();

    if (!$relatorio) {
        throw new Exception("Relatório não encontrado");
    }

    // Buscar serviços do relatório
    $stmt = $pdo->prepare("SELECT * FROM servicos WHERE relatorio_id = ? ORDER BY data_execucao DESC, created_at DESC");
    $stmt->execute([$relatorio_id]);
    $servicos = $stmt->fetchAll();

    // Estatísticas por tipo de serviço
    $stmt = $pdo->prepare("
        SELECT 
            SUM(tipo_reparo) as total_reparos,
            SUM(tipo_construcao) as total_construcoes,
            SUM(tipo_ceo) as total_ceos,
            SUM(tipo_cto) as total_ctos,
            SUM(CASE WHEN tipo_reparo = 1 AND status = 'concluido' THEN 1 ELSE 0 END) as reparos_concluidos,
            SUM(CASE WHEN tipo_construcao = 1 AND status = 'concluido' THEN 1 ELSE 0 END) as construcoes_concluidas,
            SUM(CASE WHEN tipo_ceo = 1 AND status = 'concluido' THEN 1 ELSE 0 END) as ceos_concluidos,
            SUM(CASE WHEN tipo_cto = 1 AND status = 'concluido' THEN 1 ELSE 0 END) as ctos_concluidos
        FROM servicos 
        WHERE relatorio_id = ?
    ");
    $stmt->execute([$relatorio_id]);
    $tipos_stats = $stmt->fetch();

    // Log da visualização
    logActivity('VISUALIZAR_RELATORIO', "Relatório $relatorio_id visualizado");

} catch (Exception $e) {
    $error = $e->getMessage();
    logActivity('ERRO_VISUALIZACAO', $error);
}

// Função para obter ícones dos tipos de serviço
function getTiposServico($servico) {
    $tipos = [];
    if ($servico['tipo_reparo']) $tipos[] = ['icon' => '🔧', 'label' => 'Reparo'];
    if ($servico['tipo_construcao']) $tipos[] = ['icon' => '🏗️', 'label' => 'Construção'];
    if ($servico['tipo_ceo']) $tipos[] = ['icon' => '📡', 'label' => 'CEO'];
    if ($servico['tipo_cto']) $tipos[] = ['icon' => '📦', 'label' => 'CTO'];
    return $tipos;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório: <?= htmlspecialchars($relatorio['periodo']) ?> - <?= $config['site_name'] ?></title>
    <meta name="description" content="Visualização detalhada do relatório de fibra óptica">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
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

        .header-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .content {
            padding: 30px;
        }

        .actions {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            font-size: 16px;
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

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .summary-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Estatísticas por tipo de serviço */
        .tipos-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .tipo-card {
            background: linear-gradient(135deg, #a29bfe, #6c5ce7);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .tipo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .tipo-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .tipo-numbers {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .observacoes-gerais {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }

        .observacoes-gerais h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .service-section {
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            border-left: 5px solid #3498db;
            transition: all 0.3s ease;
        }

        .service-section:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .service-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: bold;
            flex: 1;
        }

        .service-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .status-concluido {
            background: #00b894;
            color: white;
        }

        .status-andamento {
            background: #fdcb6e;
            color: #2d3436;
        }

        .status-pendente {
            background: #e17055;
            color: white;
        }

        /* Tags de tipos de serviço */
        .service-tipos {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tipo-tag {
            background: linear-gradient(135deg, #fd79a8, #e84393);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Grid de 4 fotos */
        .photos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .photo-section {
            text-align: center;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .photo-section:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .photo-section h4 {
            margin-bottom: 10px;
            color: #2c3e50;
            font-size: 1rem;
        }

        .photo-description {
            font-size: 0.8rem;
            color: #666;
            font-style: italic;
            margin-bottom: 10px;
            min-height: 20px;
        }

        .photo-section img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .photo-section img:hover {
            transform: scale(1.05);
        }

        .no-photo {
            width: 100%;
            height: 150px;
            background: #ecf0f1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-style: italic;
            border: 2px dashed #bdc3c7;
        }

        .comments {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #74b9ff;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .comments h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .comments-text {
            line-height: 1.8;
            color: #555;
        }

        .team-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .team-card {
            background: linear-gradient(135deg, #a29bfe, #6c5ce7);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        /* Modal para ampliar imagens */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .modal-content {
            display: block;
            margin: auto;
            max-width: 90%;
            max-height: 90%;
            margin-top: 2%;
        }

        .modal-caption {
            text-align: center;
            color: white;
            margin-top: 15px;
            font-size: 1.1rem;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #ff6b6b;
        }

        @media (max-width: 768px) {
            .photos-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header-info {
                grid-template-columns: 1fr;
            }
            
            .service-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .actions {
                text-align: center;
            }
            
            .btn {
                display: block;
                margin: 5px 0;
            }
        }

        @media (max-width: 480px) {
            .photos-container {
                grid-template-columns: 1fr;
            }
            
            .team-info {
                grid-template-columns: 1fr;
            }
            
            .service-tipos {
                justify-content: center;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .actions {
                display: none;
            }
            
            .service-section {
                page-break-inside: avoid;
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($error)): ?>
        <div class="container">
            <div class="content">
                <div style="text-align: center; padding: 50px; color: #e74c3c;">
                    <h2>❌ Erro</h2>
                    <p><?= htmlspecialchars($error) ?></p>
                    <a href="index.php" class="btn" style="margin-top: 20px;">⬅️ Voltar</a>
                </div>
            </div>
        </div>
    <?php else: ?>

    <div class="container">
        <div class="header">
            <h1>📡 Relatório de Fibra Óptica</h1>
            <div class="header-info">
                <div class="info-card">
                    <strong>Período:</strong><br>
                    <?= htmlspecialchars($relatorio['periodo']) ?>
                </div>
                <div class="info-card">
                    <strong>Supervisor:</strong><br>
                    <?= htmlspecialchars($relatorio['supervisor']) ?>
                </div>
                <div class="info-card">
                    <strong>Região:</strong><br>
                    <?= htmlspecialchars($relatorio['regiao']) ?>
                </div>
                <div class="info-card">
                    <strong>Data:</strong><br>
                    <?= formatDate($relatorio['data_relatorio']) ?>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="actions">
                <a href="index.php" class="btn">⬅️ Voltar ao Início</a>
                <a href="relatorios.php" class="btn">📊 Todos os Relatórios</a>
                <a href="gerar_pdf_simples.php?id=<?= $relatorio['id'] ?>" class="btn btn-success" target="_blank">📄 Baixar PDF</a>
                <button onclick="window.print()" class="btn">🖨️ Imprimir</button>
            </div>

            <div class="summary">
                <div class="summary-card">
                    <h3><?= $relatorio['total_servicos'] ?></h3>
                    <p>Serviços Executados</p>
                </div>
                <div class="summary-card">
                    <h3><?= $relatorio['servicos_concluidos'] ?></h3>
                    <p>Concluídos</p>
                </div>
                <div class="summary-card">
                    <h3><?= $relatorio['servicos_andamento'] ?></h3>
                    <p>Em Andamento</p>
                </div>
                <div class="summary-card">
                    <h3><?= $relatorio['total_servicos'] > 0 ? round(($relatorio['servicos_concluidos'] / $relatorio['total_servicos']) * 100, 1) : 0 ?>%</h3>
                    <p>Taxa de Conclusão</p>
                </div>
            </div>

            <!-- Estatísticas por tipo de serviço -->
            <?php if (!empty($tipos_stats) && ($tipos_stats['total_reparos'] > 0 || $tipos_stats['total_construcoes'] > 0 || $tipos_stats['total_ceos'] > 0 || $tipos_stats['total_ctos'] > 0)): ?>
            <div class="tipos-stats">
                <?php if ($tipos_stats['total_reparos'] > 0): ?>
                <div class="tipo-card">
                    <span class="tipo-icon">🔧</span>
                    <h4>Reparos</h4>
                    <div class="tipo-numbers">
                        <span>Total: <?= $tipos_stats['total_reparos'] ?></span>
                        <span>Concluídos: <?= $tipos_stats['reparos_concluidos'] ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($tipos_stats['total_construcoes'] > 0): ?>
                <div class="tipo-card">
                    <span class="tipo-icon">🏗️</span>
                    <h4>Construções</h4>
                    <div class="tipo-numbers">
                        <span>Total: <?= $tipos_stats['total_construcoes'] ?></span>
                        <span>Concluídos: <?= $tipos_stats['construcoes_concluidas'] ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($tipos_stats['total_ceos'] > 0): ?>
                <div class="tipo-card">
                    <span class="tipo-icon">📡</span>
                    <h4>CEOs</h4>
                    <div class="tipo-numbers">
                        <span>Total: <?= $tipos_stats['total_ceos'] ?></span>
                        <span>Concluídos: <?= $tipos_stats['ceos_concluidos'] ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($tipos_stats['total_ctos'] > 0): ?>
                <div class="tipo-card">
                    <span class="tipo-icon">📦</span>
                    <h4>CTOs</h4>
                    <div class="tipo-numbers">
                        <span>Total: <?= $tipos_stats['total_ctos'] ?></span>
                        <span>Concluídos: <?= $tipos_stats['ctos_concluidos'] ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($relatorio['observacoes_gerais']): ?>
            <div class="observacoes-gerais">
                <h3>📝 Observações Gerais do Período</h3>
                <p><?= nl2br(htmlspecialchars($relatorio['observacoes_gerais'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if (empty($servicos)): ?>
                <div class="empty-state">
                    <h3>📋 Nenhum Serviço Registrado</h3>
                    <p>Este relatório ainda não possui serviços cadastrados.</p>
                    <a href="index.php" class="btn" style="margin-top: 20px;">➕ Adicionar Serviços</a>
                </div>
            <?php else: ?>
                <h2 style="color: #2c3e50; margin-bottom: 30px; text-align: center;">📋 Serviços Executados (<?= count($servicos) ?>)</h2>
                
                <?php foreach ($servicos as $index => $servico): ?>
                <div class="service-section">
                    <div class="service-header">
                        <div class="service-title">
                            <?= ($index + 1) . '. ' . htmlspecialchars($servico['titulo']) ?>
                        </div>
                        <div class="service-status status-<?= $servico['status'] ?>">
                            <?php
                            $status_icons = [
                                'concluido' => '✅',
                                'andamento' => '⏳',
                                'pendente' => '⭕'
                            ];
                            echo $status_icons[$servico['status']] . ' ' . ucfirst($servico['status']);
                            ?>
                        </div>
                    </div>
                    
                    <!-- Tags dos tipos de serviço -->
                    <div class="service-tipos">
                        <?php foreach (getTiposServico($servico) as $tipo): ?>
                        <span class="tipo-tag">
                            <?= $tipo['icon'] ?> <?= $tipo['label'] ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="team-info">
                        <div class="team-card">
                            <strong>👥 Equipe:</strong><br>
                            <?= htmlspecialchars($servico['equipe']) ?>
                        </div>
                        <div class="team-card">
                            <strong>🔧 Técnicos:</strong><br>
                            <?= htmlspecialchars($servico['tecnicos']) ?>
                        </div>
                        <div class="team-card">
                            <strong>📅 Data:</strong><br>
                            <?= formatDate($servico['data_execucao']) ?>
                        </div>
                        <div class="team-card">
                            <strong>📍 Local:</strong><br>
                            <?= htmlspecialchars($servico['local_servico']) ?>
                        </div>
                    </div>

                    <!-- Grid de 4 fotos -->
                    <div class="photos-container">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="photo-section">
                            <h4>📸 Foto <?= $i ?></h4>
                            <div class="photo-description">
                                <?= htmlspecialchars($servico["descricao_foto_$i"] ?: "Sem descrição") ?>
                            </div>
                            <?php if ($servico["foto_$i"] && file_exists($config['upload_dir'] . $servico["foto_$i"])): ?>
                                <img src="<?= $config['upload_dir'] . $servico["foto_$i"] ?>" 
                                     alt="Foto <?= $i ?> - <?= htmlspecialchars($servico['titulo']) ?>"
                                     onclick="openModal(this.src, 'Foto <?= $i ?> - <?= htmlspecialchars($servico["descricao_foto_$i"] ?: "Sem descrição") ?>')"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="no-photo">📷 Nenhuma foto enviada</div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <?php if ($servico['comentarios']): ?>
                    <div class="comments">
                        <h4>💬 Comentários e Observações</h4>
                        <div class="comments-text">
                            <?= nl2br(htmlspecialchars($servico['comentarios'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="text-align: right; margin-top: 15px; font-size: 0.9rem; color: #666;">
                        <em>Registrado em: <?= formatDateTime($servico['created_at']) ?></em>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Rodapé com informações -->
            <div style="text-align: center; margin-top: 50px; padding: 30px; background: #2c3e50; color: white; border-radius: 10px;">
                <h3>📋 Informações do Relatório</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div>
                        <strong>Criado em:</strong><br>
                        <?= formatDateTime($relatorio['created_at']) ?>
                    </div>
                    <div>
                        <strong>Última Atualização:</strong><br>
                        <?= formatDateTime($relatorio['updated_at']) ?>
                    </div>
                    <div>
                        <strong>ID do Relatório:</strong><br>
                        #<?= $relatorio['id'] ?>
                    </div>
                    <div>
                        <strong>Total de Fotos:</strong><br>
                        <?php
                        $total_fotos = 0;
                        foreach ($servicos as $servico) {
                            for ($i = 1; $i <= 4; $i++) {
                                if ($servico["foto_$i"] && file_exists($config['upload_dir'] . $servico["foto_$i"])) {
                                    $total_fotos++;
                                }
                            }
                        }
                        echo $total_fotos;
                        ?> foto(s)
                    </div>
                </div>
                
                <div style="margin-top: 30px; font-size: 0.9rem; opacity: 0.8;">
                    <p>Relatório gerado pelo <?= $config['site_name'] ?> v2.0</p>
                    <p>Visualizado em: <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ampliar imagens -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
        <div class="modal-caption" id="modalCaption"></div>
    </div>

    <?php endif; ?>

    <script>
        // ================================================
        // JAVASCRIPT PARA FUNCIONALIDADES EXTRAS
        // ================================================

        // Função para abrir modal de imagem
        function openModal(imageSrc, caption) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalCaption = document.getElementById('modalCaption');
            
            modal.style.display = 'block';
            modalImg.src = imageSrc;
            modalCaption.textContent = caption;
            
            // Fechar com ESC
            document.addEventListener('keydown', handleEscKey);
        }

        // Função para fechar modal
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            document.removeEventListener('keydown', handleEscKey);
        }

        // Handler para tecla ESC
        function handleEscKey(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        }

        // Fechar modal clicando fora da imagem
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Navegação entre fotos com setas do teclado
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('imageModal');
            if (modal.style.display === 'block') {
                const images = Array.from(document.querySelectorAll('.photo-section img'));
                const currentSrc = document.getElementById('modalImage').src;
                const currentIndex = images.findIndex(img => img.src === currentSrc);
                
                if (e.key === 'ArrowLeft' && currentIndex > 0) {
                    const prevImg = images[currentIndex - 1];
                    const caption = prevImg.alt;
                    openModal(prevImg.src, caption);
                } else if (e.key === 'ArrowRight' && currentIndex < images.length - 1) {
                    const nextImg = images[currentIndex + 1];
                    const caption = nextImg.alt;
                    openModal(nextImg.src, caption);
                }
            }
        });

        // Animações suaves ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Animar cards de resumo
            const summaryCards = document.querySelectorAll('.summary-card');
            summaryCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animar cards de tipos
            const tipoCards = document.querySelectorAll('.tipo-card');
            tipoCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                }, (index * 150) + 300);
            });

            // Animar seções de serviços
            const serviceSections = document.querySelectorAll('.service-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            serviceSections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'all 0.6s ease';
                observer.observe(section);
            });

            // Lazy loading para imagens
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => {
                img.addEventListener('load', function() {
                    this.style.opacity = '0';
                    this.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        this.style.opacity = '1';
                    }, 100);
                });
            });

            // Contador animado para estatísticas
            animateCounters();
        });

        // Função para animar contadores
        function animateCounters() {
            const counters = document.querySelectorAll('.summary-card h3, .tipo-card .tipo-numbers span');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                if (!isNaN(target) && target > 0) {
                    let current = 0;
                    const increment = target / 30; // 30 frames de animação
                    
                    const updateCounter = () => {
                        if (current < target) {
                            current += increment;
                            counter.textContent = Math.ceil(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target;
                        }
                    };
                    
                    setTimeout(updateCounter, 500); // Delay para começar após as outras animações
                }
            });
        }

        // Função para compartilhar relatório (se suportado pelo navegador)
        function shareReport() {
            if (navigator.share) {
                navigator.share({
                    title: 'Relatório de Fibra Óptica - <?= htmlspecialchars($relatorio['periodo']) ?>',
                    text: 'Confira este relatório de serviços de fibra óptica com <?= count($servicos) ?> serviços executados',
                    url: window.location.href
                });
            } else {
                // Fallback: copiar URL
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showNotification('Link copiado para a área de transferência!', 'success');
                });
            }
        }

        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#27ae60' : '#3498db'};
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 2000;
                font-weight: bold;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animar entrada
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remover após 3 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Adicionar botão de compartilhar se suportado
        if (navigator.share || navigator.clipboard) {
            const actionsDiv = document.querySelector('.actions');
            if (actionsDiv) {
                const shareBtn = document.createElement('button');
                shareBtn.className = 'btn';
                shareBtn.innerHTML = '🔗 Compartilhar';
                shareBtn.onclick = shareReport;
                actionsDiv.appendChild(shareBtn);
            }
        }

        // Função para exportar dados do relatório
        function exportData() {
            const data = {
                relatorio: <?= json_encode($relatorio, JSON_UNESCAPED_UNICODE) ?>,
                servicos: <?= json_encode($servicos, JSON_UNESCAPED_UNICODE) ?>,
                tipos_stats: <?= json_encode($tipos_stats, JSON_UNESCAPED_UNICODE) ?>,
                exported_at: new Date().toISOString(),
                total_fotos: document.querySelectorAll('.photo-section img').length
            };
            
            const dataStr = JSON.stringify(data, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = 'relatorio_<?= $relatorio['id'] ?>_detalhado_<?= date('Y-m-d') ?>.json';
            link.click();
            
            showNotification('Dados exportados com sucesso!', 'success');
        }

        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + E para exportar
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportData();
            }
            
            // Ctrl + P para imprimir
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Implementar zoom nas fotos com duplo clique
        document.querySelectorAll('.photo-section img').forEach(img => {
            img.addEventListener('dblclick', function() {
                if (this.style.transform === 'scale(2)') {
                    this.style.transform = 'scale(1)';
                } else {
                    this.style.transform = 'scale(2)';
                    this.style.transformOrigin = 'center';
                }
            });
        });

        // Log de informações para debug
        console.log('Relatório visualizado:', {
            id: <?= $relatorio['id'] ?>,
            periodo: '<?= htmlspecialchars($relatorio['periodo']) ?>',
            total_servicos: <?= count($servicos) ?>,
            total_fotos: document.querySelectorAll('.photo-section img').length,
            tipos_servicos: <?= json_encode($tipos_stats, JSON_UNESCAPED_UNICODE) ?>
        });

        // Adicionar indicador de progresso de carregamento
        window.addEventListener('load', function() {
            const images = document.querySelectorAll('img');
            let loadedImages = 0;
            
            if (images.length > 0) {
                images.forEach(img => {
                    if (img.complete) {
                        loadedImages++;
                    } else {
                        img.onload = () => {
                            loadedImages++;
                            updateProgress();
                        };
                    }
                });
                
                function updateProgress() {
                    const progress = (loadedImages / images.length) * 100;
                    if (progress === 100) {
                        showNotification('Todas as imagens foram carregadas!', 'success');
                    }
                }
                
                updateProgress();
            }
        });
    </script>
</body>
</html>