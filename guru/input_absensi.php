<?php
require '../koneksi.php';

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

// Filter kelas
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filter_kelas'])) {
    $selected_kelas = $_POST['kelas'] ?? "";
}

// Simpan absensi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_absensi'])) {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    if (isset($_POST['absensi'])) {
        foreach ($_POST['absensi'] as $siswa_id => $status) {
            // Cek berapa kali absensi sudah dilakukan hari ini
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

                    // Bagian notifikasi WhatsApp telah dihapus
                }
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
    $stmt = $conn->prepare("SELECT siswa.id, siswa.nama, siswa.nis, kelas.singkatan 
                            FROM siswa 
                            JOIN kelas ON siswa.kelas_id = kelas.id 
                            WHERE kelas.id = ?");
    $stmt->bind_param("i", $selected_kelas);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT siswa.id, siswa.nama, siswa.nis, kelas.singkatan 
                            FROM siswa 
                            JOIN kelas ON siswa.kelas_id = kelas.id");
}
while ($row = $result->fetch_assoc()) {
    $siswa_list[] = $row;
}
?>

?>

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

