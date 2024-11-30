<?php
// Include the base layout file
ob_start(); // Start output buffering

// Start session to access user_id from session
session_start();

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=khata-app', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if user is logged in by checking the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if user is not logged in
    header("Location: /login.php");
    exit();
}

// Get the user_id from the session
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $customer_id = $_POST['customer_id'];  // The customer is now selected by ID (FK)
    $transaction_type = $_POST['transaction_type'];
    $amount = $_POST['amount'];
    $transaction_date = $_POST['transaction_date'];

    // Validate input (ensure all fields are filled)
    if (empty($customer_id) || empty($transaction_type) || empty($amount) || empty($transaction_date)) {
        $error_message = "All fields are required!";
    } else {
        // Insert new journal entry into the database
        $query = "INSERT INTO journal_entries (customer_id, transaction_type, amount, transaction_date) 
                  VALUES (:customer_id, :transaction_type, :amount, :transaction_date)";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':customer_id', $customer_id);
        $statement->bindParam(':transaction_type', $transaction_type);
        $statement->bindParam(':amount', $amount);
        $statement->bindParam(':transaction_date', $transaction_date);

        // Execute the query
        if ($statement->execute()) {
            $success_message = "Journal entry added successfully!";
        } else {
            $error_message = "An error occurred while adding the journal entry.";
        }
    }
}

// Fetch customers for the select dropdown, but only those related to the logged-in user
$query = "SELECT id, name FROM customers WHERE user_id = :user_id";
$statement = $pdo->prepare($query);
$statement->bindParam(':user_id', $user_id);
$statement->execute();
$customers = $statement->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Add Journal Entry</h2>
        <a href="journal-list.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Back to Journal List</a>
    </div>

    <!-- Error or Success Message -->
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php elseif (isset($success_message)): ?>
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add Journal Form -->
    <form action="add-journal.php" method="POST" class="space-y-4">
        <div>
            <label for="customer_id" class="block text-sm font-medium text-gray-600">Customer</label>
            <select
                id="customer_id"
                name="customer_id"
                class="w-full p-3 border border-gray-300 rounded"
            >
                <option value="">Select Customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="transaction_type" class="block text-sm font-medium text-gray-600">Transaction Type</label>
            <select
                id="transaction_type"
                name="transaction_type"
                class="w-full p-3 border border-gray-300 rounded"
            >
                <option value="credit" <?php echo isset($transaction_type) && $transaction_type == 'credit' ? 'selected' : ''; ?>>Credit</option>
                <option value="debit" <?php echo isset($transaction_type) && $transaction_type == 'debit' ? 'selected' : ''; ?>>Debit</option>
            </select>
        </div>

        <div>
            <label for="amount" class="block text-sm font-medium text-gray-600">Amount</label>
            <input
                type="number"
                id="amount"
                name="amount"
                class="w-full p-3 border border-gray-300 rounded"
                value="<?php echo isset($amount) ? htmlspecialchars($amount) : ''; ?>"
                step="0.01"
            />
        </div>

        <div>
            <label for="transaction_date" class="block text-sm font-medium text-gray-600">Transaction Date</label>
            <input
                type="date"
                id="transaction_date"
                name="transaction_date"
                class="w-full p-3 border border-gray-300 rounded"
                value="<?php echo isset($transaction_date) ? htmlspecialchars($transaction_date) : date('Y-m-d'); ?>"
            />
        </div>

        <div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">Add Journal Entry</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
