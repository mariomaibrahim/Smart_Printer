CREATE DATABASE IF NOT EXISTS aitp;
USE aitp;
-- Drop existing tables if they exist (for clean setup)
DROP TABLE IF EXISTS daily_reports;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS print_jobs;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- For storing hash
    balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email) -- Index for faster login queries
) ENGINE=InnoDB;

-- Create print_jobs table
CREATE TABLE print_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    num_pages INT UNSIGNED NOT NULL,
    num_copies INT UNSIGNED NOT NULL DEFAULT 1,
    color_mode ENUM('black_white', 'color') NOT NULL DEFAULT 'black_white',
    print_sides ENUM('single', 'double') NOT NULL DEFAULT 'single',
    orientation ENUM('portrait', 'landscape') NOT NULL DEFAULT 'portrait',
    cost DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'in_progress', 'done', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    printed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_jobs (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Create notifications table
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
    seen BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id),
    INDEX idx_unseen (user_id, seen) -- For quick retrieval of unseen notifications
) ENGINE=InnoDB;

-- Create admin_logs table
CREATE TABLE admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_name VARCHAR(100) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    operation ENUM('add', 'deduct') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_logs (user_id),
    INDEX idx_operation_date (operation, created_at)
) ENGINE=InnoDB;

-- Create daily_reports table
CREATE TABLE daily_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    total_print_jobs INT UNSIGNED NOT NULL DEFAULT 0,
    total_pages_printed INT UNSIGNED NOT NULL DEFAULT 0,
    total_income DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_unique_date (report_date) -- Ensure one report per day
) ENGINE=InnoDB;

-- Create triggers to update daily reports automatically

-- Trigger to update daily reports when a print job is completed
DELIMITER //
CREATE TRIGGER update_daily_report_after_print 
AFTER UPDATE ON print_jobs
FOR EACH ROW
BEGIN
    IF NEW.status = 'done' AND OLD.status != 'done' THEN
        -- Try to update existing record for today
        INSERT INTO daily_reports (report_date, total_print_jobs, total_pages_printed, total_income)
        VALUES (CURDATE(), 1, NEW.num_pages * NEW.num_copies, NEW.cost)
        ON DUPLICATE KEY UPDATE
            total_print_jobs = total_print_jobs + 1,
            total_pages_printed = total_pages_printed + (NEW.num_pages * NEW.num_copies),
            total_income = total_income + NEW.cost;
    END IF;
END //
DELIMITER ;

-- Stored procedure for adding funds to user balance (for admin use)
DELIMITER //
CREATE PROCEDURE add_funds_to_user(
    IN p_admin_name VARCHAR(100),
    IN p_user_id INT UNSIGNED,
    IN p_amount DECIMAL(10, 2),
    IN p_reason TEXT
)
BEGIN
    -- Update user balance
    UPDATE users 
    SET balance = balance + p_amount 
    WHERE id = p_user_id;
    
    -- Log the operation
    INSERT INTO admin_logs (admin_name, user_id, operation, amount, reason)
    VALUES (p_admin_name, p_user_id, 'add', p_amount, p_reason);
    
    -- Create a notification for the user
    INSERT INTO notifications (user_id, message, type)
    VALUES (p_user_id, CONCAT('$', p_amount, ' has been added to your balance.'), 'success');
END //
DELIMITER ;

-- Stored procedure for deducting funds from user balance (for admin use)
DELIMITER //
CREATE PROCEDURE deduct_funds_from_user(
    IN p_admin_name VARCHAR(100),
    IN p_user_id INT UNSIGNED,
    IN p_amount DECIMAL(10, 2),
    IN p_reason TEXT
)
BEGIN
    DECLARE current_balance DECIMAL(10, 2);
    
    -- Get current balance
    SELECT balance INTO current_balance FROM users WHERE id = p_user_id;
    
    -- Check if user has sufficient balance
    IF current_balance >= p_amount THEN
        -- Update user balance
        UPDATE users 
        SET balance = balance - p_amount 
        WHERE id = p_user_id;
        
        -- Log the operation
        INSERT INTO admin_logs (admin_name, user_id, operation, amount, reason)
        VALUES (p_admin_name, p_user_id, 'deduct', p_amount, p_reason);
        
        -- Create a notification for the user
        INSERT INTO notifications (user_id, message, type)
        VALUES (p_user_id, CONCAT('$', p_amount, ' has been deducted from your balance.'), 'info');
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient balance';
    END IF;
