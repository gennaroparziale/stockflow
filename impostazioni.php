<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// Gestione del salvataggio del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['prossimo_numero_ordine'])) {
        $nuovo_valore = (int)$_POST['prossimo_numero_ordine'];

        $stmt = $pdo->prepare("UPDATE impostazioni SET valore = ? WHERE chiave = 'prossimo_numero_ordine'");
        $stmt->execute(array($nuovo_valore));

        // Messaggio di successo (opzionale)
        $messaggio = "Impostazioni salvate con successo!";
    }
}

// Lettura del valore attuale dal database
$stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'prossimo_numero_ordine'");
$stmt->execute();
$prossimo_numero_ordine = $stmt->fetchColumn();


$currentPage = 'impostazioni.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Impostazioni - STOCKFLOW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Impostazioni</h1>

    <?php if (isset($messaggio)): ?>
        <div class="alert alert-success"><?php echo $messaggio; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Numerazione Documenti</h5>
            <form method="POST" action="impostazioni.php">
                <div class="mb-3">
                    <label for="prossimo_numero_ordine" class="form-label">Prossimo Numero Ordine Fornitore</label>
                    <input type="number" class="form-control" id="prossimo_numero_ordine" name="prossimo_numero_ordine" value="<?php echo htmlspecialchars($prossimo_numero_ordine); ?>" min="1">
                    <div class="form-text">Il prossimo ordine creato userà questo numero come riferimento.</div>
                </div>
                <button type="submit" class="btn btn-primary">Salva Impostazioni</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>