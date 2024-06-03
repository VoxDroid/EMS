<?php
ob_start();
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';

// Function to check if the user has exceeded the comment limit per hour
function hasExceededCommentLimit($pdo, $user_id, $comment_limit, $hour_limit) {
    try {
        // Calculate the timestamp for one hour ago
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        // Count the number of comments by the user within the last hour
        $query = "SELECT COUNT(*) AS comment_count FROM comments WHERE user_id = :user_id AND date_commented >= :one_hour_ago";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id, 'one_hour_ago' => $one_hour_ago]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the comment count exceeds the limit
        return $result['comment_count'] >= $comment_limit;
    } catch(PDOException $e) {
        // Handle database error
        return false;
    }
}

try {
    // Check if event_id is provided in the URL
    if(isset($_GET['event_id'])) {
        // Retrieve event details based on event_id
        $event_id = $_GET['event_id'];
        $queryEventDetails = "SELECT e.*, u.username AS requester_username FROM events e JOIN users u ON e.user_id = u.id WHERE e.id = :event_id";
        $stmtEventDetails = $pdo->prepare($queryEventDetails);
        $stmtEventDetails->execute(['event_id' => $event_id]);
        $eventDetails = $stmtEventDetails->fetch(PDO::FETCH_ASSOC);

        // Display event details
        if($eventDetails) {
            // Existing code to display event details

            // Comment form
            if(isset($_SESSION['user_id'])) {
                // User is logged in
                $user_id = $_SESSION['user_id'];
                $comment_limit = 5; // Maximum number of comments per hour
                $hour_limit = 1; // Hour limit
            } 

            // Existing code to display existing comments
        } else {
            echo '<p class="alert alert-danger">Event not found.</p>';
        }
    } else {
        echo '<p class="alert alert-danger">Event ID not provided.</p>';
    }

    // Existing code for handling like/dislike button actions

} catch(PDOException $e) {
    echo '<p class="alert alert-danger">Error: ' . $e->getMessage() . '</p>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <!-- CSS.PHP -->
    <?php
    require '../PARTS/CSS.php';
    require '../ASSETS/CSS/custom_design.css';
    ?>

    <style>
        body {background-color: #405164;}
        /* Form container */
    </style>
</head>
<body>
    <!-- Header -->
    <?php require '../PARTS/header.php';?>
    <!-- End Header -->

    <div class="container mt-5 flex-grow-1">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']); // Clear message after displaying
        }

        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']); // Clear message after displaying
        }
        try {
            // Check if event_id is provided in the URL
            if (isset($_GET['event_id'])) {
                // Retrieve event details based on event_id
                $event_id = $_GET['event_id'];
                $queryEventDetails = "SELECT e.*, u.username AS requester_username, u.profile_picture AS requester_profile_picture 
                                      FROM events e 
                                      JOIN users u ON e.user_id = u.id 
                                      WHERE e.id = :event_id";
                $stmtEventDetails = $pdo->prepare($queryEventDetails);
                $stmtEventDetails->execute(['event_id' => $event_id]);
                $eventDetails = $stmtEventDetails->fetch(PDO::FETCH_ASSOC);
            
                // Display event details
                if ($eventDetails) {
                    echo '<div class="custom-card mb-4">';
                    echo '<div class="custom-card-header">';
                    echo '<h4 class="custom-card-title">Event Details</h4>';
                    echo '</div>';
                    echo '<div class="custom-card-body">';
                    
                    // User Information
                    echo '<div class="user-info mb-3">';
                    echo '<img src="' . htmlspecialchars($eventDetails['requester_profile_picture'], ENT_QUOTES, 'UTF-8') . '" alt="Profile Picture" class="profile-picture">';
                    echo '<h6 class="user-name">User: ' . htmlspecialchars($eventDetails['requester_username'], ENT_QUOTES, 'UTF-8') . '</h6>';
                    echo '</div>';
                
                    // Separator line
                    echo '<hr class="custom-separator">';
                    
                    // Event Details Grid
                    echo '<div class="event-details-grid">';
                    echo '<div class="grid-item">';
                    echo '<p><strong>Title:</strong> ' . htmlspecialchars($eventDetails['title'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<p><strong>Description:</strong> ' . nl2br(htmlspecialchars($eventDetails['description'], ENT_QUOTES, 'UTF-8')) . '</p>';
                    echo '<p><strong>Facility:</strong> ' . htmlspecialchars($eventDetails['facility'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '</div>';
                    echo '<div class="grid-item">';
                    echo '<p><strong>Duration:</strong> ' . htmlspecialchars($eventDetails['duration'], ENT_QUOTES, 'UTF-8') . ' hrs</p>';
                    echo '<p><strong>Date Requested:</strong> ' . htmlspecialchars($eventDetails['date_requested'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<p><strong>Status:</strong> ' . htmlspecialchars($eventDetails['status'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '</div>';
                    echo '<div class="grid-item">';
                    echo '<p><strong>Event Start:</strong> ' . htmlspecialchars($eventDetails['event_start'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<p><strong>Event End:</strong> ' . htmlspecialchars($eventDetails['event_end'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '</div>';
                    echo '<div class="grid-item">';
                    echo '<p><strong>Likes:</strong> ' . htmlspecialchars($eventDetails['likes'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<p><strong>Dislikes:</strong> ' . htmlspecialchars($eventDetails['dislikes'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '</div>';
                    echo '</div>'; // end event-details-grid
                    
                    // Event Remarks
                    echo '<div class="event-remarks">';
                    echo '<h6><strong>Remarks:</strong></h6>';
                    echo '<p>' . nl2br(htmlspecialchars($eventDetails['remarks'], ENT_QUOTES, 'UTF-8')) . '</p>';
                    echo '</div>';
                
                    echo '<hr class="custom-separator">';
                    
                    // Display like and dislike buttons only for logged-in users
                    if (isset($_SESSION['user_id'])) {
                        $user_id = $_SESSION['user_id'];
                        // Check if the user has voted for this event and what their vote type is
                        $queryCheckVote = "SELECT vote_type FROM event_votes WHERE user_id = :user_id AND event_id = :event_id";
                        $stmtCheckVote = $pdo->prepare($queryCheckVote);
                        $stmtCheckVote->execute(['user_id' => $user_id, 'event_id' => $event_id]);
                        $vote = $stmtCheckVote->fetch(PDO::FETCH_ASSOC);
                        $voteType = $vote ? $vote['vote_type'] : '';
                
                        // Set button classes based on vote type
                        $likeClass = $voteType === 'like' ? 'btn-liked' : '';
                        $dislikeClass = $voteType === 'dislike' ? 'btn-disliked' : '';
                
                        // Display like and dislike buttons
                        echo '<div class="btn-group">';
                        echo '<form method="post" class="like-form">';
                        echo '<input type="hidden" name="event_id" value="' . $event_id . '">';
                        if ($voteType === 'like') {
                            echo '<button type="submit" name="unlike" class="custom-button-like ' . $likeClass . '">Unlike</button>';
                        } else {
                            echo '<button type="submit" name="like" class="custom-button-like ' . $likeClass . '">Like</button>';
                        }
                        echo '</form>';
                
                        echo '<form method="post" class="dislike-form">';
                        echo '<input type="hidden" name="event_id" value="' . $event_id . '">';
                        if ($voteType === 'dislike') {
                            echo '<button type="submit" name="undislike" class="custom-button-dislike ' . $dislikeClass . '">Undislike</button>';
                        } else {
                            echo '<button type="submit" name="dislike" class="custom-button-dislike ' . $dislikeClass . '">Dislike</button>';
                        }
                        echo '</form>';
                        echo '</div>';
                
                    }
                    
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<p class="alert alert-danger">Event not found.</p>';
                }
            } else {
                echo '<p class="alert alert-danger">Event ID not provided.</p>';
            }
        
            // Handle like/dislike button actions
            if(isset($_POST['like']) || isset($_POST['unlike']) || isset($_POST['dislike']) || isset($_POST['undislike'])) {
                if(isset($_SESSION['user_id'])) {
                    // User is logged in
                    $user_id = $_SESSION['user_id'];

                    // Check if the user has already voted for this event
                    $queryCheckVote = "SELECT * FROM event_votes WHERE user_id = :user_id AND event_id = :event_id";
                    $stmtCheckVote = $pdo->prepare($queryCheckVote);
                    $stmtCheckVote->execute(['user_id' => $user_id, 'event_id' => $event_id]);
                    $existingVote = $stmtCheckVote->fetch(PDO::FETCH_ASSOC);
            
                    if(!$existingVote) {
                        // User has not voted yet, proceed to update like/dislike count
                        if(isset($_POST['like'])) {
                            // Increment likes count
                            $queryUpdateLikes = "UPDATE events SET likes = likes + 1 WHERE id = :event_id";
                            $stmtUpdateLikes = $pdo->prepare($queryUpdateLikes);
                            $stmtUpdateLikes->execute(['event_id' => $event_id]);
                            $voteType = 'like';
                        } elseif(isset($_POST['dislike'])) {
                            // Increment dislikes count
                            $queryUpdateDislikes = "UPDATE events SET dislikes = dislikes + 1 WHERE id = :event_id";
                            $stmtUpdateDislikes = $pdo->prepare($queryUpdateDislikes);
                            $stmtUpdateDislikes->execute(['event_id' => $event_id]);
                            $voteType = 'dislike';
                        }
            
                        // Record user's vote
                        $queryRecordVote = "INSERT INTO event_votes (user_id, event_id, vote_type) VALUES (:user_id, :event_id, :vote_type)";
                        $stmtRecordVote = $pdo->prepare($queryRecordVote);
                        $stmtRecordVote->execute(['user_id' => $user_id, 'event_id' => $event_id, 'vote_type' => $voteType]);
                    } else {
                        // User has already voted for this event, toggle the vote
                        $voteType = $existingVote['vote_type'];

                        if(isset($_POST['like']) && $voteType === 'dislike') {
                            // Toggle dislike to like
                            $queryUpdateLikes = "UPDATE events SET likes = likes + 1, dislikes = dislikes - 1 WHERE id = :event_id";
                            $stmtUpdateLikes = $pdo->prepare($queryUpdateLikes);
                            $stmtUpdateLikes->execute(['event_id' => $event_id]);
                            $voteType = 'like';
                        } elseif(isset($_POST['unlike']) && $voteType === 'like') {
                            // Toggle like to unlike
                            $queryDeleteVote = "DELETE FROM event_votes WHERE user_id = :user_id AND event_id = :event_id";
                            $stmtDeleteVote = $pdo->prepare($queryDeleteVote);
                            $stmtDeleteVote->execute(['user_id' => $user_id, 'event_id' => $event_id]);
                            $queryUpdateLikes = "UPDATE events SET likes = likes - 1 WHERE id = :event_id";
                            $stmtUpdateLikes = $pdo->prepare($queryUpdateLikes);
                            $stmtUpdateLikes->execute(['event_id' => $event_id]);
                            $voteType = '';
                        } elseif(isset($_POST['dislike']) && $voteType === 'like') {
                            // Toggle like to dislike
                            $queryUpdateDislikes = "UPDATE events SET dislikes = dislikes + 1, likes = likes - 1 WHERE id = :event_id";
                            $stmtUpdateDislikes = $pdo->prepare($queryUpdateDislikes);
                            $stmtUpdateDislikes->execute(['event_id' => $event_id]);
                            $voteType = 'dislike';
                        } elseif(isset($_POST['undislike']) && $voteType === 'dislike') {
                            // Toggle dislike to undislike
                            $queryDeleteVote = "DELETE FROM event_votes WHERE user_id = :user_id AND event_id = :event_id";
                            $stmtDeleteVote = $pdo->prepare($queryDeleteVote);
                            $stmtDeleteVote->execute(['user_id' => $user_id, 'event_id' => $event_id]);
                            $queryUpdateDislikes = "UPDATE events SET dislikes = dislikes - 1 WHERE id = :event_id";
                            $stmtUpdateDislikes = $pdo->prepare($queryUpdateDislikes);
                            $stmtUpdateDislikes->execute(['event_id' => $event_id]);
                            $voteType = '';
                        }

                        // Update user's vote type
                        $queryUpdateVote = "UPDATE event_votes SET vote_type = :vote_type WHERE user_id = :user_id AND event_id = :event_id";
                        $stmtUpdateVote = $pdo->prepare($queryUpdateVote);
                        $stmtUpdateVote->execute(['user_id' => $user_id, 'event_id' => $event_id, 'vote_type' => $voteType]);
                    }
                    
                    // Refresh the page to reflect updated like/dislike counts
                    header("Refresh:0");
                    ob_end_flush();
                } else {
                    // User is not logged in, display a message or redirect to login page
                    echo '<p class="alert alert-warning">Please log in to vote for this event.</p>';
                }
            }
        } catch(PDOException $e) {
            echo '<p class="alert alert-danger">Error: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <!-- Comment Section -->
    <div class="container mt-5">
        <h5 class="text-white">Comments</h5>
        <hr style="border: none; height: 4px; background-color: #1c2331;">
        <?php
        if(!isset($_SESSION['user_id'])) {
            echo '<p class="alert alert-warning">Please log in to post a comment.</p>';
        } 
        ?>
        <!-- Comment Form -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php
            // Check if the user has exceeded the comment limit per hour
            if(!hasExceededCommentLimit($pdo, $user_id, $comment_limit, $hour_limit)) {
            ?>
                <form method="post" class="custom-form">
    <div class="mb-3">
        <label for="comment" class="form-label custom-form-label">Your comment:</label>
        <textarea class="form-control custom-form-textarea" id="comment" name="comment" rows="3" required></textarea>
    </div>
    <button type="submit" name="submit_comment" class="btn custom-button-indevent">Post Comment</button>
</form>

            <?php } else { ?>
                <p class="alert alert-warning">You have reached the maximum comment limit per hour.</p>
            <?php } ?>
        <?php endif; ?>
        <!-- End Comment Form -->

        <!-- Display Existing Comments -->
        <div class="mt-4">
            <?php
            // Handle comment submission
            if(isset($_POST['submit_comment'])) {
                if(isset($_SESSION['user_id'])) {
                    try {
                        // Get the user ID and event ID
                        $user_id = $_SESSION['user_id'];
                        $comment = $_POST['comment'];

                        // Insert the comment into the database
                        $queryInsertComment = "INSERT INTO comments (event_id, user_id, comment) VALUES (:event_id, :user_id, :comment)";
                        $stmtInsertComment = $pdo->prepare($queryInsertComment);
                        $stmtInsertComment->execute(['event_id' => $event_id, 'user_id' => $user_id, 'comment' => $comment]);
                        $_SESSION['success_message'] = 'Comment added successfully!';
                        // Redirect to prevent form resubmission
                        header("Location: {$_SERVER['REQUEST_URI']}");
                        exit();
                    } catch(PDOException $e) {
                        echo '<p class="alert alert-danger">Error: ' . $e->getMessage() . '</p>';
                    }
                } else {
                    echo '<p class="alert alert-warning">Please log in to post a comment.</p>';
                }
            }

            // Edit comment form
            if (isset($_POST['edit_comment'])) {
                $edit_comment_id = $_POST['comment_id'];
                $queryGetComment = "SELECT * FROM comments WHERE id = :comment_id";
                $stmtGetComment = $pdo->prepare($queryGetComment);
                $stmtGetComment->execute(['comment_id' => $edit_comment_id]);
                $edit_comment = $stmtGetComment->fetch(PDO::FETCH_ASSOC);
                if ($edit_comment) {
                    // Display edit form
                    echo '<hr style="border: none; height: 4px; background-color: #1c2331;" id="edit-comment-form-hr1">';
                    echo '<form id="edit_comment_form" method="post" class="custom-form">';
                    echo '<div class="mb-3">';
                    echo '<label for="edited_comment" class="form-label custom-form-label text-white">Edit Your Comment</label>';
                    echo '<textarea class="form-control custom-form-textarea" id="edited_comment" name="edited_comment" rows="3" required>' . $edit_comment['comment'] . '</textarea>';
                    echo '</div>';
                    echo '<div class="mb-3">';
                    echo '<button type="submit" name="submit_edit_comment" class="btn btn-primary me-3 custom-button-indevent">Submit</button>';
                    echo '<button type="button" class="btn btn-secondary" id="cancel_edit">Cancel</button>';
                    echo '<input type="hidden" name="edit_comment_id" value="' . $edit_comment_id . '">';
                    echo '</div>';
                    echo '</form>';
                    echo '<hr style="border: none; height: 4px; background-color: #1c2331;" id="edit-comment-form-hr2">';
                } else {
                    echo '<p class="alert alert-danger">Comment not found.</p>';
                }
            }
            
            // JavaScript to hide the edit comment form when cancel is clicked
            echo '<script>
            document.getElementById("cancel_edit").addEventListener("click", function() {
                document.getElementById("edit_comment_form").style.display = "none";
                document.getElementById("edit-comment-form-hr1").style.display = "none";
                document.getElementById("edit-comment-form-hr2").style.display = "none";
            });
            </script>';

            // Handle comment edit submission
            if (isset($_POST['submit_edit_comment'])) {
                $edit_comment_id = $_POST['edit_comment_id'];
                $edited_comment = $_POST['edited_comment'];
                try {
                    $queryUpdateComment = "UPDATE comments SET comment = :edited_comment WHERE id = :comment_id";
                    $stmtUpdateComment = $pdo->prepare($queryUpdateComment);
                    $stmtUpdateComment->execute(['edited_comment' => $edited_comment, 'comment_id' => $edit_comment_id]);
                    // Redirect to prevent form resubmission
                    $_SESSION['success_message'] = 'Comment updated successfully!';
                    header("Location: {$_SERVER['REQUEST_URI']}");
                    exit();
                } catch (PDOException $e) {
                    echo '<p class="alert alert-danger">Error updating comment: ' . $e->getMessage() . '</p>';
                }
            }

            // Handle comment deletion
            if(isset($_POST['confirm_delete'])) {
                if(isset($_SESSION['user_id'])) {
                    // User is logged in
                    $user_id = $_SESSION['user_id'];
                    $comment_id = $_POST['comment_id'];

                    try {
                        // Check if the user owns the comment
                        $queryCheckOwnership = "SELECT * FROM comments WHERE id = :comment_id AND user_id = :user_id";
                        $stmtCheckOwnership = $pdo->prepare($queryCheckOwnership);
                        $stmtCheckOwnership->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);
                        $comment = $stmtCheckOwnership->fetch(PDO::FETCH_ASSOC);

                        if($comment) {
                            // User owns the comment, proceed with deletion of associated votes
                            $queryDeleteVotes = "DELETE FROM comment_votes WHERE comment_id = :comment_id";
                            $stmtDeleteVotes = $pdo->prepare($queryDeleteVotes);
                            $stmtDeleteVotes->execute(['comment_id' => $comment_id]);

                            // Then delete the comment itself
                            $queryDeleteComment = "DELETE FROM comments WHERE id = :comment_id";
                            $stmtDeleteComment = $pdo->prepare($queryDeleteComment);
                            $stmtDeleteComment->execute(['comment_id' => $comment_id]);

                            // Show success message
                            echo '<div class="alert alert-success" role="alert">Comment Deleted Successfully!</div>';
                            
                            $_SESSION['success_message'] = 'Comment deleted successfully!';
                            
                            // Redirect to prevent form resubmission
                            header("Location: {$_SERVER['REQUEST_URI']}");
                            exit();
                        } else {
                            // User does not own the comment
                            echo '<p class="alert alert-danger">You do not have permission to delete this comment.</p>';
                        }
                    } catch(PDOException $e) {
                        echo '<p class="alert alert-danger">Error: ' . $e->getMessage() . '</p>';
                    }
                } else {
                    // User is not logged in
                    echo '<p class="alert alert-warning">Please log in to delete this comment.</p>';
                }
            }

            // Handle like/dislike button actions for comments
            if(isset($_POST['like_comment']) || isset($_POST['dislike_comment'])) {
                if(isset($_SESSION['user_id'])) {
                    // User is logged in
                    $user_id = $_SESSION['user_id'];
                    $comment_id = $_POST['comment_id'];

                    // Determine the vote type based on the button clicked
                    $voteType = isset($_POST['like_comment']) ? 'like' : 'dislike';

                    try {
                        // Check if the user has already voted for this comment
                        $queryCheckVote = "SELECT * FROM comment_votes WHERE user_id = :user_id AND comment_id = :comment_id";
                        $stmtCheckVote = $pdo->prepare($queryCheckVote);
                        $stmtCheckVote->execute(['user_id' => $user_id, 'comment_id' => $comment_id]);
                        $existingVote = $stmtCheckVote->fetch(PDO::FETCH_ASSOC);

                        if(!$existingVote) {
                            // User has not voted yet, insert the new vote
                            $queryInsertVote = "INSERT INTO comment_votes (user_id, event_id, comment_id, vote_type) VALUES (:user_id, :event_id, :comment_id, :vote_type)";
                            $stmtInsertVote = $pdo->prepare($queryInsertVote);
                            $stmtInsertVote->execute(['user_id' => $user_id, 'event_id' => $event_id, 'comment_id' => $comment_id, 'vote_type' => $voteType]);
                        } else {
                            // User has already voted, check if the vote type is the same
                            $existingVoteType = $existingVote['vote_type'];
                            if($existingVoteType === $voteType) {
                                // User clicked on the same vote type button, remove the vote
                                $queryDeleteVote = "DELETE FROM comment_votes WHERE user_id = :user_id AND comment_id = :comment_id";
                                $stmtDeleteVote = $pdo->prepare($queryDeleteVote);
                                $stmtDeleteVote->execute(['user_id' => $user_id, 'comment_id' => $comment_id]);
                            } else {
                                // User clicked on a different vote type button, update the existing vote
                                $queryUpdateVote = "UPDATE comment_votes SET vote_type = :vote_type WHERE user_id = :user_id AND comment_id = :comment_id";
                                $stmtUpdateVote = $pdo->prepare($queryUpdateVote);
                                $stmtUpdateVote->execute(['user_id' => $user_id, 'comment_id' => $comment_id, 'vote_type' => $voteType]);
                            }
                        }
                        // Redirect to prevent form resubmission
                        header("Location: {$_SERVER['REQUEST_URI']}");
                        exit();
                    } catch(PDOException $e) {
                        echo '<p class="alert alert-danger">Error: ' . $e->getMessage() . '</p>';
                    }
                } else {
                    // User is not logged in
                    echo '<p class="alert alert-warning">Please log in to vote for this comment.</p>';
                }
            }

            // Retrieve comments for this event ordered by date in descending order
            $queryCountComments = "SELECT COUNT(*) as total_comments FROM comments WHERE event_id = :event_id";
            $stmtCountComments = $pdo->prepare($queryCountComments);
            $stmtCountComments->execute(['event_id' => $event_id]);
            $total_comments = $stmtCountComments->fetch(PDO::FETCH_ASSOC)['total_comments'];

            $comments_per_page = 10;
            $total_pages = ceil($total_comments / $comments_per_page);

            $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
            $offset = ($current_page - 1) * $comments_per_page;

            $queryComments = "SELECT c.*, u.username AS commenter_username, u.profile_picture,
    (SELECT COUNT(*) FROM comment_votes WHERE comment_id = c.id AND vote_type = 'like') AS likes,
    (SELECT COUNT(*) FROM comment_votes WHERE comment_id = c.id AND vote_type = 'dislike') AS dislikes
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.event_id = :event_id
    ORDER BY c.date_commented DESC LIMIT :offset, :comments_per_page"; // Order by date in descending order
$stmtComments = $pdo->prepare($queryComments);
$stmtComments->bindParam(':event_id', $event_id, PDO::PARAM_INT);
$stmtComments->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmtComments->bindParam(':comments_per_page', $comments_per_page, PDO::PARAM_INT);
$stmtComments->execute();
$comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);

// Display comments
foreach ($comments as $comment) {
    echo '<div class="custom-comment-card">';
    echo '<div class="card-body">';
    
    // Commenter info with profile picture
    echo '<div class="custom-commenter-info">';
    $profilePicture = $comment['profile_picture'];
    echo '<img src="' . $profilePicture . '" class="profile-picture" width="50" height="50" alt="Profile Picture">';
    echo '<h6>Commented by:<strong> ' . htmlspecialchars($comment['commenter_username']) . '</strong> on ' . htmlspecialchars($comment['date_commented']) . '</h6>';
    echo '</div>';
    
    // Comment text
    echo '<p class="custom-comment-text">' . htmlspecialchars($comment['comment'], ENT_NOQUOTES, 'UTF-8') . '</p>';
    
    // Like and Dislike buttons
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<form method="post" style="display: inline-block;">';
    echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
    echo '<button type="submit" name="like_comment" class="btn btn-outline-primary custom-button-like me-2">Like (' . $comment['likes'] . ')</button>';
    echo '</form>';

    echo '<form method="post" style="display: inline-block;">';
    echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
    echo '<button type="submit" name="dislike_comment" class="btn btn-outline-danger custom-button-dislike">Dislike (' . $comment['dislikes'] . ')</button>';
    echo '</form>';

    // Edit and delete buttons
    if(isset($_SESSION['user_id']) && $comment['user_id'] === $_SESSION['user_id']) {
        echo '<div class="ms-auto">';
        echo '<form method="post" style="display: inline-block;">';
        echo '<button type="button" class="btn btn-outline-danger delete-comment-btn me-2 custom-button-delete" data-comment-id="' . $comment['id'] . '">Delete</button>';
        echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
        echo '</form>';
        
        echo '<form method="post" style="display: inline-block;">';
        echo '<button type="submit" name="edit_comment" class="btn btn-outline-secondary custom-button-edit custom-button-purple">Edit</button>';
        echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
        echo '</form>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
            }
            ?>
        </div>
        <?php if (!empty($comments)): ?>
        <!-- Pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($current_page == 1) ? 'disabled' : ''; ?>">
                    <a class="page-link custom-page-link" href="?event_id=<?php echo $event_id; ?>&page=<?php echo ($current_page - 1); ?>" tabindex="-1" aria-disabled="true">«</a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>"><a class="page-link custom-page-link" href="?event_id=<?php echo $event_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link custom-page-link" href="?event_id=<?php echo $event_id; ?>&page=<?php echo ($current_page + 1); ?>">»</a>
                </li>
            </ul>
        </nav>
        <!-- End Pagination -->
    <?php endif; ?>
    </div>
<!-- End Comment Section -->

<!-- Add this HTML code at the end of your body tag -->
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-labelledby="deleteCommentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteCommentModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this comment?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteCommentForm" method="post">
          <button type="submit" name="confirm_delete" class="btn btn-danger">Delete</button>
          <input type="hidden" id="commentIdToDelete" name="comment_id">
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<?php require '../PARTS/footer.php'; ?>

<script>
// Add this JavaScript code at the end of your HTML body
document.addEventListener('DOMContentLoaded', function() {
  // Show delete confirmation modal when delete button is clicked
  document.querySelectorAll('.delete-comment-btn').forEach(function(button) {
    button.addEventListener('click', function() {
      var commentId = this.getAttribute('data-comment-id');
      document.getElementById('commentIdToDelete').value = commentId;
      var deleteModal = new bootstrap.Modal(document.getElementById('deleteCommentModal'), {});
      deleteModal.show();
    });
  });
});
</script>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>
</body>
</html>