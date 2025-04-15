<?php
require '../koneksi.php';
date_default_timezone_set('Asia/Jakarta');
$hariIni = date('Y-m-d');
$bulanIni = date('Y-m');

// Total siswa
$querySiswa = "SELECT COUNT(*) AS total FROM siswa";
$resultSiswa = $conn->query($querySiswa);
$totalSiswa = $resultSiswa ? $resultSiswa->fetch_assoc()['total'] : 0;

// Total kelas
$queryKelas = "SELECT COUNT(*) AS total FROM kelas";
$resultKelas = $conn->query($queryKelas);
$totalKelas = $resultKelas ? $resultKelas->fetch_assoc()['total'] : 0;

// Total user
$queryUser = "SELECT COUNT(*) AS total FROM users";
$resultUser = $conn->query($queryUser);
$totalUser = $resultUser ? $resultUser->fetch_assoc()['total'] : 0;

// Kelas yang belum absensi hari ini
$queryKelasBelum = "
    SELECT k.id, k.singkatan 
    FROM kelas k 
    WHERE NOT EXISTS (
        SELECT 1 
        FROM absensi a 
        JOIN siswa s ON a.siswa_id = s.id 
        WHERE s.kelas_id = k.id AND DATE(a.tanggal) = '$hariIni'
    )
";
$resultKelasBelum = $conn->query($queryKelasBelum);
$kelasBelumAbsensi = [];
if ($resultKelasBelum && $resultKelasBelum->num_rows > 0) {
    while ($row = $resultKelasBelum->fetch_assoc()) {
        $kelasBelumAbsensi[] = $row;
    }
}

// Rekap bulanan (izin, sakit, alpha)
$queryRekap = "
    SELECT status, COUNT(*) AS jumlah 
    FROM absensi 
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulanIni' 
    GROUP BY status
";
$resultRekap = $conn->query($queryRekap);
$rekap = ['Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];
if ($resultRekap) {
    while ($row = $resultRekap->fetch_assoc()) {
        $keterangan = ucfirst(strtolower($row['status']));
        if (isset($rekap[$keterangan])) {
            $rekap[$keterangan] = $row['jumlah'];
        }
    }
}
?>

<div class="ml-[226px] pt-20 pl-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <!-- Total Siswa -->
        <a href="admin.php?page=tampil_siswa" class="block">
            <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition duration-300 ease-in-out border-l-4 border-blue-500 cursor-pointer h-48 flex flex-col justify-between">
                <h3 class="text-xl font-bold text-gray-800">Total Siswa</h3>
                <div>
                    <p class="mt-2 text-3xl font-semibold text-gray-700">
                        <?php echo $totalSiswa; ?> <span class="text-sm font-normal">siswa</span>
                    </p>
                    <p class="mt-1 text-base text-gray-500">dari <?php echo $totalKelas; ?> kelas</p>
                </div>
            </div>
        </a>

        <!-- Total User -->
        <a href="admin.php?page=tampil_user" class="block">
            <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition duration-300 ease-in-out border-l-4 border-green-500 cursor-pointer h-48 flex flex-col justify-center">
                <h3 class="text-xl font-bold text-gray-800">Total User</h3>
                <p class="mt-2 text-3xl font-semibold text-gray-700"><?php echo $totalUser; ?></p>
            </div>
        </a>

        <!-- Total Kelas -->
        <a href="admin.php?page=tampil_kelas" class="block">
            <div class="bg-white p-4 rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition duration-300 ease-in-out border-l-4 border-yellow-500 cursor-pointer h-48 flex flex-col justify-center">
                <h3 class="text-xl font-bold text-gray-800">Total Kelas</h3>
                <p class="mt-2 text-3xl font-semibold text-gray-700"><?php echo $totalKelas; ?></p>
            </div>
        </a>
    </div>

    <!-- Kelas Belum Absensi -->
    <div class="mt-10 max-w-md">
        <div class="bg-white shadow-md border-l-4 border-gray-400 h-48 rounded-lg p-6 flex flex-col justify-start">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Kelas Belum Absensi</h3>
            <div class="overflow-y-auto max-h-32">
                <?php if (!empty($kelasBelumAbsensi)): ?>
                    <ul class="space-y-1">
                        <?php foreach ($kelasBelumAbsensi as $kelas): ?>
                            <li class="text-gray-700 text-sm border-b pb-1">
                                <?= htmlspecialchars($kelas['singkatan']) ?> - Belum melakukan absensi
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-green-600 text-sm">Semua kelas telah melakukan absensi hari ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rekap Bulanan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 max-w-4xl">
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-purple-500">
            <h3 class="text-lg font-bold text-gray-800">Izin Bulan Ini</h3>
            <p class="text-2xl font-semibold text-gray-700 mt-2"><?= $rekap['Izin'] ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-pink-500">
            <h3 class="text-lg font-bold text-gray-800">Sakit Bulan Ini</h3>
            <p class="text-2xl font-semibold text-gray-700 mt-2"><?= $rekap['Sakit'] ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-md border-l-4 border-red-500">
            <h3 class="text-lg font-bold text-gray-800">Alpha Bulan Ini</h3>
            <p class="text-2xl font-semibold text-gray-700 mt-2"><?= $rekap['Alpha'] ?></p>
        </div>
    </div>
</div>
