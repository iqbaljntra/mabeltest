<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db_connection.php'; // Koneksi ke database

// Pastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); // Redirect ke halaman login jika bukan admin
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $nama_lengkap = $_POST['nama_lengkap']; // Ambil nama lengkap dari form

    // Cek apakah email sudah terdaftar
    $sql_check_email = "SELECT * FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Jika email sudah terdaftar, tampilkan pesan error
        echo "<script>alert('Email sudah terdaftar!');</script>";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Query untuk menyimpan pengguna baru ke database
        $sql = "INSERT INTO users (username, email, password, role, nama_lengkap) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $nama_lengkap); 

        if ($stmt->execute()) {
            // Jika berhasil, redirect ke admin-dashboard.php
            echo "<script>alert('Akun berhasil dibuat!'); window.location.href='admin-dashboard.php';</script>";
            exit();
        } else {
            // Tampilkan pesan error jika gagal
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }

        $stmt->close();
    }

    $stmt_check->close(); // Tutup statement pengecekan email
}

$conn->close(); // Tutup koneksi database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link rel="stylesheet" href="style/create-user.css"> <!-- Gaya CSS -->
    
</head>
<body>
    <div class="login-wrapper">
        <div class="company-header">
            <img src="image/7b0364d4-8f43-4c9c-8c0e-799ca609430f.png" alt="Company Logo" class="company-logo">
            <h1 class="company-name">Mabel Solusi Mandiri</h1>
        </div>
        
        <div class="login-container">
            <h2>Create New User</h2>
            <form action="create-user.php" method="POST">
                <div class="input-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-group">
                    <label for="nama_lengkap">Nama Lengkap:</label> <!-- Input untuk nama lengkap -->
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                <div class="input-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit">Create User</button>
                <button type="button" onclick="window.history.back();">Back</button> <!-- Tombol Back -->
            </form>
        </div>
    </div>
</body>
</html>
