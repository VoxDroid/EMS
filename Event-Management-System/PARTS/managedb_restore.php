<?php
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$restoreMessage = "";

// Step 3: Final confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_restore_database'])) {
    require '../PARTS/db_connection_settings.php';
    // Check if user is logged in and is an admin
    if (!$loggedIn || !$isAdmin) {
        $_SESSION['error_messages'][] = "You must be logged in as an admin to perform this action.";
        header("Location: manage_database.php");
        exit();
    }

    $userPassword = $_POST['user_password'];
    $backupFile = $_FILES['backup_file'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
    $username_2 = $_SESSION['username'];
    $stmt->execute(['username' => $username_2]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($user) {
        // Verify the user's password
        if (password_verify($userPassword, $user['password'])) {
            $_SESSION['password_confirmed'] = true;
        } else {
            $_SESSION['error_messages'][] = "Password incorrect.";
            $_SESSION['password_confirmed'] = false;
            header("Location: manage_database.php");
            exit();
        }
    } else {
        $_SESSION['error_messages'][] = "User not found."; // Handle case where user is not found
        header("Location: manage_database.php");
        exit();
    }

    if (!isset($_SESSION['password_confirmed']) || $_SESSION['password_confirmed'] !== true) {
        $_SESSION['error_messages'][] = "Password confirmation required. 22";
        header("Location: manage_database.php");
        exit();
    }

    if (isset($_SESSION['password_confirmed']) && $_SESSION['password_confirmed'] === true) {
        // Ensure the user typed "RESTORE"
        if ($_POST['final_confirm'] === 'RESTORE') {
            // Ensure a file was uploaded
            if (!empty($_FILES['backup_file']['tmp_name']) && is_uploaded_file($_FILES['backup_file']['tmp_name'])) {
                $backupFile = $_FILES['backup_file']['tmp_name'];


                // Command to execute mysql to restore database
                $command = "\"mysql\" --host={$host} --user={$username} --password={$password} {$dbname} < \"{$backupFile}\"";

                // Execute the command to restore database
                exec($command . ' 2>&1', $output, $returnValue);

                // If mysql restore fails, use the executable path as a fallback (e.g., for Windows)
                if ($returnValue !== 0) {
                    // Full path to mysql.exe
                    $mysqlPath = 'C:\xampp\mysql\bin\mysql.exe'; // Adjust this path to match your environment

                    // Command to execute using mysql.exe
                    $command = "\"{$mysqlPath}\" --host={$host} --user={$username} --password={$password} {$dbname} < \"{$backupFile}\"";

                    // Execute the command to restore database using mysql.exe
                    exec($command . ' 2>&1', $output, $returnValue);
                }

                // Check if restore was successful
                if ($returnValue === 0) {
                    $restoreMessage = '<div class="alert alert-success" role="alert">Database restore successful.</div>';
                    $_SESSION['success_message'] = "Database restore successful.";
                    unset($_SESSION['password_confirmed']); // Clear message after displaying
                    header("Location: manage_database.php");
                    exit();
                } else {
                    // Prepare error message handling
                    $errorMessage = "Database restore failed. Command output:\n" . implode("\n", $output);
                    $restoreMessage = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($errorMessage) . '</div>';
                    $_SESSION['error_messages'][] = $errorMessage;
                    unset($_SESSION['password_confirmed']); // Clear message after displaying
                    header("Location: manage_database.php");
                    exit();
                }
            } else {
                // Handle case where no file was uploaded
                $restoreMessage = '<div class="alert alert-danger" role="alert">No backup file uploaded.</div>';
                $_SESSION['error_messages'][] = "No backup file uploaded.";
                unset($_SESSION['password_confirmed']); // Clear message after displaying
                header("Location: manage_database.php");
                exit();
            }
        } else {
            $_SESSION['error_messages'][] = "Final confirmation text incorrect.";
            unset($_SESSION['password_confirmed']); // Clear message after displaying
            header("Location: manage_database.php");
            exit();
        }
    } else {
        $_SESSION['error_messages'][] = "Password confirmation required.";
        unset($_SESSION['password_confirmed']); // Clear message after displaying
        header("Location: manage_database.php");
        exit();
    }
}
?>