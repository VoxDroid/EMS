<?php
$backupMessage = "";

if (isset($_POST['backup_database'])) {
    require '../PARTS/db_connection_settings.php';

    // Determine the backup directory based on user input or default
    $backupDir = isset($_POST['backup_directory']) ? $_POST['backup_directory'] : '../db_backups/';

    // Ensure the backup directory exists
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    // Determine the backup filename based on user input or default
    $backupFilename = isset($_POST['backup_filename']) ? $_POST['backup_filename'] : 'backup_' . date('Ymd_His') . '.sql';
    
    // Construct the full path to the backup file
    $backupFile = rtrim($backupDir, '/') . '/' . $backupFilename;

    // Command to execute mysqldump
    $command = "\"mysqldump\" --host={$host} --user={$username} --password={$password} {$dbname} > \"{$backupFile}\"";

    // Execute the command to create backup using mysqldump
    exec($command . ' 2>&1', $output, $returnValue);

    // If mysqldump fails, use the executable path as a fallback
    if ($returnValue !== 0) {
        // Full path to mysqldump.exe
        $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe'; // Adjust this path to match your environment

        // Command to execute using mysqldump.exe
        $command = "\"{$mysqldumpPath}\" --host={$host} --user={$username} --password={$password} {$dbname} > \"{$backupFile}\"";

        // Execute the command to create backup using mysqldump.exe
        exec($command . ' 2>&1', $output, $returnValue);
    }
    
    // Check if backup was successful
    if ($returnValue === 0) {
        $backupMessage = '<div class="alert alert-success" role="alert">Database backup successful.</div>';
        $_SESSION['success_message'] = "Database backup successful.";
        header("Location: manage_database.php");
        exit();
    } else {
        // Prepare error message handling
        $errorMessage = "Database backup failed. Command output:\n" . implode("\n", $output);
        $backupMessage = '<div class="alert alert-danger" role="alert">' . htmlspecialchars($errorMessage) . '</div>';
        $_SESSION['error_messages'][] = $errorMessage;
        header("Location: manage_database.php");
        exit();
    }
}
?>
