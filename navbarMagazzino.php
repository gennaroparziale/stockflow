<?php
// Ottiene il nome del file della pagina corrente
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['SCRIPT_NAME']);
}

// Definisce quali pagine appartengono a quale dropdown per mantenere lo stato "active"
$anagrafichePages = array('gestione_articoli.php', 'gestione_fornitori.php', 'gestione_categorie.php', 'gestione_proprieta.php');
$reportPages = array('report_sottoscorta.php');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-box-seam"></i> StockFlow
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'index.php') echo 'active'; ?>" href="index.php">
                        <i class="bi bi-bar-chart-line-fill"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'inventario.php') echo 'active'; ?>" href="inventario.php">
                        <i class="bi bi-boxes"></i> Inventario
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'gestione_ordini.php') echo 'active'; ?>" href="gestione_ordini.php">
                        <i class="bi bi-truck"></i> Ordini Fornitore
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'storico_movimenti.php') echo 'active'; ?>" href="storico_movimenti.php">
                        <i class="bi bi-clock-history"></i> Storico
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if (in_array($currentPage, $anagrafichePages)) echo 'active'; ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-pencil-square"></i> Anagrafiche
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="gestione_articoli.php">Gestione Articoli</a></li>
                        <li><a class="dropdown-item" href="gestione_categorie.php">Gestione Categorie</a></li>
                        <li><a class="dropdown-item" href="gestione_proprieta.php">Gestione Proprietà</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="gestione_fornitori.php">Gestione Fornitori</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php if (in_array($currentPage, $reportPages)) echo 'active'; ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-file-earmark-text"></i> Report
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="report_sottoscorta.php">Articoli Sotto Scorta</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php if ($currentPage == 'impostazioni.php') echo 'active'; ?>" href="impostazioni.php">
                        <i class="bi bi-gear"></i> Impostazioni
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nome']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="impostazioni.php">Impostazioni</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>