/**
 * script.js - Scripts d'interaction côté client
 * TP PHP - Gestion des étudiants
 */

document.addEventListener('DOMContentLoaded', () => {

    /* ===================================================
       1. Demande de confirmation avant suppression
       =================================================== */
    const liensSuppression = document.querySelectorAll('.lien-suppression');

    liensSuppression.forEach((lien) => {
        lien.addEventListener('click', (evenement) => {
            const nomEtudiant = lien.getAttribute('data-nom') || 'cet étudiant';
            const confirmation = confirm(`Êtes-vous sûr de vouloir supprimer définitivement ${nomEtudiant} ?\nCette opération est irréversible.`);

            if (!confirmation) {
                evenement.preventDefault();
            }
        });
    });

    /* ===================================================
       2. Recherche instantanée en direct (Live Search)
       =================================================== */
    const champRecherche = document.getElementById('champ-recherche-live');
    const tableAnnuaire = document.getElementById('table-annuaire');

    if (champRecherche && tableAnnuaire) {
        champRecherche.addEventListener('input', () => {
            const saisie = champRecherche.value.trim().toLowerCase();
            const corpsTable = tableAnnuaire.querySelector('tbody');
            const lignesEtudiants = corpsTable.querySelectorAll('tr.ligne-etudiant');

            let nombreCorrespondances = 0;

            lignesEtudiants.forEach((ligne) => {
                const celluleNom = ligne.querySelector('.cellule-nom');
                const cellulePrenom = ligne.querySelector('.cellule-prenom');
                const celluleEmail = ligne.querySelector('.cellule-email');

                const texteNom = celluleNom ? celluleNom.textContent.toLowerCase() : '';
                const textePrenom = cellulePrenom ? cellulePrenom.textContent.toLowerCase() : '';
                const texteEmail = celluleEmail ? celluleEmail.textContent.toLowerCase() : '';

                const correspond = (saisie === '')
                    || texteNom.includes(saisie)
                    || textePrenom.includes(saisie)
                    || texteEmail.includes(saisie);

                if (correspond) {
                    ligne.style.display = '';
                    nombreCorrespondances++;
                } else {
                    ligne.style.display = 'none';
                }
            });

            // Affichage d'un message dynamique si aucun résultat n'est trouvé
            let ligneResultatVide = corpsTable.querySelector('.ligne-aucun-resultat-js');

            if (nombreCorrespondances === 0 && saisie !== '') {
                if (!ligneResultatVide) {
                    ligneResultatVide = document.createElement('tr');
                    ligneResultatVide.className = 'ligne-aucun-resultat-js';
                    ligneResultatVide.innerHTML = `
                        <td colspan="7" style="text-align:center; padding: 2.5rem; color: var(--texte-secondaire);">
                            <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">🔍</div>
                            <strong>Aucun résultat trouvé pour « ${saisie} »</strong>
                            <p style="font-size: 0.85rem; margin-top: 0.25rem;">Essayez un autre nom ou prénom.</p>
                        </td>
                    `;
                    corpsTable.appendChild(ligneResultatVide);
                }
            } else if (ligneResultatVide) {
                ligneResultatVide.remove();
            }
        });
    }

    /* ===================================================
       3. Gestion du thème Sombre / Clair (Cookie & DOM)
       =================================================== */
    const boutonTheme = document.getElementById('bascule-theme');

    const recupererCookie = (nom) => {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const [cle, val] = cookies[i].trim().split('=');
            if (cle === nom) return decodeURIComponent(val);
        }
        return null;
    };

    const enregistrerCookie = (nom, valeur, jours = 365) => {
        const expiration = new Date();
        expiration.setTime(expiration.getTime() + (jours * 24 * 60 * 60 * 1000));
        document.cookie = `${nom}=${encodeURIComponent(valeur)};expires=${expiration.toUTCString()};path=/;SameSite=Lax`;
    };

    if (boutonTheme) {
        boutonTheme.addEventListener('click', () => {
            const documentElement = document.documentElement;
            const themeActuel = documentElement.getAttribute('data-theme') || 'light';
            const nouveauTheme = themeActuel === 'dark' ? 'light' : 'dark';

            documentElement.setAttribute('data-theme', nouveauTheme);
            document.body.className = (nouveauTheme === 'dark') ? 'mode-sombre' : 'mode-clair';

            const iconeTheme = boutonTheme.querySelector('.icone-theme');
            if (iconeTheme) {
                iconeTheme.textContent = (nouveauTheme === 'dark') ? '☀️' : '🌙';
            }

            enregistrerCookie('app_theme', nouveauTheme, 365);
        });
    }

});
