<?php
require_once 'db.php';

// Validazione iniziale della richiesta
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: gestione_ordini.php');
    exit();
}

// Recupero dei parametri comuni
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$ordine_id = isset($_REQUEST['ordine_id']) ? (int)$_REQUEST['ordine_id'] : 0;

try {
    // --- 1. AZIONE: SALVA INTESTAZIONE (CREA O MODIFICA) ---
    if ($action === 'salva_intestazione') {
        $data_ordine = $_POST['data_ordine'];
        $riferimento = $_POST['riferimento'];

        if ($ordine_id > 0) { // Modalità MODIFICA
            $sql = "UPDATE ordini_fornitore SET data_ordine = ?, riferimento = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($data_ordine, $riferimento, $ordine_id));
        } else { // Modalità CREAZIONE
            $fornitore_id = $_POST['fornitore_id'];

            $pdo->beginTransaction();
            try {
                // Inserisci il nuovo ordine
                $sql = "INSERT INTO ordini_fornitore (fornitore_id, data_ordine, riferimento, stato) VALUES (?, ?, ?, 'Bozza')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array($fornitore_id, $data_ordine, $riferimento));
                $ordine_id = $pdo->lastInsertId();

                // Aggiorna il contatore nella tabella impostazioni
                $stmt_update = $pdo->prepare("UPDATE impostazioni SET valore = valore + 1 WHERE chiave = 'prossimo_numero_ordine'");
                $stmt_update->execute();

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                die("Errore durante la creazione dell'ordine: " . $e->getMessage());
            }
        }
    }

    // --- 2. AZIONE: AGGIUNGI RIGA A ORDINE ---
    elseif ($action === 'aggiungi_riga') {
        if ($ordine_id > 0 && isset($_POST['articolo_id']) && !empty($_POST['articolo_id'])) {
            $articolo_id = (int)$_POST['articolo_id'];
            $quantita_da_aggiungere = (int)$_POST['quantita_ordinata'];

            $stmt_check = $pdo->prepare("SELECT id FROM ordini_fornitore_righe WHERE ordine_id = ? AND articolo_id = ?");
            $stmt_check->execute(array($ordine_id, $articolo_id));
            $riga_esistente_id = $stmt_check->fetchColumn();

            if ($riga_esistente_id) {
                $sql = "UPDATE ordini_fornitore_righe SET quantita_ordinata = quantita_ordinata + ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array($quantita_da_aggiungere, $riga_esistente_id));
            } else {
                $stmt_prezzo = $pdo->prepare("SELECT prezzo_acquisto FROM articoli WHERE id = ?");
                $stmt_prezzo->execute(array($articolo_id));
                $prezzo = $stmt_prezzo->fetchColumn() ?: 0;
                $sql = "INSERT INTO ordini_fornitore_righe (ordine_id, articolo_id, quantita_ordinata, prezzo_unitario) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array($ordine_id, $articolo_id, $quantita_da_aggiungere, $prezzo));
            }
        }
    }

    // --- 3. AZIONE: RIMUOVI RIGA DA ORDINE ---
    elseif ($action === 'rimuovi_riga') {
        $riga_id = isset($_GET['riga_id']) ? (int)$_GET['riga_id'] : 0;
        if ($riga_id > 0) {
            $sql = "DELETE FROM ordini_fornitore_righe WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($riga_id));
        }
    }

    // --- 4. AZIONE: RICEVI MERCE ---
    elseif ($action === 'ricevi_merce') {
        $riga_ordine_id = isset($_POST['riga_ordine_id']) ? (int)$_POST['riga_ordine_id'] : 0;
        $articolo_id = isset($_POST['articolo_id']) ? (int)$_POST['articolo_id'] : 0;
        $quantita_ricevuta_ora = isset($_POST['quantita_da_ricevere']) ? (int)$_POST['quantita_da_ricevere'] : 0;

        if ($riga_ordine_id > 0 && $articolo_id > 0 && $quantita_ricevuta_ora > 0) {
            $pdo->beginTransaction();
            try {
                $stmt_riga = $pdo->prepare("SELECT quantita_ordinata, quantita_ricevuta FROM ordini_fornitore_righe WHERE id = ?");
                $stmt_riga->execute(array($riga_ordine_id));
                $riga_info = $stmt_riga->fetch(PDO::FETCH_ASSOC);

                if (!$riga_info || ($riga_info['quantita_ricevuta'] + $quantita_ricevuta_ora) > $riga_info['quantita_ordinata']) {
                    throw new Exception("La quantità ricevuta supera quella ordinata o la riga non è valida.");
                }

                $stmt_update_riga = $pdo->prepare("UPDATE ordini_fornitore_righe SET quantita_ricevuta = quantita_ricevuta + ? WHERE id = ?");
                $stmt_update_riga->execute(array($quantita_ricevuta_ora, $riga_ordine_id));

                $stmt_mov = $pdo->prepare("INSERT INTO movimenti (articolo_id, tipo_movimento, quantita) VALUES (?, 'carico', ?)");
                $stmt_mov->execute(array($articolo_id, $quantita_ricevuta_ora));

                $stmt_inv = $pdo->prepare("UPDATE inventario SET giacenza = giacenza + ? WHERE articolo_id = ?");
                $stmt_inv->execute(array($quantita_ricevuta_ora, $articolo_id));

                $stmt_check_ordine = $pdo->prepare("SELECT SUM(quantita_ordinata) as totale_ordinato, SUM(quantita_ricevuta) as totale_ricevuto FROM ordini_fornitore_righe WHERE ordine_id = ?");
                $stmt_check_ordine->execute(array($ordine_id));
                $stato_ordine_info = $stmt_check_ordine->fetch(PDO::FETCH_ASSOC);

                $nuovo_stato = 'Parzialmente Evaso';
                if ($stato_ordine_info['totale_ricevuto'] >= $stato_ordine_info['totale_ordinato']) {
                    $nuovo_stato = 'Evaso';
                }

                $stmt_update_ordine = $pdo->prepare("UPDATE ordini_fornitore SET stato = ? WHERE id = ?");
                $stmt_update_ordine->execute(array($nuovo_stato, $ordine_id));

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                die("ERRORE: " . $e->getMessage());
            }
        }
    }

    // --- 5. AZIONE: INVIA ORDINE VIA EMAIL ---
    elseif ($action === 'invia_ordine_email') {
        if ($ordine_id > 0) {
            $stmt_info = $pdo->prepare("SELECT o.riferimento, o.data_ordine, f.nome_fornitore FROM ordini_fornitore o JOIN fornitori f ON o.fornitore_id = f.id WHERE o.id = ?");
            $stmt_info->execute(array($ordine_id));
            $ordine_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
            $stmt_righe = $pdo->prepare("SELECT r.quantita_ordinata, r.prezzo_unitario, a.codice_articolo, a.descrizione FROM ordini_fornitore_righe r JOIN articoli a ON r.articolo_id = a.id WHERE r.ordine_id = ? ORDER BY a.descrizione");
            $stmt_righe->execute(array($ordine_id));
            $righe_ordine = $stmt_righe->fetchAll(PDO::FETCH_ASSOC);
            $to = "amministrazione@tuosito.com";
            $subject = "Nuovo Ordine a Fornitore: " . ($ordine_info['riferimento'] ?: '#' . $ordine_id);
            $body = "<h1>Riepilogo Ordine Fornitore</h1><p><strong>Riferimento:</strong> " . htmlspecialchars($ordine_info['riferimento'] ?: '#' . $ordine_id) . "</p><p><strong>Fornitore:</strong> " . htmlspecialchars($ordine_info['nome_fornitore']) . "</p><p><strong>Data:</strong> " . date("d/m/Y", strtotime($ordine_info['data_ordine'])) . "</p><h3>Dettaglio Articoli:</h3><table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse: collapse;'><thead style='background-color:#f2f2f2;'><tr><th>Codice</th><th>Descrizione</th><th>Quantità</th><th>Prezzo Unitario</th><th>Subtotale</th></tr></thead><tbody>";
            $totale_ordine = 0;
            foreach ($righe_ordine as $riga) {
                $subtotale = $riga['quantita_ordinata'] * $riga['prezzo_unitario'];
                $totale_ordine += $subtotale;
                $body .= "<tr><td>" . htmlspecialchars($riga['codice_articolo']) . "</td><td>" . htmlspecialchars($riga['descrizione']) . "</td><td>" . $riga['quantita_ordinata'] . "</td><td style='text-align:right;'>&euro;" . number_format($riga['prezzo_unitario'], 2, ',', '.') . "</td><td style='text-align:right;'>&euro;" . number_format($subtotale, 2, ',', '.') . "</td></tr>";
            }
            $body .= "</tbody><tfoot><tr><th colspan='4' style='text-align:right;'>Totale Ordine</th><th style='text-align:right;'>&euro;" . number_format($totale_ordine, 2, ',', '.') . "</th></tr></tfoot></table>";
            $headers = "MIME-Version: 1.0\r\n" . "Content-type:text/html;charset=UTF-8\r\n" . 'From: <magazzino@tuosito.com>' . "\r\n";
            mail($to, $subject, $body, $headers);
            $stmt_update = $pdo->prepare("UPDATE ordini_fornitore SET stato = 'Inviato' WHERE id = ?");
            $stmt_update->execute(array($ordine_id));
        }
    }

    // --- 6. AZIONE: AGGIORNA STATO ORDINE ---
    elseif ($action === 'aggiorna_stato') {
        if ($ordine_id > 0 && isset($_POST['stato'])) {
            $nuovo_stato = $_POST['stato'];
            $stmt = $pdo->prepare("UPDATE ordini_fornitore SET stato = ? WHERE id = ?");
            $stmt->execute(array($nuovo_stato, $ordine_id));
        }
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("ERRORE DATABASE: " . $e->getMessage());
}

// Reindirizzamento finale alla pagina di dettaglio
header('Location: dettaglio_ordine.php?id=' . $ordine_id);
exit();
?>