<?php
require_once 'functions.php';
exiger_connexion();

$etudiants = lire_etudiants();

// Génération du nom de fichier avec date et heure
$nomFichierCsv = 'export_etudiants_' . date('Ymd_His') . '.csv';

// En-têtes HTTP pour forcer le téléchargement du fichier CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nomFichierCsv . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Émission du marqueur BOM UTF-8 pour un affichage parfait dans Excel
echo "\xEF\xBB\xBF";

// Flux de sortie direct
$fluxSortie = fopen('php://output', 'w');

// Ligne d'en-tête CSV
fputcsv($fluxSortie, [
    'Identifiant',
    'Nom',
    'Prénom',
    'Filière',
    'Note (/20)',
    'Statut Réussite',
    'Adresse E-mail',
    'Nom Fichier Photo'
], ';');

// Écriture de chaque étudiant
foreach ($etudiants as $item) {
    $statut = ($item['note'] >= 10) ? 'Admis' : 'Ajourné';

    fputcsv($fluxSortie, [
        $item['id'],
        strtoupper($item['nom']),
        ucfirst(strtolower($item['prenom'])),
        $item['filiere'],
        number_format($item['note'], 2, '.', ''),
        $statut,
        $item['email'] ?? '',
        $item['photo'] ?? ''
    ], ';');
}

fclose($fluxSortie);
exit;
