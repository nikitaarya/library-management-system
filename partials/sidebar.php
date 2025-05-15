<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-top">
        <h2 class="sidebar-title"><i class="fas fa-book-reader"></i> Library System</h2>
        <p class="sidebar-user"><i class="fas fa-user"></i> Welcome, <?= ucfirst($name) ?></p>
        <p class="sidebar-role"><i class="fas fa-user-tag"></i> Role: <?= ucfirst($role) ?></p>
        <nav class="nav-links">
            <a href="dashboard.php"> Dashboard</a>
            <?php if ($role === 'librarian'): ?>
                <a href="add_book.php"><i class="fas fa-plus-circle"></i> Add New Book</a>
                <a href="view_books.php"><i class="fas fa-book"></i> View All Books</a>
                <a href="view_users.php"><i class="fas fa-users"></i> View Registered Users</a>
                <a href="view_transactions.php"><i class="fas fa-exchange-alt"></i> View Borrowed Books</a>
            <?php else: ?>
                <a href="view_books.php">Browse Available Books</a>
                <a href="return_book.php">Return Borrowed Books</a>
            <?php endif; ?>
        </nav>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>