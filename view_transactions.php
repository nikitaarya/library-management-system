<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login.php");
    exit();
}

require 'db.php';

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? 'librarian';

// Fetch all transactions with user name and book title
$sql = "SELECT 
            t.id,
            u.name AS user_name,
            b.title AS book_title,
            t.borrow_date,
            t.due_date,
            t.return_date,
            t.returned,
            t.created_at
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        JOIN books b ON t.book_id = b.id
        ORDER BY t.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View All Books</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="heading-row">
                <h2>View All Transactions</h2>
            </div>

            <div class="card">
                <?php if ($result->num_rows > 0): ?>
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Book</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Logged At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= strtoupper($row['user_name']) ?></td>
                                    <td><?= htmlspecialchars($row['book_title']) ?></td>
                                    <td><?= date('d M Y', strtotime($row['borrow_date'])) ?></td>
                                    <td><?= date('d M Y', strtotime($row['due_date'])) ?></td>
                                    <td>
                                        <?= $row['return_date'] ? date('d M Y', strtotime($row['return_date'])) : 'Not Returned' ?>
                                    </td>
                                    <td>
                                        <?= $row['returned'] ? '<span class="badge returned">Returned</span>' : '<span class="badge not-returned">Pending</span>' ?>
                                    </td>
                                    <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <h3 style="text-align: center;">No transactions found.</h3>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>