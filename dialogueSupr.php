<?php

// Le fait d'utiliser prepare(), nous protège contre les injections SQL, c'est à dire, le fait d'insérer à l'intérieur d'une requête normale, une requête malveillante ! 
// Le concept étant de clôturer/faire buguer la requête d'origine pour en inscrire une autre à l'intérieur (de type DELETE qqchoz, DROP TABLE, DROP DATABASE, en fonction des droits que l'on peut avoir)

// Pour éviter les injections XSS (code css, html, js mis dans notre formulaire)
// Il est possible de transformer les caractères spéciaux (les balises notamment) en entités HTML afin qu'ils ne soient pas interprétés ! 
// Les fonctions htmlspecialchars(), htmlentities(), strip_tags(), eventuellement la balise <xmp> nous protègent de ces intrusions 

// Test pour faire nos injections sur le champ message 
// Pour injection SQL : ', ''); DROP DATABASE dialogue;
// Pour injection CSS : <style>body{display:none;}</style>
// Pour injection JS : <script>while(true){alert("Achetez notre antivirus");}</script>





/* 

EXERCICE :
---------------

- Création d'un espace de tchat en ligne 

- 01 - Création de la BDD : dialogue 
        - Table : commentaire 
        - Champs de la table commentaire : 
            - id_commentaire       INT(5) PRIMARY KEY - AUTO INCREMENT
            - pseudo               VARCHAR 255 
            - message              TEXT
            - date_enregistrement  DATETIME (DATETIME =>  jour + heure)

- 02 - Créer une connexion à cette base avec PDO (c'est à dire, bien instancier le new PDO)
- 03 - Création d'un formulaire permettant de poster un message (HTML => POST)
        - Champs du formulaire :
                - pseudo (input type text)
                - message (textarea)
                - bouton de validation  
- 04 - Récupération des saisies du form avec contrôle ($_POST, on applique des contrôles (voir chapitre POST, forminscription))
- 05 - Déclenchement d'une requête d'enregistrement pour enregistrer les saisies dans la BDD (INSERT INTO)
- 06 - Requête de récupération des messages pour les afficher dans cette page  (SELECT)
- 07 - Affichage des commentaires avec un peu de mise en forme 
- 08 - Affichage en haut des messages du nombre de messages présents dans la BDD 
- 09 - Affichage de la date en français 
- 10 - Amélioration du css 

*/

// 02 - Créer une connexion à cette base avec PDO 
$host = "mysql:host=localhost;dbname=dialogue"; // hôte + bdd
$login = "root"; // login
$password = ""; // mot de passe
$options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING, // gestion des erreurs
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8' // on force l'utf8 sur les données provenants de la bdd
);

// Création de l'objet PDO :
$pdo = new PDO($host, $login, $password, $options);

// echo "<pre>";
// print_r($_POST);
// echo "</pre>";
// - 04 - Récupération des saisies du form avec contrôle ($_POST, on applique des contrôles (voir chapitre POST, forminscription))

$req = "";
$msgSystem = "";

if (isset($_POST["pseudo"], $_POST["message"])) {

        $pseudo = trim($_POST["pseudo"]);
        $message = trim($_POST["message"]);

        $erreur = false;

        // Contrôle que l'on pourrait appliquer
        // empty sur les deux champs (les deux champs sont obligatoires)
        // taille du pseudo (pas trop long, pas trop court)
        // taille du message (pas trop long, pas trop court)

        if ($erreur == false) {
                // - 05 - Déclenchement d'une requête d'enregistrement pour enregistrer les saisies dans la BDD (INSERT INTO)

                $req = "INSERT INTO commentaire (pseudo, message, date_enregistrement) VALUES ('$pseudo', '$message', NOW())";
                // $reponse = $pdo->query($req);

                // Pour éviter les injections SQL, il faut absolument utiliser prepare(), car $pseudo et $message viennent de la saisie de l'utilisateur et pourraient contenir du code malveillant 

                // Avec prepare on se protège (au moins) des injections SQL 

                // Je prépare la requête
                $reponse = $pdo->prepare("INSERT INTO commentaire (pseudo, message, date_enregistrement) VALUES (:pseudo, :message, NOW())");

                // // Je bind les valeurs aux marqueurs
                $reponse->bindParam(':pseudo', $pseudo, PDO::PARAM_STR);
                $reponse->bindParam(':message', $message, PDO::PARAM_STR);

                // // J'exécute la requête
                $reponse->execute();
        }
}

if (isset($_GET["action"], $_GET["idmsg"]) && $_GET["action"] == "delete") {
        //         // requête de suppression de l'élément récupéré via GET 
        //         // DELETE FROM commentaire etc......

        // echo "<pre>";
        // print_r($_GET);
        // echo "</pre>";

        $idMsg = $_GET["idmsg"];

        $reponse = $pdo->prepare("DELETE FROM commentaire WHERE id_commentaire = :idMsg");
        $reponse->bindParam(":idMsg",  $idMsg, PDO::PARAM_STR);
        $reponse->execute();

        header("location:dialogueSupr.php?msg=suprOk");
}

if (isset($_GET["msg"]) && $_GET["msg"] == "suprOk") {
        $msgSystem .= '<div class="alert alert-info" role="alert">
        Le message a bien été supprimé !
      </div>';
}


