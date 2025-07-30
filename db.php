<?php
// Impostazioni del database
define('DB_HOST', 'localhost');
define('DB_USER', 'itecna'); // Sostituisci con il tuo utente
define('DB_PASS', '1C53c985@5');     // Sostituisci con la tua password
define('DB_NAME', 'itecna'); // Sostituisci con il nome del tuo DB

// Creazione della connessione
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Imposta la modalità di errore di PDO su eccezione
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERRORE: Impossibile connettersi. " . $e->getMessage());
}
?>