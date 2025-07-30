<?php
header('Content-type: text/html; charset=utf-8');
$currentPage = 'dashboard_mobile'; // Per la navbar, se necessario
include 'navbarMagazzino.php';
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Stile per rendere la card più simile a un pulsante grande */
        .dashboard-card {
            transition: transform 0.2s ease-in-out;
        }
        .dashboard-card:hover {
            transform: scale(1.03);
        }
    </style>
</head>
<body>

<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-10 col-md-6 col-lg-4">

            <a href="scanner.php" class="text-decoration-none">
                <div class="card text-center text-bg-primary shadow-lg p-4 dashboard-card">
                    <div class="card-body">
                        <i class="bi bi-qr-code-scan" style="font-size: 4rem;"></i>
                        <h2 class="card-title mt-3">PRELEVA ARTICOLO</h2>
                        <p class="card-text">Scansiona il QR code per scaricare un articolo dal magazzino.</p>
                    </div>
                </div>
            </a>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>