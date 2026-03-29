document.addEventListener('DOMContentLoaded', function() {
    
    /* =========================================
       1. GESTION DU MENU MOBILE (TIROIR)
       ========================================= */
    const btnOpen = document.getElementById('mobile-menu-btn');
    const btnClose = document.querySelector('.close-drawer');
    const drawer = document.getElementById('mobile-drawer');

    if (btnOpen && btnClose && drawer) {
        btnOpen.addEventListener('click', function() {
            drawer.classList.add('active');
        });

        btnClose.addEventListener('click', function() {
            drawer.classList.remove('active');
        });

        document.addEventListener('click', function(event) {
            if (!drawer.contains(event.target) && !btnOpen.contains(event.target)) {
                drawer.classList.remove('active');
            }
        });
    }

    /* =========================================
       2. GESTION DE LA BARRE DE RECHERCHE MOBILE
       ========================================= */
    const searchBtn = document.getElementById('mobile-search-btn');
    const searchBar = document.getElementById('mobile-search-bar');

    if (searchBtn && searchBar) {
        searchBtn.addEventListener('click', function(event) {
            event.stopPropagation(); 
            searchBar.classList.toggle('active');
            
            if (searchBar.classList.contains('active')) {
                const input = searchBar.querySelector('input');
                if (input) input.focus();
            }
        });

        document.addEventListener('click', function(event) {
            if (!searchBar.contains(event.target) && !searchBtn.contains(event.target)) {
                searchBar.classList.remove('active');
            }
        });
    }

    /* =========================================
       3. GESTION DE LA BARRE D'ANNONCE (FADE)
       ========================================= */
    const announcementItems = document.querySelectorAll('.announcement-item');
    let currentIndex = 0;

    function rotateAnnouncements() {
        if (announcementItems.length > 1) {
            announcementItems[currentIndex].classList.remove('active');
            currentIndex = (currentIndex + 1) % announcementItems.length;
            announcementItems[currentIndex].classList.add('active');
        }
    }

    if (announcementItems.length > 0) {
        setInterval(rotateAnnouncements, 5000);
    }

    /* =========================================
       4. GESTION DU MEGA MENU (PC: SURVOL / MOB: CLIC)
       ========================================= */
    const megaMenuItems = document.querySelectorAll('.has-mega-menu');

    megaMenuItems.forEach(item => {
        // --- COMPORTEMENT PC (SURVOL) ---
        item.addEventListener('mouseenter', function() {
            if (window.innerWidth > 1024) { // On active seulement sur PC
                this.classList.add('active');
            }
        });

        item.addEventListener('mouseleave', function() {
            if (window.innerWidth > 1024) {
                this.classList.remove('active');
            }
        });

        // --- COMPORTEMENT TABLETTE/MOBILE (CLIC SUR LA FLÈCHE) ---
        const arrow = item.querySelector('.menu-arrow');
        if (arrow) {
            arrow.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                item.classList.toggle('active');
            });
        }
    });

    // Fermer les menus si on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.has-mega-menu')) {
            megaMenuItems.forEach(item => item.classList.remove('active'));
        }
    });

    /* =========================================
       5. GESTION ACCORDÉON MOBILE (MENU DRAWER)
       ========================================= */
    const toggleButtons = document.querySelectorAll('.toggle-submenu');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const parentLi = this.closest('li');
            const submenu = parentLi.querySelector('.submenu-mobile');

            if (submenu) {
                submenu.classList.toggle('active');
                this.textContent = submenu.classList.contains('active') ? '−' : '+';
            }
        });
    });

    /* =========================================
       6. GESTION DE LA BULLE "NOUS CONTACTER"
       ========================================= */
    const contactTrigger = document.getElementById('contactTrigger');
    const contactBubble = document.getElementById('contactBubble');

    if (contactTrigger && contactBubble) {
        contactTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            contactBubble.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!contactBubble.contains(e.target) && e.target !== contactTrigger) {
                contactBubble.classList.remove('active');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                contactBubble.classList.remove('active');
            }
        });
    }
});
/* =========================================
   7. FONCTIONS GLOBALES (SLIDER & FILTRES)
   ========================================= */

// --- GESTION DU SLIDER (ACCUEIL) ---
function moveSlide(direction) {
    const track = document.querySelector('.slider-track');
    if (!track) return;
    const scrollAmount = track.clientWidth;
    track.scrollBy({
        left: direction * scrollAmount,
        behavior: 'smooth'
    });
}

// --- FILTRAGE VISUEL (ACCUEIL / SECTIONS COURTES) ---
function filterProducts(category, event) {
    const cards = document.querySelectorAll('.product-card');
    const buttons = document.querySelectorAll('.filter-btn');

    if (buttons.length > 0) {
        buttons.forEach(btn => btn.classList.remove('active'));
        if (event) event.target.classList.add('active');
    }

    cards.forEach(card => {
        // On vérifie le data-category pour le filtrage instantané
        if (category === 'all' || card.getAttribute('data-category') === category) {
            card.style.display = 'block'; // 'block' ou 'flex' selon votre CSS
        } else {
            card.style.display = 'none';
        }
    });
}

// --- GESTION DU TIROIR DE FILTRES (PAGE COLLECTION MOBILE) ---
// Utilisation de facultatifs (?.) pour éviter les erreurs sur les pages sans filtres
const btnOpen = document.getElementById('openFilters');
const btnClose = document.getElementById('closeFilters');
const drawer = document.getElementById('filterDrawer');

if (btnOpen && drawer) {
    btnOpen.addEventListener('click', function() {
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden'; // Empêche le scroll du site derrière
    });
}

if (btnClose && drawer) {
    btnClose.addEventListener('click', function() {
        drawer.classList.remove('active');
        document.body.style.overflow = ''; // Réactive le scroll
    });
}

// Fermer le tiroir si on clique en dehors (optionnel mais pro)
window.addEventListener('click', function(e) {
    if (drawer && drawer.classList.contains('active') && e.target === drawer) {
        drawer.classList.remove('active');
        document.body.style.overflow = '';
    }
});