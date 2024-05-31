<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];

$errors = []; // Array to store error messages
$successMessage = ""; // Variable to store success message

// Fetch user details
$queryUser = "SELECT * FROM users WHERE id = :id";
$stmtUser = $pdo->prepare($queryUser);
$stmtUser->bindParam(':id', $userId, PDO::PARAM_INT);
$stmtUser->execute();
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_account'])) {
    // Validate and sanitize username
    if (isset($_POST['username'])) {
        $newUsername = trim($_POST['username']);
        if (!empty($newUsername) && !preg_match('/^[a-zA-Z0-9_]{3,}$/', $newUsername)) {
            $errors[] = "Username must be at least 3 characters long and can only contain letters, numbers, and underscores.";
        }
    } else {
        $newUsername = $user['username'];
    }

    // Validate and sanitize password
    if (isset($_POST['password'])) {
        $newPassword = trim($_POST['password']);
        if (!empty($newPassword) && strlen($newPassword) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif (!empty($newPassword)) {
            $newPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        }
    } else {
        $newPassword = $user['password'];
    }

    // Validate and sanitize gender
    $newGender = isset($_POST['gender']) ? $_POST['gender'] : $user['gender'];

    // Validate and sanitize email
    if (isset($_POST['email'])) {
        $newEmail = trim($_POST['email']);
        if (!empty($newEmail) && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
    } else {
        $newEmail = $user['email'];
    }

    // Check if username already exists
    if (!empty($newUsername)) {
        $queryUsernameCheck = "SELECT id FROM users WHERE username = :username AND id != :id";
        $stmtUsernameCheck = $pdo->prepare($queryUsernameCheck);
        $stmtUsernameCheck->bindParam(':username', $newUsername);
        $stmtUsernameCheck->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmtUsernameCheck->execute();
        $existingUsername = $stmtUsernameCheck->fetch(PDO::FETCH_ASSOC);
        if ($existingUsername) {
            $errors[] = "Username '$newUsername' is already taken.";
        }
    }

    // Check if email already exists
    if (!empty($newEmail)) {
        $queryEmailCheck = "SELECT id FROM users WHERE email = :email AND id != :id";
        $stmtEmailCheck = $pdo->prepare($queryEmailCheck);
        $stmtEmailCheck->bindParam(':email', $newEmail);
        $stmtEmailCheck->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmtEmailCheck->execute();
        $existingEmail = $stmtEmailCheck->fetch(PDO::FETCH_ASSOC);
        if ($existingEmail) {
            $errors[] = "Email '$newEmail' is already registered.";
        }
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../UPLOADS/img/USERS/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $uploadFile = $uploadDir . basename($_FILES['profile_picture']['name']);
        $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($imageFileType, $allowedExtensions)) {
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
                // Resize and crop the image to 200x200 square
                $image = imagecreatefromstring(file_get_contents($uploadFile));
                $width = imagesx($image);
                $height = imagesy($image);
                $size = min($width, $height);
                $croppedImage = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
                $resizedImage = imagescale($croppedImage, 200, 200);

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
                $stmtProfilePicture->execute();
                $successMessage = "User profile updated successfully.";
                if (!empty($successMessage)) {
                    $_SESSION['success_message'] = $successMessage;
                }
                header('Location: profile.php');
                exit();
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        } else {
            $errors[] = "Profile picture must be a JPG, JPEG, or PNG file.";
        }
    } elseif (isset($_POST['set_default_picture'])) {
        // Set default profile picture based on gender
        $defaultProfilePicture = ($user['gender'] === 'female') ? '../ASSETS/IMG/DPFP/female.png' : '../ASSETS/IMG/DPFP/male.png';

        // Update profile picture path in the database
        $updateProfilePictureQuery = "UPDATE users SET profile_picture = :profile_picture WHERE id = :id";
        $stmtProfilePicture = $pdo->prepare($updateProfilePictureQuery);
        $stmtProfilePicture->bindParam(':profile_picture', $defaultProfilePicture);
        $stmtProfilePicture->bindParam(':id', $userId);
        $stmtProfilePicture->execute();
    } elseif (isset($_POST['remove_picture'])) {
        // Set default profile picture based on gender
        $defaultProfilePicture = ($user['gender'] === 'female') ? '../ASSETS/IMG/DPFP/female.png' : '../ASSETS/IMG/DPFP/male.png';

        // Update profile picture path in the database
        $updateProfilePictureQuery = "UPDATE users SET profile_picture = :profile_picture WHERE id = :id";
        $stmtProfilePicture = $pdo->prepare($updateProfilePictureQuery);
        $stmtProfilePicture->bindParam(':profile_picture', $defaultProfilePicture);
        $stmtProfilePicture->bindParam(':id', $userId);
        $stmtProfilePicture->execute();
        $successMessage = "User profile picture removed successfully.";
        if (!empty($successMessage)) {
            $_SESSION['success_message'] = $successMessage;
        }
        header('Location: profile.php');
        exit();
    }

    // If there are no errors, proceed with updating user details
    if (empty($errors)) {
        // Update user details in the database
        $updateQuery = "UPDATE users SET username = :username, password = :password, gender = :gender, email = :email WHERE id = :id";
        $stmt = $pdo->prepare($updateQuery);
        $stmt->bindParam(':username', $newUsername);
        $stmt->bindParam(':password', $newPassword);
        $stmt->bindParam(':gender', $newGender);
        $stmt->bindParam(':email', $newEmail);
        $stmt->bindParam(':id', $userId);

        if ($stmt->execute()) {
            // Set success message
            $successMessage = "User details updated successfully.";
        } else {
            $errors[] = "Error updating user.";
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>
    <style>
        hr {
        opacity: 1;
        }   
        .password-toggle-icon {
            position: absolute;
            top: 75%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .submit-btn {
            background-color: #161c27;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background-color: #0d1117;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: scale 00.3s;
            scale: 1.05;
        }
    </style>
</head>
<body>
<!-- Header -->
<?php require '../PARTS/header.php'; ?>
<!-- End Header -->

<!-- Main Content -->
<main class="py-5 flex-grow-1">
    <div class="container mt-5">
        <?php
        // Display errors and success message at the top of the page
        if (isset($_SESSION['error_messages'])) {
            echo '<div class="alert alert-danger">';
            foreach ($_SESSION['error_messages'] as $error) {
                echo "<p>{$error}</p>";
            }
            echo '</div>';
            unset($_SESSION['error_messages']); // Clear errors after displaying
        }

        // Check for success message
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']); // Clear message after displaying
        }
        ?>
        <h2>My Profile</h2>
        <hr style="border: none; height: 4px; background-color: #1c2331;">
        <form id="profileForm" method="post" enctype="multipart/form-data">
        <div class="mb-3 text-center">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="img-thumbnail" style="width: 150px; height: 150px;">
                <?php else: ?>
                    <p>N/A</p>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" minlength="3">
            </div>
            <div class="mb-3 position-relative">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" minlength="8">
                <span class="password-toggle-icon bi bi-eye-slash" onclick="togglePasswordVisibility('password')"></span>
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-control" id="gender" name="gender">
                    <option value="male" <?php echo $user['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $user['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture</label>
                <input type="file" class="form-control" id="profile_picture" name="profile_picture">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remove_picture" name="remove_picture" onchange="handleRemovePicture()">
                <label class="form-check-label" for="remove_picture">Remove Profile Picture</label>
            </div>
            <input type="hidden" name="update_account" value="1">
            <!-- Button to trigger modal -->
            <button type="button" class="btn btn-primary submit-btn" data-bs-toggle="modal" data-bs-target="#confirmModal">Save Changes</button>

            <!-- Save Changes Confirmation Modal -->
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">Confirm Save Changes</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to save the changes?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary submit-btn" id="confirmSaveButton">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<!-- Footer -->
<?php require '../PARTS/footer.php'; ?>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>
<script>
    function togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        const icon = passwordInput.nextElementSibling;

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        } else {
            passwordInput.type = "password";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    }


    function handleRemovePicture() {
        var removeCheckbox = document.getElementById('remove_picture');
        var uploadInput = document.getElementById('profile_picture');

        if (removeCheckbox.checked) {
            // Disable the upload input and clear any selected files
            uploadInput.disabled = true;
            uploadInput.value = '';
        } else {
            // Enable the upload input
            uploadInput.disabled = false;
        }
    }
</script>
</body>
</html>
