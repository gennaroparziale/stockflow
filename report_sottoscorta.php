<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';
// --- CONFIGURAZIONE PAGINAZIONE ---
$items_per_page = 20;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Condizione WHERE per gli articoli da riordinare
$where_condition = " WHERE i.giacenza < a.scorta_minima AND a.scorta_minima > 0 ";

// --- QUERY PER CONTEGGIO TOTALE ARTICOLI ---
$count_sql = "SELECT COUNT(a.id) FROM articoli a JOIN inventario i ON a.id = i.articolo_id" . $where_condition;
$total_items = $pdo->query($count_sql)->fetchColumn();

// Calcolo pagine totali e offset
$total_pages = 0;
if ($total_items > 0) {
    $total_pages = ceil($total_items / $items_per_page);
}
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}
$offset = ($current_page - 1) * $items_per_page;

// --- QUERY PER ESTRARRE I DATI DELLA PAGINA CORRENTE ---
$sql = "
    SELECT 
        a.codice_articolo, a.descrizione, a.scorta_minima,
        i.giacenza,
        f.nome_fornitore
    FROM articoli a
    JOIN inventario i ON a.id = i.articolo_id
    LEFT JOIN fornitori f ON a.fornitore_id = f.id
    " . $where_condition . "
    ORDER BY a.descrizione
    LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->query($sql);
$articoli = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Includiamo la navbar
$currentPage = 'report_sottoscorta.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Articoli Sotto Scorta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1>Report Articoli Sotto Scorta</h1>
            <p class="text-muted">Elenco dei prodotti da riordinare.</p>
        </div>
    </div>

    <table class="table table-hover table-sm">
        <thead class="table-dark">
        <tr>
            <th>Codice Articolo</th>
            <th>Descrizione</th>
            <th>Fornitore</th>
            <th class="text-center">Giacenza</th>
            <th class="text-center">Scorta Minima</th>
            <th class="text-center bg-warning text-dark">Da Ordinare</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($articoli)): ?>
            <tr><td colspan="6" class="text-center">Nessun articolo sotto scorta. Ottimo lavoro!</td></tr>
        <?php else: ?>
            <?php foreach ($articoli as $articolo): ?>
                <tr class="table-danger">
                    <td><?php echo htmlspecialchars($articolo['codice_articolo']); ?></td>
                    <td><?php echo htmlspecialchars($articolo['descrizione']); ?></td>
                    <td><?php echo htmlspecialchars($articolo['nome_fornitore']); ?></td>
                    <td class="text-center fw-bold"><?php echo $articolo['giacenza']; ?></td>
                    <td class="text-center"><?php echo $articolo['scorta_minima']; ?></td>
                    <td class="text-center bg-light fw-bold">
                        <?php
                        // Calcola quanti pezzi mancano per raggiungere la scorta minima
                        $da_ordinare = $articolo['scorta_minima'] - $articolo['giacenza'];
                        echo $da_ordinare > 0 ? $da_ordinare : 0;
                        ?>
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
                    <a class="page-link" href="report_sottoscorta.php?page=<?php echo $current_page - 1; ?>">Indietro</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $current_page){ echo 'active'; } ?>">
                        <a class="page-link" href="report_sottoscorta.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if($current_page >= $total_pages){ echo 'disabled'; } ?>">
                    <a class="page-link" href="report_sottoscorta.php?page=<?php echo $current_page + 1; ?>">Avanti</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>