<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    die("Erro: O arquivo config.php não foi encontrado. Envie ele para o Railway.");
}


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
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 { color: #4a148c; text-align: center; margin-bottom: 20px; }
        .step { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #ddd; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-top: 5px; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin-top: 5px; }
        button {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover { background: #5a6fd6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚂 Instalação Railway</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
            try {
                // Conexão
                $db = getDB();
                echo "<div class='step'><p>✅ Conectado ao Banco de Dados!</p></div>";

                // Ler SQL
                if (!file_exists('database.sql')) throw new Exception("Arquivo database.sql não encontrado!");
                $sql = file_get_contents('database.sql');
                $sql = str_replace(['DELIMITER //', 'DELIMITER ;', '//', '$$'], '', $sql);
                $sql = cleanSQL($sql);
                
                // Executar SQL
                $stmt = $db->prepare($sql);
                $stmt->execute();
                
                echo "<div class='success'>✅ Tabelas criadas com sucesso!</div>";
                
                // Criar Pastas
                $dirs = ['logs', 'uploads', 'cache'];
                foreach($dirs as $dir) {
                    if(!is_dir($dir)) mkdir($dir, 0755, true);
                }
                echo "<div class='success'>✅ Pastas criadas!</div>";

                echo "<br><a href='TEST_SYSTEM.php'><button>Ir para Teste do Sistema</button></a>";

            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro: " . $e->getMessage() . "</div>";
            }
        } else {
        ?>
            <div class="step">
                <h3>Status:</h3>
                <?php if(getenv('MYSQLHOST')): ?>
                    <div class="success">✅ Banco de dados detectado</div>
                <?php else: ?>
                    <div class="error">❌ Banco de dados NÃO detectado</div>
                <?php endif; ?>
            </div>
            
            <form method="post">
                <button type="submit" name="install">🚀 INSTALAR AGORA</button>
            </form>
        <?php } ?>
    </div>
</body>
</html>
