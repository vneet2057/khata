<?php
// Start the session to retrieve user ID
session_start();

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: /khata-app/auth/login.php");
    exit();
}

// Start output buffering
ob_start();

// Your specific page content for the reports section
?>

<div class="container mx-auto px-6 py-4">
    <div class="flex mb-6">
        <!-- Sidebar for Report Menus -->
        <div class="w-1/4 bg-white rounded-lg shadow-md p-6 mr-6">
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">Report Menu</h2>
            <ul class="space-y-4">
                <li>
                    <a href="reports.php?report=tax" class="block text-blue-600 hover:text-blue-800 text-lg">Tax Report</a>
                </li>
                <li>
                    <a href="reports.php?report=customer" class="block text-blue-600 hover:text-blue-800 text-lg">Customer Report</a>
                </li>
                <li>
                    <a href="reports.php?report=vendor" class="block text-blue-600 hover:text-blue-800 text-lg">Vendor Report</a>
                </li>
                <li>
                    <a href="reports.php?report=pl" class="block text-blue-600 hover:text-blue-800 text-lg">Profit/Loss Report</a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area for the selected report -->
        <div class="flex-1 p-6 bg-white rounded-lg shadow-md">
            <h1 class="text-3xl font-semibold text-gray-800 mb-6">Reports</h1>

            <?php
            // Check which report to display based on query parameter
            if (isset($_GET['report'])) {
                $reportType = $_GET['report'];

                switch ($reportType) {
                    case 'tax':
                        include 'tax_report.php'; // Include the tax report
                        break;
                    case 'customer':
                        include 'customer_report.php'; // Include the customer report
                        break;
                    case 'vendor':
                        include 'vendor_report.php'; // Include the vendor report
                        break;
                    case 'pl':
                        include 'pl_report.php'; // Include the profit/loss report
                        break;
                    default:
                        echo "<p class='text-gray-500'>Select a report from the sidebar.</p>";
                }
            } else {
                echo "<p class='text-gray-500'>Select a report from the sidebar.</p>";
            }
            ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
