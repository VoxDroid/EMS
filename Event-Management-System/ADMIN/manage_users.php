<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';
ob_start();
// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Redirect to index.php if user is not an admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch all users
$queryUsers = "SELECT * FROM users";
$stmtUsers = $pdo->prepare($queryUsers);
$stmtUsers->execute();
// Process form submission
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>
    <?php require '../ASSETS/CSS/custom_design.css' ?>

    <style>
        .admin-navigation {
            background-color: #161c27;
            display: flex;
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
            justify-content: center;
            padding: 10px 0;
        }
        .nav-button {
            color: #ffffff;
            text-decoration: none;
            padding: 15px;
            margin: 5px; /* Adjusted margin for better spacing */
            border-radius: 8px;
            transition: background-color 0.3s ease;
            display: inline-flex; /* Ensure buttons are in a row */
            align-items: center; /* Center content vertically */
        }
        .nav-button:hover {
            background-color: #273447;
        }
        .nav-icon {
            margin-right: 10px;
        }
        .active {
            background-color: #273447;
        }
        .custom-button-mu {
            background-color: #161c27;
            border: none;
            color: #ffffff;
            padding: 7px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .custom-button-mu:hover {
            background-color: #273447;
            border: none;
            color: #ffffff;
            padding: 7px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .table-title {
            background-color: #161c27;
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            padding: 15px;
            margin: 0;
            border-top-left-radius: 5px; /* Adjusted to have rounded corners on the top-left */
            border-top-right-radius: 5px; 
        }
    </style>
</head>
<body>
<!-- Header -->
<?php require '../PARTS/header.php'; ?>
<!-- End Header -->

<!-- Navigation Buttons Section -->
<div class="admin-navigation">
        <a class="nav-button" href="administrator.php"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a>
        <a class="nav-button active" href="#"><i class="fas fa-users nav-icon"></i> Manage Users</a>
        <a class="nav-button" href="manage_comments.php"><i class="fas fa-comments nav-icon"></i> Manage Comments</a>
        <a class="nav-button" href="manage_events.php"><i class="fas fa-calendar-alt nav-icon"></i> Manage Events</a>
        <a class="nav-button" href="manage_database.php"><i class="fas fa-database nav-icon"></i> Manage Database</a>
    </div>

<!-- Main Content -->
<main class="py-5 flex-grow-1">
<div class="container">
    <?php

    // Check for success message
    if (isset($_SESSION['success_message'])) {
        echo "<div class='alert alert-success'>{$_SESSION['success_message']}</div>";
        unset($_SESSION['success_message']); // Clear message after displaying
    }

    // Check for error message
    if (isset($_SESSION['error_message'])) {
        echo "<div class='alert alert-danger'>{$_SESSION['error_message']}</div>";
        unset($_SESSION['error_message']); // Clear message after displaying
    }

    if (isset($_SESSION['error_messages'])) {
        echo '<div class="alert alert-danger">';
        foreach ($_SESSION['error_messages'] as $error) {
            echo "<p>{$error}</p>";
        }
        echo '</div>';
        unset($_SESSION['error_messages']); // Clear errors after displaying
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
        $userId = $_POST['user_id'];
        
        if ($userId != 1) {
            try {
                // Begin a transaction
                $pdo->beginTransaction();
        
                // Delete associated comments made by the user
                $deleteCommentsQuery = "DELETE FROM comments WHERE user_id = :id";
                $stmtComments = $pdo->prepare($deleteCommentsQuery);
                $stmtComments->bindParam(':id', $userId);
                $stmtComments->execute();
        
                // Delete associated records from comment_votes table
                $deleteCommentVotesQuery = "DELETE FROM comment_votes WHERE user_id = :id";
                $stmtCommentVotes = $pdo->prepare($deleteCommentVotesQuery);
                $stmtCommentVotes->bindParam(':id', $userId);
                $stmtCommentVotes->execute();
        
                // Delete associated records from event_votes table
                $deleteEventVotesQuery = "DELETE FROM event_votes WHERE user_id = :id";
                $stmtEventVotes = $pdo->prepare($deleteEventVotesQuery);
                $stmtEventVotes->bindParam(':id', $userId);
                $stmtEventVotes->execute();
        
                // Delete associated records from events table
                $deleteEventsQuery = "DELETE FROM events WHERE user_id = :id";
                $stmtEvents = $pdo->prepare($deleteEventsQuery);
                $stmtEvents->bindParam(':id', $userId);
                $stmtEvents->execute();
        
                // Finally, delete the user record
                $deleteUserQuery = "DELETE FROM users WHERE id = :id";
                $stmtUser = $pdo->prepare($deleteUserQuery);
                $stmtUser->bindParam(':id', $userId);
                $stmtUser->execute();
                // Commit the transaction
                $pdo->commit();
                $_SESSION['success_message'] = "User deleted successfully.";
                // Deletion successful, redirect to admin page
                header("Location: manage_users.php");
                exit();
            } catch(PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
                header("Location: manage_users.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Cannot delete the default admin account.";
            header('Location: manage_users.php');
            exit();
        }
    }
    

    // Initialize variables
    $errors = [];
    $successMessage = "";


    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
        $userId = $_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];

        // Validate username
        if (!empty(trim($username))) {
            if (strlen(trim($username)) < 3) {
                $_SESSION['error_message'] = "Username must have at least 3 characters.";
                header('Location: manage_users.php');
                exit();
            } elseif (!preg_match('/^[a-zA-Z0-9_]{3,}$/', trim($username))) {
                $_SESSION['error_message'] = "Username can only contain letters, numbers, and underscores.";
                header('Location: manage_users.php');
                exit();
            } else {
                // Check if the username is already taken
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':id', $userId);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $errors[] = "This username is already taken.";
                    $_SESSION['error_message'] = "This username is already taken.";
                    header('Location: manage_users.php');
                    exit();
                }
            }
        }

    // Validate email
    if (!empty(trim($email))) {
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = "Please enter a valid email address.";
            header('Location: manage_users.php');
            exit();
        } else {
            // Check if the email is already registered
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $_SESSION['error_message'] = "This email address is already registered";
                header('Location: manage_users.php');
                exit();
            }
        }
    }

    // Validate role and is_active for non-admin users
    $role = isset($_POST['role']) ? $_POST['role'] : null;
    $isActive = isset($_POST['is_active']) ? ($_POST['is_active'] ? 1 : 0) : null;

    // Handle profile picture actions
    $profileAction = $_POST['profile_action'] ?? '';
    if ($profileAction === 'Upload' && isset($_FILES['profile_picture_upload']) && $_FILES['profile_picture_upload']['error'] === UPLOAD_ERR_OK) {
        // Handle profile picture upload
        $uploadDir = '../UPLOADS/img/USERS/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadFile = $uploadDir . basename($_FILES['profile_picture_upload']['name']);

        // Check if the file is an image
        $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($imageFileType, $allowedExtensions)) {
            $_SESSION['error_message'] = "Only JPG, JPEG, and PNG files are allowed.";
            header('Location: manage_users.php');
            exit();
        } elseif (!move_uploaded_file($_FILES['profile_picture_upload']['tmp_name'], $uploadFile)) {
            $_SESSION['error_message'] = "Error uploading file.";
            header('Location: manage_users.php');
            exit();
        } else {
            // Resize and crop the image to 200x200 square
            $image = imagecreatefromstring(file_get_contents($uploadFile));
            $width = imagesx($image);
            $height = imagesy($image);
            $size = min($width, $height);
            $croppedImage = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
            $resizedImage = imagescale($croppedImage, 200, 200);
            
            // Overwrite the original uploaded file with the resized image
            imagepng($resizedImage, $uploadFile);
            imagedestroy($image);
            imagedestroy($croppedImage);
            imagedestroy($resizedImage);
            
            // Update profile picture path in the database
            $profilePicture = $uploadFile;
            $updateProfilePictureQuery = "UPDATE users SET profile_picture = :profile_picture WHERE id = :id";
            $stmtProfilePicture = $pdo->prepare($updateProfilePictureQuery);
            $stmtProfilePicture->bindParam(':profile_picture', $profilePicture);
            $stmtProfilePicture->bindParam(':id', $userId);
            if (!$stmtProfilePicture->execute()) {
                $_SESSION['error_message'] = "Error updating profile picture.";
                header('Location: manage_users.php');
                exit();
            }
        }
    } elseif ($profileAction === 'Default') {
        // Set default profile picture based on gender
        $queryUser = "SELECT gender FROM users WHERE id = :id";
        $stmtUser = $pdo->prepare($queryUser);
        $stmtUser->bindParam(':id', $userId);
        $stmtUser->execute();
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $gender = strtolower($userData['gender']);
            $defaultProfilePicture = ($gender === 'male') ? '../ASSETS/IMG/DPFP/male.png' : '../ASSETS/IMG/DPFP/female.png';
            
            // Update profile picture path in the database
            $updateProfilePictureQuery = "UPDATE users SET profile_picture = :profile_picture WHERE id = :id";
            $stmtUpdateProfilePicture = $pdo->prepare($updateProfilePictureQuery);
            $stmtUpdateProfilePicture->bindParam(':profile_picture', $defaultProfilePicture);
            $stmtUpdateProfilePicture->bindParam(':id', $userId);
            if (!$stmtUpdateProfilePicture->execute()) {
                $_SESSION['error_message'] = "Error updating profile picture.";
                header('Location: manage_users.php');
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Error: User data not found.";
            header('Location: manage_users.php');
            exit();
        }
    }

    // If no errors, update user details in the database
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET username = :username, email = :email";
        if ($role !== null) {
            $updateQuery .= ", role = :role";
        }
        if ($isActive !== null) {
            $updateQuery .= ", is_active = :is_active";
        }
        $updateQuery .= " WHERE id = :id";

        $stmt = $pdo->prepare($updateQuery);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        if ($role !== null) {
            $stmt->bindParam(':role', $role);
        }
        if ($isActive !== null) {
            $stmt->bindParam(':is_active', $isActive);
        }
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User details updated successfully.";
            header("Location: manage_users.php");
            exit();
            ob_end_flush();
        } else {
            $_SESSION['error_message'] = "Error updating user details.";
            header("Location: manage_users.php");
            exit();
            ob_end_flush();
        }
    }
}

    // Save success message to $_SESSION
    if (!empty($successMessage)) {
        $_SESSION['success_message'] = $successMessage;
    }

    // Save error messages to $_SESSION if they exist
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
    }

        ?>
        <?php
    $limit = 200; // Number of users per page
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page number

    // Count total number of users
    $queryCount = "SELECT COUNT(*) AS total FROM users";
    $stmtCount = $pdo->query($queryCount);
    $totalUsers = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate total pages
    $totalPages = ceil($totalUsers / $limit);

    // Adjust page number if it's out of bounds
    if ($page < 1) {
        $page = 1;
    } elseif ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
    }

    // Calculate the offset for the query
    $offset = ($page - 1) * $limit;

    // Fetch users for the current page
    $queryUsers = "SELECT * FROM users LIMIT :limit OFFSET :offset";
    $stmtUsers = $pdo->prepare($queryUsers);
    $stmtUsers->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmtUsers->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Pagination display logic
    $pagesToShow = 3; // Number of pages to show at a time
    $halfPagesToShow = floor($pagesToShow / 2); // Half of the pages to show

    $startPage = max(1, $page - $halfPagesToShow);
    $endPage = min($totalPages, $page + $halfPagesToShow);

    // Adjust startPage and endPage if they are at the edges
    if ($endPage - $startPage + 1 < $pagesToShow) {
        if ($startPage == 1) {
            $endPage = min($totalPages, $startPage + $pagesToShow - 1);
        } elseif ($endPage == $totalPages) {
            $startPage = max(1, $endPage - $pagesToShow + 1);
        }
    }
    ?>
    <h2>Manage Users</h2>
    <hr style="border: none; height: 4px; background-color: #1c2331;">
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search...">
    
    <div class="table-title" >Users</div>
    <table class="table table-striped table-bordered mb-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user) { ?>
    <tr>
        <td><?php echo htmlspecialchars($user['id']); ?></td>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
        <td><?php echo htmlspecialchars($user['role']); ?></td>
        <td>
            <button class="btn btn-primary custom-button-mu" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $user['id']; ?>" >View</button>
            <button class="btn btn-secondary custom-button-purple" data-bs-toggle="modal" data-bs-target="#manageUserModal<?php echo $user['id']; ?>">Manage</button>
        </td>
    </tr>

            <!-- View User Modal -->
            <div class="modal fade" id="viewUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="viewUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewUserModalLabel<?php echo $user['id']; ?>">View User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if (!empty($user['profile_picture'])) { ?>
                                <p><strong>Profile Picture:</strong></p>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="img-fluid">
                            <?php } ?>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($user['id']);?></p>
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($user['is_active'] ? 'Active' : 'Suspended'); ?></p>
                            <p><strong>Date Created:</strong> <?php echo htmlspecialchars($user['date_created']); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manage User Modal -->
            <div class="modal fade" id="manageUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="manageUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="manageUserModalLabel<?php echo $user['id']; ?>">Manage User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">Profile Picture</label>
                                    <div class="input-group">
                                        <?php 
                                            if (!empty($user['profile_picture'])) {
                                                $profilePictureFileName = basename($user['profile_picture']);
                                                echo '<input type="text" class="form-control mb-2" value="'.$profilePictureFileName.'" readonly>';
                                            }
                                        ?>
                                    </div>
                                    <div class="input-group">
                                    <select class="form-control" id="profile_action" name="profile_action" required>
                                    <option value="Upload" <?php if (!isset($_POST['profile_action']) || (isset($_POST['profile_action']) && $_POST['profile_action'] == 'Upload')) echo 'selected'; ?>>Upload</option>
                                    <option value="Default" <?php if (isset($_POST['profile_action']) && $_POST['profile_action'] == 'Default') echo 'selected'; ?>>Default</option>
                                </select>
                                <?php
                                    // Initially show the upload button and hide the default button
                                    $uploadButtonStyle = '';
                                    $defaultButtonStyle = 'display: none;';

                                    if (isset($_POST['profile_action']) && $_POST['profile_action'] == 'Default') {
                                        $uploadButtonStyle = 'display: none;';
                                        $defaultButtonStyle = '';
                                    }

                                    echo '<input type="file" class="form-control btn btn-primary" id="profile_picture_upload" name="profile_picture_upload" accept=".jpg, .jpeg, .png" style="' . $uploadButtonStyle . '">';
                                    // Output the default button with inline style based on selection
                                    $defaultProfilePicture = ($user['gender'] == 'male') ? '../ASSETS/IMG/DPFP/male.png' : '../ASSETS/IMG/DPFP/female.png';
                                    echo '<input type="text" class="form-control" id="profile_picture_default" name="profile_picture_default" value="'.$defaultProfilePicture.'" readonly style="' . $defaultButtonStyle . '">';
                                ?>
                                    </div>
                                </div>
                            <script>
                                document.getElementById('profile_action').addEventListener('change', function() {
                                    var uploadButton = document.getElementById('profile_picture_upload');
                                    var defaultButton = document.getElementById('profile_picture_default');

                                    if (this.value === 'Upload') {
                                        uploadButton.style.display = 'block';
                                        defaultButton.style.display = 'none';
                                    } else if (this.value === 'Default') {
                                        uploadButton.style.display = 'none';
                                        defaultButton.style.display = 'block';
                                    }
                                });
                            </script>
                        <?php
                        if (!isset($_SESSION['user_id'])) {
                            header("Location: ../index.php");
                            exit();
                        }

                        // Check if user is logged in
                        $loggedIn = isset($_SESSION['user_id']);
                        $username = $loggedIn ? $_SESSION['username'] : '';

                        // Check if user is an admin
                        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
                        $isIdOne = $_SESSION['user_id'] === 1;
                        ?>
                        <?php if ($isAdmin && $user['id'] != 1 && $user['role'] != 'admin' && $_SESSION['user_id'] != $user['id']): ?>
                            <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">                                    
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-control" id="is_active" name="is_active" required>
                                <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <?php elseif ($isIdOne && $user['id'] != 1 && $_SESSION['user_id'] != $user['id']): ?>
                            <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">                                    
                        </div>
                            <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-control" id="is_active" name="is_active" required>
                                <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <?php else: ?>
                            <?php if ($isIdOne): ?>
                        <!-- Disable the role and status fields for the admin user -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">                                    
                        </div>
                        <?php elseif ($isAdmin): ?>
                            <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>                                    
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" disabled>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-control" id="is_active" name="is_active" disabled>
                                <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary" name="update_account">Save Changes</button>
                            <?php if ($isIdOne && $user['id'] != 1 && $_SESSION['user_id'] != $user['id']): ?>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal<?php echo $user['id']; ?>">Delete Account</button>
                            <?php endif; ?>
                        </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteConfirmationModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteConfirmationModalLabel<?php echo $user['id']; ?>">Confirm Account Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Display username for confirmation -->
                            <p>Are you sure you want to delete the account of <?php echo htmlspecialchars($user['username']); ?>?</p>
                        </div>
                        <div class="modal-footer">
                            <!-- Form submission for account deletion -->
                            <form method="post">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-danger" name="delete_account">Delete Account</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-center">
            <!-- Previous Page Link -->
            <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                <a class="page-link custom-page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1" aria-disabled="true">«</a>
            </li>

            <!-- Page Links -->
            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <li class="page-item <?php echo $p == $page ? 'active' : ''; ?>">
                    <a class="page-link custom-page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next Page Link -->
            <li class="page-item <?php echo $page == $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link custom-page-link" href="?page=<?php echo $page + 1; ?>">»</a>
            </li>
        </ul>
    </nav>
