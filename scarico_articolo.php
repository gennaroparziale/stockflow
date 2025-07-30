<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';
// 1. Recupero e validazione dell'ID articolo dall'URL
$articolo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($articolo_id <= 0) {
    die("ID articolo non valido o mancante.");
}

// 2. Carico i dati dell'articolo e la sua giacenza
$stmt = $pdo->prepare("
    SELECT a.id, a.codice_articolo, a.descrizione, i.giacenza
    FROM articoli a
    JOIN inventario i ON a.id = i.articolo_id
    WHERE a.id = ?
");
$stmt->execute(array($articolo_id));
$articolo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$articolo) {
    die("Articolo con ID $articolo_id non trovato.");
}

// Opzionale, per coerenza
// include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scarico Articolo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h3><i class="bi bi-box-arrow-down"></i> Scarico da Magazzino</h3>
                </div>
                <div class="card-body">
                    <h4 class="card-title"><?php echo htmlspecialchars($articolo['descrizione']); ?></h4>
                    <p class="card-subtitle mb-2 text-muted">Codice: <?php echo htmlspecialchars($articolo['codice_articolo']); ?></p>

                    <div class="alert alert-info">
                        Giacenza attuale:
                        <span class="badge bg-primary fs-5 float-end"><?php echo $articolo['giacenza']; ?> pz</span>
                    </div>

                    <hr>

                    <form action="processa_movimento.php" method="POST" onsubmit="return confermaScarico()">
                        <input type="hidden" name="articolo_id" value="<?php echo $articolo['id']; ?>">
                        <input type="hidden" name="action" value="scarico">

                        <div class="mb-3">
                            <label for="quantita" class="form-label fs-5">
                                <strong>Quantità da prelevare:</strong>
                            </label>
                            <input type="number" name="quantita" id="quantita" class="form-control form-control-lg text-center"
                                   required min="1" max="<?php echo $articolo['giacenza']; ?>" autofocus
                                   <?php if ($articolo['giacenza'] <= 0) echo 'disabled'; ?>>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg fw-bold" <?php if ($articolo['giacenza'] <= 0) echo 'disabled'; ?>>
                                <i class="bi bi-check-lg"></i> Conferma Prelievo
                            </button>
                            <a href="scanner.php" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Annulla e Scansiona di Nuovo
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confermaScarico() {
    var qtaInput = document.getElementById('quantita');
    var qta = parseInt(qtaInput.value, 10);
    var max = parseInt(qtaInput.max, 10);

    if (qta > max) {
        alert('Errore: La quantità da scaricare (' + qta + ') supera la giacenza disponibile (' + max + ').');
        return false;
    }
    if (qta <= 0) {
        alert('Errore: Inserire una quantità valida.');
        return false;
    }
    return confirm('Stai per scaricare ' + qta + ' pz di questo articolo. Sei sicuro?');
}
</script>
</body>
</html>