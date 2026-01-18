<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include('../db/db.php');
$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else {
        // SQL matching your table: password_hash
        $sql = "SELECT password_hash FROM management_user WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_password, $user['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE management_user SET password_hash = ? WHERE user_id = ?");
            $update->bind_param("si", $new_hash, $user_id);
            
            if ($update->execute()) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Database error.";
            }
        } else {
            $error_message = "Current password incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Shared Styles with Profile */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background-color: #0C4F3B;
            background-image: radial-gradient(circle at 10% 20%, rgba(30, 179, 134, 0.2) 0%, transparent 40%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .card {
            background: #fff; width: 100%; max-width: 420px;
            border-radius: 32px; padding: 40px; box-shadow: 0 40px 100px rgba(0, 0, 0, 0.4);
        }
        .header { text-align: center; margin-bottom: 25px; }
        .icon { font-size: 40px; margin-bottom: 10px; }
        .alert { padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; text-align: center; }
        .alert-success { background: #e6f7ed; color: #1e7d4d; }
        .alert-error { background: #fff1f1; color: #c53030; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 11px; font-weight: 800; color: #4a5568; margin-bottom: 6px; text-transform: uppercase; }
        input { width: 100%; padding: 14px; border: 2px solid #edf2f7; border-radius: 12px; font-size: 14px; }
        .btn {
            width: 100%; background: #0C4F3B; color: white; border: none; padding: 16px;
            border-radius: 14px; font-weight: 700; cursor: pointer; margin-top: 10px;
        }
        .back { display: block; text-align: center; margin-top: 20px; color: #718096; text-decoration: none; font-size: 13px; font-weight: 600; }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <div class="icon">🔐</div>
        <h2>Security</h2>
        <p style="color:#718096; font-size: 13px;">Update your login password</p>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success">✅ <?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">⚠️ <?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
        <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
        <button type="submit" class="btn">Update Password</button>
    </form>
    <a href="profile.php" class="back">← Back to Profile</a>
</div>

</body>
</html>