END //
DELIMITER ;

-- Stored procedure for initiating a print job
DELIMITER //
CREATE PROCEDURE initiate_print_job(
    IN p_user_id INT UNSIGNED,
    IN p_file_name VARCHAR(255),
    IN p_file_path VARCHAR(255),
    IN p_num_pages INT UNSIGNED,
    IN p_num_copies INT UNSIGNED,
    IN p_color_mode ENUM('black_white', 'color'),
    IN p_print_sides ENUM('single', 'double'),
    IN p_orientation ENUM('portrait', 'landscape'),
    IN p_cost DECIMAL(10, 2)
)
BEGIN
    DECLARE current_balance DECIMAL(10, 2);
    
    -- Get current balance
    SELECT balance INTO current_balance FROM users WHERE id = p_user_id;
    
    -- Check if user has sufficient balance
    IF current_balance >= p_cost THEN
        -- Deduct cost from user balance
        UPDATE users 
        SET balance = balance - p_cost 
        WHERE id = p_user_id;
        
        -- Create the print job
        INSERT INTO print_jobs (
            user_id, file_name, file_path, num_pages, num_copies,
            color_mode, print_sides, orientation, cost, status, confirmed_at
        )
        VALUES (
            p_user_id, p_file_name, p_file_path, p_num_pages, p_num_copies,
            p_color_mode, p_print_sides, p_orientation, p_cost, 'pending', CURRENT_TIMESTAMP
        );
        
        -- Create a notification for the user
        INSERT INTO notifications (user_id, message, type)
        VALUES (p_user_id, CONCAT('Print job "', p_file_name, '" has been submitted.'), 'info');
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient balance for print job';
    END IF;
END //
DELIMITER ;

-- Create views for common queries

-- View for unread notifications by user
CREATE VIEW vw_unread_notifications AS
SELECT user_id, COUNT(*) as unread_count
FROM notifications
WHERE seen = FALSE
GROUP BY user_id;

-- View for user printing history with details
CREATE VIEW vw_user_print_history AS
SELECT 
    u.id as user_id,
    u.name as user_name,
    pj.id as job_id,
    pj.file_name,
    pj.num_pages,
    pj.num_copies,
    pj.color_mode,
    pj.print_sides,
    pj.orientation,
    pj.cost,
    pj.status,
    pj.created_at,
    pj.printed_at
FROM users u
JOIN print_jobs pj ON u.id = pj.user_id
ORDER BY pj.created_at DESC;

-- View for monthly report statistics
CREATE VIEW vw_monthly_reports AS
SELECT 
    DATE_FORMAT(report_date, '%Y-%m') as month,
    SUM(total_print_jobs) as monthly_jobs,
    SUM(total_pages_printed) as monthly_pages,
    SUM(total_income) as monthly_income
FROM daily_reports
GROUP BY DATE_FORMAT(report_date, '%Y-%m')
ORDER BY month DESC;

-- Insert users with generated emails and a standard initial password
-- Note: Administrators should force users to change their password on first login

-- Disable safe update mode temporarily
SET SQL_SAFE_UPDATES = 0;

