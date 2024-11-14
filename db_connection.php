<?php
$servername = "localhost";  // Server database (biasanya 'localhost')
$username = "root";         // Username MySQL (default 'root')
$password = "";             // Password MySQL (kosong jika default)
$dbname = "mabel_crm";      // Nama database

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
