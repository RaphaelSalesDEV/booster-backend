<?php
/**
 * Instalador Simplificado para Railway - InstaBoost
 * Otimizado por: Assistente IA
 */

// Ativa exibição de erros apenas para o instalador (ajuda no debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tenta carregar a config. Se falhar, avisa o usuário.
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    die("<div style='padding: 20px; font-family: sans-serif; background: #ffebee; color: #c62828;'>❌ Erro Crítico: O arquivo <strong>config.php</strong> não foi encontrado. Por favor, envie-o para o Railway.</div>");
}

// Função auxiliar para limpar comentários SQL
function cleanSQL($sql) {
    $lines = explode("\n", $sql);
    $clean = "";
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line && substr($line, 0, 2) != '--' && substr($line, 0, 1) != '#') {
            $clean .= $line . "\n";
        }
    }
    return $clean;
}
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
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #4a148c;
            margin-bottom: 25px;
            text-align: center;
            font-size: 2rem;
        }
        h3 { color: #333; margin-bottom: 10px; font-size: 1.1rem; }
        .step {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .status-box { padding: 15px; border-radius: 8px; margin-top: 10px; font-size: 0.95rem; line-height: 1.6; }
        .success { background: #e8f5e9; color: #2e7d32; border-left: 5px solid #2e7d32; }
        .error { background: #ffebee; color: #c62828; border-left: 5px solid #c62828; }
        .info { background: #e3f2fd; color: #1565c0; border-left: 5px solid #1565c0; }
        
        button {
            background: linear-gradient(to right, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(37, 117, 252, 0.4);
        }
        a { text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
        ul { list-style-position: inside; }
        code { background: #eee; padding: 2px 5px; border-radius: 4px; font-family: monospace; color: #d63384; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚂 Instalação Railway</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            
            echo "<div class='step'>";
            echo "<h3>📦 Executando instalação...</h3>";
            
            try {
                // 1. Testa conexão
                if (!function_exists('getDB')) {
                    throw new Exception("Função getDB() não encontrada no config.php");
                }
                
                $db = getDB();
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo "<div class='status-box success'>✅ Conexão com Banco de Dados estabelecida!</div>";
                
                // 2. Lê e Executa SQL
                if (!file_exists('database.sql')) {
                    throw new Exception("Arquivo <code>database.sql</code> não encontrado na raiz!");
                }
                
                $sqlContent = file_get_contents('database.sql');
                // Remove delimitadores e limpa comentários para evitar erros de parser simples
                $sqlContent = str_replace(['DELIMITER //', 'DELIMITER ;', '//', '$$'], '', $sqlContent);
                $sqlContent = cleanSQL($sqlContent);
                
                $statements = explode(';', $sqlContent);
                $executed = 0;
                $warnings = 0;

                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $db->exec($statement);
                            $executed++;
                        } catch (PDOException $e) {
                            // Ignora erros comuns de "tabela já existe"
                            if (strpos($e->getMessage(), 'already exists') !== false || 
                                strpos($e->getMessage(), 'Duplicate') !== false) {
                                $warnings++;
                            } else {
                                echo "<div class='status-box error'>⚠️ Erro SQL: " . htmlspecialchars($e->getMessage()) . "</div>";
                            }
                        }
                    }
                }
                
                echo "<div class='status-box success'>✅ Banco de dados atualizado ({$executed} comandos). " . ($warnings > 0 ? "<small>(Ignorados {$warnings} itens já existentes)</small>" : "") . "</div>";

                // 3. Verifica se tabela principal existe
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM orders");
                    $count = $stmt->fetchColumn();
                    echo "<div class='status-box info'>📊 Tabela 'orders' verificada: <strong>{$count}</strong> pedidos existentes.</div>";
                } catch (PDOException $e) {
                    echo "<div class='status-box error'>❌ A tabela 'orders' não foi criada corretamente. Verifique o arquivo SQL.</div>";
                }
                
                // 4. Cria diretórios
                $dirs = ['logs', 'uploads', 'cache'];
                echo "<div class='status-box info'>";
                foreach ($dirs as $dir) {
                    if (!is_dir($dir)) {
                        if (mkdir($dir, 0755, true)) {
                            echo "✅ Pasta <code>$dir</code> criada.<br>";
                        } else {
                            echo "❌ Falha ao criar <code>$dir</code> (Permissão negada).<br>";
                        }
                    } else {
                        echo "📂 Pasta <code>$dir</code> já existe.<br>";
                    }
                }
                echo "</div>";
                
                echo "</div>"; // Fim do step

                // Mensagem Final
                echo "<div class='step' style='text-align: center;'>";
                echo "<h3>🎉 Instalação Concluída!</h3>";
                echo "<p style='margin: 15px 0;'>O sistema está pronto para uso.</p>";
                echo "<div style='display: grid; gap: 10px; grid-template-columns: 1fr 1fr;'>";
                echo "<a href='TEST_SYSTEM.php'><button type='button' style='background: #4caf50;'>Testar Sistema</button></a>";
                echo "<a href='admin.php'><button type='button' style='background: #2196f3;'>Painel Admin</button></a>";
                echo "</div>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='status-box error'>";
                echo "<h3>❌ Erro Fatal</h3>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "<p><small>Verifique as variáveis de ambiente no Railway.</small></p>";
                echo "</div>";
            }
            
        } else {
            // TELA INICIAL (ANTES DE CLICAR EM INSTALAR)
        ?>
        
        <div class="step">
            <h3>📡 Status do Ambiente Railway</h3>
            
            <!-- Check MySQL -->
            <div class="status-box <?php echo getenv('MYSQLHOST') ? 'success' : 'error'; ?>">
                <strong>Banco de Dados (MySQL):</strong><br>
                <?php if (getenv('MYSQLHOST')): ?>
                    ✅ Conectado ao Host: <?php echo getenv('MYSQLHOST'); ?><br>
                    ✅ Banco: <?php echo getenv('MYSQLDATABASE'); ?>
                <?php else: ?>
                    ❌ Variáveis MYSQL não detectadas!<br>
                    <small>Adicione o plugin MySQL no painel da Railway.</small>
                <?php endif; ?>
            </div>

            <!-- Check APIs -->
            <div class="status-box <?php echo (defined('API_KEY') && API_KEY != '') ? 'success' : 'error'; ?>">
                <strong>Configuração de APIs:</strong><br>
                <?php if (defined('API_KEY') && API_KEY != ''): ?>
                    ✅ API Key RevisionSMM: Configurada<br>
                    ✅ API Asaas: <?php echo defined('ASAAS_API_KEY') ? 'Configurada' : 'Ausente'; ?>
                <?php else: ?>
                    ❌ API_KEY não definida no config.php ou variáveis de ambiente.<br>
                    <small>Vá em Variables no Railway e adicione: API_KEY, ASAAS_API_KEY, SERVICE_ID</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="step">
            <h3>⚙️ Ações da Instalação:</h3>
            <ul style="margin-left: 20px; color: #555; line-height: 1.8;">
                <li>📂 Importar estrutura do banco de dados (<code>database.sql</code>)</li>
                <li>📁 Criar pastas de sistema (logs, uploads, cache)</li>
                <li>🔗 Testar conexão com provedores</li>
            </ul>
        </div>

        <form method="post">
            <button type="submit" name="install">🚀 Iniciar Instalação</button>
        </form>

        <?php
        }
        ?>
        
        <p style="text-align: center; margin-top: 20px; color: #888; font-size: 0.8em;">
            InstaBoost Installer v2.0 &bull; Railway Edition
        </p>
    </div>
</body>
</html>
