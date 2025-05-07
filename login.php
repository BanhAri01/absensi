<?php
require 'koneksi.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Email atau password salah!";
        }
    } else {
        $error = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex justify-center items-center min-h-screen bg-cover bg-center bg-no-repeat bg-[url('asset/login-bg.png')]">
    <div class="w-full max-w-md p-8 bg-white bg-opacity-20 rounded-lg shadow-lg backdrop-blur-sm flex flex-col items-center">
        <img src="asset/wihope.png" alt="Logo" class="w-24 h-24 mb-4">
        <h2 class="text-white text-2xl font-bold text-center mb-4">LOGIN</h2>
        <?php if (isset($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <form action="" method="POST" class="w-full">
            <div class="mb-4">
                <input type="email" name="email" class="w-full px-4 py-2 rounded-lg bg-gray-100 text-gray-800 focus:outline-none" placeholder="Email" required>
            </div>
            <div class="mb-4">
                <input type="password" name="password" class="w-full px-4 py-2 rounded-lg bg-gray-100 text-gray-800 focus:outline-none" placeholder="Password" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Login</button>
        </form>
      
    </div>
</body>
</html>
