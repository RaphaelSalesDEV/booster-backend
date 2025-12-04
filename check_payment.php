<?php
/**
 * Verifica se o pagamento foi confirmado
 */

require_once 'config.php';

$order_hash = $_GET['order'] ?? '';

if (!$order_hash) {
    echo json_encode(['error' => 'Pedido não encontrado']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT status FROM orders WHERE order_hash = ?");
    $stmt->execute([$order_hash]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['error' => 'Pedido não encontrado']);
        exit;
    }
    
    $paid = in_array($order['status'], ['paid', 'processing', 'completed']);
    
    echo json_encode([
        'paid' => $paid,
        'status' => $order['status']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
