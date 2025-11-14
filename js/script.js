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
// Argjend KALLARI : https://github.com/Argjend05 | https://argjendkallari.fr
// Emma JOLIVET : https://github.com/emmaj2
// Dimitri KNYAZEV : https://github.com/Sopatrika | https://dimitriknyazev.fr

let start = document.querySelector('.begin');
let start_zone = document.querySelector('.black-overlay');
let url_zone = document.querySelector('#url_zone');
let choix_list = document.querySelector('.choice');
let pause_btn = document.querySelector('.pause-btn');

// === NOUVEAUX ÉLÉMENTS DOM ===
let fullscreenBtn = document.getElementById('fullscreen-btn');
let videoContainer = document.getElementById('video-container');

// Barres du Timer
let timerBars = {
    top: document.getElementById('timer-top'),
    right: document.getElementById('timer-right'),
    bottom: document.getElementById('timer-bottom'),
    left: document.getElementById('timer-left')
};
let timerAnimId = null; // ID pour l'animation
// === FIN NOUVEAUX ÉLÉMENTS ===

// === COULEURS DU TIMER ===
const TIMER_COLOR_START = { r: 76, g: 175, b: 80 }; // Vert
const TIMER_COLOR_END = { r: 244, g: 67, b: 54 }; // Rouge
// === FIN COULEURS ===


// Data temporaire (utilisée SI RIEN n'est sauvegardé)
let fallback_data = {
    'intro': {
        'url': 'video/intro.mp4',
        'duree_choix': 4000, // Durée en MS
        'choix': {
            'choix1': ['Aller à droite', 'tuto1'],
            'choix2': ['Aller à gauche', 'tuto2']
        }
    },
    'tuto1': {
        'url': 'video/1.mp4',
        'duree_choix': 10000,
        'choix': {
            'choix1': ['Mourir', 'intro'],
            'choix2': ['Pas mourir', 'tuto1'],
            'choix3': ['Ne rien faire', 'tuto2']
        }
    },
    'tuto2': {
        'url': 'video/2.mp4'
        // Pas de choix, donc pas de duree_choix
    }
};

// Tente de charger les données depuis le localStorage
const savedData = localStorage.getItem('storyswitch_data');

// Utilise les données sauvegardées si elles existent, sinon utilise les données de test
let data_film = savedData ? JSON.parse(savedData) : fallback_data;

// Trouve le nom du premier nœud dans le JSON (au lieu de forcer "intro")
const firstNodeName = (data_film && Object.keys(data_film).length > 0) ? Object.keys(data_film)[0] : null;
let currentVideo = firstNodeName;


// ///////////////////////////////////////////
// ||  Gestion Pause / Plein Écran
// ///////////////////////////////////////////
let playing_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
let paused_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';

pause_btn.addEventListener('click', togglePlayPause);
fullscreenBtn.addEventListener('click', toggleFullScreen);

// Gère aussi la barre Espace
window.addEventListener('keydown', (event) => {
    if (event.code === 'Space' && !start_zone) { // Ne pas déclencher si l'overlay de début est là
        event.preventDefault(); // Empêche le défilement
        togglePlayPause();
    }
    // On pourrait ajouter la touche 'F' pour le plein écran
    if (event.code === 'KeyF') {
        toggleFullScreen();
    }
});

function togglePlayPause() {
    if (url_zone.paused) {
        url_zone.play();
        pause_btn.innerHTML = playing_svg;
    } else {
        url_zone.pause();
        pause_btn.innerHTML = paused_svg;
    }
}

function toggleFullScreen() {
    if (!document.fullscreenElement) {
        // Demande le plein écran sur le CONTENEUR, pas juste la vidéo
        videoContainer.requestFullscreen().catch(err => {
            console.error(`Erreur lors du passage en plein écran: ${err.message} (${err.name})`);
        });
    } else {
        document.exitFullscreen();
    }
}

// ///////////////////////////////////////////
// ||  Charge la vidéo intro (1ere video)
// ///////////////////////////////////////////
function loadintro() {
    if (!url_zone.src && firstNodeName) {
        if (data_film[firstNodeName] && data_film[firstNodeName].url) {
            url_zone.src = data_film[firstNodeName].url;
            showchoice(firstNodeName);
        } else {
            console.error(`Erreur: Le nœud de démarrage "${firstNodeName}" ou son URL n'a pas été trouvé.`);
            start_zone.innerHTML = `<div style='color: red; background: black; padding: 20px;'>Erreur: Nœud de démarrage introuvable. Avez-vous sauvegardé depuis l'éditeur ?</div>`;
        }
    } else if (!firstNodeName) {
        console.error("Erreur: Impossible de charger 'data_film' ou le JSON est vide.");
        start_zone.innerHTML = `<div style='color: red; background: black; padding: 20px;'>Erreur: Aucune donnée de film n'a été trouvée. Avez-vous sauvegardé depuis l'éditeur ?</div>`;
    }
};

