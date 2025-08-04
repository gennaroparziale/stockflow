<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// --- RECUPERO DATI PER I FILTRI ---
// Recupera tutti i fornitori per il menu a tendina
$fornitori_stmt = $pdo->query("SELECT id, nome_fornitore FROM fornitori ORDER BY nome_fornitore");
$fornitori = $fornitori_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LOGICA DI FILTRAGGIO ---
$fornitore_id_filtro = isset($_GET['fornitore_id']) ? (int)$_GET['fornitore_id'] : 0;

// --- QUERY PER RECUPERARE GLI ARTICOLI (FILTRATI) ---
$sql_articoli = "
    SELECT id, codice_articolo, descrizione
    FROM articoli
";
$params = [];

if ($fornitore_id_filtro > 0) {
    $sql_articoli .= " WHERE fornitore_id = ?";
    $params[] = $fornitore_id_filtro;
}

$sql_articoli .= " ORDER BY descrizione";
$articoli_stmt = $pdo->prepare($sql_articoli);
$articoli_stmt->execute($params);
$articoli = $articoli_stmt->fetchAll(PDO::FETCH_ASSOC);

// Includi la navbar
$currentPage = 'stampa_qrcode.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stampa Massiva QR Code Articoli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
    <style>
        /* Nasconde gli elementi non necessari durante la stampa */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                -webkit-print-color-adjust: exact; /* Per Chrome/Safari */
                print-color-adjust: exact; /* Standard */
            }
            .printable-area {
                margin: 0;
                padding: 0;
            }
        }

        /* Imposta il layout della pagina di stampa su A3 */
        @page {
            size: A3;
            margin: 1cm;
        }

        /* Griglia per contenere le etichette */
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            padding: 10px;
        }

        /* Stile per ogni singola etichetta */
        .qr-item {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            overflow-wrap: break-word;
            page-break-inside: avoid; /* Evita che un'etichetta venga spezzata tra due pagine */
        }
        .qr-item .qr-code-container {
            margin: 10px auto;
            display: flex;
            justify-content: center;
        }
        .qr-item .description {
            font-weight: bold;
            font-size: 0.9rem;
            margin-top: 5px;
            min-height: 40px; /* Assicura un'altezza minima per la descrizione */
        }
        .qr-item .code {
            font-family: monospace;
            font-size: 0.8rem;
            color: #555;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <div class="card p-3 mb-4 no-print">
        <h1 class="mb-3">Stampa Etichette QR Code</h1>
        <form method="GET" action="stampa_qrcode.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="fornitore_id" class="form-label">Filtra per Fornitore (Categoria)</label>
                <select name="fornitore_id" id="fornitore_id" class="form-select">
                    <option value="0">-- Tutti i Fornitori --</option>
                    <?php foreach ($fornitori as $fornitore): ?>
                        <option value="<?php echo $fornitore['id']; ?>" <?php if ($fornitore_id_filtro == $fornitore['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($fornitore['nome_fornitore']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Applica Filtro</button>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-success w-100" onclick="window.print();">
                    <i class="bi bi-printer"></i> Stampa Pagina
                </button>
            </div>
        </form>
        <?php if (!empty($articoli)): ?>
            <div class="alert alert-info mt-3">
                <i class="bi bi-info-circle-fill"></i> Trovati <strong><?php echo count($articoli); ?></strong> articoli. La pagina è formattata per la stampa su foglio A3.
            </div>
        <?php endif; ?>
    </div>

    <div class="printable-area">
        <?php if (empty($articoli)): ?>
            <div class="alert alert-warning text-center">Nessun articolo trovato con i filtri selezionati.</div>
        <?php else: ?>
            <div class="qr-grid">
                <?php foreach ($articoli as $articolo): ?>
                    <div class="qr-item">
                        <div class="description"><?php echo htmlspecialchars($articolo['descrizione']); ?></div>
                        <div class="code"><?php echo htmlspecialchars($articolo['codice_articolo']); ?></div>
                        <div class="qr-code-container"
                             data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/scarico_articolo.php?id=' . $articolo['id']; ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Seleziona tutti i contenitori per i QR code
        var qrCodeContainers = document.querySelectorAll('.qr-code-container');

        // Itera su ogni contenitore e genera il QR code
        qrCodeContainers.forEach(function(container) {
            var url = container.getAttribute('data-url');
            if (url) {
                new QRCode(container, {
                    text: url,
                    width: 128,
                    height: 128,
                    correctLevel : QRCode.CorrectLevel.H
                });
            }
        });
    });
</script>

</body>
</html>