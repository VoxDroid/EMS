<?php
require_once '../PARTS/config.php';

// Redirect user to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize login attempts session variable
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    try {
        // Check if too many login attempts
        if ($_SESSION['login_attempts'] >= 8) {
            // Check if cooldown period has passed (1 hour cooldown)
            $cooldownPeriod = 3600; // in seconds (1 hour)
            if (time() - $_SESSION['last_login_attempt_time'] < $cooldownPeriod) {
                $cooldownTimeLeft = $cooldownPeriod - (time() - $_SESSION['last_login_attempt_time']);
                $error = "Too many login attempts. Please try again after " . gmdate("H:i:s", $cooldownTimeLeft) . ".";
            } else {
                // Reset login attempts and cooldown
                $_SESSION['login_attempts'] = 0;
                $_SESSION['last_login_attempt_time'] = null;
            }
        }

        // Proceed with login if not in cooldown
        if (!isset($error)) {
            // Prepare and execute the SQL query to check user credentials
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $_POST['username']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password
            if ($user && password_verify($_POST['password'], $user['password'])) {
                // Login successful, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Reset login attempts on successful login
                $_SESSION['login_attempts'] = 0;
                $_SESSION['last_login_attempt_time'] = null;

                // Redirect user to dashboard to avoid form resubmission
                header("Location: ../index.php");
                exit();
            } else {
                // Invalid username or password
                $error = "Invalid username or password";

                // Increment login attempts and set last attempt time
                $_SESSION['login_attempts']++;
                $_SESSION['last_login_attempt_time'] = time();
            }
        }
    } catch(PDOException $e) {
        die("Error: " . $e->getMessage());
    }
    
    // Redirect to prevent form resubmission
    header("Location: login.php?error=".urlencode($error));
    exit();
}

// Display error message if redirected from login attempt
if(isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

// Calculate cooldown time left if in cooldown period
$cooldownTimeLeft = 0;
if ($_SESSION['login_attempts'] >= 8 && isset($_SESSION['last_login_attempt_time'])) {
    $cooldownPeriod = 3600; // in seconds (1 hour)
    if (time() - $_SESSION['last_login_attempt_time'] < $cooldownPeriod) {
        $cooldownTimeLeft = $cooldownPeriod - (time() - $_SESSION['last_login_attempt_time']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Event Management System</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 20px 0px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            font-size: 2.5rem;
            color: #343a40;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .login-form {
            margin-bottom: 20px;
        }
        .login-form .form-control {
            border-radius: 25px;
            padding-left: 25px;
            height: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
            background-color: #f3f4f7;
            border: none;
        }
        .login-form .form-control:focus {
            box-shadow: none;
            background-color: #e0e2ea;
        }
        .login-form .input-group-text {
            background-color: transparent;
            border: none;
            padding: 0 15px;
            height: 50px;
            border-radius: 25px;
            font-size: 1.2rem;
        }
        .login-form .input-group-text i {
            color: #007bff;
        }
        .login-form .forgot-password {
            text-align: right;
            font-size: 0.9rem;
            margin-top: 10px;
            color: #6c757d;
        }
        .login-form .btn-login {
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
        }
        .login-form .btn-login:hover {
            background-color: #0056b3;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .register-link a:hover {
            color: #0056b3;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .login-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .login-footer a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 mx-auto text-center">
            <div class="login-container">
                <?php
                if (isset($_SESSION['registration_successful'])) {
                    echo '<div class="alert alert-success">' . $_SESSION['registration_successful'] . '</div>';
                    unset($_SESSION['registration_successful']);
                }
                ?>
                <img src="../ASSETS/IMG/EMS_icons/EMS_icon.png" width="150" height="150" alt="EMS" class="img-fluid mb-3">
                <h2 class="login-title">LOGIN</h2>
                <?php if (!empty($error)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                    <div class="form-group">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="form-group forgot-password mb-3 ">
                        <a href="recover_account.php">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-login">LogIn</button>
                </form>
                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
                <hr>
                <div class="login-footer">
                    <p>&copy; <?php echo date('Y'); ?> Event Management System. All rights reserved.</p>
                    <p>Developed by <a href="https://github.com/VoxDroid/EMS" target="_blank">VoxDroid</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>

</body>
</html>