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

// Include SendGrid autoloader
require_once '../vendor/autoload.php'; // Adjust the path as needed

use SendGrid\Mail\Mail;

try {
    // Handle password recovery form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
        // Validate email (basic validation for example)
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

        if ($email === false) {
            $error = "Invalid email format";
        } else {
            // Check if email exists in the database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate a unique token (for simplicity, using a random string here)
                $token = bin2hex(random_bytes(32));
                $tokenCreationTime = date('Y-m-d H:i:s'); // Get current time

                // Store token and creation time in the database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, token_creation_time = :token_creation_time WHERE email = :email");
                $stmt->execute(['token' => $token, 'token_creation_time' => $tokenCreationTime, 'email' => $email]);

                // Send password reset link to user's email using SendGrid
                $email = new Mail();
                $email->setFrom("/***YOUR_EMAIL***/", "Event Management System");
                $email->setSubject("Password Reset Request");
                $email->addTo($user['email'], $user['username']);
                $email->setTemplateId('/***YOUR_TEMPLATE_ID***/');
                $email->addDynamicTemplateData('username', $user['username']);
                // Replace the reset link placeholder with the actual reset link URL
                $email->addDynamicTemplateData('reset_link', 'http://localhost/event-management-system/EMS/recover_now.php?token=' . $token);

                $sendgrid = new \SendGrid('/***YOUR_API_KEY***/');

                try {
                    $response = $sendgrid->send($email);
                    if ($response->statusCode() == 202) {
                        $success = "Password reset link sent to your email. Check your inbox!";
                    } else {
                        throw new Exception("Failed to send email.");
                    }
                } catch (Exception $e) {
                    $error = "Caught exception: ". $e->getMessage() . "\n";
                }
            } else {
                $error = "Email not found in our database";
            }
        }
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - Event Management System</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #405164;
            font-family: Poppins, sans-serif;
        }
        .recover-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 20px 0px rgba(0,0,0,0.1);
        }
        .recover-title {
            text-align: center;
            font-size: 2.5rem;
            color: #343a40;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .recover-form {
            margin-bottom: 20px;
        }
        .recover-form .form-control {
            border-radius: 25px;
            padding-left: 25px;
            height: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
            background-color: #f3f4f7;
            border: none;
        }
        .recover-form .form-control:focus {
            box-shadow: none;
            background-color: #e0e2ea;
        }
        .recover-form .input-group-text {
            background-color: transparent;
            border: none;
            padding: 0 15px;
            height: 50px;
            border-radius: 25px;
            font-size: 1.2rem;
        }
        .recover-form .input-group-text i {
            color: #007bff;
        }
        .recover-form .btn-recover {
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
        }
        .recover-form .btn-recover:hover {
            background-color: #0056b3;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .login-link a:hover {
            color: #0056b3;
        }
        .recover-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .recover-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .recover-footer a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 mx-auto text-center">
            <div class="recover-container">
                <?php if (isset($success)) : ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <a href="../index.php"><img src="../ASSETS/IMG/EMS_icons/EMS_icon.png" width="150" height="150" alt="EMS"  class="img-fluid mb-3"></a>
                <h2 class="recover-title">Forgot Password</h2>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="recover-form">
                    <div class="form-group">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" name="email" id="email" class="form-control" placeholder="Your Email" required>
                   </div>
               </div>
               <button type="submit" class="btn btn-primary btn-block btn-recover">Recover Password</button>
           </form>
           <div class="login-link">
               <p>Remember your password? <a href="login.php">Login here</a></p>
           </div>
           <hr>
           <div class="recover-footer">
               <p>&copy; <?php echo date('Y'); ?> Event Management System. All rights reserved.</p>
               <p>Developed by <a href="https://github.com/VoxDroid/EMS" target="_blank">VoxDroid</a></p>
           </div>
       </div>
   </div>
</div>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>
</body>
</html>

