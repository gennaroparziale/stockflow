<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// --- 1. CONTROLLO DISPOSITIVO MOBILE ---

function isMobileDevice() {
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    $mobileKeywords = 'mobi|android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos';
    return preg_match("/($mobileKeywords)/i", $_SERVER['HTTP_USER_AGENT']);
}

// Se il dispositivo è mobile, reindirizza e interrompi tutto.
if (isMobileDevice()) {
    header('Location: dashboard_mobile.php');
    exit();
}

// Query 1: Calcolo del valore totale del magazzino
$stmt_valore = $pdo->query("
    SELECT SUM(i.giacenza * a.prezzo_acquisto) as valore_totale
    FROM inventario i
    JOIN articoli a ON i.articolo_id = a.id
");
$valore_magazzino = $stmt_valore->fetchColumn();
// Se non ci sono articoli, il valore è 0
if ($valore_magazzino === null) {
    $valore_magazzino = 0;
}


// Query 2: Conteggio articoli sotto scorta
$stmt_sottoscorta = $pdo->query("
    SELECT COUNT(*)
    FROM inventario i
    JOIN articoli a ON i.articolo_id = a.id
    WHERE i.giacenza <= a.scorta_minima AND a.scorta_minima > 0
");
$articoli_sottoscorta = $stmt_sottoscorta->fetchColumn();


// Query 3: Conteggio fornitori
$fornitori_totali = $pdo->query("SELECT COUNT(*) FROM fornitori")->fetchColumn();


// Query 4: Ultimi 5 movimenti
$stmt_ultimi_movimenti = $pdo->query("
    SELECT m.data_movimento, m.tipo_movimento, m.quantita, a.descrizione
    FROM movimenti m
    JOIN articoli a ON m.articolo_id = a.id
    ORDER BY m.data_movimento DESC
    LIMIT 5
");
$ultimi_movimenti = $stmt_ultimi_movimenti->fetchAll(PDO::FETCH_ASSOC);

// --- NUOVO: PREPARAZIONE DATI PER IL GRAFICO ---
$stmt_chart = $pdo->query("
    SELECT
        a.descrizione,
        (i.giacenza * a.prezzo_acquisto) AS valore_articolo
    FROM articoli a
    JOIN inventario i ON a.id = i.articolo_id
    WHERE (i.giacenza * a.prezzo_acquisto) > 0
    ORDER BY valore_articolo DESC
    LIMIT 5
");
$chart_data_raw = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = array();
$chart_values = array();
foreach ($chart_data_raw as $data) {
    $chart_labels[] = $data['descrizione'];
    $chart_values[] = $data['valore_articolo'];
}

// Convertiamo gli array PHP in stringhe JSON per passarli a JavaScript
$chart_labels_json = json_encode($chart_labels);
$chart_values_json = json_encode($chart_values);

// Includiamo la navbar
$currentPage = 'index.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Magazzino Portale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container mt-4">
    <h1>Dashboard</h1>
    <p class="text-muted">Panoramica generale del tuo magazzino.</p>

    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Valore Magazzino</h5>
                            <h2>&euro; <?php echo number_format($valore_magazzino, 2, ',', '.'); ?></h2>
                        </div>
                        <i class="bi bi-currency-euro fs-1"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Articoli Sotto Scorta</h5>
                            <h2><?php echo $articoli_sottoscorta; ?></h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
                <a href="report_sottoscorta.php" class="card-footer text-white text-decoration-none">
                    Visualizza report <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title">Fornitori Attivi</h5>
                            <h2><?php echo $fornitori_totali; ?></h2>
                        </div>
                        <i class="bi bi-truck fs-1"></i>
                    </div>
                </div>
                <a href="gestione_fornitori.php" class="card-footer text-white text-decoration-none">
                    Gestisci fornitori <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h3>Ultimi Movimenti</h3>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Data e Ora</th>
                            <th>Articolo</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-end">Quantità</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ultimi_movimenti)): ?>
                            <tr><td colspan="4" class="text-center">Nessun movimento registrato.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ultimi_movimenti as $movimento): ?>
                            <tr>
                                <td><?php echo date("d/m/Y H:i:s", strtotime($movimento['data_movimento'])); ?></td>
                                <td><?php echo htmlspecialchars($movimento['descrizione']); ?></td>
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
            </div>
            <div class="text-end">
                <a href="storico_movimenti.php">Vedi storico completo &rarr;</a>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-12">
            <h3>Top 5 Articoli per Valore</h3>
            <div class="card p-2">
                <canvas id="topArticoliChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Recuperiamo i dati preparati da PHP
    const labels = <?php echo $chart_labels_json; ?>;
    const dataValues = <?php echo $chart_values_json; ?>;

    const data = {
        labels: labels,
        datasets: [{
            label: 'Valore in €',
            data: dataValues,
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    };

    // Configurazione del grafico
    const config = {
        type: 'bar',
        data: data,
        options: {
            indexAxis: 'y', // Rende il grafico a barre orizzontali, più leggibile per le descrizioni lunghe
            scales: {
                x: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false // Nasconde la legenda, tanto è ovvia
                }
            }
        }
    };

    // Creazione del grafico
    new Chart(
        document.getElementById('topArticoliChart'),
        config
    );
</script>
</body>
</html>