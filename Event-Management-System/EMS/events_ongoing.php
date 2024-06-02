<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';

// Function to calculate total number of pages
function getTotalPages($totalItems, $itemsPerPage) {
    return ceil($totalItems / $itemsPerPage);
}

// Function to fetch events with pagination
function fetchEventsWithPagination($pdo, $query, $currentPage, $itemsPerPage) {
    $offset = ($currentPage - 1) * $itemsPerPage;
    $query .= " LIMIT $offset, $itemsPerPage";
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
}

// Pagination variables
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Current page (default: 1)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ongoing Events</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>
    <?php require '../ASSETS/CSS/custom_design.css'; ?>
    <style>
        body {
            background-color: #405164;
        }
    </style>
</head>
<body>
    <!-- Header -->
<?php require '../PARTS/header.php'; ?>

<!-- Ongoing Events -->
<div class="py-5 flex-grow-1" style="background-color: #405164">
    <div class="container">
        <h2 class="text-white">Ongoing Events</h2>
        <hr style="border: none; height: 4px; background-color: #FFFFFF;">
        <div class="row">
            <?php
            // Fetch ongoing events count
            $queryOngoingEventsCount = "SELECT COUNT(*) AS count FROM events WHERE status = 'ongoing'";
            $stmtOngoingEventsCount = $pdo->query($queryOngoingEventsCount);
            $ongoingEventCount = $stmtOngoingEventsCount->fetch(PDO::FETCH_ASSOC)['count'];

            // Define items per page for ongoing section
            $ongoingItemsPerPage = 50;

            // Calculate total pages for ongoing section
            $ongoingTotalPages = ceil($ongoingEventCount / $ongoingItemsPerPage);

            // Fetch ongoing events with pagination
            $ongoingCurrentPage = isset($_GET['ongoing_page']) ? max(1, intval($_GET['ongoing_page'])) : 1;
            $ongoingOffset = ($ongoingCurrentPage - 1) * $ongoingItemsPerPage;
            $queryOngoingEvents = "SELECT e.*, u.username, u.profile_picture 
                                FROM events e
                                JOIN users u ON e.user_id = u.id
                                WHERE e.status = 'ongoing' 
                                ORDER BY e.date_requested ASC 
                                LIMIT $ongoingOffset, $ongoingItemsPerPage";
            $stmtOngoingEvents = $pdo->query($queryOngoingEvents);

            if ($stmtOngoingEvents->rowCount() > 0) {
                while ($event = $stmtOngoingEvents->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="col-md-6 mb-4">';
                    echo '<div class="card shadow-sm event-card">';
                    echo '<div class="card-body">';
                    // User profile picture and name
                    echo '<div class="d-flex align-items-center mb-3">';
                    // Adjust profile picture path if it starts with '../'
                    $profilePicture = $event['profile_picture'];
                    echo '<img src="' . $profilePicture . '" class="rounded-circle me-3 profile-picture" width="50" height="50" alt="Profile Picture">';
                    echo '<div>';
                    echo '<h5 class="card-title mb-0">' . htmlspecialchars($event['title']) . '</h5>';
                    echo '<p class="card-text text-muted mb-1">Organized by: ' . htmlspecialchars($event['username']) . '</p>';
                    echo '<p class="card-text text-muted mb-0">Date: ' . date('M d, Y', strtotime($event['date_requested'])) . '</p>';
                    echo '</div>';
                    echo '</div>';
                    // Event details
                    echo '<p class="card-text event-description">' . nl2br(htmlspecialchars($event['description'])) . '</p>';
                    // Additional event information
                    echo '<div class="row mb-3">';
                    echo '<div class="col-md-6">';
                    echo '<p class="card-text"><strong>Duration:</strong> ' . htmlspecialchars($event['duration']) . ' hours</p>';
                    echo '<p class="card-text"><strong>Location:</strong> ' . htmlspecialchars($event['facility']) . '</p>';
                    echo '</div>';
                    echo '<div class="col-md-6">';
                    echo '<p class="card-text"><strong>Status:</strong> ' . ucfirst($event['status']) . '</p>';
                    echo '<p class="card-text"><strong>Remarks:</strong> ' . ($event['remarks'] ? htmlspecialchars($event['remarks']) : 'None') . '</p>';
                    echo '</div>';
                    echo '</div>';
                    // Likes and dislikes icons and numbers
                    echo '<div class="d-flex align-items-center mb-3">';
                    echo '<div class="like-dislike me-4">';
                    echo '<img src="../SVG/hand-thumbs-up-fill.svg" alt="Likes" width="16" height="16" class="text-success me-1">';
                    echo '<span class="like-count">' . $event['likes'] . '</span>';
                    echo '</div>';
                    echo '<div class="like-dislike">';
                    echo '<img src="../SVG/hand-thumbs-down-fill.svg" alt="Dislikes" width="16" height="16" class="text-danger me-1">';
                    echo '<span class="dislike-count">' . $event['dislikes'] . '</span>';
                    echo '</div>';
                    echo '</div>';
                    // View button
                    echo '<a href="event_details.php?event_id=' . $event['id'] . '" class="btn btn-primary btn-sm custom-button-ind">View Details</a>';
                    echo '</div>'; // .card-body
                    echo '</div>'; // .card
                    echo '</div>'; // .col-md-6
                }
            } else {
                echo '<div class="no-events-found">';
                echo '<div class="no-events-content">';
                echo '<h4>No Ongoing Events</h4>';
                echo '<p>It seems quiet around here. Check back later or explore other sections of our site!</p>';
                echo '<div class="no-events-animation">';
                echo '<div class="no-events-circle"></div>';
                echo '<div class="no-events-circle"></div>';
                echo '<div class="no-events-circle"></div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }

            // Display pagination controls for ongoing section
            if ($ongoingEventCount > $ongoingItemsPerPage) {
                echo '<div class="col-md-12">';
                echo '<nav aria-label="Page navigation example">';
                echo '<ul class="pagination justify-content-center">';
                echo '<li class="page-item ' . ($ongoingCurrentPage == 1 ? 'disabled' : '') . '">';
                echo '<a class="page-link custom-page-link" href="?ongoing_page=' . max(1, $ongoingCurrentPage - 1) . '" aria-label="Previous">';
                echo '<span aria-hidden="true">&laquo;</span>';
                echo '<span class="sr-only">Previous</span>';
                echo '</a>';
                echo '</li>';
                for ($i = 1; $i <= $ongoingTotalPages; $i++) {
                    echo '<li class="page-item ' . ($ongoingCurrentPage == $i ? 'active' : '') . '"><a class="page-link custom-page-link" href="?ongoing_page=' . $i . '">' . $i . '</a></li>';
                }
                echo '<li class="page-item ' . ($ongoingCurrentPage == $ongoingTotalPages ? 'disabled' : '') . '">';
                echo '<a class="page-link custom-page-link" href="?ongoing_page=' . min($ongoingTotalPages, $ongoingCurrentPage + 1) . '" aria-label="Next">';
                echo '<span aria-hidden="true">&raquo;</span>';
                echo '<span class="sr-only">Next</span>';
                echo '</a>';
                echo '</li>';
                echo '</ul>';
                echo '</nav>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>
<!-- End of Ongoing Events -->

<!-- Footer -->
<?php require '../PARTS/footer.php'; ?>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>

</body>
</html>
