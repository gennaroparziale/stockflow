<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// --- Logica di gestione CARICO/SCARICO da modale ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Qui puoi inserire la logica per processare i movimenti dal modale, se necessario
    // Per ora, lo lasciamo reindirizzare a processa_movimento.php come fa già
}

// --- CONFIGURAZIONE RICERCA E PAGINAZIONE ---
$items_per_page = 15;
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// --- QUERY PER CONTEGGIO TOTALE ARTICOLI (FILTRATI) ---
$count_sql_base = "
    SELECT COUNT(a.id)
    FROM articoli a
    LEFT JOIN fornitori f ON a.fornitore_id = f.id
";
$params = array();
if (!empty($search_term)) {
    $count_sql_base .= " WHERE a.codice_articolo LIKE ? OR a.descrizione LIKE ? OR f.nome_fornitore LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = array($like_term, $like_term, $like_term);
}
$count_stmt = $pdo->prepare($count_sql_base);
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
$sql = "
    SELECT
        a.id, a.codice_articolo, a.descrizione,
        f.nome_fornitore,
        i.giacenza
    FROM articoli a
    JOIN inventario i ON a.id = i.articolo_id
    LEFT JOIN fornitori f ON a.fornitore_id = f.id
";
if (!empty($search_term)) {
    $sql .= " WHERE a.codice_articolo LIKE ? OR a.descrizione LIKE ? OR f.nome_fornitore LIKE ?";
}
$sql .= " ORDER BY a.descrizione ASC LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'inventario.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Magazzino</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <h1>Inventario</h1>

    <div class="row mb-4">
        <div class="col-md-8">
            <form action="inventario.php" method="GET">
                <div class="input-group">
                    <input type="search" name="q" class="form-control" placeholder="Cerca per codice, descrizione, fornitore..." value="<?php echo htmlspecialchars($search_term); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Cerca</button>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
            <th>Codice</th>
            <th>Descrizione</th>
            <th>Fornitore</th>
            <th class="text-center">Giacenza</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($inventario)): ?>
            <tr><td colspan="5" class="text-center">Nessun articolo trovato.</td></tr>
        <?php else: ?>
            <?php foreach ($inventario as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['codice_articolo']); ?></td>
                    <td><?php echo htmlspecialchars($item['descrizione']); ?></td>
                    <td><?php echo htmlspecialchars($item['nome_fornitore']); ?></td>
                    <td class="text-center">
                        <span class="badge fs-6 <?php echo $item['giacenza'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $item['giacenza']; ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="dettaglio_articolo.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info" title="Vedi Dettaglio"><i class="bi bi-eye"></i></a>
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
                    <a class="page-link" href="inventario.php?q=<?php echo urlencode($search_term); ?>&page=<?php echo $current_page - 1; ?>">Indietro</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $current_page){ echo 'active'; } ?>">
                        <a class="page-link" href="inventario.php?q=<?php echo urlencode($search_term); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if($current_page >= $total_pages){ echo 'disabled'; } ?>">
                    <a class="page-link" href="inventario.php?q=<?php echo urlencode($search_term); ?>&page=<?php echo $current_page + 1; ?>">Avanti</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>