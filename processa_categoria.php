<?php
require_once 'db.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestione_categorie.php');
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$descrizione = isset($_POST['descrizione']) ? trim($_POST['descrizione']) : '';

try {
    if ($action === 'add' && !empty($nome)) {
        $sql = "INSERT INTO categorie_articoli (nome, descrizione) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($nome, $descrizione));
    } elseif ($action === 'edit' && $id > 0 && !empty($nome)) {
        $sql = "UPDATE categorie_articoli SET nome = ?, descrizione = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($nome, $descrizione, $id));
    } elseif ($action === 'delete' && $id > 0) {
        $sql = "DELETE FROM categorie_articoli WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($id));
    }
} catch (PDOException $e) {
    // In un'applicazione reale, gestire l'errore in modo più appropriato (es. logging)
    die("Errore database: " . $e->getMessage());
}

header("Location: gestione_categorie.php");
exit();
?>