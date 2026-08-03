<?php
    session_start();
    if(!isset($_COOKIE['uemail']))
    {
        header('location: nlogin.php');
        die();
    }

    if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
        $_SESSION['uemail'] = $_COOKIE['uemail'];
    }
    ?>

    <?php
    include("navigation.php");
    include("moviesinsertion.php");
    ?>
    
