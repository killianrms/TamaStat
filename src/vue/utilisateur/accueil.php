<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Les rêves ambrés</title>
</head>
<body>

<main>
    <h1>Bienvenue sur Les rêves ambrés</h1>
    <p>Nous proposons une variété de parfums de haute qualité.</p>

    <section>
        <h2>Nos parfums</h2>
        <div class="product-grid">
            <?php
            /** @var Produit[] $produits */

            use App\Lib\ConnexionUtilisateur;
            use App\Modele\DataObject\Produit;
            use App\Modele\Repository\Specifique\UtilisateurRepository;

            $estAdmin = false;
            if (ConnexionUtilisateur::estConnecte()) {
                $utilisateur = UtilisateurRepository::recupererUtilisateurParEmail(ConnexionUtilisateur::getemailUtilisateurConnecte());
                $estAdmin = $utilisateur->getEstAdminBool();
            }


            foreach ($produits as $produit) {
                echo $produit->__toString();
                if ($estAdmin) {
                    echo '<a href="controleurFrontal.php?action=afficherSupprimer&id=' . rawurlencode($produit->getId()) . '" class="btn delete-btn">Supprimer</a>';
                    echo '<a href="controleurFrontal.php?action=afficherFormulaireMiseAJour&id=' . rawurlencode($produit->getId()) . '" class="btn edit-btn">Éditer</a>';
                }
                echo '<a href="controleurFrontal.php?action=ajouterAuPanier&id=' . rawurlencode($produit->getId()) . '" class="btn add-to-cart-btn">Ajouter au panier</a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            if ($estAdmin) {
                echo '<a href="controleurFrontal.php?action=afficherFormulaireCreation" class="btn add-btn">Ajouter un produit</a>';
            }
            ?>
        </div>
    </section>
</main>
</body>
</html>
