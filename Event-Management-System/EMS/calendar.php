<?php
require_once '../PARTS/background_worker.php';
require_once '../PARTS/config.php';

// Determine the month and year to display
$thisMonth = isset($_GET['this_month']) ? DateTime::createFromFormat('Y-m', $_GET['this_month']) : new DateTime();

// Determine the selected day
$selectedDay = isset($_GET['selected_day']) ? (int)$_GET['selected_day'] : 1; // Default to 1 for the first day of the month

// Fetch events from the database (only active events for the selected month and future days)
$startOfMonth = clone $thisMonth;
$startOfMonth->modify('first day of this month');
$endOfMonth = clone $thisMonth;
$endOfMonth->modify('last day of this month');

// Fetch events from the database (only active, completed, and ongoing events for the selected month and future days)
$sql = "SELECT id, title, event_start, event_end 
        FROM events 
        WHERE event_start IS NOT NULL 
        AND event_end IS NOT NULL
        AND (
            (DATE(event_start) <= :endOfMonth AND DATE(event_end) >= :startOfMonth AND status = 'active') OR
            (DATE(event_end) < :startOfMonth AND status = 'completed') OR
            (DATE(event_start) > :endOfMonth AND status = 'ongoing')
        )";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':startOfMonth', $startOfMonth->format('Y-m-d'));
$stmt->bindValue(':endOfMonth', $endOfMonth->format('Y-m-d'));
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group events by date
$eventsByDate = [];
foreach ($events as $event) {
    $startDate = new DateTime($event['event_start']);
    $endDate = new DateTime($event['event_end']);

    while ($startDate <= $endDate) {
        $dateKey = $startDate->format('j');
        $eventsByDate[$dateKey][] = $event;
        $startDate->modify('+1 day');
    }
}

