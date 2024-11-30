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

// Get the vendor_id from the URL
if (!isset($_GET['vendor_id'])) {
    // Redirect to vendor list if no vendor ID is provided
    header("Location: /khata-app/dashboard/vendors/vendors.php");
    exit();
}

$vendor_id = $_GET['vendor_id'];

// Get the logged-in user's ID from session
$user_id = $_SESSION['user_id'];

// Fetch vendor data based on vendor_id and user_id (to ensure vendor belongs to the logged-in user)
$query = "
    SELECT id, name, email, phone, address
    FROM vendors
    WHERE id = :vendor_id AND user_id = :user_id
";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':vendor_id', $vendor_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

// If vendor is not found, redirect to vendor list
if (!$vendor) {
    header("Location: /khata-app/dashboard/vendors/vendors.php");
    exit();
}

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);

    try {
        // Update vendor data in the database
        $updateQuery = "
            UPDATE vendors 
            SET name = :name, email = :email, phone = :phone, address = :address 
            WHERE id = :vendor_id AND user_id = :user_id
        ";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':vendor_id' => $vendor_id,
            ':user_id' => $user_id
        ]);

        $success_message = "Vendor updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

?>

<!-- Edit Vendor Form -->
<div class="container mx-auto px-6 py-4">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Edit Vendor</h2>
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

    <form action="edit-vendor.php?vendor_id=<?php echo $vendor['id']; ?>" method="POST" class="space-y-4 bg-white p-6 rounded shadow-md">
        <div>
            <label for="name" class="block text-gray-700 font-medium">Name</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="w-full p-3 border border-gray-300 rounded" 
                placeholder="Enter vendor name" 
                value="<?php echo htmlspecialchars($vendor['name']); ?>" 
                required
            />
        </div>
        <div>
            <label for="email" class="block text-gray-700 font-medium">Email</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="w-full p-3 border border-gray-300 rounded" 
                placeholder="Enter vendor email" 
                value="<?php echo htmlspecialchars($vendor['email']); ?>" 
                required
            />
        </div>
        <div>
            <label for="phone" class="block text-gray-700 font-medium">Phone</label>
            <input 
                type="text" 
                id="phone" 
                name="phone" 
                class="w-full p-3 border border-gray-300 rounded" 
                placeholder="Enter vendor phone" 
                value="<?php echo htmlspecialchars($vendor['phone']); ?>" 
                required
            />
        </div>
        <div>
            <label for="address" class="block text-gray-700 font-medium">Address</label>
            <textarea 
                id="address" 
                name="address" 
                class="w-full p-3 border border-gray-300 rounded" 
                placeholder="Enter vendor address"
            ><?php echo htmlspecialchars($vendor['address']); ?></textarea>
        </div>
        <div>
            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Update Vendor
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
