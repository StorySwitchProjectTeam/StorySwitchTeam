const listes = document.querySelectorAll(".menu_liste .element_liste");
const sous_liste = document.querySelectorAll(".element_sous_liste");
const li = document.querySelectorAll(".element_sous_liste li");
const span = document.querySelectorAll(".element_liste span");
const section = document.querySelectorAll(".right_main section");

// Affcher les sous listes
listes.forEach(function(liste) {
    liste.addEventListener("click", function() {
        const sousListeId = this.id.replace("#", "") + "_sous";
        const sousListe = document.getElementById(sousListeId);
        const current_span = this.querySelector("span");
        const open = this.classList.contains("element_liste_open");
        
        if (open) {
            this.classList.remove("element_liste_open");
            current_span.classList.remove("open_span");
            sousListe.style.display = "none";
        } else {
            this.classList.add("element_liste_open");
            current_span.classList.add("open_span");
            sousListe.style.display = "flex";
        }
    });
});

//Faire afficher les sections à partir des sous_listes
li.forEach(function(item, index) {
    item.addEventListener("click", function(event) {
        event.stopPropagation();
        section.forEach(s => {
            s.style.display = "none";
        });
        if (section[index]) {
            section[index].style.display = "block";
        }
        li.forEach(l => l.classList.remove('li_active'));
        this.classList.add('li_active');
    });
});

// Par défaut on ouvre la preière partie
document.addEventListener("DOMContentLoaded", e=>{
    listes[0].classList.add("element_liste_open");
    section[0].style.display = "block";
    span[0].classList.add("open_span");
    sous_liste[0].style.display = "flex";
})