<?php
// index.php - Homepage Assembler
session_start();
require_once 'database/config.php';
require_once 'inc/functions.php';

$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $cartCount = 0;
}

include 'src/Views/layouts/header.php';
include 'src/Views/home/hero.php';
include 'src/Views/home/services.php';
include 'src/Views/home/hardware.php';
include 'src/Views/home/about.php';
include 'src/Views/home/locations.php';
include 'src/Views/layouts/footer.php';
?>