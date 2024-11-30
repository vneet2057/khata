<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Start the session to store user data
session_start();

// Database credentials
$host = "localhost";
$username = "root";
$password = "";
$dbname = "khata-app";

// Initialize variables for dynamic messages
$message = "";
$messageType = "";

// Connect to the database
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
if (!$conn->select_db($dbname)) {
    $sql = "CREATE DATABASE `$dbname`";
    if ($conn->query($sql) === TRUE) {
        $message = "Database created successfully.";
        $messageType = "success";
    } else {
        die("Error creating database: " . $conn->error);
    }
    $conn->select_db($dbname);
}

// Create `user` table if it doesn't exist
$tableCheckQuery = "SHOW TABLES LIKE 'user'";
$result = $conn->query($tableCheckQuery);

if ($result->num_rows === 0) {
    $createTableQuery = "
        CREATE TABLE user (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ";
    if ($conn->query($createTableQuery) === TRUE) {
        $message = "Table 'user' created successfully.";
        $messageType = "success";
    } else {
        die("Error creating table: " . $conn->error);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? null;
    $userPassword = $_POST['password'] ?? null;

    if ($email && $userPassword) {
        // Check if the user exists
        $query = "SELECT * FROM user WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Verify the password for existing user
            $user = $result->fetch_assoc();
            if (password_verify($userPassword, $user['password'])) {
                // Store user ID in session on successful login
                $_SESSION['user_id'] = $user['id']; // Store the user ID in session

                // Redirect to dashboard or another page after successful login
                header("Location: /khata-app/dashboard/index.php");
                exit();
            } else {
                $message = "Invalid email or password.";
                $messageType = "error";
            }
        } else {
            // If no user found, show an error message
            $message = "No user found with that email.";
            $messageType = "error";
        }
    } else {
        $message = "Email and password are required.";
        $messageType = "error";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-center text-gray-800">Login</h2>

        <!-- Display Message -->
        <?php if (!empty($message)): ?>
            <div class="mt-4 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="" method="POST" class="mt-6">
            <!-- Email -->
            <div>
                <label for="email" class="block text-sm text-gray-700">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="w-full mt-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-indigo-300"
                    placeholder="Enter your email"
                    required
                />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <label for="password" class="block text-sm text-gray-700">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full mt-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-indigo-300"
                    placeholder="Enter your password"
                    required
                />
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button
                    type="submit"
                    class="w-full px-4 py-2 text-white bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:ring focus:ring-indigo-300"
                >
                    Submit
                </button>
            </div>
        </form>

        <!-- Register Link -->
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account? 
                <a href="register.php" class="text-indigo-500 hover:text-indigo-600">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>
