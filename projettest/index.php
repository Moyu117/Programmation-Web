<?php 
session_start();
include 'Donnees.inc.php'; // 包含数据
include 'recherche.php';

// 用户登录处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    // 读取用户数据
    $users = [];
    if (file_exists('user.json')) {
        $json = file_get_contents('user.json');
        $users = json_decode($json, true);
    }

    $login = trim($_POST['login']);
    $password = $_POST['password'];

    if (isset($users[$login]) && password_verify($password, $users[$login]['password'])) {
        $_SESSION['user'] = $users[$login];

        // 加载用户的收藏列表
        if (isset($users[$login]['favorites'])) {
            $_SESSION['favorites'] = $users[$login]['favorites'];
        }
    } else {
        $login_error = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }
}

// 处理注销
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit();
}

// 处理收藏状态的切换
if (isset($_GET['action']) && $_GET['action'] === 'toggle_favorite' && isset($_GET['recipe'])) {
    $recipeTitle = $_GET['recipe'];

    // 初始化收藏列表
    if (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }

    // 切换收藏状态
    if (in_array($recipeTitle, $_SESSION['favorites'])) {
        // 取消收藏
        $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$recipeTitle]);
    } else {
        // 添加收藏
        $_SESSION['favorites'][] = $recipeTitle;
    }

    // 如果用户已登录，保存收藏到用户数据
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

    // 重定向回首页
    header('Location: index.php');
    exit();
}
    
	// 检查是否有搜索请求
    $search_results = [];
    $desired_ingredients = [];
    $undesired_ingredients = [];
    $unrecognized_elements = [];
    $error_message = '';
	$is_search = false;
	if (isset($_GET['recherche']) && trim($_GET['recherche']) !== '') {
    $recherche = $_GET['recherche'];
    $is_search = true;
    // 调用 perform_search 函数
    $search_data = perform_search($recherche, $Recettes, $Hierarchie);

    // 提取返回的数据
    $error_message = $search_data['error_message'];
    $desired_ingredients = $search_data['desired_ingredients'] ?? [];
    $undesired_ingredients = $search_data['undesired_ingredients'] ?? [];
    $unrecognized_elements = $search_data['unrecognized_elements'] ?? [];
    $search_results = $search_data['search_results'] ?? [];
    }
    
include 'header.php'; // 包含头部导航
?>
<main>
<?php
include 'aside.php'; // 包含侧边栏
?>

<div id="content">
    <?php
    // 显示解析结果和错误消息
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

    // 检查并加载用户的收藏列表
    if (isset($_SESSION['user']) && isset($_SESSION['user']['favorites'])) {
        $_SESSION['favorites'] = $_SESSION['user']['favorites'];
    } elseif (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }

    // 如果有搜索结果，使用搜索结果
    if (!empty($search_results)) {
        $recipes_to_display = $search_results;
    } else {
        // 原有的类别筛选逻辑
        // 获取选定的类别
        $category = isset($_GET['cat']) ? $_GET['cat'] : null;

        // 如果选择了类别，获取所有子类别
        if ($category) {
            // 获取所有子类别的函数
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

        // 如果选择了类别，筛选食谱
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

    // 显示食谱列表
    if (!empty($recipes_to_display)) {
        echo '<div class="cocktail-list">';
        foreach ($recipes_to_display as $cocktail) {
            $titre = $cocktail['titre'];
            echo '<div class="cocktail-item">';
            echo '<h3 class="cocktail-title">';

            // 将标题包裹在链接中
            echo '<a href="recette.php?titre=' . urlencode($titre) . '">';
            echo htmlspecialchars($titre);
            echo '</a>';

            // 判断是否已收藏
            $isFavorite = in_array($titre, $_SESSION['favorites']);

            // 设置心形图标的类名
            $heartClass = $isFavorite ? 'heart filled' : 'heart';

            // 心形图标，点击后调用 toggle_favorite 动作
            echo '<a href="index.php?action=toggle_favorite&recipe=' . urlencode($titre) . '">';
            echo '<span class="' . $heartClass . '">❤</span>';
            echo '</a>';

            echo '</h3>';

            // 图像处理
            $titreNormalized = strtolower(trim($cocktail['titre']));
            $titreNormalized = str_replace([' ', 'ï', 'ñ', "'"], ['_', 'i', 'n', ''], $titreNormalized);
            $imageName = $titreNormalized . '.jpg';
            $imagePath = 'Photos/' . $imageName;

            // 仅当标题完全匹配图片名称时才匹配
            $photosDir = 'Photos/';
            $matchedImage = 'default.jpg';
            foreach (glob($photosDir . '*.jpg') as $photo) {
                if (strcasecmp($titreNormalized, basename($photo, '.jpg')) == 0) {
                    $matchedImage = basename($photo);
                    break;
                }
            }
            $imagePath = $photosDir . $matchedImage;

            // 将图片包裹在链接中
            echo '<a href="recette.php?titre=' . urlencode($titre) . '">';
            if (file_exists($imagePath)) {
                echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($cocktail['titre']) . '">';
            } else {
                echo '<img src="Photos/default.jpg" alt="Image not found">';
            }
            echo '</a>';

            // 显示匹配度（如果存在）
           if ($is_search && isset($cocktail['score'])) {
            echo '<p class="match-percentage">Score de satisfaction : ' . round($cocktail['score'], 2) . '%</p>';
           }

            // 显示成分
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
