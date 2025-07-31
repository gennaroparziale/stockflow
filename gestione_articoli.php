<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// Fetch di tutti i fornitori e categorie per i menu a tendina
$fornitori_stmt = $pdo->query("SELECT id, nome_fornitore FROM fornitori ORDER BY nome_fornitore");
$fornitori = $fornitori_stmt->fetchAll(PDO::FETCH_ASSOC);
$categorie_stmt = $pdo->query("SELECT id, nome FROM categorie_articoli ORDER BY nome");
$categorie = $categorie_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LOGICA DI RICERCA E FILTRI ---
$params = array();
$where_clauses = array();

// Recupera i valori dei filtri dal GET
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria_id_filtro = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$filtri_proprieta = isset($_GET['filter_prop']) && is_array($_GET['filter_prop']) ? $_GET['filter_prop'] : array();

$filtri_proprieta_attivi = array_filter($filtri_proprieta, function($v) { return $v !== '' && $v !== null; });
$num_filtri_proprieta = count($filtri_proprieta_attivi);

// Costruzione della query base
$articoli_sql = "
    SELECT 
        a.id, a.codice_articolo, a.descrizione, a.prezzo_acquisto, 
        a.scorta_minima, a.fornitore_id, a.categoria_id,
        f.nome_fornitore,
        c.nome as nome_categoria
    FROM 
        articoli a
    LEFT JOIN fornitori f ON a.fornitore_id = f.id
    LEFT JOIN categorie_articoli c ON a.categoria_id = c.id
";

