
/**
 * On reçoit un tag. On regarde dans le tableau « listeTagsProjets » son état et on l'inverse. Puis, on change également
 * son mode d'affichage.
 *
 * @param tag Le tag dont il faut changer l'état (actif/inactif).
 */
function toggleTag(tag)
{
    // Les tags sont tous de la forme « tag_nomCatégorie ».
    const nomCategorie = tag.replace('tag_', '');
    // On change l'apparence du bouton tag.
    const boutonAChanger = document.getElementById(tag);
    listeTagsProjets[nomCategorie] ? boutonAChanger.classList.remove('boutonTagActif') : boutonAChanger.classList.remove('boutonTagInactif');
    listeTagsProjets[nomCategorie] ? boutonAChanger.classList.add('boutonTagInactif') : boutonAChanger.classList.add('boutonTagActif');
    // On bascule l'état mémorisé du tag.
    listeTagsProjets[nomCategorie] ? listeTagsProjets[nomCategorie]=false : listeTagsProjets[nomCategorie]=true;
}

/**
 * Un tag a été cliqué, il faut gérer l'affichage des blocs qui devront correspondre aux tags activés.
 */
function modifierAffichageMiniBlocs()
{
    let itemSelector, parentBloc;
    //console.log(blocCourant);
    switch (blocCourant) {
    case "projets":
        parentBloc='#miniBlocProjets';
        itemSelector='';
            break;
    case "prototypes":
        parentBloc='#miniBlocPrototypes';
        itemSelector='';
            break;
    case "evenements":
        parentBloc='#miniBlocEvenements';
        itemSelector='';
            break;
    case 'articlesAuteur':
        parentBloc='#blocContenantArticles';
        itemSelector='';
    default:
            break;
    }
    isoSousBloc = new Isotope(
        parentBloc, {
            itemSelector: '.blocGrille',
            layoutMode: 'fitRows',
            transitionDuration: '0'
        }
    );
    isoSousBloc.arrange(
        {
            filter: function ( index, itemElem ) {
                //console.log(objetAffichable((itemElem)));
                return objetAffichable(itemElem);
            }
        }
    );
}

/**
 * L'objet doit-il être affiché ?
 *
 * @param  objet Un mini-bloc (projet/prototype/évènement)
 * @return {boolean}
 */
function objetAffichable(objet=null)
{
    /*
        Une règle prime sur le reste : si tous les tags sont déselectionnés, cela revient à ce qu'ils le soient tous.
        Donc, il faut afficher.
     */
    let tousDeselectionne = true;
    for (let tag in listeTagsProjets){
        // Si un seul tag est activé (à true), c'est donc... qu'ils ne sont pas tous désactivés.
        if (listeTagsProjets[tag]) {
            tousDeselectionne=false;
            break;
        }
    }
    if (tousDeselectionne===true) {
        objet.style.display='inline-block';
        return true;
    }

    // Comportement normal
    for (let tag in listeTagsProjets){
        if (listeTagsProjets[tag] && objet.classList.contains(tag)) {
            objet.style.display='inline-block';
            return true;
        }
    }
    return false;
}

/**
 * Ouverture du menu latéral.
 */
function openNav()
{
    if ($(window).width() < 595) {
        document.getElementById("menu_principal").style.width = "80%";
    }
    else {
        document.getElementById("menu_principal").style.width = "25vw";
    }
}

/**
 * Fermeture du menu latéral.
 */
function closeNav()
{
    document.getElementById("menu_principal").style.width = "0%";
}
function afficherRecherche()
{
    if ($(".formulaireRechercheEnTete #recherche").hasClass("cacher")) {
        $(this).removeClass("cacher")
    }
    else {
        $(this).addClass("cacher")

    }
}


/**
 * Déplace la vue en haut de page.
 *
 * @param ancre
 */
function deplacerVueEnHaut(ancre='')
{
    // D'abord, on renvoie en haut de la page.
    location.hash = '#pageSommaire';
    // Puis, on remet le tag marquant la page actuelle (utile et nécessaire pour actualiser la page sans heurts)
    location.hash = '#' + ancre;
}

/**
 * Affichage Modale
 */
function openModal(modalId)
{
    $("body").prepend("<div class='modal-overlay'></div>")
    console.log("MODAL SHOW")
    $("#"+modalId).show()
}


/**
 * Cacher modale
 */
function closeModal(modalId)
{
    $(".modal-overlay").remove()
    console.log("MODAL HIDE")
    $("#"+modalId).hide()
}



/**
 * Une fois la page chargée
 */
$(document).ready(
    function () {
        /**
         * Initialise le slider de la page partenaire
         */
        var lazyLoadInstance = new LazyLoad(
            {
                // Your custom settings go here
            }
        );

        $('.partnenaire-slider').slick(
            {
                infinite: true,
                slidesToShow: 6,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                draggable: true,
                accessibility: false,
                centerMode: true,
                variableWidth: false,
                arrows : true
            }
        );

    }
);


