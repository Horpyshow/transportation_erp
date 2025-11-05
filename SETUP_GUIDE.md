# Transportation ERP System - Setup Guide

## System Overview
A comprehensive PHP/MySQL ERP system for transportation businesses managing fleet, revenue, expenses, and financial reporting.

## Prerequisites
- XAMPP (PHP 7.4+, MySQL 5.7+)
- phpMyAdmin
- Code Editor (VS Code recommended)
- Browser (Chrome/Firefox)

## Installation Steps

### 1. XAMPP Setup
```bash
# Start Apache and MySQL from XAMPP Control Panel
# Verify access: http://localhost/phpmyadmin
```

### 2. Project Structure
```
transportation_erp/
├── config.php
├── database/
│   └── schema.sql
├── app/
│   └── classes/
│       ├── Database.php
│       ├── ChartOfAccounts.php
│       ├── GeneralLedger.php
│       ├── Revenue.php
│       ├── Expense.php
│       └── Fleet.php
├── public/
│   ├── index.php
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js
│   └── assets/
├── SETUP_GUIDE.md
└── .htaccess
```

### 3. Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Go to Import tab
3. Select `database/schema.sql`
4. Click Import
5. Verify tables created successfully

### 4. Configure Database
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Leave empty if no password
define('DB_NAME', 'transportation_erp');
```

### 5. Create Required Directories
```bash
mkdir -p public/css
mkdir -p public/js
mkdir -p public/assets
mkdir -p logs
```

### 6. Set File Permissions
```bash
chmod 755 logs
chmod 644 config.php
```

### 7. Access Application
Navigate to: `http://localhost/transportation_erp/public/`

## Database Schema Summary

### Core Tables
- **users** - User accounts and roles (admin, manager, accountant, driver, dispatcher)
- **chart_of_accounts** - General ledger accounts with balances
- **general_ledger** - Daily transaction records
- **journal_entries** - Grouped transactions with posting status

### Fleet Management
- **vehicles** - Bus/truck inventory (200+ vehicles)
- **maintenance_records** - Repair and service logs
- **fuel_logs** - Fuel consumption and costs

### Revenue Management
- **waybills** - Trip documentation
- **cargo** - Cargo tracking per trip
- **revenue_records** - Income entries by source
- **revenue_sources** - Types of income (passengers, cargo, charter)

### Expense Management
- **expense_categories** - Expense types (fuel, maintenance, salaries, insurance)
- **expense_records** - Individual expense transactions

### Operations
- **dispatch_assignments** - Driver and vehicle assignments
- **trial_balance** - Periodic trial balance snapshots
- **financial_reports** - Generated reports (P&L, Balance Sheet, Cash Flow)
- **audit_logs** - Transaction history and changes

## Default Account Structure

### Assets (1000-1300)
- 1100: Cash in Hand
- 1110: Bank Accounts
- 1200: Vehicles
- 1250: Accumulated Depreciation
- 1300: Fuel Inventory

### Liabilities (2000-2200)
- 2100: Accounts Payable
- 2200: Loans Payable

### Equity (3000-3100)
- 3100: Owner Equity

### Revenue (4000-4300)
- 4100: Passenger Revenue
- 4200: Cargo Revenue
- 4300: Charter Revenue

### Expenses (5000-5500)
- 5100: Fuel & Oil Expenses
- 5200: Maintenance Expenses
- 5300: Salaries & Wages
- 5400: Insurance Expenses
- 5500: Administrative Expenses

## Core Classes

### Database Class
Handles PDO connections, prepared statements, and transactions.

**Methods:**
- `query($query)` - Prepare SQL statement
- `bind($param, $value, $type)` - Bind parameters
- `execute()` - Execute prepared statement
- `resultSet()` - Fetch all results
- `single()` - Fetch single result
- `beginTransaction()`, `endTransaction()`, `cancelTransaction()`

### ChartOfAccounts Class
Manages chart of accounts operations.

