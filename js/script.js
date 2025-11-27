// Script made by Michaël VLESIK-SCHMITT | Argjend KALLARI | Emma JOLIVET | Dimitri KNYAZEV

const projectFile = (typeof AUTO_LOAD_PROJECT !== 'undefined') ? AUTO_LOAD_PROJECT : 'exemple.json';
const JSON_PATH = `json/${projectFile}`;

console.log("📂 Script démarré. Cible :", JSON_PATH);

// --- SÉLECTEURS ---
let start = document.querySelector('.begin');
let start_zone = document.querySelector('.black-overlay');
let url_zone = document.querySelector('#url_zone');
let choix_list = document.querySelector('.choice');
let pause_btn = document.querySelector('.pause-btn');
let fullscreenBtn = document.getElementById('fullscreen-btn');
let videoContainer = document.getElementById('video-container');

// --- OVERLAY ERREUR ---
let errorOverlay = document.getElementById('error-overlay');
if (!errorOverlay) {
    errorOverlay = document.createElement('div');
    errorOverlay.id = 'error-overlay';
    errorOverlay.style.cssText = "display:none; position:fixed; inset:0; background:rgba(50,0,0,0.95); color:white; z-index:9999; justify-content:center; align-items:center; text-align:center; padding:2rem; flex-direction:column;";
    errorOverlay.innerHTML = `
        <h2 style="font-size:2rem; margin-bottom:1rem; color:#ff5555;">⚠️ Erreur de Lecture</h2>
        <p id="error-message" style="font-size:1.2rem; font-family:monospace; background:#220000; padding:15px; border-radius:5px; border:1px solid #ff5555;"></p>
        <button onclick="document.getElementById('error-overlay').style.display='none'" style="margin-top:20px; padding:10px 20px; cursor:pointer; background:white; color:black; border:none; font-weight:bold; border-radius:4px;">Fermer</button>
    `;
    document.body.appendChild(errorOverlay);
}
let errorMsg = document.getElementById('error-message');

// --- VARIABLES ---
let data_film = {}; 
let currentVideo = null;
let timerAnimId = null; 
let isSwitching = false; // VERROU DE SÉCURITÉ

let timerBars = {
    top: document.getElementById('timer-top'),
    right: document.getElementById('timer-right'),
    bottom: document.getElementById('timer-bottom'),
    left: document.getElementById('timer-left')
};

const TIMER_COLOR_START = { r: 76, g: 175, b: 80 }; 
const TIMER_COLOR_END = { r: 244, g: 67, b: 54 }; 
let playing_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
let paused_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333337" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';


// --- INITIALISATION ---
async function initPlayer() {
    try {
        const response = await fetch(`${JSON_PATH}?t=${Date.now()}`); 
        if (!response.ok) throw new Error(`Fichier introuvable.`);
        const fullData = await response.json();
        data_film = fullData.data_film ? fullData.data_film : fullData;
        console.log("✅ Données chargées :", data_film);
        
        setupPermanentListeners(); 
        findStartNode();
    } catch (error) {
        showError(`Impossible de charger : <strong>${projectFile}</strong><br>${error.message}`);
    }
}

function findStartNode() {
    const keys = Object.keys(data_film);
    if (keys.length === 0) return showError("JSON vide.");
    
    const starts = ['intro', 'debut', 'début', 'start', 'scene 1'];
    currentVideo = keys.find(k => starts.includes(k.toLowerCase())) || keys[0];
    
    if (data_film[currentVideo]) {
        // Juste set la source, pas de play auto au chargement de la page
        url_zone.src = data_film[currentVideo].url || "";
    } else {
        showError(`Scène de départ introuvable.`);
    }
}

function showError(msg) {
    if (errorOverlay) { errorMsg.innerHTML = msg; errorOverlay.style.display = "flex"; }
    else alert(msg.replace(/<br>/g, "\n"));
}

// --- COEUR DU SYSTÈME (ESPION PERMANENT) ---
function setupPermanentListeners() {
    
    // 1. Surveillance du temps
    url_zone.addEventListener('timeupdate', () => {
        // Si on est en train de changer de vidéo (verrou actif), on ignore tout
        if (isSwitching) return;

        // Si pas de vidéo active ou choix déjà affichés, on sort
        if (!currentVideo || choix_list.classList.contains('choices-visible')) return;

        let node = data_film[currentVideo];
        if (!node || !node.choix || Object.keys(node.choix).length === 0) return; 

        // Sécurité technique
        if (!url_zone.duration || isNaN(url_zone.duration)) return;

        let timeLeft = url_zone.duration - url_zone.currentTime;
        let choiceTime = (node.duree_choix || 4000) / 1000; 

        // Trigger des choix
        if (timeLeft <= choiceTime) {
            displayChoices(node);
            startTimer(node.duree_choix || 4000);
        }
    });

    // 2. Fin de vidéo
    url_zone.addEventListener('ended', () => {
        if (isSwitching) return; // Ignore les fin de vidéo fantômes lors du switch
        if(errorOverlay && errorOverlay.style.display !== 'none') return;

        let node = data_film[currentVideo];
        
        if (timerAnimId) {
            handleTimerEnd(); 
        } else if (!node || !node.choix || Object.keys(node.choix).length === 0) {
            document.querySelector(".black-end").classList.remove("hide-end");
        }
    });

    // 3. Déblocage du verrou quand la lecture COMMENCE vraiment
    url_zone.addEventListener('playing', () => {
        if (isSwitching) {
            // Un petit délai pour être sûr que les métadonnées de durée sont à jour
            setTimeout(() => { isSwitching = false; }, 100);
        }
    });

    // 4. Erreurs
    url_zone.addEventListener('error', () => {
        if(url_zone.src && url_zone.src !== window.location.href) { 
            showError(`Erreur de chargement :<br><small>${url_zone.src}</small>`);
        }
    });
}

