<?php
session_start();

// Si l'utilisateur n'est pas connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // On le redirige vers la page de login
    header('Location: login.php');
    exit;
}
?>