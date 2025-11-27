<?php
// Scan du dossier JSON pour trouver le fichier le plus récent automatiquement
$jsonDir = 'json/';
$latestProject = 'exemple.json'; // Fallback par défaut
$projectToLoad = '';

if (is_dir($jsonDir)) {
    $files = glob($jsonDir . '*.json');
    
    if ($files && count($files) > 0) {
        // On trie simplement par date de modification (le plus récent en premier)
        // On NE FILTRE PLUS 'exemple.json' : si c'est le dernier modifié, c'est celui qu'on veut !
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // On prend le premier de la liste (le plus frais)
        if (!empty($files)) {
            $latestProject = basename($files[0]);
        }
    }
}

// Si un paramètre URL existe, il est prioritaire
if (isset($_GET['projet']) && !empty($_GET['projet'])) {
    $projectToLoad = $_GET['projet'];
} else {
    $projectToLoad = $latestProject;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StorySwitch - Lecteur</title>
    <!-- CACHE BUSTING SUR LE CSS -->
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
    
    <!-- Injection de la variable PHP vers JS -->
    <script>
        const AUTO_LOAD_PROJECT = "<?php echo htmlspecialchars($projectToLoad); ?>";
        console.log("PHP a décidé de charger : " + AUTO_LOAD_PROJECT);
    </script>
</head>

<body>
    <div class="black-overlay"><button class="begin">Commencer l'aventure</button></div>
    
    <!-- Zone de fin (écran noir) -->
    <div class="black-end hide-end">Merci d'avoir joué !</div>
    
    <!-- Zone d'erreur (Rouge) pour debugger -->
    <div id="error-overlay" style="display:none; position:fixed; inset:0; background:rgba(50,0,0,0.9); color:white; z-index:9999; justify-content:center; align-items:center; text-align:center; padding:2rem;">
        <div>
            <h2 style="font-size:2rem; margin-bottom:1rem;">⚠️ Lien Cassé</h2>
            <p id="error-message" style="font-size:1.2rem;"></p>
            <button onclick="document.getElementById('error-overlay').style.display='none'" style="margin-top:20px; padding:10px 20px; cursor:pointer;">Fermer</button>
        </div>
    </div>

    <div class="video-container" id="video-container">
        
        <div class="button-overlay">
            <button class="pause-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>
            </button>
            <button id="fullscreen-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>
            </button>
        </div>

        <video id="url_zone" width="768" height="432" controls playsinline></video>

        <div class="choice"></div>

        <div class="timer-border" id="timer-top"></div>
        <div class="timer-border" id="timer-right"></div>
        <div class="timer-border" id="timer-bottom"></div>
        <div class="timer-border" id="timer-left"></div>

    </div>

    <!-- CACHE BUSTING SUR LE JS : Le ?v=time() force le rechargement -->
    <script src="js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>