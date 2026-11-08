<?php
/**
 * SIMAYA - Index Entry Point
 */
session_start();

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}

