<?php
/**
 * Teste do Serviço 4119 na RevisionSMM
 * Use este arquivo para verificar preço e disponibilidade
 */

require_once 'Api.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Serviço 4119</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #6B46C1;
            margin-bottom: 30px;
            text-align: center;
        }
        .info-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 4px solid #6B46C1;
        }
        .info-box h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #555;
        }
        .value {
            color: #333;
            font-weight: 500;
        }
        .price {
            color: #9333EA;
            font-size: 1.5em;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .balance-box {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
        }
        .balance-box h2 {
            margin-bottom: 10px;
        }
        .balance-amount {
            font-size: 3em;
            font-weight: bold;
        }
        .calc-box {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .calc-box h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        button {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }
        button:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste do Serviço 4119</h1>

        <?php
        $api = new Api();
        
        // Testa saldo
        echo "<div class='balance-box'>";
        echo "<h2>💰 Seu Saldo na RevisionSMM</h2>";
        
        try {
            $balance = $api->balance();
            if (isset($balance->balance)) {
                echo "<div class='balance-amount'>R$ " . number_format($balance->balance, 2, ',', '.') . "</div>";
                echo "<p style='margin-top: 10px;'>Créditos disponíveis: R$ " . number_format($balance->balance, 2, ',', '.') . "</p>";
            } else {
                echo "<div class='error'>Erro ao buscar saldo</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Erro: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
        
        // Busca informações do serviço 4119
        echo "<div class='info-box'>";
        echo "<h3>📊 Informações do Serviço 4119</h3>";
        
        try {
            $services = $api->services();
            $serviceFound = false;
            
            foreach ($services as $service) {
                if ($service->service == 4119) {
                    $serviceFound = true;
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>ID do Serviço:</span>";
                    echo "<span class='value'><strong>4119</strong></span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Nome:</span>";
                    echo "<span class='value'>" . htmlspecialchars($service->name) . "</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Preço (seu custo):</span>";
                    echo "<span class='value price'>R$ " . number_format($service->rate, 2, ',', '.') . " / 1.000</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Mínimo:</span>";
                    echo "<span class='value'>" . number_format($service->min) . "</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Máximo:</span>";
                    echo "<span class='value'>" . number_format($service->max) . "</span>";
                    echo "</div>";
                    
                    if (isset($service->type)) {
                        echo "<div class='info-item'>";
                        echo "<span class='label'>Tipo:</span>";
                        echo "<span class='value'>" . htmlspecialchars($service->type) . "</span>";
                        echo "</div>";
                    }
                    
                    if (isset($service->category)) {
                        echo "<div class='info-item'>";
                        echo "<span class='label'>Categoria:</span>";
                        echo "<span class='value'>" . htmlspecialchars($service->category) . "</span>";
                        echo "</div>";
                    }
                    
                    // Calculadora de lucro
                    $costPrice = $service->rate;
                    $sellPrice = 4.85; // Preço que você está vendendo
                    $profit = $sellPrice - $costPrice;
                    $profitPercent = ($profit / $costPrice) * 100;
                    
                    echo "</div>";
                    
                    echo "<div class='calc-box'>";
                    echo "<h3>💰 Cálculo de Lucro</h3>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Seu custo (RevisionSMM):</span>";
                    echo "<span class='value'>R$ " . number_format($costPrice, 2, ',', '.') . " / 1.000</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Seu preço de venda:</span>";
                    echo "<span class='value'>R$ " . number_format($sellPrice, 2, ',', '.') . " / 1.000</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Lucro por 1.000:</span>";
                    echo "<span class='value' style='color: #28a745; font-weight: bold;'>R$ " . number_format($profit, 2, ',', '.') . " (" . number_format($profitPercent, 0) . "%)</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Vendendo 10.000:</span>";
                    echo "<span class='value' style='color: #28a745; font-weight: bold;'>R$ " . number_format($profit * 10, 2, ',', '.') . "</span>";
                    echo "</div>";
                    
                    echo "<div class='info-item'>";
                    echo "<span class='label'>Vendendo 100.000:</span>";
                    echo "<span class='value' style='color: #28a745; font-weight: bold;'>R$ " . number_format($profit * 100, 2, ',', '.') . "</span>";
                    echo "</div>";
                    
                    echo "</div>";
                    
                    // Se o preço de venda for menor que o custo, alerta
                    if ($sellPrice < $costPrice) {
                        echo "<div class='error'>";
                        echo "<strong>⚠️ ATENÇÃO!</strong><br>";
                        echo "Você está vendendo por R$ " . number_format($sellPrice, 2, ',', '.') . " mas o custo é R$ " . number_format($costPrice, 2, ',', '.') . "!<br>";
                        echo "Você está tendo PREJUÍZO de R$ " . number_format($costPrice - $sellPrice, 2, ',', '.') . " por 1.000 seguidores!";
                        echo "</div>";
                    } else {
                        echo "<div class='success'>";
                        echo "<strong>✅ Tudo certo!</strong><br>";
                        echo "Você está lucrando R$ " . number_format($profit, 2, ',', '.') . " por cada 1.000 seguidores vendidos.";
                        echo "</div>";
                    }
                    
                    break;
                }
            }
            
            if (!$serviceFound) {
                echo "<div class='error'>";
                echo "<strong>❌ Serviço 4119 não encontrado!</strong><br>";
                echo "Verifique se o serviço ainda está ativo na RevisionSMM.";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>Erro ao buscar serviços: " . $e->getMessage() . "</div>";
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <button onclick="location.reload()">🔄 Atualizar Informações</button>
            <button onclick="window.location.href='index.html'" style="background: #28a745;">🏠 Ir para o Site</button>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 10px; border-left: 4px solid #0d6efd;">
            <h3 style="color: #0d6efd; margin-bottom: 10px;">💡 Dica</h3>
            <p style="color: #555;">
                Se o preço de custo mudou na RevisionSMM, você pode ajustar seu preço de venda editando o arquivo <code>index.html</code> (linha ~155) e <code>create_charge.php</code>.
            </p>
        </div>
    </div>
</body>
</html>
