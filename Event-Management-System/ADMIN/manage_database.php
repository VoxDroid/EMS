<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';
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

// PHP Code For Backup
require '../PARTS/managedb_backup.php';

// PHP Code for Restoration
require '../PARTS/managedb_restore.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Database</title>

    <!-- CSS.PHP -->
    <?php require_once '../PARTS/CSS.php'; ?>
    <!-- Internal CSS -->
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
        .custom-button-md {
            background-color: #161c27;
            border-style: none;
        }
        .custom-button-md:hover {
            background-color: #273447;
            border-style: none;
            color:lightcyan;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require '../PARTS/header.php'; ?>
    
    <!-- Navigation Buttons Section -->
    <div class="admin-navigation">
        <a class="nav-button" href="administrator.php"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a>
        <a class="nav-button" href="manage_users.php"><i class="fas fa-users nav-icon"></i> Manage Users</a>
        <a class="nav-button" href="manage_comments.php"><i class="fas fa-comments nav-icon"></i> Manage Comments</a>
        <a class="nav-button" href="manage_events.php"><i class="fas fa-calendar-alt nav-icon"></i> Manage Events</a>
        <a class="nav-button active" href="#"><i class="fas fa-database nav-icon"></i> Database Management</a>
    </div>
    <!-- End Navigation Buttons Section -->

    <!-- Main Content Section -->
    <div class="container py-5 flex-grow-1">
        <?php 
        if (isset($_SESSION['success_message'])) {
        echo "<div class='alert alert-success'>{$_SESSION['success_message']}</div>";
        unset($_SESSION['success_message']); // Clear message after displaying
        }
        if (isset($_SESSION['error_messages']) && !empty($_SESSION['error_messages'])) {
            foreach ($_SESSION['error_messages'] as $errorMessage) {
                echo "<div class='alert alert-danger'>$errorMessage</div>";
            }
            unset($_SESSION['error_messages']); // Clear error messages after displaying
        }
        ?>
        <h2 class="mb-4">Manage Database</h2>
        <hr style="border: none; height: 4px; background-color: #1c2331;">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-black">Backup Database</h5>
                        <p class="card-text text-black">Create a backup of the database for security purposes.</p>
                        <button type="button" class="btn btn-primary custom-button-md" data-bs-toggle="modal" data-bs-target="#backupModal">Backup Now</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-black">Restore Database</h5>
                        <p class="card-text text-black">Restore the database from a previous backup.</p>
                        <button type="button" class="btn btn-primary custom-button-md" data-bs-toggle="modal" data-bs-target="#confirmPasswordModal">Restore Now</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-black">Export Data (Coming Soon)</h5>
                        <p class="card-text text-black">Export data from selected tables to a file.</p>
                        <a href="#" class="btn btn-primary custom-button-md">Export Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-black">Import Data (Coming Soon)</h5>
                        <p class="card-text text-black">Import data from a file into the database.</p>
                        <a href="#" class="btn btn-primary custom-button-md">Import Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-black">Optimize Tables (Coming Soon)</h5>
                        <p class="card-text text-black">Optimize all database tables for better performance.</p>
                        <a href="#" class="btn btn-primary custom-button-md">Optimize Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title text-black">View Database Structure (Coming Soon)</h5>
                        <p class="card-text text-black">View the structure of the database tables.</p>
                        <a href="#" class="btn btn-primary custom-button-md">View Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Main Content Section -->

    <!-- Backup Modal -->
    <div class="modal fade" id="backupModal" tabindex="-1" aria-labelledby="backupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="backupModalLabel">Confirm Database Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="backupDir" class="form-label">Backup Directory</label>
                            <input type="text" class="form-control" id="backupDir" name="backup_directory" value="../db_backups/" required>
                        </div>
                        <div class="mb-3">
                            <label for="backupFilename" class="form-label">Backup Filename</label>
                            <input type="text" class="form-control" id="backupFilename" name="backup_filename" value="backup_<?php echo date('Ymd_His'); ?>.sql" required>
                        </div>
                        <?php if (!empty($backupMessage)) echo $backupMessage; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="backup_database" class="btn btn-primary">Backup Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Backup Modal -->

    <!-- Restore Modal Step 1: Confirm Password -->
    <div class="modal fade" id="confirmPasswordModal" tabindex="-1" aria-labelledby="confirmPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmPasswordModalLabel">Confirm Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="restoreFormStep2" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="userPassword" class="form-label"><strong>Enter Your Account Password:</strong></label>
                            <input type="password" class="form-control" id="userPassword" name="user_password" required>
                            <ul class="mt-3">
                            <li><strong>Note:</strong></li>
                            <p>Enter your account password. It will be used to granting you the permissions to restore your database later.</p>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="confirm_password" class="btn btn-primary" data-bs-target="#confirmPasswordModal" data-bs-toggle="modal">Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore Modal Step 2: Final Confirmation -->
    <div class="modal fade" id="finalConfirmModal" tabindex="-1" aria-labelledby="finalConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalConfirmModalLabel">Final Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="restoreFormStep3" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <p><strong>Warning:</strong> Proceeding with the database restore will initiate a process that may have significant implications for your application's data integrity and availability.</p>

                        <p>Restoring the database involves overwriting all current data with the contents of the selected backup file. This action cannot be undone easily and may result in:</p>

                        <ul>
                            <li><strong>Data Loss:</strong> Any changes made since the backup was created will be lost.</li>
                            <li><strong>Downtime:</strong> During the restore process, your application may be temporarily unavailable to users.</li>
                            <li><strong>System Impact:</strong> Depending on the size of your database and server resources, the restore process could affect overall system performance.</li>
                        </ul>

                        <p>Before proceeding, please ensure:</p>

                        <ul>
                            <li><strong>Backup Verification:</strong> The selected backup file is correct and up-to-date.</li>
                            <li><strong>Current State Consideration:</strong> Understand the current state of your application and the impact a restore will have.</li>
                            <li><strong>Permissions:</strong> You have the necessary administrative privileges and authority to perform this action.</li>
                        </ul>

                        <p><strong>Proceed with caution:</strong> Ensure that you have followed all necessary protocols and considered the implications of restoring the database. If unsure, consult with your team or IT support before continuing.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="final_restore_database">Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore Modal Step 3: Final Confirmation -->
    <div class="modal fade" id="reallyFinalRestoreModal" tabindex="-1" aria-labelledby="finalRestoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalRestoreModalLabel">Choose the backup file:</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="restoreFormStep4" method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="backupFile" class="form-label">Backup File</label>
                                <input type="file" class="form-control" id="backupFile" name="backup_file" required>
                            </div>
                            <?php if (!empty($restoreMessage)) echo $restoreMessage; ?>
                            
                        <p>Please type <strong>RESTORE</strong> to confirm.</p>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="finalConfirmInput" name="final_confirm" required>
                        </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger" name="final_restore_database">Restore now</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require '../PARTS/footer.php'; ?>

    <!-- JS.PHP -->
    <?php require '../PARTS/JS.php'; ?>
    <script>
    $(document).ready(function() {

        // Show final confirm modal on successful password confirmation
        $('#restoreFormStep2').submit(function(event) {
            event.preventDefault(); // Prevent form submission

            userPassword = $('#userPassword').val();
            $('#confirmPasswordModal').modal('hide'); // Hide Confirm Password modal
            $('#finalConfirmModal').modal('show'); // Show Final Confirmation modal
        });

        // Show final confirm modal on successful password confirmation
        $('#restoreFormStep3').submit(function(event) {
            event.preventDefault(); // Prevent form submission
            $('#finalConfirmModal').modal('hide');
            $('#reallyFinalRestoreModal').modal('show'); // Show Final Confirmation modal
        });

        // Validate final restore input before submitting
        $('#restoreFormStep4').submit(function(event) {
            var finalConfirmInput = $('#finalConfirmInput').val().trim();
            if (finalConfirmInput.toUpperCase() !== 'RESTORE') {
                alert('Please type "RESTORE" to confirm.'); // Show alert for incorrect input
                return false; // Prevent form submission
            }
            $('<input />').attr('type', 'hidden')
                      .attr('name', 'user_password')
                      .attr('value', $('#userPassword').val())
                      .appendTo('#restoreFormStep4');
            
            return true; // Allow form submission
        });
    });
    </script>
</body>
</html>