<?php
// Database connection settings
require_once 'db_connection_settings.php';

try {
    // Connect to MySQL database using PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start session
    session_start();
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$username = $loggedIn ? $_SESSION['username'] : '';

// Check if user is an admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Logout function
if (isset($_POST['logout'])) {
    session_destroy();
    // Redirect to index.php after logout
    header("Location: index.php");
    exit();
}

if (isset($_POST['logout_EMS'])) {
    session_destroy();
    // Redirect to index.php after logout
    header("Location: ../index.php");
    exit();
}

// WILL PREVENT GOING TO OTHER PAGES IF NOT LOGGED IN
// Redirect to index.php if user is not logged in
/* 
if (!$loggedIn && !strpos($_SERVER['REQUEST_URI'], 'index.php')) {
    header("Location: ../index.php");
    exit();
} 
*/

if ($loggedIn) {
    $userId = $_SESSION['user_id'];
    $queryUser = "SELECT * FROM users WHERE id = :id";
    $stmtUser = $pdo->prepare($queryUser);
    $stmtUser->bindParam(':id', $userId);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (basename($_SERVER['REQUEST_URI']) == 'index.php') {
        if (!$user) {
            // User no longer exists in the database
            session_destroy();
            header("Location: index.php");
            exit();
        }
        
        // Check if the user is suspended
        if (!$user['is_active'] && !strpos($_SERVER['REQUEST_URI'], 'suspended.php')) {
            header("Location: EMS/suspended.php");
            exit();
        }
    } else {
        if (!$user) {
            // User no longer exists in the database
            session_destroy();
            header("Location: ../index.php");
            exit();
        }
        
        // Check if the user is suspended
        if (!$user['is_active'] && !strpos($_SERVER['REQUEST_URI'], 'suspended.php')) {
            header("Location: ../EMS/suspended.php");
            exit();
        }
    }
    

    // Flag to check if session data was updated
    $sessionUpdated = false;

    // Update session if user details changed
    if (isset($_SESSION['username']) && $_SESSION['username'] !== $user['username']) {
        $_SESSION['username'] = $user['username'];
        $sessionUpdated = true;
    }
    if (isset($_SESSION['email']) && $_SESSION['email'] !== $user['email']) {
        $_SESSION['email'] = $user['email'];
        $sessionUpdated = true;
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] !== $user['role']) {
        $_SESSION['role'] = $user['role'];
        $sessionUpdated = true;
    }
    if (isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== $user['profile_picture']) {
        $_SESSION['profile_picture'] = $user['profile_picture'];
        $sessionUpdated = true;
    }
    if (isset($_SESSION['can_request_event']) && $_SESSION['can_request_event'] !== $user['can_request_event']) {
        $_SESSION['can_request_event'] = $user['can_request_event'];
        $sessionUpdated = true;
    }
    if (isset($_SESSION['can_review_request']) && $_SESSION['can_review_request'] !== $user['can_review_request']) {
        $_SESSION['can_review_request'] = $user['can_review_request'];
        $sessionUpdated = true;
    }
    if (isset($_SESSION['can_delete_user']) && $_SESSION['can_delete_user'] !== $user['can_delete_user']) {
        $_SESSION['can_delete_user'] = $user['can_delete_user'];
        $sessionUpdated = true;
    }

    // Log out user if their account is deleted
    if ($stmtUser->rowCount() === 0) {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    // Refresh the page only if session data was updated
    if ($sessionUpdated) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
