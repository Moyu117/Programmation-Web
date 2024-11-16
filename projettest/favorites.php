<?php
session_start();
include 'header.php'; // 包含头部导航
?>
<main>
<?php
include 'aside.php'; // 包含侧边栏
?>
<div id="content">
    <h2>Mes Recettes Préférées</h2>
    <?php
    include 'Donnees.inc.php'; // 包含数据

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

        // 重定向回收藏页面
        header('Location: favorites.php');
        exit();
    }

    // 获取用户的收藏列表
    if (isset($_SESSION['favorites']) && !empty($_SESSION['favorites'])) {
        $favorites = $_SESSION['favorites'];

        // 按字母顺序排序
        sort($favorites);

        echo '<div class="cocktail-list">';
        foreach ($favorites as $favoriteTitle) {
            // 查找对应的食谱信息
            foreach ($Recettes as $cocktail) {
                if ($cocktail['titre'] === $favoriteTitle) {
                    echo '<div class="cocktail-item">';
                    echo '<h3 class="cocktail-title">';

                    // 将标题包裹在链接中
                    echo '<a href="recette.php?titre=' . urlencode($cocktail['titre']) . '">';
                    echo htmlspecialchars($cocktail['titre']);
                    echo '</a>';

                    // 判断是否已收藏
                    $isFavorite = in_array($cocktail['titre'], $_SESSION['favorites']);

                    // 设置心形图标的类名
                    $heartClass = $isFavorite ? 'heart filled' : 'heart';

                    // 心形图标，点击后调用 toggle_favorite 动作
                    echo '<a href="favorites.php?action=toggle_favorite&recipe=' . urlencode($cocktail['titre']) . '">';
                    echo '<span class="' . $heartClass . '">❤</span>';
                    echo '</a>';

                    echo '</h3>';

                    // 图像处理
                    $titreNormalized = strtolower(trim($cocktail['titre']));
                    $titreNormalized = str_replace([' ', 'ï', 'ñ', "'"], ['_', 'i', 'n', ''], $titreNormalized);
                    $imageName = $titreNormalized . '.jpg';
                    $imagePath = 'Photos/' . $imageName;

                    // 查找是否有部分匹配的图片
                    $photosDir = 'Photos/';
                    $matchedImage = 'default.jpg';
                    foreach (glob($photosDir . '*.jpg') as $photo) {
                        if (stripos($titreNormalized, basename($photo, '.jpg')) !== false) {
                            $matchedImage = basename($photo);
                            break;
                        }
                    }
                    $imagePath = $photosDir . $matchedImage;

                    // 将图片包裹在链接中
                    echo '<a href="recette.php?titre=' . urlencode($cocktail['titre']) . '">';
                    if (file_exists($imagePath)) {
                        echo '<img src="' . $imagePath . '" alt="' . htmlspecialchars($cocktail['titre']) . '">';
                    } else {
                        echo '<img src="Photos/default.jpg" alt="Image not found">';
                    }
                    echo '</a>';

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

