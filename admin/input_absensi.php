<?php
require '../koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$selected_kelas = $_POST['kelas'] ?? "";
$error = '';
$success = '';
$siswa_list = [];

// Ambil daftar kelas
$kelas_list = [];
$result_kelas = $conn->query("SELECT * FROM kelas");
while ($row = $result_kelas->fetch_assoc()) {
    $kelas_list[] = $row;
}

// Fungsi kirim notifikasi WA
function kirimNotifikasiWA($nomor, $pesan) {
    $instance_id = "instance113723";
    $token = "4sd8ktsua5evscgx";

    $url = "https://api.ultramsg.com/$instance_id/messages/chat";
    $data = [
        "token" => $token,
        "to"    => $nomor,
        "body"  => $pesan
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/x-www-form-urlencoded",
            "method"  => "POST",
            "content" => http_build_query($data),
        ],
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result;
}

// Simpan absensi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_absensi'])) {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $absensiData = $_POST['absensi'] ?? [];

    if (!empty($absensiData)) {
        $rekapPesan = [];

        foreach ($absensiData as $siswa_id => $status) {
            // Ambil data siswa
            $stmt = $conn->prepare("SELECT siswa.nama, kelas.singkatan FROM siswa JOIN kelas ON siswa.kelas_id = kelas.id WHERE siswa.id = ?");
            $stmt->bind_param("i", $siswa_id);
            $stmt->execute();
            $resultSiswa = $stmt->get_result();
            $dataSiswa = $resultSiswa->fetch_assoc();

            $nama_siswa = $dataSiswa['nama'];
            $kelas_singkatan = $dataSiswa['singkatan'];

            // Cek apakah absensi sudah ada
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
                if ($count === 0) {
                    $tipeArray = [1, 2, 3];
                    foreach ($tipeArray as $tipe_id) {
                        $stmt = $conn->prepare("INSERT INTO absensi (siswa_id, tanggal, status, tipe_id) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("issi", $siswa_id, $tanggal, $status, $tipe_id);
                        $stmt->execute();
                    }
                }
            }

            // Tambahkan ke rekap pesan hanya jika status bukan 'hadir'
            if ($status !== "hadir") {
                $rekapPesan[] = "- $nama_siswa ($status)";
            }
        }

        // Kirim notifikasi WA hanya jika ada rekap pesan (status non-hadir)
        if (!empty($rekapPesan)) {
            // Ambil nomor WA wali kelas
            $query = $conn->prepare("
                SELECT users.no_wa, kelas.singkatan 
                FROM users
                JOIN jabatan ON users.jabatan_id = jabatan.id
                JOIN kelas ON kelas.id = ? 
                WHERE jabatan.nama_jabatan = 'Wali Kelas'
            ");
            $query->bind_param("i", $selected_kelas);
            $query->execute();
            $result = $query->get_result();
            $row = $result->fetch_assoc();

            $nomor_wali = $row['no_wa'] ?? '';
            $nama_kelas = $row['singkatan'] ?? '';

            if ($nomor_wali && $nama_kelas) {
                $isiPesan = "Absensi Kelas $nama_kelas - $tanggal:\n" . implode("\n", $rekapPesan);
                kirimNotifikasiWA($nomor_wali, $isiPesan);
            }
        }

        $success = "Data absensi berhasil disimpan.";
    } else {
        $error = "Tidak ada data absensi yang diterima.";
    }

    $selected_kelas = $_POST['kelas'] ?? "";
}

// Ambil daftar siswa sesuai kelas
if ($selected_kelas !== "") {
    $stmt = $conn->prepare("
        SELECT siswa.id, siswa.nama, siswa.nis, kelas.singkatan 
        FROM siswa 
        JOIN kelas ON siswa.kelas_id = kelas.id 
        WHERE kelas.id = ?
    ");
    $stmt->bind_param("i", $selected_kelas);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("
        SELECT siswa.id, siswa.nama, siswa.nis, kelas.singkatan 
        FROM siswa 
        JOIN kelas ON siswa.kelas_id = kelas.id
    ");
}
while ($row = $result->fetch_assoc()) {
    $siswa_list[] = $row;
}
?>

<div class="shadow-lg rounded-lg bg-white p-3 w-[83%] h-auto ml-64 mt-16">
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

