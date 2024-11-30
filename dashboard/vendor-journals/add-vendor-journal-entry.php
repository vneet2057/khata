<?php
// Start the session to retrieve user ID
session_start();

// Check if user is logged in by checking the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if the user is not logged in
    header("Location: /khata-app/login.php");
    exit();
}

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=khata-app', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get the logged-in user's ID from session
$user_id = $_SESSION['user_id'];

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $vendor_id = $_POST['vendor_id'];
    $transaction_type = htmlspecialchars($_POST['transaction_type']);
    $amount = htmlspecialchars($_POST['amount']);
    $transaction_date = htmlspecialchars($_POST['transaction_date']);

    try {
        // Insert the vendor journal entry into the table
        $stmt = $pdo->prepare("
            INSERT INTO vendor_journal_entries (vendor_id, transaction_type, amount, transaction_date) 
            VALUES (:vendor_id, :transaction_type, :amount, :transaction_date)
        ");
        $stmt->execute([
            ':vendor_id' => $vendor_id,
            ':transaction_type' => $transaction_type,
            ':amount' => $amount,
            ':transaction_date' => $transaction_date,
        ]);

        $success_message = "Vendor journal entry added successfully!";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Fetch vendors for the dropdown
$vendorQuery = "SELECT id, name FROM vendors WHERE user_id = :user_id";
$stmt = $pdo->prepare($vendorQuery);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Add Vendor Journal Entry Form -->
<div class="container mx-auto px-6 py-4">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Add Vendor Journal Entry</h2>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-6">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-6">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <form action="add-vendor-journal-entry.php" method="POST" class="space-y-4 bg-white p-6 rounded shadow-md">
        <div>
            <label for="vendor_id" class="block text-gray-700 font-medium">Vendor</label>
            <select 
                id="vendor_id" 
                name="vendor_id" 
                class="w-full p-3 border border-gray-300 rounded" 
                required
            >
                <option value="">Select Vendor</option>
                <?php foreach ($vendors as $vendor): ?>
                    <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="transaction_type" class="block text-gray-700 font-medium">Transaction Type</label>
            <select 
                id="transaction_type" 
                name="transaction_type" 
                class="w-full p-3 border border-gray-300 rounded" 
                required
            >
                <option value="Credit">Credit</option>
                <option value="Debit">Debit</option>
            </select>
        </div>
        <div>
            <label for="amount" class="block text-gray-700 font-medium">Amount</label>
            <input 
                type="number" 
                id="amount" 
                name="amount" 
                class="w-full p-3 border border-gray-300 rounded" 
                placeholder="Enter amount" 
                required
            />
        </div>
        <div>
            <label for="transaction_date" class="block text-gray-700 font-medium">Transaction Date</label>
            <input 
                type="date" 
                id="transaction_date" 
                name="transaction_date" 
                class="w-full p-3 border border-gray-300 rounded" 
                required
            />
        </div>
        <div>
            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Add Journal Entry
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
