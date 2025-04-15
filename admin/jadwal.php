<?php
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mapel_id = $_POST['mapel_id'];
    $user_id = $_POST['user_id'];
    $kelas_id = $_POST['kelas_id'];
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    $query = "INSERT INTO jadwal (mapel_id, user_id, kelas_id, hari, jam_mulai, jam_selesai)
              VALUES ('$mapel_id', '$user_id', '$kelas_id', '$hari', '$jam_mulai', '$jam_selesai')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Jadwal berhasil ditambahkan!');</script>";
    } else {
        echo "<script>alert('Gagal menambahkan jadwal!');</script>";
    }
}

$mapel = mysqli_query($conn, "SELECT * FROM mapel");
$guru = mysqli_query($conn, "SELECT * FROM users 
    INNER JOIN jabatan ON users.jabatan_id = jabatan.id 
    WHERE jabatan.nama_jabatan = 'guru'");
$kelas = mysqli_query($conn, "SELECT * FROM kelas");
?>


<div class="shadow-lg rounded-lg bg-white p-3 w-[83%] h-auto ml-64 mt-16">
    <h2 class="text-xl font-bold mb-4 bg-[#001F3F] text-white p-3 rounded-md text-center">
        Form Input Jadwal Pelajaran
    </h2>

    <form method="POST" class="space-y-4">

        <div>
            <label class="block font-semibold">Mata Pelajaran</label>
            <select name="mapel_id" class="w-full p-2 border rounded-md" required>
                <option value="">-- Pilih Mapel --</option>
                <?php while ($m = mysqli_fetch_assoc($mapel)) { ?>
                    <option value="<?= $m['id'] ?>"><?= $m['nama_mapel'] ?></option>
                <?php } ?>
            </select>
        </div>

<div>
    <label class="block font-semibold">Guru Pengajar</label>
    <select name="user_id" required class="w-full p-2 border rounded-md">
        <option value="">Pilih Guru</option>
        <?php
        $data_guru = mysqli_query($conn, "SELECT * FROM users WHERE role='guru'");
        while ($g = mysqli_fetch_assoc($data_guru)) {
            echo "<option value='{$g['id']}'>{$g['username']}</option>";
        }
        ?>
    </select>
</div>


        <div>
            <label class="block font-semibold">Kelas</label>
            <select name="kelas_id" class="w-full p-2 border rounded-md" required>
                <option value="">-- Pilih Kelas --</option>
                <?php while ($k = mysqli_fetch_assoc($kelas)) { ?>
                    <option value="<?= $k['id'] ?>"><?= $k['nama_kelas'] ?></option>
                <?php } ?>
            </select>
        </div>

        <div>
            <label class="block font-semibold">Hari</label>
            <select name="hari" class="w-full p-2 border rounded-md" required>
                <option value="">-- Pilih Hari --</option>
                <option value="Senin">Senin</option>
                <option value="Selasa">Selasa</option>
                <option value="Rabu">Rabu</option>
                <option value="Kamis">Kamis</option>
                <option value="Jumat">Jumat</option>
                <option value="Sabtu">Sabtu</option>
            </select>
        </div>

        <div class="flex space-x-4">
            <div class="w-1/2">
                <label class="block font-semibold">Jam Mulai</label>
                <input type="time" name="jam_mulai" class="w-full p-2 border rounded-md" required>
            </div>
            <div class="w-1/2">
                <label class="block font-semibold">Jam Selesai</label>
                <input type="time" name="jam_selesai" class="w-full p-2 border rounded-md" required>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="bg-[#001F3F] text-white px-6 py-2 rounded-md hover:bg-blue-800">
                Simpan Jadwal
            </button>
        </div>
    </form>
</div>