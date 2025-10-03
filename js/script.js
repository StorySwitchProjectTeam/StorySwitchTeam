let start = document.querySelector('.begin');
let start_zone = document.querySelector('.black-overlay');
let url_zone = document.querySelector('#url_zone');
let choix_list = document.querySelector('.choice');

// Data temporaire pour les phases de test
let data_film = {
    'intro': {
        'url': 'video/intro.mp4',
        'duree': 13000,
        'duree_choix': 4,
        'choix': {
            'choix1': ['Aller à droite', 'tuto1'],
            'choix2': ['Aller à gauche', 'tuto2']
        }
    },

    'tuto1': {
        'url': 'video/1.mp4',
        'duree': 11000,
        'duree_choix': 10,
        'choix': {
            'choix1': ['Mourir', 'intro'],
            'choix2': ['Pas mourir', 'tuto1'],
            'choix3': ['Ne rien faire', 'tuto2']
        }
    },

    'tuto2': {
        'url': 'video/2.mp4'
    }
}

// ///////////////////////////////////////////
// ||  Charge la vidéo intro (1ere video)
// ||                   
// ||   Input:
// ||
// ||   Output:
// ///////////////////////////////////////////
function loadintro() {
    if (!url_zone.src) {
        url_zone.src = data_film['intro'].url;
        showchoice('intro');
    }
}

// ///////////////////////////////////////////
// //Lance la vidéo quand on appuie sur start/
// ///////////////////////////////////////////
start.addEventListener('click', starting);
function starting() {
    start_zone.remove();
    url_zone.play();
}

// /////////////////////////////////////
// ||  Charge les choix de la vidéo
// ||                   
// ||   Input:
// ||     -> video : str
// ||
// ||   Output:
// ///////////////////////////////////////////
function showchoice(video) {
    choix_list.innerHTML = "";  // Enlève les anciens choix
    choix_list.className = "";
    url_zone.play();
    
    if (!data_film[video].choix) {
        url_zone.addEventListener("ended", () => {
            document.querySelector(".black-end").classList.remove("hide-end");
        })
    };

    url_zone.onloadedmetadata = () => {
        url_zone.addEventListener("timeupdate", function videoduration() {
            let videoTime = url_zone.duration - url_zone.currentTime;

            if (videoTime <= data_film[video].duree_choix) {
                choix_list.classList.add("choice");
                document.documentElement.style.setProperty('--timerduration', (data_film[video].duree_choix) + "s");
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
            url_zone.addEventListener("ended", () => {
                let cles = Object.keys(data_film);

                let cleAleatoire = cles[Math.floor(Math.random() * cles.length)];

                playvideo(cleAleatoire);
            })



        });
    };
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
    choix_list.innerHTML = "";
    url_zone.src = data_film[video].url;
    showchoice(video);
}

loadintro();