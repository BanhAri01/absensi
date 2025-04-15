<?php
require '../koneksi.php';
require '../vendor/autoload.php'; // Pastikan Twilio SDK sudah terinstal

use Twilio\Rest\Client;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$selected_kelas = "";
$error = '';
$success = '';
$siswa_list = [];

// Ambil daftar kelas
$kelas_list = [];
$result_kelas = $conn->query("SELECT * FROM kelas");
while ($row = $result_kelas->fetch_assoc()) {
    $kelas_list[] = $row;
}

// Proses filter kelas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_kelas'])) {
    $selected_kelas = isset($_POST['kelas']) ? $_POST['kelas'] : "";
}

// Proses simpan absensi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_absensi'])) {
    $tanggal = !empty($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');

    if (isset($_POST['absensi'])) {
        foreach ($_POST['absensi'] as $siswa_id => $status) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM absensi WHERE siswa_id = ? AND tanggal = ?");
            $countStmt->bind_param("is", $siswa_id, $tanggal);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $row = $countResult->fetch_assoc();
            $count = intval($row['count']);

            if ($status === "hadir") {
                if ($count < 3) {
                    $tipe_id = $count + 1; 
                    $stmt = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, status, tipe_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("issi", $siswa_id, $tanggal, $status, $tipe_id);
                    $stmt->execute();
                } 
            } else {
                if ($count == 0) {
                    $tipeArray = [1, 2, 3];
                    foreach ($tipeArray as $tipe_id) {
                        $stmt = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, status, tipe_id) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("issi", $siswa_id, $tanggal, $status, $tipe_id);
                        $stmt->execute();
                    }

                    // Kirim notifikasi hanya jika statusnya bukan hadir
                    kirimNotifikasiWhatsAppKeWaliKelas($siswa_id, $status);
                }
            }
        }
        $success = "Data absensi berhasil disimpan.";
    } else {
        $error = "Tidak ada data absensi yang diterima.";
    }

    $selected_kelas = $_POST['kelas'] ?? "";
}

// Ambil data siswa berdasarkan kelas yang dipilih
if (!empty($selected_kelas)) {
    $stmt = $conn->prepare("SELECT siswa.id, siswa.nama, siswa.nis, kelas.singkatan 
                            FROM siswa 
                            JOIN kelas ON siswa.kelas_id = kelas.id 
                            WHERE kelas.id = ?");
    $stmt->bind_param("i", $selected_kelas);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $siswa_list[] = $row;
    }
}

// Fungsi kirim WhatsApp
function kirimNotifikasiWhatsAppKeWaliKelas($siswa_id, $status) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT siswa.nama, siswa.nis, kelas.singkatan, siswa.kelas_id
                            FROM siswa 
                            LEFT JOIN kelas ON siswa.kelas_id = kelas.id
                            WHERE siswa.id = ?");
    $stmt->bind_param("i", $siswa_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $siswa = $result->fetch_assoc();
    
    if (!$siswa) {
        return;
    }

    $kelas_id = $siswa['kelas_id'];

    $stmtGuru = $conn->prepare("SELECT users.no_wa 
                                FROM users 
                                JOIN jabatan ON users.jabatan_id = jabatan.id 
                                WHERE jabatan.nama_jabatan = 'Wali Kelas' AND users.kelas_id = ?");
    $stmtGuru->bind_param("i", $kelas_id);
    $stmtGuru->execute();
    $resultGuru = $stmtGuru->get_result();
    $guru = $resultGuru->fetch_assoc();

    if (!$guru) {
        return;
    }

    $nomor_wa_guru = $guru['no_wa'];
    $pesan = "Notifikasi Absensi:\nSiswa: " . $siswa['nama'] . "\nNIS: " . $siswa['nis'] . "\nKelas: " . $siswa['singkatan'] . "\nStatus: " . ucfirst($status);

    // Twilio credentials
    $sid = 'AC672f343467b3acbfac2e45cdb9694d8c';
    $token = '893fe7d8ad52c450e9163db56d43c078'; // Ganti dengan token Twilio kamu
    $from = 'whatsapp:+14155238886';
    $to = 'whatsapp:' . $nomor_wa_guru;

    $client = new Client($sid, $token);
    $client->messages->create($to, [
        'from' => $from,
        'body' => $pesan
    ]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Absensi Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="shadow-lg rounded-lg bg-white p-3 w-[83%] h-auto ml-64 mt-16">
    <h2 class="text-center text-xl font-bold mb-4 bg-[#001F3F] text-white p-3 rounded-md">
      Input Absensi Siswa
    </h2>
    
    <!-- Form filter kelas -->
    <div class="mb-4">
        <form action="" method="post" class="flex items-end space-x-4">
            <div>
                <label class="block font-medium">Pilih Kelas:</label>
                <select name="kelas" class="p-2 border rounded-md">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelas_list as $kelas): ?>
                        <option value="<?= htmlspecialchars($kelas['id']) ?>" 
                          <?= ($selected_kelas == $kelas['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kelas['singkatan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" name="filter_kelas" 
                        class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                  Filter
                </button>
            </div>
        </form>
    </div>
    
    <?php 
    if ($error) echo "<p class='text-red-500 text-center'>$error</p>"; 
    if ($success) echo "<p class='text-green-500 text-center'>$success</p>"; 
    ?>
    
    <!-- Form input absensi -->
    <form action="" method="post">
        <input type="hidden" name="kelas" value="<?= htmlspecialchars($selected_kelas) ?>">
        <div class="mb-4">
            <label class="block font-medium">Tanggal Absensi:</label>
            <input type="date" name="tanggal" class="w-full p-2 border rounded-md" 
                   value="<?= date('Y-m-d'); ?>" required>
        </div>
        <table class="min-w-full border-collapse border border-gray-200">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border border-gray-300 px-4 py-2">NIS</th>
                    <th class="border border-gray-300 px-4 py-2">Nama</th>
                    <th class="border border-gray-300 px-4 py-2">Kelas</th>
                    <th class="border border-gray-300 px-4 py-2">Status Absensi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($siswa_list) > 0): ?>
                    <?php foreach ($siswa_list as $siswa): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($siswa['nis']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($siswa['nama']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($siswa['singkatan']); ?></td>
                        <td class="border border-gray-300 px-4 py-2">
                            <label><input type="radio" name="absensi[<?= $siswa['id'] ?>]" value="hadir" required> Hadir</label>
                            <label class="ml-2"><input type="radio" name="absensi[<?= $siswa['id'] ?>]" value="izin"> Izin</label>
                            <label class="ml-2"><input type="radio" name="absensi[<?= $siswa['id'] ?>]" value="sakit"> Sakit</label>
                            <label class="ml-2"><input type="radio" name="absensi[<?= $siswa['id'] ?>]" value="alpha"> Alpha</label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">Tidak ada siswa untuk kelas yang dipilih.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="flex justify-center mt-4">
            <button type="submit" name="save_absensi" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
              Simpan Absensi
            </button>
        </div>
    </form>
</div>
</body>
</html>
