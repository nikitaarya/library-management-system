<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? 'student';

// Handle book return
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book_id'])) {
    $returnBookId = intval($_POST['return_book_id']);
    $transactionId = intval($_POST['transaction_id']);
    $returnDate = date('Y-m-d');

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update transaction record
        $updateStmt = $conn->prepare("UPDATE transactions SET return_date = ?, returned = TRUE WHERE id = ? AND user_id = ?");
        $updateStmt->bind_param("sii", $returnDate, $transactionId, $user_id);

        // Increase book quantity
        $bookStmt = $conn->prepare("UPDATE books SET quantity = quantity + 1 WHERE id = ?");

        if ($updateStmt->execute()) {
            $bookStmt->bind_param("i", $returnBookId);
            $bookStmt->execute();
            $conn->commit();
            $_SESSION['success'] = "Book returned successfully!";
        } else {
            $conn->rollback();
            $_SESSION['success'] = "Failed to return the book. Try again.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['success'] = "Error: " . $e->getMessage();
    }

    $updateStmt->close();
    $bookStmt->close();
    header("Location: return_book.php");
    exit();
}

// Fetch ALL transactions for this user (both current and past)
$stmt = $conn->prepare("
    SELECT t.id as transaction_id, t.borrow_date, t.due_date, t.return_date, 
           b.id as book_id, b.title, b.author, b.genre
    FROM transactions t
    JOIN books b ON t.book_id = b.id
    WHERE t.user_id = ?
    ORDER BY t.return_date IS NULL DESC, t.due_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Borrowing History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <div class="heading-row">
                <h2>My Borrowing History</h2>
                <a href="view_books.php" class="btn add-btn"><i class="fas fa-book"></i> Browse Books</a>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="success-message">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <?php if ($result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="book-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Genre</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()):
                                    $isOverdue = !$row['return_date'] && strtotime($row['due_date']) < time();
                                    $isReturned = !is_null($row['return_date']);
                                ?>
                                    <tr class="<?= $isOverdue && !$isReturned ? 'overdue-row' : '' ?>">
                                        <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= htmlspecialchars($row['author']) ?></td>
                                        <td><?= htmlspecialchars($row['genre']) ?></td>
                                        <td><?= date('d M Y', strtotime($row['borrow_date'])) ?></td>
                                        <td><?= date('d M Y', strtotime($row['due_date'])) ?></td>
                                        <td><?= $row['return_date'] ? date('d M Y', strtotime($row['return_date'])) : 'Null' ?></td>
                                        <td>
                                            <?php if ($isReturned): ?>
                                                <span class="status-returned">Returned</span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="status-overdue">Overdue</span>
                                            <?php else: ?>
                                                <span class="status-borrowed">Borrowed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$isReturned): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to return this book?');">
                                                    <input type="hidden" name="return_book_id" value="<?= $row['book_id'] ?>">
                                                    <input type="hidden" name="transaction_id" value="<?= $row['transaction_id'] ?>">
                                                    <button type="submit" class="btn return-btn" style="width: 120px;">
                                                        <i class="fas fa-undo-alt"></i> Return
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn return-btn" style="width: 120px;" disabled>
                                                    <i class="fas fa-check"></i> Returned
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <h3 style="text-align: center;">You haven't borrowed any books yet.</h3>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>