<?php
session_start();
require 'db_connection.php'; // Pastikan koneksi database di-include

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data pengguna dari database
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style/style-login-page.css">
    <link rel="stylesheet" href="style/user-dashboard.css"> <!-- Link CSS untuk style tabel -->
</head>
<body>
<div class="navbar">
    <div class="navbar-links">
        <a href="products.php" class="navbar-link">Product</a> <!-- Tautan ke halaman produk -->
        <a href="create-user.php" class="navbar-link">Add New User</a>
        <a href="logout.php" class="navbar-link">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>This is your Admin dashboard.</p>
    
    <!-- Tabel Data User -->
    <div class="dashboard-wrapper">
        <h2>Data User</h2>
        <div class="sales-proposal">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th>Info</th> <!-- Kolom tambahan untuk logo kaca pembesar -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <!-- Logo kaca pembesar dengan tautan -->
                                    <a href="data-user.php?user_id=<?php echo $row['id']; ?>" title="View user proposals" class="magnify-icon">
                                        <img src="image/—Pngtree—magnifying glass icon_5464833.png" alt="Info" style="width: 24px; height: 24px;">
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No user data found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>

<?php
// Tutup koneksi database
mysqli_close($conn);
?>
