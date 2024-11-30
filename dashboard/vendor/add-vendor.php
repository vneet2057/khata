<?php
// Start the session to retrieve user ID
session_start();

// Check if user is logged in by checking the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if the user is not logged in
    header("Location: /khata-app/login.php");
    exit();
}

// Include the base layout file
ob_start(); // Start output buffering

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);
    
    // Get the logged-in user's ID from session
    $user_id = $_SESSION['user_id'];

    try {
        // Database connection
        $pdo = new PDO('mysql:host=localhost;dbname=khata-app', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert the vendor data into the table, including user_id
        $stmt = $pdo->prepare("
            INSERT INTO vendors (name, email, phone, address, user_id) 
            VALUES (:name, :email, :phone, :address, :user_id)
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':user_id' => $user_id // Include the user_id from the session
        ]);

        $success_message = "Vendor added successfully!";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!-- Add Vendor Form -->
<div class="container mx-auto px-6 py-4">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Add Vendor</h2>
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

    <form action="add-vendor.php" method="POST" class="space-y-4 bg-white p-6 rounded shadow-md">
        <div>
            <label for="name" class="block text-gray-700 font-medium">Name</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="w-full p-3 border border-gray-300 rounded" 
                placeholder="Enter vendor name" 
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
            ></textarea>
        </div>
        <div>
            <button 
                type="submit" 
                class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Add Vendor
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
