<?php
require_once 'functions.php';
exiger_connexion();

$titre = 'Liste des étudiants';

// Récupération de l'ensemble des données
$annuaire = lire_etudiants();

// --- 1. Filtrage / Recherche (GET q) ---
$rechercheTexte = trim($_GET['q'] ?? '');
if ($rechercheTexte !== '') {
    $annuaire = array_filter($annuaire, function(array $etudiant) use ($rechercheTexte): bool {
        return (stripos($etudiant['nom'], $rechercheTexte) !== false)
            || (stripos($etudiant['prenom'], $rechercheTexte) !== false);
    });
}

// --- 2. Tri des enregistrements (GET tri) ---
$optionsTriAutorisees = ['id', 'nom', 'filiere', 'note'];
$critereTri = isset($_GET['tri']) && in_array($_GET['tri'], $optionsTriAutorisees, true)
    ? $_GET['tri']
    : 'id';

usort($annuaire, function(array $a, array $b) use ($critereTri): int {
    if ($critereTri === 'nom') {
        return strcasecmp($a['nom'], $b['nom']);
    }
    if ($critereTri === 'filiere') {
        return strcasecmp($a['filiere'], $b['filiere']);
    }
    if ($critereTri === 'note') {
        // Tri décroissant pour les notes (de la plus haute à la plus basse)
        return $b['note'] <=> $a['note'];
    }
    // Ordre par défaut : ordre d'ajout (id croissant)
    return $a['id'] <=> $b['id'];
});

// --- 3. Calcul des statistiques ---
$nombreEtudiants = count($annuaire);
$serieNotes = array_column($annuaire, 'note');

if ($nombreEtudiants > 0) {
    $moyenneNotes = number_format(array_sum($serieNotes) / $nombreEtudiants, 2);
    $meilleureNote = max($serieNotes);
} else {
    $moyenneNotes = '0.00';
    $meilleureNote = 0;
}

// Bonus : Moyenne par filière
$notesParFiliere = [];
foreach ($annuaire as $etu) {
    $notesParFiliere[$etu['filiere']][] = $etu['note'];
}

include 'header.php';
?>

<!-- Affichage du message flash de session -->
<?= afficher_message_flash() ?>

<section class="section-titre">
    <div>
        <h1 class="titre-principal">Annuaire des Étudiants</h1>
        <p class="description-section">Consultez, recherchez et gérez les fiches académiques des étudiants inscrits.</p>
    </div>
    <div class="actions-haut">
        <a href="ajouter.php" class="btn btn-nouveau">
            <span>➕</span> Ajouter un étudiant
        </a>
    </div>
</section>

<!-- Barre de statistiques clés -->
<div class="panneau-statistiques">
    <div class="carte-stat">
        <span class="stat-libelle">Inscrits répertoriés</span>
        <span class="stat-valeur stat-nombre"><?= $nombreEtudiants ?></span>
    </div>
    <div class="carte-stat">
        <span class="stat-libelle">Moyenne de la sélection</span>
        <span class="stat-valeur stat-moyenne"><?= $moyenneNotes ?> <small>/20</small></span>
    </div>
    <div class="carte-stat">
        <span class="stat-libelle">Meilleure note obtenue</span>
        <span class="stat-valeur stat-top"><?= $meilleureNote ?> <small>/20</small></span>
    </div>
</div>

<!-- Moyenne par filière (Bonus) -->
<?php if (!empty($notesParFiliere)): ?>
<div class="bloc-filieres">
    <span class="titre-filieres-bloc">Moyennes par filière :</span>
    <div class="liste-pilules-filieres">
        <?php foreach ($notesParFiliere as $filiereCode => $notesFiliere): ?>
            <?php
            $moyFiliere = number_format(array_sum($notesFiliere) / count($notesFiliere), 2);
            ?>
            <span class="pilule-filiere">
                <span class="pilule-nom"><?= h($filiereCode) ?></span>
                <span class="pilule-moyenne"><?= $moyFiliere ?>/20</span>
                <span class="pilule-count">(<?= count($notesFiliere) ?>)</span>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Formulaire de recherche et de tri -->
