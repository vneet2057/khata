<?php
// Start session only if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering
ob_start();

// Report all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = "localhost";
$username = "root";
$password = "";
$dbname = "khata-app";

// Check if user is logged in by checking the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if the user is not logged in
    header("Location: /khata-app/login.php");
    exit();
}

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the user_id from the session
$user_id = $_SESSION['user_id'];

// Create `vendors` table with foreign key referencing `user` table
$tableCheckQuery = "SHOW TABLES LIKE 'vendors'";
$result = $conn->query($tableCheckQuery);

if ($result->num_rows === 0) {
    $createTableQuery = "
        CREATE TABLE vendors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        ) ENGINE=InnoDB;
    ";
    if ($conn->query($createTableQuery) === TRUE) {
        $message = "Table 'vendors' created successfully.";
        $messageType = "success";
    } else {
        die("Error creating table: " . $conn->error);
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vendor_id'])) {
    $deleteVendorId = intval($_POST['delete_vendor_id']);
    $deleteQuery = "DELETE FROM vendors WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $deleteVendorId, $user_id);
    if ($stmt->execute()) {
        header("Location: /khata-app/dashboard/vendor/vendors.php");
        exit();
    } else {
        echo "<script>alert('Failed to delete vendor.');</script>";
    }
}

// Handle search query
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $conn->real_escape_string($_GET['search']);
}

// Fetch vendors and calculate remaining credit from vendor journal entries
$vendors = [];
$query = "
    SELECT v.id, v.name, v.email, v.phone, v.user_id,
           SUM(CASE WHEN j.transaction_type = 'credit' THEN j.amount ELSE 0 END) AS total_credit,
           SUM(CASE WHEN j.transaction_type = 'debit' THEN j.amount ELSE 0 END) AS total_debit
    FROM vendors v
    LEFT JOIN vendor_journal_entries j ON v.id = j.vendor_id
    WHERE v.user_id = $user_id
      AND (v.name LIKE '%$searchTerm%' OR v.email LIKE '%$searchTerm%' OR v.phone LIKE '%$searchTerm%')
    GROUP BY v.id, v.name, v.email, v.phone, v.user_id
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $remainingCredit = $row['total_credit'] - $row['total_debit']; // Calculate remaining credit
        $row['remaining_credit'] = $remainingCredit; // Add remaining credit to the result
        $vendors[] = $row;
    }
}

$conn->close();
?>

<div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Vendor List</h2>
        <!-- Add Vendor Button -->
        <a href="add-vendor.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Vendor</a>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <form method="GET" action="" id="searchForm">
            <input
                type="text"
                id="search"
                name="search"
                placeholder="Search Vendors"
                value="<?php echo htmlspecialchars($searchTerm); ?>"
                class="w-full p-3 border border-gray-300 rounded"
            />
        </form>
    </div>

    <!-- Vendor Table -->
    <table class="w-full table-auto border-collapse bg-white rounded-lg shadow-md overflow-hidden">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">ID</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Name</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Email</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Phone</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Remaining Credit</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-600 border-b">Actions</th>
            </tr>
        </thead>
        <tbody id="vendor-table-body">
            <?php if (!empty($vendors)): ?>
                <?php foreach ($vendors as $vendor): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($vendor['id']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($vendor['name']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($vendor['email']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($vendor['phone']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo number_format($vendor['remaining_credit'], 2); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t">
                            <a href="edit-vendor.php?vendor_id=<?php echo $vendor['id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                            |
                            <button
                                class="text-red-600 hover:text-red-800 delete-btn"
                                data-vendor-id="<?php echo $vendor['id']; ?>"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-600">No vendors found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow-md text-center max-w-lg w-full">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Are you sure you want to delete this vendor?</h3>
        <form action="" method="POST" id="deleteForm">
            <input type="hidden" name="delete_vendor_id" id="delete_vendor_id">
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
    const deleteVendorIdInput = document.getElementById('delete_vendor_id');
    const cancelBtn = document.getElementById('cancelBtn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const vendorId = button.getAttribute('data-vendor-id');
            deleteVendorIdInput.value = vendorId;
            deleteModal.classList.remove('hidden');
        });
    });

    cancelBtn.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
    });
</script>

<?php
$content = ob_get_clean();
include('../layout/base.php');
?>
