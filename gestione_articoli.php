<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// Fetch di tutti i fornitori per il menu a tendina
$fornitori_stmt = $pdo->query("SELECT id, nome_fornitore FROM fornitori ORDER BY nome_fornitore");
$fornitori = $fornitori_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch di tutte le categorie per il menu a tendina
$categorie_stmt = $pdo->query("SELECT id, nome FROM categorie_articoli ORDER BY nome");
$categorie = $categorie_stmt->fetchAll(PDO::FETCH_ASSOC);


// --- LOGICA DI RICERCA E VISUALIZZAZIONE ---
$search_term = isset($_GET['q']) ? $_GET['q'] : '';
$articoli_sql = "
    SELECT 
        a.id, a.codice_articolo, a.descrizione, a.prezzo_acquisto, 
        a.scorta_minima, a.fornitore_id, a.categoria_id,
        f.nome_fornitore,
        c.nome as nome_categoria
    FROM 
        articoli a
    LEFT JOIN 
        fornitori f ON a.fornitore_id = f.id
    LEFT JOIN
        categorie_articoli c ON a.categoria_id = c.id
";
$params = array();

if (!empty($search_term)) {
    $articoli_sql .= " WHERE a.codice_articolo LIKE ? OR a.descrizione LIKE ? OR f.nome_fornitore LIKE ? OR c.nome LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = array($like_term, $like_term, $like_term, $like_term);
}

$articoli_sql .= " ORDER BY a.descrizione";
$articoli_stmt = $pdo->prepare($articoli_sql);
$articoli_stmt->execute($params);
$articoli = $articoli_stmt->fetchAll(PDO::FETCH_ASSOC);
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
<?php include 'navbarMagazzino.php';?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Anagrafica Articoli</h1>
        <button class="btn btn-primary" onclick="prepareAddModal()">
            <i class="bi bi-plus-circle"></i> Aggiungi Articolo
        </button>
    </div>

    <form action="gestione_articoli.php" method="GET" class="mb-4">
        <div class="input-group">
            <input type="search" name="q" class="form-control" placeholder="Cerca per codice, descrizione, fornitore, categoria..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Cerca</button>
        </div>
    </form>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
            <th>Codice</th>
            <th>Descrizione</th>
            <th>Categoria</th>
            <th>Fornitore</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($articoli)): ?>
            <tr><td colspan="5" class="text-center">Nessun articolo trovato.</td></tr>
        <?php else: ?>
            <?php foreach ($articoli as $articolo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($articolo['codice_articolo']); ?></td>
                    <td><?php echo htmlspecialchars($articolo['descrizione']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($articolo['nome_categoria']); ?></span></td>
                    <td><?php echo htmlspecialchars($articolo['nome_fornitore']); ?></td>
                    <td class="text-end">
                        <a href="dettaglio_articolo.php?id=<?php echo $articolo['id']; ?>" class="btn btn-sm btn-info" title="Dettaglio">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-warning" onclick='prepareEditModal(<?php echo json_encode($articolo); ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteArticolo(<?php echo $articolo['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="articoloModal" tabindex="-1">
    <div class="modal-dialog modal-lg"> <div class="modal-content">
            <form id="articoloForm" action="processa_articolo.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Aggiungi Nuovo Articolo</h5>
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
                                <option value="">-- Seleziona una categoria --</option>
                                <?php foreach ($categorie as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fornitore_id" class="form-label">Fornitore</label>
                            <select class="form-select" id="fornitore_id" name="fornitore_id">
                                <option value="">-- Seleziona un fornitore --</option>
                                <?php foreach ($fornitori as $fornitore): ?>
                                    <option value="<?php echo $fornitore['id']; ?>">
                                        <?php echo htmlspecialchars($fornitore['nome_fornitore']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prezzo_acquisto" class="form-label">Prezzo di Acquisto</label>
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
                    <div id="proprieta-dinamiche-container" class="row">
                        <p class="text-muted">Seleziona una categoria per visualizzare le proprietà specifiche.</p>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" action="processa_articolo.php" class="d-none">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="action" value="delete">
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var articoloModal = new bootstrap.Modal(document.getElementById('articoloModal'));
    var articoloForm = document.getElementById('articoloForm');
    var proprietaContainer = $('#proprieta-dinamiche-container');

    function prepareAddModal() {
        articoloForm.reset();
        document.getElementById('modalTitle').textContent = 'Aggiungi Nuovo Articolo';
        document.getElementById('formAction').value = 'add';
        document.getElementById('articoloId').value = '';
        proprietaContainer.html('<p class="text-muted">Seleziona una categoria per visualizzare le proprietà specifiche.</p>');
        articoloModal.show();
    }

    function prepareEditModal(articolo) {
        articoloForm.reset();
        document.getElementById('modalTitle').textContent = 'Modifica Articolo';
        document.getElementById('formAction').value = 'edit';

        // Popola i campi standard
        for (const key in articolo) {
            const el = document.getElementById(key);
            if (el) {
                el.value = articolo[key];
            }
        }
        document.getElementById('articoloId').value = articolo.id;

        // Carica le proprietà per la categoria pre-selezionata
        if (articolo.categoria_id) {
            caricaProprieta(articolo.categoria_id, articolo.id);
        } else {
            proprietaContainer.html('<p class="text-muted">Seleziona una categoria per visualizzare le proprietà specifiche.</p>');
        }

        articoloModal.show();
    }

    function deleteArticolo(id) {
        if (confirm('Sei sicuro di voler eliminare questo articolo? Tutti i movimenti e la giacenza associati verranno cancellati.')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    function caricaProprieta(categoriaId, articoloId = 0) {
        if (!categoriaId) {
            proprietaContainer.html('<p class="text-muted">Seleziona una categoria per visualizzare le proprietà specifiche.</p>');
            return;
        }

        proprietaContainer.html('<div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>');

        $.ajax({
            url: 'get_proprieta_articolo.php',
            type: 'GET',
            data: {
                categoria_id: categoriaId,
                articolo_id: articoloId
            },
            dataType: 'json',
            success: function(response) {
                proprietaContainer.empty();
                if (response.length === 0) {
                    proprietaContainer.html('<p class="text-muted">Nessuna proprietà specifica per questa categoria.</p>');
                    return;
                }

                response.forEach(function(prop) {
                    let inputType = 'text';
                    if (prop.tipo === 'numero') inputType = 'number';
                    if (prop.tipo === 'data') inputType = 'date';

                    var fieldHtml = `
                        <div class="col-md-6 mb-3">
                            <label for="prop_${prop.id}" class="form-label">${prop.nome}</label>
                            <input type="${inputType}" class="form-control" id="prop_${prop.id}" name="prop[${prop.id}]" value="${prop.valore}">
                        </div>`;
                    proprietaContainer.append(fieldHtml);
                });
            },
            error: function() {
                proprietaContainer.html('<p class="text-danger">Errore nel caricamento delle proprietà.</p>');
            }
        });
    }

    // Event listener per il cambio di categoria
    $('#categoria_id').on('change', function() {
        var categoriaId = $(this).val();
        var articoloId = $('#articoloId').val(); // 0 se è un nuovo articolo
        caricaProprieta(categoriaId, articoloId);
    });

</script>
</body>
</html>