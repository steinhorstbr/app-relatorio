<?php
/**
 * ================================================
 * ARQUIVO: config.php
 * DESCRIÇÃO: Configurações do Sistema de Relatórios
 * ================================================
 * 
 * INSTRUÇÕES PARA HOSTGATOR:
 * 1. Altere as configurações do banco de dados abaixo
 * 2. As informações do banco estão no painel cPanel
 * 3. Mantenha este arquivo seguro (não compartilhe senhas)
 */

// ================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ================================================
$config = [
    // ALTERE ESTAS INFORMAÇÕES CONFORME SEU PAINEL HOSTGATOR
    'db_host' => 'localhost',                    // Geralmente 'localhost' na HostGator
    'db_name' => 'infot135_relatorios', // Formato: usuario_nomedobanco
    'db_user' => 'infot135_relatorios',            // Usuário MySQL criado no cPanel
    'db_pass' => 'infot135_relatorios',              // Senha do usuário MySQL
    
    // ================================================
    // CONFIGURAÇÕES DE UPLOAD
    // ================================================
    'upload_dir' => 'uploads/',                  // Diretório para fotos (não alterar)
    'max_file_size' => 5 * 1024 * 1024,        // 5MB por arquivo
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'], // Tipos permitidos
    
    // ================================================
    // CONFIGURAÇÕES GERAIS
    // ================================================
    'site_name' => 'Sistema de Relatórios - Fibra Óptica',
    'timezone' => 'America/Sao_Paulo',
    'date_format' => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i:s',
    
    // ================================================
    // CONFIGURAÇÕES DE SEGURANÇA
    // ================================================
    'session_timeout' => 3600, // 1 hora em segundos
    'max_login_attempts' => 3,
    'password_min_length' => 6,
];

// ================================================
// CONFIGURAR TIMEZONE
// ================================================
date_default_timezone_set($config['timezone']);

// ================================================
// CONFIGURAÇÕES DE ERRO (APENAS PARA DESENVOLVIMENTO)
// ================================================
// Descomente as linhas abaixo apenas para debug
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// ================================================
// CRIAR DIRETÓRIO DE UPLOADS
// ================================================
if (!file_exists($config['upload_dir'])) {
    if (!mkdir($config['upload_dir'], 0755, true)) {
        die('Erro: Não foi possível criar o diretório de uploads. Verifique as permissões.');
    }
}

// ================================================
// CONEXÃO COM BANCO DE DADOS
// ================================================
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
} catch (PDOException $e) {
    // Em produção, não mostrar detalhes do erro
    die('Erro de conexão com o banco de dados. Verifique as configurações.');
    
    // Para debug, descomente a linha abaixo:
    // die('Erro de conexão: ' . $e->getMessage());
}

// ================================================
// FUNÇÕES AUXILIARES
// ================================================

/**
 * Formatar data no padrão brasileiro
 */
function formatDate($date, $format = null) {
    global $config;
    if (empty($date) || $date === '0000-00-00') return '-';
    
    $format = $format ?: $config['date_format'];
    return date($format, strtotime($date));
}

/**
 * Formatar data e hora no padrão brasileiro
 */
function formatDateTime($datetime, $format = null) {
    global $config;
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') return '-';
    
    $format = $format ?: $config['datetime_format'];
    return date($format, strtotime($datetime));
}

/**
 * Upload de arquivo com validações
 */
function uploadFile($file, $config) {
    // Verificar se arquivo foi enviado
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false, 
            'error' => 'Erro no upload do arquivo.'
        ];
    }
    
    // Verificar tamanho
    if ($file['size'] > $config['max_file_size']) {
        $max_mb = round($config['max_file_size'] / 1024 / 1024, 1);
        return [
            'success' => false, 
            'error' => "Arquivo muito grande. Máximo {$max_mb}MB."
        ];
    }
    
    // Verificar extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $config['allowed_extensions'])) {
        $allowed = implode(', ', $config['allowed_extensions']);
        return [
            'success' => false, 
            'error' => "Tipo de arquivo não permitido. Permitidos: {$allowed}"
        ];
    }
    
    // Verificar se é realmente uma imagem
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return [
            'success' => false, 
            'error' => 'Arquivo não é uma imagem válida.'
        ];
    }
    
    // Gerar nome único
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $full_path = $config['upload_dir'] . $filename;
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        // Redimensionar se necessário (opcional)
        resizeImage($full_path, 800, 600); // máximo 800x600px
        
        return [
            'success' => true, 
            'filename' => $filename,
            'full_path' => $full_path
        ];
    }
    
    return [
        'success' => false, 
        'error' => 'Erro ao salvar o arquivo.'
    ];
}

