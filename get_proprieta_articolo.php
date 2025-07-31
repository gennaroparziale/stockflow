<?php
require_once 'db.php';
header('Content-Type: application/json');

$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$articolo_id = isset($_GET['articolo_id']) ? (int)$_GET['articolo_id'] : 0;
$response = array();

if ($categoria_id > 0) {
    // 1. Prendo le definizioni delle proprietà per la categoria
    $sql = "SELECT id, nome_proprieta, tipo_dato FROM proprieta WHERE id_categoria = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($categoria_id));
    $proprieta = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Se è un articolo esistente, prendo i suoi valori già salvati
    $valori_esistenti = array();
    if ($articolo_id > 0) {
        $sql_val = "SELECT id_proprieta, valore FROM valori_proprieta WHERE id_articolo = ?";
        $stmt_val = $pdo->prepare($sql_val);
        $stmt_val->execute(array($articolo_id));
        while ($row = $stmt_val->fetch(PDO::FETCH_ASSOC)) {
            $valori_esistenti[$row['id_proprieta']] = $row['valore'];
        }
    }

    // 3. Unisco le definizioni con i valori
    foreach($proprieta as $prop) {
        $response[] = array(
            'id' => $prop['id'],
            'nome' => $prop['nome_proprieta'],
            'tipo' => $prop['tipo_dato'],
            'valore' => isset($valori_esistenti[$prop['id']]) ? $valori_esistenti[$prop['id']] : ''
        );
    }
}

echo json_encode($response);
?>