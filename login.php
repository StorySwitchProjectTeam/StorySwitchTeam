<?php
session_start();

// Si déjà connecté, on envoie direct au créateur
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: creation.php');
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputKey = $_POST['access_key'] ?? '';
    
    // On charge le fichier généré par le .sh
    $secretFile = 'secret.json';
    
    if (file_exists($secretFile)) {
        $json = json_decode(file_get_contents($secretFile), true);
        $storedHash = $json['hash'] ?? '';

        // On compare ce que l'utilisateur a tapé avec le hash du fichier
        if (password_verify($inputKey, $storedHash)) {
            $_SESSION['logged_in'] = true;
            header('Location: creation.php');
            exit;
        } else {
            $error = "Clé d'authentification incorrecte.";
        }
    } else {
        $error = "Erreur configuration : Fichier secret.json manquant. Relancez install.sh";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification - StorySwitch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { background: #09090b; color: #f4f4f5; font-family: sans-serif; }</style>
</head>
<body class="h-screen w-full flex items-center justify-center">
    
    <div class="bg-[#18181b] border border-[#3f3f46] p-8 rounded-xl shadow-2xl w-full max-w-md text-center">
        <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg mx-auto mb-6">
            <i class="fa-solid fa-shield-halved text-white text-2xl"></i>
        </div>
        
        <h1 class="text-xl font-bold mb-2">Protection Serveur</h1>
        <p class="text-gray-400 text-sm mb-6">Veuillez vous identifier pour accéder à l'éditeur.</p>

        <?php if($error): ?>
            <div class="bg-red-500/20 text-red-400 px-4 py-2 rounded text-sm mb-4 border border-red-500/50">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="text-left mb-1">
                <label class="text-xs font-bold text-gray-500 uppercase ml-1">Votre clé d'authentification :</label>
            </div>
            <div class="relative mb-6">
                <i class="fa-solid fa-key absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                <input type="password" name="access_key" placeholder="Collez le token ici..." 
                       class="w-full bg-[#09090b] border border-[#3f3f46] text-white rounded-lg py-3 pl-10 pr-4 outline-none focus:border-blue-500 transition-colors" required autofocus>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg transition-all">
                Entrer
            </button>
        </form>
    </div>

</body>
</html>