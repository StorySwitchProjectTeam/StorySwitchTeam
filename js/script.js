// $$\      $$\ $$\      $$\ $$$$$$\       $$$$$$$\  $$$$$$$$\ $$\    $$\   $$\   
// $$$\    $$$ |$$$\    $$$ |\_$$  _|      $$  __$$\ $$  _____|$$ |   $$ |$$$$ |  
// $$$$\  $$$$ |$$$$\  $$$$ |  $$ |        $$ |  $$ |$$ |      $$ |   $$ |\_$$ |  

// $$\$$\$$ $$ |$$\$$\$$ $$ |  $$ |        $$ |  $$ |$$$$$\    \$$\  $$  |  $$ |  
// $$ \$$$  $$ |$$ \$$$  $$ |  $$ |        $$ |  $$ |$$  __|    \$$\$$  /   $$ |  
// $$ |\$  /$$ |$$ |\$  /$$ |  $$ |        $$ |  $$ |$$ |        \$$$  /    $$ |  
// $$ | \_/ $$ |$$ | \_/ $$ |$$$$$$\       $$$$$$$  |$$$$$$$$\    \$  /   $$$$$$\ 
// \__|     \__|\__|     \__|\______|      \_______/ \________|    \_/    \______|
// Script made by Michaël VLESIK-SCHMITT | Argjend KALLARI | Emma JOLIVET | Dimitri KNYAZEV
// Michaël VLESIK-SCHMITT : https://github.com/FroostDev | https://michaelvlesik.fr
// Argjend KALLLARI : https://github.com/Argjend05 | https://argjendkallari.fr
// Emma JOLIVET : https://github.com/emmaj2
// Dimitri KNYAZEV : https://github.com/Sopatrika | https://dimitriknyazev.fr

let start = document.querySelector('.begin');
let start_zone = document.querySelector('.black-overlay');
let url_zone = document.querySelector('#url_zone');
let choix_list = document.querySelector('.choice');
let pause_btn = document.querySelector('.pause-btn');

// Data temporaire (utilisée SI RIEN n'est sauvegardé)
let fallback_data = {
    'intro': {
        'url': 'video/intro.mp4',
        'duree_choix': 4,
        'choix': {
            'choix1': ['Aller à droite', 'tuto1'],
            'choix2': ['Aller à gauche', 'tuto2']
        }
    },

    'tuto1': {
        'url': 'video/1.mp4',
        'duree_choix': 10,
        'choix': {
            'choix1': ['Mourir', 'intro'],
            'choix2': ['Pas mourir', 'tuto1'],
            'choix3': ['Ne rien faire', 'tuto2']
        }
    },

    'tuto2': {
        'url': 'video/2.mp4',
        'duree': 5000
    }
};

// Tente de charger les données depuis le localStorage
const savedData = localStorage.getItem('storyswitch_data');

// Utilise les données sauvegardées si elles existent, sinon utilise les données de test
let data_film = savedData ? JSON.parse(savedData) : fallback_data;

// === MODIFICATION ICI ===
// Trouve le nom du premier nœud dans le JSON (au lieu de forcer "intro")
// S'assure que data_film existe et n'est pas vide avant de chercher
const firstNodeName = (data_film && Object.keys(data_film).length > 0) ? Object.keys(data_film)[0] : null;

let currentVideo = firstNodeName;
// === FIN MODIFICATION ===


// ///////////////////////////////////////////
// ||  Met pause quand on clique sur pause
// ||                   
// ||   Input:
// ||
// ||   Output:
// ///////////////////////////////////////////
let playing_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
let paused_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';

pause_btn.addEventListener('click', () => {
    if (url_zone.paused) {
        url_zone.play();
        pause_btn.innerHTML = playing_svg;
    }
    else {
        url_zone.pause();
        pause_btn.innerHTML = paused_svg;
    }
})

window.addEventListener('keydown', (event) => {
    if (event.keyCode == 32) {
        if (url_zone.paused) {
            url_zone.play();
            pause_btn.innerHTML = playing_svg;
        }
        else {
            url_zone.pause();
            pause_btn.innerHTML = paused_svg;
        }
    }
});

