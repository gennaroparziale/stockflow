<?php
require_once 'db.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestione_proprieta.php');
    exit();
}

$action = $_POST['action'];
$id_categoria = isset($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : 0;

try {
    if ($action === 'add') {
        $nome = trim($_POST['nome_proprieta']);
        $tipo = $_POST['tipo_dato'];
        if (!empty($nome) && $id_categoria > 0) {
            $stmt = $pdo->prepare("INSERT INTO proprieta (id_categoria, nome_proprieta, tipo_dato) VALUES (?, ?, ?)");
            $stmt->execute(array($id_categoria, $nome, $tipo));
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM proprieta WHERE id = ?");
        $stmt->execute(array($id));
    }
} catch (PDOException $e) {
    die("Errore: " . $e->getMessage());
}

header("Location: gestione_proprieta.php?categoria_id=" . $id_categoria);
exit();
?>