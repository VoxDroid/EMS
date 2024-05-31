<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';

// Check if user is logged in and is a regular user
if (!(isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'user')) {
    // Redirect to index.php if not logged in or not a regular user
    header("Location: ../index.php");
    exit();
}

// Function to redirect to index.php
function redirectToIndex() {
    header("Location: ../index.php");
    exit();
}

try {
    // Initialize variables for form input values
    $title = isset($_SESSION['request_event_data']['title']) ? $_SESSION['request_event_data']['title'] : '';
    $description = isset($_SESSION['request_event_data']['description']) ? $_SESSION['request_event_data']['description'] : '';
    $facility = isset($_SESSION['request_event_data']['facility']) ? $_SESSION['request_event_data']['facility'] : '';
    $eventStart = isset($_SESSION['request_event_data']['event_start']) ? $_SESSION['request_event_data']['event_start'] : '';
    $eventEnd = isset($_SESSION['request_event_data']['event_end']) ? $_SESSION['request_event_data']['event_end'] : '';

    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Retrieve form data
        $title = filter_input(INPUT_POST, 'title');
        $description = filter_input(INPUT_POST, 'description');
        $facility = filter_input(INPUT_POST, 'facility');
        $eventStart = ($_POST['event_start']);
        $eventEnd = ($_POST['event_end']);

        // Additional validation
        if (strlen($title) < 5 || strlen($description) < 5) {
            $_SESSION['error_message'] = 'Title and Description must be at least 5 characters long!';
            $_SESSION['request_event_data'] = [
                'title' => $title,
                'description' => $description,
                'facility' => $facility,
                'event_start' => $eventStart,
                'event_end' => $eventEnd
            ];
            header("Location: request_event.php");
            exit();
        }


        // Additional validation
        if (!$title || !$description || !$facility || !$eventStart || !$eventEnd) {
            $_SESSION['error_message'] = 'Please fill in all required fields!';
            $_SESSION['request_event_data'] = [
                'title' => $title,
                'description' => $description,
                'facility' => $facility,
                'event_start' => $eventStart,
                'event_end' => $eventEnd
            ];
            header("Location: request_event.php");
            exit();
        }

        // Validate event start and end dates
        $startDateTime = new DateTime($eventStart);
        $endDateTime = new DateTime($eventEnd);

        // Check if event start date is past the current time
        if ($startDateTime <= new DateTime()) {
            $_SESSION['error_message'] = 'Event start date must be in the future!';
            $_SESSION['request_event_data'] = [
                'title' => $title,
                'description' => $description,
                'facility' => $facility,
                'event_start' => $eventStart,
                'event_end' => $eventEnd
            ];
            header("Location: request_event.php");
            exit();
        }

        // Check if event end date is before the start date
        if ($startDateTime >= $endDateTime) {
            $_SESSION['error_message'] = 'Event end date must be after event start date!';
            $_SESSION['request_event_data'] = [
                'title' => $title,
                'description' => $description,
                'facility' => $facility,
                'event_start' => $eventStart,
                'event_end' => $eventEnd
            ];
            header("Location: request_event.php");
            exit();
        }

        // Calculate duration in total hours
        $interval = $startDateTime->diff($endDateTime);
        $duration = ($interval->days * 24) + $interval->h + ($interval->i / 60);

        // Insert the event into the database
        $stmt = $pdo->prepare("INSERT INTO events (user_id, title, description, facility, duration, status, event_start, event_end) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $facility, $duration, $eventStart, $eventEnd]);

        $_SESSION['success_message'] = 'Event submitted successfully!';
        header("Location: ../index.php");
        exit();
    }
} catch(PDOException $e) {
    // Redirect to index.php if there's an error
    redirectToIndex();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Event</title>

    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>

    <style>
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
    ?>
    <h2>Request Event</h2>
    <hr style="border: none; height: 4px; background-color: #1c2331;">
    <!-- Event request form -->
    <form action="request_event.php" method="POST" id="eventForm">
        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Event Description *</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="form-group">
            <label for="facility">Facility *</label>
            <input type="text" class="form-control" id="facility" name="facility" value="<?php echo htmlspecialchars($facility); ?>" required>
        </div>
        <div class="form-group">
            <label for="event_start">Event Start Date and Time *</label>
            <input type="datetime-local" class="form-control" id="event_start" name="event_start" value="<?php echo htmlspecialchars($eventStart); ?>" required>
        </div>
        <div class="form-group">
            <label for="event_end">Event End Date and Time *</label>
            <input type="datetime-local" class="form-control" id="event_end" name="event_end" value="<?php echo htmlspecialchars($eventEnd); ?>" required>
        </div>
        <div class="form-group">
            <label for="duration">Duration (in hours)</label>
            <input type="number" class="form-control" id="duration" name="duration" min="1" readonly>
        </div>
        <button type="button" class="btn btn-primary mt-3 submit-btn" data-bs-toggle="modal" data-bs-target="#confirmModal">Submit</button>

        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">Confirm Event Submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to submit this event? You won't be able to edit the details later.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Event</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Footer -->
<?php require '../PARTS/footer.php'; ?>

<!-- JS.PHP -->
<?php require '../PARTS/JS.php'; ?>

<script>
    // Function to set min attribute of event_start input to tomorrow's date
    function setMinStartDate() {
        var currentDate = new Date();
        var tomorrowDate = new Date(currentDate.getTime() + (24 * 60 * 60 * 1000));
        // Set min attribute of event start input to tomorrow's date
        document.getElementById("event_start").min = tomorrowDate.toISOString().slice(0, 16);
    }

    // Function to set min attribute of event_end input based on event start date
    function setMinEndDate() {
        var eventStartInput = document.getElementById("event_start");
        var eventEndInput = document.getElementById("event_end");
        // Ensure event end date cannot be before event start date
        if (eventStartInput.value) {
            var startDate = new Date(eventStartInput.value);
            // Set min date for event end to event start date
            eventEndInput.min = startDate.toISOString().slice(0, 16);
        }
        // Calculate duration and fill in the input
        if (eventStartInput.value && eventEndInput.value) {
            var startDateTime = new Date(eventStartInput.value);
            var endDateTime = new Date(eventEndInput.value);
            var durationHours = Math.abs(endDateTime - startDateTime) / 36e5; // Milliseconds to hours
            document.getElementById("duration").value = Math.ceil(durationHours);
        }
    }

    // Event listener to call setMinEndDate function when event start date changes
    document.getElementById("event_start").addEventListener("change", function() {
        setMinEndDate();
    });

    // Event listener to call setMinEndDate function when event end date changes
    document.getElementById("event_end").addEventListener("change", function() {
        setMinEndDate();
    });

    // Call setMinStartDate function to set min attribute of event_start input
    setMinStartDate();
    // Call setMinEndDate function initially to set min attribute of event_end input
    setMinEndDate();
</script>

</body>
</html>
