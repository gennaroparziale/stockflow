<?php
require_once 'db.php';
require_once 'auth_check.php';

// --- LOGICA DI ELABORAZIONE POST (PER MODIFICA/CANCELLAZIONE/AGGIUNTA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    try {
        if ($action === 'add') {
            $sql = "INSERT INTO fornitori (nome_fornitore, referente, email, telefono) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($_POST['nome_fornitore'], $_POST['referente'], $_POST['email'], $_POST['telefono']));
        } elseif ($action === 'edit') {
            $sql = "UPDATE fornitori SET nome_fornitore = ?, referente = ?, email = ?, telefono = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($_POST['nome_fornitore'], $_POST['referente'], $_POST['email'], $_POST['telefono'], $_POST['id']));
        } elseif ($action === 'delete') {
            $sql = "DELETE FROM fornitori WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array($_POST['id']));
        }
        header("Location: gestione_fornitori.php");
        exit();
    } catch (PDOException $e) {
        echo "Errore: " . $e->getMessage();
    }
}

// --- LOGICA DI RICERCA E VISUALIZZAZIONE ---
$search_term = isset($_GET['q']) ? $_GET['q'] : '';
$sql = "SELECT * FROM fornitori";
$params = array();

if (!empty($search_term)) {
    $sql .= " WHERE nome_fornitore LIKE ? OR referente LIKE ? OR email LIKE ?";
    $like_term = '%' . $search_term . '%';
    $params = array($like_term, $like_term, $like_term);
}

$sql .= " ORDER BY nome_fornitore";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fornitori = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Fornitori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<? include 'navbarMagazzino.php';?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Anagrafica Fornitori</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fornitoreModal" onclick="prepareAddModal()">
            <i class="bi bi-plus-circle"></i> Aggiungi Fornitore
        </button>
    </div>

    <form action="gestione_fornitori.php" method="GET" class="mb-4">
        <div class="input-group">
            <input type="search" name="q" class="form-control" placeholder="Cerca per nome, referente o email..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Cerca</button>
        </div>
    </form>

    <table class="table table-striped table-hover">
        <thead class="table-dark">
        <tr>
            <th>Nome Fornitore</th>
            <th>Referente</th>
            <th>Email</th>
            <th>Telefono</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($fornitori)): ?>
            <tr>
                <td colspan="5" class="text-center">Nessun fornitore trovato.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($fornitori as $fornitore): ?>
                <tr>
                    <td><?php echo htmlspecialchars($fornitore['nome_fornitore']); ?></td>
                    <td><?php echo htmlspecialchars($fornitore['referente']); ?></td>
                    <td><?php echo htmlspecialchars($fornitore['email']); ?></td>
                    <td><?php echo htmlspecialchars($fornitore['telefono']); ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-warning" onclick='prepareEditModal(<?php echo json_encode($fornitore); ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteFornitore(<?php echo $fornitore['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="fornitoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="fornitoreForm" method="POST" action="gestione_fornitori.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Aggiungi Fornitore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="fornitoreId">
                    <input type="hidden" name="action" id="formAction">
                    <div class="mb-3">
                        <label for="nome_fornitore" class="form-label">Nome Fornitore <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome_fornitore" name="nome_fornitore" required>
                    </div>
                    <div class="mb-3">
                        <label for="referente" class="form-label">Referente</label>
                        <input type="text" class="form-control" id="referente" name="referente">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Telefono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono">
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

<form id="deleteForm" method="POST" action="gestione_fornitori.php" class="d-none">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="action" value="delete">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var fornitoreModal = new bootstrap.Modal(document.getElementById('fornitoreModal'));
    var fornitoreForm = document.getElementById('fornitoreForm');

    function prepareAddModal() {
        fornitoreForm.reset();
        document.getElementById('modalTitle').textContent = 'Aggiungi Fornitore';
        document.getElementById('formAction').value = 'add';
        document.getElementById('fornitoreId').value = '';
        fornitoreModal.show();
    }

    function prepareEditModal(fornitore) {
        fornitoreForm.reset();
        document.getElementById('modalTitle').textContent = 'Modifica Fornitore';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('fornitoreId').value = fornitore.id;
        document.getElementById('nome_fornitore').value = fornitore.nome_fornitore;
        document.getElementById('referente').value = fornitore.referente;
        document.getElementById('email').value = fornitore.email;
        document.getElementById('telefono').value = fornitore.telefono;
        fornitoreModal.show();
    }

    function deleteFornitore(id) {
        if (confirm('Sei sicuro di voler eliminare questo fornitore?')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

</body>
</html>