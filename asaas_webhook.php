<?php
/**
 * Webhook do Asaas - Recebe notificações de pagamento
 */

require_once 'config.php';
require_once 'Api.php';

// Log da requisição
$input = file_get_contents('php://input');
logMessage("Webhook recebido: " . $input, 'webhook.log');

$data = json_decode($input, true);

// Verifica se é notificação de pagamento
if (!isset($data['event']) || !isset($data['payment'])) {
    logMessage("Webhook inválido - sem event ou payment", 'webhook.log');
    http_response_code(200);
    exit;
}

$event = $data['event'];
$paymentId = $data['payment']['id'];

logMessage("Evento: {$event} - Payment ID: {$paymentId}", 'webhook.log');

// Eventos que nos interessam
$validEvents = [
    'PAYMENT_RECEIVED',          // Pagamento confirmado
    'PAYMENT_CONFIRMED',         // Pagamento confirmado (cartão)
    'PAYMENT_APPROVED_BY_RISK_ANALYSIS' // Aprovado pela análise de risco
];

if (!in_array($event, $validEvents)) {
    logMessage("Evento ignorado: {$event}", 'webhook.log');
    http_response_code(200);
    exit;
}

try {
    // Busca informações completas do pagamento
    $paymentInfo = asaasRequest("/payments/{$paymentId}", 'GET');
    
    if ($paymentInfo['code'] !== 200) {
        logMessage("Erro ao buscar pagamento: " . json_encode($paymentInfo), 'webhook.log');
        http_response_code(200);
        exit;
    }
    
    $payment = $paymentInfo['data'];
    $orderHash = $payment['externalReference'];
    $status = $payment['status'];
    
    logMessage("Status do pagamento: {$status} - Pedido: {$orderHash}", 'webhook.log');
    
    // Apenas processa se status for RECEIVED ou CONFIRMED
    if ($status !== 'RECEIVED' && $status !== 'CONFIRMED') {
        logMessage("Status não processável: {$status}", 'webhook.log');
        http_response_code(200);
        exit;
    }
    
    $db = getDB();
    
    // Busca o pedido
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_hash = ? AND status = 'pending'");
    $stmt->execute([$orderHash]);
    $order = $stmt->fetch();
    
    if (!$order) {
        logMessage("Pedido não encontrado ou já processado: {$orderHash}", 'webhook.log');
        http_response_code(200);
        exit;
    }
    
    // Atualiza status para pago
    $stmt = $db->prepare("UPDATE orders SET status = 'paid', paid_at = NOW() WHERE id = ?");
    $stmt->execute([$order['id']]);
    
    logMessage("Pedido marcado como pago: {$orderHash}", 'webhook.log');
    
    // Processa o pedido na API do RevisionSMM
    $api = new Api();
    
    $orderData = [
        'service' => SERVICE_ID, // 4119
        'link' => $order['instagram_link'],
        'quantity' => $order['quantity']
    ];
    
    logMessage("Enviando para RevisionSMM (Serviço 4119): " . json_encode($orderData), 'api.log');
    
    $result = $api->order($orderData);
    
    if (isset($result->order)) {
        $apiOrderId = $result->order;
        
        // Atualiza com ID da API e marca como processando
        $stmt = $db->prepare("
            UPDATE orders 
            SET api_order_id = ?, 
                status = 'processing',
                processed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$apiOrderId, $order['id']]);
        
        logMessage("Pedido enviado para API! Order ID: {$apiOrderId}", 'api.log');
        
        // Envia e-mail de confirmação
        sendConfirmationEmail($order, $apiOrderId);
        
    } else {
        // Erro ao processar na API
        $stmt = $db->prepare("UPDATE orders SET status = 'error' WHERE id = ?");
        $stmt->execute([$order['id']]);
        
        $errorMsg = isset($result->error) ? $result->error : 'Erro desconhecido';
        logMessage("ERRO ao enviar para API: {$errorMsg}", 'api.log');
        
        // Envia e-mail de erro
        sendErrorEmail($order, $errorMsg);
    }
    
} catch (Exception $e) {
    logMessage("EXCEÇÃO no webhook: " . $e->getMessage(), 'errors.log');
}

http_response_code(200);
exit;

/**
 * Envia e-mail de confirmação
 */
function sendConfirmationEmail($order, $apiOrderId) {
    $to = $order['customer_email'];
    $subject = "✅ Pedido Confirmado - #{$order['order_hash']}";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #6B46C1, #9333EA); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background: #f9f9f9; }
            .box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .success { color: #28a745; font-weight: bold; font-size: 1.2em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Pagamento Confirmado!</h1>
            </div>
            <div class='content'>
                <p class='success'>Seu pedido está sendo processado agora!</p>
                
                <div class='box'>
                    <p><strong>Pedido:</strong> #{$order['order_hash']}</p>
                    <p><strong>Serviço:</strong> Seguidores Instagram</p>
                    <p><strong>Quantidade:</strong> {$order['quantity']}</p>
                    <p><strong>Link:</strong> {$order['instagram_link']}</p>
                    <p><strong>ID de Rastreamento:</strong> {$apiOrderId}</p>
                </div>
                
                <h3>📦 O que acontece agora?</h3>
                <ul>
                    <li>✓ Seu pagamento foi confirmado</li>
                    <li>✓ Pedido enviado para processamento</li>
                    <li>⏳ Entrega em até 24 horas</li>
                </ul>
                
                <p><strong>Acompanhamento:</strong><br>
                Você pode verificar o progresso acessando seu perfil do Instagram.</p>
                
                <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 0.9em; color: #666;'>
                    Se tiver alguma dúvida, responda este e-mail.<br>
                    <strong>InstaBoost</strong> - Aumente seus seguidores
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: InstaBoost <noreply@seudominio.com>\r\n";
    
    mail($to, $subject, $message, $headers);
    logMessage("E-mail de confirmação enviado para: {$to}", 'emails.log');
}

/**
 * Envia e-mail de erro
 */
function sendErrorEmail($order, $error) {
    $to = $order['customer_email'];
    $subject = "⚠️ Problema no Pedido - #{$order['order_hash']}";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #dc3545;'>⚠️ Detectamos um problema</h2>
            <p>Seu pagamento foi confirmado, mas houve um problema ao processar seu pedido.</p>
            
            <div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <strong>Pedido:</strong> #{$order['order_hash']}<br>
                <strong>Erro:</strong> {$error}
            </div>
            
            <p><strong>Não se preocupe!</strong> Nossa equipe foi notificada e estamos trabalhando para resolver.</p>
            <p>Você será notificado assim que seu pedido for processado.</p>
            
            <p>Se preferir, entre em contato conosco respondendo este e-mail.</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: InstaBoost <noreply@seudominio.com>\r\n";
    
    mail($to, $subject, $message, $headers);
    logMessage("E-mail de erro enviado para: {$to}", 'emails.log');
}
