<?php
/**
 * Cria cobrança PIX no Asaas - SEM CPF
 */

require_once 'config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

logMessage("Nova requisição de pedido: " . json_encode($data), 'orders.log');

// Validação dos dados (apenas link e quantidade)
if (!isset($data['link']) || !isset($data['quantity']) || !isset($data['total'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dados incompletos'
    ]);
    exit;
}

$link = filter_var($data['link'], FILTER_SANITIZE_URL);
$quantity = intval($data['quantity']);
$total = floatval($data['total']);

// Validações
if ($quantity < 500 || $quantity > 1000000) {
    echo json_encode(['success' => false, 'message' => 'Quantidade deve estar entre 500 e 1.000.000']);
    exit;
}

if (!strpos($link, 'instagram.com')) {
    echo json_encode(['success' => false, 'message' => 'Link do Instagram inválido']);
    exit;
}

// Recalcula total (mínimo R$ 5,00)
$total = max(($quantity / 1000) * 10.00, 5.00);

try {
    $db = getDB();
    
    // Gera ID único do pedido
    $order_hash = 'ORD-' . strtoupper(substr(md5(uniqid() . time()), 0, 12));
    
    // Cria cobrança PIX no Asaas SEM CPF
    $chargeData = [
        'billingType' => 'PIX',
        'dueDate' => date('Y-m-d'),
        'value' => $total,
        'description' => "InstaBoost - {$quantity} seguidores Instagram",
        'externalReference' => $order_hash,
        'postalService' => false
    ];
    
    $charge = asaasRequest('/payments', 'POST', $chargeData);
    
    if ($charge['code'] !== 200 && $charge['code'] !== 201) {
        logMessage("Erro ao criar cobrança: " . json_encode($charge), 'asaas.log');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar PIX. Tente novamente.'
        ]);
        exit;
    }
    
    $chargeId = $charge['data']['id'];
    $invoiceUrl = $charge['data']['invoiceUrl'];
    
    // Dados do PIX
    $pixData = isset($charge['data']['pixTransaction']) ? $charge['data']['pixTransaction'] : null;
    $pixQrCode = $pixData ? $pixData['qrCode']['payload'] : null;
    $pixQrCodeImage = $pixData ? $pixData['qrCode']['encodedImage'] : null;
    
    logMessage("Cobrança PIX criada: {$chargeId}", 'asaas.log');
    
    // Salva pedido no banco
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_hash,
            instagram_link,
            quantity,
            total_amount,
            asaas_charge_id,
            payment_url,
            pix_code,
            pix_qrcode_image,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $order_hash,
        $link,
        $quantity,
        $total,
        $chargeId,
        $invoiceUrl,
        $pixQrCode,
        $pixQrCodeImage
    ]);
    
    $orderId = $db->lastInsertId();
    
    logMessage("Pedido salvo: ID {$orderId} - {$order_hash}", 'orders.log');
    
    // Retorna sucesso
    echo json_encode([
        'success' => true,
        'order_hash' => $order_hash,
        'charge_id' => $chargeId
    ]);
    
} catch (Exception $e) {
    logMessage("ERRO: " . $e->getMessage(), 'errors.log');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar pedido: ' . $e->getMessage()
    ]);
}
