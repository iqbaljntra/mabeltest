<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include 'db_connection.php'; // Koneksi ke database

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location.href='html.html';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fungsi untuk mengambil produk berdasarkan pencarian
function getProducts($conn, $term) {
    $term = $term . '%';
    $stmt = $conn->prepare("SELECT nama_produk, spesifikasi, link_ekatalog FROM products WHERE nama_produk LIKE ?");
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'label' => $row['nama_produk'],
            'value' => $row['nama_produk'],
            'spesifikasi' => $row['spesifikasi'],
            'link_ekatalog' => $row['link_ekatalog']
        ];
    }

    return json_encode($products);
}

// Menangani permintaan untuk autocomplete produk
if (isset($_GET['term'])) {
    echo getProducts($conn, $_GET['term']);
    exit;
}

// Menangani pengeditan usulan
if (isset($_GET['code_usulan'])) {
    $code_usulan = $_GET['code_usulan'];
    
    // Mengambil data usulan berdasarkan code_usulan
    $sql = "SELECT * FROM usulan_sales WHERE code_usulan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code_usulan);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usulan = $result->fetch_assoc();
        $nama_dinas_user = $usulan['nama_dinas_user'];
        
        // Mengambil produk yang terkait dengan usulan
        $sql_produk = "SELECT * FROM usulan_sales WHERE code_usulan = ?";
        $stmt_produk = $conn->prepare($sql_produk);
        $stmt_produk->bind_param("s", $code_usulan);
        $stmt_produk->execute();
        $produk_result = $stmt_produk->get_result();
        $produk_data = [];
        
        while ($row = $produk_result->fetch_assoc()) {
            $produk_data[] = $row;
        }
    } else {
        echo "<script>alert('Usulan tidak ditemukan.'); window.location.href='user-dashboard.php';</script>";
        exit();
    }
}

// Menangani pengiriman form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_dinas_user = $_POST['nama_dinas_user'];
    $status = "Pending";
    
    // Update usulan berdasarkan code_usulan
    $sql = "UPDATE usulan_sales SET nama_dinas_user = ?, status = ? WHERE code_usulan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nama_dinas_user, $status, $code_usulan);
    $stmt->execute();

    // Menghapus produk yang dicentang
    if (isset($_POST['delete_produk'])) {
        foreach ($_POST['delete_produk'] as $id_produk) {
            $sql_delete = "DELETE FROM usulan_sales WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $id_produk);
            $stmt_delete->execute();
        }
    }

    // Loop untuk setiap item yang diupdate atau ditambah
    for ($i = 0; $i < count($_POST['request_produk']); $i++) {
        $request_produk = $_POST['request_produk'][$i];
        $spesifikasi = $_POST['spesifikasi'][$i];
        $vol = $_POST['vol'][$i];
        $harga_satuan = $_POST['harga_satuan'][$i];
        $referensi_ekatalog = $_POST['referensi_ekatalog'][$i];
        $referensi_tokped = $_POST['referensi_tokped'][$i];

        $target_dir = "uploads/";
        $gambar = basename($_FILES["gambar"]["name"][$i]);
        $target_file = $target_dir . $gambar;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Memeriksa apakah file adalah gambar
        if (!empty($_FILES["gambar"]["tmp_name"][$i])) {
            $check = getimagesize($_FILES["gambar"]["tmp_name"][$i]);
            if ($check === false) {
                echo "<script>alert('File bukan gambar.');</script>";
                $uploadOk = 0;
            }

            // Memeriksa ukuran file
            if ($_FILES["gambar"]["size"][$i] > 50000000) {
                echo "<script>alert('Maaf, ukuran file terlalu besar.');</script>";
                $uploadOk = 0;
            }

            // Memeriksa format file
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "<script>alert('Hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.');</script>";
                $uploadOk = 0;
            }
        } else {
            $gambar = $produk_data[$i]['gambar'] ?? ''; // Set gambar ke string kosong jika tidak diupload
        }

        // Menyimpan gambar dan memasukkan data ke database
        if ($uploadOk == 1 && (empty($_FILES["gambar"]["tmp_name"][$i]) || move_uploaded_file($_FILES["gambar"]["tmp_name"][$i], $target_file))) {
            if (!empty($produk_data[$i]['id'])) {
                // Update produk yang sudah ada
                $sql_update_produk = "UPDATE usulan_sales SET request_produk = ?, spesifikasi = ?, vol = ?, harga_satuan = ?, gambar = ?, referensi_ekatalog = ?, referensi_tokped = ? WHERE id = ?";
                $stmt_update_produk = $conn->prepare($sql_update_produk);
                $stmt_update_produk->bind_param("ssissssi", $request_produk, $spesifikasi, $vol, $harga_satuan, $gambar, $referensi_ekatalog, $referensi_tokped, $produk_data[$i]['id']);
                $stmt_update_produk->execute();
                $stmt_update_produk->close();
            } else {
                // Tambah produk baru
                $sql_insert_produk = "INSERT INTO usulan_sales (user_id, request_produk, spesifikasi, vol, harga_satuan, gambar, referensi_ekatalog, referensi_tokped, code_usulan, status, nama_dinas_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert_produk = $conn->prepare($sql_insert_produk);
                $stmt_insert_produk->bind_param("ississsssss", $user_id, $request_produk, $spesifikasi, $vol, $harga_satuan, $gambar, $referensi_ekatalog, $referensi_tokped, $code_usulan, $status, $nama_dinas_user);
                $stmt_insert_produk->execute();
                $stmt_insert_produk->close();
            }
        } else {
            echo "<script>alert('Maaf, terjadi kesalahan saat mengupload gambar.');</script>";
        }
    }

    echo "<script>alert('Usulan berhasil diperbarui!'); window.location.href='user-dashboard.php';</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Usulan Sales</title>
    <link rel="stylesheet" href="style/usulan-sales.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function () {
            // Fungsi autocomplete untuk produk
            $(document).on('focus', 'input[name="request_produk[]"]', function () {
                $(this).autocomplete({
                    source: function (request, response) {
                        $.ajax({
                            url: "usulan-sales.php",
                            dataType: "json",
                            data: { term: request.term },
                            success: function (data) {
                                response(data);
                            }
                        });
                    },
                    minLength: 1,
                    select: function (event, ui) {
                        const itemIndex = $(this).closest('.item').index();
                        $(this).closest('.item').find('textarea[name="spesifikasi[]"]').val(ui.item.spesifikasi);
                        $(this).closest('.item').find('input[name="referensi_ekatalog[]"]').val(ui.item.link_ekatalog);
                    }
                });
            });
        });

        let itemCounter = 1;

        function addItem() {
            itemCounter++;
            const itemsContainer = document.getElementById('items-container');

            const newItem = document.createElement('div');
            newItem.classList.add('item');
            newItem.innerHTML = `
                <h3>Item ${itemCounter}</h3>
                <label>Request Produk:</label>
                <input type="text" name="request_produk[]" required>

                <label>Spesifikasi:</label>
                <textarea name="spesifikasi[]"></textarea>

                <label>Volume:</label>
                <input type="number" name="vol[]" required>

                <label>Harga Satuan:</label>
                <input type="number" name="harga_satuan[]" required>

                <label>Referensi Ekatalog:</label>
                <input type="text" name="referensi_ekatalog[]">

                <label>Referensi Tokped:</label>
                <input type="text" name="referensi_tokped[]">

                <label>Gambar:</label>
                <input type="file" name="gambar[]" accept="image/*">

                <input type="checkbox" name="delete_produk[]" value="${itemCounter}" /> Hapus Produk
            `;
            itemsContainer.appendChild(newItem);

            // Menginisialisasi autocomplete pada input produk baru
            $(newItem).find('input[name="request_produk[]"]').autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "usulan-sales.php",
                        dataType: "json",
                        data: { term: request.term },
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                minLength: 1,
                select: function (event, ui) {
                    $(newItem).find('textarea[name="spesifikasi[]"]').val(ui.item.spesifikasi);
                    $(newItem).find('input[name="referensi_ekatalog[]"]').val(ui.item.link_ekatalog);
                }
            });
        }
    </script>
