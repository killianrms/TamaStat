<!DOCTYPE html>
<html lang="fr">
<body>
<main>
    <h1>Votre historique</h1>
    <div class="order-history">
        <?php
        /** @var array $historique */

        use App\Modele\Repository\Specifique\ProduitRepository;

        if (!empty($historique)): ?>
            <?php foreach ($historique as $commande): ?>
                <div class="order-item">
                    <table>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Date</th>
                        </tr>
                        <tr>
                            <?php
                            $produit = ProduitRepository::recupererParId($commande->getIdProduit());
                            ?>
                            <td><?= htmlspecialchars($produit->getNom()) ?></td>
                            <td><?= htmlspecialchars($commande->getQuantite()) ?></td>
                            <td><?= htmlspecialchars($commande->getDate()) ?></td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucune commande dans l'historique.</p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
