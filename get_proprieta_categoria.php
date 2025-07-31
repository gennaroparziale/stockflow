<?php
require_once 'db.php';
header('Content-Type: application/json');

$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$response = array();

if ($categoria_id > 0) {
    $sql = "SELECT id, nome_proprieta, tipo_dato FROM proprieta WHERE id_categoria = ? ORDER BY nome_proprieta";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($categoria_id));
    $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($response);
?>