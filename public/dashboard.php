<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/classes/Database.php';
require_once __DIR__ . '/../app/classes/ChartOfAccounts.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=session_expired');
    exit;
}

if ((time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: index.php?error=session_expired');
    exit;
}

$_SESSION['login_time'] = time();

try {
    $db = new Database();
    $coa = new ChartOfAccounts($db);

    $db->query('
        SELECT
            COUNT(*) as total_vehicles,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_vehicles,
            SUM(CASE WHEN status = "maintenance" THEN 1 ELSE 0 END) as maintenance_vehicles
        FROM vehicles
    ');
    $vehicleStats = $db->single();

    $db->query('
        SELECT COALESCE(SUM(amount), 0) as total_revenue
        FROM revenue_records
        WHERE revenue_date >= DATE_FORMAT(NOW(), "%Y-%m-01")
    ');
    $revenueStats = $db->single();

    $db->query('
        SELECT COALESCE(SUM(amount), 0) as total_expenses
        FROM expense_records
        WHERE expense_date >= DATE_FORMAT(NOW(), "%Y-%m-01")
        AND status = "recorded"
    ');
    $expenseStats = $db->single();

    $accountBalances = $coa->getAllAccountBalances();

} catch (Exception $e) {
    error_log('Dashboard Error: ' . $e->getMessage());
    $vehicleStats = ['total_vehicles' => 0, 'active_vehicles' => 0];
    $revenueStats = ['total_revenue' => 0];
    $expenseStats = ['total_expenses' => 0];
    $accountBalances = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Transportation ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar.collapsed {
            margin-left: -256px;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .menu-item {
            transition: all 0.3s ease;
        }
        .menu-item:hover {
            padding-left: 1.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-gray-900 text-white flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-600 rounded-lg p-3">
                        <i class="fas fa-truck text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">TransERP</h2>
                        <p class="text-gray-400 text-xs">v1.0.0</p>
                    </div>
                </div>
            </div>

            <!-- Menu -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                <div class="mb-6">
                    <p class="text-gray-500 text-xs font-semibold px-4 py-2 uppercase tracking-wider">Main</p>
                    <a href="dashboard.php" class="menu-item flex items-center px-4 py-3 rounded-lg bg-blue-600 text-white">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="mb-6">
                    <p class="text-gray-500 text-xs font-semibold px-4 py-2 uppercase tracking-wider">Operations</p>
                    <a href="vehicles.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-bus w-5"></i>
                        <span>Fleet Management</span>
                    </a>
                    <a href="dispatch.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-map-marker-alt w-5"></i>
                        <span>Dispatch</span>
                    </a>
                    <a href="waybills.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-file-alt w-5"></i>
                        <span>Waybills</span>
                    </a>
                </div>

                <div class="mb-6">
                    <p class="text-gray-500 text-xs font-semibold px-4 py-2 uppercase tracking-wider">Finance</p>
                    <a href="chart-of-accounts.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-list-ul w-5"></i>
                        <span>Chart of Accounts</span>
                    </a>
                    <a href="journal-entries.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-book w-5"></i>
                        <span>Journal Entries</span>
                    </a>
                    <a href="general-ledger.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-receipt w-5"></i>
                        <span>General Ledger</span>
                    </a>
                    <a href="revenue.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-coins w-5"></i>
                        <span>Revenue</span>
                    </a>
                    <a href="expenses.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-credit-card w-5"></i>
                        <span>Expenses</span>
                    </a>
                </div>

                <div class="mb-6">
                    <p class="text-gray-500 text-xs font-semibold px-4 py-2 uppercase tracking-wider">Reports</p>
                    <a href="trial-balance.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-balance-scale w-5"></i>
                        <span>Trial Balance</span>
                    </a>
                    <a href="financial-reports.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-file-pdf w-5"></i>
                        <span>Financial Reports</span>
                    </a>
                    <a href="performance.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Performance</span>
                    </a>
                </div>

                <div class="mb-6">
                    <p class="text-gray-500 text-xs font-semibold px-4 py-2 uppercase tracking-wider">Settings</p>
                    <a href="users.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-users w-5"></i>
                        <span>Users</span>
                    </a>
                    <a href="settings.php" class="menu-item flex items-center px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800">
                        <i class="fas fa-cog w-5"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <!-- User Profile -->
            <div class="p-4 border-t border-gray-700">
                <div class="flex items-center justify-between p-3 rounded-lg bg-gray-800">
                    <div>
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                    </div>
                    <a href="logout.php" class="text-gray-400 hover:text-red-500" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <div class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <div class="flex items-center space-x-6">
                    <button class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bell text-lg"></i>
                    </button>
                    <button class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-envelope text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Welcome Card -->
                <div class="mb-8 p-6 bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl text-white">
                    <h2 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
                    <p class="text-blue-100">Here's your business overview for today</p>
                </div>

                <!-- Statistics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Vehicles -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-blue-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">Total Vehicles</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $vehicleStats['total_vehicles']; ?></p>
                                <p class="text-xs text-green-600 mt-2">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <?php echo $vehicleStats['active_vehicles']; ?> Active
                                </p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-4">
                                <i class="fas fa-bus text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- This Month Revenue -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-green-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">This Month Revenue</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">₦<?php echo number_format($revenueStats['total_revenue'], 2); ?></p>
                                <p class="text-xs text-green-600 mt-2">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +12% from last month
                                </p>
                            </div>
                            <div class="bg-green-100 rounded-full p-4">
                                <i class="fas fa-coins text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- This Month Expenses -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-red-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">This Month Expenses</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">₦<?php echo number_format($expenseStats['total_expenses'], 2); ?></p>
                                <p class="text-xs text-red-600 mt-2">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +5% from last month
                                </p>
                            </div>
                            <div class="bg-red-100 rounded-full p-4">
                                <i class="fas fa-credit-card text-red-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Net Profit -->
                    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-purple-600">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600 text-sm font-semibold">Net Profit</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">₦<?php echo number_format($revenueStats['total_revenue'] - $expenseStats['total_expenses'], 2); ?></p>
                                <p class="text-xs text-purple-600 mt-2">
                                    <i class="fas fa-percentage mr-1"></i>
                                    <?php echo ($revenueStats['total_revenue'] > 0) ? round((($revenueStats['total_revenue'] - $expenseStats['total_expenses']) / $revenueStats['total_revenue']) * 100, 1) : 0; ?>% Margin
                                </p>
                            </div>
                            <div class="bg-purple-100 rounded-full p-4">
                                <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Quick Links -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-bolt text-blue-600 mr-2"></i>Quick Actions
                        </h3>
                        <div class="space-y-3">
                            <a href="revenue.php?action=new" class="flex items-center p-3 rounded-lg bg-blue-50 hover:bg-blue-100 transition">
                                <i class="fas fa-plus-circle text-blue-600 w-5"></i>
                                <span class="ml-3 text-gray-700 font-medium">Record Revenue</span>
                            </a>
                            <a href="expenses.php?action=new" class="flex items-center p-3 rounded-lg bg-red-50 hover:bg-red-100 transition">
                                <i class="fas fa-plus-circle text-red-600 w-5"></i>
                                <span class="ml-3 text-gray-700 font-medium">Record Expense</span>
                            </a>
                            <a href="journal-entries.php?action=new" class="flex items-center p-3 rounded-lg bg-purple-50 hover:bg-purple-100 transition">
                                <i class="fas fa-plus-circle text-purple-600 w-5"></i>
                                <span class="ml-3 text-gray-700 font-medium">New Journal Entry</span>
                            </a>
                            <a href="waybills.php?action=new" class="flex items-center p-3 rounded-lg bg-green-50 hover:bg-green-100 transition">
                                <i class="fas fa-plus-circle text-green-600 w-5"></i>
                                <span class="ml-3 text-gray-700 font-medium">Create Waybill</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-history text-blue-600 mr-2"></i>System Info
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">System Version</span>
                                <span class="font-semibold text-gray-900">1.0.0</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Current User</span>
                                <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">User Role</span>
                                <span class="font-semibold text-gray-900 capitalize"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Session Status</span>
                                <span class="font-semibold text-green-600"><i class="fas fa-circle text-green-500 mr-1"></i>Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Dashboard loaded successfully');
    </script>
</body>
</html>
