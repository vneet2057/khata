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

// Check if the journal_entries table exists, if not, create it
$tableCheckQuery = "
    CREATE TABLE IF NOT EXISTS journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL, 
        transaction_type VARCHAR(50) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id)
    ) ENGINE=INNODB;
";
$pdo->exec($tableCheckQuery);

// Handle deletion of journal entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_journal_id'])) {
    // Get the journal ID to delete
    $journal_id = $_POST['delete_journal_id'];

    // Prepare the delete query
    $deleteQuery = "DELETE FROM journal_entries WHERE id = :journal_id";
    $stmt = $pdo->prepare($deleteQuery);
    $stmt->bindParam(':journal_id', $journal_id, PDO::PARAM_INT);

    // Execute the query
    if ($stmt->execute()) {
        // Reload the page after deletion
        header('Location: /khata-app/dashboard/journals/journals.php');
        exit;
    } else {
        echo "Failed to delete the journal entry.";
    }
}

// Fetch journal entries from the database, but only those related to the logged-in user's customers
$query = "
    SELECT je.id, je.customer_id, je.transaction_type, je.amount, je.transaction_date
    FROM journal_entries je
    JOIN customers c ON je.customer_id = c.id
    WHERE c.user_id = :user_id
";
$statement = $pdo->prepare($query);
$statement->bindParam(':user_id', $user_id);
$statement->execute();
$journal_entries = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Journal List</h2>
        <a href="add-journal.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Journal</a>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <input
            type="text"
            id="search"
            placeholder="Search Journal Entries"
            class="w-full p-3 border border-gray-300 rounded"
        />
    </div>

    <!-- Journal Table -->
    <table class="w-full table-auto border-collapse bg-white rounded-lg shadow-md overflow-hidden">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">ID</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Customer</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Transaction Type</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Amount</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Date</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Actions</th>
            </tr>
        </thead>
        <tbody id="journal-table-body">
            <?php if (!empty($journal_entries)): ?>
                <?php foreach ($journal_entries as $entry): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['id']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t">
                            <?php 
                            // Fetch customer name based on customer_id
                            $customerQuery = "SELECT name FROM customers WHERE id = :customer_id";
                            $customerStmt = $pdo->prepare($customerQuery);
                            $customerStmt->bindParam(':customer_id', $entry['customer_id']);
                            $customerStmt->execute();
                            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
                            echo htmlspecialchars($customer['name']);
                            ?>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['transaction_type']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['amount']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['transaction_date']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t">
                            <a href="edit-journal.php?journal_id=<?php echo $entry['id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                            |
                            <button
                                class="text-red-600 hover:text-red-800 delete-btn"
                                data-journal-id="<?php echo $entry['id']; ?>"
                                data-customer-id="<?php echo $entry['customer_id']; ?>"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-600">No journal entries found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Deleting Journal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow-md text-center max-w-lg w-full">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Are you sure you want to delete this journal entry?</h3>
        <form action="/khata-app/dashboard/journals/journals.php" method="POST" id="deleteForm">
            <input type="hidden" name="delete_journal_id" id="delete_journal_id">
            <div class="flex justify-between">
                <button type="button" class="bg-gray-400 text-white px-4 py-2 rounded mr-2" id="cancelBtn">Cancel</button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const deleteModal = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const deleteJournalIdInput = document.getElementById('delete_journal_id');
    const cancelBtn = document.getElementById('cancelBtn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const journalId = button.getAttribute('data-journal-id');
            deleteJournalIdInput.value = journalId;
            deleteModal.classList.remove('hidden');
        });
    });

    cancelBtn.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
    });
</script>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
