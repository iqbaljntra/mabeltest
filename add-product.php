<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db_connection.php'; // Koneksi ke database

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location.href='html.html';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Jika pengguna mengunggah file CSV
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $fileType = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);

        if ($fileType !== 'csv') {
            echo "<script>alert('File harus dalam format CSV!');</script>";
        } elseif ($_FILES['csv_file']['size'] > 500000) { // Maksimal 500 KB
            echo "<script>alert('Ukuran file terlalu besar! Maksimal 500KB');</script>";
        } else {
            $csvFile = fopen($_FILES['csv_file']['tmp_name'], 'r');
            fgetcsv($csvFile); // Skip header row jika ada

            while (($data = fgetcsv($csvFile, 1000, ",")) !== FALSE) {
                // Pastikan jumlah kolom sesuai
                if (count($data) < 3) {
                    echo "<script>alert('Format CSV tidak sesuai');</script>";
                    break;
                }

                $nama_produk = $data[0];
                $spesifikasi = $data[1];
                $link_ekatalog = $data[2];

                $sql = "INSERT INTO products (nama_produk, spesifikasi, link_ekatalog) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    die("Prepare failed: " . htmlspecialchars($conn->error));
                }

                $stmt->bind_param("sss", $nama_produk, $spesifikasi, $link_ekatalog);
                if (!$stmt->execute()) {
                    echo "<script>alert('Error: " . htmlspecialchars($stmt->error) . "');</script>";
                    error_log("SQL Error: " . $stmt->error);
                }
            }
            fclose($csvFile);
            echo "<script>alert('Data dari CSV berhasil diimpor!'); window.location.href='products.php';</script>";
            exit();
        }
    } else {
        // Proses input manual
        $nama_produk = $_POST['nama_produk'];
        $spesifikasi = $_POST['spesifikasi'];
        $link_ekatalog = $_POST['link_ekatalog'];

        $sql = "INSERT INTO products (nama_produk, spesifikasi, link_ekatalog) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Prepare failed: " . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("sss", $nama_produk, $spesifikasi, $link_ekatalog);

        if ($stmt->execute()) {
            header("Location: products.php");
            exit();
        } else {
            echo "<script>alert('Error: " . htmlspecialchars($stmt->error) . "');</script>";
            error_log("SQL Error: " . $stmt->error);
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
    <link rel="stylesheet" href="style/user-dashboard.css">
    <script>
        function toggleRequiredFields() {
            const csvInput = document.getElementById("csv_file");
            const isCsvSelected = csvInput.value !== "";
            document.getElementById("nama_produk").required = !isCsvSelected;
            document.getElementById("spesifikasi").required = !isCsvSelected;
            document.getElementById("link_ekatalog").required = !isCsvSelected;
            document.getElementById("importBtn").disabled = !isCsvSelected;
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="navbar-links">
            <a href="user-dashboard.php" class="navbar-link">Dashboard</a>
            <a href="products.php" class="navbar-link">Daftar Produk</a>
            <a href="logout.php" class="navbar-link">Logout</a>
        </div>
    </div>

    <div class="tambah-produk-wrapper">
        <h2>Tambah Produk</h2>
        <form action="add-product.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="nama_produk">Nama Produk:</label>
                <input type="text" id="nama_produk" name="nama_produk" required>
            </div>
            <div class="input-group">
                <label for="spesifikasi">Spesifikasi:</label>
                <textarea id="spesifikasi" name="spesifikasi" rows="4" required></textarea>
            </div>
            <div class="input-group">
                <label for="link_ekatalog">Link Ekatalog:</label>
                <input type="url" id="link_ekatalog" name="link_ekatalog" required>
            </div>
            <button type="submit">Tambah Produk</button>
            <button type="button" onclick="window.history.back();">Back</button>
            <br><br>
            <h3>Import Produk dari CSV</h3>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" onchange="toggleRequiredFields()">
            <button type="submit" id="importBtn" disabled>Import CSV</button>
        </form>
    </div>
    <br><br>
<h3>Contoh Format CSV</h3>
<pre>
Nama Produk,Spesifikasi,Link Ekatalog
Produk A,Spesifikasi lengkap produk A,https://ekatalog.example.com/produk-a
Produk B,Spesifikasi lengkap produk B,https://ekatalog.example.com/produk-b
Produk C,Spesifikasi lengkap produk C,https://ekatalog.example.com/produk-c
</pre>

</body>
</html>
