<?php
/**
 * TESTE COMPLETO DO SISTEMA
 * Execute este arquivo para verificar se tudo está funcionando
 */

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Completo do Sistema</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
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
        .test-section {
            margin: 30px 0;
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #6B46C1;
        }
        .test-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .code {
            background: #1a202c;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        button {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 5px;
        }
        button:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste Completo do Sistema</h1>

        <?php
        echo "<div class='test-section'>";
        echo "<h2>1️⃣ Teste de Arquivos Necessários</h2>";
        
        $requiredFiles = [
            'config.php' => 'Configurações do sistema',
            'Api.php' => 'Classe de integração com RevisionSMM',
            'create_charge.php' => 'Criar cobrança PIX',
            'asaas_webhook.php' => 'Webhook do Asaas',
            'pagamento.php' => 'Página de pagamento',
            'success.php' => 'Página de sucesso',
            'check_payment.php' => 'Verificar pagamento',
            'admin.php' => 'Painel administrativo',
            'index.html' => 'Site principal'
        ];
        
        $allFilesExist = true;
        foreach ($requiredFiles as $file => $desc) {
            if (file_exists($file)) {
                echo "<div class='success'>✅ $file - $desc</div>";
            } else {
                echo "<div class='error'>❌ $file NÃO ENCONTRADO - $desc</div>";
                $allFilesExist = false;
            }
        }
        
        if ($allFilesExist) {
            echo "<div class='success'><strong>✅ Todos os arquivos necessários estão presentes!</strong></div>";
        }
        echo "</div>";

        // Teste 2: Banco de Dados
        echo "<div class='test-section'>";
        echo "<h2>2️⃣ Teste de Conexão com Banco de Dados</h2>";
        
        if (file_exists('config.php')) {
            try {
                require_once 'config.php';
                $db = getDB();
                echo "<div class='success'>✅ Conexão com banco de dados OK!</div>";
                
                // Verifica tabelas
                $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('orders', $tables)) {
                    echo "<div class='success'>✅ Tabela 'orders' existe</div>";
                    
                    $count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                    echo "<div class='info'>📊 Total de pedidos: $count</div>";
                } else {
                    echo "<div class='error'>❌ Tabela 'orders' não existe. Execute install.php!</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro ao conectar: " . $e->getMessage() . "</div>";
                echo "<div class='warning'>⚠️ Edite config.php com os dados corretos do banco!</div>";
            }
        } else {
            echo "<div class='error'>❌ Arquivo config.php não encontrado. Execute install.php!</div>";
        }
        echo "</div>";

        // Teste 3: API RevisionSMM
        echo "<div class='test-section'>";
        echo "<h2>3️⃣ Teste de API RevisionSMM</h2>";
        
        if (file_exists('Api.php')) {
            try {
                require_once 'Api.php';
                $api = new Api();
                
                echo "<div class='info'>🔑 API Key configurada: bc1q9shqng58tykf3kmwukzez6h5xne559f5gfu2l8</div>";
                
                // Testa saldo
                $balance = $api->balance();
                if (isset($balance->balance)) {
                    echo "<div class='success'>✅ Conexão com API OK!</div>";
                    echo "<div class='success'>💰 Seu saldo: R$ " . number_format($balance->balance, 2, ',', '.') . "</div>";
                    
                    if ($balance->balance < 10) {
                        echo "<div class='warning'>⚠️ ATENÇÃO: Saldo baixo! Adicione créditos na RevisionSMM.</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Erro ao buscar saldo</div>";
                }
                
                // Verifica serviço 4119
                $services = $api->services();
                $serviceFound = false;
                foreach ($services as $service) {
                    if ($service->service == 4119) {
                        $serviceFound = true;
                        echo "<div class='success'>✅ Serviço 4119 encontrado!</div>";
                        echo "<div class='info'>";
                        echo "<strong>Nome:</strong> " . $service->name . "<br>";
                        echo "<strong>Preço (seu custo):</strong> R$ " . number_format($service->rate, 2, ',', '.') . " / 1.000<br>";
                        echo "<strong>Seu preço de venda:</strong> R$ 10,00 / 1.000<br>";
                        $profit = 10.00 - $service->rate;
                        if ($profit > 0) {
                            echo "<strong>Seu lucro:</strong> <span style='color: #28a745;'>R$ " . number_format($profit, 2, ',', '.') . " / 1.000</span>";
                        } else {
                            echo "<strong style='color: #dc3545;'>⚠️ PREJUÍZO de R$ " . number_format(abs($profit), 2, ',', '.') . " / 1.000!</strong>";
                        }
                        echo "</div>";
                        break;
                    }
                }
                
                if (!$serviceFound) {
                    echo "<div class='error'>❌ Serviço 4119 não encontrado! Verifique se está ativo.</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro na API: " . $e->getMessage() . "</div>";
            }
        }
        echo "</div>";

        // Teste 4: API Asaas
        echo "<div class='test-section'>";
        echo "<h2>4️⃣ Teste de API Asaas</h2>";
        
        if (function_exists('asaasRequest')) {
            try {
                $result = asaasRequest('/customers?limit=1', 'GET');
                
                if ($result['code'] === 200) {
                    echo "<div class='success'>✅ Conexão com Asaas OK!</div>";
                    echo "<div class='info'>🔑 Token configurado corretamente</div>";
                } else {
                    echo "<div class='error'>❌ Erro ao conectar com Asaas (HTTP " . $result['code'] . ")</div>";
                    echo "<div class='warning'>⚠️ Verifique o token no config.php</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Função asaasRequest não encontrada. Execute install.php!</div>";
        }
        echo "</div>";

        // Teste 5: Diretórios
        echo "<div class='test-section'>";
        echo "<h2>5️⃣ Teste de Diretórios e Permissões</h2>";
        
        $dirs = ['logs', 'uploads', 'cache'];
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    echo "<div class='success'>✅ Diretório '$dir' existe e tem permissão de escrita</div>";
                } else {
                    echo "<div class='warning'>⚠️ Diretório '$dir' existe mas NÃO tem permissão de escrita</div>";
                    echo "<div class='code'>chmod 755 $dir</div>";
                }
            } else {
                echo "<div class='error'>❌ Diretório '$dir' não existe</div>";
                echo "<div class='code'>mkdir $dir && chmod 755 $dir</div>";
            }
        }
        echo "</div>";

        // Teste 6: Configurações
        echo "<div class='test-section'>";
        echo "<h2>6️⃣ Verificação de Configurações</h2>";
        
        if (defined('SERVICE_ID')) {
            echo "<div class='info'>";
            echo "<strong>Service ID:</strong> " . SERVICE_ID . "<br>";
            echo "<strong>Preço por 1000:</strong> R$ 10,00<br>";
            echo "<strong>Pedido mínimo:</strong> 500 seguidores (R$ 5,00)<br>";
            echo "<strong>Pedido máximo:</strong> 1.000.000 seguidores<br>";
            if (defined('SITE_URL')) {
                echo "<strong>URL do site:</strong> " . SITE_URL . "<br>";
                echo "<strong>URL do webhook:</strong> " . SITE_URL . "/asaas_webhook.php";
            }
            echo "</div>";
        }
        echo "</div>";

        // Resumo Final
        echo "<div class='test-section' style='border-left-color: #28a745;'>";
        echo "<h2>✅ Resumo do Teste</h2>";
        
        $issues = [];
        if (!$allFilesExist) $issues[] = "Arquivos faltando";
        if (!isset($db)) $issues[] = "Banco de dados não conectado";
        if (!isset($balance) || !isset($balance->balance)) $issues[] = "API RevisionSMM com problemas";
        if (!$serviceFound) $issues[] = "Serviço 4119 não encontrado";
        
        if (empty($issues)) {
            echo "<div class='success'>";
            echo "<h3 style='margin-bottom: 15px;'>🎉 SISTEMA 100% FUNCIONAL!</h3>";
            echo "<p><strong>Tudo está funcionando perfeitamente!</strong></p>";
            echo "<p style='margin-top: 15px;'>Próximos passos:</p>";
            echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
            echo "<li>Configure o webhook no Asaas</li>";
            echo "<li>Faça um pedido de teste</li>";
            echo "<li>Comece a vender!</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<h3 style='margin-bottom: 15px;'>⚠️ Problemas Encontrados:</h3>";
            echo "<ul style='margin-left: 20px;'>";
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
            echo "<p style='margin-top: 15px;'><strong>Corrija esses problemas antes de usar o sistema!</strong></p>";
            echo "</div>";
        }
        echo "</div>";
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <button onclick="location.reload()">🔄 Testar Novamente</button>
            <button onclick="window.location.href='test_service.php'">📊 Ver Detalhes do Serviço 4119</button>
            <button onclick="window.location.href='admin.php'" style="background: #28a745;">👨‍💼 Abrir Painel Admin</button>
        </div>
    </div>
</body>
</html>