// - 06 - Requête de récupération des messages pour les afficher dans cette page  (SELECT)
//  09 Formatage de la date en français grâce à la fonction MySQL date_format 
$liste_commentaire = $pdo->query("SELECT id_commentaire, pseudo, message, date_format(date_enregistrement, '%d/%m/%Y à %H:%i:%s') AS date_fr FROM commentaire ORDER BY date_enregistrement DESC");

// Le fetchAll et le print_r me permettent de vérifier que ma requête de selection fonctionne bien
// $commentaires = $liste_commentaire->fetchAll(PDO::FETCH_ASSOC);
// echo "<pre>";
// print_r($commentaires);
// echo "</pre>";

?>
<!DOCTYPE html>
<html lang="fr">

<head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">

        <!-- Google font -->
        <link rel="preconnect" href="https://fonts.gstatic.com">
        <!-- Playfair display -->
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
        <!-- Roboto -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap" rel="stylesheet">

        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <style>
                * {
                        font-family: 'Roboto', sans-serif;
                }

                h1,
                h2,
                h3,
                h4,
                h5,
                h6 {
                        font-family: 'Playfair Display', serif;
                }
        </style>

        <title>Dialogue</title>
</head>

<body class="bg-secondary">
        <div class="container bg-light g-0">
                <div class='row '>
                        <div class="col-12">
                                <h2 class="text-center text-dark fs-1 bg-light p-5 border-bottom"><i class="far fa-comments"></i> Espace de dialogue <i class="far fa-comments"></i></h2>
                                <!-- Etape 3 - Creation du form HTML -->
                                <form action="dialogueSupr.php" method="post" class="mt-5 mx-auto w-50 border p-3 bg-white">

                                        <?= $req; // on affiche la requete pour voir les injections SQL 
                                        ?>
                                        <?= $msgSystem; // on affiche la requete pour voir les injections SQL 
                                        ?>


                                        <hr>
                                        <div class="mb-3">
                                                <label for="pseudo" class="form-label">Pseudo <i class="fas fa-user-alt"></i></label>
                                                <input type="text" class="form-control" id="pseudo" name="pseudo">
                                        </div>
                                        <div class="mb-3">
                                                <label for="message" class="form-label">Message <i class="fas fa-feather-alt"></i></label>
                                                <textarea class="form-control" id="message" name="message"></textarea>
                                        </div>
                                        <div class="mb-3">
                                                <hr>
                                                <button type="submit" class="btn btn-secondary w-100" id="enregistrer" name="enregistrer"><i class="fas fa-keyboard"></i> Enregistrer <i class="fas fa-keyboard"></i></button>
                                        </div>
                                </form>
                        </div>
                </div>
                <div class='row mt-5'>
                        <div class="col-12">
                                <!-- Affichage des commentaire -->
                                <p class="w-75 mx-auto mb-3"><?php
                                                                // - 08 - Affichage en haut des messages du nombre de message présent dans la bdd
                                                                echo 'il y a : <b>' . $liste_commentaire->rowCount() . '</b> messages';
                                                                ?></p>
                                <?php
                                // - 07 - Affichage des commentaire avec un peu mise en forme
                                while ($commentaire = $liste_commentaire->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<div class="card w-75 mx-auto mb-3">
                                    <div class="card-header bg-dark text-white d-flex justify-content-between">
                                        Par : ' . htmlspecialchars($commentaire['pseudo']) . ', le : ' . $commentaire['date_fr'] . '
                                     - #'  . $commentaire["id_commentaire"] .  '<div class="d-grid gap-2 d-md-flex justify-content-md-end"><button type="button" class="btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#Modal' . $commentaire["id_commentaire"] . '"><i class="fa-solid fa-xmark"></i></button></div></div>
                                    <div class="card-body">
                                        <p class="card-text">' . htmlspecialchars($commentaire['message']) . '</p>
                                    </div>
                                </div>'; ?>

                                        <!-- Modal -->
                                        <div class="modal fade" id="Modal<?= $commentaire["id_commentaire"] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                        <div class="modal-content">
                                                                <div class="modal-header">
                                                                        <h5 class="modal-title" id="exampleModalLabel">Suppression</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                        Êtes vous sûr de vouloir supprimer le message #<?= $commentaire["id_commentaire"] ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                        <a class="btn btn-danger" href="?action=delete&idmsg=<?= $commentaire["id_commentaire"] ?>" role="button">Suppression</a>
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>
                                <?php
                                        // Dans cette boucle, je veux créer un lien dynamique me permettant d'envoyer dans le GET, les informations nécessaires (l'id du commentaire en cours) pour lancer ma suppression
                                }


                                ?>

                        </div>
                </div>
        </div>


        <!-- Option 1: Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>
</body>

</html>

<?php

// EXERCICE 
// Evolution de notre système, on aimerait pouvoir supprimer un message en cliquant sur un bouton de suppression lié à chaque message 

// Pour ça, on a modifié notre requête de selection des messages, en récupérant également l'id_commentaire 

// Le but étant ici de manipuler nos connaissances de $_GET, pour récupérer cette information là et lancer une requête de suppression 

// 01 - Modification de notre requête SELECT pour récupérer les id_commentaire (déjà fait)
// 02 - A chaque tour de boucle while pour l'affichage de nos messages, on crée un bouton/lien de suppression qui envoie en information GET, à la fois une info de type "action=delete" et également l'id_commentaire du commentaire en cours 
// 03 - Récupération des informations envoyées par le GET (print_r de GET pour visualiser que j'ai bien les infos)
// 04 - Déclenchement d'une requête de suppression de ce message cliqué 

?>