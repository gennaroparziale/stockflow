<?php
require_once 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // MODIFICA: La query ora legge dalla tabella "UTENTI" (in maiuscolo come indicato)
    $stmt = $pdo->prepare("SELECT * FROM UTENTI WHERE username = ?");
    $stmt->execute(array($username));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // MODIFICA: La verifica della password ora usa md5()
    // 1. Calcola l'hash MD5 della password inviata dall'utente.
    // 2. Confronta il risultato con l'hash MD5 salvato nel database.
    if ($user && $user['password'] === md5($password)) {
        // Credenziali corrette, imposta le variabili di sessione
        $_SESSION['userid'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['userLevel'] = $user['userLevel'];
        $_SESSION['nome'] = $user['nome'];
        $_SESSION['cognome'] = $user['cognome'];

        // Reindirizza alla dashboard
        header('Location: index.php');
        exit();
    } else {
        // Credenziali errate, torna al login con un errore
        header('Location: login.php?error=1');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>