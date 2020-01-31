<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Commandez nos produits !</title>
</head>

<body>
    <h1>Commandez nos produits !</h1>

    <form action="" method="post">
        <select required name="product" id="product">
            <option value="">Choisissez un produit</option>
            <option value="chaise">Chaise</option>
            <option value="table">Table</option>
            <option value="meuble">Meuble</option>
        </select>
        <input required type="number" name="quantity" id="quantity" placeholder="Quantité voulue ?" step="1" min="1" max="10">
        <input required type="email" name="email" id="email" placeholder="Votre adresse email ?">
        <input required type="text" name="phone" id="phone" placeholder="Votre numéro de téléphone">
        <button type="submit">Passez la commande !</button>
    </form>
</body>

</html>