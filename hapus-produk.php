<?php
include 'db_connection.php';

if (isset($_POST['id'])) {
    $id_produk = $_POST['id'];

    // Hapus produk dari database
    $sql = "DELETE FROM usulan_sales WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_produk);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
}

$conn->close();
?>
