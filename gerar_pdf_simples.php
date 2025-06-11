<?php
/**
 * ================================================
 * ARQUIVO: gerar_pdf_simples.php (VERSÃO 2)
 * DESCRIÇÃO: Geração de PDF atualizada com novos campos
 * ================================================
 */

// Incluir configurações
require_once 'config.php';

// Verificar se foi passado ID do relatório
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID do relatório não fornecido');
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

    // Log da geração
    logActivity('GERAR_PDF', "PDF v2 gerado para relatório $relatorio_id");

} catch (Exception $e) {
    die('Erro: ' . $e->getMessage());
}

// Função para obter tipos de serviço
function getTiposServico($servico) {
    $tipos = [];
    if ($servico['tipo_reparo']) $tipos[] = '🔧 Reparo';
    if ($servico['tipo_construcao']) $tipos[] = '🏗️ Construção';
    if ($servico['tipo_ceo']) $tipos[] = '📡 CEO';
    if ($servico['tipo_cto']) $tipos[] = '📦 CTO';
    return $tipos;
}

// Contar total de fotos
$total_fotos = 0;
foreach ($servicos as $servico) {
    for ($i = 1; $i <= 4; $i++) {
        if ($servico["foto_$i"] && file_exists($config['upload_dir'] . $servico["foto_$i"])) {
            $total_fotos++;
        }
    }
}