// ///////////////////////////////////////////
// ||  Charge la vidéo intro (1ere video)
// ||                   
// ||   Input:
// ||
// ||   Output:
// ///////////////////////////////////////////
function loadintro() {
    // === MODIFICATION ICI ===
    // Utilise la variable firstNodeName au lieu de "intro"
    if (!url_zone.src && firstNodeName) {
        // Vérifie aussi que la data existe bien
        if (data_film[firstNodeName]) {
            url_zone.src = data_film[firstNodeName].url;
            showchoice(firstNodeName);
        } else {
            console.error(`Erreur: Le nœud de démarrage "${firstNodeName}" n'a pas été trouvé dans data_film.`);
            start_zone.innerHTML = `<div style='color: red; background: black; padding: 20px;'>Erreur: Nœud de démarrage introuvable. Avez-vous sauvegardé depuis l'éditeur ?</div>`;
        }
    } else if (!firstNodeName) {
        // Gère le cas où le JSON est vide ou corrompu
        console.error("Erreur: Impossible de charger 'data_film' ou le JSON est vide.");
        start_zone.innerHTML = `<div style='color: red; background: black; padding: 20px;'>Erreur: Aucune donnée de film n'a été trouvée. Avez-vous sauvegardé depuis l'éditeur ?</div>`;
    }
    // === FIN MODIFICATION ===
};

// ///////////////////////////////////////////
// //Lance la vidéo quand on appuie sur start/
// ///////////////////////////////////////////
start.addEventListener('click', starting);
function starting() {
    // === MODIFICATION ICI ===
    // Ne fait rien si le premier nœud n'a pas pu être chargé
    if (!firstNodeName) {
        console.error("Démarrage annulé : aucun nœud initial chargé.");
        return; 
    }
    // === FIN MODIFICATION ===
    
    start_zone.remove();
    url_zone.play();
};

// ///////////////////////////////////////////
// ||  Joue une vidéo
// ||                   
// ||   Input:
// ||     -> video : str
// ||
// ||   Output:
// ///////////////////////////////////////////
function playvideo(video) {
    // Sécurité : si la vidéo cible n'existe pas, on arrête
    if (!data_film[video]) {
        console.error(`Erreur: Tentative de jouer une vidéo inconnue: "${video}".`);
        // On pourrait recharger l'intro, mais on va plutôt afficher l'écran de fin
        document.querySelector(".black-end").classList.remove("hide-end");
        return;
    }
    
    currentVideo = video;
    choix_list.innerHTML = "";
    url_zone.src = data_film[video].url;
    url_zone.play();
    showchoice(video);
};

// /////////////////////////////////////
// ||  Charge les choix de la vidéo
// ||                   
// ||   Input:
// ||     -> video : str
// ||
// ||   Output:
// ///////////////////////////////////////////
function showchoice(video) {
    // Sécurité : si la vidéo n'existe pas dans le JSON
    if (!data_film[video]) {
        console.error(`Erreur showchoice: Le nœud "${video}" n'existe pas.`);
        return;
    }
    
    choix_list.innerHTML = "";  // Enlève les anciens choix
    choix_list.className = "";

    url_zone.onloadedmetadata = () => {
        url_zone.addEventListener("timeupdate", function videoduration() {
            let videoTime = url_zone.duration - url_zone.currentTime;

            // Vérifie que duree_choix et choix existent avant de continuer
            if (data_film[video].duree_choix && data_film[video].choix && videoTime <= (data_film[video].duree_choix / 1000)) { // Converti ms en secondes
                choix_list.classList.add("choice");
                document.documentElement.style.setProperty('--timerduration', (data_film[video].duree_choix / 1000) + "s"); // Converti ms en secondes
                choix_list.classList.add("timeranim");

                Object.values(data_film[video].choix).forEach((elt) => {
                    let choix = document.createElement('button');
                    choix.innerText = elt[0];
                    choix.addEventListener('click', () => {
                        playvideo(elt[1]);
                    });
                    choix_list.appendChild(choix);
                });
                url_zone.removeEventListener("timeupdate", videoduration);
            };
        });
    };
};

url_zone.addEventListener('ended', () => {
    let choix = data_film[currentVideo];

    if (!choix || !choix.choix) {
        document.querySelector(".black-end").classList.remove("hide-end");
        return;
    };
});

choix_list.addEventListener('animationend', () => {
    let choix = data_film[currentVideo];

    if (choix) {
        if (!choix.choix) return; 
        
        let cles = Object.keys(data_film[currentVideo].choix);
        if (!cles.length) return;
        let cleAleatoire = cles[Math.floor(Math.random() * cles.length)];
        let nextVideo = data_film[currentVideo].choix[cleAleatoire][1];

        // Sécurité : si le choix aléatoire mène à une vidéo nulle ou inexistante
        if (nextVideo && data_film[nextVideo]) {
            playvideo(nextVideo);
        } else {
             console.warn(`Fin de la branche : le choix aléatoire "${nextVideo}" n'existe pas.`);
            document.querySelector(".black-end").classList.remove("hide-end");
        }
    };
});

url_zone.controls = false; // Retire les fonctions de base
loadintro();