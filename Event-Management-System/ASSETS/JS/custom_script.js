$(document).ready(function() {
    // Initially show only the active item for approved events
    $('.carousel-item.active').fadeIn();

    // Initially show only the active item for ongoing events
    $('.carousel-item.active').fadeIn();

    // Lock carousel during animation for approved events
    var isAnimatingApproved = false;

    // Lock carousel during animation for ongoing events
    var isAnimatingOngoing = false;

    // Show the next item for approved events when the next arrow is clicked
    $('#approvedEventsCarousel .carousel-control-next').click(function(event) {
        event.preventDefault(); // Prevent the default anchor tag behavior
        if (isAnimatingApproved) return;
        isAnimatingApproved = true;

        var $currentItem = $('#approvedEventsCarousel .carousel-item.active');
        var $nextItem = $currentItem.next('.carousel-item');
        if ($nextItem.length === 0) {
            $nextItem = $('#approvedEventsCarousel .carousel-item').first();
        }

        // Disable next button during animation
        $('#approvedEventsCarousel .carousel-control-next').addClass('disabled');

        $currentItem.fadeOut(function() {
            $currentItem.removeClass('active');
            $nextItem.fadeIn(function() {
                $nextItem.addClass('active');
                isAnimatingApproved = false;
                updateCarouselControls('#approvedEventsCarousel');
            });
        });
    });

    // Show the previous item for approved events when the previous arrow is clicked
    $('#approvedEventsCarousel .carousel-control-prev').click(function(event) {
        event.preventDefault(); // Prevent the default anchor tag behavior
        if (isAnimatingApproved) return;
        isAnimatingApproved = true;

        var $currentItem = $('#approvedEventsCarousel .carousel-item.active');
        var $prevItem = $currentItem.prev('.carousel-item');
        if ($prevItem.length === 0) {
            $prevItem = $('#approvedEventsCarousel .carousel-item').last();
        }

        // Disable previous button during animation
        $('#approvedEventsCarousel .carousel-control-prev').addClass('disabled');

        $currentItem.fadeOut(function() {
            $currentItem.removeClass('active');
            $prevItem.fadeIn(function() {
                $prevItem.addClass('active');
                isAnimatingApproved = false;
                updateCarouselControls('#approvedEventsCarousel');
            });
        });
    });

    // Show the next item for ongoing events when the next arrow is clicked
    $('#ongoingEventsCarousel .carousel-control-next').click(function(event) {
        event.preventDefault(); // Prevent the default anchor tag behavior
        if (isAnimatingOngoing) return;
        isAnimatingOngoing = true;

        var $currentItem = $('#ongoingEventsCarousel .carousel-item.active');
        var $nextItem = $currentItem.next('.carousel-item');
        if ($nextItem.length === 0) {
            $nextItem = $('#ongoingEventsCarousel .carousel-item').first();
        }

        // Disable next button during animation
        $('#ongoingEventsCarousel .carousel-control-next').addClass('disabled');

        $currentItem.fadeOut(function() {
            $currentItem.removeClass('active');
            $nextItem.fadeIn(function() {
                $nextItem.addClass('active');
                isAnimatingOngoing = false;
                updateCarouselControls('#ongoingEventsCarousel');
            });
        });
    });

    // Show the previous item for ongoing events when the previous arrow is clicked
    $('#ongoingEventsCarousel .carousel-control-prev').click(function(event) {
        event.preventDefault(); // Prevent the default anchor tag behavior
        if (isAnimatingOngoing) return;
        isAnimatingOngoing = true;

        var $currentItem = $('#ongoingEventsCarousel .carousel-item.active');
        var $prevItem = $currentItem.prev('.carousel-item');
        if ($prevItem.length === 0) {
            $prevItem = $('#ongoingEventsCarousel .carousel-item').last();
        }

        // Disable previous button during animation
        $('#ongoingEventsCarousel .carousel-control-prev').addClass('disabled');

        $currentItem.fadeOut(function() {
            $currentItem.removeClass('active');
            $prevItem.fadeIn(function() {
                $prevItem.addClass('active');
                isAnimatingOngoing = false;
                updateCarouselControls('#ongoingEventsCarousel');
            });
        });
    });

    // Function to update carousel control button states
    function updateCarouselControls(carouselID) {
        var $activeItem = $(carouselID + ' .carousel-item.active');
        var $nextControl = $(carouselID + ' .carousel-control-next');
        var $prevControl = $(carouselID + ' .carousel-control-prev');

        // Enable/disable next button
        if ($activeItem.is(':last-child')) {
            $nextControl.addClass('disabled');
        } else {
            $nextControl.removeClass('disabled');
        }

        // Enable/disable previous button
        if ($activeItem.is(':first-child')) {
            $prevControl.addClass('disabled');
        } else {
            $prevControl.removeClass('disabled');
        }

        // Update carousel indicators
        var activeIndex = $activeItem.index();
        $(carouselID + ' .carousel-indicators li').removeClass('active');
        $(carouselID + ' .carousel-indicators li').eq(activeIndex).addClass('active');
    }

    // Automatically slide the approved events carousel
    setInterval(function() {
        if (!isAnimatingApproved) {
            $('#approvedEventsCarousel .carousel-control-next').click();
        }
    }, 5000); // Adjust the interval as needed

    // Automatically slide the ongoing events carousel
    setInterval(function() {
        if (!isAnimatingOngoing) {
            $('#ongoingEventsCarousel .carousel-control-next').click();
        }
    }, 5000); // Adjust the interval as needed
});