if ($num_filtri_proprieta > 0) {
    $articoli_sql .= " JOIN valori_proprieta vp ON a.id = vp.id_articolo ";
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

if (!empty($where_clauses)) {
    $articoli_sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$articoli_sql .= " GROUP BY a.id, c.nome, f.nome_fornitore ";

if ($num_filtri_proprieta > 0) {
    $articoli_sql .= " HAVING COUNT(DISTINCT vp.id_proprieta) = ?";
    $params[] = $num_filtri_proprieta;
}

$articoli_sql .= " ORDER BY a.descrizione";

$articoli_stmt = $pdo->prepare($articoli_sql);
$articoli_stmt->execute($params);
$articoli = $articoli_stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'gestione_articoli.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Articoli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Anagrafica Articoli</h1>
        <button id="add-articolo-btn" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Aggiungi Articolo</button>
    </div>

    <div class="card bg-light p-3 mb-4">
        <form action="gestione_articoli.php" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-12">
                    <label for="q" class="form-label">Ricerca Rapida</label>
                    <input type="search" id="q" name="q" class="form-control" placeholder="Cerca per codice, descrizione..." value="<?php echo htmlspecialchars($search_term); ?>">
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
                <a href="gestione_articoli.php" class="btn btn-secondary">Resetta Filtri</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Applica Filtri</button>
            </div>
        </form>
    </div>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr><th>Codice</th><th>Descrizione</th><th>Categoria</th><th>Fornitore</th><th class="text-end">Azioni</th></tr>
        </thead>
        <tbody>
        <?php if (empty($articoli)): ?>
            <tr><td colspan="5" class="text-center">Nessun articolo trovato con i filtri applicati.</td></tr>
        <?php else: ?>
            <?php foreach ($articoli as $articolo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($articolo['codice_articolo']); ?></td>
                    <td><?php echo htmlspecialchars($articolo['descrizione']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($articolo['nome_categoria']); ?></span></td>
                    <td><?php echo htmlspecialchars($articolo['nome_fornitore']); ?></td>
                    <td class="text-end">
                        <a href="dettaglio_articolo.php?id=<?php echo $articolo['id']; ?>" class="btn btn-sm btn-info" title="Dettaglio"><i class="bi bi-eye"></i></a>
                        <button class="btn btn-sm btn-warning edit-btn" data-articolo='<?php echo htmlspecialchars(json_encode($articolo), ENT_QUOTES, 'UTF-8'); ?>' title="Modifica"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $articolo['id']; ?>" title="Elimina"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="articoloModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="articoloForm" action="processa_articolo.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="articoloId">
                    <input type="hidden" name="action" id="formAction">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="codice_articolo" class="form-label">Codice Articolo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="codice_articolo" name="codice_articolo" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="descrizione" class="form-label">Descrizione <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="descrizione" name="descrizione" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($categorie as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fornitore_id" class="form-label">Fornitore</label>
                            <select class="form-select" id="fornitore_id" name="fornitore_id">
                                <option value="">-- Seleziona --</option>
                                <?php foreach ($fornitori as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['nome_fornitore']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prezzo_acquisto" class="form-label">Prezzo Acquisto</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" step="0.01" class="form-control" id="prezzo_acquisto" name="prezzo_acquisto">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="scorta_minima" class="form-label">Scorta Minima</label>
                            <input type="number" class="form-control" id="scorta_minima" name="scorta_minima" min="0">
                        </div>
                    </div>
                    <hr>
                    <h5 class="mt-3">Proprietà Specifiche</h5>
                    <div id="proprieta-dinamiche-container" class="row"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>
<form id="deleteForm" method="POST" action="processa_articolo.php">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="action" value="delete">
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        // --- INIZIALIZZAZIONE ELEMENTI ---
        const articoloModal = new bootstrap.Modal(document.getElementById('articoloModal'));
        const articoloForm = $('#articoloForm');
        const urlParams = new URLSearchParams(window.location.search);

        // --- FUNZIONI AJAX ---
        function caricaProprieta(url, container, categoriaId, articoloId = 0) {
            if (!categoriaId) {
                container.html('<p class="text-muted small m-0 pt-2">Seleziona una categoria.</p>');
                return;
            }
            container.html('<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">...</span></div>');
            $.ajax({
                url: url, type: 'GET', data: { categoria_id: categoriaId, articolo_id: articoloId }, dataType: 'json',
                success: function(response) {
                    container.empty();
                    if (response.length === 0) {
                        container.html('<p class="text-muted small m-0 pt-2">Nessuna proprietà specifica.</p>');
                        return;
                    }
                    response.forEach(function(prop) {
                        let inputType = (prop.tipo_dato === 'numero') ? 'number' : (prop.tipo_dato === 'data') ? 'date' : 'text';
                        let valore, fieldHtml;

                        if (url.includes('get_proprieta_categoria')) { // Siamo nei filtri
                            valore = urlParams.get(`filter_prop[${prop.id}]`) || '';
                            fieldHtml = `<div class="col-md-4"><input type="${inputType}" class="form-control form-control-sm" name="filter_prop[${prop.id}]" placeholder="${prop.nome_proprieta}" value="${valore}"></div>`;
                        } else { // Siamo nel modale
                            valore = prop.valore || '';
                            fieldHtml = `<div class="col-md-6 mb-3"><label class="form-label">${prop.nome}</label><input type="${inputType}" class="form-control" name="prop[${prop.id}]" value="${valore}"></div>`;
                        }
                        container.append(fieldHtml);
                    });
                },
                error: () => container.html('<p class="text-danger small m-0 pt-2">Errore caricamento.</p>')
            });
        }

        // --- GESTIONE FILTRI ---
        const filtriContainer = $('#filtri-proprieta-container');
        $('#filtro_categoria_id').on('change', function() {
            caricaProprieta('get_proprieta_categoria.php', filtriContainer, $(this).val());
        }).trigger('change');

        // --- GESTIONE MODALE ---
        const proprietaContainerModale = $('#proprieta-dinamiche-container');
        $('#add-articolo-btn').on('click', function() {
            articoloForm[0].reset();
            $('#modalTitle').text('Aggiungi Nuovo Articolo');
            $('#formAction').val('add');
            proprietaContainerModale.html('<p class="text-muted">Seleziona una categoria.</p>');
            articoloModal.show();
        });

        $('.table').on('click', '.edit-btn', function() {
            const articolo = $(this).data('articolo');
            articoloForm[0].reset();
            $('#modalTitle').text('Modifica Articolo');
            $('#formAction').val('edit');
            articoloForm.find('#articoloId').val(articolo.id);
            articoloForm.find('#codice_articolo').val(articolo.codice_articolo);
            articoloForm.find('#descrizione').val(articolo.descrizione);
            articoloForm.find('#categoria_id').val(articolo.categoria_id);
            articoloForm.find('#fornitore_id').val(articolo.fornitore_id);
            articoloForm.find('#prezzo_acquisto').val(articolo.prezzo_acquisto);
            articoloForm.find('#scorta_minima').val(articolo.scorta_minima);
            caricaProprieta('get_proprieta_articolo.php', proprietaContainerModale, articolo.categoria_id, articolo.id);
            articoloModal.show();
        });

        $('.table').on('click', '.delete-btn', function() {
            if (confirm('Sei sicuro di voler eliminare questo articolo?')) {
                $('#deleteId').val($(this).data('id'));
                $('#deleteForm').submit();
            }
        });

        $('#articoloForm #categoria_id').on('change', function() {
            let articoloId = $('#articoloId').val() || 0;
            caricaProprieta('get_proprieta_articolo.php', proprietaContainerModale, $(this).val(), articoloId);
        });
    });
</script>
</body>
</html>