<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportation ERP - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .btn-submit {
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .logo-icon {
            animation: slideDown 0.6s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .form-container {
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md px-4">
        <div class="login-container rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-8 py-12 text-center text-white">
                <div class="logo-icon mb-4 flex justify-center">
                    <div class="bg-white rounded-full p-4">
                        <i class="fas fa-truck text-blue-600 text-3xl"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold mb-2">Transportation ERP</h1>
                <p class="text-blue-100 text-sm">Fleet & Financial Management System</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8 form-container">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700 text-sm">
                                <?php
                                switch($_GET['error']) {
                                    case 'invalid_credentials':
                                        echo 'Invalid username or password';
                                        break;
                                    case 'account_disabled':
                                        echo 'Your account has been disabled';
                                        break;
                                    case 'session_expired':
                                        echo 'Your session has expired. Please login again';
                                        break;
                                    default:
                                        echo 'An error occurred. Please try again';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700 text-sm">Logged out successfully</p>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-5">
                    <!-- Username Field -->
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-blue-600"></i>Username
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            class="input-field w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                            placeholder="Enter your username"
                            autocomplete="username"
                        >
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-blue-600"></i>Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                class="input-field w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                class="absolute right-3 top-3 text-gray-600 hover:text-gray-800"
                                onclick="togglePassword()"
                            >
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center text-gray-700 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-200 cursor-pointer">
                            <span class="ml-2">Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="text-blue-600 hover:text-blue-800 font-medium">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-submit w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center justify-center"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </form>

                <!-- Demo Credentials -->
                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-xs font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>Demo Credentials
                    </p>
                    <div class="text-xs text-gray-600 space-y-1">
                        <p><span class="font-semibold">Username:</span> admin</p>
                        <p><span class="font-semibold">Password:</span> admin123</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
                <p class="text-xs text-gray-600">
                    <i class="fas fa-copyright mr-1"></i>2024 Transportation ERP System. All rights reserved.
                </p>
            </div>
        </div>

        <!-- System Status -->
        <div class="mt-6 text-center text-white text-sm">
            <p class="flex items-center justify-center">
                <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                System Online
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }

            if (password.length < 3) {
                e.preventDefault();
                alert('Invalid password');
                return false;
            }
        });
    </script>
</body>
</html>
