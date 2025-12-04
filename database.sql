-- Database: instaboost_db
-- Criação do banco de dados simplificado

CREATE DATABASE IF NOT EXISTS instaboost_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE instaboost_db;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_hash VARCHAR(50) UNIQUE NOT NULL,
    
    -- Dados do pedido (SEM dados pessoais)
    instagram_link TEXT NOT NULL,
    quantity INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    
    -- Dados do Asaas
    asaas_charge_id VARCHAR(100),
    payment_url TEXT,
    pix_code TEXT,
    pix_qrcode_image LONGTEXT,
    
    -- Dados da API RevisionSMM
    api_order_id VARCHAR(100),
    
    -- Status do pedido
    status ENUM('pending', 'paid', 'processing', 'completed', 'error', 'cancelled') DEFAULT 'pending',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    -- Índices
    INDEX idx_order_hash (order_hash),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_asaas_charge (asaas_charge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL, -- 'order', 'payment', 'api', 'error'
    order_id INT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_log_type (log_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de estatísticas (para dashboard)
CREATE TABLE IF NOT EXISTS stats_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE UNIQUE NOT NULL,
    total_orders INT DEFAULT 0,
    total_paid INT DEFAULT 0,
    total_completed INT DEFAULT 0,
    total_revenue DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View para estatísticas em tempo real
CREATE OR REPLACE VIEW dashboard_stats AS
SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_orders,
    SUM(CASE WHEN status IN ('paid', 'processing', 'completed') THEN total_amount ELSE 0 END) as total_revenue,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
    SUM(CASE WHEN DATE(created_at) = CURDATE() AND status IN ('paid', 'processing', 'completed') THEN total_amount ELSE 0 END) as today_revenue,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_orders,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_orders
FROM orders;

-- Procedure para atualizar estatísticas diárias (rodar via cron)
DELIMITER //
CREATE PROCEDURE update_daily_stats()
BEGIN
    INSERT INTO stats_daily (stat_date, total_orders, total_paid, total_completed, total_revenue)
    SELECT 
        DATE(created_at) as stat_date,
        COUNT(*) as total_orders,
        SUM(CASE WHEN status IN ('paid', 'processing', 'completed') THEN 1 ELSE 0 END) as total_paid,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
        SUM(CASE WHEN status IN ('paid', 'processing', 'completed') THEN total_amount ELSE 0 END) as total_revenue
    FROM orders
    WHERE DATE(created_at) = CURDATE()
    GROUP BY DATE(created_at)
    ON DUPLICATE KEY UPDATE
        total_orders = VALUES(total_orders),
        total_paid = VALUES(total_paid),
        total_completed = VALUES(total_completed),
        total_revenue = VALUES(total_revenue);
END//
DELIMITER ;

-- Trigger para criar log automático quando pedido é criado
DELIMITER //
CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO logs (log_type, order_id, message, data)
    VALUES ('order', NEW.id, 'Novo pedido criado', JSON_OBJECT(
        'order_hash', NEW.order_hash,
        'quantity', NEW.quantity,
        'total', NEW.total_amount
    ));
END//
DELIMITER ;

-- Trigger para criar log quando status muda
DELIMITER //
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO logs (log_type, order_id, message, data)
        VALUES ('order', NEW.id, CONCAT('Status alterado: ', OLD.status, ' → ', NEW.status), JSON_OBJECT(
            'old_status', OLD.status,
            'new_status', NEW.status,
            'order_hash', NEW.order_hash
        ));
    END IF;
END//
DELIMITER ;

-- Inserir alguns dados de exemplo para teste (opcional - remova em produção)
-- INSERT INTO orders (order_hash, customer_name, customer_email, customer_cpf, instagram_link, quantity, total_amount, status)
-- VALUES ('ORD-TEST123', 'Teste Silva', 'teste@email.com', '12345678900', 'https://instagram.com/teste', 1000, 10.00, 'pending');
