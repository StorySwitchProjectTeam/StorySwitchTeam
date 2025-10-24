const listes = document.querySelectorAll(".menu_liste .element_liste");
const sous_liste = document.querySelectorAll(".element_sous_liste");
const li = document.querySelectorAll(".element_sous_liste li");
const span = document.querySelectorAll(".element_liste span");

listes.forEach(function(liste) {
    liste.addEventListener("click", function() {
        const sousListeId = this.id.replace("#", "") + "_sous";
        const sousListe = document.getElementById(sousListeId);
        const current_span = this.querySelector("span");
        const Open = this.classList.contains("element_liste_open");
        
        if (Open) {
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