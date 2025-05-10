CREATE DATABASE IF NOT EXISTS aitp;
USE aitp;

-- Set UTF-8 character set
SET NAMES utf8mb4;

-- Users Table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- stored encrypted
    balance DECIMAL(10, 2) DEFAULT 0.00,
    role ENUM('admin', 'user', 'guest') DEFAULT 'user',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files Table
CREATE TABLE Files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL, -- in kilobytes
    page_count INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'deleted') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    INDEX idx_user_file (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Printers Table
CREATE TABLE Printers (
    printer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    status ENUM('online', 'offline', 'maintenance', 'error') DEFAULT 'online',
    capabilities JSON, -- contains printer capabilities (color, duplex, etc.)
    paper_level INT DEFAULT 100, -- percentage of remaining paper
    toner_level INT DEFAULT 100, -- percentage of remaining toner
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_maintenance TIMESTAMP NULL,
    notes TEXT,
    INDEX idx_printer_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pricing Rules Table
CREATE TABLE Pricing_Rules (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color_price DECIMAL(10, 2) NOT NULL, -- price per color page
    bw_price DECIMAL(10, 2) NOT NULL, -- price per black and white page
    double_sided_discount DECIMAL(5, 2) DEFAULT 0.00, -- discount percentage for double-sided printing
    bulk_discount JSON, -- bulk discounts like {"50": 0.05, "100": 0.10}
    special_papers JSON, -- prices for special paper types
    user_role VARCHAR(50) DEFAULT NULL, -- if rule is specific to certain user roles
    is_active BOOLEAN DEFAULT TRUE,
    valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_to TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    INDEX idx_price_rule_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Print Jobs Table
CREATE TABLE Print_Jobs (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_id INT NOT NULL,
    printer_id INT NOT NULL,
    job_name VARCHAR(100) NOT NULL,
    copies INT NOT NULL DEFAULT 1,
    pages VARCHAR(255) DEFAULT 'all', -- can be "all" or "1-5, 8, 11-13"
    orientation ENUM('portrait', 'landscape') DEFAULT 'portrait',
    paper_size VARCHAR(20) DEFAULT 'A4',
    color_mode ENUM('color', 'bw') DEFAULT 'bw',
    double_sided BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    cost DECIMAL(10, 2) NOT NULL,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES Files(file_id) ON DELETE CASCADE,
    FOREIGN KEY (printer_id) REFERENCES Printers(printer_id) ON DELETE CASCADE,
    INDEX idx_job_status (status),
    INDEX idx_job_user (user_id),
    INDEX idx_job_printer (printer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job Logs Table
CREATE TABLE Job_Logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES Print_Jobs(job_id) ON DELETE CASCADE,
    INDEX idx_log_job (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions Table
CREATE TABLE Transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NULL, -- transaction may not be related to a print job (e.g., balance top-up)
    type ENUM('deposit', 'withdrawal', 'job_payment', 'refund') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    balance_before DECIMAL(10, 2) NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
    payment_method VARCHAR(50) DEFAULT NULL,
    reference_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES Print_Jobs(job_id) ON DELETE SET NULL,
    INDEX idx_transaction_user (user_id),
    INDEX idx_transaction_job (job_id),
    INDEX idx_transaction_type (type),
    INDEX idx_transaction_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NULL, -- sender can be a user or the system
    receiver_id INT NULL, -- receiver can be a user or a group
    printer_id INT NULL, -- notifications can be related to a specific printer
    job_id INT NULL, -- notifications can be related to a specific print job
    type VARCHAR(50) NOT NULL, -- notification type (error, warning, info)
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (printer_id) REFERENCES Printers(printer_id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES Print_Jobs(job_id) ON DELETE CASCADE,
    INDEX idx_notification_receiver (receiver_id),
    INDEX idx_notification_read (is_read),
    INDEX idx_notification_printer (printer_id),
    INDEX idx_notification_job (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table
CREATE TABLE System_Settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES Users(user_id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stored Procedure for Updating User Balance
DELIMITER $$
CREATE PROCEDURE update_user_balance(
    IN p_user_id INT,
    IN p_amount DECIMAL(10, 2),
    IN p_type ENUM('deposit', 'withdrawal', 'job_payment', 'refund'),
    IN p_job_id INT,
    IN p_description TEXT,
    IN p_payment_method VARCHAR(50),
    IN p_reference_id VARCHAR(100)
)
BEGIN
    DECLARE current_balance DECIMAL(10, 2);
    DECLARE new_balance DECIMAL(10, 2);
    
    -- Get current balance
    SELECT balance INTO current_balance FROM Users WHERE user_id = p_user_id FOR UPDATE;
    
    -- Calculate new balance
    IF p_type IN ('deposit', 'refund') THEN
        SET new_balance = current_balance + p_amount;
    ELSE
        SET new_balance = current_balance - p_amount;
    END IF;
    
    -- Update user balance
    UPDATE Users SET balance = new_balance WHERE user_id = p_user_id;
    
    -- Create transaction record
    INSERT INTO Transactions (
        user_id,
        job_id,
        type,
        amount,
        balance_before,
        balance_after,
        description,
        payment_method,
        reference_id,
        processed_at
    ) VALUES (
        p_user_id,
        p_job_id,
        p_type,
        p_amount,
        current_balance,
        new_balance,
        p_description,
        p_payment_method,
        p_reference_id,
        CURRENT_TIMESTAMP
    );
END $$
DELIMITER ;

-- Trigger for Logging Print Job Status Changes
DELIMITER $$
CREATE TRIGGER after_print_job_update
AFTER UPDATE ON Print_Jobs
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO Job_Logs (job_id, status, message)
        VALUES (NEW.job_id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
END $$
DELIMITER ;

-- Views for Common Queries
-- View for Print Jobs with User, File, and Printer Information
CREATE VIEW view_print_jobs AS
SELECT 
    pj.job_id,
    pj.job_name,
    pj.status,
    pj.cost,
    pj.created_at,
    pj.completed_at,
    u.user_id,
    u.name AS user_name,
    u.email AS user_email,
    f.file_id,
    f.file_name,
    f.page_count,
    p.printer_id,
    p.name AS printer_name,
    p.location AS printer_location
FROM 
    Print_Jobs pj
    JOIN Users u ON pj.user_id = u.user_id
    JOIN Files f ON pj.file_id = f.file_id
    JOIN Printers p ON pj.printer_id = p.printer_id;

-- View for Transactions with User Information
CREATE VIEW view_transactions AS
SELECT 
    t.transaction_id,
    t.type,
    t.amount,
    t.balance_before,
    t.balance_after,
    t.status,
    t.created_at,
    u.user_id,
    u.name AS user_name,
    u.email AS user_email,
    pj.job_id,
    pj.job_name
FROM 
    Transactions t
    JOIN Users u ON t.user_id = u.user_id
    LEFT JOIN Print_Jobs pj ON t.job_id = pj.job_id;

-- Initial Data for the System
-- Insert admin user
INSERT INTO Users (name, email, password, role, balance)
VALUES ('System Administrator', 'admin@printingsystem.com', '$2y$10$n5WOqaiqGcVKUKuqgk.8p.h1fF2BHmqYkSNFq2bB9vPUOKXZiNGVK', 'admin', 1000);

-- Insert basic pricing rules
INSERT INTO Pricing_Rules (name, color_price, bw_price, double_sided_discount)
VALUES 
('Basic Rule', 2.00, 0.50, 0.20),
('Students', 1.50, 0.40, 0.25),
('Staff', 1.75, 0.45, 0.20);

-- Insert default printers
INSERT INTO Printers (name, location, model, status, capabilities)
VALUES 
('Reception Printer', 'Ground Floor - Reception', 'HP LaserJet Pro M404', 'online', '{"color": true, "duplex": true, "formats": ["A4", "A3", "Letter"]}'),
('Library Printer', 'First Floor - Library', 'Canon MAXIFY GX7020', 'online', '{"color": true, "duplex": true, "formats": ["A4", "Letter"]}'),
('Main Hall Printer', 'Second Floor - Main Hall', 'Epson EcoTank L8180', 'maintenance', '{"color": true, "duplex": true, "formats": ["A4", "A3", "Letter", "Legal"]}');

-- Insert system settings
INSERT INTO System_Settings (setting_key, setting_value, description, updated_by)
VALUES 
('site_name', 'Print Management System', 'System name shown in the user interface', 1),
('currency', 'USD', 'System currency', 1),
('low_balance_threshold', '10', 'Minimum balance threshold for notifications', 1),
('max_file_size', '20', 'Maximum file size in megabytes', 1),
('allowed_file_types', 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,jpg,jpeg,png', 'Allowed file types', 1),
('system_email', 'no-reply@printingsystem.com', 'System email address', 1);
