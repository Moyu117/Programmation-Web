<?php 
session_start();
include 'Donnees.inc.php'; 
include 'recherche.php';

// connexion des utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // Lire les données
    $users = [];
    if (file_exists('user.json')) {
        $json = file_get_contents('user.json');
        $users = json_decode($json, true);
    }

    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (isset($users[$login])) {
        // sha256
        $hashedPassword = hash('sha256', $password);

        if ($hashedPassword === $users[$login]['password']) {
            $_SESSION['user'] = $users[$login];

            // favorite
            if (isset($users[$login]['favorites'])) {
                $_SESSION['favorites'] = $users[$login]['favorites'];
            }
        } else {
            $login_error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        }
    } else {
        $login_error = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }

}

// logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit();
}

//changement de statut de favorable
if (isset($_GET['action']) && $_GET['action'] === 'toggle_favorite' && isset($_GET['recipe'])) {
    $recipeTitle = $_GET['recipe'];

    // Initialise
    if (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }

    // changer etat 
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

    header('Location: index.php');
    exit();
}
    
	// Vérifier demande de recherche
    $search_results = [];
    $desired_ingredients = [];
    $undesired_ingredients = [];
    $unrecognized_elements = [];
    $error_message = '';
	$is_search = false;
	if (isset($_GET['recherche']) && trim($_GET['recherche']) !== '') {
    $recherche = $_GET['recherche'];
    $is_search = true;
    // recherche
    $search_data = perform_search($recherche, $Recettes, $Hierarchie);

    // retourne les resultat
    $error_message = $search_data['error_message'];
    $desired_ingredients = $search_data['desired_ingredients'] ;
    $undesired_ingredients = $search_data['undesired_ingredients'];
    $unrecognized_elements = $search_data['unrecognized_elements'];
    $search_results = $search_data['search_results'];
    }
    
include 'header.php'; 
?>
<main>
<?php
include 'aside.php'; 
?>

<div id="content">
    <?php
    //afficher les res de recherche
    if (isset($error_message) && $error_message !== '') {
        echo '<p>' . htmlspecialchars($error_message) . '</p>';
    } else {
        if (!empty($desired_ingredients)) {
            echo '<p>Liste des aliments souhaités : ' . implode(', ', $desired_ingredients) . '</p>';
        }

        if (!empty($undesired_ingredients)) {
            echo '<p>Liste des aliments non souhaités : ' . implode(', ', $undesired_ingredients) . '</p>';
        }

        if (!empty($unrecognized_elements)) {
            echo '<p>Éléments non reconnus dans la requête : ' . implode(', ', $unrecognized_elements) . '</p>';
        }
    }

    
    if (isset($_SESSION['user']) && isset($_SESSION['user']['favorites'])) {
        $_SESSION['favorites'] = $_SESSION['user']['favorites'];
    } elseif (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }

    // si recherche
    if (!empty($search_results)) {
        $recipes_to_display = $search_results;
    } else {
        $category = isset($_GET['cat']) ? $_GET['cat'] : null;

        // Si la catégorie est sélectionnée, obtenez toutes les sous-catégories
        if ($category) {
            function getAllSubcategories($category, $Hierarchie) {
                $subcategories = array();
                if (isset($Hierarchie[$category]['sous-categorie'])) {
                    foreach ($Hierarchie[$category]['sous-categorie'] as $subcat) {
                        $subcategories[] = $subcat;
                        $subcategories = array_merge($subcategories, getAllSubcategories($subcat, $Hierarchie));
                    }
                }
                return $subcategories;
            }
            $allCategories = array_merge(array($category), getAllSubcategories($category, $Hierarchie));
        }
        // filtrer les recettes
        if ($category) {
            $filteredRecipes = array();
            foreach ($Recettes as $recette) {
                $ingredients = $recette['index'];
                if (array_intersect($allCategories, $ingredients)) {
                    $filteredRecipes[] = $recette;
                }
            }
            $recipes_to_display = $filteredRecipes;
        } else {
            $recipes_to_display = $Recettes;
        }
    }

    // afficher les recettes
    if (!empty($recipes_to_display)) {
        echo '<div class="cocktail-list">';
        foreach ($recipes_to_display as $cocktail) {
            $titre = $cocktail['titre'];
            echo '<div class="cocktail-item">';
            echo '<h3 class="cocktail-title">';
            echo '<a href="recette.php?titre=' . urlencode($titre) . '">';
            echo htmlspecialchars($titre);
            echo '</a>';
            // verifier
            $isFavorite = in_array($titre, $_SESSION['favorites']);
            $heartClass = $isFavorite ? 'heart filled' : 'heart';
            echo '<a href="index.php?action=toggle_favorite&recipe=' . urlencode($titre) . '">';
            echo '<span class="' . $heartClass . '">❤</span>';
            echo '</a>';
            echo '</h3>';
            $titreNormalized = strtolower(trim($cocktail['titre']));
            $titreNormalized = str_replace([' ', 'ï', 'ñ', "'"], ['_', 'i', 'n', ''], $titreNormalized);
            $imageName = $titreNormalized . '.jpg';
            $imagePath = 'Photos/' . $imageName;
            // Correspond uniquement si le titre correspond exactement au nom de l'image
            $photosDir = 'Photos/';
            $matchedImage = 'default.jpg';
            foreach (glob($photosDir . '*.jpg') as $photo) {
                if (strcasecmp($titreNormalized, basename($photo, '.jpg')) == 0) {
                    $matchedImage = basename($photo);
                    break;
                }
            }
            $imagePath = $photosDir . $matchedImage;
            echo '<a href="recette.php?titre=' . urlencode($titre) . '">';
            if (file_exists($imagePath)) {
                echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($cocktail['titre']) . '">';
            } else {
                echo '<img src="Photos/default.jpg" alt="Image not found">';
            }
            echo '</a>';

            // Afficher la percentage
           if ($is_search && isset($cocktail['score'])) {
            echo '<p class="match-percentage">Score de satisfaction : ' . round($cocktail['score'], 2) . '%</p>';
           }

            // afficher detail
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
        echo '</div>';
    } else {
        if ($error_message === '') {
            echo '<p>Aucun résultat trouvé.</p>';
        }
    }
    ?>
</div>
</main>
</body>
</html>
