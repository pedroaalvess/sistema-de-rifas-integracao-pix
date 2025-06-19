-- Create database if not exists
CREATE DATABASE IF NOT EXISTS raffle_system;
USE raffle_system;

-- Campaigns table
CREATE TABLE IF NOT EXISTS campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    unit_price DECIMAL(10,2) NOT NULL,
    combo_prices JSON,
    draw_date DATETIME NOT NULL,
    status ENUM('active', 'inactive', 'drawn') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Buyers table
CREATE TABLE IF NOT EXISTS buyers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    cellphone VARCHAR(15) NOT NULL,
    email VARCHAR(255) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    campaign_id INT NOT NULL,
    payment_status ENUM('pending', 'awaiting_confirmation', 'paid', 'cancelled') DEFAULT 'pending',
    pix_code TEXT,
    qr_code_url VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    combo_type VARCHAR(50),
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES buyers(id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Raffle numbers table
CREATE TABLE IF NOT EXISTS raffle_numbers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    campaign_id INT NOT NULL,
    number INT NOT NULL,
    status ENUM('reserved', 'paid', 'cancelled') DEFAULT 'reserved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
    UNIQUE KEY unique_number_campaign (campaign_id, number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction logs table
CREATE TABLE IF NOT EXISTS transaction_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT,
    event_type VARCHAR(50) NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123 - should be changed after first login)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
