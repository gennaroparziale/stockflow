<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// Fetch di tutte le categorie per il menu a tendina
$categorie_stmt = $pdo->query("SELECT id, nome FROM categorie_articoli ORDER BY nome");
$categorie = $categorie_stmt->fetchAll(PDO::FETCH_ASSOC);

// Determina la categoria selezionata
$selected_cat_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$proprieta_list = array();

if ($selected_cat_id > 0) {
    $prop_stmt = $pdo->prepare("SELECT * FROM proprieta WHERE id_categoria = ? ORDER BY nome_proprieta");
    $prop_stmt->execute(array($selected_cat_id));
    $proprieta_list = $prop_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$currentPage = 'gestione_categorie.php'; // Rimane nel menu anagrafiche
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Proprietà Categorie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gestione Proprietà per Categoria</h1>
        <?php if ($selected_cat_id > 0): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proprietaModal" onclick="prepareAddModal(<?php echo $selected_cat_id; ?>)">
                <i class="bi bi-plus-circle"></i> Aggiungi Proprietà
            </button>
        <?php endif; ?>
    </div>

    <div class="card p-3 mb-4">
        <form method="GET" action="gestione_proprieta.php">
            <label for="categoria_id" class="form-label">Seleziona una categoria per gestire le sue proprietà:</label>
            <div class="input-group">
                <select name="categoria_id" id="categoria_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleziona --</option>
                    <?php foreach ($categorie as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $selected_cat_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-secondary" type="submit">Vai</button>
            </div>
        </form>
    </div>

    <?php if ($selected_cat_id > 0): ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
            <tr>
                <th>Nome Proprietà</th>
                <th>Tipo Dato</th>
                <th class="text-end">Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($proprieta_list)): ?>
                <tr><td colspan="3" class="text-center">Nessuna proprietà definita per questa categoria.</td></tr>
            <?php else: ?>
                <?php foreach ($proprieta_list as $prop): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prop['nome_proprieta']); ?></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($prop['tipo_dato']); ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-danger" onclick="deleteProprieta(<?php echo $prop['id']; ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<div class="modal fade" id="proprietaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="proprietaForm" method="POST" action="processa_proprieta.php">
                <div class="modal-header">
                    <h5 class="modal-title">Aggiungi Proprietà</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_categoria" id="id_categoria_form">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="nome_proprieta" class="form-label">Nome Proprietà</label>
                        <input type="text" class="form-control" name="nome_proprieta" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipo_dato" class="form-label">Tipo di Dato</label>
                        <select name="tipo_dato" class="form-select">
                            <option value="testo">Testo</option>
                            <option value="numero">Numero</option>
                            <option value="data">Data</option>
                        </select>
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

<form id="deleteForm" method="POST" action="processa_proprieta.php">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="id_categoria" value="<?php echo $selected_cat_id; ?>">
    <input type="hidden" name="action" value="delete">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var proprietaModal = new bootstrap.Modal(document.getElementById('proprietaModal'));

    function prepareAddModal(catId) {
        document.getElementById('id_categoria_form').value = catId;
        proprietaModal.show();
    }

    function deleteProprieta(propId) {
        if (confirm('Sei sicuro di voler eliminare questa proprietà? Tutti i valori salvati per gli articoli verranno persi.')) {
            document.getElementById('deleteId').value = propId;
            document.getElementById('deleteForm').submit();
        }
    }
</script>
</body>
</html>