<div class="boite-recherche-filtres">
    <form method="GET" action="liste.php" class="formulaire-filtres">
        <div class="groupe-filtre champ-croissant">
            <label for="champ-recherche-live" class="label-cache">Rechercher</label>
            <input type="text"
                   name="q"
                   id="champ-recherche-live"
                   value="<?= h($rechercheTexte) ?>"
                   placeholder="Tapez un nom ou prénom pour filtrer..."
                   autocomplete="off">
        </div>

        <div class="groupe-filtre">
            <label for="selecteur-tri">Trier par :</label>
            <select name="tri" id="selecteur-tri">
                <option value="id"      <?= $critereTri === 'id'      ? 'selected' : '' ?>>Ordre d'enregistrement</option>
                <option value="nom"     <?= $critereTri === 'nom'     ? 'selected' : '' ?>>Nom alphabétique</option>
                <option value="filiere" <?= $critereTri === 'filiere' ? 'selected' : '' ?>>Filière d'étude</option>
                <option value="note"    <?= $critereTri === 'note'    ? 'selected' : '' ?>>Note la plus élevée</option>
            </select>
        </div>

        <div class="boutons-filtres">
            <button type="submit" class="btn btn-filtrer">Appliquer</button>
            <?php if ($rechercheTexte !== '' || $critereTri !== 'id'): ?>
                <a href="liste.php" class="btn btn-reinitialiser" title="Effacer les filtres">✕ Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tableau principal des données -->
<div class="grille-tableau-conteneur">
    <table class="tableau-etudiants" id="table-annuaire">
        <thead>
            <tr>
                <th class="col-visage">Photo</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Filière</th>
                <th>Note</th>
                <th>Contact Email</th>
                <th class="col-options">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($annuaire)): ?>
            <tr class="ligne-vide">
                <td colspan="7">
                    <div class="etat-vide">
                        <span class="icone-vide">📂</span>
                        <p>Aucun étudiant</p>
                        <small>Aucune donnée ne correspond aux critères de sélection.</small>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($annuaire as $etudiant): ?>
                <?php
                // Détermination de la classe de la note via l'opérateur ternaire
                $classeStatutNote = ($etudiant['note'] >= 10) ? 'note-reussie' : 'note-echec';
                $nomMajuscules = strtoupper($etudiant['nom']);
                $prenomCapitalise = ucfirst(strtolower($etudiant['prenom']));
                $photoFichier = $etudiant['photo'];
                $cheminCompletPhoto = __DIR__ . '/uploads/' . $photoFichier;
                ?>
                <tr class="ligne-etudiant">
                    <td class="col-visage">
                        <?php if ($photoFichier !== '' && file_exists($cheminCompletPhoto)): ?>
                            <img src="uploads/<?= h($photoFichier) ?>"
                                 alt="Photo de <?= h($prenomCapitalise) ?>"
                                 class="vignette-ronde">
                        <?php else: ?>
                            <div class="avatar-initiale" title="Aucune photo disponible">
                                <?= h(mb_strtoupper(mb_substr($prenomCapitalise, 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="cellule-nom">
                        <strong><?= h($nomMajuscules) ?></strong>
                    </td>
                    <td class="cellule-prenom">
                        <?= h($prenomCapitalise) ?>
                    </td>
                    <td>
                        <span class="pastille-filiere pastille-<?= strtolower(h($etudiant['filiere'])) ?>">
                            <?= h($etudiant['filiere']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="pastille-note <?= $classeStatutNote ?>">
                            <?= h((string)$etudiant['note']) ?> / 20
                        </span>
                    </td>
                    <td class="cellule-email">
                        <?php if (!empty($etudiant['email'])): ?>
                            <a href="mailto:<?= h($etudiant['email']) ?>" class="lien-email">
                                <?= h($etudiant['email']) ?>
                            </a>
                        <?php else: ?>
                            <span class="texte-attente">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-options">
                        <div class="groupe-boutons-action">
                            <a href="ajouter.php?id=<?= (int)$etudiant['id'] ?>"
                               class="btn-icone btn-editer"
                               title="Modifier cet étudiant">
                                ✎
                            </a>
                            <a href="supprimer.php?id=<?= (int)$etudiant['id'] ?>"
                               class="btn-icone btn-supprimer lien-suppression"
                               data-nom="<?= h($prenomCapitalise . ' ' . $nomMajuscules) ?>"
                               title="Supprimer cet étudiant">
                                🗑
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
