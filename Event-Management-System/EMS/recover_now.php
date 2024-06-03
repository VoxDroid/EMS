<?php
require_once '../PARTS/config.php';

try {
    // Verify token from the URL
    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        // Check if token exists in the database and is still valid
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $tokenCreationTime = strtotime($user['token_creation_time']);
            $currentTime = time();

            if (($currentTime - $tokenCreationTime) > 3600) { // Token is valid for 1 hour (3600 seconds)
                $error = "The token has expired. Please request a new password reset.";
                $_SESSION['error_message'] = $error;
                header("Location: ../index.php");
                exit();
            }
        } else {
            $error = "Invalid token.";
            $_SESSION['error_message'] = $error;
            header("Location: ../index.php");
            exit();
        }
    } else {
        header("Location: recover_now.php");
        exit();
    }

    // Initialize variables
    $password = $confirm_password = "";
    $error = $error ?? ""; // Use existing error message if set

    // Handle password reset form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if cancel button was pressed
        if (isset($_POST['cancel'])) {
            unset($_SESSION['show_confirmation']);
        } else {
            if (isset($_POST['password']) && isset($_POST['confirm_password'])) {
                if (empty($error)) { // Ensure there is no error before processing the form
                    $password = $_POST['password'];
                    $confirm_password = $_POST['confirm_password'];

                    // Validate password (minimum 8 characters, no specific requirements)
                    if (empty(trim($password))) {
                        $error = "Please enter a password.";
                    } elseif (strlen(trim($password)) < 8) {
                        $error = "Password must have at least 8 characters.";
                    } else {
                        // Proceed with password reset
                        if ($password != $confirm_password) {
                            $error = "Passwords do not match.";
                        } else {
                            // Check if the confirmation step is already shown
                            if (isset($_POST['confirm_submission']) && $_POST['confirm_submission'] == 'yes') {
                                // Hash the password
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                                // Update user's password and clear token
                                $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, token_creation_time = NULL WHERE id = :id");
                                $stmt->execute(['password' => $hashed_password, 'id' => $user['id']]);

                                // Redirect user to login page after password reset
                                $_SESSION['registration_successful'] = "Password reset successful. You can now login with your new password.";
                                header("Location: login.php");
                                exit();
                            } else {
                                // Show confirmation step
                                $_SESSION['show_confirmation'] = true;
                            }
                        }
                    }
                }
            }
        }
    }

    $show_confirmation = isset($_SESSION['show_confirmation']);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Event Management System</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #405164;
            font-family: Poppins, sans-serif;
        }
        .reset-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 20px 0px rgba(0,0,0,0.1);
        }
        .reset-title {
            text-align: center;
            font-size: 2.5rem;
            color: #343a40;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .reset-form {
            margin-bottom: 20px;
        }
        .reset-form .form-control {
            border-radius: 25px;
            padding-left: 25px;
            height: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
            background-color: #f3f4f7;
            border: none;
        }
        .reset-form .form-control:focus {
            box-shadow: none;
            background-color: #e0e2ea;
        }
        .reset-form .input-group-text {
            background-color: transparent;
            border: none;
            padding: 0 15px;
            height: 50px;
            border-radius: 25px;
            font-size: 1.2rem;
        }
        .reset-form .input-group-text i {
            color: #007bff;
        }
        .reset-form .btn-reset {
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
        }
        .reset-form .btn-reset:hover {
            background-color: #0056b3;
        }
        .reset-form .btn-cancel {
            background-color: #6c757d;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        .reset-form .btn-cancel:hover {
            background-color: #5a6268;
        }
        .reset-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .reset-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .reset-footer a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="reset-container text-center">
                <?php if ($error != '') : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <a href="../index.php"><img src="../ASSETS/IMG/EMS_icons/EMS_icon.png" width="150" height="150" alt="EMS"  class="img-fluid mb-3"></a>
                <h2 class="reset-title">Reset Password</h2>
                <?php if ($show_confirmation) : ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $_GET['token']; ?>" method="post" class="reset-form">
                        <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>">
                        <input type="hidden" name="confirm_password" value="<?php echo htmlspecialchars($confirm_password); ?>">
                        <input type="hidden" name="confirm_submission" value="yes">
                        <div class="alert alert-warning" role="alert">
                            Are you sure you want to reset your password?
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-reset">Confirm Reset Password</button>
                        <button type="submit" class="btn btn-secondary btn-block btn-cancel" name="cancel">Cancel</button>
                    </form>
                <?php else : ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $_GET['token']; ?>" method="post" class="reset-form">
                        <div class="form-group">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" name="password" id="password" class="form-control" placeholder="New Password" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block btn-reset">Reset Password</button>
                    </form>
                <?php endif; ?>
                <hr>
                <div class="reset-footer">
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
