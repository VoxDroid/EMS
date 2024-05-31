<?php
require_once '../PARTS/config.php';

// Redirect user to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize registration variables
$username = $email = $password = $gender = "";
$username_err = $email_err = $password_err = $gender_err = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (empty(trim($_POST['username']))) {
            $username_err = "Please enter a username.";
        } elseif (strlen(trim($_POST['username'])) < 3) {
            $username_err = "Username must have at least 3 characters.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,}$/', trim($_POST['username']))) {
            $username_err = "Username can only contain letters, numbers, and underscores.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST['username']);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST['username']);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }

        // Validate email
        if (empty(trim($_POST['email']))) {
            $email_err = "Please enter an email address.";
        } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $param_email, PDO::PARAM_STR);
            $param_email = trim($_POST['email']);
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $email_err = "This email address is already registered.";
                } else {
                    $email = trim($_POST['email']);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }

        // Validate password
        if (empty(trim($_POST['password']))) {
            $password_err = "Please enter a password.";
        } elseif (strlen(trim($_POST['password'])) < 8) {
            $password_err = "Password must have at least 8 characters.";
        } else {
            $password = trim($_POST['password']);
        }

        // Validate gender
        if (!isset($_POST['gender']) || ($_POST['gender'] != 'male' && $_POST['gender'] != 'female')) {
            $gender_err = "Please select a gender.";
        } else {
            $gender = $_POST['gender'];
        }

        // Check input errors before inserting into database
        if (empty($username_err) && empty($email_err) && empty($password_err) && empty($gender_err)) {
            $sql = "INSERT INTO users (username, password, gender, email, profile_picture, role, can_request_event, can_review_request, can_delete_user)
                    VALUES (:username, :password, :gender, :email, :profile_picture, 'user', TRUE, FALSE, FALSE)";

            if ($stmt = $pdo->prepare($sql)) {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Set default profile picture based on gender
                $defaultProfilePicture = ($gender === 'female') ? '../ASSETS/IMG/DPFP/female.png' : '../ASSETS/IMG/DPFP/male.png';

                // Bind variables to the prepared statement as parameters
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':gender', $gender, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':profile_picture', $defaultProfilePicture, PDO::PARAM_STR);

                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    // Registration successful, redirect to login page
                    $_SESSION['registration_successful'] = "Registration successful. Please log in to your account.";
                    header("Location: login.php");
                    exit();
                } else {
                    echo "Something went wrong. Please try again later.";
                }

                // Close statement
                unset($stmt);
            }
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

    // Close connection
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Event Management System</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .register-container {
            max-width: 400px;
            margin: 0 auto;
            margin-top: 100px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 20px 0px rgba(0,0,0,0.1);
        }
        .register-title {
            text-align: center;
            font-size: 2.5rem;
            color: #343a40;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .register-form {
            margin-bottom: 20px;
        }
        .register-form .form-control {
            border-radius: 25px;
            padding-left: 25px;
            height: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
            background-color: #f3f4f7;
            border: none;
        }
        .register-form .form-control:focus {
            box-shadow: none;
            background-color: #e0e2ea;
        }
        .register-form .input-group-text {
            background-color: transparent;
            border: none;
            padding: 0 15px;
            height: 50px;
            border-radius: 25px;
            font-size: 1.2rem;
        }
        .register-form .input-group-text i {
            color: #007bff;
        }
        .register-form .register-link {
            text-align: right;
            font-size: 0.9rem;
            margin-top: 10px;
            color: #6c757d;
        }
        .register-form .btn-register {
            background-color: #007bff;
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: background-color 0.3s;
            width: 100%;
        }
        .register-form .btn-register:hover {
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
        .register-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .register-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }
        .register-footer a:hover {
            color: #0056b3;
        }
        .input-group-text {
            width: 50px;
            text-align: center;
            padding: 15px;
            font-size: 1.2rem;
        }
        .input-group-prepend {
            width: 50px;
        }
        .input-group {
            position: relative;
        }
        .form-control {
            border-radius: 25px;
            padding-left: 25px;
            height: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
            background-color: #f3f4f7;
            border: none;
        }
        .form-control:focus {
            box-shadow: none;
            background-color: #e0e2ea;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 mx-auto text-center">
            <div class="register-container">
                <img src="../ASSETS/IMG/EMS_icons/EMS_icon.png" width="150" height="150" alt="EMS" class="img-fluid mb-3">
                <h2 class="register-title">REGISTER</h2>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="register-form">
                <div class="form-group">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                    </div>
                    <span class="text-danger"><?php echo $username_err; ?></span>
                </div>
                <div class="form-group">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    </div>
                    <span class="text-danger"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
                    </div>
                    <span class="text-danger"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                        </div>
                        <select class="form-control" name="gender" id="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <span class="text-danger"><?php echo $gender_err; ?></span>
                </div>
                    <button type="submit" class="btn btn-primary btn-block btn-register" name="register">Register</button>
                </form>
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Log in here</a></p>
                </div>
                <hr>
                <div class="register-footer">
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