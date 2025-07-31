<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// Fetch delle categorie per il menu a tendina dei filtri
$categorie_stmt = $pdo->query("SELECT id, nome FROM categorie_articoli ORDER BY nome");
$categorie = $categorie_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LOGICA DI RICERCA, FILTRI E PAGINAZIONE ---
$items_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Recupera i valori dei filtri dal GET
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria_id_filtro = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$filtri_proprieta = isset($_GET['filter_prop']) && is_array($_GET['filter_prop']) ? $_GET['filter_prop'] : array();

$filtri_proprieta_attivi = array_filter($filtri_proprieta, function($v) { return $v !== '' && $v !== null; });
$num_filtri_proprieta = count($filtri_proprieta_attivi);

// --- COSTRUZIONE QUERY ---
$params = array();
$where_clauses = array();

$sql_base = "
    FROM articoli a
    JOIN inventario i ON a.id = i.articolo_id
    LEFT JOIN fornitori f ON a.fornitore_id = f.id
    LEFT JOIN categorie_articoli c ON a.categoria_id = c.id
";

if ($num_filtri_proprieta > 0) {
    $sql_base .= " JOIN valori_proprieta vp ON a.id = vp.id_articolo ";
    $prop_conditions = [];
    foreach ($filtri_proprieta_attivi as $id_prop => $valore) {
        $prop_conditions[] = "(vp.id_proprieta = ? AND vp.valore LIKE ?)";
        $params[] = (int)$id_prop;
        $params[] = '%' . $valore . '%';
    }
    $where_clauses[] = "(" . implode(' OR ', $prop_conditions) . ")";
}

if (!empty($search_term)) {
    $where_clauses[] = "(a.codice_articolo LIKE ? OR a.descrizione LIKE ? OR f.nome_fornitore LIKE ? OR c.nome LIKE ?)";
    $like_term = '%' . $search_term . '%';
    array_push($params, $like_term, $like_term, $like_term, $like_term);
}

if ($categoria_id_filtro > 0) {
    $where_clauses[] = "a.categoria_id = ?";
    $params[] = $categoria_id_filtro;
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(' AND ', $where_clauses);
}

// --- QUERY PER CONTEGGIO TOTALE ---
$count_sql = "SELECT COUNT(DISTINCT a.id) " . $sql_base . $sql_where;
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();


// Calcolo pagine totali e offset
$total_pages = $total_items > 0 ? ceil($total_items / $items_per_page) : 1;
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;

// --- QUERY PER ESTRARRE I DATI DELLA PAGINA CORRENTE ---
$sql = "
    SELECT
        a.id, a.codice_articolo, a.descrizione,
        f.nome_fornitore,
        i.giacenza
    " . $sql_base . $sql_where . "
    GROUP BY a.id, f.nome_fornitore, i.giacenza
";

if ($num_filtri_proprieta > 0) {
    $sql .= " HAVING COUNT(DISTINCT vp.id_proprieta) = ?";
    array_push($params, $num_filtri_proprieta);
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

    <div class="card bg-light p-3 mb-4">
        <form id="filtri-form" action="inventario.php" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-12">
                    <label for="q" class="form-label">Ricerca Rapida</label>
                    <input type="search" id="q" name="q" class="form-control" placeholder="Cerca per codice, descrizione, fornitore..." value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-md-4">
                    <label for="filtro_categoria_id" class="form-label">Filtra per Categoria</label>
                    <select class="form-select" id="filtro_categoria_id" name="categoria_id">
                        <option value="">-- Tutte le categorie --</option>
                        <?php foreach ($categorie as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" <?php if ($categoria_id_filtro == $categoria['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Filtri Specifici</label>
                    <div class="row g-2" id="filtri-proprieta-container">
                        <p class="text-muted small m-0 pt-2">Seleziona una categoria per vedere i filtri.</p>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-end">
                <a href="inventario.php" class="btn btn-secondary">Resetta Filtri</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Applica Filtri</button>
            </div>
        </form>
    </div>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
            <th>Codice</th><th>Descrizione</th><th>Fornitore</th><th class="text-center">Giacenza</th><th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($inventario)): ?>
            <tr><td colspan="5" class="text-center">Nessun articolo trovato con i filtri applicati.</td></tr>
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
                <?php
                $queryParams = $_GET;
                if(isset($queryParams['page'])) unset($queryParams['page']);
                ?>
                <li class="page-item <?php if($current_page <= 1){ echo 'disabled'; } ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $current_page - 1])); ?>">Indietro</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $current_page){ echo 'active'; } ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if($current_page >= $total_pages){ echo 'disabled'; } ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $current_page + 1])); ?>">Avanti</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        const filtriContainer = $('#filtri-proprieta-container');
        const urlParams = new URLSearchParams(window.location.search);

        function caricaFiltriProprieta(categoriaId) {
            if (!categoriaId) {
                filtriContainer.html('<p class="text-muted small m-0 pt-2">Seleziona una categoria per vedere i filtri.</p>');
                return;
            }
            filtriContainer.html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">...</span></div>');

            $.ajax({
                url: 'get_proprieta_categoria.php', type: 'GET', data: { categoria_id: categoriaId }, dataType: 'json',
                success: function(proprieta) {
                    filtriContainer.empty();
                    if (proprieta.length === 0) {
                        filtriContainer.html('<p class="text-muted small m-0 pt-2">Nessun filtro specifico per questa categoria.</p>');
                        return;
                    }
                    proprieta.forEach(function(prop) {
                        const valoreEsistente = urlParams.get(`filter_prop[${prop.id}]`) || '';
                        let inputType = (prop.tipo_dato === 'numero') ? 'number' : (prop.tipo_dato === 'data') ? 'date' : 'text';
                        let fieldHtml = `<div class="col-md-4"><input type="${inputType}" class="form-control form-control-sm" name="filter_prop[${prop.id}]" placeholder="${prop.nome_proprieta}" value="${valoreEsistente}"></div>`;
                        filtriContainer.append(fieldHtml);
                    });
                },
                error: () => filtriContainer.html('<p class="text-danger small m-0 pt-2">Errore caricamento filtri.</p>')
            });
        }

        $('#filtro_categoria_id').on('change', function(event) {
            if (event.originalEvent) {
                $('#filtri-form').submit();
            } else {
                caricaFiltriProprieta($(this).val());
            }
        }).trigger('change');
    });
</script>
</body>
</html>