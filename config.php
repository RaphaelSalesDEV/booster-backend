<?php
/**
 * Configurações para Railway
 * Railway pega as variáveis de ambiente automaticamente
 */

// Configurações do Banco de Dados (Railway gera)
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');

// Configurações da API RevisionSMM
define('API_KEY', getenv('API_KEY') ?: 'bc1q9shqng58tykf3kmwukzez6h5xne559f5gfu2l8');
define('SERVICE_ID', getenv('SERVICE_ID') ?: 4119);

// Configurações da API Asaas
define('ASAAS_API_KEY', getenv('ASAAS_API_KEY') ?: '$aact_prod_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6Ojk5Mjg1MDE5LTA5ODUtNDk0Yi1hZmE2LTNiMzFkMWRlNjBhZjo6JGFhY2hfNWYwMjQxMTItOTVlOC00ODVmLWIxNzEtOWZhYzE1Njg5MWNm');
define('ASAAS_API_URL', 'https://api.asaas.com/v3');

// Preço e Configurações de Venda
define('PRICE_PER_THOUSAND', 10.00);
define('MINIMUM_ORDER', 500);
define('MINIMUM_PRICE', 5.00);
define('MAXIMUM_ORDER', 1000000);

// URLs do Sistema (Railway gera automaticamente)
$railway_url = getenv('RAILWAY_STATIC_URL');
$site_url = $railway_url ? 'https://' . $railway_url : (getenv('SITE_URL') ?: 'http://localhost');
define('SITE_URL', $site_url);
define('WEBHOOK_URL', SITE_URL . '/asaas_webhook.php');

// Configurações Gerais
define('TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set(TIMEZONE);

// Headers para API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Permite Vercel chamar
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Se for requisição OPTIONS, retorna 200
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexão com Banco de Dados
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Log do erro
        error_log("Database connection error: " . $e->getMessage());
        die(json_encode([
            'success' => false, 
            'message' => 'Erro na conexão com banco de dados'
        ]));
    }
}

// Função para fazer requisições à API do Asaas
function asaasRequest($endpoint, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    $url = ASAAS_API_URL . $endpoint;
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'access_token: ' . ASAAS_API_KEY
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Função para log
function logMessage($message, $file = 'app.log') {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logDir . '/' . $file, $logMessage, FILE_APPEND);
}
