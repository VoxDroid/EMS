<?php
$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page === 'index.php') {
    // Include links specific to index.php
    echo <<<HTML
    <!-- Icons -->
    <link href="ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="icon">
    <link href="ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="apple-touch-icon">

    <!-- Online Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Fonts -->
    <link href="ASSETS/FONTS/Poppins/Poppins.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="ASSETS/CSS/bootstrap.min.css" rel="stylesheet">
    <link href="ASSETS/CSS/bootstrap-icons.min.css" rel="stylesheet">
    <link href="ASSETS/CSS/bootstrap-icons.css" rel="stylesheet">
    <link href="ASSETS/CSS/FA-all.min.css" rel="stylesheet">

    <!-- Internal CSS -->
    <link href="ASSETS/CSS/custom_design.css" rel="stylesheet" >
HTML;
} else {
    // Include links for other pages (assuming they are in a subdirectory)
    echo <<<HTML
    <!-- UPPER Icons -->
    <link href="../ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="icon">
    <link href="../ASSETS/IMG/EMS_Icons/EMS_Icon.png" rel="apple-touch-icon">

    <!-- Online Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <!-- Fonts -->
    <link href="../ASSETS/FONTS/Poppins/Poppins.css" rel="stylesheet">

    <!-- UPPER Bootstrap CSS -->
    <link href="../ASSETS/CSS/bootstrap.min.css" rel="stylesheet">
    <link href="../ASSETS/CSS/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../ASSETS/CSS/bootstrap-icons.css" rel="stylesheet">
    <link href="../ASSETS/CSS/FA-all.min.css" rel="stylesheet">
    
    <!-- Internal CSS -->
    <link href="../ASSETS/CSS/custom_design.css" rel="stylesheet" >
HTML;
}
?>
