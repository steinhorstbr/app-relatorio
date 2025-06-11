<?php
/**
 * ================================================
 * ARQUIVO: gerar_pdf_real.php
 * DESCRIÇÃO: Geração de PDF real usando DomPDF
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

    // Buscar serviços
    $stmt = $pdo->prepare("SELECT * FROM servicos WHERE relatorio_id = ? ORDER BY data_execucao DESC, created_at DESC");
    $stmt->execute([$relatorio_id]);
    $servicos = $stmt->fetchAll();

    // Log da geração
    logActivity('GERAR_PDF', "PDF gerado para relatório $relatorio_id");

} catch (Exception $e) {
    die('Erro: ' . $e->getMessage());
}

// ================================================
// GERAR HTML PARA CONVERSÃO EM PDF
// ================================================

// Função para converter imagem para base64 (para embed no PDF)
function imageToBase64($imagePath) {
    if (!file_exists($imagePath)) {
        return null;
    }
    
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        return null;
    }
    
    $imageData = file_get_contents($imagePath);
    $base64 = base64_encode($imageData);
    $mimeType = $imageInfo['mime'];
    
    return "data:$mimeType;base64,$base64";
}

// Gerar o HTML do PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório <?= htmlspecialchars($relatorio['periodo']) ?></title>
    <style>
        @page {
            margin: 2cm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            font-size: 11px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 20px;
        }
        
        .header h2 {
            color: #3498db;
            font-size: 16px;
            font-weight: normal;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            width: 50%;
            padding: 8px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            vertical-align: top;
        }
        
        .info-cell strong {
            color: #2c3e50;
            display: block;
            margin-bottom: 3px;
            font-size: 9px;
            text-transform: uppercase;
        }
        
        .summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 3px solid #3498db;
        }
        
        .summary h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-top: 10px;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .stat-cell {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
        }
        
        .stat-number {
            font-size: 16px;
            font-weight: bold;
            color: #3498db;
            display: block;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        
        .service {
            border: 1px solid #ddd;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            page-break-inside: avoid;
            background: white;
        }
        
        .service-header {
            background: #3498db;
            color: white;
            padding: 10px 15px;
            margin: -15px -15px 15px -15px;
            border-radius: 5px 5px 0 0;
        }
        
        .service-title {
            font-size: 12px;
            font-weight: bold;
            float: left;
        }
        
        .status {
            float: right;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-concluido { background: #27ae60; }
        .status-andamento { background: #f39c12; }
        .status-pendente { background: #e74c3c; }
        
        .service-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
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
        }
        
        .fotos {
            margin: 15px 0;
            page-break-inside: avoid;
        }
        
        .fotos-table {
            display: table;
            width: 100%;
        }
        
        .fotos-row {
            display: table-row;
        }
        
        .foto-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 5px;
            vertical-align: top;
        }
        
        .foto-cell h4 {
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 10px;
        }
        
        .foto-cell img {
            max-width: 100%;
            height: auto;
            max-height: 150px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .no-foto {
            width: 100%;
            height: 100px;
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
            padding: 12px;
            border-left: 3px solid #74b9ff;
            margin-top: 15px;
            border-radius: 0 3px 3px 0;
        }
        
        .comentarios h4 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório Mensal - Serviços de Fibra Óptica</h1>
        <h2><?= htmlspecialchars($relatorio['periodo']) ?></h2>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <strong>Supervisor Responsável:</strong>
                <?= htmlspecialchars($relatorio['supervisor']) ?>
            </div>
            <div class="info-cell">
                <strong>Região de Cobertura:</strong>
                <?= htmlspecialchars($relatorio['regiao']) ?>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <strong>Data do Relatório:</strong>
                <?= formatDate($relatorio['data_relatorio']) ?>
            </div>
            <div class="info-cell">
                <strong>Período de Referência:</strong>
                <?= htmlspecialchars($relatorio['periodo']) ?>
            </div>
        </div>
    </div>

    <div class="summary">
        <h3>Resumo Executivo</h3>
        <div class="summary-stats">
            <div class="summary-row">
                <div class="stat-cell">
                    <span class="stat-number"><?= $relatorio['total_servicos'] ?></span>
                    <div class="stat-label">Total de Serviços</div>
                </div>
                <div class="stat-cell">
                    <span class="stat-number"><?= $relatorio['servicos_concluidos'] ?></span>
                    <div class="stat-label">Serviços Concluídos</div>
                </div>
                <div class="stat-cell">
                    <span class="stat-number"><?= $relatorio['servicos_andamento'] ?></span>
                    <div class="stat-label">Em Andamento</div>
                </div>
                <div class="stat-cell">
                    <span class="stat-number"><?= $relatorio['total_servicos'] > 0 ? round(($relatorio['servicos_concluidos'] / $relatorio['total_servicos']) * 100, 1) : 0 ?>%</span>
                    <div class="stat-label">Taxa de Conclusão</div>
                </div>
            </div>
        </div>
        
        <?php if ($relatorio['observacoes_gerais']): ?>
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <strong>Observações Gerais do Período:</strong><br>
            <?= nl2br(htmlspecialchars($relatorio['observacoes_gerais'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($servicos)): ?>
        <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 5px; color: #666;">
            <h3>Nenhum Serviço Registrado</h3>
            <p>Este relatório não possui serviços cadastrados para o período especificado.</p>
        </div>
    <?php else: ?>
        <h2 style="color: #2c3e50; margin-bottom: 20px; text-align: center; font-size: 14px;">
            Detalhamento dos Serviços Executados
        </h2>
        
        <?php foreach ($servicos as $index => $servico): ?>
        <div class="service">
            <div class="service-header">
                <div class="service-title">
                    <?= ($index + 1) . '. ' . htmlspecialchars($servico['titulo']) ?>
                </div>
                <div class="status status-<?= $servico['status'] ?>">
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

            <?php if ($servico['foto_antes'] || $servico['foto_depois']): ?>
            <div class="fotos">
                <div class="fotos-table">
                    <div class="fotos-row">
                        <div class="foto-cell">
                            <h4>Situação Inicial (Antes)</h4>
                            <?php if ($servico['foto_antes'] && file_exists($config['upload_dir'] . $servico['foto_antes'])): ?>
                                <?php $base64_antes = imageToBase64($config['upload_dir'] . $servico['foto_antes']); ?>
                                <?php if ($base64_antes): ?>
                                    <img src="<?= $base64_antes ?>" alt="Foto antes do serviço">
                                <?php else: ?>
                                    <div class="no-foto">Erro ao carregar imagem</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="no-foto">Foto não disponível</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="foto-cell">
                            <h4>Situação Final (Depois)</h4>
                            <?php if ($servico['foto_depois'] && file_exists($config['upload_dir'] . $servico['foto_depois'])): ?>
                                <?php $base64_depois = imageToBase64($config['upload_dir'] . $servico['foto_depois']); ?>
                                <?php if ($base64_depois): ?>
                                    <img src="<?= $base64_depois ?>" alt="Foto depois do serviço">
                                <?php else: ?>
                                    <div class="no-foto">Erro ao carregar imagem</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="no-foto">Foto não disponível</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($servico['comentarios']): ?>
            <div class="comentarios">
                <h4>Observações Técnicas e Comentários</h4>
                <div class="comentarios-text">
                    <?= nl2br(htmlspecialchars($servico['comentarios'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="text-align: right; margin-top: 15px; font-size: 9px; color: #666; font-style: italic;">
                Registrado em: <?= formatDateTime($servico['created_at']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="footer">
        <h3>Aprovações e Assinaturas</h3>
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
        
        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 9px;">
            <p><strong>Relatório gerado em:</strong> <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
            <p><strong>ID do Relatório:</strong> #<?= $relatorio['id'] ?></p>
            <p><strong>Sistema:</strong> <?= $config['site_name'] ?></p>
        </div>
    </div>
</body>
</html>
<?php
$html_content = ob_get_clean();

// ================================================
// USAR DOMPDF PARA GERAR PDF REAL
// ================================================

// Verificar se DomPDF está disponível
if (!class_exists('Dompdf\Dompdf')) {
    // Se não tiver DomPDF, usar biblioteca alternativa simples
    // ou forçar download do HTML
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="relatorio_' . $relatorio['id'] . '_' . date('Y-m-d') . '.html"');
    header('Content-Description: File Transfer');
    echo $html_content;
    exit;
}

// Usar DomPDF se disponível
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html_content);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'relatorio_' . $relatorio['id'] . '_' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, array("Attachment" => true));
?>