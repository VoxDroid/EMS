<?php
// Include necessary files and configurations
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
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Current page (default: 1)

// Check if search query is provided
if (isset($_GET['q'])) {
    $searchQuery = $_GET['q'];

    // Prepare query to search events
    $query = "SELECT e.*, u.username, u.profile_picture 
              FROM events e
              JOIN users u ON e.user_id = u.id
              WHERE e.title LIKE :searchQuery
                 OR e.description LIKE :searchQuery
                 OR e.facility LIKE :searchQuery
                 OR e.remarks LIKE :searchQuery
                 OR e.status LIKE :searchQuery
                 OR e.date_requested LIKE :searchQuery
              ORDER BY e.date_requested DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->execute();

    // Fetch all search results
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pagination for search results
    $totalSearchResults = count($searchResults);
    $totalPages = getTotalPages($totalSearchResults, $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Fetch paginated search results
    $query .= " LIMIT $offset, $itemsPerPage";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':searchQuery', '%' . $searchQuery . '%', PDO::PARAM_STR);
    $stmt->execute();
    $searchResultsPaginated = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Redirect or handle case when no search query is provided
    header('Location: index.php'); // Redirect to homepage or appropriate page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Search Results</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <!-- Pagination CSS -->
    <?php require '../ASSETS/CSS/custom_design.css'; ?>
    <style>
        body {
            background-color: #405164;
        }
        hr {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require '../PARTS/header.php'; ?>

    <!-- Search Results -->
    <div class="py-5 flex-grow-1" style="background-color: #405164">
        <div class="container">
            <h2 class="text-white">Search Results for "<?php echo htmlspecialchars($searchQuery); ?>":</h2>
            <hr style="border: none; height: 4px; background-color: #FFFFFF;">
            <div class="row">
                <?php if (empty($searchResultsPaginated)): ?>
                    <div class="col-md-12">
                        <p class="text-white">No events found matching your search.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($searchResultsPaginated as $event): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm event-card">
                                <div class="card-body">
                                    <!-- User profile picture and name -->
                                    <div class="d-flex align-items-center mb-3">
                                        <?php
                                        // Adjust profile picture path if it starts with '../'
                                        $profilePicture = $event['profile_picture'];
                                        echo '<img src="' . $profilePicture . '" class="rounded-circle me-3 profile-picture" width="50" height="50" alt="Profile Picture">';
                                        ?>
                                        <div>
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($event['title']); ?></h5>
                                            <p class="card-text text-muted mb-1">Organized by: <?php echo htmlspecialchars($event['username']); ?></p>
                                            <p class="card-text text-muted mb-0">Date: <?php echo date('M d, Y', strtotime($event['date_requested'])); ?></p>
                                        </div>
                                    </div>
                                    <!-- Event details -->
                                    <p class="card-text event-description"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                    <!-- Additional event information -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="card-text"><strong>Duration:</strong> <?php echo htmlspecialchars($event['duration']); ?> hours</p>
                                            <p class="card-text"><strong>Location:</strong> <?php echo htmlspecialchars($event['facility']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="card-text"><strong>Status:</strong> <?php echo ucfirst($event['status']); ?></p>
                                            <p class="card-text"><strong>Remarks:</strong> <?php echo ($event['remarks'] ? htmlspecialchars($event['remarks']) : 'None'); ?></p>
                                        </div>
                                    </div>
                                    <!-- Likes and dislikes icons and numbers -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="like-dislike me-4">
                                            <img src="../SVG/hand-thumbs-up-fill.svg" alt="Likes" width="16" height="16" class="text-success me-1">
                                            <span class="like-count"><?php echo $event['likes']; ?></span>
                                        </div>
                                        <div class="like-dislike">
                                            <img src="../SVG/hand-thumbs-down-fill.svg" alt="Dislikes" width="16" height="16" class="text-danger me-1">
                                            <span class="dislike-count"><?php echo $event['dislikes']; ?></span>
                                        </div>
                                    </div>
                                    <!-- View button -->
                                    <a href="event_details.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm custom-button-ind">View Details</a>
                                </div> <!-- .card-body -->
                            </div> <!-- .card -->
                        </div> <!-- .col-md-6 -->
                    <?php endforeach; ?>
                <?php endif; ?>
            </div> <!-- .row -->
            <!-- Pagination controls -->
            <?php if ($totalPages > 1): ?>
                <div class="col-md-12 mt-4">
                    <nav aria-label="Page navigation example">
                        <ul class="pagination justify-content-center">
                            <!-- Previous page link -->
                            <li class="page-item <?php echo ($currentPage == 1 ? 'disabled' : ''); ?>">
                                <a class="page-link custom-page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo max(1, $currentPage - 1); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                    <span class="sr-only">Previous</span>
                                </a>
                            </li>
                            <!-- Display up to 8 pages at a time -->
                            <?php
                            $startPage = max(1, $currentPage - 4);
                            $endPage = min($totalPages, $startPage + 7);
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo ($currentPage == $i ? 'active' : ''); ?>">
                                    <a class="page-link custom-page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <!-- Next page link -->
                            <li class="page-item <?php echo ($currentPage == $totalPages ? 'disabled' : ''); ?>">
                                <a class="page-link custom-page-link" href="?q=<?php echo urlencode($searchQuery); ?>&page=<?php echo min($totalPages, $currentPage + 1); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                    <span class="sr-only">Next</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div> <!-- .container -->
    </div> <!-- .py-5 -->

    <!-- Footer -->
    <?php require '../PARTS/footer.php'; ?>

    <!-- JS.PHP -->
    <?php require '../PARTS/JS.php'; ?>
</body>
</html>
