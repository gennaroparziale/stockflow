<?php
header('Content-type: text/html; charset=utf-8');
require_once 'auth_check.php';
// Per un'esperienza più pulita sullo scanner, la navbar può essere nascosta.
// include 'navbarMagazzino.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { background-color: #212529; }
        .card { border: none; }
        .scanner-container {
            max-width: 600px;
            margin: auto;
        }
        #qr-reader video {
            /* Forza il video ad avere un aspetto corretto su alcuni dispositivi */
            object-fit: cover !important;
        }
    </style>
</head>
<body>

<div class="container mt-4 scanner-container">
    <div class="card shadow-lg">
        <div class="card-header text-center bg-dark text-white">
            <h4 class="mb-0"><i class="bi bi-qr-code-scan"></i> Inquadra il Codice</h4>
        </div>
        <div class="card-body text-center p-2 bg-light">
            <div id="qr-reader" style="width:100%;"></div>
            <div id="qr-status-message" class="mt-2 p-2"></div>
        </div>
    </div>
     <div class="text-center mt-3 d-grid">
        <a href="dashboard_mobile.php" class="btn btn-secondary btn-lg">
            <i class="bi bi-house-door-fill"></i> Dashboard
        </a>
    </div>
</div>

<script>
    let html5QrcodeScanner;

    function onScanSuccess(decodedText, decodedResult) {
        html5QrcodeScanner.clear().catch(error => {
            console.error("Errore durante la chiusura dello scanner.", error);
        });

        const statusDiv = document.getElementById('qr-status-message');
        console.log(`Testo scansionato: "${decodedText}"`);

        if (decodedText && (decodedText.startsWith('http://') || decodedText.startsWith('https://'))) {
            statusDiv.innerHTML = `<div class="alert alert-success"><strong>Link Riconosciuto!</strong> Reindirizzamento...</div>`;
            window.location.href = decodedText;
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Errore:</strong> Il QR code non contiene un link valido.
                    <p class="small mb-0">Contenuto: ${decodedText}</p>
                </div>
                <button onclick="riavviaScanner()" class="btn btn-primary mt-2">Scansiona di Nuovo</button>
            `;
        }
    }

    function onScanFailure(error) { /* Lasciata vuota intenzionalmente */ }

    function riavviaScanner() {
        const statusDiv = document.getElementById('qr-status-message');
        statusDiv.innerHTML = '';
        if (html5QrcodeScanner && html5QrcodeScanner.getState() !== 2) { // 2 = SCANNING
             html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const statusDiv = document.getElementById('qr-status-message');

        // --- CONFIGURAZIONE ESPLICITA PER MAGGIORE COMPATIBILITÀ ---
        const config = {
            fps: 10,
            qrbox: (w, h) => ({ width: Math.floor(Math.min(w, h) * 0.75), height: Math.floor(Math.min(w, h) * 0.75) }),
            // Richiesta esplicita della fotocamera e delle sue capacità
            videoConstraints: {
                facingMode: "environment", // Fotocamera posteriore
                width: { min: 640, ideal: 1280 }, // Risoluzione preferita
                height: { min: 480, ideal: 720 }
            },
            disableFlip: true // Disabilita l'opzione di invertire la fotocamera
        };

        html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", config, false);

        statusDiv.innerHTML = `
            <div class="text-center text-muted">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 mb-0">In attesa dei permessi per la fotocamera...</p>
            </div>
        `;

        html5QrcodeScanner.render(onScanSuccess, onScanFailure).catch(err => {
            console.error("Errore nell'avvio dello scanner: ", err);
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Errore Fotocamera:</strong>
                    <p class="mb-0">Assicurati che il sito sia in <strong>HTTPS</strong> e di aver dato i <strong>permessi</strong> al browser.</p>
                </div>
            `;
        });
    });
</script>

</body>
</html>