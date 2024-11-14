<?php
session_start();
include 'db_connection.php';

// Ambil user_id dan username dari sesi
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil user_id yang dipilih jika ada, atau gunakan user_id sesi
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $user_id;

// Ambil limit dari request GET, jika tidak ada set ke 10
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Jika limit adalah -1, tampilkan semua data
$sql_limit = ($limit == -1) ? "" : "LIMIT ?";
$sql = "SELECT * FROM usulan_sales WHERE user_id = ? ORDER BY created_at DESC $sql_limit";
$stmt = $conn->prepare($sql);
if ($limit != -1) {
    $stmt->bind_param("ii", $selected_user_id, $limit);
} else {
    $stmt->bind_param("i", $selected_user_id);
}
$stmt->execute();
$result = $stmt->get_result();

$usulan_sales = [];
while ($row = $result->fetch_assoc()) {
    $usulan_sales[] = $row;
}

// Ambil daftar user untuk dropdown (Admin dapat memilih user lain)
$sql_users = "SELECT id, username FROM users ORDER BY username";
$result_users = $conn->query($sql_users);
$users = [];
while ($row = $result_users->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style/user-dashboard.css">
    <style>
        .icon { width: 24px; height: 24px; cursor: pointer; margin: 0 5px; vertical-align: middle; }
        .group-header { font-size: 18px; font-weight: bold; background-color: #e0e0e0; padding: 10px; margin-top: 20px; }
        .data-limit { margin-top: 20px; }
        form select {
            padding: 5px;
            margin-left: 10px;
            font-size: 14px;
            background-color: #f2f2f2;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        form select:focus {
            border-color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-links">
            <a href="admin-dashboard.php" class="navbar-link">Dashboard Admin</a>
            <a href="logout.php" class="navbar-link">Logout</a>
        </div>
    </div>

    <!-- Dashboard Konten -->
    <div class="dashboard-wrapper">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <p style="color: white; font-weight: bold;">This is your admin dashboard.</p>

        <!-- Filter User (Admin Only) -->
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <form method="GET" action="">
                <label for="userSelect">Select User:</label>
                <select name="user_id" id="userSelect" onchange="this.form.submit()">
                    <option value="">-- Select User --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo ($selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <!-- Tabel Usulan Sales -->
        <div class="sales-proposal">
            <h2>Data Usulan Sales</h2>
            <?php if (count($usulan_sales) > 0): ?>
                <?php 
                $current_group = ""; 
                $no = 1;
                foreach ($usulan_sales as $row): 
                    // Gunakan code_usulan untuk membuat grup
                    $group_key = $row['code_usulan'];
                    if ($current_group !== $group_key): 
                        if ($current_group !== "") {
                            echo "</tbody></table></div>"; // Tutup div table-responsive dan tabel sebelumnya
                        }
                        $current_group = $group_key; 
                ?>
                        <div class="group-header">
                            <?php echo htmlspecialchars($row['nama_dinas_user']); ?> - <?php echo date("d M Y", strtotime($row['created_at'])); ?>
                            <!-- Tombol Download dan Edit hanya sekali per grup -->
                            <a href="download-xlx.php?code_usulan=<?php echo $row['code_usulan']; ?>" class="download-csv-button">Download</a>
                            <a href="edit-usulan-admin.php?code_usulan=<?php echo $row['code_usulan']; ?>" class="download-csv-button">Edit</a>
                            
                            <!-- Pilihan Status -->
                            <form action="update-status.php" method="POST" style="display:inline;">
                                <input type="hidden" name="code_usulan" value="<?php echo htmlspecialchars($row['code_usulan']); ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="On Going" <?php echo $row['status'] == 'On Going' ? 'selected' : ''; ?>>On Going</option>
                                    <option value="Canceled" <?php echo $row['status'] == 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
                                    <option value="Failed" <?php echo $row['status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="Success" <?php echo $row['status'] == 'Success' ? 'selected' : ''; ?>>Success</option>
                                </select>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Request Produk</th>
                                        <th>Spesifikasi</th>
                                        <th>Vol</th>
                                        <th>Harga Satuan (pagu)</th>
                                        <th>Total (pagu)</th>
                                        <th>Referensi Ekatalog</th>
                                        <th>Referensi Tokped</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                    <?php endif; ?>

                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['request_produk']); ?></td>
                        <td><?php echo htmlspecialchars($row['spesifikasi']); ?></td>
                        <td><?php echo htmlspecialchars($row['vol']); ?></td>
                        <td>Rp <?php echo number_format($row['harga_satuan'], 2, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($row['total'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($row['referensi_ekatalog']); ?></td>
                        <td><?php echo htmlspecialchars($row['referensi_tokped']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <img src="image/image-gallery.png" alt="Lihat Gambar" class="icon" onclick="openImageModal('<?php echo htmlspecialchars($row['gambar']); ?>')">
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>
                </table>
                </div> 
            <?php else: ?>
                <p>Tidak ada usulan sales untuk ditampilkan.</p>
            <?php endif; ?>
            <div class="data-limit">
                <label for="dataLimit">Show:</label>
                <select id="dataLimit" onchange="changeLimit()">
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                    <option value="-1" <?php echo $limit == -1 ? 'selected' : ''; ?>>Show All</option>
                </select>
            </div>
        </div>
    </div>
    <script>
        function changeLimit() {
            const limit = document.getElementById('dataLimit').value;
            window.location.href = '?limit=' + limit;
        }
    </script>
</body>
</html>
