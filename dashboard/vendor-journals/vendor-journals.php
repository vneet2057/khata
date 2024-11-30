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

// Check if the vendor_journal_entries table exists, if not, create it
$tableCheckQuery = "
    CREATE TABLE IF NOT EXISTS vendor_journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_id INT NOT NULL,
        transaction_type VARCHAR(50) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    ) ENGINE=INNODB;
";
$pdo->exec($tableCheckQuery);

// Handle deletion of journal entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vendor_journal_id'])) {
    // Get the journal ID to delete
    $vendor_journal_id = $_POST['delete_vendor_journal_id'];

    // Prepare the delete query
    $deleteQuery = "DELETE FROM vendor_journal_entries WHERE id = :vendor_journal_id";
    $stmt = $pdo->prepare($deleteQuery);
    $stmt->bindParam(':vendor_journal_id', $vendor_journal_id, PDO::PARAM_INT);

    // Execute the query
    if ($stmt->execute()) {
        // Reload the page after deletion
        header('Location: /khata-app/dashboard/vendor-journals/vendor_journals.php');
        exit;
    } else {
        echo "Failed to delete the vendor journal entry.";
    }
}

// Fetch vendor journal entries from the database, but only those related to the logged-in user's vendors
$query = "
    SELECT vje.id, vje.vendor_id, vje.transaction_type, vje.amount, vje.transaction_date
    FROM vendor_journal_entries vje
    JOIN vendors v ON vje.vendor_id = v.id
    WHERE v.user_id = :user_id
";
$statement = $pdo->prepare($query);
$statement->bindParam(':user_id', $user_id);
$statement->execute();
$vendor_journal_entries = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Vendor Journal List</h2>
        <a href="add-vendor-journal-entry.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Vendor Journal</a>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <input
            type="text"
            id="search"
            placeholder="Search Vendor Journal Entries"
            class="w-full p-3 border border-gray-300 rounded"
        />
    </div>

    <!-- Vendor Journal Table -->
    <table class="w-full table-auto border-collapse bg-white rounded-lg shadow-md overflow-hidden">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">ID</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Vendor</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Transaction Type</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Amount</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Date</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Actions</th>
            </tr>
        </thead>
        <tbody id="vendor-journal-table-body">
            <?php if (!empty($vendor_journal_entries)): ?>
                <?php foreach ($vendor_journal_entries as $entry): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['id']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t">
                            <?php 
                            // Fetch vendor name based on vendor_id
                            $vendorQuery = "SELECT name FROM vendors WHERE id = :vendor_id";
                            $vendorStmt = $pdo->prepare($vendorQuery);
                            $vendorStmt->bindParam(':vendor_id', $entry['vendor_id']);
                            $vendorStmt->execute();
                            $vendor = $vendorStmt->fetch(PDO::FETCH_ASSOC);
                            echo htmlspecialchars($vendor['name']);
                            ?>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['transaction_type']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['amount']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($entry['transaction_date']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t">
                            <a href="edit-vendor-journal.php?vendor_journal_id=<?php echo $entry['id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                            |
                            <button
                                class="text-red-600 hover:text-red-800 delete-btn"
                                data-vendor-journal-id="<?php echo $entry['id']; ?>"
                                data-vendor-id="<?php echo $entry['vendor_id']; ?>"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-600">No vendor journal entries found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Deleting Vendor Journal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow-md text-center max-w-lg w-full">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Are you sure you want to delete this vendor journal entry?</h3>
        <form action="/khata-app/dashboard/vendor-journals/vendor_journals.php" method="POST" id="deleteForm">
            <input type="hidden" name="delete_vendor_journal_id" id="delete_vendor_journal_id">
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
    const deleteVendorJournalIdInput = document.getElementById('delete_vendor_journal_id');
    const cancelBtn = document.getElementById('cancelBtn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const vendorJournalId = button.getAttribute('data-vendor-journal-id');
            deleteVendorJournalIdInput.value = vendorJournalId;
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