// ///////////////////////////////////////////
// //Lance la vidéo quand on appuie sur start/
// ///////////////////////////////////////////
start.addEventListener('click', starting);
function starting() {
    if (!firstNodeName) {
        console.error("Démarrage annulé : aucun nœud initial chargé.");
        return; 
    }
    start_zone.remove();
    start_zone = null; // Pour que la barre Espace fonctionne
    
    // Demande au navigateur de pouvoir jouer du son
    url_zone.play().catch(error => {
        console.warn("La lecture auto a été bloquée, la vidéo est en pause.", error);
        pause_btn.innerHTML = paused_svg; // Met l'icône "play"
    });
};

// ///////////////////////////////////////////
// ||  Joue une vidéo
// ||                   
// ||   Input:
// ||     -> video : str
// ///////////////////////////////////////////
function playvideo(video) {
    if (!data_film[video]) {
        console.error(`Erreur: Tentative de jouer une vidéo inconnue: "${video}".`);
        document.querySelector(".black-end").classList.remove("hide-end");
        return;
    }
    
    currentVideo = video;
    
    // Réinitialise l'UI
    choix_list.innerHTML = "";
    choix_list.classList.remove('choices-visible'); // Cache les choix
    resetTimerBars(); // Arrête et cache le timer
    
    url_zone.src = data_film[video].url;
    url_zone.play();
    pause_btn.innerHTML = playing_svg; // S'assure que l'icône est "pause"
    
    showchoice(video);
};

// /////////////////////////////////////
// ||  Charge les choix de la vidéo
// ||                   
// ||   Input:
// ||     -> video : str
// ///////////////////////////////////////////
function showchoice(video) {
    if (!data_film[video]) {
        console.error(`Erreur showchoice: Le nœud "${video}" n'existe pas.`);
        return;
    }
    
    // Vide les anciens choix
    choix_list.innerHTML = "";  
    choix_list.className = "choice"; // Réinitialise les classes

    url_zone.onloadedmetadata = () => {
        // Vérifie s'il y a des choix à afficher
        const hasChoices = data_film[video].duree_choix && data_film[video].choix && Object.keys(data_film[video].choix).length > 0;
        
        if (hasChoices) {
            url_zone.addEventListener("timeupdate", function videoduration() {
                let videoTimeRemaining = url_zone.duration - url_zone.currentTime;
                let choiceDurationMs = data_film[video].duree_choix;
                
                // Compare les temps en secondes
                if (videoTimeRemaining <= (choiceDurationMs / 1000)) {
                    
                    // 1. Affiche les choix (déclenche l'animation CSS)
                    choix_list.classList.add("choices-visible");

                    // 2. Crée les boutons
                    Object.values(data_film[video].choix).forEach((elt) => {
                        let choix = document.createElement('button');
                        choix.innerText = elt[0];
                        choix.addEventListener('click', () => {
                            // Vérifie que la destination existe
                            if(elt[1] && data_film[elt[1]]) {
                                playvideo(elt[1]);
                            } else {
                                console.warn(`Destination de choix "${elt[1]}" introuvable. Fin.`);
                                document.querySelector(".black-end").classList.remove("hide-end");
                            }
                        });
                        choix_list.appendChild(choix);
                    });
                    
                    // 3. Démarre le timer de la bordure
                    startTimer(choiceDurationMs);
                    
                    // 4. On a fini, on retire l'écouteur
                    url_zone.removeEventListener("timeupdate", videoduration);
                };
            });
        }
    };
};

// /////////////////////////////////////
// ||  Gestion du Timer de la Bordure (Modifié)
// ///////////////////////////////////////////

/** Arrête toute animation de timer et cache les barres */
function resetTimerBars() {
    if (timerAnimId) {
        cancelAnimationFrame(timerAnimId);
        timerAnimId = null;
    }
    // Cache les barres et réinitialise la couleur au vert
    const startColor = `rgb(${TIMER_COLOR_START.r}, ${TIMER_COLOR_START.g}, ${TIMER_COLOR_START.b})`;
    Object.values(timerBars).forEach(bar => {
        bar.style.opacity = '0';
        bar.style.width = '0';
        bar.style.height = '0';
        bar.style.backgroundColor = startColor; // Réinitialise au vert
    });
}

