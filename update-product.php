<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nama = $_POST['nama_produk'];
    $spesifikasi = $_POST['spesifikasi'];
    $link = $_POST['link_ekatalog'];

    // Menyiapkan query
    $query = "UPDATE products SET nama_produk = ?, spesifikasi = ?, link_ekatalog = ? WHERE id_produk = ?";
    
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'sssi', $nama, $spesifikasi, $link, $id);
        $execute = mysqli_stmt_execute($stmt);

        if ($execute) {
            echo "Success"; // Jika update berhasil
        } else {
            echo "Failed: " . mysqli_error($conn); // Jika gagal, tampilkan pesan error MySQL
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Query preparation failed: " . mysqli_error($conn); // Menangkap error jika query gagal disiapkan
    }
} else {
    echo "Invalid request method."; // Jika bukan POST
}

mysqli_close($conn);
?>
