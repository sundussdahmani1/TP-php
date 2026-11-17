<?php
require_once 'functions.php';

// Redirection si une session est déjà ouverte
if (connecte()) {
    header('Location: liste.php');
    exit;
}

$titre = 'Authentification';
$messageErreur = '';

// Lecture du cookie de mémorisation du dernier identifiant saisi
$identifiantSaisi = $_COOKIE['dernier_user'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = nettoyer($_POST['login'] ?? '');
    $mdp   = $_POST['mdp'] ?? '';

    if (isset(UTILISATEURS[$login]) && UTILISATEURS[$login] === $mdp) {
        // Enregistrement de l'utilisateur dans la session
        $_SESSION['utilisateur_connecte'] = $login;

        // Création du cookie valable pendant 7 jours (7 * 24 * 3600 secondes)
        setcookie('dernier_user', $login, time() + (7 * 86400), '/', '', false, true);

        header('Location: liste.php');
        exit;
    } else {
        $messageErreur = 'Identifiants invalides. Veuillez vérifier votre nom d\'utilisateur et mot de passe.';
        $identifiantSaisi = $login;
    }
}

include 'header.php';
?>

<div class="cadre-centre">
    <div class="carte-connexion">
        <div class="entete-connexion">
            <div class="icone-badge">🛡️</div>
            <h2>Espace d'Accès Sécurisé</h2>
            <p class="sous-titre-connexion">Connectez-vous pour gérer les dossiers des étudiants</p>
        </div>

        <?php if ($messageErreur !== ''): ?>
            <div class="alerte alerte-danger">
                <span class="alerte-icone">⚠️</span>
                <span><?= h($messageErreur) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php" class="formulaire-acces">
            <div class="groupe-saisie">
                <label for="champ-login">Identifiant de connexion</label>
                <div class="input-icone-wrap">
                    <span class="champ-icone">👤</span>
                    <input type="text"
                           id="champ-login"
                           name="login"
                           value="<?= h($identifiantSaisi) ?>"
                           placeholder="Entrez votre identifiant"
                           autocomplete="username"
                           required
                           autofocus>
                </div>
            </div>

            <div class="groupe-saisie">
                <label for="champ-mdp">Mot de passe</label>
                <div class="input-icone-wrap">
                    <span class="champ-icone">🔒</span>
                    <input type="password"
                           id="champ-mdp"
                           name="mdp"
                           placeholder="Entrez votre mot de passe"
                           autocomplete="current-password"
                           required>
                </div>
            </div>

            <button type="submit" class="bouton-soumission-login">
                Se connecter à la plateforme &rarr;
            </button>
        </form>

        <div class="comptes-demonstration">
            <p class="demo-titre">Identifiants disponibles pour l'évaluation :</p>
            <div class="demo-tags">
                <code>sunduss</code> / <code>sunduss123</code> &bull;
                <code>professeur</code> / <code>tp2024</code> &bull;
                <code>admin</code> / <code>admin123</code>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
