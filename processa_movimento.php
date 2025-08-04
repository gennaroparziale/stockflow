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
$source_page = isset($_POST['source']) ? $_POST['source'] : 'mobile'; // Può essere 'inventario' o 'mobile'

// Controlla che i dati necessari siano presenti e validi.
if (($action !== 'scarico' && $action !== 'carico') || $articolo_id === false || $quantita === false || $quantita <= 0) {
    die("Errore critico: Dati per il movimento non validi o mancanti.");
}


// === 2. ESECUZIONE DELLA LOGICA SUL DATABASE ===
try {
    // Inizia una transazione: o tutte le operazioni riescono, o nessuna viene salvata.
    $pdo->beginTransaction();

    if ($action === 'scarico') {
        // --- LOGICA DI SCARICO ---
        // Recupera la giacenza attuale e blocca la riga per l'aggiornamento.
        $stmt_check = $pdo->prepare("SELECT giacenza FROM inventario WHERE articolo_id = ? FOR UPDATE");
        $stmt_check->execute(array($articolo_id));
        $giacenza_attuale = $stmt_check->fetchColumn();

        if ($giacenza_attuale === false) {
            throw new Exception("Articolo con ID $articolo_id non trovato nell'inventario.");
        }
        if ($giacenza_attuale < $quantita) {
            throw new Exception("Quantità non disponibile a magazzino. Richiesti: $quantita, Disponibili: $giacenza_attuale.");
        }

        // A. Aggiorna la giacenza
        $stmt_inventario = $pdo->prepare("UPDATE inventario SET giacenza = giacenza - ? WHERE articolo_id = ?");
        $stmt_inventario->execute(array($quantita, $articolo_id));

        // B. Registra il movimento
        $stmt_movimento = $pdo->prepare("INSERT INTO movimenti (articolo_id, tipo_movimento, quantita) VALUES (?, 'scarico', ?)");
        $stmt_movimento->execute(array($articolo_id, $quantita));

    } elseif ($action === 'carico') {
        // --- LOGICA DI CARICO ---
        // A. Aggiorna la giacenza
        $stmt_inventario = $pdo->prepare("UPDATE inventario SET giacenza = giacenza + ? WHERE articolo_id = ?");
        $stmt_inventario->execute(array($quantita, $articolo_id));

        // B. Registra il movimento
        $stmt_movimento = $pdo->prepare("INSERT INTO movimenti (articolo_id, tipo_movimento, quantita) VALUES (?, 'carico', ?)");
        $stmt_movimento->execute(array($articolo_id, $quantita));
    }

    // Se tutto è andato a buon fine, conferma la transazione
    $pdo->commit();

    // === 3. REINDIRIZZAMENTO FINALE ===
    if ($source_page === 'inventario') {
        // Aggiungo i parametri di ricerca per mantenere i filtri attivi
        $redirect_url = 'inventario.php?movimento=ok';
        header('Location: ' . $redirect_url);
    } else { // default to mobile
        header('Location: dashboard_mobile.php?scarico=ok');
    }
    exit();

} catch (Exception $e) {
    // Se si è verificato un qualsiasi errore, annulla tutte le operazioni.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Mostra un errore dettagliato per il debug.
    die("ERRORE DURANTE IL MOVIMENTO DELL'ARTICOLO: " . $e->getMessage());
}
?>