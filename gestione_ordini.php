<?php
header('Content-type: text/html; charset=utf-8');
require_once 'db.php';
require_once 'auth_check.php';

// Query per recuperare la lista degli ordini con il nome del fornitore
$sql = "
    SELECT 
        o.id,
        o.riferimento,
        o.data_ordine,
        o.stato,
        f.nome_fornitore
    FROM ordini_fornitore o
    JOIN fornitori f ON o.fornitore_id = f.id
    ORDER BY o.data_ordine DESC, o.id DESC
";
$stmt = $pdo->query($sql);
$ordini = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'gestione_ordini.php';
include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Ordini a Fornitore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Ordini a Fornitore</h1>
        <a href="dettaglio_ordine.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Crea Nuovo Ordine
        </a>
    </div>

    <table class="table table-hover">
        <thead class="table-dark">
        <tr>
            <th>Riferimento</th>
            <th>Fornitore</th>
            <th>Data Ordine</th>
            <th class="text-center">Stato</th>
            <th class="text-end">Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($ordini)): ?>
            <tr><td colspan="5" class="text-center">Nessun ordine trovato.</td></tr>
        <?php else: ?>
            <?php foreach ($ordini as $ordine): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($ordine['riferimento'] ?: 'Ordine #' . $ordine['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($ordine['nome_fornitore']); ?></td>
                    <td><?php echo date("d/m/Y", strtotime($ordine['data_ordine'])); ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?php echo htmlspecialchars($ordine['stato']); ?></span></td>
                    <td class="text-end">
                        <a href="dettaglio_ordine.php?id=<?php echo $ordine['id']; ?>" class="btn btn-sm btn-info" title="Vedi/Modifica">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>