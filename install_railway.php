<?php
/**
 * Instalador Railway - InstaBoost
 * Correção: Força HTML mesmo se o config.php pedir JSON
 */

// 1. Carrega as configurações do sistema
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    die("Erro: config.php não encontrado.");
}

// 2. CORREÇÃO CRÍTICA:
// O config.php define o cabeçalho como JSON (para a API).
// Aqui nós sobrescrevemos para HTML para o navegador montar a página colorida.
header('Content-Type: text/html; charset=utf-8');

// 3. Função auxiliar para limpar SQL
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
            font-family: 'Segoe UI', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        h1 { color: #4a148c; text-align: center; margin-bottom: 25px; font-weight: 700; }
        h3 { color: #333; margin-top: 0; }
        .step { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 15px; border: 1px solid #e9ecef; }
        
        .status-box { padding: 12px; border-radius: 6px; margin-top: 10px; font-weight: 500; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

        button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
            margin-top: 10px;
        }
        button:hover { opacity: 0.9; }
        ul { margin-left: 20px; margin-top: 10px; line-height: 1.6; }
        a { text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚂 Instalação Railway</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            
            echo "<div class='step'>";
            echo "<h3>📦 Executando Instalação...</h3>";
            
            try {
                // Tenta conectar (Se falhar, o config.php pode matar o script com die(), então verifique o log)
                $db = getDB(); 
                echo "<div class='status-box success'>✅ Conexão com Banco de Dados OK!</div>";

                // Ler SQL
                if (!file_exists('database.sql')) {
                    throw new Exception("O arquivo database.sql não foi encontrado na raiz.");
                }

                $sqlOriginal = file_get_contents('database.sql');
                // Remove delimitadores que causam erro no PDO
                $sqlClean = str_replace(['DELIMITER //', 'DELIMITER ;', '//', '$$'], '', $sqlOriginal);
                $sqlClean = cleanSQL($sqlClean);
                
                // Executa os comandos um por um
                $statements = explode(';', $sqlClean);
                $count = 0;
                
                foreach ($statements as $stmt) {
                    if (trim($stmt) !== '') {
                        try {
                            $db->exec(trim($stmt));
                            $count++;
                        } catch (PDOException $e) {
                            // Ignora erro se tabela já existe
                            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), 'Duplicate') === false) {
                                // Se for outro erro, exibe
                                echo "<div class='status-box error'>⚠️ Aviso SQL: " . $e->getMessage() . "</div>";
                            }
                        }
                    }
                }
                
                echo "<div class='status-box success'>✅ Banco de dados configurado ({$count} comandos processados).</div>";

                // Criar Pastas
                $dirs = ['logs', 'uploads', 'cache'];
                $dirsCreated = 0;
                foreach($dirs as $dir) {
                    if(!is_dir($dir)) {
                        if(mkdir($dir, 0755, true)) $dirsCreated++;
                    }
                }
                echo "<div class='status-box info'>📂 Estrutura de pastas verificada.</div>";

                echo "</div>"; // Fim step

                // Botões Finais
                echo "<div class='step' style='text-align: center;'>";
                echo "<h3>🎉 Tudo Pronto!</h3>";
                echo "<p>O sistema foi instalado com sucesso.</p>";
                echo "<div style='margin-top: 20px; display: grid; gap: 10px;'>";
                echo "<a href='TEST_SYSTEM.php'><button type='button' style='background: #28a745;'>Testar Sistema (TEST_SYSTEM.php)</button></a>";
                echo "<a href='admin.php'><button type='button' style='background: #17a2b8;'>Ir para Painel Admin</button></a>";
                echo "</div>";
                echo "</div>";

            } catch (Exception $e) {
                echo "<div class='status-box error'>";
                echo "<strong>❌ Erro Fatal:</strong> " . $e->getMessage();
                echo "</div>";
            }
            
        } else {
        ?>
        
            <div class="step">
                <h3>🔍 Verificação de Ambiente</h3>
                
                <?php if(getenv('MYSQLHOST')): ?>
                    <div class="status-box success">
                        ✅ <strong>Railway MySQL Detectado</strong><br>
                        Host: <?php echo getenv('MYSQLHOST'); ?><br>
                        Banco: <?php echo getenv('MYSQLDATABASE'); ?>
                    </div>
                <?php else: ?>
                    <div class="status-box error">
                        ❌ <strong>Variáveis MySQL não encontradas!</strong><br>
                        Verifique se você adicionou o serviço MySQL no Canvas do Railway.
                    </div>
                <?php endif; ?>

                <?php if(defined('API_KEY') && strlen(API_KEY) > 10): ?>
                    <div class="status-box success">✅ API Key configurada</div>
                <?php else: ?>
                    <div class="status-box info">⚠️ API Key usando valor padrão ou vazia</div>
                <?php endif; ?>
            </div>

            <div class="step">
                <h3>⚙️ O que será feito?</h3>
                <ul>
                    <li>Criação das tabelas (pedidos, logs, etc)</li>
                    <li>Criação das pastas de uploads e cache</li>
                    <li>Configuração inicial do sistema</li>
                </ul>
            </div>
            
            <form method="post">
                <button type="submit" name="install">🚀 CLIQUE AQUI PARA INSTALAR</button>
            </form>

        <?php } ?>
    </div>
</body>
</html>
