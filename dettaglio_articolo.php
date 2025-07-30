<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';
// 1. Recupero e validazione dell'ID articolo dall'URL
$articolo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($articolo_id <= 0) {
    die("ID articolo non valido.");
}

// 2. Query per recuperare i dati principali dell'articolo (Anagrafica, Giacenza, Fornitore)
$sql_articolo = "
    SELECT
        a.id, a.codice_articolo, a.descrizione, a.prezzo_acquisto, a.scorta_minima,
        i.giacenza,
        f.nome_fornitore
    FROM articoli a
    JOIN inventario i ON a.id = i.articolo_id
    LEFT JOIN fornitori f ON a.fornitore_id = f.id
    WHERE a.id = ?
";
$stmt_articolo = $pdo->prepare($sql_articolo);
$stmt_articolo->execute(array($articolo_id));
$articolo = $stmt_articolo->fetch(PDO::FETCH_ASSOC);

// Se l'articolo non esiste, termina lo script
if (!$articolo) {
    die("Articolo non trovato.");
}

// 3. Logica di paginazione per i movimenti di QUESTO articolo
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Conteggio totale dei movimenti per questo articolo
$count_sql = "SELECT COUNT(*) FROM movimenti WHERE articolo_id = ?";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(array($articolo_id));
$total_items = $count_stmt->fetchColumn();

// Calcolo pagine totali e offset
$total_pages = $total_items > 0 ? ceil($total_items / $items_per_page) : 0;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;

// 4. Query per recuperare lo storico movimenti paginato di QUESTO articolo
$sql_movimenti = "
    SELECT data_movimento, tipo_movimento, quantita
    FROM movimenti
    WHERE articolo_id = ?
    ORDER BY data_movimento DESC
    LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;
$stmt_movimenti = $pdo->prepare($sql_movimenti);
$stmt_movimenti->execute(array($articolo_id));
$movimenti = $stmt_movimenti->fetchAll(PDO::FETCH_ASSOC);

include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio: <?php echo htmlspecialchars($articolo['descrizione']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
</head>
<body>

<div class="container mt-4">
    <h1 class="mb-0"><?php echo htmlspecialchars($articolo['descrizione']); ?></h1>
    <p class="text-muted">Codice: <?php echo htmlspecialchars($articolo['codice_articolo']); ?></p>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Anagrafica e Giacenza</h4>
            <button class="btn btn-outline-secondary" onclick="stampaEtichetta()">
                <i class="bi bi-printer"></i> Stampa Etichetta
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <dl class="row">
                        <dt class="col-sm-3">Fornitore</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($articolo['nome_fornitore']) ?: 'Non specificato'; ?></dd>

                        <dt class="col-sm-3">Prezzo Acquisto</dt>
                        <dd class="col-sm-9">&euro; <?php echo number_format($articolo['prezzo_acquisto'], 2, ',', '.'); ?></dd>

                        <dt class="col-sm-3">Giacenza Attuale</dt>
                        <dd class="col-sm-9"><span class="badge bg-primary fs-6"><?php echo $articolo['giacenza']; ?> pz</span></dd>

                        <dt class="col-sm-3">Scorta Minima</dt>
                        <dd class="col-sm-9"><span class="badge bg-secondary fs-6"><?php echo $articolo['scorta_minima']; ?> pz</span></dd>
                    </dl>
                </div>
                <div class="col-md-4 text-center" id="etichetta-stampabile">
                    <h5>QR Code</h5>
                    <div id="qrcode" class="d-flex justify-content-center mb-2"></div>
                    <p class="mt-2 mb-0 fw-bold"><?php echo htmlspecialchars($articolo['descrizione']); ?></p>
                    <p>Cod: <?php echo htmlspecialchars($articolo['codice_articolo']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <h3>Storico Movimenti</h3>
    <table class="table table-sm table-striped">
        <thead class="table-light">
            <tr>
                <th>Data e Ora</th>
                <th class="text-center">Tipo</th>
                <th class="text-end">Quantità</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($movimenti)): ?>
                <tr><td colspan="3" class="text-center">Nessun movimento per questo articolo.</td></tr>
            <?php else: ?>
                <?php foreach ($movimenti as $movimento): ?>
                <tr>
                    <td><?php echo date("d/m/Y H:i:s", strtotime($movimento['data_movimento'])); ?></td>
                    <td class="text-center">
                        <?php if ($movimento['tipo_movimento'] == 'carico'): ?>
                            <span class="badge bg-success">CARICO</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">SCARICO</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold">
                        <?php echo ($movimento['tipo_movimento'] == 'carico' ? '+' : '-') . $movimento['quantita']; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item <?php if($current_page <= 1){ echo 'disabled'; } ?>">
                <a class="page-link" href="dettaglio_articolo.php?id=<?php echo $articolo_id; ?>&page=<?php echo $current_page - 1; ?>">Indietro</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php if($i == $current_page){ echo 'active'; } ?>">
                <a class="page-link" href="dettaglio_articolo.php?id=<?php echo $articolo_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?php if($current_page >= $total_pages){ echo 'disabled'; } ?>">
                <a class="page-link" href="dettaglio_articolo.php?id=<?php echo $articolo_id; ?>&page=<?php echo $current_page + 1; ?>">Avanti</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Genera il QR Code quando la pagina è caricata
    document.addEventListener("DOMContentLoaded", function() {
        var urlPaginaScarico = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/scarico_articolo.php?id=<?php echo $articolo_id; ?>';
        new QRCode(document.getElementById("qrcode"), {
            text: urlPaginaScarico,
            width: 150,
            height: 150
        });
    });

    // Funzione per la stampa dell'etichetta
    function stampaEtichetta() {
        var contenuto = document.getElementById('etichetta-stampabile').innerHTML;
        var finestraStampa = window.open('', '', 'height=400,width=400');
        finestraStampa.document.write('<html><head><title>Stampa Etichetta</title>');
        finestraStampa.document.write('<style>body { width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; text-align: center; font-family: sans-serif; }</style>');
        finestraStampa.document.write('</head><body><div style="border: 2px dashed #ccc; padding: 20px;">');
        finestraStampa.document.write(contenuto);
        finestraStampa.document.write('</div></body></html>');
        finestraStampa.document.close();
        finestraStampa.onload = function() {
            finestraStampa.focus();
            finestraStampa.print();
            finestraStampa.close();
        };
    }
</script>
</body>
</html>