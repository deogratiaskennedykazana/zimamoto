<?php
    session_start();
    // FIX: `if(!$_SESSION)` always evaluates to false because $_SESSION is always an array.
    // Correct check: verify the user's ID is set in the session.
    if (!isset($_SESSION['userid'])) {
        echo "<script>window.location.href='./login.php';</script>";
        exit;
    }
