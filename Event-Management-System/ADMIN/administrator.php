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
function fetchSingleValue($pdo, $query) {
    $stmt = $pdo->query($query);
    return $stmt->fetchColumn();
}

// User Statistics
$totalUsers = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users");
$activeUsers = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE is_active = 1");
$suspendedUsers = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE is_active = 0");
$totalAdmins = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE role = 'admin'");
$maleUsers = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE gender = 'male'");
$femaleUsers = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE gender = 'female'");
$usersCanRequestEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE can_request_event = TRUE");
$usersCanReviewRequests = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE can_review_request = TRUE");
$usersCanDeleteUsers = fetchSingleValue($pdo, "SELECT COUNT(*) FROM users WHERE can_delete_user = TRUE");

// Event Statistics
$totalEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM events");
$pendingEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM events WHERE status = 'pending'");
$activeEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM events WHERE status = 'active'");
$deniedEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM events WHERE status = 'denied'");
$ongoingEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM events WHERE status = 'ongoing'");
$completedEvents = fetchSingleValue($pdo, "SELECT COUNT(*) FROM events WHERE status = 'completed'");
$averageEventDuration = fetchSingleValue($pdo, "SELECT AVG(duration) FROM events");

// Comment Statistics
$totalComments = fetchSingleValue($pdo, "SELECT COUNT(*) FROM comments");
$averageCommentsPerEvent = fetchSingleValue($pdo, "SELECT AVG((SELECT COUNT(*) FROM comments WHERE event_id = events.id)) FROM events");

// Engagement Statistics
$totalLikes = fetchSingleValue($pdo, "SELECT SUM(likes) FROM events");
$totalDislikes = fetchSingleValue($pdo, "SELECT SUM(dislikes) FROM events");
$totalCommentLikes = fetchSingleValue($pdo, "SELECT COUNT(*) FROM comment_votes WHERE vote_type = 'like'");
$totalCommentDislikes = fetchSingleValue($pdo, "SELECT COUNT(*) FROM comment_votes WHERE vote_type = 'dislike'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <!-- Custom CSS -->
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
        .admin-statistics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .statistics-card {
            border: none;
            border-radius: 10px;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        .statistics-card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #161c27;
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
            padding: 15px;
        }
        .card-body {
            padding: 20px;
        }
        .statistics-list {
            list-style: none;
            padding: 0;
        }
        .statistics-list-item {
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .statistics-list-item:last-child {
            border-bottom: none;
        }
        .statistics-list-item span {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require '../PARTS/header.php'; ?>
    <!-- End Header -->
    <!-- Navigation Buttons Section -->
    <div class="admin-navigation">
        <a class="nav-button active" href="#"><i class="fas fa-tachometer-alt nav-icon"></i> Dashboard</a>
        <a class="nav-button" href="manage_users.php"><i class="fas fa-users nav-icon"></i> Manage Users</a>
        <a class="nav-button" href="manage_comments.php"><i class="fas fa-comments nav-icon"></i> Manage Comments</a>
        <a class="nav-button" href="manage_events.php"><i class="fas fa-calendar-alt nav-icon"></i> Manage Events</a>
        <a class="nav-button" href="manage_database.php"><i class="fas fa-database nav-icon"></i> Manage Database</a>
    </div>


    <div class="container-fluid py-5 flex-grow-1">
    <div class="container"><h2>Dashboard</h2>
    <hr style="border: none; height: 4px; background-color: #1c2331;">
    </div>
    <div class="container admin-statistics">
        <!-- User Statistics Card -->
        <div class="card statistics-card">
            <div class="card-header">User Statistics</div>
            <div class="card-body">
                <ul class="statistics-list">
                    <li class="statistics-list-item"><span>Total Users:</span> <?php echo $totalUsers; ?></li>
                    <li class="statistics-list-item"><span>Active Users:</span> <?php echo $activeUsers; ?></li>
                    <li class="statistics-list-item"><span>Inactive Users:</span> <?php echo $suspendedUsers; ?></li>
                    <li class="statistics-list-item"><span>Total Admins:</span> <?php echo $totalAdmins; ?></li>
                    <li class="statistics-list-item"><span>Male Users:</span> <?php echo $maleUsers; ?></li>
                    <li class="statistics-list-item"><span>Female Users:</span> <?php echo $femaleUsers; ?></li>
                    <li class="statistics-list-item"><span>Users Can Request Events:</span> <?php echo $usersCanRequestEvents; ?></li>
                    <li class="statistics-list-item"><span>Users Can Review Requests:</span> <?php echo $usersCanReviewRequests; ?></li>
                    <li class="statistics-list-item"><span>Users Can Delete Users:</span> <?php echo $usersCanDeleteUsers; ?></li>
                </ul>
            </div>
        </div>

        <!-- Event Statistics Card -->
        <div class="card statistics-card">
            <div class="card-header">Event Statistics</div>
            <div class="card-body">
                <ul class="statistics-list">
                    <li class="statistics-list-item"><span>Total Events:</span> <?php echo $totalEvents; ?></li>
                    <li class="statistics-list-item"><span>Pending Events:</span> <?php echo $pendingEvents; ?></li>
                    <li class="statistics-list-item"><span>Active Events:</span> <?php echo $activeEvents; ?></li>
                    <li class="statistics-list-item"><span>Denied Events:</span> <?php echo $deniedEvents; ?></li>
                    <li class="statistics-list-item"><span>Ongoing Events:</span> <?php echo $ongoingEvents; ?></li>
                    <li class="statistics-list-item"><span>Completed Events:</span> <?php echo $completedEvents; ?></li>
                    <li class="statistics-list-item"><span>Average Event Duration:</span> <?php echo $averageEventDuration; ?> hours</li>
                </ul>
            </div>
        </div>

        <!-- Comment Statistics Card -->
        <div class="card statistics-card">
            <div class="card-header">Comment Statistics</div>
            <div class="card-body">
                <ul class="statistics-list">
                    <li class="statistics-list-item"><span>Total Comments:</span> <?php echo $totalComments; ?></li>
                    <li class="statistics-list-item"><span>Average Comments per Event:</span> <?php echo round($averageCommentsPerEvent, 2); ?></li>
                </ul>
            </div>
        </div>

        <!-- Engagement Statistics Card -->
        <div class="card statistics-card">
            <div class="card-header">Engagement Statistics</div>
            <div class="card-body">
                <ul class="statistics-list">
                    <li class="statistics-list-item"><span>Total Likes (Events):</span> <?php echo $totalLikes; ?></li>
                    <li class="statistics-list-item"><span>Total Dislikes (Events):</span> <?php echo $totalDislikes; ?></li>
                    <li class="statistics-list-item"><span>Total Likes (Comments):</span> <?php echo $totalCommentLikes; ?></li>
                    <li class="statistics-list-item"><span>Total Dislikes (Comments):</span> <?php echo $totalCommentDislikes; ?></li>
                </ul>
            </div>
        </div>
        <!-- Additional Statistics Cards can be added similarly -->
    </div>
</div>
    <!-- End Content Section -->

    <!-- Footer -->
    <?php require '../PARTS/footer.php'; ?>

    <!-- JS.PHP -->
    <?php require '../PARTS/JS.php'; ?>
</body>
</html>
