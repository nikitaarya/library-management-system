<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$searchQuery = '';
$searchTerm = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $searchQuery = "WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book_id'])) {
    $borrowBookId = intval($_POST['borrow_book_id']);
    $userId = $_SESSION['user_id'];

    $checkBorrowedStmt = $conn->prepare("SELECT id FROM transactions WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
    $checkBorrowedStmt->bind_param("ii", $userId, $borrowBookId);
    $checkBorrowedStmt->execute();
    $checkBorrowedStmt->store_result();

    if ($checkBorrowedStmt->num_rows > 0) {
        $_SESSION['success'] = "You already have this book borrowed.";
        header("Location: view_books.php");
        exit();
    }
    $checkBorrowedStmt->close();

    $checkStmt = $conn->prepare("SELECT quantity FROM books WHERE id = ?");
    $checkStmt->bind_param("i", $borrowBookId);
    $checkStmt->execute();
    $checkStmt->bind_result($quantity);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($quantity > 0) {
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+14 days'));

        $insertStmt = $conn->prepare("INSERT INTO transactions (user_id, book_id, borrow_date, due_date) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("iiss", $userId, $borrowBookId, $borrowDate, $dueDate);
        $updateStmt = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");

        if ($insertStmt->execute()) {
            $updateStmt->bind_param("i", $borrowBookId);
            $updateStmt->execute();
            $_SESSION['success'] = "Book borrowed successfully!";
        } else {
            $_SESSION['success'] = "Failed to borrow the book.";
        }

        $insertStmt->close();
        $updateStmt->close();
    } else {
        $_SESSION['success'] = "This book is currently unavailable.";
    }

    header("Location: view_books.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? 'student';

if ($role === 'librarian' && isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $_SESSION['success'] = $stmt->affected_rows > 0 ? "Book deleted successfully." : "Failed to delete book.";
    $stmt->close();
    header("Location: view_books.php");
    exit();
}

$borrowedBooks = [];
if ($role === 'student') {
    $checkBorrowedStmt = $conn->prepare("SELECT book_id FROM transactions WHERE user_id = ? AND return_date IS NULL");
    $checkBorrowedStmt->bind_param("i", $user_id);
    $checkBorrowedStmt->execute();
    $result = $checkBorrowedStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $borrowedBooks[] = $row['book_id'];
    }
    $checkBorrowedStmt->close();
}

// Fetch books (with optional search)
if (!empty($searchQuery)) {
    $likeTerm = "%" . $searchTerm . "%";
    $stmt = $conn->prepare("SELECT * FROM books $searchQuery ORDER BY created_at ASC");
    $stmt->bind_param("sss", $likeTerm, $likeTerm, $likeTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query("SELECT * FROM books ORDER BY created_at ASC");
}
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
        <?php include 'partials/sidebar.php'; ?>

        <main class="main-content">
            <div class="heading-row">
                <h2>View All Books</h2>
                <?php if ($role === 'librarian'): ?>
                    <a href="add_book.php" class="btn add-btn" style="width: 180px;"><i class="fas fa-plus-circle"></i> Add New Book</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="success-message">
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by title, author, or genre" value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="view_books.php" class="reset-btn"><i class="fas fa-undo-alt"></i> Reset</a>
                <?php endif; ?>
            </form>

            <div class="card">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Genre</th>
                                <?php if ($role === 'librarian'): ?>
                                    <th>Quantity</th>
                                <?php endif; ?>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($book = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $book['id'] ?></td>
                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['genre']) ?></td>
                                    <?php if ($role === 'librarian'): ?>
                                        <td><?= htmlspecialchars($book['quantity']) ?></td>
                                    <?php endif; ?>
                                    <td><?= date('d M Y, H:i', strtotime($book['created_at'])) ?></td>
                                    <td>
                                        <?php if ($role === 'librarian'): ?>
                                            <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn edit-btn"><i class="fas fa-edit"></i></a>
                                            <a href="view_books.php?delete_id=<?= $book['id'] ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this book?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to borrow this book?');">
                                                <input type="hidden" name="borrow_book_id" value="<?= $book['id'] ?>">
                                                <button type="submit" class="btn borrow-btn" <?= ($book['quantity'] < 1 || in_array($book['id'], $borrowedBooks)) ? 'disabled' : '' ?>>
                                                    <i class="fas fa-book-reader"></i>
                                                    <?= ($book['quantity'] < 1) ? 'Unavailable' : (in_array($book['id'], $borrowedBooks) ? 'Borrowed' : 'Borrow') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <h3 style="text-align: center;">No books found<?= $searchTerm ? " for '$searchTerm'" : '' ?>.</h3>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>