<?php
/**
 * ================================================
 * ARQUIVO: visualizar.php
 * DESCRIÇÃO: Visualização detalhada dos relatórios
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

    // Log da visualização
    logActivity('VISUALIZAR_RELATORIO', "Relatório $relatorio_id visualizado");

} catch (Exception $e) {
    $error = $e->getMessage();
    logActivity('ERRO_VISUALIZACAO', $error);
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
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
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .service-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: bold;
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

        .photos-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
        }

        .photo-section {
            text-align: center;
        }

        .photo-section h4 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .photo-section img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .photo-section img:hover {
            transform: scale(1.05);
        }

        .no-photo {
            width: 100%;
            height: 250px;
            background: #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-style: italic;
            border: 2px dashed #999;
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

        .empty-state img {
            max-width: 200px;
            opacity: 0.5;
            margin-bottom: 20px;
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

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #bbb;
        }

        @media (max-width: 768px) {
            .photos-container {
                grid-template-columns: 1fr;
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
                <a href="gerar_pdf_identico.php?id=<?= $relatorio['id'] ?>" class="btn btn-success" target="_blank">📄 Baixar PDF</a>
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
                            🔧 <?= htmlspecialchars($servico['titulo']) ?>
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

                    <div class="photos-container">
                        <div class="photo-section">
                            <h4>📸 Antes da Execução</h4>
                            <?php if ($servico['foto_antes'] && file_exists($config['upload_dir'] . $servico['foto_antes'])): ?>
                                <img src="<?= $config['upload_dir'] . $servico['foto_antes'] ?>" 
                                     alt="Foto Antes - <?= htmlspecialchars($servico['titulo']) ?>"
                                     onclick="openModal(this.src)"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="no-photo">📷 Nenhuma foto enviada</div>
                            <?php endif; ?>
                        </div>
                        <div class="photo-section">
                            <h4>📸 Após a Execução</h4>
                            <?php if ($servico['foto_depois'] && file_exists($config['upload_dir'] . $servico['foto_depois'])): ?>
                                <img src="<?= $config['upload_dir'] . $servico['foto_depois'] ?>" 
                                     alt="Foto Depois - <?= htmlspecialchars($servico['titulo']) ?>"
                                     onclick="openModal(this.src)"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="no-photo">📷 Nenhuma foto enviada</div>
                            <?php endif; ?>
                        </div>
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
                        <strong>Total de Páginas:</strong><br>
                        <?= ceil(count($servicos) / 3) ?> página(s)
                    </div>
                </div>
                
                <div style="margin-top: 30px; font-size: 0.9rem; opacity: 0.8;">
                    <p>Relatório gerado pelo <?= $config['site_name'] ?></p>
                    <p>Visualizado em: <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ampliar imagens -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <?php endif; ?>

    <script>
        // ================================================
        // JAVASCRIPT PARA FUNCIONALIDADES EXTRAS
        // ================================================

        // Função para abrir modal de imagem
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            modal.style.display = 'block';
            modalImg.src = imageSrc;
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
        }

        // Função para fechar modal
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
        }

        // Fechar modal clicando fora da imagem
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeModal();
            }
        }

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
        });

        // Função para compartilhar relatório (se suportado pelo navegador)
        function shareReport() {
            if (navigator.share) {
                navigator.share({
                    title: 'Relatório de Fibra Óptica - <?= htmlspecialchars($relatorio['periodo']) ?>',
                    text: 'Confira este relatório de serviços de fibra óptica',
                    url: window.location.href
                });
            } else {
                // Fallback: copiar URL
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link copiado para a área de transferência!');
                });
            }
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

        // Função para exportar dados (JSON)
        function exportData() {
            const data = {
                relatorio: <?= json_encode($relatorio, JSON_UNESCAPED_UNICODE) ?>,
                servicos: <?= json_encode($servicos, JSON_UNESCAPED_UNICODE) ?>,
                exported_at: new Date().toISOString()
            };
            
            const dataStr = JSON.stringify(data, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = 'relatorio_<?= $relatorio['id'] ?>_<?= date('Y-m-d') ?>.json';
            link.click();
        }

        // Auto-refresh da página se houver atualizações (opcional)
        let lastUpdate = '<?= $relatorio['updated_at'] ?>';
        
        function checkForUpdates() {
            fetch(`check_updates.php?id=<?= $relatorio_id ?>&last=${encodeURIComponent(lastUpdate)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.updated) {
                        const notification = document.createElement('div');
                        notification.innerHTML = `
                            <div style="position: fixed; top: 20px; right: 20px; background: #3498db; color: white; 
                                        padding: 15px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                                📊 Este relatório foi atualizado. 
                                <button onclick="location.reload()" style="background: white; color: #3498db; border: none; 
                                        padding: 5px 10px; border-radius: 4px; margin-left: 10px; cursor: pointer;">
                                    Recarregar
                                </button>
                                <button onclick="this.parentElement.remove()" style="background: transparent; color: white; 
                                        border: none; margin-left: 10px; cursor: pointer;">✕</button>
                            </div>
                        `;
                        document.body.appendChild(notification);
                    }
                })
                .catch(err => console.log('Check updates failed:', err));
        }

        // Verificar atualizações a cada 2 minutos
        setInterval(checkForUpdates, 120000);
    </script>
</body>
</html>