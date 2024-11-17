<?php
session_start();
include 'header.php';
?>
<main>
<?php
include 'aside.php'; 
?>
<div id="content">
    <?php
    include 'Donnees.inc.php';
    if (isset($_GET['action']) && $_GET['action'] === 'toggle_favorite' && isset($_GET['recipe'])) {
        $recipeTitle = $_GET['recipe'];
        // initialiser
        if (!isset($_SESSION['favorites'])) {
            $_SESSION['favorites'] = [];
        }
        // Changer le etat de favorites
        if (in_array($recipeTitle, $_SESSION['favorites'])) {
            $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$recipeTitle]);
        } else {
            $_SESSION['favorites'][] = $recipeTitle;
        }
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
        header('Location: recette.php?titre=' . urlencode($recipeTitle));
        exit();
    }

    if (isset($_GET['titre'])) {
        $titre = $_GET['titre'];
        if (isset($_SESSION['user']) && isset($_SESSION['user']['favorites'])) {
            $_SESSION['favorites'] = $_SESSION['user']['favorites'];
        } elseif (!isset($_SESSION['favorites'])) {
            $_SESSION['favorites'] = [];
        }

        //cherche chaque cocktail
        foreach ($Recettes as $cocktail) {
            if ($cocktail['titre'] === $titre) {
                echo '<div class="cocktail-item">';
                echo '<h3 class="cocktail-title">' . htmlspecialchars($cocktail['titre']);
                $isFavorite = in_array($titre, $_SESSION['favorites']);
                $heartClass = $isFavorite ? 'heart filled' : 'heart';
                echo '<a href="recette.php?action=toggle_favorite&recipe=' . urlencode($titre) . '&titre=' . urlencode($titre) . '">';
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

                if (file_exists($imagePath)) {
                    echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($cocktail['titre']) . '">';
                } else {
                    echo '<img src="Photos/default.jpg" alt="Image not found">';
                }

               
                echo '<p>' . htmlspecialchars($cocktail['preparation']) . '</p>';

                
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
                break;
            }
        }
    } else {
        echo '<p>Aucune recette sélectionnée.</p>';
    }
    ?>
</div>
</main>
</body>
</html>
