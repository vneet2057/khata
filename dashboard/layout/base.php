<?php


// Handle logout
if (isset($_GET['logout'])) {
    // Destroy session and redirect to login page
    session_unset();
    session_destroy();
    header("Location: /khata-app/auth/login.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</head>
<body class="bg-gray-100 h-screen flex">
    <!-- Sidebar -->
    <div class="w-64 bg-gray-900 text-white flex flex-col">
        <div class="px-6 py-4 text-2xl font-bold border-b border-white">
            Credit Tracker
        </div>
        <nav class="flex-1 px-6 py-4 space-y-4">
            <?php 
            $current_page = basename($_SERVER['PHP_SELF']); // Get current page file name
            ?>
            <a href="/khata-app/dashboard/index.php" class="block px-4 py-2 rounded hover:bg-emerald-500 <?php if ($current_page == 'index.php') echo 'bg-emerald-500'; ?>">
                <i class="fas fa-tachometer-alt mr-3"></i> Home
            </a>
            <a href="/khata-app/dashboard/customer/customers.php" class="block px-4 py-2 rounded hover:bg-emerald-500 <?php if ($current_page == 'customers.php' || $current_page == 'add-customer.php') echo 'bg-emerald-500'; ?>">
                <i class="fas fa-users mr-3"></i> Customer
            </a>
            <a href="/khata-app/dashboard/journals/journals.php" class="block px-4 py-2 rounded hover:bg-emerald-500 <?php if ($current_page == 'journals.php') echo 'bg-emerald-500'; ?>">
                <i class="fas fa-credit-card mr-3"></i> Customer Journals
            </a>
            <a href="/khata-app/dashboard/vendor/vendors.php" class="block px-4 py-2 rounded hover:bg-emerald-500 <?php if ($current_page == 'vendors.php' || $current_page == 'add-customer.php') echo 'bg-emerald-500'; ?>">
                <i class="fas fa-users mr-3"></i> Vendors
            </a>
            <a href="/khata-app/dashboard/vendor-journals/vendor-journals.php" class="block px-4 py-2 rounded hover:bg-emerald-500 <?php if ($current_page == 'vendor-journals.php') echo 'bg-emerald-500'; ?>">
                <i class="fas fa-credit-card mr-3"></i> Vendors Journals
            </a>
            <a href="/khata-app/dashboard/reports/report-index.php" class="block px-4 py-2 rounded hover:bg-emerald-500 <?php if ($current_page == 'reports.php') echo 'bg-emerald-500'; ?>">
                <i class="fas fa-chart-line mr-3"></i> Reports
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-md py-4 px-6 flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-700">
                Welcome to Khata App
            </h1>

            <!-- Profile Section -->
            <div class="relative">
                <button class="flex items-center space-x-2 bg-gray-200 text-gray-700 px-4 py-2 rounded-full hover:bg-gray-300">
                    <i class="fas fa-user-circle"></i>
                    <span>P</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <!-- Dropdown Menu -->
                <div class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-lg hidden group-hover:block">
                    <a href="change-password.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Change Password</a>
                    <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                    <a href="?logout=true" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a> <!-- Logout link -->
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-6">
            <?php echo $content; ?>
        </main>
    </div>

    <script>
        // Toggle dropdown visibility on profile button hover
        document.querySelector('button').addEventListener('click', function () {
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('hidden');
        });
    </script>
</body>
</html>
