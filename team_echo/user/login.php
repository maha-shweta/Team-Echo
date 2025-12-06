<?php
session_start();
include('../db/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $sql = "SELECT * FROM management_user WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];

                    // Update last login
                    $update_stmt = $conn->prepare("UPDATE management_user SET last_login = NOW() WHERE user_id = ?");
                    $update_stmt->bind_param("i", $user['user_id']);
                    $update_stmt->execute();
                    $update_stmt->close();

                    if ($user['role'] == 'admin') {
                        header('Location: ../admin/admin_dashboard.php');
                        exit;
                    } elseif ($user['role'] == 'hr') {
                        header('Location: ../hr/hr_dashboard.php');
                        exit;
                    } else {
                        header('Location: ../user/user_dashboard.php');
                        exit;
                    }
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with this email.";
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
<title>Login</title>

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
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .left h1 {
        font-size: 32px;
        margin-bottom: 10px;
        color: #064c44;
    }
    .left p {
        color: #666;
        margin-bottom: 30px;
    }
    .input-box {
        position: relative;
        margin-bottom: 25px;
    }
    .input-box input {
        width: 100%;
        padding: 14px 14px 14px 12px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        outline: none;
    }
    .input-box label {
        position: absolute;
        top: 50%;
        left: 12px;
        transform: translateY(-50%);
        background: #fff;
        padding: 0 5px;
        color: #999;
        transition: 0.3s;
        pointer-events: none;
    }
    .input-box input:focus + label,
    .input-box input:not(:placeholder-shown) + label {
        top: -10px;
        font-size: 12px;
        color: #064c44;
    }

    .input-box .char-count {
        position: absolute;
        bottom: -18px;
        right: 10px;
        font-size: 12px;
        color: #999;
    }

    .password-toggle {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 14px;
        color: #064c44;
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

    /* Right Side */
    .right {
        width: 50%;
        background: linear-gradient(135deg, #0a5a52, #083e38);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 60px;
        overflow: hidden;
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
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    .right-content {
        animation: fadeSlideUp 1.2s ease-out forwards;
    }
    .right-content .quote {
        animation: fadeSlideUp 1.6s ease-out forwards;
        opacity: 0;
    }

    /* Toast */
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.2);
        display: none;
        z-index: 9999;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .left, .right {
            width: 100%;
            padding: 40px;
        }
        body {
            flex-direction: column;
        }
    }
</style>
</head>
<body>

<div class="left">
    <h1>Welcome Back</h1>
    <p>Sign in to access your dashboard.</p>

    <form method="POST">
        <div class="input-box">
            <input type="email" name="email" placeholder=" " required>
            <label>Email</label>
        </div>

        <div class="input-box">
            <input type="password" name="password" id="password" placeholder=" " maxlength="20" required>
            <label>Password</label>
            <span class="char-count" id="charCount">0/20</span>
            <span class="password-toggle" id="togglePassword">Show</span>
        </div>

        <button class="btn" type="submit">Login</button>

        <div class="login-link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </form>
</div>

<div class="right">
    <div class="right-content">
        <h2>Effortless Campus Feedback<br>Starts with Better Insights</h2>
        <p class="quote">“Log in to manage feedback, analyze data trends, and empower your institution.”</p>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
    // Password toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    togglePassword.addEventListener('click', () => {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        togglePassword.textContent = type === 'password' ? 'Show' : 'Hide';
    });

    // Character count
    const charCount = document.getElementById('charCount');
    passwordField.addEventListener('input', () => {
        charCount.textContent = `${passwordField.value.length}/20`;
    });

    // Toast for errors
    <?php if (!empty($error)) { ?>
        const toast = document.getElementById('toast');
        toast.textContent = "<?= $error ?>";
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 4000);
    <?php } ?>
</script>

</body>
</html>
