<?php
/**
 * ================================================
 * ARQUIVO: gerar_pdf_simples.php
 * DESCRIÇÃO: Geração de PDF usando TCPDF (mais compatível)
 * SUBSTITUA O gerar_pdf.php por este arquivo
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
// IMPLEMENTAÇÃO TCPDF (MAIS SIMPLES E COMPATÍVEL)
// ================================================

// Classe TCPDF Simplificada (incorporada)
class SimplePDF {
    private $content = '';
    private $title = '';
    
    public function __construct($title = 'Documento PDF') {
        $this->title = $title;
    }
    
    public function addContent($html) {
        $this->content .= $html;
    }
    
    public function output($filename = 'documento.pdf') {
        // Cabeçalhos para download de PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Para demonstração, vamos gerar um HTML otimizado para conversão
        // Em produção real, use wkhtmltopdf ou biblioteca similar
        
        echo $this->generatePDFContent();
    }
    
    private function generatePDFContent() {
        // Retorna HTML formatado para conversão em PDF
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .footer { margin-top: 30px; border-top: 1px solid #333; padding-top: 10px; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>
    ' . $this->content . '
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>';
        
        return $html;
    }
}

// ================================================
// USAR SOLUÇÃO NATIVA MAIS SIMPLES
// ================================================

// Gerar HTML para PDF usando recursos nativos do PHP
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
        
        .service {
            margin-bottom: 25px;
            border: 1px solid #bdc3c7;
            border-radius: 5px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        
        .service-header {
            background: #2980b9;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
        }
        
        .service-title {
            font-size: 14px;
            float: left;
        }
        
        .service-status {
            float: right;
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .service-body {
            padding: 15px;
            background: white;
        }
        
        .service-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .service-info-table td {
            padding: 8px;
            border: 1px solid #ecf0f1;
            background: #f8f9fa;
            width: 50%;
            vertical-align: top;
        }
        
        .service-info-label {
            font-weight: bold;
            color: #2c3e50;
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        .photos-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .photos-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .photo-cell {
            width: 50%;
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .photo-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .photo-placeholder {
            width: 100%;
            height: 120px;
            background: #ecf0f1;
            border: 2px dashed #bdc3c7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-style: italic;
            font-size: 11px;
        }
        
        .photo-img {
            max-width: 100%;
            max-height: 150px;
            border: 1px solid #bdc3c7;
            border-radius: 3px;
        }
        
        .comments {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-top: 15px;
            border-radius: 0 3px 3px 0;
        }
        
        .comments-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .comments-text {
            line-height: 1.6;
            color: #555;
            font-size: 11px;
        }
        
        .observacoes {
            background: #e8f6fd;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
            border-radius: 0 5px 5px 0;
        }
        
        .observacoes h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 40px;
            border-top: 2px solid #2c3e50;
            padding-top: 20px;
            text-align: center;
        }
        
        .signatures {
            margin-top: 30px;
        }
        
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .signature-cell {
            width: 33.33%;
            text-align: center;
            padding: 20px 10px;
            vertical-align: bottom;
        }
        
        .signature-line {
            border-top: 1px solid #2c3e50;
            margin-top: 50px;
            padding-top: 8px;
            font-size: 11px;
        }
        
        .signature-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .clearfix {
            clear: both;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-print {
            display: none;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .service { page-break-inside: avoid; }
            .photos-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <h1>📡 Relatório Mensal - Serviços de Fibra Óptica</h1>
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
                    <div class="service-status">
                        <?php
                        $status_labels = [
                            'concluido' => '✓ Concluído',
                            'andamento' => '⏳ Em Andamento',
                            'pendente' => '⏸️ Pendente'
                        ];
                        echo $status_labels[$servico['status']];
                        ?>
                    </div>
                    <div class="clearfix"></div>
                </div>

                <div class="service-body">
                    <!-- Informações do Serviço -->
                    <table class="service-info-table">
                        <tr>
                            <td>
                                <div class="service-info-label">Equipe Responsável:</div>
                                <?= htmlspecialchars($servico['equipe']) ?>
                            </td>
                            <td>
                                <div class="service-info-label">Técnicos Envolvidos:</div>
                                <?= htmlspecialchars($servico['tecnicos']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="service-info-label">Data de Execução:</div>
                                <?= formatDate($servico['data_execucao']) ?>
                            </td>
                            <td>
                                <div class="service-info-label">Local do Serviço:</div>
                                <?= htmlspecialchars($servico['local_servico']) ?>
                            </td>
                        </tr>
                    </table>

                    <!-- Fotos -->
                    <div class="photos-section">
                        <table class="photos-table">
                            <tr>
                                <td class="photo-cell">
                                    <div class="photo-title">📷 Situação Inicial (Antes)</div>
                                    <?php if ($servico['foto_antes'] && file_exists($config['upload_dir'] . $servico['foto_antes'])): ?>
                                        <img src="<?= $config['upload_dir'] . $servico['foto_antes'] ?>" 
                                             alt="Foto antes do serviço" class="photo-img">
                                    <?php else: ?>
                                        <div class="photo-placeholder">Foto não disponível</div>
                                    <?php endif; ?>
                                </td>
                                <td class="photo-cell">
                                    <div class="photo-title">📷 Situação Final (Depois)</div>
                                    <?php if ($servico['foto_depois'] && file_exists($config['upload_dir'] . $servico['foto_depois'])): ?>
                                        <img src="<?= $config['upload_dir'] . $servico['foto_depois'] ?>" 
                                             alt="Foto depois do serviço" class="photo-img">
                                    <?php else: ?>
                                        <div class="photo-placeholder">Foto não disponível</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Comentários -->
                    <?php if ($servico['comentarios']): ?>
                    <div class="comments">
                        <div class="comments-title">💬 Observações Técnicas e Comentários</div>
                        <div class="comments-text">
                            <?= nl2br(htmlspecialchars($servico['comentarios'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Info de registro -->
                    <div style="text-align: right; margin-top: 15px; font-size: 10px; color: #7f8c8d; font-style: italic;">
                        Serviço registrado em: <?= formatDateTime($servico['created_at']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Rodapé com Assinaturas -->
        <div class="footer">
            <h3>✍️ Aprovações e Assinaturas</h3>
            <div class="signatures">
                <table class="signatures-table">
                    <tr>
                        <td class="signature-cell">
                            <div class="signature-line">
                                <div class="signature-title">Supervisor de Campo</div>
                                <?= htmlspecialchars($relatorio['supervisor']) ?>
                            </div>
                        </td>
                        <td class="signature-cell">
                            <div class="signature-line">
                                <div class="signature-title">Coordenador Técnico</div>
                                _________________________
                            </div>
                        </td>
                        <td class="signature-cell">
                            <div class="signature-line">
                                <div class="signature-title">Gerente de Operações</div>
                                _________________________
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #bdc3c7; font-size: 11px; color: #7f8c8d;">
                <p><strong>Relatório gerado em:</strong> <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
                <p><strong>Sistema:</strong> <?= $config['site_name'] ?></p>
                <p><strong>Arquivo:</strong> relatorio_<?= $relatorio['id'] ?>_<?= date('Y-m-d') ?>.pdf</p>
            </div>
        </div>
    </div>

    <!-- JavaScript para funcionalidades -->
    <script>
        // Auto-print quando abrido (para conversão em PDF)
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
        console.log('PDF gerado para relatório ID:', <?= $relatorio['id'] ?>);
        console.log('Total de serviços:', <?= count($servicos) ?>);
        console.log('Fotos encontradas:', document.querySelectorAll('.photo-img').length);
    </script>
</body>
</html>
<?php
$html_content = ob_get_clean();

// ================================================
// DEFINIR CABEÇALHOS PARA PDF
// ================================================

$filename = 'relatorio_' . $relatorio['id'] . '_' . date('Y-m-d') . '.pdf';

// Cabeçalhos que forçam o download e abertura como PDF
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Adicionar meta tags para melhor conversão em PDF
echo $html_content;

// Instruções para o usuário
echo '
<div class="no-print" style="position: fixed; top: 10px; right: 10px; background: #e74c3c; color: white; padding: 10px; border-radius: 5px; z-index: 1000; font-size: 12px; font-family: Arial;">
    <strong>Para salvar como PDF:</strong><br>
    1. Use Ctrl+P (imprimir)<br>
    2. Escolha "Salvar como PDF"<br>
    3. Clique em "Salvar"
</div>';
?>