/** Démarre l'animation de la bordure */
function startTimer(durationMs) {
    resetTimerBars(); // S'assure qu'aucun autre timer ne tourne
    
    // Remet les barres à 0 et visibles (elles sont déjà vertes)
    Object.values(timerBars).forEach(bar => bar.style.opacity = '1');
    timerBars.top.style.height = '5px';
    timerBars.right.style.width = '5px';
    timerBars.bottom.style.height = '5px';
    timerBars.left.style.width = '5px';

    let startTime = performance.now();

    function animateLoop(now) {
        let elapsed = now - startTime;
        let progress = Math.min(elapsed / durationMs, 1.0); // 0.0 à 1.0

        // === MODIFICATION : Interpolation de couleur ===
        const r = Math.round(TIMER_COLOR_START.r + (TIMER_COLOR_END.r - TIMER_COLOR_START.r) * progress);
        const g = Math.round(TIMER_COLOR_START.g + (TIMER_COLOR_END.g - TIMER_COLOR_START.g) * progress);
        const b = Math.round(TIMER_COLOR_START.b + (TIMER_COLOR_END.b - TIMER_COLOR_START.b) * progress);
        const newColor = `rgb(${r}, ${g}, ${b})`;

        Object.values(timerBars).forEach(bar => {
            bar.style.backgroundColor = newColor;
        });
        // === FIN MODIFICATION ===

        // 1. Barre du haut (0% -> 25%)
        let topProgress = Math.min(progress / 0.25, 1.0);
        timerBars.top.style.width = (topProgress * 100) + '%';
        
        // 2. Barre de droite (25% -> 50%)
        let rightProgress = Math.max(0, Math.min((progress - 0.25) / 0.25, 1.0));
        timerBars.right.style.height = (rightProgress * 100) + '%';
        
        // 3. Barre du bas (50% -> 75%)
        let bottomProgress = Math.max(0, Math.min((progress - 0.50) / 0.25, 1.0));
        timerBars.bottom.style.width = (bottomProgress * 100) + '%';
        
        // 4. Barre de gauche (75% -> 100%)
        let leftProgress = Math.max(0, Math.min((progress - 0.75) / 0.25, 1.0));
        timerBars.left.style.height = (leftProgress * 100) + '%';
        
        
        if (progress < 1.0) {
            // Continue la boucle
            timerAnimId = requestAnimationFrame(animateLoop);
        } else {
            // Le temps est écoulé !
            timerAnimId = null;
            handleTimerEnd();
        }
    }

    // Lance la première frame
    timerAnimId = requestAnimationFrame(animateLoop);
}

/** Appelé quand le timer de la bordure est terminé */
function handleTimerEnd() {
    resetTimerBars(); // Cache la bordure
    
    let choix = data_film[currentVideo];

    if (choix && choix.choix) {
        let cles = Object.keys(choix.choix);
        if (!cles.length) {
            // Pas de choix, c'est la fin
            document.querySelector(".black-end").classList.remove("hide-end");
            return;
        }
        
        let cleAleatoire = cles[Math.floor(Math.random() * cles.length)];
        let nextVideo = choix.choix[cleAleatoire][1];
        
        if (nextVideo && data_film[nextVideo]) {
            playvideo(nextVideo);
        } else {
            console.warn(`Fin de la branche : le choix aléatoire "${nextVideo}" n'existe pas ou est nul.`);
            document.querySelector(".black-end").classList.remove("hide-end");
        }
    } else {
        // Pas de data de choix, c'est la fin
        document.querySelector(".black-end").classList.remove("hide-end");
    }
}


// /////////////////////////////////////
// ||  Gestion de la Fin de Vidéo
// ///////////////////////////////////////////

url_zone.addEventListener('ended', () => {
    let choix = data_film[currentVideo];
    
    // Si la vidéo se termine et qu'il n'y avait pas de choix, c'est la fin
    if (!choix || !choix.choix || Object.keys(choix.choix).length === 0) {
        document.querySelector(".black-end").classList.remove("hide-end");
        return;
    }
    
    // Si la vidéo se termine alors que des choix étaient affichés 
    // (par ex, si duree_choix est plus long que la fin de la vidéo),
    // on force le choix aléatoire.
    if (timerAnimId) { // Le timer tournait
        handleTimerEnd();
    }
});

url_zone.controls = false; // Retire les fonctions de base
loadintro();