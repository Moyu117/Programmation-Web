<?php
session_start();

// verifer si connecter
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// lire donnee
$users = [];
if (file_exists('user.json')) {
    $json = file_get_contents('user.json');
    $users = json_decode($json, true);
}

$login = $_SESSION['user']['login'];
$user = $users[$login];
// obtenir donne de list
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $Nom = trim($_POST['Nom']);
    $prenom = trim($_POST['prenom']);
    $gender = $_POST['gender'];
    $birthDate = $_POST['birthDate'];
    $errors = [];

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
        //renouveler
        $users[$login]['Nom'] = $Nom;
        $users[$login]['prenom'] = $prenom;
        $users[$login]['gender'] = $gender;
        $users[$login]['birthDate'] = $birthDate;
        // stoker a  user.json
        file_put_contents('user.json', json_encode($users));
        $_SESSION['user'] = $users[$login];

        $success = 'Vos informations ont été mises à jour avec succès.';
    }
}

// apres renouveler
$user = $users[$login];

?>
<?php include 'header.php'; ?>

<!-- CSS-->
<link rel="stylesheet" href="profil_style.css">

<main>
    <div class="container">
        <h2>Profil</h2>
        <?php if (isset($success)): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php
                foreach ($errors as $error) {
                    echo '<p>' . htmlspecialchars($error) . '</p>';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- afficher  -->
        <div class="user-info">
            <p><strong>Login:</strong> <?php echo htmlspecialchars($user['login']); ?></p>
            <?php if ($user['Nom'] || $user['prenom']): ?>
                <p><strong>Nom complet:</strong> <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['Nom']); ?></p>
			<?php else:?>
			    <p><strong>Nom complet:</strong> NULL</p>
            <?php endif; ?>
            <?php if ($user['gender']): ?>
                <p><strong>Sexe:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
			<?php else:?>
			    <p><strong>Sexe:</strong> NULL</p>
            <?php endif; ?>
            <?php if ($user['birthDate']): ?>
                <p><strong>Date de naissance:</strong> <?php echo htmlspecialchars($user['birthDate']); ?></p>
			<?php else:?>
			    <p><strong>Date de naissance:</strong> NULL</p>
            <?php endif; ?>
        </div>

        <!-- modifier -->
        <h3>Modifier vos informations</h3>
        <form method="post" action="profil.php">
            <div class="form-group">
                <label for="Nom">Nom:</label>
                <input type="text" name="Nom" id="Nom" value="<?php echo htmlspecialchars($user['Nom']); ?>">
            </div>
            <div class="form-group">
                <label for="prenom">Prénom:</label>
                <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>">
            </div>
            <div class="form-group">
                <label for="gender">Sexe:</label>
                <select name="gender" id="gender">
                    <option value="">Sélectionnez</option>
                    <option value="homme" <?php if ($user['gender'] == 'homme') echo 'selected'; ?>>Homme</option>
                    <option value="femme" <?php if ($user['gender'] == 'femme') echo 'selected'; ?>>Femme</option>
                </select>
            </div>
            <div class="form-group">
                <label for="birthDate">Date de naissance:</label>
                <input type="date" name="birthDate" id="birthDate" value="<?php echo $user['birthDate']; ?>">
            </div>
            <button type="submit">Enregistrer les modifications</button>
        </form>
    </div>
</main>

</body>
</html>
