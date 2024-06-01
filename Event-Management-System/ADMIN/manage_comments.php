<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';

// Ensure user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Pagination setup
$limit = 500; // Number of comments per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page number

// Function to fetch comments for a specific page
function fetchComments($pdo, $page, $limit) {
    // Calculate offset
    $offset = ($page - 1) * $limit;

    // SQL query to retrieve comments along with user information
    $query = "SELECT comments.id, comments.comment, comments.likes, comments.dislikes, comments.date_commented, users.username, comments.event_id
              FROM comments
              INNER JOIN users ON comments.user_id = users.id
              ORDER BY comments.id DESC
              LIMIT :limit OFFSET :offset";
    
    // Prepare the query
    $statement = $pdo->prepare($query);
    $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
    $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    // Execute the query
    $statement->execute();
    
    // Fetch all comments as an associative array
    $comments = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    return $comments;
}

// Fetch comments for the current page
$comments = fetchComments($pdo, $page, $limit);

// Count total number of comments
$queryCount = "SELECT COUNT(*) AS total FROM comments";
$stmtCount = $pdo->query($queryCount);
$totalComments = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

// Calculate total pages
$totalPages = ceil($totalComments / $limit);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    // Ensure comment_id is set and is a valid integer
    if (isset($_POST['comment_id']) && is_numeric($_POST['comment_id'])) {
        $commentId = $_POST['comment_id']; // Get the comment ID to be deleted
        
        // Prepare the deletion query
        $deleteQuery = "DELETE FROM comments WHERE id = :comment_id";
        $stmt = $pdo->prepare($deleteQuery);
        
        // Bind parameters
        $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
        
        // Execute the deletion query
        if ($stmt->execute()) {
            // Deletion successful
            $_SESSION['success'] = "Comment deleted successfully.";
            header("Location: manage_comments.php");
            exit();
        } else {
            // Deletion failed
            $_SESSION['error'] = "Failed to delete comment.";
            header("Location: manage_comments.php");
            exit();
        }
    } else {
        // Invalid comment_id provided
        $_SESSION['error'] = "Invalid comment ID provided.";
        header("Location: manage_comments.php");
        exit();
    }
}
// Fetch comments for the current page
$comments = fetchComments($pdo, $page, $limit);
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
        .custom-button-mc {
            background-color: #161c27;
            border: none;
            color: #ffffff;
            padding: 7px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .custom-button-mc:hover {
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
        <a class="nav-button" href="manage_users.php"><i class="fas fa-users nav-icon"></i> Manage Users</a>
        <a class="nav-button active" href="#"><i class="fas fa-comments nav-icon"></i> Manage Comments</a>
        <a class="nav-button" href="manage_events.php"><i class="fas fa-calendar-alt nav-icon"></i> Manage Events</a>
        <a class="nav-button" href="manage_database.php"><i class="fas fa-database nav-icon"></i> Manage Database</a>
    </div>

        <!-- Manage Comments Table -->
    <div class="container py-5 flex-grow-1">
        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        <h2>Manage Comments</h2>
        <hr style="border: none; height: 4px; background-color: #1c2331;">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search...">
        <div class="table-responsive">
            
    <div class="table-title" >Comments</div>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Comment</th>
                        <th>Likes</th>
                        <th>Dislikes</th>
                        <th>Date Commented</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['id']); ?></td>
                            <td><?php echo htmlspecialchars($comment['username']); ?></td>
                            <td><?php echo htmlspecialchars($comment['comment']); ?></td>
                            <td><?php echo htmlspecialchars($comment['likes']); ?></td>
                            <td><?php echo htmlspecialchars($comment['dislikes']); ?></td>
                            <td><?php echo htmlspecialchars($comment['date_commented']); ?></td>
                            <td>
                                <button class="btn btn-primary custom-button-mc" data-bs-toggle="modal" data-bs-target="#viewCommentModal<?php echo $comment['id']; ?>">View</button>
                                <!-- Delete Button with Confirmation Modal -->
                                <button class="btn btn-secondary custom-button-delete" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal<?php echo $comment['id']; ?>">Delete</button>
                            </td>
                        </tr>
                        <!-- View Comment Modal -->
                        <div class="modal fade" id="viewCommentModal<?php echo $comment['id']; ?>" tabindex="-1" aria-labelledby="viewCommentModalLabel<?php echo $comment['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewCommentModalLabel<?php echo $comment['id']; ?>">View Comment</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>User: <?php echo htmlspecialchars($comment['username']); ?></p>
                                        <p>Comment: <?php echo htmlspecialchars($comment['comment']); ?></p>
                                        <p>Likes: <?php echo htmlspecialchars($comment['likes']); ?></p>
                                        <p>Dislikes: <?php echo htmlspecialchars($comment['dislikes']); ?></p>
                                        <p>Date Commented: <?php echo htmlspecialchars($comment['date_commented']); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <a class="btn btn-primary" href="../EMS/event_details.php?event_id=<?php echo $comment['event_id']; ?>">Go to Event</a>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Delete Confirmation Modal -->
                        <div class="modal fade" id="confirmDeleteModal<?php echo $comment['id']; ?>" tabindex="-1" aria-labelledby="confirmDeleteModalLabel<?php echo $comment['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="confirmDeleteModalLabel<?php echo $comment['id']; ?>">Confirm Delete</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this comment?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form method="post">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="delete_comment" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <nav aria-label="Page navigation example" class="mt-3">
            <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page == 1 ? 'disabled' : ''; ?>">
                        <a class="page-link custom-page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1" aria-disabled="true">«</a>
                    </li>

                <?php for ($p = $startPage; $p <= $endPage; $p++) : ?>
                    <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                        <a class="page-link custom-page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>

                    <li class="page-item <?php echo $page == $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link custom-page-link" href="?page=<?php echo $page + 1; ?>">»</a>
                    </li>
            </ul>
        </nav>
        <!-- End Pagination -->
    </div>
    <!-- End Manage Comments Table -->

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
                const comment = row.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
                const likes = row.querySelector('td:nth-child(3)').textContent.trim().toLowerCase();
                const dislikes = row.querySelector('td:nth-child(4)').textContent.trim().toLowerCase();
                const date_commented = row.querySelector('td:nth-child(5)').textContent.trim().toLowerCase();
                const username = row.querySelector('td:nth-child(6)').textContent.trim().toLowerCase();
                const event_id = row.querySelector('td:nth-child(7)').textContent.trim().toLowerCase();

                // Check if any of the row's data matches the search term
                if (id.includes(searchTerm) || comment.includes(searchTerm) || likes.includes(searchTerm) || dislikes.includes(searchTerm) ||
                    date_commented.includes(searchTerm) || username.includes(searchTerm) || event_id.includes(searchTerm)) {
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

