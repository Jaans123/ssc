<?php
session_start();

$config_file = 'config.php';
$data_file = 'api/phonenum/data.txt';

// Create default config if not exists
if (!file_exists($config_file)) {
    file_put_contents($config_file, "<?php\nreturn ['username' => 'admin', 'password' => '1234'];");
}

$config = require $config_file;

// Read current number (if any)
$current_number = '';
if (file_exists($data_file)) {
    $json = json_decode(file_get_contents($data_file), true);
    if (isset($json['number'])) {
        $current_number = $json['number'];
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: save-number.php");
    exit();
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username === $config['username'] && $password === $config['password']) {
        $_SESSION['logged_in'] = true;
    } else {
        $login_error = "Invalid username or password.";
    }
}

// Handle number update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_number']) && isset($_SESSION['logged_in'])) {
    $number = trim($_POST['number'] ?? '');
    if (preg_match('/^\d{10,15}$/', $number)) {
        file_put_contents($data_file, json_encode(['number' => $number]));
        $number_success = "✅ Number updated successfully! Current Number: " . htmlspecialchars($number);
        $current_number = $number;
    } else {
        $number_success = "❌ Invalid number format.";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password']) && isset($_SESSION['logged_in'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current !== $config['password']) {
        $pass_error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $pass_error = "New passwords do not match.";
    } else {
        file_put_contents($config_file, "<?php\nreturn ['username' => '" . $config['username'] . "', 'password' => '" . $new . "'];");
        $pass_success = "Password changed successfully.";
    }
}

// Show login screen if not logged in
if (!isset($_SESSION['logged_in'])):
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 40px; }
        .container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        input[type=text], input[type=password] {
            width: 100%; padding: 10px; margin: 8px 0;
        }
        input[type=submit] {
            background-color: #4CAF50; color: white; border: none; padding: 10px; width: 100%;
        }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <h2>🔐 Login</h2>
    <?php if (!empty($login_error)) echo "<p class='error'>$login_error</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required/>
        <input type="password" name="password" placeholder="Password" required/>
        <input type="submit" name="login" value="Login"/>
    </form>
</div>
</body>
</html>
<?php exit(); endif; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Number Dashboard</title>
    <style>
        body { font-family: Arial; background: #eef1f5; padding: 40px; }
        .box { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; }
        input { width: 100%; padding: 10px; margin-top: 10px; }
        input[type=submit], button {
            background: #28a745; color: white; border: none;
            padding: 10px; cursor: pointer; margin-top: 10px;
        }
        .success { color: green; margin-top: 10px; }
        .error { color: red; margin-top: 10px; }
        .logout { float: right; text-decoration: none; color: #dc3545; }
        .password-form { display: none; margin-top: 15px; }
    </style>
    <script>
        function togglePasswordForm() {
            const form = document.getElementById('passwordForm');
            form.style.display = (form.style.display === 'none') ? 'block' : 'none';
        }
    </script>
</head>
<body>
<div class="box">
    <h2>Welcome, <?= htmlspecialchars($config['username']) ?> <a class="logout" href="?logout=1">Logout</a></h2>

    <h3>📱 Update Forwarding Number</h3>
    <?php if (!empty($number_success)) echo "<p class='success'>$number_success</p>"; ?>
    <form method="post">
        <input type="text" name="number" placeholder="Enter mobile number" required/>
        <input type="submit" name="update_number" value="Update Number"/>
    </form>
    <?php if (!empty($current_number)) echo "<p><strong>Current Number:</strong> " . htmlspecialchars($current_number) . "</p>"; ?>

    <h3>🔐 Change Password</h3>
    <button onclick="togglePasswordForm()">Change Password</button>
    <div class="password-form" id="passwordForm">
        <?php if (!empty($pass_error)) echo "<p class='error'>$pass_error</p>"; ?>
        <?php if (!empty($pass_success)) echo "<p class='success'>$pass_success</p>"; ?>
        <form method="post">
            <input type="password" name="current_password" placeholder="Current Password" required/>
            <input type="password" name="new_password" placeholder="New Password" required/>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required/>
            <input type="submit" name="change_password" value="Change Password"/>
        </form>
    </div>
</div>
</body>
</html>
