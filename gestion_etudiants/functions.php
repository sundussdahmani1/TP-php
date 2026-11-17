<?php
/**
 * Module des fonctions communes et configuration
 * TP PHP - Gestion des étudiants
 */

// Initialisation de la session utilisateur
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Emplacement du fichier texte contenant la base de données
define('FICHIER', __DIR__ . '/data/etudiants.txt');

// Liste des filières académiques proposées
define('FILIERES', ['GI', 'IDIA', 'GE', 'GC']);

// Comptes autorisés pour l'accès à la plateforme (login => mot de passe)
define('UTILISATEURS', [
    'sunduss'     => 'sunduss123',
    'professeur'  => 'tp2024',
    'admin'       => 'admin123',
]);

/**
 * Sécurise une chaîne contre les attaques XSS
 *
 * @param string $s Chaîne brute
 * @return string Chaîne échappée
 */
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si l'utilisateur courant possède une session active
 *
 * @return bool Vrai s'il est authentifié, faux sinon
 */
function connecte(): bool {
    return isset($_SESSION['utilisateur_connecte']) && !empty($_SESSION['utilisateur_connecte']);
}

/**
 * Bloque l'accès aux pages réservées aux membres connectés
 * Redirige vers la page de connexion le cas échéant
 */
function exiger_connexion(): void {
    if (!connecte()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Assainit les données textuelles saisies par l'utilisateur
 * (retrait des espaces résiduels, points-virgules délimiteurs et sauts de ligne)
 *
 * @param string $s Entrée utilisateur
 * @return string Texte purifié
 */
function nettoyer(string $s): string {
    $s = trim($s);
    return str_replace([';', "\r", "\n"], '', $s);
}

/**
 * Charge tous les étudiants à partir du fichier texte
 *
 * @return array Tableau associatif des étudiants indexé par leur ID
 */
function lire_etudiants(): array {
    $donnees = [];

    if (!file_exists(FICHIER) || filesize(FICHIER) === 0) {
        return $donnees;
    }

    $lignes = file(FICHIER, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lignes === false) {
        return $donnees;
    }

    foreach ($lignes as $ligne) {
        $champs = explode(';', $ligne);
        // id;nom;prenom;filiere;note;photo;email
        if (count($champs) >= 5) {
            $id = (int)$champs[0];
            $donnees[$id] = [
                'id'      => $id,
                'nom'     => $champs[1] ?? '',
                'prenom'  => $champs[2] ?? '',
                'filiere' => $champs[3] ?? '',
                'note'    => isset($champs[4]) && $champs[4] !== '' ? (float)$champs[4] : 0.0,
                'photo'   => $champs[5] ?? '',
                'email'   => $champs[6] ?? '',
            ];
        }
    }

    return $donnees;
}

/**
 * Enregistre l'ensemble des étudiants dans le fichier texte
 *
 * @param array $liste Tableau associatif des étudiants
 */
function sauver_etudiants(array $liste): void {
    $lignes = [];
    foreach ($liste as $item) {
        $lignes[] = implode(';', [
            (int)$item['id'],
            nettoyer((string)$item['nom']),
            nettoyer((string)$item['prenom']),
            nettoyer((string)$item['filiere']),
            (string)$item['note'],
            nettoyer((string)($item['photo'] ?? '')),
            nettoyer((string)($item['email'] ?? '')),
        ]);
    }

    $contenu = !empty($lignes) ? implode("\n", $lignes) . "\n" : '';
    file_put_contents(FICHIER, $contenu, LOCK_EX);
}

/**
 * Enregistre un message flash dans la session
 *
 * @param string $texte Message à afficher
 * @param string $statut 'succes' ou 'danger'
 */
function flash_message(string $texte, string $statut = 'succes'): void {
    $_SESSION['message_flash'] = [
        'texte'  => $texte,
        'statut' => $statut,
    ];
}

/**
 * Récupère le message flash courant et le supprime de la session
 *
 * @return string Code HTML de l'alerte ou chaîne vide
 */
function afficher_message_flash(): string {
    if (!isset($_SESSION['message_flash'])) {
        return '';
    }

    $info = $_SESSION['message_flash'];
    unset($_SESSION['message_flash']);

    $classeAlerte = ($info['statut'] === 'danger') ? 'alerte-danger' : 'alerte-succes';
    $icone = ($info['statut'] === 'danger') ? '⚠️' : '✅';

    return '<div class="alerte ' . $classeAlerte . '">'
         . '<span class="alerte-icone">' . $icone . '</span> '
         . '<span>' . h($info['texte']) . '</span>'
         . '</div>';
}