// Gerar HTML para PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório <?= htmlspecialchars($relatorio['periodo']) ?></title>
    <style>
        @page {
            margin: 1.5cm;
            size: A4;
        }
        
        body {
            font-family: Arial, 'Helvetica Neue', sans-serif;
            line-height: 1.4;
            color: #333;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2980b9;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header h2 {
            color: #2980b9;
            font-size: 18px;
            font-weight: normal;
            margin: 0;
        }
        
        .info-section {
            margin-bottom: 25px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2980b9;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            width: 50%;
            padding: 10px;
            border: 1px solid #ddd;
            background: white;
            vertical-align: top;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .summary {
            background: #ecf0f1;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
            border-left: 4px solid #2980b9;
        }
        
        .summary h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .stats-table td {
            text-align: center;
            padding: 15px 10px;
            border: 1px solid #bdc3c7;
            background: white;
        }
        
        .stat-number {
            display: block;
            font-size: 20px;
            font-weight: bold;
            color: #2980b9;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 10px;
            color: #7f8c8d;
            text-transform: uppercase;
        }

        /* Estatísticas por tipo */
        .tipos-section {
            background: #e8f4fd;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }

        .tipos-grid {
            display: table;
            width: 100%;
            margin-top: 15px;
        }

        .tipos-row {
            display: table-row;
        }

        .tipo-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 15px 10px;
            background: white;
            border: 1px solid #ddd;
        }

        .tipo-icon {
            font-size: 18px;
            margin-bottom: 8px;
            display: block;
        }

        .tipo-numbers {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }
        
        .service {
            border: 1px solid #ddd;
            margin-bottom: 25px;
            padding: 20px;
            border-radius: 5px;
            page-break-inside: avoid;
            background: white;
        }
        
        .service-header {
            background: #3498db;
            color: white;
            padding: 15px 20px;
            margin: -20px -20px 20px -20px;
            border-radius: 5px 5px 0 0;
        }
        
        .service-title {
            font-size: 14px;
            font-weight: bold;
            float: left;
            max-width: 70%;
        }
        
        .service-status {
            float: right;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-concluido { background: #27ae60; }
        .status-andamento { background: #f39c12; }
        .status-pendente { background: #e74c3c; }

        /* Tags de tipos de serviço */
        .service-tipos {
            margin: 15px 0;
            clear: both;
        }

        .tipo-tag {
            display: inline-block;
            background: #fd79a8;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .service-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            clear: both;
        }
        
        .service-info-row {
            display: table-row;
        }
        
        .service-info-cell {
            display: table-cell;
            width: 50%;
            padding: 8px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .service-info-cell strong {
            display: block;
            color: #2c3e50;
            font-size: 9px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        /* Grid de 4 fotos */
        .fotos {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .fotos-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .fotos-row {
            display: table-row;
        }
        
        .foto-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px;
            vertical-align: top;
            border: 1px solid #ddd;
        }
        
        .foto-cell h4 {
            margin-bottom: 5px;
            color: #2c3e50;
            font-size: 10px;
        }

        .foto-description {
            font-size: 8px;
            color: #666;
            font-style: italic;
            margin-bottom: 8px;
            min-height: 12px;
        }
        
        .foto-cell img {
            max-width: 100%;
            height: auto;
            max-height: 120px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .no-foto {
            width: 100%;
            height: 80px;
            background: #f0f0f0;
            border: 1px dashed #ccc;
            border-radius: 3px;
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            color: #999;
            font-style: italic;
            font-size: 9px;
        }
        
        .comentarios {
            background: #f8f9fa;
            padding: 15px;
            border-left: 3px solid #74b9ff;
            margin-top: 15px;
            border-radius: 0 3px 3px 0;
        }
        
        .comentarios h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .comentarios-text {
            line-height: 1.6;
            color: #555;
            font-size: 10px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            page-break-inside: avoid;
        }
        
        .signatures {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        
        .signatures-row {
            display: table-row;
        }
        
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 10px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 8px;
            font-size: 10px;
        }
        
        .signature-cell strong {
            display: block;
            margin-bottom: 3px;
            color: #2c3e50;
            font-size: 9px;
        }
        
        .observacoes-gerais {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 3px solid #3498db;
            page-break-inside: avoid;
        }
        
        .observacoes-gerais h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .clearfix {
            clear: both;
        }

        .no-print {
            display: none;
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .service { page-break-inside: avoid; }
            .fotos { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <h1>📡 Relatório Mensal - Serviços de Fibra Óptica v2.0</h1>
            <h2><?= htmlspecialchars($relatorio['periodo']) ?></h2>
        </div>

        <!-- Informações Gerais -->
        <div class="info-section">
            <table class="info-grid">
                <tr class="info-row">
                    <td class="info-cell">
                        <div class="info-label">Supervisor Responsável:</div>
                        <?= htmlspecialchars($relatorio['supervisor']) ?>
                    </td>
                    <td class="info-cell">
                        <div class="info-label">Região de Cobertura:</div>
                        <?= htmlspecialchars($relatorio['regiao']) ?>
                    </td>
                </tr>
                <tr class="info-row">
                    <td class="info-cell">
                        <div class="info-label">Data do Relatório:</div>
                        <?= formatDate($relatorio['data_relatorio']) ?>
                    </td>
                    <td class="info-cell">
                        <div class="info-label">ID do Relatório:</div>
                        #<?= $relatorio['id'] ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Resumo Executivo -->
        <div class="summary">
            <h3>📊 Resumo Executivo</h3>
            <table class="stats-table">
                <tr>
                    <td>
                        <span class="stat-number"><?= $relatorio['total_servicos'] ?></span>
                        <div class="stat-label">Total de Serviços</div>
                    </td>
                    <td>
                        <span class="stat-number"><?= $relatorio['servicos_concluidos'] ?></span>
                        <div class="stat-label">Serviços Concluídos</div>
                    </td>
                    <td>
                        <span class="stat-number"><?= $relatorio['servicos_andamento'] ?></span>
                        <div class="stat-label">Em Andamento</div>
                    </td>
                    <td>
                        <span class="stat-number"><?= $relatorio['total_servicos'] > 0 ? round(($relatorio['servicos_concluidos'] / $relatorio['total_servicos']) * 100, 1) : 0 ?>%</span>
                        <div class="stat-label">Taxa de Conclusão</div>
                    </td>
                </tr>
            </table>
            
            <?php if ($relatorio['observacoes_gerais']): ?>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #bdc3c7;">
                <strong>Observações Gerais do Período:</strong><br>
                <?= nl2br(htmlspecialchars($relatorio['observacoes_gerais'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Estatísticas por Tipo de Serviço -->
        <?php if (!empty($tipos_stats) && ($tipos_stats['total_reparos'] > 0 || $tipos_stats['total_construcoes'] > 0 || $tipos_stats['total_ceos'] > 0 || $tipos_stats['total_ctos'] > 0)): ?>
        <div class="tipos-section">
            <h3>🔧 Estatísticas por Tipo de Serviço</h3>
            <div class="tipos-grid">
                <div class="tipos-row">
                    <?php if ($tipos_stats['total_reparos'] > 0): ?>
                    <div class="tipo-cell">
                        <span class="tipo-icon">🔧</span>
                        <strong>Reparos</strong>
                        <div class="tipo-numbers">
                            Total: <?= $tipos_stats['total_reparos'] ?><br>
                            Concluídos: <?= $tipos_stats['reparos_concluidos'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($tipos_stats['total_construcoes'] > 0): ?>
                    <div class="tipo-cell">
                        <span class="tipo-icon">🏗️</span>
                        <strong>Construções</strong>
                        <div class="tipo-numbers">
                            Total: <?= $tipos_stats['total_construcoes'] ?><br>
                            Concluídos: <?= $tipos_stats['construcoes_concluidas'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($tipos_stats['total_ceos'] > 0): ?>
                    <div class="tipo-cell">
                        <span class="tipo-icon">📡</span>
                        <strong>CEOs</strong>
                        <div class="tipo-numbers">
                            Total: <?= $tipos_stats['total_ceos'] ?><br>
                            Concluídos: <?= $tipos_stats['ceos_concluidos'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($tipos_stats['total_ctos'] > 0): ?>
                    <div class="tipo-cell">
                        <span class="tipo-icon">📦</span>
                        <strong>CTOs</strong>
                        <div class="tipo-numbers">
                            Total: <?= $tipos_stats['total_ctos'] ?><br>
                            Concluídos: <?= $tipos_stats['ctos_concluidos'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Serviços -->
        <?php if (empty($servicos)): ?>
            <div style="text-align: center; padding: 40px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">
                <h3>📋 Nenhum Serviço Registrado</h3>
                <p>Este relatório não possui serviços cadastrados para o período especificado.</p>
            </div>
        <?php else: ?>
            <h2 style="color: #2c3e50; margin-bottom: 25px; text-align: center; font-size: 18px;">
                📋 Detalhamento dos Serviços Executados (<?= count($servicos) ?>)
            </h2>
            
            <?php foreach ($servicos as $index => $servico): ?>
            <div class="service">
                <div class="service-header">
                    <div class="service-title">
                        <?= ($index + 1) . '. ' . htmlspecialchars($servico['titulo']) ?>
                    </div>
                    <div class="service-status status-<?= $servico['status'] ?>">
                        <?php
                        $status_texts = [
                            'concluido' => 'Concluído',
                            'andamento' => 'Em Andamento',
                            'pendente' => 'Pendente'
                        ];
                        echo $status_texts[$servico['status']];
                        ?>
                    </div>
                    <div class="clearfix"></div>
                </div>

                <!-- Tags dos tipos de serviço -->
                <div class="service-tipos">
                    <?php foreach (getTiposServico($servico) as $tipo): ?>
                    <span class="tipo-tag"><?= $tipo ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="service-info">
                    <div class="service-info-row">
                        <div class="service-info-cell">
                            <strong>Equipe Responsável:</strong>
                            <?= htmlspecialchars($servico['equipe']) ?>
                        </div>
                        <div class="service-info-cell">
                            <strong>Técnicos Envolvidos:</strong>
                            <?= htmlspecialchars($servico['tecnicos']) ?>
                        </div>
                    </div>
                    <div class="service-info-row">
                        <div class="service-info-cell">
                            <strong>Data de Execução:</strong>
                            <?= formatDate($servico['data_execucao']) ?>
                        </div>
                        <div class="service-info-cell">
                            <strong>Local do Serviço:</strong>
                            <?= htmlspecialchars($servico['local_servico']) ?>
                        </div>
                    </div>
                </div>

                <!-- Grid de 4 fotos -->
                <div class="fotos">
                    <div class="fotos-grid">
                        <div class="fotos-row">
                            <?php for ($i = 1; $i <= 2; $i++): ?>
                            <div class="foto-cell">
                                <h4>📸 Foto <?= $i ?></h4>
                                <div class="foto-description">
                                    <?= htmlspecialchars($servico["descricao_foto_$i"] ?: "Sem descrição") ?>
                                </div>
                                <?php if ($servico["foto_$i"] && file_exists($config['upload_dir'] . $servico["foto_$i"])): ?>
                                    <img src="<?= $config['upload_dir'] . $servico["foto_$i"] ?>" alt="Foto <?= $i ?> do serviço">
                                <?php else: ?>
                                    <div class="no-foto">Foto não disponível</div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div class="fotos-row">
                            <?php for ($i = 3; $i <= 4; $i++): ?>
                            <div class="foto-cell">
                                <h4>📸 Foto <?= $i ?></h4>
                                <div class="foto-description">
                                    <?= htmlspecialchars($servico["descricao_foto_$i"] ?: "Sem descrição") ?>
                                </div>
                                <?php if ($servico["foto_$i"] && file_exists($config['upload_dir'] . $servico["foto_$i"])): ?>
                                    <img src="<?= $config['upload_dir'] . $servico["foto_$i"] ?>" alt="Foto <?= $i ?> do serviço">
                                <?php else: ?>
                                    <div class="no-foto">Foto não disponível</div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <?php if ($servico['comentarios']): ?>
                <div class="comentarios">
                    <h4>💬 Observações Técnicas e Comentários</h4>
                    <div class="comentarios-text">
                        <?= nl2br(htmlspecialchars($servico['comentarios'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="text-align: right; margin-top: 15px; font-size: 9px; color: #666; font-style: italic;">
                    Serviço registrado em: <?= formatDateTime($servico['created_at']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Rodapé com Assinaturas -->
        <div class="footer">
            <h3>✍️ Aprovações e Assinaturas</h3>
            <div class="signatures">
                <div class="signatures-row">
                    <div class="signature-cell">
                        <div class="signature-line">
                            <strong>Supervisor de Campo</strong>
                            <?= htmlspecialchars($relatorio['supervisor']) ?>
                        </div>
                    </div>
                    <div class="signature-cell">
                        <div class="signature-line">
                            <strong>Coordenador Técnico</strong>
                            _________________________
                        </div>
                    </div>
                    <div class="signature-cell">
                        <div class="signature-line">
                            <strong>Gerente de Operações</strong>
                            _________________________
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #bdc3c7; font-size: 11px; color: #7f8c8d;">
                <p><strong>Relatório gerado em:</strong> <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
                <p><strong>Sistema:</strong> <?= $config['site_name'] ?> v2.0</p>
                <p><strong>Total de fotos:</strong> <?= $total_fotos ?> foto(s) anexadas</p>
                <p><strong>Arquivo:</strong> relatorio_v2_<?= $relatorio['id'] ?>_<?= date('Y-m-d') ?>.pdf</p>
            </div>
        </div>
    </div>

    <!-- JavaScript para funcionalidades -->
    <script>
        // Auto-print quando aberto (para conversão em PDF)
        document.addEventListener('DOMContentLoaded', function() {
            // Aguardar carregamento das imagens antes de imprimir
            let images = document.querySelectorAll('img');
            let loadedImages = 0;
            
            if (images.length === 0) {
                // Se não há imagens, imprimir imediatamente
                setTimeout(() => window.print(), 500);
            } else {
                images.forEach(img => {
                    if (img.complete) {
                        loadedImages++;
                    } else {
                        img.onload = img.onerror = () => {
                            loadedImages++;
                            if (loadedImages === images.length) {
                                setTimeout(() => window.print(), 500);
                            }
                        };
                    }
                });
                
                if (loadedImages === images.length) {
                    setTimeout(() => window.print(), 500);
                }
                
                // Fallback: imprimir após 3 segundos mesmo se imagens não carregarem
                setTimeout(() => window.print(), 3000);
            }
        });
        
        // Melhorar qualidade de impressão
        window.addEventListener('beforeprint', function() {
            document.querySelectorAll('img').forEach(img => {
                img.style.printColorAdjust = 'exact';
                img.style.webkitPrintColorAdjust = 'exact';
            });
        });
        
        // Log para debug
        console.log('PDF v2 gerado para relatório ID:', <?= $relatorio['id'] ?>);
        console.log('Total de serviços:', <?= count($servicos) ?>);
        console.log('Total de fotos:', <?= $total_fotos ?>);
        console.log('Estatísticas por tipo:', <?= json_encode($tipos_stats, JSON_UNESCAPED_UNICODE) ?>);
    </script>

    <!-- Instruções para o usuário -->
    <div class="no-print" style="position: fixed; top: 10px; right: 10px; background: #e74c3c; color: white; padding: 10px; border-radius: 5px; z-index: 1000; font-size: 12px; font-family: Arial;">
        <strong>📄 Para salvar como PDF:</strong><br>
        1. Use Ctrl+P (imprimir)<br>
        2. Escolha "Salvar como PDF"<br>
        3. Clique em "Salvar"<br>
        <small style="opacity: 0.8;">Sistema v2.0 - <?= count($servicos) ?> serviços, <?= $total_fotos ?> fotos</small>
    </div>
</body>
</html>
<?php
$html_content = ob_get_clean();

// ================================================
// DEFINIR CABEÇALHOS PARA PDF
// ================================================

$filename = 'relatorio_v2_' . $relatorio['id'] . '_' . date('Y-m-d') . '.pdf';

// Cabeçalhos que facilitam a conversão para PDF
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Retornar o conteúdo HTML
echo $html_content;
?>