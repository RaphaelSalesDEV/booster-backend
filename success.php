<?php
require_once 'config.php';

$order_hash = $_GET['order'] ?? '';

if (!$order_hash) {
    die('Pedido não encontrado');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_hash = ?");
$stmt->execute([$order_hash]);
$order = $stmt->fetch();

if (!$order) {
    die('Pedido não encontrado');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - InstaBoost</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6B46C1 0%, #9333EA 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-box {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .success-icon {
            font-size: 5em;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        h1 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 2.2em;
        }

        .subtitle {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 30px;
        }

        .order-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 30px 0;
            text-align: left;
        }

        .order-details h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
            text-align: center;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 600;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .timeline {
            background: #e7f3ff;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: left;
        }

        .timeline h3 {
            color: #0d6efd;
            margin-bottom: 15px;
            text-align: center;
        }

        .timeline-item {
            display: flex;
            align-items: start;
            gap: 15px;
            margin: 15px 0;
        }

        .timeline-icon {
            font-size: 1.5em;
        }

        .timeline-text {
            color: #555;
        }

        .btn-home {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-home:hover {
            opacity: 0.9;
        }

        .info-box {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            border-left: 4px solid #ffc107;
            text-align: left;
        }

        .info-box p {
            color: #856404;
            margin: 8px 0;
        }

        @media (max-width: 768px) {
            .success-box {
                padding: 35px 20px;
            }
            h1 {
                font-size: 1.7em;
            }
        }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="success-icon">✅</div>
        <h1>Pagamento Confirmado!</h1>
        <p class="subtitle">Seu pedido está sendo processado</p>

        <div class="order-details">
            <h2>📦 Detalhes do Pedido</h2>
            <div class="detail-item">
                <span class="detail-label">Pedido:</span>
                <span class="detail-value">#<?php echo htmlspecialchars($order['order_hash']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Serviço:</span>
                <span class="detail-value">Seguidores Instagram</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Quantidade:</span>
                <span class="detail-value"><?php echo number_format($order['quantity']); ?> seguidores</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Instagram:</span>
                <span class="detail-value" style="word-break: break-all; text-align: right;">
                    <?php echo htmlspecialchars($order['instagram_link']); ?>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Valor Pago:</span>
                <span class="detail-value" style="color: #28a745; font-weight: 700;">
                    R$ <?php echo number_format($order['total_amount'], 2, ',', '.'); ?>
                </span>
            </div>
        </div>

        <div class="timeline">
            <h3>⏱️ O que acontece agora?</h3>
            <div class="timeline-item">
                <div class="timeline-icon">✅</div>
                <div class="timeline-text">
                    <strong>Pagamento confirmado</strong><br>
                    <small>Recebemos seu pagamento com sucesso</small>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon">⚙️</div>
                <div class="timeline-text">
                    <strong>Processando pedido</strong><br>
                    <small>Seu pedido foi enviado para processamento</small>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon">🚀</div>
                <div class="timeline-text">
                    <strong>Entrega iniciada</strong><br>
                    <small>Os seguidores começarão a chegar em 0-1 hora</small>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon">🎉</div>
                <div class="timeline-text">
                    <strong>Pedido concluído</strong><br>
                    <small>Todos os seguidores serão entregues em até 6 horas</small>
                </div>
            </div>
        </div>

        <div class="info-box">
            <p><strong>⚠️ Importante:</strong></p>
            <p>• Seu perfil do Instagram deve estar público</p>
            <p>• Não faça outro pedido no mesmo link antes deste finalizar</p>
            <p>• Os seguidores chegarão de forma gradual e natural</p>
        </div>

        <a href="index.html" class="btn-home">🏠 Fazer Novo Pedido</a>
    </div>
</body>
</html>