</head>
<body>
<div id="container">
    <h2>Edit Usulan Sales</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label>Nama Dinas:</label>
        <input type="text" name="nama_dinas_user" value="<?= htmlspecialchars($nama_dinas_user) ?>" required>
        
        <div id="items-container">
            <?php foreach ($produk_data as $i => $produk): ?>
                <div class="item">
                    <h3>Item <?= $i + 1 ?></h3>
                    <label>Request Produk:</label>
                    <input type="text" name="request_produk[]" value="<?= htmlspecialchars($produk['request_produk']) ?>" required>

                    <label>Spesifikasi:</label>
                    <textarea name="spesifikasi[]"readonly><?= htmlspecialchars($produk['spesifikasi']) ?></textarea>

                    <label>Volume:</label>
                    <input type="number" name="vol[]" value="<?= htmlspecialchars($produk['vol']) ?>" required>

                    <label>Harga Satuan:</label>
                    <input type="number" name="harga_satuan[]" value="<?= htmlspecialchars($produk['harga_satuan']) ?>" required>

                    <label>Referensi Ekatalog:</label>
                    <input type="text" name="referensi_ekatalog[]" value="<?= htmlspecialchars($produk['referensi_ekatalog']) ?>"readonly>

                    <label>Referensi Tokped:</label>
                    <input type="text" name="referensi_tokped[]" value="<?= htmlspecialchars($produk['referensi_tokped']) ?>">

                    <label>Gambar:</label>
                    <input type="file" name="gambar[]" accept="image/*">
                    <?php if (!empty($produk['gambar'])): ?>
                        <p><img src="uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="gambar produk" width="100"></p>
                    <?php endif; ?>

                    <input type="checkbox" name="delete_produk[]" value="<?= $produk['id'] ?>"> Hapus Produk
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addItem()">Tambah Item</button>
        <button type="submit">Simpan Perubahan</button>
    </form>
</div>
</body>
</html>
