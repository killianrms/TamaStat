<?php
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<body>
<main>
    <h1>Votre panier</h1>
    <div class="cart">
        <?php if (!empty($_SESSION['panier'])): ?>
            <?php
            /** @var array $panier */
            foreach ($panier as $index => $produit): ?>
                <div class="cart-item">
                    <table>
                        <tr>
                            <th>Produit</th>
                            <th>Quantité</th>
                            <th>Prix</th>
                        </tr>
                        <tr>
                            <td><?= htmlspecialchars($produit['nom']) ?></td>
                            <td><?= htmlspecialchars($produit['quantite']) ?></td>
                            <td><?= htmlspecialchars($produit['prix']*$produit['quantite']) ?> €</td>
                        </tr>
                    </table>
                    <form method="POST" action="controleurFrontal.php?action=payer&idProduit=<?= $produit['id'] ?>&quantite=<?= $produit['quantite'] ?>">
                        <input type="hidden" name="payer" value="<?= $index ?>">
                        <button type="submit" class="pay-button">Payer cette commande</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Votre panier est vide.</p>
        <?php endif; ?>
    </div>
</main>

</body>

</html>
