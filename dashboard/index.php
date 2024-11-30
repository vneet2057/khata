<?php
// Start the session at the top of the page
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: /khata-app/auth/login.php");
    exit();
}

// Include the base layout file
ob_start(); // Start output buffering

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=khata-app', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Calculate total customers
$customerQuery = "SELECT COUNT(*) AS total_customers FROM customers WHERE user_id = :user_id";
$stmt = $pdo->prepare($customerQuery);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];

// Calculate total receivables (sum of credit - sum of debit for the customer's journal_entries)
$receivablesQuery = "
    SELECT 
        SUM(CASE WHEN transaction_type = 'Credit' THEN amount ELSE 0 END) - 
        SUM(CASE WHEN transaction_type = 'Debit' THEN amount ELSE 0 END) AS total_receivables
    FROM journal_entries je
    JOIN customers c ON je.customer_id = c.id
    WHERE c.user_id = :user_id
";
$stmt = $pdo->prepare($receivablesQuery);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$totalReceivables = $stmt->fetch(PDO::FETCH_ASSOC)['total_receivables'] ?? 0;

// Calculate total payables (sum of credit - sum of debit for the vendor_journal_entries)
$payablesQuery = "
    SELECT 
        SUM(CASE WHEN transaction_type = 'Credit' THEN amount ELSE 0 END) - 
        SUM(CASE WHEN transaction_type = 'Debit' THEN amount ELSE 0 END) AS total_payables
    FROM vendor_journal_entries vje
    JOIN vendors v ON vje.vendor_id = v.id
    WHERE v.user_id = :user_id
";
$stmt = $pdo->prepare($payablesQuery);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$totalPayables = $stmt->fetch(PDO::FETCH_ASSOC)['total_payables'] ?? 0;

// Calculate total P/L (total payables - total receivables)
$totalPL = $totalPayables - $totalReceivables;

?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="p-4 bg-white rounded shadow">
        <h2 class="text-sm font-medium text-gray-500">Total Customers</h2>
        <p class="text-2xl font-bold text-gray-800"><?php echo $totalCustomers; ?></p>
    </div>
    <div class="p-4 bg-white rounded shadow">
        <h2 class="text-sm font-medium text-gray-500">Total Receivables</h2>
        <p class="text-2xl font-bold text-gray-800">NPR <?php echo number_format($totalReceivables, 2); ?></p>
    </div>
    <div class="p-4 bg-white rounded shadow">
        <h2 class="text-sm font-medium text-gray-500">Total Payable</h2>
        <p class="text-2xl font-bold text-gray-800">NPR <?php echo number_format($totalPayables, 2); ?></p>
    </div>
    <div class="p-4 bg-white rounded shadow">
        <h2 class="text-sm font-medium text-gray-500">Total P/L</h2>
        <p class="text-2xl font-bold text-gray-800">NPR <?php echo number_format($totalPL, 2); ?></p>
    </div>
</div>
<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('layout/base.php');
?>