// --- NAVIGATION ---
function playvideo(videoKey) {
    // 1. Activation du verrou pour bloquer les anciens événements
    isSwitching = true;
    
    console.log("🎬 Playvideo demandé :", videoKey);

    if (!videoKey || !data_film[videoKey]) return showError(`Scène introuvable : "${videoKey}"`);
    if (!data_film[videoKey].url) return showError(`URL manquante pour : "${videoKey}"`);

    // 2. Mise à jour état interne
    currentVideo = videoKey;
    
    // 3. Reset UI
    choix_list.innerHTML = ""; 
    choix_list.classList.remove('choices-visible');
    resetTimerBars();

    // 4. Gestion Vidéo
    let newSrc = data_film[videoKey].url;
    
    // Pour forcer le navigateur à comprendre que c'est une NOUVELLE lecture
    url_zone.pause();
    
    if (url_zone.src.includes(newSrc)) {
        url_zone.currentTime = 0;
    } else {
        url_zone.src = newSrc;
        url_zone.load(); // Force le rechargement propre
    }

    url_zone.play().catch(e => console.warn("Autoplay bloqué :", e));
    pause_btn.innerHTML = playing_svg;
}

function displayChoices(node) {
    choix_list.innerHTML = ""; 
    choix_list.classList.add("choices-visible");
    
    Object.values(node.choix).forEach(arr => {
        let btn = document.createElement('button');
        btn.innerText = arr[0]; // Texte
        
        btn.addEventListener('click', (e) => { 
            e.stopPropagation(); 
            // Clic = changement de scène
            playvideo(arr[1]); 
        });
        
        choix_list.appendChild(btn);
    });
}

// --- CONTROLES & TIMER ---
pause_btn.addEventListener('click', () => {
    if(url_zone.paused) { url_zone.play(); pause_btn.innerHTML = playing_svg; }
    else { url_zone.pause(); pause_btn.innerHTML = paused_svg; }
});

fullscreenBtn.addEventListener('click', () => {
    !document.fullscreenElement ? videoContainer.requestFullscreen() : document.exitFullscreen();
});

start.addEventListener('click', () => {
    if (!currentVideo) return;
    start_zone.style.opacity = '0'; 
    setTimeout(() => start_zone?.remove(), 500);
    playvideo(currentVideo);
});

function resetTimerBars() {
    if (timerAnimId) { cancelAnimationFrame(timerAnimId); timerAnimId = null; }
    const c = `rgb(${TIMER_COLOR_START.r}, ${TIMER_COLOR_START.g}, ${TIMER_COLOR_START.b})`;
    Object.values(timerBars).forEach(b => { if(b) { b.style.opacity='0'; b.style.width='0'; b.style.height='0'; b.style.backgroundColor=c; }});
}

function startTimer(duration) {
    resetTimerBars();
    Object.values(timerBars).forEach(b => b.style.opacity = '1');
    timerBars.top.style.height='5px'; timerBars.right.style.width='5px';
    timerBars.bottom.style.height='5px'; timerBars.left.style.width='5px';

    let start = performance.now();
    
    function loop(now) {
        if (!choix_list.classList.contains('choices-visible') || isSwitching) {
            timerAnimId = null;
            return;
        }

        let p = Math.min((now - start) / duration, 1.0);
        
        const r = Math.round(TIMER_COLOR_START.r + (TIMER_COLOR_END.r - TIMER_COLOR_START.r)*p);
        const g = Math.round(TIMER_COLOR_START.g + (TIMER_COLOR_END.g - TIMER_COLOR_START.g)*p);
        const b = Math.round(TIMER_COLOR_START.b + (TIMER_COLOR_END.b - TIMER_COLOR_START.b)*p);
        const col = `rgb(${r},${g},${b})`;
        Object.values(timerBars).forEach(bar => bar.style.backgroundColor = col);

        timerBars.top.style.width = (Math.min(p/0.25, 1)*100)+'%';
        timerBars.right.style.height = (Math.max(0, Math.min((p-0.25)/0.25, 1))*100)+'%';
        timerBars.bottom.style.width = (Math.max(0, Math.min((p-0.5)/0.25, 1))*100)+'%';
        timerBars.left.style.height = (Math.max(0, Math.min((p-0.75)/0.25, 1))*100)+'%';

        if (p < 1) {
            timerAnimId = requestAnimationFrame(loop);
        } else {
            timerAnimId = null;
            handleTimerEnd(); 
        }
    }
    timerAnimId = requestAnimationFrame(loop);
}

function handleTimerEnd() {
    // Si on est déjà en train de changer, on stop
    if(isSwitching) return;

    let node = data_film[currentVideo];
    if (node && node.choix) {
        let k = Object.keys(node.choix);
        if (k.length > 0) {
            let randomKey = k[Math.floor(Math.random()*k.length)];
            playvideo(node.choix[randomKey][1]);
        } else {
            document.querySelector(".black-end").classList.remove("hide-end");
        }
    } else {
        document.querySelector(".black-end").classList.remove("hide-end");
    }
}

url_zone.controls = false;
initPlayer();