<?php
require_once 'functions.php';
exiger_connexion();

// Récupération et conversion stricte de l'identifiant en entier
$idCible = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$etudiants = lire_etudiants();

if ($idCible > 0 && isset($etudiants[$idCible])) {
    $etudiantASupprimer = $etudiants[$idCible];

    // Suppression physique du fichier photo si existant
    $fichierPhoto = $etudiantASupprimer['photo'] ?? '';
    if ($fichierPhoto !== '') {
        $cheminPhoto = __DIR__ . '/uploads/' . $fichierPhoto;
        if (file_exists($cheminPhoto)) {
            @unlink($cheminPhoto);
        }
    }

    // Retrait de l'étudiant de la collection
    unset($etudiants[$idCible]);

    // Réécriture du fichier de persistance
    sauver_etudiants($etudiants);

    $nomComplet = ucfirst($etudiantASupprimer['prenom']) . ' ' . strtoupper($etudiantASupprimer['nom']);
    flash_message("L'étudiant « $nomComplet » a été retiré de la base de données avec succès.", 'succes');
} else {
    flash_message("L'étudiant demandé n'a pas été trouvé ou a déjà été supprimé.", 'danger');
}

// Redirection immédiate vers la liste
header('Location: liste.php');
exit;
