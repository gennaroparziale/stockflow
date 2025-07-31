<?php
require_once 'db.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestione_articoli.php');
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$articolo_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

try {
    $pdo->beginTransaction();

    if ($action === 'add') {
        // --- LOGICA DI AGGIUNTA ---
        $sql_articolo = "INSERT INTO articoli (codice_articolo, descrizione, prezzo_acquisto, scorta_minima, fornitore_id, categoria_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_articolo = $pdo->prepare($sql_articolo);
        $stmt_articolo->execute(array(
            $_POST['codice_articolo'],
            $_POST['descrizione'],
            !empty($_POST['prezzo_acquisto']) ? $_POST['prezzo_acquisto'] : 0,
            !empty($_POST['scorta_minima']) ? $_POST['scorta_minima'] : 0,
            !empty($_POST['fornitore_id']) ? $_POST['fornitore_id'] : null,
            !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null
        ));
        $articolo_id = $pdo->lastInsertId();

        $sql_inventario = "INSERT INTO inventario (articolo_id, giacenza) VALUES (?, 0)";
        $stmt_inventario = $pdo->prepare($sql_inventario);
        $stmt_inventario->execute(array($articolo_id));

    } elseif ($action === 'edit') {
        // --- LOGICA DI MODIFICA ---
        $sql = "UPDATE articoli SET 
                    codice_articolo = ?, descrizione = ?, prezzo_acquisto = ?, 
                    scorta_minima = ?, fornitore_id = ?, categoria_id = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            $_POST['codice_articolo'],
            $_POST['descrizione'],
            !empty($_POST['prezzo_acquisto']) ? $_POST['prezzo_acquisto'] : 0,
            !empty($_POST['scorta_minima']) ? $_POST['scorta_minima'] : 0,
            !empty($_POST['fornitore_id']) ? $_POST['fornitore_id'] : null,
            !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null,
            $articolo_id
        ));

    } elseif ($action === 'delete') {
        // --- LOGICA DI ELIMINAZIONE ---
        $sql = "DELETE FROM articoli WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($articolo_id));
        $pdo->commit();
        header('Location: gestione_articoli.php');
        exit();
    }

    // --- GESTIONE VALORI PROPRIETÀ DINAMICHE (per add e edit) ---
    if (($action === 'add' || $action === 'edit') && $articolo_id > 0) {
        // Prima cancello i vecchi valori per semplicità
        $stmt_delete = $pdo->prepare("DELETE FROM valori_proprieta WHERE id_articolo = ?");
        $stmt_delete->execute(array($articolo_id));

        // Inserisco i nuovi valori se sono stati inviati
        if (isset($_POST['prop']) && is_array($_POST['prop'])) {
            $stmt_insert = $pdo->prepare("INSERT INTO valori_proprieta (id_articolo, id_proprieta, valore) VALUES (?, ?, ?)");
            foreach ($_POST['prop'] as $id_proprieta => $valore) {
                if (!empty($valore)) { // Salva solo se il valore non è vuoto
                    $stmt_insert->execute(array($articolo_id, (int)$id_proprieta, trim($valore)));
                }
            }
        }
    }


    $pdo->commit();

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("ERRORE DATABASE: " . $e->getMessage());
}

// Reindirizza alla pagina di gestione
header('Location: gestione_articoli.php');
exit();
?>