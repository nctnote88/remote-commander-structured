<?php
session_start();

// ✅ Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// ✅ Validate login
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ✅ Simple config - Replace with DB/Auth system in production
    $valid_users = [
        'admin' => password_hash('strongpassword123', PASSWORD_DEFAULT)
    ];

    if (!isset($valid_users[$username])) {
        $errors[] = 'Invalid username or password';
    } elseif (!password_verify($password, $valid_users[$username])) {
        $errors[] = 'Invalid username or password';
    } else {
        // ✅ Login success
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Remote Commander</title>
    <style>
        body {
            background-color: #f4f7f9;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #007BFF;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .error {
            background: #fdd;
            color: #900;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <?php foreach ($errors as $error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
