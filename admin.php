<?php
/**
 * Painel Administrativo Simples
 * Use senha forte em produção!
 */

session_start();

// SENHA DO ADMIN - MUDE ISSO!
define('ADMIN_PASSWORD', 'admin123'); // ⚠️ TROCAR URGENTE!

// Login
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
    } else {
        $error = 'Senha incorreta!';
    }
}

if (!isset($_SESSION['admin_logged'])) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        h1 { color: #6B46C1; margin-bottom: 30px; text-align: center; }
        input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1em;
            margin-bottom: 20px;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🔐 Admin</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="password" name="password" placeholder="Senha" required autofocus>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// Dashboard
require_once 'config.php';

$db = getDB();

// Estatísticas
$stats = $db->query("SELECT * FROM dashboard_stats")->fetch();

// Pedidos recentes
$recentOrders = $db->query("
    SELECT * FROM orders 
    ORDER BY created_at DESC 
    LIMIT 20
")->fetchAll();

// Estatísticas por dia (últimos 7 dias)
$dailyStats = $db->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('paid', 'processing', 'completed') THEN 1 ELSE 0 END) as paid,
        SUM(CASE WHEN status IN ('paid', 'processing', 'completed') THEN total_amount ELSE 0 END) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #cce5ff; color: #004085; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        button {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-small {
            padding: 5px 12px;
            font-size: 0.9em;
        }
        .refresh {
            background: white;
            color: #6B46C1;
            border: 2px solid #6B46C1;
        }
        .daily-table td { font-size: 0.95em; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>📊 Dashboard Admin</h1>
            <p>InstaBoost - Gestão de Pedidos</p>
        </div>
        <form method="post" style="margin: 0;">
            <button type="submit" name="logout" class="refresh">🚪 Sair</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total de Pedidos</h3>
            <div class="number"><?php echo number_format($stats['total_orders']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Receita Total</h3>
            <div class="number">R$ <?php echo number_format($stats['total_revenue'], 2, ',', '.'); ?></div>
        </div>
        <div class="stat-card">
            <h3>Hoje</h3>
            <div class="number"><?php echo $stats['today_orders']; ?></div>
            <small style="color: #666;">R$ <?php echo number_format($stats['today_revenue'], 2, ',', '.'); ?></small>
        </div>
        <div class="stat-card">
            <h3>Processando</h3>
            <div class="number"><?php echo $stats['processing_orders']; ?></div>
        </div>
    </div>

    <div class="section">
        <h2>📅 Últimos 7 Dias</h2>
        <table class="daily-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Total Pedidos</th>
                    <th>Pagos</th>
                    <th>Receita</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyStats as $day): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($day['date'])); ?></td>
                    <td><?php echo $day['total']; ?></td>
                    <td><?php echo $day['paid']; ?></td>
                    <td>R$ <?php echo number_format($day['revenue'], 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">📦 Pedidos Recentes</h2>
            <button onclick="location.reload()" class="btn-small refresh">🔄 Atualizar</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Instagram</th>
                        <th>Qtd</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><strong><?php echo $order['order_hash']; ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo htmlspecialchars($order['instagram_link']); ?>" target="_blank" style="color: #9333EA;">
                                Link
                            </a>
                        </td>
                        <td><?php echo number_format($order['quantity']); ?></td>
                        <td>R$ <?php echo number_format($order['total_amount'], 2, ',', '.'); ?></td>
                        <td>
                            <span class="status status-<?php echo $order['status']; ?>">
                                <?php echo strtoupper($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <h2>⚙️ Ações Rápidas</h2>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <button onclick="window.open('logs/orders.log', '_blank')" class="btn-small">📋 Ver Logs de Pedidos</button>
            <button onclick="window.open('logs/webhook.log', '_blank')" class="btn-small">📋 Ver Logs de Webhook</button>
            <button onclick="window.open('logs/api.log', '_blank')" class="btn-small">📋 Ver Logs de API</button>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
