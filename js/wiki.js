const listes = document.querySelectorAll(".menu_liste .element_liste");
const sous_liste = document.querySelectorAll(".element_sous_liste");
const li = document.querySelectorAll(".element_sous_liste li");
const span = document.querySelectorAll(".element_liste span");

listes.forEach(function(liste) {
    liste.addEventListener("click", function() {
        const sousListeId = this.id.replace("#", "") + "_sous";
        const sousListe = document.getElementById(sousListeId);
        const currentSpan = this.querySelector("span");
        const isAlreadyOpen = this.classList.contains("element_liste_open");
        
        listes.forEach(el => {
            el.classList.remove("element_liste_open");
            const elSpan = el.querySelector("span");
            if (elSpan) elSpan.classList.remove("open_span");
        });
        
        sous_liste.forEach(sous => {
            sous.style.display = "none";
        });
        
        if (!isAlreadyOpen) {
            this.classList.add("element_liste_open");
            currentSpan.classList.add("open_span");
            sousListe.style.display = "flex";
        } else {
            this.classList.remove("element_liste_open");
            urrentSpan.classList.remove("open_span");
            sousListe.style.display = "none";
        }
    });
});