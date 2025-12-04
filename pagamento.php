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

// Se já foi pago, redireciona
if ($order['status'] === 'paid' || $order['status'] === 'processing' || $order['status'] === 'completed') {
    header('Location: success.php?order=' . $order_hash);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - InstaBoost</title>
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
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .payment-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center;
        }

        h1 {
            color: #6B46C1;
            margin-bottom: 10px;
            font-size: 2em;
        }

        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: left;
        }

        .order-info p {
            margin: 8px 0;
            color: #555;
        }

        .order-info strong {
            color: #333;
        }

        .pix-section {
            margin: 30px 0;
        }

        .pix-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .qrcode-box {
            background: white;
            padding: 20px;
            border: 3px solid #6B46C1;
            border-radius: 15px;
            margin: 20px auto;
            max-width: 350px;
        }

        .qrcode-box img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .pix-code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            color: #333;
            border: 2px dashed #dee2e6;
        }

        .btn-copy {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin: 10px 0;
        }

        .btn-copy:hover {
            opacity: 0.9;
        }

        .instructions {
            text-align: left;
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            border-left: 4px solid #0d6efd;
        }

        .instructions h3 {
            color: #0d6efd;
            margin-bottom: 15px;
        }

        .instructions ol {
            margin-left: 20px;
        }

        .instructions li {
            margin: 8px 0;
            color: #555;
        }

        .status-check {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #6B46C1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }

        @media (max-width: 768px) {
            .payment-box {
                padding: 25px 20px;
            }
            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-box">
            <h1>💳 Pagamento PIX</h1>
            <p style="color: #666; margin-bottom: 20px;">Pedido #<?php echo htmlspecialchars($order['order_hash']); ?></p>

            <div class="order-info">
                <p><strong>Serviço:</strong> Seguidores Instagram</p>
                <p><strong>Quantidade:</strong> <?php echo number_format($order['quantity']); ?> seguidores</p>
                <p><strong>Instagram:</strong> <?php echo htmlspecialchars($order['instagram_link']); ?></p>
                <p><strong>Valor:</strong> <span style="color: #9333EA; font-size: 1.3em; font-weight: bold;">R$ <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span></p>
            </div>

            <div class="pix-section">
                <h2>📱 Escaneie o QR Code</h2>
                
                <?php if ($order['pix_qrcode_image']): ?>
                    <div class="qrcode-box">
                        <img src="data:image/png;base64,<?php echo $order['pix_qrcode_image']; ?>" alt="QR Code PIX">
                    </div>
                <?php endif; ?>

                <p style="margin: 20px 0; color: #666;"><strong>OU</strong></p>

                <p style="color: #555; margin-bottom: 10px;"><strong>Copie o código PIX:</strong></p>
                
                <?php if ($order['pix_code']): ?>
                    <div class="pix-code" id="pixCode"><?php echo htmlspecialchars($order['pix_code']); ?></div>
                    <button class="btn-copy" onclick="copyPixCode()">📋 Copiar Código PIX</button>
                <?php endif; ?>
            </div>

            <div class="instructions">
                <h3>📝 Como pagar com PIX</h3>
                <ol>
                    <li>Abra o app do seu banco</li>
                    <li>Escolha pagar com PIX</li>
                    <li>Escaneie o QR Code OU cole o código copiado</li>
                    <li>Confirme o pagamento</li>
                    <li>Aguarde a confirmação (geralmente instantâneo)</li>
                </ol>
            </div>

            <div class="status-check" id="statusCheck">
                <div class="loading"></div>
                <p style="margin-left: 30px; display: inline-block;">Aguardando pagamento...</p>
            </div>

            <p style="color: #666; font-size: 0.9em; margin-top: 20px;">
                ⏰ Este PIX expira em 24 horas
            </p>
        </div>
    </div>

    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pixCode').textContent;
            navigator.clipboard.writeText(pixCode).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copiado!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '';
                }, 2000);
            });
        }

        // Verificar status do pagamento a cada 5 segundos
        let checkInterval = setInterval(checkPaymentStatus, 5000);

        function checkPaymentStatus() {
            fetch('check_payment.php?order=<?php echo $order_hash; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.paid) {
                        clearInterval(checkInterval);
                        document.getElementById('statusCheck').innerHTML = `
                            <div class="alert-success">
                                <h3 style="margin-bottom: 10px;">✅ Pagamento Confirmado!</h3>
                                <p>Redirecionando...</p>
                            </div>
                        `;
                        setTimeout(() => {
                            window.location.href = 'success.php?order=<?php echo $order_hash; ?>';
                        }, 2000);
                    }
                })
                .catch(error => console.error('Erro:', error));
        }

        // Verificar status ao carregar a página
        checkPaymentStatus();
    </script>
</body>
</html>
