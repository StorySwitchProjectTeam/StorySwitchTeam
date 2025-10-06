const listes = document.querySelectorAll(".menu_liste .element_liste");
const sous_liste = document.querySelectorAll(".element_sous_liste");
const li = document.querySelectorAll(".element_sous_liste li");

listes.forEach(function(liste) {
    liste.addEventListener("click", function() {
        const sousListeId = this.id.replace("#", "") + "_sous";
        const sousListe = document.getElementById(sousListeId);
        listes.forEach(el => el.classList.remove("element_liste_open"));
        
        if (sousListe) {
            sousListe.style.display = sousListe.style.display === "flex" ? "none" : "flex";
        }
        this.classList.toggle("element_liste_open");
    });
});