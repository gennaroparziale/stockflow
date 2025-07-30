<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';
// --- LOGICA INIZIALE PER CARICARE I DATI ---
$ordine_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$in_modifica = ($ordine_id > 0);
$ordine = null;
$righe_ordine = array();
$prossimo_riferimento = ''; // Variabile per il nuovo numero
// Se stiamo creando un nuovo ordine, recuperiamo il prossimo numero
$stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'prossimo_numero_ordine'");
$stmt->execute();
$prossimo_numero = $stmt->fetchColumn();
// Possiamo anche formattarlo con l'anno corrente (opzionale)
$prossimo_riferimento = "PO-" . date('Y') . "-" . str_pad($prossimo_numero, 4, '0', STR_PAD_LEFT);
// Carica dati di fornitori e articoli per i menu a tendina
$fornitori = $pdo->query("SELECT id, nome_fornitore FROM fornitori ORDER BY nome_fornitore")->fetchAll(PDO::FETCH_ASSOC);
$articoli = $pdo->query("SELECT id, codice_articolo, descrizione FROM articoli ORDER BY descrizione")->fetchAll(PDO::FETCH_ASSOC);

if ($in_modifica) {

    // Carica l'intestazione dell'ordine
    $stmt_ordine = $pdo->prepare("SELECT * FROM ordini_fornitore WHERE id = ?");
    $stmt_ordine->execute(array($ordine_id));
    $ordine = $stmt_ordine->fetch(PDO::FETCH_ASSOC);
    if (!$ordine) die("Ordine non trovato.");

    // Carica le righe dell'ordine (gli articoli)
    $stmt_righe = $pdo->prepare("
        SELECT r.id, r.articolo_id, r.quantita_ordinata, r.prezzo_unitario, r.quantita_ricevuta,
               a.descrizione, a.codice_articolo
        FROM ordini_fornitore_righe r
        JOIN articoli a ON r.articolo_id = a.id
        WHERE r.ordine_id = ? ORDER BY r.id
    ");
    $stmt_righe->execute(array($ordine_id));
    $righe_ordine = $stmt_righe->fetchAll(PDO::FETCH_ASSOC);
}

// Include la navbar
$currentPage = 'gestione_ordini.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $in_modifica ? 'Dettaglio Ordine #' . $ordine_id : 'Nuovo Ordine'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <h3><?php echo $in_modifica ? 'Modifica Ordine #' . htmlspecialchars($ordine['riferimento'] ?: $ordine_id) : 'Crea Nuovo Ordine'; ?></h3>
    <div class="card p-3 mb-4">
        <form action="processa_ordine.php" method="POST">
            <input type="hidden" name="ordine_id" value="<?php echo $ordine_id; ?>">
            <div class="row">
                <div class="col-md-5">
                    <label for="fornitore_id" class="form-label">Fornitore</label>
                    <select name="fornitore_id" id="fornitore_id" class="form-select" <?php if ($in_modifica) echo 'disabled'; ?>>
                        <option value="">-- Seleziona --</option>
                        <?php foreach ($fornitori as $fornitore): ?>
                            <option value="<?php echo $fornitore['id']; ?>" <?php if ($in_modifica && $fornitore['id'] == $ordine['fornitore_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($fornitore['nome_fornitore']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="data_ordine" class="form-label">Data Ordine</label>
                    <input type="date" name="data_ordine" id="data_ordine" class="form-control" value="<?php echo $in_modifica ? htmlspecialchars($ordine['data_ordine']) : date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <label for="riferimento" class="form-label">Riferimento</label>
                    <input type="text" name="riferimento" id="riferimento" class="form-control" value="<?php echo $in_modifica ? htmlspecialchars($ordine['riferimento']) : htmlspecialchars($prossimo_riferimento); ?>">
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" name="action" value="salva_intestazione" class="btn btn-success">
                    <i class="bi bi-save"></i> <?php echo $in_modifica ? 'Salva Modifiche Intestazione' : 'Crea e Aggiungi Articoli'; ?>
                </button>
            </div>
        </form>
    </div>

    <?php if ($in_modifica): ?>

        <div class="card mb-4">
            <div class="card-header">Articoli nell'Ordine</div>
            <div class="card-body">
                <table class="table table-sm table-striped">
                    <thead>
                    <tr>
                        <th>Articolo</th>
                        <th class="text-center">Q.tà Ordinata</th>
                        <th class="text-center">Q.tà Ricevuta</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($righe_ordine)): ?>
                        <tr><td colspan="4" class="text-center">Nessun articolo aggiunto.</td></tr>
                    <?php else: ?>
                        <?php foreach($righe_ordine as $riga): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($riga['codice_articolo'] . ' - ' . $riga['descrizione']); ?></td>
                                <td class="text-center"><?php echo $riga['quantita_ordinata']; ?></td>
                                <td class="text-center fw-bold"><?php echo $riga['quantita_ricevuta']; ?></td>
                                <td class="text-end">
                                    <?php if (($ordine['stato'] == 'Inviato' || $ordine['stato'] == 'Parzialmente Evaso') && $riga['quantita_ricevuta'] < $riga['quantita_ordinata']): ?>
                                        <button class="btn btn-sm btn-success" title="Ricevi Merce" onclick="apriModaleRicezione(
                                        <?php echo $riga['id']; ?>,
                                                '<?php echo htmlspecialchars($riga['codice_articolo'], ENT_QUOTES); ?>',
                                        <?php echo $riga['quantita_ordinata']; ?>,
                                        <?php echo $riga['quantita_ricevuta']; ?>,
                                        <?php echo $riga['articolo_id']; ?>
                                                )">
                                            <i class="bi bi-box-arrow-in-down"></i> Ricevi
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($ordine['stato'] == 'Bozza'): ?>
                                        <a href="processa_ordine.php?action=rimuovi_riga&riga_id=<?php echo $riga['id']; ?>&ordine_id=<?php echo $ordine_id; ?>" class="btn btn-sm btn-danger" title="Rimuovi">&times;</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-3">
            <form action="processa_ordine.php" method="POST">
                <h5 class="mb-3">Aggiungi Articolo</h5>
                <input type="hidden" name="ordine_id" value="<?php echo $ordine_id; ?>">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="articolo_id" class="form-label">Articolo</label>
                        <select name="articolo_id" id="articolo_id" class="form-select">
                            <option value="">-- Seleziona un articolo --</option>
                            <?php foreach($articoli as $articolo): ?>
                                <option value="<?php echo $articolo['id']; ?>"><?php echo htmlspecialchars($articolo['codice_articolo'] . ' - ' . $articolo['descrizione']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="quantita_ordinata" class="form-label">Quantità</label>
                        <input type="number" name="quantita_ordinata" id="quantita_ordinata" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="action" value="aggiungi_riga" class="btn btn-primary w-100">Aggiungi</button>
                    </div>
                </div>
            </form>
        </div>
        <hr>
        <div class="card p-3 mb-4">
            <h5 class="card-title">Azioni sull'Ordine</h5>
            <div class="card-body">
                <?php if ($ordine['stato'] == 'Bozza'): ?>
                    <form action="processa_ordine.php" method="POST" onsubmit="return confirm('Sei sicuro di voler finalizzare e inviare l\'ordine? Lo stato passerà a Inviato.');" class="mb-3">
                        <input type="hidden" name="ordine_id" value="<?php echo $ordine_id; ?>">
                        <button type="submit" name="action" value="invia_ordine_email" class="btn btn-info">
                            <i class="bi bi-send"></i> Finalizza e Invia Ordine
                        </button>
                    </form>
                    <hr>
                <?php endif; ?>
                <form action="processa_ordine.php" method="POST" class="row g-3 align-items-center">
                    <input type="hidden" name="ordine_id" value="<?php echo $ordine_id; ?>">
                    <div class="col-auto"><label for="stato" class="form-label mb-0">Cambia stato in:</label></div>
                    <div class="col-auto">
                        <select name="stato" id="stato" class="form-select">
                            <option value="Bozza" <?php if($ordine['stato'] == 'Bozza') echo 'selected'; ?>>Bozza</option>
                            <option value="Inviato" <?php if($ordine['stato'] == 'Inviato') echo 'selected'; ?>>Inviato</option>
                            <option value="Parzialmente Evaso" <?php if($ordine['stato'] == 'Parzialmente Evaso') echo 'selected'; ?>>Parzialmente Evaso</option>
                            <option value="Evaso" <?php if($ordine['stato'] == 'Evaso') echo 'selected'; ?>>Evaso</option>
                            <option value="Annullato" <?php if($ordine['stato'] == 'Annullato') echo 'selected'; ?>>Annullato</option>
                        </select>
                    </div>
                    <div class="col-auto"><button type="submit" name="action" value="aggiorna_stato" class="btn btn-secondary">Salva Stato</button></div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="ricezioneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="ricezioneForm" action="processa_ordine.php" method="POST">
                <div class="modal-header"><h5 class="modal-title">Ricevi Merce</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="ordine_id" value="<?php echo $ordine_id; ?>">
                    <input type="hidden" name="riga_ordine_id" id="riga_ordine_id">
                    <input type="hidden" name="articolo_id" id="articolo_id_modale">
                    <p>Articolo: <strong id="codiceArticoloModale"></strong></p>
                    <p>Quantità Ordinata: <strong id="qtaOrdinataModale"></strong><br>Già Ricevuta: <strong id="qtaRicevutaModale"></strong></p>
                    <div class="mb-3">
                        <label for="quantita_da_ricevere" class="form-label">Quantità che stai ricevendo ora <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantita_da_ricevere" name="quantita_da_ricevere" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" name="action" value="ricevi_merce" class="btn btn-success">Conferma e Carica a Magazzino</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var ricezioneModal = new bootstrap.Modal(document.getElementById('ricezioneModal'));
    function apriModaleRicezione(rigaId, codiceArticolo, qtaOrdinata, qtaGiaRicevuta, articoloId) {
        document.getElementById('riga_ordine_id').value = rigaId;
        document.getElementById('articolo_id_modale').value = articoloId;
        document.getElementById('codiceArticoloModale').textContent = codiceArticolo;
        document.getElementById('qtaOrdinataModale').textContent = qtaOrdinata;
        document.getElementById('qtaRicevutaModale').textContent = qtaGiaRicevuta;
        var maxRicevibile = qtaOrdinata - qtaGiaRicevuta;
        var inputQuantita = document.getElementById('quantita_da_ricevere');
        inputQuantita.value = maxRicevibile;
        inputQuantita.max = maxRicevibile;
        ricezioneModal.show();
    }
</script>

</body>
</html>