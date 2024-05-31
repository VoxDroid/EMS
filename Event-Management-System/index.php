<?php
require_once 'PARTS/background_worker.php';
require_once 'PARTS/config.php';

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
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Current page (default: 1)

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management System</title>

    <!-- CSS.PHP -->
    <?php require 'PARTS/CSS.php'; ?>
    <?php require 'ASSETS/CSS/pagination_cards.css'; ?>

    <!-- Internal CSS -->
    <style>
    /* Event Cards */
    .event-card {
        border: none;
        transition: box-shadow 0.3s;
        border-radius: 10px;
    }
    .event-card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .card-body {
        padding: 20px;
    }
    .profile-picture {
        border: 2px solid #fff;
    }
    .event-description {
        color: #555;
        font-size: 0.95rem;
    }
    .like-dislike img {
        margin-right: 5px;
    }
    /* Custom Styling */
    hr {
    opacity: 1;
    }
    .custom-heading {
        align-items: center;
        font-size: 2rem;
        text-decoration: none;
        display: inline-flex;
        position: relative;
        padding: 10px 15px; 
        transition: color 0.3s ease; 
    }
    .custom-heading .bi {
        margin-left: 10px;
        position: relative;
        transition: transform 0.3s ease;
    }
    .custom-heading.white-background {
        color: #161c27;
    }
    .custom-heading.white-background:hover .bi {
        color: #34495e;
        transform: translateX(10px); 
    }
    .custom-heading.white-background:hover {
        color: #34495e;
    }
    .custom-heading.blue-background {
        color: #ffffff;
    }
    .custom-heading.blue-background:hover .bi {
        color: #c0c0c0;
        transform: translateX(10px);
    }
    .custom-heading.blue-background:hover {
        color: #c0c0c0;
    }
    .custom-heading:hover {
        scale: 1.02;
        transition: scale 0.3s;
    }
    /* Carousel Controls */
    .carousel-control-prev,
    .carousel-control-next {
        color: #fff;
        background-color: rgba(23, 34, 47, 0.8);
        width: 50px;
        height: 50px;
        border: 2px solid rgba(200, 200, 255, 0.5);
        border-radius: 50%;
        font-size: 24px;
        line-height: 50px;
        text-align: center;
        position: absolute; 
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.3s ease, border-color 0.3s ease;
        z-index: 10;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }
    .carousel-control-prev {
        left: -110px;
    }
    .carousel-control-next {
        right: -110px;
    }
    .carousel-control-prev:hover,
    .carousel-control-next:hover {
        background-color: rgba(30, 46, 62, 0.8);
        transform: translateY(-50%) scale(1.1);
        border-color: rgba(200, 200, 255, 0.7);
    }
    .carousel-indicators{
        bottom: -50px;
    }
    .carousel-indicators li {
        position: relative;
        bottom: -20px;
        background-color: rgba(170, 178, 189, 0.7);
        border: 2px solid rgba(200, 200, 255, 0.5);
        border-radius: 50%;
        width: 12px;
        height: 12px;
        margin: 0 5px;
        cursor: pointer;
        list-style: none;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .carousel-indicators li.active {
        background-color: rgba(39, 52, 71, 0.8);
        border-color: rgba(200, 200, 255, 0.7);
    }
    /* Carousel Control Icons */
    .carousel-control-next-icon,
    .carousel-control-prev-icon {
        width: 20px;
        height: 20px;
    }
    /* Additional Styling for Control Icons */
    .carousel-control-prev-icon::before,
    .carousel-control-next-icon::before {
        font-size: 20px;
        color: #fff;
    }
    </style>
</head>
<body>
    
<!-- Header -->
<?php require 'PARTS/header.php'; ?>
<!-- End Header -->

<!-- Main Content -->
<main class="py-5 flex-grow-1" style="background-color: #1c2331">
    <div class="container">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']); // Clear message after displaying
        }

        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']); // Clear message after displaying
        }
        ?>
        <h2>
            <a href="EMS/events_ongoing.php" class="custom-heading blue-background">
                Ongoing Events
                <i class="bi bi-chevron-right"></i>
            </a>
        </h2>
        <hr style="border: none; height: 4px; background-color: #FFFFFF;">
        <div id="ongoingEventsCarousel" class="carousel slide mb-5" data-ride="carousel">
            <div class="carousel-inner">
                <?php
                // Fetch ongoing events with user information
                $queryOngoingEvents = "SELECT e.*, u.username, u.profile_picture 
                                    FROM events e
                                    JOIN users u ON e.user_id = u.id
                                    WHERE e.status = 'ongoing' 
                                    ORDER BY e.date_requested ASC 
                                    LIMIT 20";
                $stmtOngoingEvents = $pdo->query($queryOngoingEvents);
                $ongoingEventCount = $stmtOngoingEvents->rowCount();

                if ($ongoingEventCount > 0) {
                    $first = true;
                    $slideIndex = 0;
                    while ($event = $stmtOngoingEvents->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="carousel-item' . ($first ? ' active' : '') . '">';
                        echo '<div class="card shadow-sm event-card">';
                        echo '<div class="card-body">';
                        // User profile picture and name
                        echo '<div class="d-flex align-items-center mb-3">';
                        // Adjust profile picture path if it starts with '../'
                        $profilePicture = $event['profile_picture'];
                        if (strpos($profilePicture, '../') === 0) {
                            $profilePicture = substr($profilePicture, 3); // Remove '../'
                        }
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
                        echo '<p class="card-text"><strong>Duration:</strong> ' . $event['duration'] . ' hours</p>';
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
                        echo '<img src="SVG/hand-thumbs-up-fill.svg" alt="Likes" width="16" height="16" class="text-success me-1">';
                        echo '<span class="like-count">' . $event['likes'] . '</span>';
                        echo '</div>';
                        echo '<div class="like-dislike">';
                        echo '<img src="SVG/hand-thumbs-down-fill.svg" alt="Dislikes" width="16" height="16" class="text-danger me-1">';
                        echo '<span class="dislike-count">' . $event['dislikes'] . '</span>';
                        echo '</div>';
                        echo '</div>';
                        // View button
                        echo '<a href="EMS/event_details.php?event_id=' . $event['id'] . '" class="btn btn-primary btn-sm custom-button-ind">View Details</a>';
                        echo '</div>'; // .card-body
                        echo '</div>'; // .card
                        echo '</div>'; // .carousel-item
                        $first = false;
                    }
                } else {
                    echo '<p class="text-white">No ongoing events found.</p>';
                }
                ?>
            </div>
            <?php if ($ongoingEventCount > 1): ?>
                <ol class="carousel-indicators">
                    <?php
                    // Resetting the statement cursor
                    $stmtOngoingEvents->execute();
                    $first = true;
                    while ($event = $stmtOngoingEvents->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li data-target="#ongoingEventsCarousel" data-slide-to="' . $slideIndex . '" class="' . ($first ? 'active' : '') . '"></li>';
                        $first = false;
                        $slideIndex++;
                    }
                    ?>
                </ol>
                <a class="carousel-control-prev custom-carousel-control" href="#ongoingEventsCarousel" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon custom-carousel-control" aria-hidden="true"></span>
                </a>
                <a class="carousel-control-next custom-carousel-control" href="#ongoingEventsCarousel" role="button" data-slide="next">
                    <span class="carousel-control-next-icon custom-carousel-control" aria-hidden="true"></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>
