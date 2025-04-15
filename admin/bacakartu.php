<?php
require '../koneksi.php';

$sql = mysqli_query($conn, "SELECT * FROM tipe");
$data = mysqli_fetch_array($sql);

// Hanya ada dua mode: 1 = masuk, 2 = pulang
$mode_absen = $data['mode'];

$mode = "";
if ($mode_absen == 1)
    $mode = "masuk";
elseif ($mode_absen == 2)
    $mode = "pulang";

$baca_kartu = mysqli_query($conn, "SELECT * FROM rfid");
$data_kartu = mysqli_fetch_array($baca_kartu);
$nokartu = $data_kartu['nokartu'];

$tanggal = date('Y-m-d');

?>
<div class="w-full text-center mt-10">
    <?php if ($nokartu == "") { ?>
        <h3>Absen <?php echo htmlspecialchars($mode); ?></h3>
        <h3 class="text-xl font-semibold mb-4">Silakan Tempel Kartu RFID</h3>
        <div class="flex flex-col items-center">
            <img src="../asset/scan.png" alt="Scan" class="w-40 mb-4" />
            <img src="../asset/scan.gif" alt="Scanning Animation" class="w-32" />
        </div>
    <?php } else {
        $cari_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE nokartu='$nokartu'");
        $jumlah_data = mysqli_num_rows($cari_siswa);

        if ($jumlah_data == 0) {
            echo "<h3 class='text-red-600 text-xl font-bold'>Kartu tidak dikenali!</h3>";
        } else {
            $data_siswa = mysqli_fetch_array($cari_siswa);
            $id_siswa = $data_siswa['id'];
            $nama_siswa = $data_siswa['nama'];

            // Ambil waktu saat ini untuk logika keterlambatan
            $waktu_sekarang = date("H:i:s");
            $status = 'hadir';
            // Hanya cek keterlambatan untuk absensi masuk (tipe 1)
            if ($mode_absen == 1 && ($waktu_sekarang >= "07:30:00" && $waktu_sekarang <= "10:05:00")) {
                $status = 'terlambat';
            }

            // Cek apakah sudah ada data absen untuk siswa ini pada mode dan tanggal ini
            $cek_absen = mysqli_query($conn, "SELECT * FROM absensi WHERE siswa_id='$id_siswa' AND tanggal='$tanggal' AND tipe_id='$mode_absen'");
            if (mysqli_num_rows($cek_absen) == 0) {
                // Simpan absensi dengan status yang telah ditentukan dan waktu menggunakan CURTIME()
                mysqli_query($conn, "INSERT INTO absensi (siswa_id, tipe_id, tanggal, status, waktu) VALUES ('$id_siswa', '$mode_absen', '$tanggal', '$status', CURTIME())");

                echo "<h3 class='text-green-600 text-2xl font-bold'>Absen berhasil!</h3>";
                echo "<h4 class='text-lg mt-2'>Nama: <span class='font-semibold'>" . htmlspecialchars($nama_siswa) . "</span></h4>";
                echo "<p class='text-sm text-gray-600'>Tanggal: $tanggal</p>";
                echo "<p class='text-sm text-gray-600'>Tipe: $mode</p>";
                echo "<p class='text-sm text-gray-600'>Status: " . htmlspecialchars($status) . "</p>";
            } else {
                echo "<h3 class='text-yellow-600 text-xl font-bold'>Sudah absen sebelumnya!</h3>";
                echo "<p class='text-gray-600 mt-2'>Nama: <strong>" . htmlspecialchars($nama_siswa) . "</strong></p>";
                echo "<p class='text-sm text-gray-600'>Tipe: $mode</p>";
            }
        }
        // Reset nilai RFID agar siap membaca kartu berikutnya
        mysqli_query($conn, "UPDATE rfid SET nokartu=''");
    } ?>
</div>

<?php if($nokartu != "") { ?>
    <script>
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    </script>
<?php } ?>

</div>

<?php if ($nokartu != "") { ?>
    <script>
        // Setelah 3 detik, lakukan reload halaman
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    </script>
<?php } ?>
