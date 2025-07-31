<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// --- LOGICA DI RICERCA E VISUALIZZAZIONE ---
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM categorie_articoli";
$params = array();

if (!empty($search_term)) {
    $sql .= " WHERE nome LIKE ? OR descrizione LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = array($like_term, $like_term);
}

$sql .= " ORDER BY nome";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categorie = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Includi la navbar
$currentPage = 'gestione_categorie.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Categorie Articoli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Anagrafica Categorie Articoli</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal" onclick="prepareAddModal()">
            <i class="bi bi-plus-circle"></i> Aggiungi Categoria
        </button>
    </div>

    <form action="gestione_categorie.php" method="GET" class="mb-4">
        <div class="input-group">
            <input type="search" name="q" class="form-control" placeholder="Cerca per nome o descrizione..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Cerca</button>
        </div>
    </form>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
            <th>Nome Categoria</th>
            <th>Descrizione</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($categorie)): ?>
            <tr>
                <td colspan="3" class="text-center">Nessuna categoria trovata.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($categorie as $categoria): ?>
                <tr>
                    <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                    <td><?php echo htmlspecialchars($categoria['descrizione']); ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-warning" onclick='prepareEditModal(<?php echo json_encode($categoria); ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCategoria(<?php echo $categoria['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="categoriaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="categoriaForm" method="POST" action="processa_categoria.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Aggiungi Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoriaId">
                    <input type="hidden" name="action" id="formAction">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Categoria <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="descrizione" name="descrizione" rows="3"></textarea>
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

<form id="deleteForm" method="POST" action="processa_categoria.php" class="d-none">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="action" value="delete">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var categoriaModal = new bootstrap.Modal(document.getElementById('categoriaModal'));
    var categoriaForm = document.getElementById('categoriaForm');

    function prepareAddModal() {
        categoriaForm.reset();
        document.getElementById('modalTitle').textContent = 'Aggiungi Categoria';
        document.getElementById('formAction').value = 'add';
        document.getElementById('categoriaId').value = '';
        categoriaModal.show();
    }

    function prepareEditModal(categoria) {
        categoriaForm.reset();
        document.getElementById('modalTitle').textContent = 'Modifica Categoria';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('categoriaId').value = categoria.id;
        document.getElementById('nome').value = categoria.nome;
        document.getElementById('descrizione').value = categoria.descrizione;
        categoriaModal.show();
    }

    function deleteCategoria(id) {
        if (confirm('Sei sicuro di voler eliminare questa categoria? Gli articoli associati non verranno eliminati ma rimarranno senza categoria.')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

</body>
</html>