<!-- End Main Content -->

<!-- Approved events -->
<div class="py-5">
    <div class="container">
        <h2>
            <a href="EMS/events_approved.php" class="custom-heading white-background">
                Approved Events
                <i class="bi bi-chevron-right"></i>
            </a>
        </h2>
        <hr style="border: none; height: 4px; background-color: #1c2331;">
        <div id="approvedEventsCarousel" class="carousel slide mb-5" data-ride="carousel">
            <div class="carousel-inner">
                <?php
                // Fetch approved events with user information
                $queryApprovedEvents = "SELECT e.*, u.username, u.profile_picture 
                                    FROM events e
                                    JOIN users u ON e.user_id = u.id
                                    WHERE e.status = 'active' 
                                    ORDER BY e.date_requested ASC 
                                    LIMIT 20";
                $stmtApprovedEvents = $pdo->query($queryApprovedEvents);
                $approvedEventCount = $stmtApprovedEvents->rowCount();

                if ($approvedEventCount > 0) {
                    $first = true;
                    $slideIndex = 0;
                    while ($event = $stmtApprovedEvents->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="carousel-item' . ($first ? ' active' : '') . '">';
                        echo '<div class="card shadow-sm event-card">';
                        echo '<div class="card-body">';
                        // User profile picture and name
                        echo '<div class="d-flex align-items-center mb-3">';
                        // Adjust profile picture path if it starts with '../'
                        $profilePicture = $event['profile_picture'];
                        if (strpos($profilePicture, '../') === 0) {
                            $profilePicture = substr($profilePicture, 3); // Remove '../'
                        }
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
                        echo '<p class="card-text"><strong>Duration:</strong> ' . $event['duration'] . ' hours</p>';
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
                        echo '<img src="SVG/hand-thumbs-up-fill.svg" alt="Likes" width="16" height="16" class="text-success me-1">';
                        echo '<span class="like-count">' . $event['likes'] . '</span>';
                        echo '</div>';
                        echo '<div class="like-dislike">';
                        echo '<img src="SVG/hand-thumbs-down-fill.svg" alt="Dislikes" width="16" height="16" class="text-danger me-1">';
                        echo '<span class="dislike-count">' . $event['dislikes'] . '</span>';
                        echo '</div>';
                        echo '</div>';
                        // View button
                        echo '<a href="EMS/event_details.php?event_id=' . $event['id'] . '" class="btn btn-primary btn-sm custom-button-ind">View Details</a>';
                        echo '</div>'; // .card-body
                        echo '</div>'; // .card
                        echo '</div>'; // .carousel-item
                        $first = false;
                    }
                } else {
                    echo '<p>No approved events found.</p>';
                }
                ?>
            </div>
            <?php if ($approvedEventCount > 1): ?>
                <ol class="carousel-indicators">
                    <?php
                    // Resetting the statement cursor
                    $stmtApprovedEvents->execute();
                    $first = true;
                    while ($event = $stmtApprovedEvents->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li data-target="#approvedEventsCarousel" data-slide-to="' . $slideIndex . '" class="' . ($first ? 'active' : '') . '"></li>';
                        $first = false;
                        $slideIndex++;
                    }
                    ?>
                </ol>
                <a class="carousel-control-prev" href="#approvedEventsCarousel" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </a>
                <a class="carousel-control-next" href="#approvedEventsCarousel" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- End of Approved events -->