$today = new DateTime(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
    
    <!-- CSS.PHP -->
    <?php require '../PARTS/CSS.php'; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../ASSETS/CSS/ml-calendar.css">
    <style>
        body {background-color: #405164;}
        .calendar-day {
            width: 100px;
            height: 100px;
            cursor: pointer;
            border: 1px solid #ccc;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 5px;
        }
        .ml-calendar {
            display: flex;
            height: 100%;
        }
        .calendar-left {
            flex: 1;
            overflow-y: auto;
        }
        .calendar-right {
            flex: 2;
        }
        .clear {
            clear: both;
        }
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .calendar-header h2 {
            margin: 0;
        }
        .calendar-days {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .calendar-day-label {
            width: 100px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require '../PARTS/header.php'; ?>
    <div class="container-fluid">
    <div class="container mt-5 ml-calendar-demo" style="scale: 1.1">
        <div class="ml-calendar">
            <section class="calendar-left">
                <div class="sidebar">
                    <p class="subheading mt-4 mb-4">
                        <?php
                            // Display selected month, day, and year
                            if (isset($_GET['selected_day'])) {
                                echo $thisMonth->format('F ') . htmlspecialchars($_GET['selected_day']) . ', ' . $thisMonth->format('Y');
                            } else {
                                echo $thisMonth->format('F j, Y');
                            }
                        ?>
                    </p>
                    <?php
                        // Calculate total items for selected day
                        $totalItems = isset($eventsByDate[$selectedDay]) ? count($eventsByDate[$selectedDay]) : 0;
                        echo '<h3 class="primary-color">' . $totalItems . ' ' . ($totalItems == 1 ? 'Item' : 'Items') . '</h3>';

                        // Display events for selected day
                        echo '<ul class="calendar-events">';
                        if (isset($eventsByDate[$selectedDay])) {
                            foreach ($eventsByDate[$selectedDay] as $event) {
                                echo '<li>';
                                echo '<p>';
                                echo '<a href="event_details.php?event_id=' . $event['id'] . '" class="event-link">';
                                echo '<strong>' . date('h:i A', strtotime($event['event_start'])) . '</strong> - ';
                                echo htmlspecialchars($event['title']);
                                echo '</a>';
                                echo '</p>';
                                echo '</li>';
                            }
                        } else {
                            echo '<li>No events</li>';
                        }
                        echo '</ul>';
                    ?>
                </div>
            </section>
            <section class="calendar-right">
                <div class="calendar">
                    <section class="calendar-header">
                        <?php
                            // Display current month and year
                            echo '<h2 class="mt-4"><strong>' . $thisMonth->format('F') . '</strong> ' . $thisMonth->format('Y') . '</h2>';
                        ?>
                        <div class="calendar-nav">
                            <a href="#" onclick="changeMonth('prev'); return false;"><i class="fas fa-arrow-left"></i></a>
                            <a href="#" onclick="changeMonth('today'); return false;">Today</a>
                            <a href="#" onclick="changeMonth('next'); return false;"><i class="fas fa-arrow-right"></i></a>
                        </div>
                    </section>
                    <div class="calendar-days">
                        <div class="calendar-day-label">Mon</div>
                        <div class="calendar-day-label">Tue</div>
                        <div class="calendar-day-label">Wed</div>
                        <div class="calendar-day-label">Thu</div>
                        <div class="calendar-day-label">Fri</div>
                        <div class="calendar-day-label">Sat</div>
                        <div class="calendar-day-label">Sun</div>
                    </div>
                    <?php
                        // Generate calendar days
                        $daysInMonth = $thisMonth->format('t');
                        $firstDayOfMonth = new DateTime('first day of ' . $thisMonth->format('F') . ' ' . $thisMonth->format('Y'));
                        $dayOfWeek = $firstDayOfMonth->format('N');
                        
                        echo '<section class="calendar-row">';
                        for ($i = 1; $i < $dayOfWeek; $i++) {
                            echo '<div class="calendar-day inactive"></div>';
                        }

                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $dayKey = (int)$day;
                            $isActiveDay = $dayKey == $selectedDay;
                            $hasEvents = isset($eventsByDate[$dayKey]);

                            $classes = 'calendar-day';
                            if (!$isActiveDay) {
                                $classes .= ' inactive';
                            }
                            if ($hasEvents) {
                                $classes .= ' active';
                            }

                            // Prepare JavaScript function call to change the URL on click
                            $jsFunctionCall = "selectDay(" . $thisMonth->format('Y') . ", " . $thisMonth->format('m') . ", " . $day . ")";

                            echo '<div class="' . $classes . '" onclick="location.href=\'calendar.php?this_month=' . $thisMonth->format('Y-m') . '&selected_day=' . $day . '\';">';
                            echo '<span class="calendar-date">' . $day . '</span>';
                            if ($hasEvents) {
                                echo '<br/><span class="calendar-event">' . count($eventsByDate[$dayKey]) . '</span>';
                            }
                            echo '</div>';

                            // Start new row for next week
                            if (($dayOfWeek + $day - 1) % 7 == 0 && $day != $daysInMonth) {
                                echo '</section><section class="calendar-row">';
                            }
                        }

                        // Fill in remaining days of the week after the last day of the month
                        $remainingDays = 7 - (($dayOfWeek + $daysInMonth - 1) % 7);
                        if ($remainingDays < 7) {
                            for ($i = 0; $i < $remainingDays; $i++) {
                                echo '<div class="calendar-day inactive"></div>';
                            }
                        }

                        echo '</section>';
                    ?>
                </div>
            </section>
            <div class="clear"></div>
        </div>
    </div>
    </div>
    <!-- Footer -->
    <?php require '../PARTS/footer.php'; ?>

    <!-- JS.PHP -->
    <?php require '../PARTS/JS.php'; ?>

    <script>
        function changeMonth(direction) {
            var urlParams = new URLSearchParams(window.location.search);
            var currentMonth = urlParams.get('this_month');
            var newDate;

            if (currentMonth === null || direction === 'today') {
                newDate = new Date(); // Set to today's date
            } else {
                newDate = new Date(currentMonth);
            }

            if (direction === 'prev') {
                newDate.setMonth(newDate.getMonth() - 1);
            } else if (direction === 'next') {
                newDate.setMonth(newDate.getMonth() + 1);
            }

            var newMonth = newDate.getMonth() + 1;
            var newYear = newDate.getFullYear();
            var newUrl = 'calendar.php?this_month=' + newYear + '-' + (newMonth < 10 ? '0' + newMonth : newMonth);

            // Determine selected day
            var selectedDay;
            if (direction === 'today') {
                selectedDay = new Date().getDate(); // Current day
            } else {
                // Check if current month and year match the current date
                var today = new Date();
                if (newDate.getMonth() === today.getMonth() && newDate.getFullYear() === today.getFullYear()) {
                    selectedDay = today.getDate(); // Current day in the current month
                } else {
                    selectedDay = 1; // Default to 1st day of the month
                }
            }

            newUrl += '&selected_day=' + selectedDay;
            window.location.href = newUrl;
        }

        function selectDay(year, month, day) {
            var selectedDate = new Date(year, month - 1, day);
            var selectedMonth = selectedDate.getMonth() + 1;
            var selectedYear = selectedDate.getFullYear();
            var newUrl = 'calendar.php?this_month=' + selectedYear + '-' + (selectedMonth < 10 ? '0' + selectedMonth : selectedMonth) + '&selected_day=' + day;

            window.location.href = newUrl;
        }
    </script>
</body>
</html>
