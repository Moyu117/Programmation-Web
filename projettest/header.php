<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des cocktails</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">
                <button class="Navigation">Navigation</button>
            </a>
            <a href="favorites.php">
                <button class="Recettes">Recettes ❤</button>
            </a>
            <!-- recherche -->
            <form method="GET" action="index.php" style="display:inline;">
            <input class="recherchetext" type="text" name="recherche" placeholder='"Jus de fruits" +Sel -Whisky'">
            <button type="submit">Recherche</button>
            </form>

            <div class="login">
                <?php if (!isset($_SESSION['user'])): ?> 
                <form method="post" action="index.php" style="display:inline;">
                    <input class="logintext" type="text" name="login" placeholder="Login" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit" name="action" value="login">Connexion</button>
                </form>
                <a href="inscrire.php">
                    <button>S'inscrire</button>
                </a>
                <?php else: ?>
                <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['user']['login']); ?></span>
                <a href="profil.php">
                    <button>Profil</button>
                </a>
                <a href="index.php?action=logout">
                    <button>Déconnexion</button>
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
