<?php
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page === 'index.php') {
    // Include links specific to index.php
    echo <<<HTML
    <!-- Icons -->
    <link href="ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="icon">
    <link href="ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="ASSETS/FONTS/Poppins/Poppins.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="ASSETS/CSS/bootstrap.min.css" rel="stylesheet">
    <link href="ASSETS/CSS/bootstrap-icons.min.css" rel="stylesheet">
    <link href="ASSETS/CSS/bootstrap-icons.css" rel="stylesheet">
    <link href="ASSETS/CSS/FA-all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="ASSETS/CSS/custom_style.css" rel="stylesheet">
HTML;
} else {
    // Include links for other pages (assuming they are in a subdirectory)
    echo <<<HTML
    <!-- UPPER Icons -->
    <link href="../ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="icon">
    <link href="../ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="../ASSETS/FONTS/Poppins/Poppins.css" rel="stylesheet">

    <!-- UPPER Bootstrap CSS -->
    <link href="../ASSETS/CSS/bootstrap.min.css" rel="stylesheet">
    <link href="../ASSETS/CSS/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../ASSETS/CSS/bootstrap-icons.css" rel="stylesheet">
    <link href="../ASSETS/CSS/FA-all.min.css" rel="stylesheet">

    <!-- UPPER Custom CSS -->
    <link href="../ASSETS/CSS/custom_style.css" rel="stylesheet">
HTML;
}
?>
