<?php
// Définir le dossier de sortie
$outputFolder = __DIR__ . "/restaurants_json";
$zipFilePath = __DIR__ . "/restaurants_json.zip";

// Créer le dossier s'il n'existe pas
if (!file_exists($outputFolder)) {
    mkdir($outputFolder, 0777, true);
}

// URL de base pour les appels API
$baseUrl = "https://ws.mcdonalds.fr/api/restaurant/";

// Configuration initiale
$delay = isset($_POST['delay']) ? intval($_POST['delay']) : 0;
$startId = isset($_POST['start_id']) ? intval($_POST['start_id']) : null;
$endId = isset($_POST['end_id']) ? intval($_POST['end_id']) : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récupération des Restaurants McDonald's</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f2f2f2;}
        h1 { color: #333; background: #FFC107; padding: 10px; width: max-content; border-radius: 10px; text-align: center; margin: 20px auto;}
        form {text-align: center; background-color: #264f36; width: max-content; color: white; padding: 10px; border-radius: 10px; margin: 0px auto;}
        .success { color: green; }
        .error { color: red; }
        .alert { color: orange; }
        .download-link { margin-top: 20px; }
        .download-link a { color: white; background: #264f36; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
        .download-link a:hover { background: #3e8359; }
        .comment {text-align: center; color: #264f36; margin: 10px auto 20px;}
    </style>
</head>
<body>
    <h1>Récupération des Restaurants McDonald's</h1>

    <!-- Formulaire pour choisir les IDs -->
    <form method="POST">
        <label for="start_id">ID de départ :</label>
        <select name="start_id" id="start_id" required>
            <?php for ($i = 1; $i <= 1500; $i += 50): ?>
                <option value="<?= $i; ?>"><?= $i; ?></option>
            <?php endfor; ?>
        </select>

        <label for="end_id">ID de fin :</label>
        <select name="end_id" id="end_id" required>
            <?php for ($i = 50; $i <= 1500; $i += 50): ?>
                <option value="<?= $i; ?>"><?= $i; ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit">Valider</button>
    </form>

    <?php
    if ($startId !== null && $endId !== null && $startId <= $endId) {
        echo "<h3 class='comment'>Traitement des restaurants de l'ID $startId à $endId</h3>";

        // Boucle sur les ID des restaurants
        for ($restaurantId = $startId; $restaurantId <= $endId; $restaurantId++) {
            $url = $baseUrl . $restaurantId . "/";
            $outputFile = $outputFolder . "/restaurant_$restaurantId.json";

            try {
                // Récupérer le JSON via file_get_contents
                $jsonData = @file_get_contents($url);

                if ($jsonData !== false) {
                    // Décoder les données JSON pour accéder au "name"
                    $restaurantData = json_decode($jsonData, true);

                    if (isset($restaurantData['name'])) {
                        $restaurantName = $restaurantData['name'];
                        file_put_contents($outputFile, $jsonData);
                        echo "<div class='success'>✔️ ID $restaurantId : $restaurantName</div>";
                    } else {
                        echo "<div class='error'>❌ ID $restaurantId : Données invalides ou 'name' absent.</div>";
                    }
                } else {
                    echo "<div class='error'>❌ ID $restaurantId : Restaurant non trouvé (404).</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert'>❗ ID $restaurantId : Erreur - " . $e->getMessage() . "</div>";
            }

            usleep($delay);
        }

        // Création de l'archive ZIP
        echo "<h3>Création de l'archive ZIP...</h3>";
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($outputFolder),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($outputFolder) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            echo "<div class='success'>✔️ Archive ZIP créée avec succès.</div>";
            echo "<div class='download-link'><a href='restaurants_json.zip' download>Télécharger l'archive ZIP</a></div>";

    // Définir le dossier où les JSON sont stockés
$outputFolder = __DIR__ . "/restaurants_json";
$csvFilePath = __DIR__ . "/restaurants.csv";

// Ouvrir le fichier CSV en mode écriture
$csvFile = fopen($csvFilePath, 'w');

// Ajouter l'en-tête du CSV
$headers = [
    'id', 'codepostal', 'adresse2', 'adresse1', 'Adresse 1 clean', 'ville', 'region', 'longitude', 'latitude', 'nom'
];
fputcsv($csvFile, $headers);

// Parcourir les fichiers JSON dans le dossier
$files = glob($outputFolder . "/*.json");

foreach ($files as $file) {
    $jsonData = file_get_contents($file);
    $restaurantData = json_decode($jsonData, true);

    if (isset($restaurantData['restaurantAddress'][0])) {
        $address = $restaurantData['restaurantAddress'][0];
        
        // Extraction des informations
        $id = $restaurantData['ref'] ?? 'N/A';
        $codePostal = $address['zipCode'] ?? 'N/A';
        $adresse2 = $address['address2'] ?? 'N/A';
        $adresse1 = $address['address1'] ?? 'N/A';
        $adresse1Clean = cleanAddress($adresse1);
        $ville = $address['city'] ?? 'N/A';
        $region = $restaurantData['region'] ?? 'N/A';
        $longitude = $restaurantData['coordinates']['longitude'] ?? 'N/A';
        $latitude = $restaurantData['coordinates']['latitude'] ?? 'N/A';
        $nom = $restaurantData['name'] ?? 'N/A';

        // Enregistrer les données dans le CSV
        $row = [
            $id, $codePostal, $adresse2, $adresse1, $adresse1Clean, $ville, $region, $longitude, $latitude, $nom
        ];
        fputcsv($csvFile, $row);
    }
}

// Fermer le fichier CSV
fclose($csvFile);

echo "<h3>Création du fichier CSV...</h3>
<div class='success'>✔️ Fichier CSV créé avec succès.</div>
<div class='download-link'>
    <a class='download-link' href='restaurants.csv' download>Télécharger le CSV</a>
</div>";
} else {
    echo "<div class='error'>❌ Erreur lors de la création de l'archive ZIP.</div>";
}
}
// Fonction pour nettoyer l'adresse (par exemple, en supprimant les caractères inutiles)
function cleanAddress($address) {
    // Supprimer les espaces multiples et les caractères spéciaux
    $cleaned = preg_replace('/\s+/', ' ', $address); // Remplacer les espaces multiples par un seul espace
    $cleaned = trim($cleaned); // Retirer les espaces au début et à la fin
    return $cleaned;
}
    ?>
</body>
</html>
