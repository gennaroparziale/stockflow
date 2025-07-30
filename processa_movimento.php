<?php
require_once 'db.php';

// === 1. VALIDAZIONE RIGOROSA DEI DATI IN INGRESSO ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Accetta solo richieste di tipo POST
    header('Location: inventario.php');
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$articolo_id = isset($_POST['articolo_id']) ? filter_var($_POST['articolo_id'], FILTER_VALIDATE_INT) : false;
$quantita = isset($_POST['quantita']) ? filter_var($_POST['quantita'], FILTER_VALIDATE_INT) : false;

// Controlla che tutti i dati necessari per lo scarico siano presenti e validi.
if ($action !== 'scarico' || $articolo_id === false || $quantita === false || $quantita <= 0) {
    // Se i dati non sono validi, termina l'esecuzione con un messaggio di errore.
    die("Errore critico: Dati per lo scarico non validi o mancanti. Controlla il form.");
}


// === 2. ESECUZIONE DELLA LOGICA DI SCARICO SUL DATABASE ===
try {
    // Inizia una transazione: o tutte le operazioni riescono, o nessuna viene salvata.
    $pdo->beginTransaction();

    // Recupera la giacenza attuale e blocca la riga per l'aggiornamento con "FOR UPDATE".
    // Questo previene problemi di concorrenza (es. due utenti che scaricano lo stesso articolo nello stesso istante).
    $stmt_check = $pdo->prepare("SELECT giacenza FROM inventario WHERE articolo_id = ? FOR UPDATE");
    $stmt_check->execute(array($articolo_id));
    $giacenza_attuale = $stmt_check->fetchColumn();

    if ($giacenza_attuale === false) {
        // L'articolo non esiste nell'inventario, impossibile procedere.
        throw new Exception("Articolo con ID $articolo_id non trovato nell'inventario.");
    }

    // Controlla se la quantità richiesta è effettivamente disponibile
    if ($giacenza_attuale < $quantita) {
        throw new Exception("Quantità non disponibile a magazzino. Richiesti: $quantita, Disponibili: $giacenza_attuale.");
    }

    // A. Aggiorna la giacenza nella tabella 'inventario'
    $stmt_inventario = $pdo->prepare("UPDATE inventario SET giacenza = giacenza - ? WHERE articolo_id = ?");
    $stmt_inventario->execute(array($quantita, $articolo_id));

    // B. Registra il movimento nello storico per tracciabilità
    $stmt_movimento = $pdo->prepare("INSERT INTO movimenti (articolo_id, tipo_movimento, quantita) VALUES (?, 'scarico', ?)");
    $stmt_movimento->execute(array($articolo_id, $quantita));

    // Se A e B sono andate a buon fine, conferma la transazione rendendo le modifiche permanenti.
    $pdo->commit();

    // === 3. REINDIRIZZAMENTO FINALE (COME RICHIESTO) ===
    // Torna alla dashboard mobile mostrando un messaggio di successo.
    header('Location: dashboard_mobile.php?scarico=ok');
    exit();

} catch (Exception $e) {
    // Se si è verificato un qualsiasi errore durante il try{}, annulla tutte le operazioni.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Mostra un errore dettagliato per il debug.
    die("ERRORE DURANTE LO SCARICO DELL'ARTICOLO: " . $e->getMessage());
}
?>