**Methods:**
- `createAccount($data)` - Create new account
- `getAllAccounts($filters)` - Get filtered accounts
- `getAccountById($id)` - Get account details
- `getAccountBalance($accountId, $asOfDate)` - Calculate balance
- `getAllAccountBalances($asOfDate)` - Get trial balance
- `updateAccount($id, $data)` - Update account
- `deactivateAccount($id)` - Deactivate account
- `initializeDefaultAccounts($classId)` - Setup default chart

## Features to Implement

### Phase 1 (Current)
- ✓ Database Schema
- ✓ Database Connection Class
- ✓ Chart of Accounts Management
- Chart of Accounts Dashboard
- User Authentication

### Phase 2
- General Ledger Entry Processing
- Revenue Recording & Waybill Management
- Expense Recording & Approval Workflow
- Fleet Management Dashboard

### Phase 3
- Trial Balance Generation
- P&L Statement
- Balance Sheet
- Cash Flow Statement
- Monthly Performance Reports

### Phase 4
- Dispatch Management
- Cargo Tracking
- Driver Performance Analytics
- Vehicle Maintenance Scheduling

### Phase 5
- User Roles & Permissions
- Audit Trail
- Data Export (PDF, Excel)
- System Backup

## Security Best Practices

1. **SQL Injection Prevention**
   - Always use prepared statements with parameterized queries
   - Never concatenate user input into SQL

2. **Password Security**
   - Use `password_hash()` with bcrypt
   - Hash passwords: `hash_password($plaintext)`
   - Verify: `password_verify($plaintext, $hash)`

3. **Session Management**
   - Session timeout: 1 hour (configurable in config.php)
   - Use `SESSION_TIMEOUT` constant
   - Regenerate session ID after login

4. **Input Validation**
   - Validate all user inputs
   - Sanitize output with `htmlspecialchars()`
   - Use type casting for numeric values

5. **Database Access Control**
   - Use separate DB user with limited privileges
   - Restrict file uploads to safe directories
   - Implement Row Level Security via user roles

6. **Error Handling**
   - Log errors to file (never display in production)
   - Use PDO exceptions
   - Return generic error messages to users

## Common Tasks

### Add a New Revenue Entry
```php
$revenue = new Revenue($db);
$revenue->recordRevenue([
    'revenue_date' => date('Y-m-d'),
    'revenue_source_id' => 1,
    'vehicle_id' => 1,
    'amount' => 50000,
    'description' => 'Trip revenue',
    'account_id' => 4100
]);
```

### Record Maintenance Expense
```php
$expense = new Expense($db);
$expense->recordExpense([
    'expense_date' => date('Y-m-d'),
    'category_id' => 2,
    'vehicle_id' => 5,
    'amount' => 15000,
    'description' => 'Engine repair',
    'vendor_name' => 'Auto Repair Shop'
]);
```

### Generate Trial Balance
```php
$gl = new GeneralLedger($db);
$trialBalance = $gl->generateTrialBalance(date('Y-m-d'));
```

### Get Account Balance
```php
$coa = new ChartOfAccounts($db);
$balance = $coa->getAccountBalance(4100); // Passenger Revenue
echo $balance['balance'];
```

## Troubleshooting

### Database Connection Error
- Verify XAMPP MySQL is running
- Check `config.php` credentials
- Ensure `transportation_erp` database exists

### PDO Exception
- Check database user privileges
- Verify table names and columns exist
- Enable error logging to view full error

### Session Issues
- Clear browser cookies
- Check `session_start()` is called in config.php
- Verify session timeout settings

## Support Resources

- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/
- PDO Tutorial: https://www.php.net/manual/en/pdo.connections.php
- XAMPP Documentation: https://www.apachefriends.org/

## Version History

**v1.0.0** (2024)
- Initial database schema
- Core classes (Database, ChartOfAccounts)
- User management foundation
- Fleet management tables
- Revenue & expense tracking
- General ledger implementation

## Next Steps

1. Create authentication system
2. Build user interface with Tailwind CSS
3. Implement journal entry processing
4. Create financial reporting module
5. Deploy to production environment

---

**Important:** Always backup your database before major updates. Use phpMyAdmin Export feature regularly.

For detailed class documentation, refer to inline comments in respective class files.
