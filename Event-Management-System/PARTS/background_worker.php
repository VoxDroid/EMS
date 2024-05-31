<?php
function updateEventStatus($pdo) {
    // Get the current date
    $currentDate = date('Y-m-d');

    // Update events that have started but not yet completed to "ongoing"
    $updateOngoingQuery = "UPDATE events SET status = 'ongoing' WHERE status = 'active' AND event_start <= ? AND event_end >= ?";
    $stmtOngoing = $pdo->prepare($updateOngoingQuery);
    $stmtOngoing->execute([$currentDate, $currentDate]);

    // Update events that have ended to "completed"
    $updateCompletedQuery = "UPDATE events SET status = 'completed' WHERE status = 'ongoing' AND event_end < ?";
    $stmtCompleted = $pdo->prepare($updateCompletedQuery);
    $stmtCompleted->execute([$currentDate]);
}
require_once 'config.php';

try {
    updateEventStatus($pdo);
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
