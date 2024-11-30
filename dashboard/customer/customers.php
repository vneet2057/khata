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

// Create `customers` table with foreign key referencing `user` table
$tableCheckQuery = "SHOW TABLES LIKE 'customers'";
$result = $conn->query($tableCheckQuery);

if ($result->num_rows === 0) {
    $createTableQuery = "
        CREATE TABLE customers (
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
        $message = "Table 'customers' created successfully.";
        $messageType = "success";
    } else {
        die("Error creating table: " . $conn->error);
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer_id'])) {
    $deleteCustomerId = intval($_POST['delete_customer_id']);
    $deleteQuery = "DELETE FROM customers WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $deleteCustomerId, $user_id);
    if ($stmt->execute()) {
        header("Location: /khata-app/dashboard/customer/customers.php");
        exit();
    } else {
        echo "<script>alert('Failed to delete customer.');</script>";
    }
}

// Handle search query
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $conn->real_escape_string($_GET['search']);
}

// Fetch customers and calculate remaining credit from journal entries
$customers = [];
$query = "
    SELECT c.id, c.name, c.email, c.phone, c.user_id,
           SUM(CASE WHEN j.transaction_type = 'credit' THEN j.amount ELSE 0 END) AS total_credit,
           SUM(CASE WHEN j.transaction_type = 'debit' THEN j.amount ELSE 0 END) AS total_debit
    FROM customers c
    LEFT JOIN journal_entries j ON c.id = j.customer_id
    WHERE c.user_id = $user_id
      AND (c.name LIKE '%$searchTerm%' OR c.email LIKE '%$searchTerm%' OR c.phone LIKE '%$searchTerm%')
    GROUP BY c.id, c.name, c.email, c.phone, c.user_id
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $remainingCredit = $row['total_credit'] - $row['total_debit']; // Calculate remaining credit
        $row['remaining_credit'] = $remainingCredit; // Add remaining credit to the result
        $customers[] = $row;
    }
}

$conn->close();
?>

<div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Customer List</h2>
        <!-- Add Customer Button -->
        <a href="add-customer.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Customer</a>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <form method="GET" action="" id="searchForm">
            <input
                type="text"
                id="search"
                name="search"
                placeholder="Search Customers"
                value="<?php echo htmlspecialchars($searchTerm); ?>"
                class="w-full p-3 border border-gray-300 rounded"
            />
        </form>
    </div>

    <!-- Customer Table -->
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
        <tbody id="customer-table-body">
            <?php if (!empty($customers)): ?>
                <?php foreach ($customers as $customer): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($customer['id']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo htmlspecialchars($customer['phone']); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t"><?php echo number_format($customer['remaining_credit'], 2); ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800 border-t">
                            <a href="edit-customer.php?id=<?php echo $customer['id']; ?>" class="text-blue-600 hover:text-blue-800">Edit</a>
                            |
                            <button
                                class="text-red-600 hover:text-red-800 delete-btn"
                                data-customer-id="<?php echo $customer['id']; ?>"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-600">No customers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white p-6 rounded shadow-md text-center max-w-lg w-full">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Are you sure you want to delete this customer?</h3>
        <form action="" method="POST" id="deleteForm">
            <input type="hidden" name="delete_customer_id" id="delete_customer_id">
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
    const deleteCustomerIdInput = document.getElementById('delete_customer_id');
    const cancelBtn = document.getElementById('cancelBtn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const customerId = button.getAttribute('data-customer-id');
            deleteCustomerIdInput.value = customerId;
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
