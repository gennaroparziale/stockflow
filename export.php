<?php
require_once 'db.php';

// Determina quale report esportare
$report = isset($_GET['report']) ? $_GET['report'] : '';

if ($report == 'inventario') {
    // Impostazioni per il file CSV
    $filename = "inventario_" . date('Y-m-d') . ".csv";

    // Intestazioni per forzare il download del file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // --- QUERY PER ESTRARRE I DATI (RISPETTANDO I FILTRI) ---
    $search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

    $sql = "
        SELECT 
            a.codice_articolo, a.descrizione, i.giacenza, a.scorta_minima, 
            a.prezzo_acquisto, f.nome_fornitore
        FROM articoli a
        JOIN inventario i ON a.id = i.articolo_id
        LEFT JOIN fornitori f ON a.fornitore_id = f.id
    ";
    $params = array();

    if (!empty($search_term)) {
        $sql .= " WHERE a.codice_articolo LIKE ? OR a.descrizione LIKE ?";
        $like_term = '%' . $search_term . '%';
        $params = array($like_term, $like_term);
    }

    $sql .= " ORDER BY a.descrizione";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Apri lo stream di output di PHP per scrivere il CSV
    $output = fopen('php://output', 'w');

    // Scrivi l'intestazione del file CSV
    fputcsv($output, array(
        'Codice Articolo',
        'Descrizione',
        'Giacenza',
        'Scorta Minima',
        'Prezzo Acquisto',
        'Fornitore'
    ), ';'); // Usa il punto e virgola come separatore

    // Scrivi ogni riga di dati nel file CSV
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row, ';');
    }

    // Chiudi lo stream
    fclose($output);
    exit();

} else {
    // Se il report non è valido, mostra un errore o reindirizza
    die("Report non valido.");
}
?>