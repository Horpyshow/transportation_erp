<?php
/**
 * ChartOfAccounts Class
 * Manages chart of accounts and account operations
 * Handles account creation, retrieval, and account balance calculations
 */

class ChartOfAccounts {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create a new account
     *
     * @param array $data Account data
     * @return bool|int Returns account ID on success, false on failure
     */
    public function createAccount($data) {
        $this->db->query('
            INSERT INTO chart_of_accounts
            (account_number, account_name, account_class_id, description, account_type, normal_balance, opening_balance)
            VALUES
            (:account_number, :account_name, :account_class_id, :description, :account_type, :normal_balance, :opening_balance)
        ');

        $this->db->bind(':account_number', $data['account_number']);
        $this->db->bind(':account_name', $data['account_name']);
        $this->db->bind(':account_class_id', $data['account_class_id']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':account_type', $data['account_type']);
        $this->db->bind(':normal_balance', $data['normal_balance']);
        $this->db->bind(':opening_balance', $data['opening_balance'] ?? 0);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get all accounts with filters
     *
     * @param array $filters Optional filters
     * @return array
     */
    public function getAllAccounts($filters = []) {
        $query = '
            SELECT
                coa.id,
                coa.account_number,
                coa.account_name,
                coa.account_type,
                coa.normal_balance,
                coa.is_active,
                ac.name AS account_class,
                coa.opening_balance,
                COALESCE(SUM(CASE WHEN gl.debit > 0 THEN gl.debit ELSE 0 END), 0) AS total_debit,
                COALESCE(SUM(CASE WHEN gl.credit > 0 THEN gl.credit ELSE 0 END), 0) AS total_credit
            FROM chart_of_accounts coa
            LEFT JOIN account_classes ac ON coa.account_class_id = ac.id
            LEFT JOIN general_ledger gl ON coa.id = gl.account_id
            WHERE 1=1
        ';

        if (!empty($filters['account_type'])) {
            $query .= ' AND coa.account_type = :account_type';
        }

        if (!empty($filters['is_active'])) {
            $query .= ' AND coa.is_active = :is_active';
        }

        $query .= ' GROUP BY coa.id ORDER BY coa.account_number ASC';

        $this->db->query($query);

        if (!empty($filters['account_type'])) {
            $this->db->bind(':account_type', $filters['account_type']);
        }

        if (!empty($filters['is_active'])) {
            $this->db->bind(':is_active', $filters['is_active']);
        }

        return $this->db->resultSet();
    }

    /**
     * Get account by ID
     *
     * @param int $id Account ID
     * @return array|false
     */
    public function getAccountById($id) {
        $this->db->query('
            SELECT
                coa.*,
                ac.name AS account_class
            FROM chart_of_accounts coa
            LEFT JOIN account_classes ac ON coa.account_class_id = ac.id
            WHERE coa.id = :id
        ');

        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Get account by account number
     *
     * @param string $accountNumber Account number
     * @return array|false
     */
    public function getAccountByNumber($accountNumber) {
        $this->db->query('
            SELECT
                coa.*,
                ac.name AS account_class
            FROM chart_of_accounts coa
            LEFT JOIN account_classes ac ON coa.account_class_id = ac.id
            WHERE coa.account_number = :account_number
        ');

        $this->db->bind(':account_number', $accountNumber);
        return $this->db->single();
    }

    /**
     * Get account balance
     *
     * @param int $accountId Account ID
     * @param string|null $asOfDate Date to calculate balance as of
     * @return array Balance information
     */
    public function getAccountBalance($accountId, $asOfDate = null) {
        $query = '
            SELECT
                coa.id,
                coa.account_number,
                coa.account_name,
                coa.account_type,
                coa.normal_balance,
                coa.opening_balance,
                COALESCE(SUM(CASE WHEN gl.debit > 0 THEN gl.debit ELSE 0 END), 0) AS total_debit,
                COALESCE(SUM(CASE WHEN gl.credit > 0 THEN gl.credit ELSE 0 END), 0) AS total_credit
            FROM chart_of_accounts coa
            LEFT JOIN general_ledger gl ON coa.id = gl.account_id
            WHERE coa.id = :account_id
        ';

        if ($asOfDate) {
            $query .= ' AND gl.transaction_date <= :as_of_date';
        }

        $query .= ' GROUP BY coa.id';

        $this->db->query($query);
        $this->db->bind(':account_id', $accountId);

        if ($asOfDate) {
            $this->db->bind(':as_of_date', $asOfDate);
        }

        $result = $this->db->single();

        if ($result) {
            $result['balance'] = $result['opening_balance'] +
                ($result['normal_balance'] === 'debit' ?
                    ($result['total_debit'] - $result['total_credit']) :
                    ($result['total_credit'] - $result['total_debit']));
        }

        return $result;
    }

    /**
     * Get all account balances as of a specific date
     *
     * @param string|null $asOfDate Date for balance calculation
     * @return array
     */
    public function getAllAccountBalances($asOfDate = null) {
        $query = '
            SELECT
                coa.id,
                coa.account_number,
                coa.account_name,
                coa.account_type,
                coa.normal_balance,
                coa.opening_balance,
                COALESCE(SUM(CASE WHEN gl.debit > 0 THEN gl.debit ELSE 0 END), 0) AS total_debit,
                COALESCE(SUM(CASE WHEN gl.credit > 0 THEN gl.credit ELSE 0 END), 0) AS total_credit
            FROM chart_of_accounts coa
            LEFT JOIN general_ledger gl ON coa.id = gl.account_id
            WHERE coa.is_active = 1
        ';

        if ($asOfDate) {
            $query .= ' AND gl.transaction_date <= :as_of_date';
        }

        $query .= ' GROUP BY coa.id ORDER BY coa.account_number ASC';

        $this->db->query($query);

        if ($asOfDate) {
            $this->db->bind(':as_of_date', $asOfDate);
        }

        $results = $this->db->resultSet();

        foreach ($results as &$result) {
            $result['balance'] = $result['opening_balance'] +
                ($result['normal_balance'] === 'debit' ?
                    ($result['total_debit'] - $result['total_credit']) :
                    ($result['total_credit'] - $result['total_debit']));
        }

        return $results;
    }

    /**
     * Update account
     *
     * @param int $id Account ID
     * @param array $data Updated data
     * @return bool
     */
    public function updateAccount($id, $data) {
        $this->db->query('
            UPDATE chart_of_accounts
            SET
                account_name = :account_name,
                description = :description,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ');

        $this->db->bind(':id', $id);
        $this->db->bind(':account_name', $data['account_name']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':is_active', $data['is_active'] ?? 1);

        return $this->db->execute();
    }

    /**
     * Deactivate account
     *
     * @param int $id Account ID
     * @return bool
     */
    public function deactivateAccount($id) {
        $this->db->query('
            UPDATE chart_of_accounts
            SET is_active = 0, updated_at = NOW()
            WHERE id = :id
        ');

        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Get accounts by type
     *
     * @param string $type Account type (asset, liability, equity, revenue, expense)
     * @return array
     */
    public function getAccountsByType($type) {
        $this->db->query('
            SELECT *
            FROM chart_of_accounts
            WHERE account_type = :account_type AND is_active = 1
            ORDER BY account_number ASC
        ');

        $this->db->bind(':account_type', $type);
        return $this->db->resultSet();
    }

    /**
     * Validate account number uniqueness
     *
     * @param string $accountNumber Account number
     * @param int|null $excludeId Account ID to exclude from check
     * @return bool
     */
    public function isAccountNumberUnique($accountNumber, $excludeId = null) {
        $query = 'SELECT COUNT(*) as count FROM chart_of_accounts WHERE account_number = :account_number';

        if ($excludeId) {
            $query .= ' AND id != :id';
        }

        $this->db->query($query);
        $this->db->bind(':account_number', $accountNumber);

        if ($excludeId) {
            $this->db->bind(':id', $excludeId);
        }

        $result = $this->db->single();
        return $result['count'] == 0;
    }

    /**
     * Initialize default chart of accounts for transportation business
     *
     * @param int $accountClassId Account class ID
     * @return bool
     */
    public function initializeDefaultAccounts($accountClassId) {
        $defaultAccounts = [
            ['1100', 'Cash in Hand', 'asset', 'debit', 0],
            ['1110', 'Bank Accounts', 'asset', 'debit', 0],
            ['1200', 'Vehicles', 'asset', 'debit', 0],
            ['1250', 'Accumulated Depreciation', 'asset', 'credit', 0],
            ['1300', 'Fuel Inventory', 'asset', 'debit', 0],
            ['2100', 'Accounts Payable', 'liability', 'credit', 0],
            ['2200', 'Loans Payable', 'liability', 'credit', 0],
            ['3100', 'Owner Equity', 'equity', 'credit', 0],
            ['4100', 'Passenger Revenue', 'revenue', 'credit', 0],
            ['4200', 'Cargo Revenue', 'revenue', 'credit', 0],
            ['4300', 'Charter Revenue', 'revenue', 'credit', 0],
            ['5100', 'Fuel & Oil Expenses', 'expense', 'debit', 0],
            ['5200', 'Maintenance Expenses', 'expense', 'debit', 0],
            ['5300', 'Salaries & Wages', 'expense', 'debit', 0],
            ['5400', 'Insurance Expenses', 'expense', 'debit', 0],
            ['5500', 'Administrative Expenses', 'expense', 'debit', 0],
        ];

        try {
            $this->db->beginTransaction();

            foreach ($defaultAccounts as $account) {
                $this->db->query('
                    INSERT INTO chart_of_accounts
                    (account_number, account_name, account_class_id, account_type, normal_balance, opening_balance)
                    VALUES
                    (:account_number, :account_name, :account_class_id, :account_type, :normal_balance, :opening_balance)
                ');

                $this->db->bind(':account_number', $account[0]);
                $this->db->bind(':account_name', $account[1]);
                $this->db->bind(':account_class_id', $accountClassId);
                $this->db->bind(':account_type', $account[2]);
                $this->db->bind(':normal_balance', $account[3]);
                $this->db->bind(':opening_balance', $account[4]);

                if (!$this->db->execute()) {
                    $this->db->cancelTransaction();
                    return false;
                }
            }

            $this->db->endTransaction();
            return true;
        } catch (Exception $e) {
            $this->db->cancelTransaction();
            return false;
        }
    }
}
?>
