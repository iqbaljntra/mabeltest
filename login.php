<?php
session_start(); // Memulai sesi
include 'db_connection.php'; // Koneksi ke database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query untuk mendapatkan pengguna berdasarkan username
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Ambil data pengguna dari database
        $user = $result->fetch_assoc();
        
        // Cek password yang di-input dengan password yang ada di database (hashed)
        if (password_verify($password, $user['password'])) {
            // Password cocok, buat session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect ke dashboard sesuai role
            echo "Welcome, " . $_SESSION['username']; // Pesan selamat datang
            exit();
        } else {
            echo "Password salah!";
        }
    } else {
        echo "Username tidak ditemukan!";
    }
}

$conn->close(); // Tutup koneksi database
?>
