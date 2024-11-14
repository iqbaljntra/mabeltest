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

    // Fungsi untuk mengambil produk
    function getProducts($conn, $term) {
        $term = $term . '%'; // Menggunakan term untuk pencarian produk
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

        return json_encode($products); // Mengembalikan hasil sebagai JSON
    }

    // Menangani permintaan untuk autocomplete produk
    if (isset($_GET['term'])) {
        echo getProducts($conn, $_GET['term']);
        exit; // Keluar setelah mengembalikan hasil
    }

    // Menangani pengiriman form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nama_dinas_user = $_POST['nama_dinas_user'];
        $status = "Pending";

        // Generate code_usulan unik
    do {
        $code_usulan = 'USL' . strtoupper(uniqid());
        $checkQuery = "SELECT code_usulan FROM usulan_sales WHERE code_usulan = ?";
        $stmtCheck = $conn->prepare($checkQuery);
        $stmtCheck->bind_param("s", $code_usulan);
        $stmtCheck->execute();
        $stmtCheck->store_result();
    } while ($stmtCheck->num_rows > 0);
    $stmtCheck->close();

        // Loop untuk setiap item yang diusulkan
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
                $gambar = ''; // Set gambar ke string kosong jika tidak diupload
            }

            // Menyimpan gambar dan memasukkan data ke database
            if ($uploadOk == 1 && (empty($_FILES["gambar"]["tmp_name"][$i]) || move_uploaded_file($_FILES["gambar"]["tmp_name"][$i], $target_file))) {
                $total = $vol * $harga_satuan;
                $sql = "INSERT INTO usulan_sales (user_id, request_produk, spesifikasi, vol, harga_satuan, gambar, referensi_ekatalog, referensi_tokped, nama_dinas_user, status, code_usulan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt === false) {
                    die("Prepare failed: " . htmlspecialchars($conn->error));
                }

                $stmt->bind_param("ississsssss", $user_id, $request_produk, $spesifikasi, $vol, $harga_satuan, $gambar, $referensi_ekatalog, $referensi_tokped, $nama_dinas_user, $status, $code_usulan);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "<script>alert('Maaf, terjadi kesalahan saat mengupload gambar.');</script>";
            }
        }

        echo "<script>alert('Usulan berhasil disubmit!'); window.location.href='user-dashboard.php';</script>";
    }

    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Submit Usulan Sales</title>
        <link rel="stylesheet" href="style/user-dashboard.css">
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
                                url: "usulan-sales.php", // Ubah menjadi nama file ini
                                dataType: "json",
                                data: { term: request.term },
                                success: function (data) {
                                    response(data);
                                }
                            });
                        },
                        minLength: 1,
                        select: function (event, ui) {
                            // Mengisi otomatis kolom spesifikasi dan link ekatalog
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
                    <textarea name="spesifikasi[]" rows="4" readonly></textarea>

                    <label>Volume:</label>
                    <input type="number" name="vol[]" required>

                    <label>Harga Satuan (pagu):</label>
                    <input type="number" name="harga_satuan[]" required>

                    <label>Gambar:</label>
                    <input type="file" name="gambar[]" accept="image/*">
                    <p>
                    </p>
                    <label>Referensi Ekatalog:</label>
                    <input type="text" name="referensi_ekatalog[]" readonly>

                    <label>Referensi Tokopedia:</label>
                    <input type="text" name="referensi_tokped[]">
                `;
                itemsContainer.appendChild(newItem);
            }
        </script>
    </head>
    <body>
        <div class="navbar">
            <div class="navbar-links">
                <a href="user-dashboard.php" class="navbar-link">Dashboard</a>
                <a href="usulan-sales.php" class="navbar-link">Usulan Sales</a>
                <a href="logout.php" class="navbar-link">Logout</a>
            </div>
        </div>
        <div class="usulan-wrapper">
            <div class="company-header">
                <img src="image/7b0364d4-8f43-4c9c-8c0e-799ca609430f.png" alt="Company Logo" class="company-logo">
                <h1 class="company-name">Mabel Solusi Mandiri</h1>
            </div>

            <div class="usulan-container">
                <h2>Submit Usulan Sales</h2>
                <form action="usulan-sales.php" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label for="nama_dinas_user">Nama Dinas:</label>
                        <input type="text" name="nama_dinas_user" id="nama_dinas_user" required>
                    </div>

                    <div id="items-container">
                        <div class="item">
                            <h3>Item 1</h3>
                            <label>Request Produk:</label>
                            <input type="text" name="request_produk[]" required>

                            <label>Spesifikasi:</label>
                            <textarea name="spesifikasi[]" rows="4" readonly></textarea>

                            <label>Volume:</label>
                            <input type="number" name="vol[]" required>

                            <label>Harga Satuan (pagu):</label>
                            <input type="number" name="harga_satuan[]" required>

                            <label>Gambar:</label>
                            <input type="file" name="gambar[]" accept="image/*">
                            <p></p>
                            <label>Referensi Ekatalog:</link>
                            <input type="text" name="referensi_ekatalog[]" readonly>
                    
                            <label>Referensi Tokopedia:</link>
                            <input type="text" name="referensi_tokped[]">
                        </div>
                    </div>

                    <button type="button" onclick="addItem()">Tambah Item</button>
                    <button type="submit">Submit Usulan</button>
                </form>
            </div>
        </div>
    </body>
    </html>
