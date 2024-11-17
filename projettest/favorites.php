<?php
session_start();
include 'header.php'; 
?>
<main>
<?php
include 'aside.php'; // left
?>
<div id="content">
    <h2>Mes Recettes Préférées</h2>
    <?php
    include 'Donnees.inc.php'; 

    // changer etat de favorites
    if (isset($_GET['action']) && $_GET['action'] === 'toggle_favorite' && isset($_GET['recipe'])) {
        $recipeTitle = $_GET['recipe'];

        // initialisation
        if (!isset($_SESSION['favorites'])) {
            $_SESSION['favorites'] = [];
        }

        // Changer le etat de collecte
        if (in_array($recipeTitle, $_SESSION['favorites'])) {
            // annuler
            $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$recipeTitle]);
        } else {
            // ajouter
            $_SESSION['favorites'][] = $recipeTitle;
        }

        // si connect 
        if (isset($_SESSION['user'])) {
            $users = [];
            if (file_exists('user.json')) {
                $json = file_get_contents('user.json');
                $users = json_decode($json, true);
            }
            $login = $_SESSION['user']['login'];
            $users[$login]['favorites'] = $_SESSION['favorites'];
            file_put_contents('user.json', json_encode($users));
        }

        //retourner
        header('Location: favorites.php');
        exit();
    }

    // Obtenir la liste des favoris de l'utilisateur
    if (isset($_SESSION['favorites']) && !empty($_SESSION['favorites'])) {
        $favorites = $_SESSION['favorites'];

        //ordre alphabétique
        sort($favorites);
        //cookie carte
        echo '<div class="cocktail-list">';
        foreach ($favorites as $favoriteTitle) {
            // cherche
            foreach ($Recettes as $cocktail) {
                if ($cocktail['titre'] === $favoriteTitle) {
                    echo '<div class="cocktail-item">';
                    echo '<h3 class="cocktail-title">';
                    echo '<a href="recette.php?titre=' . urlencode($cocktail['titre']) . '">';
                    echo htmlspecialchars($cocktail['titre']);
                    echo '</a>';
                    $isFavorite = in_array($cocktail['titre'], $_SESSION['favorites']);
                    $heartClass = $isFavorite ? 'heart filled' : 'heart';
                    echo '<a href="favorites.php?action=toggle_favorite&recipe=' . urlencode($cocktail['titre']) . '">';
                    echo '<span class="' . $heartClass . '">❤</span>';
                    echo '</a>';
                    echo '</h3>';

                    $titreNormalized = strtolower(trim($cocktail['titre']));
                    $titreNormalized = str_replace([' ', 'ï', 'ñ', "'"], ['_', 'i', 'n', ''], $titreNormalized);
                    $imageName = $titreNormalized . '.jpg';
                    $imagePath = 'Photos/' . $imageName;
                    $photosDir = 'Photos/';
                    $matchedImage = 'default.jpg';
                    foreach (glob($photosDir . '*.jpg') as $photo) {
                        if (stripos($titreNormalized, basename($photo, '.jpg')) !== false) {
                            $matchedImage = basename($photo);
                            break;
                        }
                    }
                    $imagePath = $photosDir . $matchedImage;
                    echo '<a href="recette.php?titre=' . urlencode($cocktail['titre']) . '">';
                    if (file_exists($imagePath)) {
                        echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($cocktail['titre']) . '">';
                    } else {
                        echo '<img src="Photos/default.jpg" alt="Image not found">';
                    }
                    echo '</a>';
                    //afficher detail
                    echo '<ul>';
                    if (!empty($cocktail['ingredients'])) {
                        $ingredients = explode('|', $cocktail['ingredients']);
                        foreach ($ingredients as $ingredient) {
                            echo '<li>' . htmlspecialchars($ingredient) . '</li>';
                        }
                    } else {
                        echo '<li>Ingredients not available</li>';
                    }
                    echo '</ul>';

                    echo '</div>';
                }
            }
        }
        echo '</div>';
    } else {
        echo '<p>Vous n\'avez pas encore de recettes préférées.</p>';
    }
    ?>
</div>
</main>
</body>
</html>

