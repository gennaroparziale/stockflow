<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// --- CONFIGURAZIONE RICERCA, FILTRI E PAGINAZIONE ---
$items_per_page = 20;
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$data_inizio = isset($_GET['data_inizio']) && !empty($_GET['data_inizio']) ? $_GET['data_inizio'] : '';
$data_fine = isset($_GET['data_fine']) && !empty($_GET['data_fine']) ? $_GET['data_fine'] : '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// --- COSTRUZIONE QUERY E PARAMETRI ---
$sql_base = " FROM movimenti m JOIN articoli a ON m.articolo_id = a.id ";
$where_clauses = array();
$params = array();

// Aggiungi filtro per termine di ricerca
if (!empty($search_term)) {
    $where_clauses[] = "(a.codice_articolo LIKE ? OR a.descrizione LIKE ? OR m.tipo_movimento LIKE ?)";
    $like_term = '%' . $search_term . '%';
    $params[] = $like_term;
    $params[] = $like_term;
    $params[] = $like_term;
}

// Aggiungi filtro per data inizio
if (!empty($data_inizio)) {
    $where_clauses[] = "m.data_movimento >= ?";
    $params[] = $data_inizio;
}

// Aggiungi filtro per data fine
if (!empty($data_fine)) {
    $where_clauses[] = "m.data_movimento <= ?";
    // Aggiungo l'orario per includere tutto il giorno selezionato
    $params[] = $data_fine . ' 23:59:59';
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(' AND ', $where_clauses);
}

// --- QUERY PER CONTEGGIO TOTALE MOVIMENTI (FILTRATI) ---
$count_sql = "SELECT COUNT(m.id)" . $sql_base . $sql_where;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

// Calcolo pagine totali e offset
$total_pages = 0;
if ($total_items > 0) {
    $total_pages = ceil($total_items / $items_per_page);
}
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}
$offset = ($current_page - 1) * $items_per_page;

// --- QUERY PER ESTRARRE I DATI DELLA PAGINA CORRENTE (FILTRATI) ---
$sql = "SELECT m.tipo_movimento, m.quantita, m.data_movimento, a.codice_articolo, a.descrizione"
    . $sql_base . $sql_where
    . " ORDER BY m.data_movimento DESC LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Includi la navbar
$currentPage = 'storico_movimenti.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storico Movimenti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>Storico Movimenti</h1>

    <form action="storico_movimenti.php" method="GET" class="mb-4 p-3 border rounded bg-light">
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="q" class="form-label">Cerca</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Codice, descrizione, tipo (carico/scarico)..." value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div class="col-md-2">
                <label for="data_inizio" class="form-label">Da</label>
                <input type="date" id="data_inizio" name="data_inizio" class="form-control" value="<?php echo htmlspecialchars($data_inizio); ?>">
            </div>
            <div class="col-md-2">
                <label for="data_fine" class="form-label">A</label>
                <input type="date" id="data_fine" name="data_fine" class="form-control" value="<?php echo htmlspecialchars($data_fine); ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Filtra</button>
            </div>
        </div>
    </form>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
            <th>Data e Ora</th>
            <th>Codice Articolo</th>
            <th>Descrizione</th>
            <th class="text-center">Tipo</th>
            <th class="text-end">Quantità</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($movimenti)): ?>
            <tr><td colspan="5" class="text-center">Nessun movimento trovato con i filtri attuali.</td></tr>
        <?php else: ?>
            <?php foreach ($movimenti as $movimento): ?>
                <tr>
                    <td><?php echo date("d/m/Y H:i:s", strtotime($movimento['data_movimento'])); ?></td>
                    <td><?php echo htmlspecialchars($movimento['codice_articolo']); ?></td>
                    <td><?php echo htmlspecialchars($movimento['descrizione']); ?></td>
                    <td class="text-center">
                        <?php if ($movimento['tipo_movimento'] == 'carico'): ?>
                            <span class="badge bg-success">CARICO</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">SCARICO</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php
                        $prefix = ($movimento['tipo_movimento'] == 'carico') ? '+' : '-';
                        echo '<strong>' . $prefix . htmlspecialchars($movimento['quantita']) . '</strong>';
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
                <?php
                // Costruisci la parte dell'URL con i parametri dei filtri
                $query_string = http_build_query(array(
                    'q' => $search_term,
                    'data_inizio' => $data_inizio,
                    'data_fine' => $data_fine
                ));
                ?>
                <li class="page-item <?php if($current_page <= 1){ echo 'disabled'; } ?>">
                    <a class="page-link" href="storico_movimenti.php?<?php echo $query_string; ?>&page=<?php echo $current_page - 1; ?>">Indietro</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $current_page){ echo 'active'; } ?>">
                        <a class="page-link" href="storico_movimenti.php?<?php echo $query_string; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if($current_page >= $total_pages){ echo 'disabled'; } ?>">
                    <a class="page-link" href="storico_movimenti.php?<?php echo $query_string; ?>&page=<?php echo $current_page + 1; ?>">Avanti</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>