<!-- Pending Events -->
<div class="py-5" style="background-color: #1c2331">
    <div class="container">
        <h2>
            <a href="EMS/events_pending.php" class="custom-heading blue-background">
                Pending Events
                <i class="bi bi-chevron-right"></i>
            </a>
        </h2>
        <hr style="border: none; height: 4px; background-color: #FFFFFF;">
        <div class="row">
            <?php
            $queryPendingEventsCount = "SELECT COUNT(*) AS count FROM events WHERE status = 'pending'";
            $stmtPendingEventsCount = $pdo->query($queryPendingEventsCount);
            $pendingEventCount = $stmtPendingEventsCount->fetch(PDO::FETCH_ASSOC)['count'];

            $pendingItemsPerPage = 10;
            $pendingTotalPages = ceil($pendingEventCount / $pendingItemsPerPage);
            $pendingCurrentPage = isset($_GET['pending_page']) ? max(1, intval($_GET['pending_page'])) : 1;
            $pendingOffset = ($pendingCurrentPage - 1) * $pendingItemsPerPage;
            $queryPendingEvents = "SELECT e.*, u.username, u.profile_picture 
                                FROM events e
                                JOIN users u ON e.user_id = u.id
                                WHERE e.status = 'pending' 
                                ORDER BY e.date_requested ASC 
                                LIMIT $pendingOffset, $pendingItemsPerPage";
            $stmtPendingEvents = $pdo->query($queryPendingEvents);

            if ($stmtPendingEvents->rowCount() > 0) {
                while ($event = $stmtPendingEvents->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="col-md-6 mb-4">';
                    echo '<div class="card shadow-sm event-card">';
                    echo '<div class="card-body">';
                    // User profile picture and name
                    echo '<div class="d-flex align-items-center mb-3">';
                    // Adjust profile picture path if it starts with '../'
                    $profilePicture = $event['profile_picture'];
                    if (strpos($profilePicture, '../') === 0) {
                        $profilePicture = substr($profilePicture, 3); // Remove '../'
                    }
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
                    echo '<img src="SVG/hand-thumbs-up-fill.svg" alt="Likes" width="16" height="16" class="text-success me-1">';
                    echo '<span class="like-count">' . $event['likes'] . '</span>';
                    echo '</div>';
                    echo '<div class="like-dislike">';
                    echo '<img src="SVG/hand-thumbs-down-fill.svg" alt="Dislikes" width="16" height="16" class="text-danger me-1">';
                    echo '<span class="dislike-count">' . $event['dislikes'] . '</span>';
                    echo '</div>';
                    echo '</div>';
                    // View button
                    echo '<a href="EMS/event_details.php?event_id=' . $event['id'] . '" class="btn btn-primary btn-sm custom-button-ind">View Details</a>';
                    echo '</div>'; // .card-body
                    echo '</div>'; // .card
                    echo '</div>'; // .col-md-6
                }
            } else {
                echo '<div class="col-md-12">';
                echo '<p class="text-white">No pending events found.</p>';
                echo '</div>';
            }

            // Display pagination controls for pending section
            if ($pendingEventCount > $pendingItemsPerPage) {
                echo '<div class="col-md-12">';
                echo '<nav aria-label="Page navigation example">';
                echo '<ul class="pagination justify-content-center">';
                echo '<li class="page-item ' . ($pendingCurrentPage == 1 ? 'disabled' : '') . '">';
                echo '<a class="page-link custom-page-link" href="?pending_page=' . max(1, $pendingCurrentPage - 1) . '" aria-label="Previous">';
                echo '<span aria-hidden="true">&laquo;</span>';
                echo '<span class="sr-only">Previous</span>';
                echo '</a>';
                echo '</li>';
                for ($i = 1; $i <= $pendingTotalPages; $i++) {
                    echo '<li class="page-item ' . ($pendingCurrentPage == $i ? 'active' : '') . '"><a class="page-link custom-page-link" href="?pending_page=' . $i . '">' . $i . '</a></li>';
                }
                echo '<li class="page-item ' . ($pendingCurrentPage == $pendingTotalPages ? 'disabled' : '') . '">';
                echo '<a class="page-link custom-page-link" href="?pending_page=' . min($pendingTotalPages, $pendingCurrentPage + 1) . '" aria-label="Next">';
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
<!-- End of Pending Events -->

<!-- Archive Section -->
<div class="py-5">
    <div class="container">
    <h2>
            <a href="EMS/events_archived.php" class="custom-heading white-background">
                Archived Events
                <i class="bi bi-chevron-right"></i>
            </a>
        </h2>
    <hr style="border: none; height: 4px; background-color: #1c2331;">
        <div class="row">
            <?php
            // Fetch archive events count
            $queryArchiveEventsCount = "SELECT COUNT(*) AS count FROM events WHERE status IN ('completed', 'denied')";
            $stmtArchiveEventsCount = $pdo->query($queryArchiveEventsCount);
            $archiveEventCount = $stmtArchiveEventsCount->fetch(PDO::FETCH_ASSOC)['count'];

            // Define items per page
            $archiveItemsPerPage = 10;

            // Calculate total pages for archive section
            $archiveTotalPages = ceil($archiveEventCount / $archiveItemsPerPage);

            // Fetch archive events with pagination
            $archiveCurrentPage = isset($_GET['archive_page']) ? max(1, intval($_GET['archive_page'])) : 1;
            $archiveOffset = ($archiveCurrentPage - 1) * $archiveItemsPerPage;
            $queryArchiveEvents = "SELECT e.*, u.username, u.profile_picture 
                                FROM events e
                                JOIN users u ON e.user_id = u.id
                                WHERE e.status IN ('completed', 'denied') 
                                ORDER BY e.date_requested ASC 
                                LIMIT $archiveOffset, $archiveItemsPerPage";
            $stmtArchiveEvents = $pdo->query($queryArchiveEvents);

            if ($stmtArchiveEvents->rowCount() > 0) {
                while ($event = $stmtArchiveEvents->fetch(PDO::FETCH_ASSOC)) {
                    echo '<div class="col-md-6 mb-4">';
                    echo '<div class="card shadow-sm event-card">';
                    echo '<div class="card-body">';
                    // User profile picture and name
                    echo '<div class="d-flex align-items-center mb-3">';
                    // Adjust profile picture path if it starts with '../'
                    $profilePicture = $event['profile_picture'];
                    if (strpos($profilePicture, '../') === 0) {
                        $profilePicture = substr($profilePicture, 3); // Remove '../'
                    }
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
                    echo '<p class="card-text"><strong>Duration:</strong> ' . $event['duration'] . ' hours</p>';
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
                    echo '<img src="SVG/hand-thumbs-up-fill.svg" alt="Likes" width="16" height="16" class="text-success me-1">';
                    echo '<span class="like-count">' . $event['likes'] . '</span>';
                    echo '</div>';
                    echo '<div class="like-dislike">';
                    echo '<img src="SVG/hand-thumbs-down-fill.svg" alt="Dislikes" width="16" height="16" class="text-danger me-1">';
                    echo '<span class="dislike-count">' . $event['dislikes'] . '</span>';
                    echo '</div>';
                    echo '</div>';
                    // View button
                    echo '<a href="EMS/event_details.php?event_id=' . $event['id'] . '" class="btn btn-primary btn-sm custom-button-ind">View Details</a>';
                    echo '</div>'; // .card-body
                    echo '</div>'; // .card
                    echo '</div>'; // .col-md-6
                }
            } else {
                echo '<div class="col-md-12">';
                echo '<p>No archived events found.</p>';
                echo '</div>';
            }

            // Display pagination controls for archive section
            if ($archiveEventCount > $archiveItemsPerPage) {
                echo '<div class="col-md-12">';
                echo '<nav aria-label="Page navigation example">';
                echo '<ul class="pagination justify-content-center">';
                echo '<li class="page-item ' . ($archiveCurrentPage == 1 ? 'disabled' : '') . '">';
                echo '<a class="page-link custom-page-link" href="?archive_page=' . max(1, $archiveCurrentPage - 1) . '" aria-label="Previous">';
                echo '<span aria-hidden="true">&laquo;</span>';
                echo '<span class="sr-only">Previous</span>';
                echo '</a>';
                echo '</li>';
                for ($i = 1; $i <= $archiveTotalPages; $i++) {
                    echo '<li class="page-item ' . ($archiveCurrentPage == $i ? 'active' : '') . '"><a class="page-link custom-page-link" href="?archive_page=' . $i . '">' . $i . '</a></li>';
                }
                echo '<li class="page-item ' . ($archiveCurrentPage == $archiveTotalPages ? 'disabled' : '') . '">';
                echo '<a class="page-link custom-page-link" href="?archive_page=' . min($archiveTotalPages, $archiveCurrentPage + 1) . '" aria-label="Next">';
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
<!-- End of Archive Section -->

<!-- Footer -->
<?php require 'PARTS/footer.php'; ?>

<!-- JS.PHP -->
<?php require 'PARTS/JS.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const customHeadings = document.querySelectorAll('.custom-heading');

        customHeadings.forEach(function(customHeading) {
            const chevronIcon = customHeading.querySelector('.bi');

            customHeading.addEventListener('mouseenter', function () {
                chevronIcon.style.animation = 'moveLeftRight 0.5s ease infinite alternate';
            });

            customHeading.addEventListener('mouseleave', function () {
                chevronIcon.style.animation = 'none';
            });
        });
    });
</script>
</body>
</html>