</div>
</main>
<!-- End Main Content -->

<!-- Footer -->
<?php require '../PARTS/footer.php'; ?>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.querySelector('.table-striped tbody');
        const allPagination = document.querySelectorAll('.pagination');
        let allRows = []; // Array to store all rows from the table

        // Function to initialize the rows array
        function initializeRows() {
            allRows = Array.from(tableBody.querySelectorAll('tr'));
        }

        // Function to filter rows based on search term
        function filterRows(searchTerm) {
            searchTerm = searchTerm.trim().toLowerCase();

            allRows.forEach(row => {
                const id = row.querySelector('td:nth-child(1)').textContent.trim().toLowerCase();
                const username = row.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.trim().toLowerCase();
                const role = row.querySelector('td:nth-child(4)').textContent.trim().toLowerCase();

                // Check if any of the row's data matches the search term
                if (username.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm) || id.includes(searchTerm)) {
                    row.style.display = ''; // Show the row if it matches
                } else {
                    row.style.display = 'none'; // Hide the row if it doesn't match
                }
            });

            // Toggle pagination visibility based on search term
            if (searchTerm !== '') {
                allPagination.forEach(pagination => {
                    pagination.style.display = 'none'; // Hide pagination when searching
                });
            } else {
                allPagination.forEach(pagination => {
                    pagination.style.display = ''; // Show pagination when no search term
                });
            }
        }

        // Event listener for input changes in search input
        searchInput.addEventListener('input', function () {
            const searchTerm = searchInput.value;
            filterRows(searchTerm);
        });

        // Handle pagination clicks
        allPagination.forEach(pagination => {
            pagination.addEventListener('click', function () {
                // Re-initialize rows array on pagination click
                initializeRows();

                // Get the current search term and filter rows
                const searchTerm = searchInput.value;
                filterRows(searchTerm);
            });
        });

        // Initialize rows array on page load
        initializeRows();
    });
</script>
</body>
</html>

