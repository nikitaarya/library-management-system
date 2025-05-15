<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login.php");
    exit();
}

require 'db.php';

$name = $_SESSION['name'];
$role = $_SESSION['role'];

$result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Registered Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>
        <!-- Main Content -->
        <main class="main-content">
            <div class="heading-row">
                <h2>View Registered Users</h2>
            </div>

            <div class="card">
                <?php if ($result->num_rows > 0): ?>
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= ucfirst($user['role']) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No registered users found.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>