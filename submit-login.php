    <?php
    session_start(); // Memulai sesi
    include 'db_connection.php'; // Mengimpor file koneksi database

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Cek apakah username ada di database
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Ambil data pengguna dari database
            $user = $result->fetch_assoc();

            // Verifikasi password yang diinput dengan hash yang ada di database
            if (password_verify($password, $user['password'])) {
                // Set session jika login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect ke dashboard sesuai role
                if ($user['role'] == 'admin') {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: user-dashboard.php");
                }
                exit();
            } else {
                echo "Password salah!<br>";
            }
        } else {
            echo "Username tidak ditemukan!";
        }
    }
    $conn->close(); // Menutup koneksi database
    ?>
