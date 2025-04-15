<?php
require '../koneksi.php';

if (session_status() == PHP_SESSION_NONE) session_start();
if ($conn === false) die("Koneksi database gagal: " . mysqli_connect_error());

$tipe_rekap = $_POST['tipe_rekap'] ?? 'harian';
$selected_kelas = $_POST['kelas'] ?? '';
$selected_tanggal = $_POST['tanggal'] ?? date('Y-m-d');
$selected_bulan = $_POST['bulan'] ?? date('n');
$selected_tahun = $_POST['tahun'] ?? date('Y');

$judul = "";
$nama_file = "";

if ($tipe_rekap === 'bulanan') {
    $start_date = "$selected_tahun-" . str_pad($selected_bulan, 2, '0', STR_PAD_LEFT) . "-01";
    $end_date = date("Y-m-t", strtotime($start_date));
    $judul = "Rekap Absensi Bulan " . date('F', strtotime($start_date)) . " $selected_tahun";
    $nama_file = "rekap-absensi-$selected_bulan-$selected_tahun.xls";

    $sql = "SELECT siswa.id, siswa.nis, siswa.nama, kelas.singkatan,
        (SELECT COUNT(*) FROM absensi WHERE siswa_id = siswa.id AND tanggal BETWEEN ? AND ? AND tipe_id = 1 AND status = 'izin') AS total_izin,
        (SELECT COUNT(*) FROM absensi WHERE siswa_id = siswa.id AND tanggal BETWEEN ? AND ? AND tipe_id = 1 AND status = 'sakit') AS total_sakit,
        (SELECT COUNT(*) FROM absensi WHERE siswa_id = siswa.id AND tanggal BETWEEN ? AND ? AND tipe_id = 1 AND status = 'alpha') AS total_alpha
    FROM siswa
    LEFT JOIN kelas ON siswa.kelas_id = kelas.id
    WHERE 1";

    $params = [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date];
    $types = "ssssss";
} else {
    $judul = "Rekap Absensi Tanggal $selected_tanggal";
    $nama_file = "rekap-absensi-$selected_tanggal.xls";

    $sql = "SELECT siswa.id, siswa.nis, siswa.nama, kelas.singkatan,
        (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 1 LIMIT 1) AS masuk,
        (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 2 LIMIT 1) AS istirahat,
        (SELECT status FROM absensi WHERE siswa_id = siswa.id AND tanggal = ? AND tipe_id = 3 LIMIT 1) AS pulang
    FROM siswa
    LEFT JOIN kelas ON siswa.kelas_id = kelas.id
    WHERE 1";

    $params = [$selected_tanggal, $selected_tanggal, $selected_tanggal];
    $types = "sss";
}

if (!empty($selected_kelas)) {
    $sql .= " AND siswa.kelas_id = ?";
    $params[] = $selected_kelas;
    $types .= "i";
}

$sql .= " ORDER BY kelas.singkatan, siswa.nama ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();


if ($tipe_rekap === 'bulanan') {
    $queryCard = "SELECT status, COUNT(*) as total FROM absensi WHERE tipe_id = 1 AND tanggal BETWEEN ? AND ?";
    $paramsCard = [$start_date, $end_date];
    $typesCard = "ss";
} else {
    $queryCard = "SELECT status, COUNT(*) as total FROM absensi WHERE tipe_id = 1 AND tanggal = ?";
    $paramsCard = [$selected_tanggal];
    $typesCard = "s";
}

if (!empty($selected_kelas)) {
    $queryCard .= " AND siswa_id IN (SELECT id FROM siswa WHERE kelas_id = ?)";
    $paramsCard[] = $selected_kelas;
    $typesCard .= "i";
}

$queryCard .= " GROUP BY status";
$stmtCard = $conn->prepare($queryCard);
$stmtCard->bind_param($typesCard, ...$paramsCard);
$stmtCard->execute();
$resultCard = $stmtCard->get_result();

$cardData = ['izin' => 0, 'sakit' => 0, 'alpha' => 0];
while ($row = $resultCard->fetch_assoc()) {
    $status = strtolower($row['status']);
    if (isset($cardData[$status])) $cardData[$status] = $row['total'];
}


header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$nama_file");
?>

<h2 style="text-align: center;"><?= $judul ?></h2>

<table border="1" cellpadding="8" cellspacing="0" style="margin-bottom: 20px;">
    <tr style="background-color: #DFF0D8;">
        <th colspan="2">Izin</th>
        <th colspan="2">Sakit</th>
        <th colspan="2">Alpha</th>
    </tr>
    <tr>
        <td colspan="2" align="center"><?= $cardData['izin'] ?></td>
        <td colspan="2" align="center"><?= $cardData['sakit'] ?></td>
        <td colspan="2" align="center"><?= $cardData['alpha'] ?></td>
    </tr>
</table>

<?php if ($tipe_rekap === 'harian'): ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Masuk</th>
                <th>Istirahat</th>
                <th>Pulang</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['singkatan']) ?></td>
                        <td><?= $row['masuk'] ?? '-' ?></td>
                        <td><?= $row['istirahat'] ?? '-' ?></td>
                        <td><?= $row['pulang'] ?? '-' ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" align="center">Tidak ada data absensi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Total Izin</th>
                <th>Total Sakit</th>
                <th>Total Alpha</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nis']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['singkatan']) ?></td>
                        <td><?= $row['total_izin'] ?></td>
                        <td><?= $row['total_sakit'] ?></td>
                        <td><?= $row['total_alpha'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" align="center">Tidak ada data absensi.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>
