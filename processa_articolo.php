<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestione_articoli.php');
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    if ($action === 'add') {
        // --- LOGICA DI AGGIUNTA ---
        $pdo->beginTransaction();

        $sql_articolo = "INSERT INTO articoli (codice_articolo, descrizione, prezzo_acquisto, scorta_minima, fornitore_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_articolo = $pdo->prepare($sql_articolo);
        $stmt_articolo->execute(array(
            $_POST['codice_articolo'],
            $_POST['descrizione'],
            $_POST['prezzo_acquisto'],
            $_POST['scorta_minima'],
            !empty($_POST['fornitore_id']) ? $_POST['fornitore_id'] : null
        ));
        $nuovo_articolo_id = $pdo->lastInsertId();

        $sql_inventario = "INSERT INTO inventario (articolo_id, giacenza) VALUES (?, ?)";
        $stmt_inventario = $pdo->prepare($sql_inventario);
        $stmt_inventario->execute(array($nuovo_articolo_id, 0));

        $pdo->commit();

    } elseif ($action === 'edit') {
        // --- LOGICA DI MODIFICA ---
        $sql = "UPDATE articoli SET 
                    codice_articolo = ?, 
                    descrizione = ?, 
                    prezzo_acquisto = ?, 
                    scorta_minima = ?, 
                    fornitore_id = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            $_POST['codice_articolo'],
            $_POST['descrizione'],
            $_POST['prezzo_acquisto'],
            $_POST['scorta_minima'],
            !empty($_POST['fornitore_id']) ? $_POST['fornitore_id'] : null,
            $_POST['id']
        ));

    } elseif ($action === 'delete') {
        // --- LOGICA DI ELIMINAZIONE ---
        // Grazie a ON DELETE CASCADE definito nello schema del DB,
        // eliminando l'articolo verranno cancellati in automatico
        // anche i record collegati in 'inventario' e 'movimenti'.
        $sql = "DELETE FROM articoli WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($_POST['id']));
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("ERRORE DATABASE: " . $e->getMessage());
}

// Reindirizza alla pagina di gestione
header('Location: gestione_articoli.php');
exit();
?>