<?php
include 'db_connection.php'; // Koneksi ke database

if (isset($_GET['term'])) {
    $term = $_GET['term'] . '%'; // Menggunakan term untuk pencarian produk

    // Query untuk mendapatkan nama_produk, spesifikasi, dan link_ekatalog
    $stmt = $conn->prepare("SELECT nama_produk, spesifikasi, link_ekatalog FROM products WHERE nama_produk LIKE ?");
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'label' => $row['nama_produk'], // Label untuk autocomplete
            'value' => $row['nama_produk'], // Nilai yang diisi ke input
            'spesifikasi' => $row['spesifikasi'], // Spesifikasi yang terkait
            'link_ekatalog' => $row['link_ekatalog'] // Link ke ekatalog
        ];
    }

    echo json_encode($products); // Mengembalikan hasil sebagai JSON
    $stmt->close();
    $conn->close();
    echo "File get_products.php berhasil diakses."; 
    exit; 
    
}
?>
