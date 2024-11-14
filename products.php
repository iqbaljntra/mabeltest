<?php
session_start();
require 'db_connection.php'; // Pastikan koneksi database di-include

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Menentukan batasan data per halaman
$limit = isset($_GET['limit']) ? $_GET['limit'] : 10; // Default 10
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk menghitung total data produk
$totalQuery = "SELECT COUNT(*) AS total FROM products WHERE nama_produk LIKE '%$search%'";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalData = $totalRow['total'];

// Menentukan halaman saat ini
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Query untuk mengambil data produk berdasarkan halaman dan pencarian
$query = "SELECT * FROM products WHERE nama_produk LIKE '%$search%' LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Menghitung jumlah halaman yang diperlukan
$totalPages = ceil($totalData / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Produk</title>
    <link rel="stylesheet" href="style/user-dashboard.css"> <!-- Link CSS untuk style tabel -->
</head>
<body>
    <div class="navbar">
        <div class="navbar-links">
            <a href="admin-dashboard.php" class="navbar-link">Admin Dashboard</a>
            <a href="create-user.php" class="navbar-link">Add New User</a>
            <a href="logout.php" class="navbar-link">Logout</a>
        </div>
    </div>
    
    <div class="dashboard-container">
        <h1>Daftar Produk</h1>
        
        <!-- Fitur Pencarian Produk -->
        <form method="get" action="" class="search-form">
            <input type="text" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Cari</button>
        </form>

        <!-- Tabel Data Produk -->
        <div class="dashboard-wrapper">
            <h2>Data Produk</h2>
            <div class="sales-proposal">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Produk</th>
                            <th>Spesifikasi</th>
                            <th>Link Ekatalog</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['nama_produk']; ?></td>
                                    <td><?php echo $row['spesifikasi']; ?></td>
                                    <td><a href="<?php echo $row['link_ekatalog']; ?>" target="_blank">Lihat</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No product data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pilihan "Show" untuk membatasi jumlah data per halaman -->
            <div class="show-option">
                <form method="get" action="" class="limit-form">
                    <label for="limit">Show:</label>
                    <select name="limit" id="limit" onchange="this.form.submit()">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </form>
            </div>

            <!-- Navigasi Halaman -->
    <div class="pagination">
    <!-- Tombol ke halaman pertama dan halaman sebelumnya -->
     <?php if ($page > 1): ?>
        <a href="?page=1&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">First</a>
        <a href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
    <?php endif; ?>

    <!-- Menampilkan halaman 1, 2, 3 berdasarkan kondisi -->
    <?php
    $startPage = max(1, $page - 1); // Mulai dari 1 halaman sebelumnya
    $endPage = min($totalPages, $page + 1); // Sampai 1 halaman berikutnya

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $page) {
            echo "<span class='current-page'>$i</span>"; // Menandai halaman saat ini
        } else {
            echo "<a href='?page=$i&limit=$limit&search=" . urlencode($search) . "'>$i</a>";
        }
    }
    ?>

    <!-- Tombol ke halaman berikutnya dan halaman terakhir -->
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Next</a>
        <a href="?page=<?php echo $totalPages; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Last</a>
    <?php endif; ?>
        </div>
            <!-- Tambahkan tombol "Tambah Product" di sini -->
    <div class="add-product">
    <a href="add-product.php" class="btn-add-product">Tambah Product</a>
        </div>
     </div>
    </div>
</body>
</html>

<?php
// Tutup koneksi database
mysqli_close($conn);
?>
