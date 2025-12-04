<?php
/**
 * Instalador Simplificado para Railway
 * Railway já configura o banco automaticamente!
 */

// CARREGA CONFIG PRIMEIRO!
require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação Railway - InstaBoost</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #6B46C1;
            margin-bottom: 30px;
            text-align: center;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #6B46C1;
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
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        button {
            background: linear-gradient(135deg, #6B46C1, #9333EA);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        button:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚂 Instalação Railway</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            
            echo "<div class='step'>";
            echo "<h3>📦 Criando estrutura...</h3>";
            
            try {
                // Testa conexão
                echo "<div class='info'>Testando conexão com banco de dados...</div>";
                $db = getDB();
                echo "<div class='success'>✅ Conexão com MySQL OK!</div>";
                
                // Lê SQL
                if (!file_exists('database.sql')) {
                    throw new Exception("Arquivo database.sql não encontrado!");
                }
                
                $sql = file_get_contents('database.sql');
                
                // Remove delimitadores problemáticos
                $sql = str_replace('DELIMITER //', '', $sql);
                $sql = str_replace('DELIMITER ;', '', $sql);
                
                // Separa comandos
                $statements = explode(';', $sql);
                
                $executed = 0;
                $errors = 0;
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement) && strlen($statement) > 5) {
                        try {
                            $db->exec($statement);
                            $executed++;
                        } catch (PDOException $e) {
                            // Ignora erros de "já existe"
                            if (strpos($e->getMessage(), 'already exists') === false && 
                                strpos($e->getMessage(), 'Duplicate') === false) {
                                echo "<div class='error'>⚠️ " . $e->getMessage() . "</div>";
                                $errors++;
                            }
                        }
                    }
                }
                
                echo "<div class='success'>✅ {$executed} comandos SQL executados!</div>";
                if ($errors > 0) {
                    echo "<div class='info'>ℹ️ {$errors} avisos (pode ignorar se tabelas já existiam)</div>";
                }
                
                // Verifica tabelas
                $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('orders', $tables)) {
                    echo "<div class='success'>✅ Tabela 'orders' criada com sucesso!</div>";
                    
                    $count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                    echo "<div class='info'>📊 Total de pedidos: {$count}</div>";
                } else {
                    echo "<div class='error'>❌ Tabela 'orders' não foi criada!</div>";
                }
                
                // Cria diretórios
                $dirs = ['logs', 'uploads', 'cache'];
                foreach ($dirs as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                        echo "<div class='success'>✅ Diretório '{$dir}' criado</div>";
                    }
                }
                
                echo "</div>";
                
                echo "<div class='step'>";
                echo "<h3>✅ Instalação Concluída!</h3>";
                echo "<div class='success'>";
                echo "<p><strong>🎉 Sistema instalado com sucesso!</strong></p>";
                echo "<p style='margin-top: 15px;'><strong>Próximos passos:</strong></p>";
                echo "<ol style='margin-left: 20px; margin-top: 10px;'>";
                echo "<li>Configure webhook no Asaas</li>";
                echo "<li>Adicione créditos na RevisionSMM</li>";
                echo "<li>Acesse TEST_SYSTEM.php</li>";
                echo "<li>Faça um pedido de teste</li>";
                echo "</ol>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='step'>";
                echo "<h3>🔗 Links Úteis</h3>";
                echo "<p><a href='TEST_SYSTEM.php' style='color: #6B46C1;'>→ Testar Sistema</a></p>";
                echo "<p><a href='test_service.php' style='color: #6B46C1;'>→ Ver Serviço 4119</a></p>";
                echo "<p><a href='admin.php' style='color: #6B46C1;'>→ Painel Admin</a></p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
                echo "<div class='info'>";
                echo "<p><strong>Verifique:</strong></p>";
                echo "<ul style='margin-left: 20px;'>";
                echo "<li>Railway MySQL está rodando?</li>";
                echo "<li>Variáveis de ambiente configuradas?</li>";
                echo "<li>Arquivo database.sql existe?</li>";
                echo "</ul>";
                echo "</div>";
            }
            
        } else {
        ?>
        
        <div class="step">
            <h3>📋 Railway já configurou automaticamente:</h3>
            <div class="<?php echo getenv('MYSQLHOST') ? 'success' : 'error'; ?>">
                <?php
                if (getenv('MYSQLHOST')) {
                    echo "<p>✅ Host: " . getenv('MYSQLHOST') . "</p>";
                    echo "<p>✅ Porta: " . getenv('MYSQLPORT') . "</p>";
                    echo "<p>✅ Banco: " . getenv('MYSQLDATABASE') . "</p>";
                    echo "<p>✅ Usuário: " . getenv('MYSQLUSER') . "</p>";
                    echo "<p>✅ Senha: ••••••••</p>";
                } else {
                    echo "<p>❌ Variáveis de ambiente MySQL não detectadas!</p>";
                    echo "<p>Certifique-se de adicionar MySQL no Railway!</p>";
                }
                ?>
            </div>
        </div>

        <div class="step">
            <h3>🔐 APIs configuradas:</h3>
            <div class="<?php echo defined('API_KEY') ? 'success' : 'error'; ?>">
                <?php
                if (defined('API_KEY')) {
                    echo "<p>✅ RevisionSMM: " . substr(API_KEY, 0, 20) . "...</p>";
                    echo "<p>✅ Asaas: Configurado</p>";
                    echo "<p>✅ Serviço ID: " . SERVICE_ID . "</p>";
                } else {
                    echo "<p>❌ Variáveis API_KEY não detectadas!</p>";
                    echo "<p>Adicione as 3 variáveis na Railway:</p>";
                    echo "<ul style='margin-left: 20px; margin-top: 10px;'>";
                    echo "<li>API_KEY</li>";
                    echo "<li>ASAAS_API_KEY</li>";
                    echo "<li>SERVICE_ID</li>";
                    echo "</ul>";
                }
                ?>
            </div>
        </div>

        <div class="step">
            <h3>⚙️ O que este instalador faz:</h3>
            <ul style="margin-left: 20px; color: #555;">
                <li>Cria tabelas no banco MySQL</li>
                <li>Configura estrutura de pastas</li>
                <li>Prepara sistema para uso</li>
            </ul>
        </div>

        <form method="post">
            <button type="submit" name="install">🚀 Criar Tabelas Agora</button>
        </form>

        <?php
        }
        ?>
    </div>
</body>
</html>
