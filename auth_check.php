<?php
// Avvia sempre la sessione all'inizio
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Controlla se l'ID utente NON è presente nella sessione.
// Se l'utente arriva dall'altro tuo software, questa sessione sarà già attiva e il controllo passerà.
if (!isset($_SESSION['userid'])) {
    // Se non è loggato, reindirizza alla pagina di login e termina lo script.
    header('Location: login.php');
    exit();
}
?>