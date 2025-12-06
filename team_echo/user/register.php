<?php
// Include the database connection file
include('../db/db.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO management_user (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $name, $email, $password_hash, $role);
            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }
        /* Left Side */
        .left {
            width: 50%;
            padding: 60px;
            background: #ffffff;
        }
        .left h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .left p {
            color: #666;
            margin-bottom: 30px;
        }
        .input-box {
            margin-bottom: 20px;
        }
        .input-box label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .input-box input,
        .input-box select {
            width: 100%;
            padding: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }
        .btn {
            background: #064c44;
            color: #fff;
            padding: 14px;
            width: 100%;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            background: #043d36;
        }
        .login-link {
            margin-top: 20px;
            text-align: center;
        }
        .login-link a {
            color: #064c44;
            text-decoration: none;
            font-weight: 600;
        }

        /* Right Side Panel */
        .right {
            width: 50%;
            background: linear-gradient(135deg, #0a5a52, #083e38);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
        }
        .right h2 {
            font-size: 38px;
            font-weight: 700;
            line-height: 1.3;
        }
        .quote {
            margin-top: 30px;
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
        }

        /* Animation */
@keyframes fadeSlideUp {
    0% {
        opacity: 0;
        transform: translateY(40px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.right-content {
    animation: fadeSlideUp 1.2s ease-out forwards;
}

/* Optional staggered animation for quote */
.right-content .quote {
    animation: fadeSlideUp 1.6s ease-out forwards;
    opacity: 0;
}

    </style>
</head>
<body>

<div class="left">
    <h1>Create an Account</h1>
    <p>Join our platform and access all features.</p>

    <?php if (!empty($error)) { ?>
        <p style="color: red;"><?= $error ?></p>
    <?php } ?>

    <form method="POST">
        <div class="input-box">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="input-box">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="input-box">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="input-box">
            <label>Role</label>
            <select name="role" required>
                <option value="admin">Admin</option>
                <option value="hr">HR</option>
                <option value="user">User</option>
            </select>
        </div>

        <button class="btn" type="submit">Create Account</button>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </form>
</div>

<div class="right">
    <div class="right-content">
        <h2>Revolutionize Campus Feedback<br>with Smart Automation</h2>

        <p class="quote">
            “Our platform makes analyzing student feedback simple, accurate, and insightful.”
        </p>
    </div>
</div>


</body>
</html>
