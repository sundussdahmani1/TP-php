<?php
require_once 'functions.php';
exiger_connexion();

$baseEtudiants = lire_etudiants();
$erreurs = [];

// Récupération sécurisée du paramètre GET id
$idUrl = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modeEdition = ($idUrl > 0 && isset($baseEtudiants[$idUrl]));

$titre = $modeEdition ? 'Modifier la fiche étudiant' : 'Enregistrer un nouvel étudiant';

// Initialisation des valeurs de formulaire
$nomChamp     = '';
$prenomChamp  = '';
$filiereChamp = '';
$noteChamp    = '';
$emailChamp   = '';
$photoActuelle= '';

// Pré-remplissage en mode modification
if ($modeEdition) {
    $cible = $baseEtudiants[$idUrl];
    $nomChamp     = $cible['nom'];
    $prenomChamp  = $cible['prenom'];
    $filiereChamp = $cible['filiere'];
    $noteChamp    = (string)$cible['note'];
    $emailChamp   = $cible['email'] ?? '';
    $photoActuelle= $cible['photo'] ?? '';
}

// Traitement de la requête de soumission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomChamp     = nettoyer($_POST['nom'] ?? '');
    $prenomChamp  = nettoyer($_POST['prenom'] ?? '');
    $filiereChamp = nettoyer($_POST['filiere'] ?? '');
    $noteChamp    = nettoyer($_POST['note'] ?? '');
    $emailChamp   = nettoyer($_POST['email'] ?? '');
    $idPoste      = isset($_POST['id_etudiant']) ? (int)$_POST['id_etudiant'] : 0;

    // --- Contrôles de validation côté serveur ---
    if ($nomChamp === '') {
        $erreurs[] = 'Le nom de famille est obligatoire.';
    }

    if ($prenomChamp === '') {
        $erreurs[] = 'Le prénom de l\'étudiant est obligatoire.';
    }

    if (!in_array($filiereChamp, FILIERES, true)) {
        $erreurs[] = 'Veuillez sélectionner une filière valide parmi les options proposées.';
    }

    if (!is_numeric($noteChamp) || (float)$noteChamp < 0 || (float)$noteChamp > 20) {
        $erreurs[] = 'La note doit obligatoirement être une valeur numérique comprise entre 0 et 20.';
    }

    // Validation du champ email (Bonus)
    if ($emailChamp !== '' && !filter_var($emailChamp, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Le format de l\'adresse e-mail saisie est invalide.';
    }

    // Contrôle d'unicité (Bonus) : Refuser l'ajout d'un doublon nom + prénom
    if (!$modeEdition && empty($erreurs)) {
        foreach ($baseEtudiants as $existant) {
            if (strcasecmp($existant['nom'], $nomChamp) === 0 && strcasecmp($existant['prenom'], $prenomChamp) === 0) {
                $erreurs[] = 'Un dossier au nom de « ' . h($nomChamp . ' ' . $prenomChamp) . ' » existe déjà dans l\'annuaire.';
                break;
            }
        }
    }

    // --- Gestion du fichier de photo (Exercice 9) ---
    $nomFichierPhoto = '';
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fichierTelecharge = $_FILES['photo_profil'];

        if ($fichierTelecharge['error'] !== UPLOAD_ERR_OK) {
            $erreurs[] = 'Une erreur est survenue lors de la réception du fichier photo.';
        } else {
            $extensionFichier = strtolower(pathinfo($fichierTelecharge['name'], PATHINFO_EXTENSION));
            $extensionsPermises = ['jpg', 'jpeg', 'png'];

            if (!in_array($extensionFichier, $extensionsPermises, true)) {
                $erreurs[] = 'Format de photo non accepté. Seules les extensions JPG, JPEG et PNG sont permises.';
            } elseif ($fichierTelecharge['size'] > 1024 * 1024) {
                $erreurs[] = 'Le poids de l\'image dépasse la taille maximale autorisée (1 Mo).';
            } else {
                // Renommage unique pour éviter les conflits
                $nomFichierPhoto = 'etu_' . uniqid('', true) . '.' . $extensionFichier;
                $destinationFinale = __DIR__ . '/uploads/' . $nomFichierPhoto;

                if (!move_uploaded_file($fichierTelecharge['tmp_name'], $destinationFinale)) {
                    $erreurs[] = 'Échec du transfert du fichier vers le dossier de destination.';
                    $nomFichierPhoto = '';
                }
            }
        }
    }

    // --- Enregistrement si aucune anomalie ---
    if (empty($erreurs)) {
        if ($modeEdition && $idPoste > 0 && isset($baseEtudiants[$idPoste])) {
            // Modification de l'enregistrement existant
            $anciennePhoto = $baseEtudiants[$idPoste]['photo'] ?? '';

            // Remplacement de photo : suppression de l'ancien fichier
            if ($nomFichierPhoto !== '') {
                if ($anciennePhoto !== '' && file_exists(__DIR__ . '/uploads/' . $anciennePhoto)) {
                    @unlink(__DIR__ . '/uploads/' . $anciennePhoto);
                }
                $baseEtudiants[$idPoste]['photo'] = $nomFichierPhoto;
            }

            $baseEtudiants[$idPoste]['nom']     = $nomChamp;
            $baseEtudiants[$idPoste]['prenom']  = $prenomChamp;
            $baseEtudiants[$idPoste]['filiere'] = $filiereChamp;
            $baseEtudiants[$idPoste]['note']    = (float)$noteChamp;
            $baseEtudiants[$idPoste]['email']   = $emailChamp;

            sauver_etudiants($baseEtudiants);
            flash_message('La fiche de l\'étudiant a été modifiée avec succès.');
        } else {
            // Création d'un nouvel enregistrement (nouvel ID = max + 1)
            $identifiants = array_keys($baseEtudiants);
            $nouveauId = !empty($identifiants) ? max($identifiants) + 1 : 1;

            $baseEtudiants[$nouveauId] = [
                'id'      => $nouveauId,
                'nom'     => $nomChamp,
                'prenom'  => $prenomChamp,
                'filiere' => $filiereChamp,
                'note'    => (float)$noteChamp,
                'photo'   => $nomFichierPhoto,
                'email'   => $emailChamp,
            ];

            sauver_etudiants($baseEtudiants);
            flash_message('Le nouvel étudiant a été enregistré avec succès.');
        }

        header('Location: liste.php');
        exit;
    }
}

