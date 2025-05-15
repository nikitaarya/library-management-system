<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if a librarian already exists
$hasLibrarian = false;
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'librarian'");
if ($result) {
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        $hasLibrarian = true;
    }
}

$error = '';
$name = $email = $password = $confirmPassword = $role = '';

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Server-side validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if the email or name already exists
        $emailCheckQuery = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
        $emailCheckQuery->bind_param("s", $email);
        $emailCheckQuery->execute();
        $emailCheckResult = $emailCheckQuery->get_result();
        $emailRow = $emailCheckResult->fetch_assoc();

        $nameCheckQuery = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE name = ?");
        $nameCheckQuery->bind_param("s", $name);
        $nameCheckQuery->execute();
        $nameCheckResult = $nameCheckQuery->get_result();
        $nameRow = $nameCheckResult->fetch_assoc();

        // If email or name already exists
        if ($emailRow['count'] > 0) {
            $error = "This email is already registered.";
        } elseif ($nameRow['count'] > 0) {
            $error = "This name is already taken.";
        } else {
            // Default to student if librarian is already registered
            $role = ($_POST['role'] === 'librarian' && !$hasLibrarian) ? 'librarian' : 'student';

            // Hash the password before storing it
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Prepare and execute the insert query
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                // Successful registration, redirect to login page
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                header("Location: login.php");
                exit();
            } else {
                $error = "Error: " . $conn->error;
            }

            $stmt->close();
        }

        // Close prepared statements
        $emailCheckQuery->close();
        $nameCheckQuery->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="fas fa-book-open"></i>
            </div>
            <h2>Library Management System - Register</h2>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="post" action="register.php" novalidate>
                <div class="form-group">
                    <label for="name">Name</label>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" placeholder="Enter your name" value="<?= htmlspecialchars($name) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Register As</label>
                    <div class="input-field">
                        <select name="role" id="role" required>
                            <option value="student" <?php echo ($role == 'student') ? 'selected' : ''; ?>>Student</option>
                            <?php if (!$hasLibrarian): ?>
                                <option value="librarian" <?php echo ($role == 'librarian') ? 'selected' : ''; ?>>Librarian</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn">Register</button>
            </form>

            <p class="bottom-text">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>

</html>
