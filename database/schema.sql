-- Transportation ERP System Database Schema
-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS transportation_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE transportation_erp;

-- ========================================
-- 1. USERS & AUTHENTICATION TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'accountant', 'driver', 'dispatcher') NOT NULL DEFAULT 'driver',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. FLEET MANAGEMENT TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('bus', 'truck', 'van', 'car') NOT NULL,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year YEAR NOT NULL,
    chassis_number VARCHAR(50) UNIQUE,
    engine_number VARCHAR(50) UNIQUE,
    capacity INT,
    purchase_price DECIMAL(15, 2),
    purchase_date DATE,
    status ENUM('active', 'maintenance', 'retired', 'sold') NOT NULL DEFAULT 'active',
    current_location VARCHAR(255),
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    depreciation_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chassis (chassis_number),
    INDEX idx_registration (registration_number),
    INDEX idx_status (status),
    INDEX idx_vehicle_type (vehicle_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'inspection', 'accident') NOT NULL,
    description TEXT NOT NULL,
    maintenance_date DATE NOT NULL,
    cost DECIMAL(15, 2) NOT NULL,
    vendor_name VARCHAR(100),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_maintenance_date (maintenance_date),
    INDEX idx_maintenance_type (maintenance_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fuel_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    fuel_date DATE NOT NULL,
    liters DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(15, 2) NOT NULL,
    mileage INT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_fuel_date (fuel_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. CHART OF ACCOUNTS TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS account_classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account_type (account_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_class_id INT NOT NULL,
    description TEXT,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    normal_balance ENUM('debit', 'credit') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    opening_balance DECIMAL(15, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_class_id) REFERENCES account_classes(id) ON DELETE RESTRICT,
    INDEX idx_account_number (account_number),
    INDEX idx_account_type (account_type),
    INDEX idx_account_class_id (account_class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. GENERAL LEDGER TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS journal_entries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_date DATE NOT NULL,
    entry_description TEXT NOT NULL,
    reference_type ENUM('revenue', 'expense', 'maintenance', 'fuel', 'waybill', 'adjustment') NOT NULL,
    reference_id INT,
    total_debit DECIMAL(15, 2) NOT NULL DEFAULT 0,
    total_credit DECIMAL(15, 2) NOT NULL DEFAULT 0,
    is_posted TINYINT(1) DEFAULT 0,
    posted_date DATETIME,
    posted_by INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_entry_date (entry_date),
    INDEX idx_is_posted (is_posted),
    INDEX idx_reference_type (reference_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_line_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id INT NOT NULL,
    account_id INT NOT NULL,
    debit DECIMAL(15, 2) DEFAULT 0,
    credit DECIMAL(15, 2) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    INDEX idx_journal_entry_id (journal_entry_id),
    INDEX idx_account_id (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS general_ledger (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT NOT NULL,
    debit DECIMAL(15, 2) DEFAULT 0,
    credit DECIMAL(15, 2) DEFAULT 0,
    balance DECIMAL(15, 2) DEFAULT 0,
    journal_entry_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    INDEX idx_account_id (account_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_journal_entry_id (journal_entry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. REVENUE TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS revenue_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS waybills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    waybill_number VARCHAR(50) UNIQUE NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT,
    departure_location VARCHAR(255) NOT NULL,
    destination_location VARCHAR(255) NOT NULL,
    departure_date DATE NOT NULL,
    arrival_date DATE,
    revenue_source_id INT,
    passenger_count INT DEFAULT 0,
    cargo_count INT DEFAULT 0,
    total_revenue DECIMAL(15, 2) NOT NULL,
    expenses DECIMAL(15, 2) DEFAULT 0,
    net_revenue DECIMAL(15, 2),
    status ENUM('pending', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (revenue_source_id) REFERENCES revenue_sources(id) ON DELETE SET NULL,
    INDEX idx_waybill_number (waybill_number),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_departure_date (departure_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cargo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    waybill_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    weight_kg DECIMAL(10, 2),
    volume_m3 DECIMAL(10, 4),
    cargo_value DECIMAL(15, 2),
    revenue_per_unit DECIMAL(15, 2),
    quantity INT DEFAULT 1,
    status ENUM('received', 'in_transit', 'delivered', 'lost', 'damaged') DEFAULT 'received',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (waybill_id) REFERENCES waybills(id) ON DELETE CASCADE,
    INDEX idx_waybill_id (waybill_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS revenue_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    revenue_date DATE NOT NULL,
    revenue_source_id INT NOT NULL,
    waybill_id INT,
    vehicle_id INT,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    account_id INT NOT NULL,
    reference_doc_number VARCHAR(50),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (revenue_source_id) REFERENCES revenue_sources(id) ON DELETE RESTRICT,
    FOREIGN KEY (waybill_id) REFERENCES waybills(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_revenue_date (revenue_date),
    INDEX idx_revenue_source_id (revenue_source_id),
    INDEX idx_vehicle_id (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. EXPENSE TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS expense_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    account_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expense_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expense_date DATE NOT NULL,
    expense_category_id INT NOT NULL,
    vehicle_id INT,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT NOT NULL,
    reference_doc_number VARCHAR(50),
    vendor_name VARCHAR(100),
    receipt_number VARCHAR(50),
    payment_method ENUM('cash', 'cheque', 'bank_transfer', 'card') DEFAULT 'cash',
    status ENUM('pending', 'approved', 'recorded') DEFAULT 'pending',
    approved_by INT,
    approved_date DATETIME,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_expense_date (expense_date),
    INDEX idx_expense_category_id (expense_category_id),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. DISPATCH MANAGEMENT TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS dispatch_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_date DATE NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    co_driver_id INT,
    route VARCHAR(255) NOT NULL,
    departure_time TIME,
    expected_arrival_time TIME,
    actual_arrival_time TIME,
    status ENUM('assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'assigned',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (co_driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_assignment_date (assignment_date),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 8. TRIAL BALANCE & REPORTS TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS trial_balance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    account_id INT NOT NULL,
    debit_balance DECIMAL(15, 2) DEFAULT 0,
    credit_balance DECIMAL(15, 2) DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_account (report_date, account_id),
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE RESTRICT,
    INDEX idx_report_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_type ENUM('trial_balance', 'income_statement', 'balance_sheet', 'cash_flow') NOT NULL,
    report_date DATE NOT NULL,
    start_date DATE,
    end_date DATE,
    report_data LONGTEXT,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_report_date (report_date),
    INDEX idx_report_type (report_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 9. AUDIT & LOGGING TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values LONGTEXT,
    new_values LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

INSERT INTO account_classes (code, name, account_type) VALUES
('1000', 'Assets', 'asset'),
('2000', 'Liabilities', 'liability'),
('3000', 'Equity', 'equity'),
('4000', 'Revenue', 'revenue'),
('5000', 'Expenses', 'expense');

INSERT INTO expense_categories (category_name, category_code, account_id) SELECT
'Fuel & Oil', 'EXP001', id FROM chart_of_accounts WHERE account_number = '5100' LIMIT 1;

INSERT INTO revenue_sources (name) VALUES
('Passenger Fares'),
('Cargo Revenue'),
('Charter Services'),
('Other Income');

CREATE INDEX idx_vehicles_status ON vehicles(status);
CREATE INDEX idx_journal_entries_date ON journal_entries(entry_date);
CREATE INDEX idx_revenue_records_date ON revenue_records(revenue_date);
CREATE INDEX idx_expense_records_date ON expense_records(expense_date);

-- Create views for reporting
CREATE OR REPLACE VIEW v_vehicle_summary AS
SELECT
    v.id,
    v.registration_number,
    v.vehicle_type,
    v.status,
    COUNT(DISTINCT w.id) AS total_trips,
    COUNT(DISTINCT m.id) AS maintenance_count,
    SUM(w.total_revenue) AS total_revenue,
    SUM(w.expenses) AS total_expenses,
    MAX(w.departure_date) AS last_trip_date
FROM vehicles v
LEFT JOIN waybills w ON v.id = w.vehicle_id
LEFT JOIN maintenance_records m ON v.id = m.vehicle_id
GROUP BY v.id;

-- Create backup of current structure
CREATE TABLE IF NOT EXISTS schema_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO schema_versions (version) VALUES ('1.0.0');
