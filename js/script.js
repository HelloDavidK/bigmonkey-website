(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        /* =========================================
           1. GESTION DU MENU MOBILE (TIROIR)
           ========================================= */
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuCloseBtn = document.querySelector('.close-drawer');
        const mobileDrawer = document.getElementById('mobile-drawer');

        if (mobileMenuBtn && mobileMenuCloseBtn && mobileDrawer) {
            mobileMenuBtn.addEventListener('click', function () {
                mobileDrawer.classList.add('active');
            });

            mobileMenuCloseBtn.addEventListener('click', function () {
                mobileDrawer.classList.remove('active');
            });

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (target instanceof Element && !mobileDrawer.contains(target) && !mobileMenuBtn.contains(target)) {
                    mobileDrawer.classList.remove('active');
                }
            });
        }

        /* =========================================
           2. GESTION DE LA BARRE DE RECHERCHE MOBILE
           ========================================= */
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearchBar = document.getElementById('mobile-search-bar');

        if (mobileSearchBtn && mobileSearchBar) {
            mobileSearchBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                mobileSearchBar.classList.toggle('active');

                if (mobileSearchBar.classList.contains('active')) {
                    const input = mobileSearchBar.querySelector('input');
                    if (input) {
                        input.focus();
                    }
                }
            });

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (target instanceof Element && !mobileSearchBar.contains(target) && !mobileSearchBtn.contains(target)) {
                    mobileSearchBar.classList.remove('active');
                }
            });
        }

        /* =========================================
           3. GESTION DE LA BARRE D'ANNONCE (FADE)
           ========================================= */
        const announcementItems = document.querySelectorAll('.announcement-item');
        let currentAnnouncementIndex = 0;

        function rotateAnnouncements() {
            if (announcementItems.length <= 1) {
                return;
            }

            announcementItems[currentAnnouncementIndex].classList.remove('active');
            currentAnnouncementIndex = (currentAnnouncementIndex + 1) % announcementItems.length;
            announcementItems[currentAnnouncementIndex].classList.add('active');
        }

        if (announcementItems.length > 1) {
            window.setInterval(rotateAnnouncements, 5000);
        }

        /* =========================================
           4. GESTION DU MEGA MENU (PC: SURVOL / MOB: CLIC)
           ========================================= */
        const megaMenuItems = document.querySelectorAll('.has-mega-menu');

        megaMenuItems.forEach(function (item) {
            item.addEventListener('mouseenter', function () {
                if (window.innerWidth > 1024) {
                    item.classList.add('active');
                }
            });

            item.addEventListener('mouseleave', function () {
                if (window.innerWidth > 1024) {
                    item.classList.remove('active');
                }
            });

            const arrow = item.querySelector('.menu-arrow');
            if (arrow) {
                arrow.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    item.classList.toggle('active');
                });
            }
        });

        document.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof Element) || target.closest('.has-mega-menu')) {
                return;
            }

            megaMenuItems.forEach(function (item) {
                item.classList.remove('active');
            });
        });

        /* =========================================
           5. GESTION ACCORDÉON MOBILE (MENU DRAWER)
           ========================================= */
        const toggleButtons = document.querySelectorAll('.toggle-submenu');

        toggleButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const parentLi = button.closest('li');
                if (!parentLi) {
                    return;
                }

                const submenu = parentLi.querySelector('.submenu-mobile');
                if (!submenu) {
                    return;
                }

                submenu.classList.toggle('active');
                button.textContent = submenu.classList.contains('active') ? '−' : '+';
            });
        });

        /* =========================================
           6. GESTION DU MENU UTILISATEUR (SESSION CONNECTÉE)
           ========================================= */
        const userTrigger = document.getElementById('user-top-zone');
        const userDropdown = document.querySelector('.user-dropdown');

        if (userTrigger && userDropdown) {
            const closeUserMenu = function () {
                userDropdown.classList.remove('show');
                userTrigger.setAttribute('aria-expanded', 'false');
            };

            const openUserMenu = function () {
                userDropdown.classList.add('show');
                userTrigger.setAttribute('aria-expanded', 'true');
            };

            userTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (userDropdown.classList.contains('show')) {
                    closeUserMenu();
                } else {
                    openUserMenu();
                }
            });

            userTrigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();

                    if (userDropdown.classList.contains('show')) {
                        closeUserMenu();
                    } else {
                        openUserMenu();
                    }
                }
            });

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (target instanceof Element && !userDropdown.contains(target) && !userTrigger.contains(target)) {
                    closeUserMenu();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeUserMenu();
                }
            });
        }

        /* =========================================
           7. GESTION DE LA BULLE "NOUS CONTACTER"
           ========================================= */
        const contactTrigger = document.getElementById('contactTrigger');
        const contactBubble = document.getElementById('contactBubble');

        if (contactTrigger && contactBubble) {
            contactTrigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                contactBubble.classList.toggle('active');
            });

            document.addEventListener('click', function (event) {
                const target = event.target;
                if (target instanceof Element && !contactBubble.contains(target) && target !== contactTrigger) {
                    contactBubble.classList.remove('active');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    contactBubble.classList.remove('active');
                }
            });
        }
    });

    /* =========================================
       8. FONCTIONS GLOBALES (SLIDER & FILTRES)
       ========================================= */

    // --- GESTION DU SLIDER (ACCUEIL) ---
    window.moveSlide = function (direction) {
        const track = document.querySelector('.slider-track');
        if (!track) {
            return;
        }

        const scrollAmount = track.clientWidth;
        track.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    };

    // --- FILTRAGE VISUEL (ACCUEIL / SECTIONS COURTES) ---
    window.filterProducts = function (category, event) {
        const cards = document.querySelectorAll('.product-card');
        const buttons = document.querySelectorAll('.filter-btn');

        if (buttons.length > 0) {
            buttons.forEach(function (btn) {
                btn.classList.remove('active');
            });

            if (event && event.target instanceof Element) {
                event.target.classList.add('active');
            }
        }

        cards.forEach(function (card) {
            if (category === 'all' || card.getAttribute('data-category') === category) {
                card.style.display = 'block'; // 'block' ou 'flex' selon votre CSS
            } else {
                card.style.display = 'none';
            }
        });
    };

    // --- GESTION DU TIROIR DE FILTRES (PAGE COLLECTION MOBILE) ---
    const filterBtnOpen = document.getElementById('openFilters');
    const filterBtnClose = document.getElementById('closeFilters');
    const filterDrawer = document.getElementById('filterDrawer');

    if (filterBtnOpen && filterDrawer) {
        filterBtnOpen.addEventListener('click', function () {
            filterDrawer.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêche le scroll du site derrière
        });
    }

    if (filterBtnClose && filterDrawer) {
        filterBtnClose.addEventListener('click', function () {
            filterDrawer.classList.remove('active');
            document.body.style.overflow = ''; // Réactive le scroll
        });
    }

    window.addEventListener('click', function (event) {
        if (filterDrawer && filterDrawer.classList.contains('active') && event.target === filterDrawer) {
            filterDrawer.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
})();
