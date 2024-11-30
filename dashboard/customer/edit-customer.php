<?php
// Include the base layout file
ob_start(); // Start output buffering

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=khata-app', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch customer data if ID is passed
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $customerId = intval($_GET['id']);
    
    // Get customer data
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        die("Customer not found.");
    }
}

// Handle form submission for updating customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer_id'])) {
    $customerId = intval($_POST['update_customer_id']);
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);

    try {
        // Update the customer in the database
        $stmt = $pdo->prepare("
            UPDATE customers SET name = :name, email = :email, phone = :phone, address = :address 
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':id' => $customerId
        ]);

        $success_message = "Customer updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

?>

<!-- Edit Customer Form -->
<div class="container mx-auto px-6 py-4">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Edit Customer</h2>
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

    <form action="edit-customer.php?id=<?php echo $customer['id']; ?>" method="POST" class="space-y-4 bg-white p-6 rounded shadow-md">
        <input type="hidden" name="update_customer_id" value="<?php echo $customer['id']; ?>">

        <div>
            <label for="name" class="block text-gray-700 font-medium">Name</label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="w-full p-3 border border-gray-300 rounded" 
                value="<?php echo htmlspecialchars($customer['name']); ?>"
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
                value="<?php echo htmlspecialchars($customer['email']); ?>"
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
                value="<?php echo htmlspecialchars($customer['phone']); ?>"
                required
            />
        </div>
        <div>
            <label for="address" class="block text-gray-700 font-medium">Address</label>
            <textarea 
                id="address" 
                name="address" 
                class="w-full p-3 border border-gray-300 rounded" 
                required
            ><?php echo htmlspecialchars($customer['address']); ?></textarea>
        </div>
        <div>
            <button 
                type="submit" 
                class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Update Customer
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean(); // Get the output of the page content

// Now include the base layout and pass the content
include('../layout/base.php');
?>
