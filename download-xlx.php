<?php
session_start();
include 'db_connection.php'; // Pastikan Anda menghubungkan ke database

require 'vendor/autoload.php'; // Menyertakan autoloader Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Ambil data usulan berdasarkan ID
    $sql = "SELECT * FROM usulan_sales WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $nama_dinas_user = $data['nama_dinas_user'];

        // Ambil semua usulan sales untuk nama dinas yang sama
        $sql_group = "SELECT * FROM usulan_sales WHERE nama_dinas_user = ? ORDER BY created_at DESC";
        $stmt_group = $conn->prepare($sql_group);
        $stmt_group->bind_param("s", $nama_dinas_user);
        $stmt_group->execute();
        $result_group = $stmt_group->get_result();

        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Tulis informasi di luar tabel ke file Excel
        $sheet->setCellValue('A1', 'Usulan:');
        $sheet->setCellValue('B1', $nama_dinas_user);
        $sheet->setCellValue('A2', 'Nama:');
        $sheet->setCellValue('B2', $_SESSION['username']);
        $sheet->setCellValue('A3', 'Tanggal:');
        $sheet->setCellValue('B3', date("d-m-Y"));

        // Baris kosong untuk memberikan jarak
        $sheet->setCellValue('A5', ''); 

        // Tulis header kolom untuk data usulan
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Request Produk');
        $sheet->setCellValue('C6', 'Spesifikasi');
        $sheet->setCellValue('D6', 'Vol');
        $sheet->setCellValue('E6', 'Harga Satuan');
        $sheet->setCellValue('F6', 'Total');
        $sheet->setCellValue('G6', 'Referensi Ekatalog');
        $sheet->setCellValue('H6', 'Referensi Tokped');
        $sheet->setCellValue('I6', 'Status');

        // Inisialisasi nomor urut
        $no = 1;
        $row = 7; // Mulai dari baris 7 untuk data

        // Tulis data grup ke file Excel
        while ($group_row = $result_group->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $group_row['request_produk']);
            $sheet->setCellValue('C' . $row, $group_row['spesifikasi']);
            $sheet->setCellValue('D' . $row, $group_row['vol']);
            $sheet->setCellValue('E' . $row, $group_row['harga_satuan']);
            $sheet->setCellValue('F' . $row, $group_row['total']);
            $sheet->setCellValue('G' . $row, $group_row['referensi_ekatalog']);
            $sheet->setCellValue('H' . $row, $group_row['referensi_tokped']);
            $sheet->setCellValue('I' . $row, $group_row['status']);
            $row++; // Pindah ke baris berikutnya
        }

        // Set header untuk mengunduh file Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="usulan_sales_' . str_replace(' ', '_', $nama_dinas_user) . '_' . date("Ymd") . '.xlsx"');

        // Buat writer dan simpan file
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();
    }
}
?>
