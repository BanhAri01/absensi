<?php
require '../koneksi.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($conn === false) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil data kelas
$stmt_kelas = $conn->query("SELECT * FROM kelas");
if ($stmt_kelas === false) {
    die("Error saat mengambil data kelas: " . $conn->error);
}
$kelas_list = $stmt_kelas->fetch_all(MYSQLI_ASSOC);

$selected_kelas = isset($_POST['kelas']) ? $_POST['kelas'] : '';
$selected_tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');

$sql = "SELECT siswa.id, siswa.nis, siswa.nama, kelas.singkatan,
  (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 1 LIMIT 1) AS masuk,
  (SELECT jam FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 1 LIMIT 1) AS waktu_masuk,
  (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 2 LIMIT 1) AS istirahat,
  (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 3 LIMIT 1) AS pulang,
  (SELECT jam FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 3 LIMIT 1) AS waktu_pulang
FROM siswa
LEFT JOIN kelas ON siswa.kelas_id = kelas.id
WHERE 1";

$params = [$selected_tanggal, $selected_tanggal, $selected_tanggal, $selected_tanggal, $selected_tanggal];
$types = "sssss";

if ($selected_kelas !== '') {
    $sql .= " AND siswa.kelas_id = ?";
    $params[] = $selected_kelas;
    $types .= "i";
}
$sql .= " ORDER BY kelas.singkatan, siswa.nama ASC";

// Sekarang types dan params selalu cocok
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params); // ini tidak akan error sekarang

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
    'izin' => 0,
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
                        <td class="border border-gray-300 px-4 py-2">
                            <?php
                            if ($row['masuk'] && $row['waktu_masuk']) {
                                $formattedTime = date("H.i", strtotime($row['waktu_masuk']));
                                if ($row['masuk'] === 'terlambat') {
                                    echo "Terlambat-" . $formattedTime;
                                } else {
                                    echo "Hadir-" . $formattedTime;
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="border border-gray-300 px-4 py-2">
                            <?php
                            if ($row['pulang'] && $row['waktu_pulang']) {
                                $formattedTime = date("H.i", strtotime($row['waktu_pulang']));
                                echo "Pulang-" . $formattedTime;
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-4">Tidak ada data absensi untuk filter yang dipilih.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <button type="button" onclick="showRekapModal()" class="bg-green-500 text-white px-4 py-2 mt-2 rounded-md hover:bg-green-600">
        Export Excel
    </button>

    <div id="rekapModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white w-[90%] max-w-md p-6 rounded-xl shadow-xl transition-all duration-300">
            <h2 class="text-xl font-semibold text-[#273092] text-center mb-6">Pilih Tipe Rekap</h2>
            <form id="rekapForm" action="export_excel.php" method="post">
                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($selected_tanggal) ?>">
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($selected_kelas) ?>">
                <input type="hidden" name="bulan" value="<?= date('n', strtotime($selected_tanggal)) ?>">
                <input type="hidden" name="tahun" value="<?= date('Y', strtotime($selected_tanggal)) ?>">
                <input type="hidden" name="tipe_rekap" id="tipeRekap">

                <div class="flex flex-col gap-3">
                    <button type="button" onclick="submitRekap('harian')" class="w-full bg-[#273092] hover:bg-[#1e256b] text-white font-medium py-2 rounded-md transition duration-200">
                        Rekap Harian
                    </button>
                    <button type="button" onclick="submitRekap('bulanan')" class="w-full bg-[#273092] hover:bg-[#1e256b] text-white font-medium py-2 rounded-md transition duration-200">
                        Rekap Bulanan
                    </button>
                    <button type="button" onclick="closeRekapModal()" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 rounded-md transition duration-200">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>

    <script>
        function showRekapModal() {
            document.getElementById('rekapModal').classList.remove('hidden');
        }

        function closeRekapModal() {
            document.getElementById('rekapModal').classList.add('hidden');
        }

        function submitRekap(tipe) {
            Swal.fire({
                title: 'Konfirmasi',
                text: `Apakah kamu yakin ingin mengunduh rekap ${tipe}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#273092',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('tipeRekap').value = tipe;
                    closeRekapModal();
                    setTimeout(() => {
                        document.getElementById('rekapForm').submit();
                    }, 100);
                }
            });
        }
    </script>
</div>