include 'header.php';
?>

<div class="conteneur-formulaire">
    <div class="navigation-retour">
        <a href="liste.php" class="lien-retour">&larr; Retour à l'annuaire</a>
    </div>

    <div class="carte-formulaire">
        <div class="entete-carte-form">
            <span class="icone-entete-form"><?= $modeEdition ? '✏️' : '🎓' ?></span>
            <div>
                <h2><?= $modeEdition ? 'Modifier l\'étudiant #' . (int)$idUrl : 'Inscrire un nouvel étudiant' ?></h2>
                <p class="sous-titre-form">
                    <?= $modeEdition
                        ? 'Mettez à jour les informations et les notes de l\'étudiant.'
                        : 'Complétez les informations requises pour ajouter un nouvel apprenant.' ?>
                </p>
            </div>
        </div>

        <?php if (!empty($erreurs)): ?>
            <div class="alerte alerte-danger">
                <span class="alerte-icone">⚠️</span>
                <div class="liste-erreurs-wrap">
                    <strong>Veuillez corriger les erreurs suivantes :</strong>
                    <ul>
                        <?php foreach ($erreurs as $msgErreur): ?>
                            <li><?= h($msgErreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST"
              action="ajouter.php<?= $modeEdition ? '?id=' . (int)$idUrl : '' ?>"
              enctype="multipart/form-data"
              class="formulaire-donnees">

            <?php if ($modeEdition): ?>
                <input type="hidden" name="id_etudiant" value="<?= (int)$idUrl ?>">
            <?php endif; ?>

            <div class="grille-deux-champs">
                <div class="champ-groupe">
                    <label for="champ-nom">Nom de famille <span class="etoile-obligatoire">*</span></label>
                    <input type="text"
                           id="champ-nom"
                           name="nom"
                           value="<?= h($nomChamp) ?>"
                           placeholder="ex: Benali"
                           required>
                </div>

                <div class="champ-groupe">
                    <label for="champ-prenom">Prénom <span class="etoile-obligatoire">*</span></label>
                    <input type="text"
                           id="champ-prenom"
                           name="prenom"
                           value="<?= h($prenomChamp) ?>"
                           placeholder="ex: Sara"
                           required>
                </div>
            </div>

            <div class="grille-deux-champs">
                <div class="champ-groupe">
                    <label for="champ-filiere">Filière d'études <span class="etoile-obligatoire">*</span></label>
                    <select id="champ-filiere" name="filiere" required>
                        <option value="">Sélectionnez une filière</option>
                        <?php foreach (FILIERES as $codeFiliere): ?>
                            <option value="<?= h($codeFiliere) ?>"
                                <?= $filiereChamp === $codeFiliere ? 'selected' : '' ?>>
                                <?= h($codeFiliere) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ-groupe">
                    <label for="champ-note">Note obtenue (/20) <span class="etoile-obligatoire">*</span></label>
                    <input type="number"
                           id="champ-note"
                           name="note"
                           value="<?= h($noteChamp) ?>"
                           min="0"
                           max="20"
                           step="0.25"
                           placeholder="ex: 15.5"
                           required>
                </div>
            </div>

            <div class="champ-groupe">
                <label for="champ-email">Adresse e-mail <small class="mention-facultative">(optionnelle)</small></label>
                <input type="email"
                       id="champ-email"
                       name="email"
                       value="<?= h($emailChamp) ?>"
                       placeholder="prenom.nom@universite.ma">
            </div>

            <div class="champ-groupe bloc-upload-photo">
                <label for="champ-photo">Photo de profil <small class="mention-facultative">(JPG, PNG &bull; max 1 Mo)</small></label>

                <?php if ($modeEdition && $photoActuelle !== '' && file_exists(__DIR__ . '/uploads/' . $photoActuelle)): ?>
                    <div class="apercu-photo-existante">
                        <img src="uploads/<?= h($photoActuelle) ?>" alt="Photo actuelle" class="vignette-ronde">
                        <span class="texte-photo-info">Photo enregistrée actuellement (choisissez un nouveau fichier pour la remplacer)</span>
                    </div>
                <?php endif; ?>

                <div class="zone-fichier">
                    <input type="file"
                           id="champ-photo"
                           name="photo_profil"
                           accept=".jpg,.jpeg,.png">
                </div>
            </div>

            <div class="barre-actions-form">
                <button type="submit" class="btn btn-nouveau">
                    <?= $modeEdition ? '💾 Mettre à jour la fiche' : '✓ Confirmer l\'enregistrement' ?>
                </button>
                <a href="liste.php" class="btn btn-annuler">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
