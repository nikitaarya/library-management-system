<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];

include 'db.php';

// General stats
$bookCount = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$borrowedCount = $conn->query("SELECT COUNT(*) AS total FROM transactions WHERE returned = 0")->fetch_assoc()['total'];
$totalTransactions = $conn->query("SELECT COUNT(*) AS total FROM transactions")->fetch_assoc()['total'];

if ($role === 'librarian') {
    $studentCount = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
    $toBeReturnedToday = $conn->query("SELECT COUNT(*) AS total FROM transactions WHERE due_date = CURDATE() AND returned = 0")->fetch_assoc()['total'];
    $overdueBooks = $conn->query("SELECT COUNT(*) AS total FROM transactions WHERE due_date < CURDATE() AND returned = 0")->fetch_assoc()['total'];
    $returnedToday = $conn->query("SELECT COUNT(*) AS total FROM transactions WHERE return_date = CURDATE() AND returned = 1")->fetch_assoc()['total'];
    $activeBorrowers = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM transactions WHERE returned = 0")->fetch_assoc()['total'];
} else {
    // For students
    // Count total borrowed books by student (Not Returned)
    $userBorrowed = $conn->prepare("SELECT COUNT(*) AS total FROM transactions WHERE user_id = ? AND returned = 0");
    $userBorrowed->bind_param("i", $_SESSION['user_id']);
    $userBorrowed->execute();
    $userBorrowedCount = $userBorrowed->get_result()->fetch_assoc()['total'];
    $userBorrowed->close();

    // Count books currently borrowed (Not Returned)
    $currentlyBorrowed = $conn->prepare("SELECT COUNT(*) AS total FROM transactions WHERE user_id = ? AND returned = 0");
    $currentlyBorrowed->bind_param("i", $_SESSION['user_id']);
    $currentlyBorrowed->execute();
    $currentlyBorrowedCount = $currentlyBorrowed->get_result()->fetch_assoc()['total'];
    $currentlyBorrowed->close();

    // Count books that have been returned by student
    $returnedBooks = $conn->prepare("SELECT COUNT(*) AS total FROM transactions WHERE user_id = ? AND returned = 1");
    $returnedBooks->bind_param("i", $_SESSION['user_id']);
    $returnedBooks->execute();
    $returnedBooksCount = $returnedBooks->get_result()->fetch_assoc()['total'];
    $returnedBooks->close();

    // Count overdue books
    $today = date('Y-m-d');
    $overdueBooks = $conn->prepare("SELECT COUNT(*) AS total FROM transactions WHERE user_id = ? AND returned = 0 AND due_date < ?");
    $overdueBooks->bind_param("is", $_SESSION['user_id'], $today);
    $overdueBooks->execute();
    $overdueBooksCount = $overdueBooks->get_result()->fetch_assoc()['total'];
    $overdueBooks->close();
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <h2><?= ucfirst($role) ?> Dashboard</h2>

            <div class="cards-container" style="margin-top: 60px;">
                <div class="card" style="border: 1px solid #007bff;">
                    <h3>Total Books</h3>
                    <p><?= $bookCount ?></p>
                </div>

                <?php if ($role === 'librarian'): ?>
                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Total Registered Students</h3>
                        <p><?= $studentCount ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Total Transactions</h3>
                        <p><?= $totalTransactions ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Borrowed Books</h3>
                        <p><?= $borrowedCount ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Books To Be Returned Today</h3>
                        <p><?= $toBeReturnedToday ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #dc3545;">
                        <h3>Overdue Books</h3>
                        <p><?= $overdueBooks ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Books Returned Today</h3>
                        <p><?= $returnedToday ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Active Borrowers</h3>
                        <p><?= $activeBorrowers ?></p>
                    </div>

                <?php else: ?>
                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Total Books You've Borrowed</h3>
                        <p><?= $userBorrowedCount ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Currently Borrowed (Not Returned)</h3>
                        <p><?= $currentlyBorrowedCount ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #007bff;">
                        <h3>Books You Returned</h3>
                        <p><?= $returnedBooksCount ?></p>
                    </div>

                    <div class="card" style="border: 1px solid #dc3545;">
                        <h3>Overdue Books</h3>
                        <p><?= $overdueBooksCount ?></p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>

</html>