<?php
/**
 * En-tête HTML commun
 * Variable attendue : $titre (string)
 */
$themeActif = $_COOKIE['app_theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= h($themeActif) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre) ? h($titre) . ' — Portail Étudiants' : 'Portail Gestion Étudiants' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $themeActif === 'dark' ? 'mode-sombre' : 'mode-clair' ?>">

<header class="barre-navigation">
    <div class="nav-conteneur">
        <a href="liste.php" class="logo-site">
            <span class="logo-symbole">✦</span>
            <span class="logo-nom">Uni<strong>Manager</strong></span>
        </a>

        <?php if (connecte()): ?>
        <nav class="liens-navigation">
            <a href="liste.php" class="nav-item">
                <span class="nav-icone">📑</span> Annuaire
            </a>
            <a href="ajouter.php" class="nav-item">
                <span class="nav-icone">➕</span> Inscription
            </a>
            <a href="export.php" class="nav-item">
                <span class="nav-icone">📥</span> Exporter CSV
            </a>
        </nav>

        <div class="nav-actions">
            <button type="button" id="bascule-theme" class="bouton-theme" aria-label="Basculer thème" title="Changer le mode clair/sombre">
                <span class="icone-theme"><?= $themeActif === 'dark' ? '☀️' : '🌙' ?></span>
            </button>
            <div class="profil-connecte">
                <span class="badge-connecte">En ligne</span>
                <span class="nom-utilisateur"><?= h($_SESSION['utilisateur_connecte'] ?? 'Utilisateur') ?></span>
            </div>
            <a href="logout.php" class="bouton-deconnexion" title="Fermer la session">
                Quitter ⏻
            </a>
        </div>
        <?php endif; ?>
    </div>
</header>

<main class="zone-principale">