-- Use a strong initial password (to be changed on first login)
-- In a real-world scenario, use a proper password hashing mechanism
INSERT INTO users (id, name, email, password, balance) VALUES
(2320241, 'Zeyad Rabea Abd El-Hamid', '2320241@gmail.com', MD5('aitp25'), 100.00),
(2320603, 'Maryam Eid Abdelsalam', '2320603@gmail.com', MD5('aitp25'), 100.00),
(2320326, 'Abdulrahman Khamis Abdo', '2320326@gmail.com', MD5('aitp25'), 100.00),
(2320707, 'Hager Ragab El-Said Madian', '2320707@gmail.com', MD5('aitp25'), 100.00),
(2320630, 'Malk Saif Alden Attia', '2320630@gmail.com', MD5('aitp25'), 100.00),
(2320604, 'Mariam Mohamed Mubarak', '2320604@gmail.com', MD5('aitp25'), 100.00),
(2320269, 'Seif Eldeen Ehab Mohamed', '2320269@gmail.com', MD5('aitp25'), 100.00),
(2320605, 'Mariam Medhat Omar Gewely', '2320605@gmail.com', MD5('aitp25'), 100.00),
(2320712, 'Hager Taha Hassan Mohamed', '2320712@gmail.com', MD5('aitp25'), 100.00),
(2320677, 'Nada Mohammed Ragab', '2320677@gmail.com', MD5('aitp25'), 100.00),
(2320728, 'Wafaa Abdul Raouf Mohammed', '2320728@gmail.com', MD5('aitp25'), 100.00),
(2320091, 'Osama El-Sayed Ali', '2320091@gmail.com', MD5('aitp25'), 100.00),
(2320633, 'Manar Ashraf Mohamed', '2320633@gmail.com', MD5('aitp25'), 100.00),
(2320499, 'Mohammed Hamada Mohaseb', '2320499@gmail.com', MD5('aitp25'), 100.00),
(2020312, 'Abdulrahman Ahmed Attia', '2020312@gmail.com', MD5('aitp25'), 100.00),
(2320498, 'Mohamed Hossam Abdelfatah', '2320498@gmail.com', MD5('aitp25'), 100.00),
(2320523, 'Mohamed Abdelhamed El- Sayed', '2320523@gmail.com', MD5('aitp25'), 100.00),
(2320636, 'Manar Mahmoud El-Sayed', '2320636@gmail.com', MD5('aitp25'), 100.00),
(2320337, 'Abdelrahman Mohamed Said', '2320337@gmail.com', MD5('aitp25'), 100.00),
(2320721, 'Huda Hamdy Ibrahim', '2320721@gmail.com', MD5('aitp25'), 100.00),
(2320450, 'Karim Ahmed Mohamed', '2320450@gmail.com', MD5('aitp25'), 100.00),
(2320310, 'Abd El-Hamid Mohammed Abd El-Hamid', '2320310@gmail.com', MD5('aitp25'), 100.00),
(2320222, 'Rahma Saleh Ramadan', '2320222@gmail.com', MD5('aitp25'), 100.00),
(2320280, 'Shrouk Hesham Mohamed', '2320280@gmail.com', MD5('aitp25'), 100.00),
(2320598, 'Mariam Ibrahim Mohamed', '2320598@gmail.com', MD5('aitp25'), 100.00),
(2320220, 'Rahma El-Shahat Nour', '2320220@gmail.com', MD5('aitp25'), 100.00),
(2320155, 'Aya Mokhtar Eid', '2320155@gmail.com', MD5('aitp25'), 100.00),
(2320583, 'Mahmoud Arafa Abdel Halim', '2320583@gmail.com', MD5('aitp25'), 100.00),
(2320160, 'Basma Abd al Rasoul', '2320160@gmail.com', MD5('aitp25'), 100.00),
(2320338, 'Abdulrahman Muhammad Salim Khalil', '2320338@gmail.com', MD5('aitp25'), 100.00),
(2320334, 'Abdulrahman Awad Daif Allah Awad', '2320334@gmail.com', MD5('aitp25'), 100.00),
(2320344, 'Abdulrahman Muhammad Yusuf Abdul Mawjoud', '2320344@gmail.com', MD5('aitp25'), 100.00),
(2320296, 'Shahd Muhammad Rajab Turki', '2320296@gmail.com', MD5('aitp25'), 100.00),
(2320286, 'Shihab al-Din Muhammad Ahmad Hashim', '2320286@gmail.com', MD5('aitp25'), 100.00),
(2320210, 'Hanin Mohammed Murad Mohammed', '2320210@gmail.com', MD5('aitp25'), 100.00);

-- Re-enable safe update mode
SET SQL_SAFE_UPDATES = 1;

-- Optional: Create notifications for first-time login
INSERT INTO notifications (user_id, message, type)
SELECT id, 'Welcome! Please change your password upon first login.', 'info'
FROM users;

-- Helpful View/Verification Query
SELECT id, name, email FROM users;

ALTER TABLE print_jobs ADD COLUMN page_range VARCHAR(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS transactions (
  id int(11) UNSIGNED NOT NULL,
  user_id int(11) UNSIGNED NOT NULL,
  amount decimal(10,2) NOT NULL,
  type enum('deposit','withdrawal','print_payment') NOT NULL,
  reference_id int(11) UNSIGNED DEFAULT NULL COMMENT 'مرجع إلى print_jobs أو عمليات أخرى',
  description varchar(255) DEFAULT NULL,
  balance_before decimal(10,2) NOT NULL,
  balance_after decimal(10,2) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;