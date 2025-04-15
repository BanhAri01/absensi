<?php
require '../koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($conn === false) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}


$stmt_kelas = $conn->query("SELECT * FROM kelas");
if ($stmt_kelas === false) {
    die("Error saat mengambil data kelas: " . $conn->error);
}
$kelas_list = $stmt_kelas->fetch_all(MYSQLI_ASSOC);

$selected_kelas = isset($_POST['kelas']) ? $_POST['kelas'] : '';
$selected_tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');


$sql = "SELECT siswa.id, siswa.nis, siswa.nama, kelas.singkatan,
  (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 1 LIMIT 1) AS masuk,
  (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 2 LIMIT 1) AS istirahat,
  (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 3 LIMIT 1) AS pulang
FROM siswa
LEFT JOIN kelas ON siswa.kelas_id = kelas.id
WHERE 1";
$params = [$selected_tanggal, $selected_tanggal, $selected_tanggal];
$types = "sss";
if ($selected_kelas !== '') {
    $sql .= " AND siswa.kelas_id = ?";
    $params[] = $selected_kelas;
    $types .= "i";
}
$sql .= " ORDER BY kelas.singkatan, siswa.nama ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


$queryCard = "SELECT status, COUNT(*) as total FROM absensi WHERE tipe_id = 1 AND tanggal = ?";
$paramsCard = [$selected_tanggal];
$typesCard = "s";
if ($selected_kelas !== '') {
    $queryCard .= " AND siswa_id IN (SELECT id FROM siswa WHERE kelas_id = ?)";
    $paramsCard[] = $selected_kelas;
    $typesCard .= "i";
}
$queryCard .= " GROUP BY status";

$stmtCard = $conn->prepare($queryCard);
$stmtCard->bind_param($typesCard, ...$paramsCard);
$stmtCard->execute();
$resultCard = $stmtCard->get_result();


$cardData = [
    'izin'  => 0,
    'sakit' => 0,
    'alpha' => 0
];
while ($row = $resultCard->fetch_assoc()) {
    $status = strtolower($row['status']);
    if (isset($cardData[$status])) {
        $cardData[$status] = $row['total'];
    }
}
?>
<div class="shadow-lg rounded-lg bg-white p-3 w-[83%] h-auto ml-64 mt-20">
    <h2 class="text-center text-2xl font-bold mb-4">Rekap Absensi Siswa - Tanggal <?= htmlspecialchars($selected_tanggal) ?></h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-100 p-4 rounded-lg shadow-md h-32 flex flex-col justify-center">
            <h3 class="text-xl font-bold text-green-800 text-center">Izin</h3>
            <p class="text-3xl font-semibold text-green-900 text-center"><?= htmlspecialchars($cardData['izin']) ?></p>
            <p class="text-sm text-green-700 text-center">Siswa Izin</p>
        </div>
        <div class="bg-yellow-100 p-4 rounded-lg shadow-md h-32 flex flex-col justify-center">
            <h3 class="text-xl font-bold text-yellow-800 text-center">Sakit</h3>
            <p class="text-3xl font-semibold text-yellow-900 text-center"><?= htmlspecialchars($cardData['sakit']) ?></p>
            <p class="text-sm text-yellow-700 text-center">Siswa Sakit</p>
        </div>
        <div class="bg-red-100 p-4 rounded-lg shadow-md h-32 flex flex-col justify-center">
            <h3 class="text-xl font-bold text-red-800 text-center">Alpha</h3>
            <p class="text-3xl font-semibold text-red-900 text-center"><?= htmlspecialchars($cardData['alpha']) ?></p>
            <p class="text-sm text-red-700 text-center">Siswa Alpha</p>
        </div>
    </div>
    

    <form method="post" action="" class="mb-6 flex flex-wrap items-end space-x-4">
        <div class="mb-2">
            <label class="block font-medium">Pilih Kelas:</label>
            <select name="kelas" class="p-2 border rounded-md">
                <option value="">Semua Kelas</option>
                <?php foreach ($kelas_list as $kelas): ?>
                    <option value="<?= htmlspecialchars($kelas['id']) ?>" <?= ($selected_kelas == $kelas['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kelas['singkatan']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-2">
            <label class="block font-medium">Pilih Tanggal:</label>
            <input type="date" name="tanggal" class="p-2 border rounded-md" value="<?= htmlspecialchars($selected_tanggal) ?>">
        </div>
        <div class="mb-2">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Filter</button>
        </div>
    </form>
  

    <table class="min-w-full border-collapse border border-gray-200">
        <thead>
            <tr class="bg-gray-200">
                <th class="border border-gray-300 px-4 py-2">NIS</th>
                <th class="border border-gray-300 px-4 py-2">Nama</th>
                <th class="border border-gray-300 px-4 py-2">Kelas</th>
                <th class="border border-gray-300 px-4 py-2">Masuk</th>
                <th class="border border-gray-300 px-4 py-2">Istirahat</th>
                <th class="border border-gray-300 px-4 py-2">Pulang</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($row['nis']) ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($row['nama']) ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($row['singkatan']) ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= $row['masuk'] ? htmlspecialchars($row['masuk']) : '-' ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= $row['istirahat'] ? htmlspecialchars($row['istirahat']) : '-' ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?= $row['pulang'] ? htmlspecialchars($row['pulang']) : '-' ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-4">Tidak ada data absensi untuk filter yang dipilih.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
