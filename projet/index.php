<?php
session_start();

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

include 'header.php'; // 包含头部导航
?>
<main>
<?php
include 'aside.php'; // 包含侧边栏
?>
<div id="content">
    <?php
    // 检测 URL 参数加载相应的内容
    if (isset($_GET['page']) && $_GET['page'] != '') {
        $page = $_GET['page'];
        if ($page == 'inscrire' && file_exists('inscrire.php')) {
            include 'inscrire.php';
        } else {
            // 页面未找到
            echo '<p>Page non trouvée.</p>';
        }
    } else {
        // 默认加载内容
    }
    ?>
    <div class="cocktail-list">
        <?php
        include 'Donnees.inc.php'; // 包含数据

        // 检查并加载用户的收藏列表
        if (isset($_SESSION['user']) && isset($_SESSION['user']['favorites'])) {
            $_SESSION['favorites'] = $_SESSION['user']['favorites'];
        } elseif (!isset($_SESSION['favorites'])) {
            $_SESSION['favorites'] = [];
        }

        // 检查 $Recettes 数组是否存在且为数组
        if (isset($Recettes) && is_array($Recettes)) {
            // 遍历每个鸡尾酒配方
            foreach ($Recettes as $cocktail) {
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
                echo '<a href="recette.php?titre=' . urlencode($titre) . '">';
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
        } else {
            echo 'No cocktails found.';
        }
        ?>
    </div>
</div>
</main>
</body>
</html>
