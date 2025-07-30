<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Se l'utente è già loggato, lo reindirizzo alla dashboard
if (isset($_SESSION['userid'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - STOCKFLOW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin: 100px auto; padding: 2rem; background-color: #fff; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); }
    </style>
</head>
<body>
<div class="login-container text-center">
    <div class="col-md-12">
        <img src="img/logo_stockflow.png" width="150px">
    </div>
    <p class="text-muted">Accedi al tuo account</p>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger">Credenziali non valide. Riprova.</div>
    <?php endif; ?>

    <form action="processa_login.php" method="POST">
        <div class="form-floating mb-3">
            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
            <label for="username">Username</label>
        </div>
        <div class="form-floating mb-3">
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            <label for="password">Password</label>
        </div>
        <button class="w-100 btn btn-lg btn-primary" type="submit">Accedi</button>
    </form>
</div>
</body>
</html>