/**
 * Redimensionar imagem para otimizar espaço
 */
function resizeImage($file_path, $max_width = 800, $max_height = 600, $quality = 85) {
    $image_info = getimagesize($file_path);
    if (!$image_info) return false;
    
    list($orig_width, $orig_height, $image_type) = $image_info;
    
    // Se já está dentro do tamanho, não redimensionar
    if ($orig_width <= $max_width && $orig_height <= $max_height) {
        return true;
    }
    
    // Calcular novas dimensões mantendo proporção
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    $new_width = round($orig_width * $ratio);
    $new_height = round($orig_height * $ratio);
    
    // Criar nova imagem
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Carregar imagem original
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($file_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($file_path);
            break;
        default:
            return false;
    }
    
    // Redimensionar
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
    
    // Salvar
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $file_path, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $file_path, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $file_path);
            break;
    }
    
    // Limpar memória
    imagedestroy($source);
    imagedestroy($new_image);
    
    return true;
}

/**
 * Deletar arquivo do servidor
 */
function deleteFile($filename, $config) {
    if (empty($filename)) return false;
    
    $full_path = $config['upload_dir'] . $filename;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}

/**
 * Sanitizar string para evitar XSS
 */
function sanitizeString($string) {
    return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gerar token CSRF (para futuras implementações de segurança)
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log de atividades (opcional)
 */
function logActivity($action, $details = '') {
    $log_file = 'logs/activity.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = "[{$timestamp}] IP: {$ip} | Action: {$action} | Details: {$details} | User-Agent: {$user_agent}\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Verificar se é requisição AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Resposta JSON padronizada
 */
function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ================================================
// CONSTANTES ÚTEIS
// ================================================
define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/');
define('UPLOAD_URL', BASE_URL . $config['upload_dir']);

// ================================================
// VERIFICAÇÕES INICIAIS
// ================================================

// Verificar se diretório uploads tem permissão de escrita
if (!is_writable($config['upload_dir'])) {
    error_log("Aviso: Diretório de uploads não tem permissão de escrita: " . $config['upload_dir']);
}

// Criar arquivo .htaccess no diretório uploads para segurança
$htaccess_file = $config['upload_dir'] . '.htaccess';
if (!file_exists($htaccess_file)) {
    $htaccess_content = "# Proteger diretório de uploads\n";
    $htaccess_content .= "Options -Indexes\n";
    $htaccess_content .= "DirectoryIndex index.html\n\n";
    $htaccess_content .= "# Bloquear execução de scripts PHP\n";
    $htaccess_content .= "<Files *.php>\n";
    $htaccess_content .= "    Require all denied\n";
    $htaccess_content .= "</Files>\n\n";
    $htaccess_content .= "# Permitir apenas imagens\n";
    $htaccess_content .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
    $htaccess_content .= "    Require all granted\n";
    $htaccess_content .= "</FilesMatch>\n";
    
    file_put_contents($htaccess_file, $htaccess_content);
}

// Criar arquivo index.html vazio no uploads para evitar listagem
$index_file = $config['upload_dir'] . 'index.html';
if (!file_exists($index_file)) {
    file_put_contents($index_file, '<!-- Acesso negado -->');
}

// ================================================
// INFORMAÇÕES PARA DEBUG (REMOVER EM PRODUÇÃO)
// ================================================
/*
Para testar a conexão, descomente este bloco:

try {
    $test_query = $pdo->query("SELECT 1");
    echo "<!-- Conexão com banco de dados: OK -->\n";
} catch (Exception $e) {
    echo "<!-- Erro de conexão: " . $e->getMessage() . " -->\n";
}
*/
?>