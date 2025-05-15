<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    header("Location: login.php");
    exit();
}

require 'db.php';

$name = $_SESSION['name'];
$role = $_SESSION['role'];

$errors = $errors ?? [];
$title = $title ?? '';
$author = $author ?? '';
$genre = $genre ?? '';
$quantity = $quantity ?? '';


$errors = [];
$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$genre = trim($_POST['genre'] ?? '');
$quantity = $_POST['quantity'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate title
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    }

    // Validate author
    if (empty($author)) {
        $errors['author'] = 'Author is required';
    }

    // Validate genre
    if (empty($genre)) {
        $errors['genre'] = 'Genre is required';
    }

    // Validate quantity
    if (!is_numeric($quantity) || $quantity <= 0) {
        $errors['quantity'] = 'Quantity must be a positive number';
    }

    // If no errors, insert into DB
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO books (title, author, genre, quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $author, $genre, $quantity);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Book added successfully!";
            header("Location: view_books.php");
            exit();
        } else {
            $errors['db'] = "Database error: Unable to add book.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New Book</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="heading-row">
                <h2>Add New Book</h2>
                <a href="view_books.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
            <form action="add_book.php" method="POST" class="book-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" value="<?= htmlspecialchars($title) ?>" required>
                        <?php if (!empty($errors['title'])): ?>
                            <p class="error"><?= $errors['title'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" name="author" id="author" value="<?= htmlspecialchars($author) ?>" required>
                        <?php if (!empty($errors['author'])): ?>
                            <p class="error"><?= $errors['author'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="genre">Genre</label>
                        <input type="text" name="genre" id="genre" value="<?= htmlspecialchars($genre) ?>" required>
                        <?php if (!empty($errors['genre'])): ?>
                            <p class="error"><?= $errors['genre'] ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" name="quantity" id="quantity" value="<?= htmlspecialchars($quantity) ?>" >
                        <?php if (!empty($errors['quantity'])): ?>
                            <p class="error"><?= $errors['quantity'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn"><i class="fas fa-save"></i> Save Book</button>
            </form>
        </main>
    </div>
</body>

</html>