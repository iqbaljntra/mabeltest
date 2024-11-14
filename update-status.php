<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();
include 'db_connection.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data dari form
$code_usulan = $_POST['code_usulan'];
$status = $_POST['status'];

// Update status di
