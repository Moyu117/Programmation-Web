<?php
session_start();
?>
<?php
// 如果用户已登录，重定向到主页
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// 处理注册请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 读取用户数据
    $users = [];
    if (file_exists('user.json')) {
        $json = file_get_contents('user.json');
        $users = json_decode($json, true);
    }

    // 获取表单数据
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $Nom = trim($_POST['Nom']);
    $prenom = trim($_POST['prenom']);
    $gender = $_POST['gender'];
    $birthDate = $_POST['birthDate'];

    // 数据验证
    $errors = [];

    if (!preg_match('/^[A-Za-z0-9]+$/', $login)) {
        $errors[] = 'Le nom d\'utilisateur doit contenir uniquement des lettres non accentuées et/ou des chiffres.';
    }

    if (isset($users[$login])) {
        $errors[] = 'Le nom d\'utilisateur existe déjà.';
    }

    if ($Nom && !preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ\-' ]+$/u", $Nom)) {
        $errors[] = 'Le nom de famille contient des caractères invalides.';
    }

    if ($prenom && !preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ\-' ]+$/u", $prenom)) {
        $errors[] = 'Le prénom contient des caractères invalides.';
    }

    if ($birthDate) {
        $birthTime = strtotime($birthDate);
        $age = (int)((time() - $birthTime) / (365.25 * 24 * 60 * 60));
        if ($age < 18) {
            $errors[] = 'Vous devez avoir au moins 18 ans.';
        }
    }

    if (empty($errors)) {
        // 密码哈希
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 存储用户数据
        $users[$login] = [
            'login' => $login,
            'password' => $hashedPassword,
            'Nom' => $Nom,
            'prenom' => $prenom,
            'gender' => $gender,
            'birthDate' => $birthDate
        ];

        file_put_contents('user.json', json_encode($users));

        // 注册成功，重定向到登录页面
        header('Location: index.php');
        exit();
    }
}
?>

<?php include 'header.php'; ?>
<link rel="stylesheet" href="inscrire_style.css">
<main>
    <div class="container">
        <h2>Inscription</h2>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php
                foreach ($errors as $error) {
                    echo '<p>' . htmlspecialchars($error) . '</p>';
                }
                ?>
            </div>
        <?php endif; ?>
        <form method="post" action="inscrire.php">
            <label>Login (obligatoire):
                <input type="text" name="login" required>
            </label>
            <label>Mot de passe (obligatoire):
                <input type="password" name="password" required>
            </label>
            <label>Nom:
                <input type="text" name="Nom">
            </label>
            <label>Prénom:
                <input type="text" name="prenom">
            </label>
            <label>Sexe:
                <select name="gender">
                    <option value="">Sélectionnez</option>
                    <option value="homme">Homme</option>
                    <option value="femme">Femme</option>
                </select>
            </label>
            <label>Date de naissance(obligatoire):
                <input type="date" name="birthDate" required>
            </label>
            <button type="submit">S'inscrire</button>
        </form>
    </div>
</main>
</body>
</html>


