<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'collection_helpers.php';

$context = getCollectionContextFromRequest($pdo, $_GET);
$productsData = fetchCollectionProductsData($pdo, $context);

include 'header.php';
include 'add_to_cart_form.php';
?>

<style>
:root {
    --collection-sidebar-width: 320px;
    --collection-gap: 40px;
    --collection-sticky-top: 24px;
}

/* =========================
   LAYOUT COLLECTION
========================= */
.container-collection {
    max-width: 1440px;
    margin: 40px auto 0;
    padding: 0 20px;
    display: grid;
    grid-template-columns: var(--collection-sidebar-width) minmax(0, 1fr);
    gap: var(--collection-gap);
    align-items: start;
}

.sidebar-filters {
    align-self: start;
    position: sticky;
    top: var(--collection-sticky-top);
    max-height: calc(100vh - (var(--collection-sticky-top) * 2));
    overflow-y: auto;
    padding-right: 18px;
    border-right: 1px solid #e5e7eb;
    scrollbar-width: thin;
}

.products-grid-container {
    min-width: 0;
}

.filter-title {
    margin: 0 0 20px;
    font-size: 2.2rem;
    line-height: 0.95;
    font-weight: 900;
    color: #f6c400;
    text-transform: uppercase;
    letter-spacing: 0.01em;
}
.filter-group {
    margin-bottom: 26px;
}

.filter-group h3 {
    margin: 0 0 14px;
    font-size: 15px;
    line-height: 1.2;
    font-weight: 900;
    color: #0f172a;
    text-transform: uppercase;
}

.filter-group ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.filter-group li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 14px;
}

.filter-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-top: 1px;
    flex: 0 0 18px;
    cursor: pointer;
}

.filter-group label {
    font-size: 13px;
    line-height: 1.3;
    color: #334155;
    cursor: pointer;
}

.btn-apply-filters {
    width: 100%;
    border: none;
    background: #111;
    color: #fff;
    font-size: 14px;
    font-weight: 900;
    text-transform: uppercase;
    padding: 14px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-apply-filters:hover {
    background: #000;
}
.container-collection {
    margin-bottom: 16px;
}

/* =========================
   PRODUCTS GRID
========================= */
.products-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 15px;
    max-width: 1200px;
    margin: 0 auto;
    align-items: stretch;
}

.product-card {
    border: 1px solid #eeeeee;
    padding: 0 !important;
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
}

.product-image-wrap {
    width: 100%;
    margin: 0;
    display: block;
}

.product-image-wrap img {
    width: 100% !important;
    height: auto !important;
    display: block;
}

.product-info {
    padding: 10px 12px 8px !important;
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex-grow: 1;
}

.product-brand {
    font-size: 11px;
    color: #777777;
    margin: 0;
    text-transform: uppercase;
    font-weight: 700;
    line-height: 1.1;
    min-height: 12px;
}

.product-name {
    font-size: 16px !important;
    font-weight: 900 !important;
    text-transform: uppercase;
    margin: 0 !important;
    color: #000;
    line-height: 1.08;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: calc(2 * 1.08em);
    max-height: calc(2 * 1.08em);
}

.product-desc {
    font-size: 12px !important;
    color: #777777;
    line-height: 1.15;
    margin: 0 !important;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: calc(2 * 1.15em);
    max-height: calc(2 * 1.15em);
}

.price {
    font-size: 18px !important;
    font-weight: 900;
    color: #000;
    margin-top: auto;
    margin-bottom: 8px !important;
    padding-top: 6px;
    line-height: 1.1;
}

.add-to-cart {
    width: 100%;
    background: #000;
    color: #fff;
    border: none;
    padding: 12px !important;
    font-weight: 800;
    font-size: 12px;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.2s ease;
    line-height: 1.1;
    margin-top: auto;
}

.add-to-cart:hover {
    background: #111;
}

.add-to-cart:active {
    background: #333;
}


/* =========================
   FILTERS / AJAX / SHORTCUTS
========================= */
.mobile-filter-trigger {
    display: none;
}

.filter-overlay {
    display: none;
}

.filter-header-mobile {
    display: none;
}

.flavor-shortcuts {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 0 0 24px;
}

.flavor-shortcuts a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    background: #fff;
    color: #111;
    font-weight: 800;
    text-decoration: none;
    transition: all 0.2s ease;
}

.flavor-shortcuts a:hover,
.flavor-shortcuts a.active {
    background: #f6c400;
    border-color: #f6c400;
    color: #000;
}

.collection-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.collection-count {
    font-size: 14px;
    font-weight: 700;
    color: #667085;
}

.ajax-loading {
    opacity: 0.55;
    pointer-events: none;
    transition: opacity 0.2s ease;
}

.collection-results-loader {
    display: none;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
    color: #667085;
    font-weight: 700;
}

.collection-results-loader.is-active {
    display: flex;
}

.collection-results-loader::before {
    content: '';
    width: 16px;
    height: 16px;
    border: 2px solid #d0d5dd;
    border-top-color: #111;
    border-radius: 50%;
    animation: spinLoader 0.8s linear infinite;
}

@keyframes spinLoader {
    to {
        transform: rotate(360deg);
    }
}

/* =========================
   DESKTOP
========================= */
@media screen and (min-width: 769px) {
    .products-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }

    .product-name {
        font-size: 18px !important;
    }
}

/* =========================
   TABLET / MOBILE
========================= */
@media screen and (max-width: 768px) {
    body.filters-open {
        overflow: hidden;
    }

    .mobile-filter-trigger {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 14px 20px 18px;
        padding: 18px 16px;
        background: #f6c400;
        color: #000;
        font-weight: 900;
        font-size: 15px;
        border-radius: 6px;
        cursor: pointer;
        text-transform: uppercase;
    }

    .container-collection {
        display: block;
        position: relative;
        margin-top: 0;
        padding: 0 20px;
    }

    .sidebar-filters {
        position: fixed;
        top: 0;
        left: -100%;
        width: 88%;
        max-width: 360px;
        height: 100vh;
        max-height: 100vh;
        background: #fff;
        z-index: 9999;
        overflow-y: auto;
        transition: left 0.3s ease;
        box-shadow: 8px 0 28px rgba(0, 0, 0, 0.18);
        padding: 20px 16px 30px;
        border-right: none;
    }

    .sidebar-filters.is-open {
        left: 0;
    }

    .filter-overlay {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .filter-overlay.is-open {
        opacity: 1;
        visibility: visible;
    }

    .filter-header-mobile {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        font-weight: 900;
        font-size: 18px;
    }

    .filter-title {
    font-size: 1.8rem;
    margin-bottom: 18px;
}
.filter-group h3 {
    font-size: 14px;
}
.filter-group label {
    font-size: 12px;
}

    .products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .product-card {
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .product-image-wrap img {
        width: 100% !important;
        height: auto !important;
        display: block;
        object-fit: contain;
    }

    .product-info {
        padding: 10px 8px 8px !important;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex-grow: 1;
    }

    .product-brand {
        font-size: 10px !important;
        line-height: 1.1 !important;
        margin: 0 !important;
        min-height: auto !important;
    }

    .product-name {
        font-size: 13px !important;
        line-height: 1.15 !important;
        font-weight: 900 !important;
        text-align: center;
        padding: 0 6px !important;
        margin: 0 !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        min-height: calc(2 * 1.15em) !important;
        max-height: calc(2 * 1.15em) !important;
    }

    .product-desc {
        font-size: 11px !important;
        line-height: 1.2 !important;
        margin: 0 !important;
        padding: 0 6px !important;
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
        min-height: calc(2 * 1.2em) !important;
        max-height: calc(2 * 1.2em) !important;
    }

    .price {
        font-size: 15px !important;
        line-height: 1.1 !important;
        margin-top: 6px !important;
        margin-bottom: 8px !important;
        padding-top: 0 !important;
    }

    .add-to-cart {
        font-size: 11px !important;
        padding: 12px 6px !important;
        line-height: 1.1 !important;
        margin-top: auto !important;
    }

    .flavor-shortcuts {
        gap: 8px;
        margin-bottom: 18px;
    }

    .flavor-shortcuts a {
        padding: 9px 14px;
        font-size: 13px;
    }

    .collection-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }
}
.products-grid-container {
    min-width: 0;
    padding-bottom: 24px;
}

.collection-pagination {
    margin-top: 10px;
    margin-bottom: 20px;
}

@media screen and (max-width: 480px) {
    .products-grid {
        gap: 10px;
    }

    .product-name {
        font-size: 12px !important;
    }

    .product-desc {
        font-size: 10px !important;
    }

    .price {
        font-size: 14px !important;
    }

    .add-to-cart {
        font-size: 10px !important;
    }
}
</style>

<div class="banner-container">
    <?php if (!isset($_SESSION['user_id'])): ?>
        <a href="compte.php" class="banner-link banner-desktop">
            <img src="img/banière image.jpg" alt="Rejoignez Big Monkey">
        </a>
        <a href="compte.php" class="banner-link banner-mobile">
            <img src="img/baniere-mobile.jpg" alt="Rejoignez Big Monkey Mobile">
        </a>
    <?php else: ?>
        <div class="slider-wrapper" aria-label="Bannières promotionnelles">
            <div class="slides">
                <div class="slide active">
                    <a href="collection.php?cat=nouveautes" class="banner-link banner-desktop">
                        <img src="img/slider1-pc.jpg" alt="Nouveautés E-liquides">
                    </a>
                    <a href="collection.php?cat=nouveautes" class="banner-link banner-mobile">
                        <img src="img/slider1-mobile.jpg" alt="Nouveautés E-liquides Mobile">
                    </a>
                </div>

                <div class="slide">
                    <a href="collection.php?cat=bons-plans" class="banner-link banner-desktop">
                        <img src="img/slider2-pc.jpg" alt="Promotions Matriel">
                    </a>
                    <a href="collection.php?cat=bons-plans" class="banner-link banner-mobile">
                        <img src="img/slider2-mobile.jpg" alt="Promotions Matériel Mobile">
                    </a>
                </div>
            </div>

            <div class="slider-dots" aria-hidden="true">
                <span class="dot active"></span>
                <span class="dot"></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="promo-bar-container">
    <div class="promo-bar-content">
        10% DE REMISE SUR VOTRE PREMIÈRE COMMANDE
    </div>
</div>

<section class="demo-assurance-compact">
    <div class="slider-container-wrapper">
        <button class="slider-arrow prev" onclick="moveSlide(-1)" aria-label="Slide précédent">&#10094;</button>

        <div class="slider-track">
            <div class="demo-item-compact">
                <img src="img/produit certifié-icon.svg" alt="Authentique">
                <div class="text-wrapper-compact">
                    <h3>100% AUTHENTIQUE</h3>
                    <p>Fioles premium certifiées importées de France.</p>
                </div>
            </div>

            <div class="demo-item-compact">
                <img src="img/livraison-icon.svg" alt="Livraison">
                <div class="text-wrapper-compact">
                    <h3>LIVRAISON EXPRESS</h3>
                    <p>Le jour même sur Antananarivo.</p>
                </div>
            </div>

            <div class="demo-item-compact">
                <img src="img/paiement-icon.svg" alt="Paiement">
                <div class="text-wrapper-compact">
                    <h3>PAIEMENT FLEXIBLE</h3>
                    <p>MVola, Orange Money, Airtel Money & Cash.</p>
                </div>
            </div>
        </div>

        <button class="slider-arrow next" onclick="moveSlide(1)" aria-label="Slide suivant">&#10095;</button>
    </div>
</section>

<div class="mobile-filter-trigger" id="openFilters">
    <i class="fas fa-filter" aria-hidden="true"></i> FILTRER LES PRODUITS
</div>

<div class="filter-overlay" id="filterOverlay"></div>

<div class="container-collection">
    <aside class="sidebar-filters" id="filterDrawer">
        <div class="filter-header-mobile">
            <span>FILTRES</span>
            <span id="closeFilters" style="cursor:pointer; font-size:1.5rem;" aria-label="Fermer">&times;</span>
        </div>

        <form action="collection.php" method="get" id="filtersForm">
            <input type="hidden" name="cat" value="<?= e($context['category']); ?>">

            <h2 class="filter-title">FILTRER PAR</h2>

            <?php if (!empty($context['brandOptions'])): ?>
                <div class="filter-group">
                    <h3>MARQUES</h3>
                    <ul>
                        <?php foreach ($context['brandOptions'] as $index => $brand): ?>
                            <?php $id = 'brand_' . $index; ?>
                            <li>
                                <input
                                    type="checkbox"
                                    name="brand[]"
                                    id="<?= e($id); ?>"
                                    value="<?= e($brand); ?>"
                                    <?= in_array($brand, $context['selectedBrands'], true) ? 'checked' : ''; ?>
                                >
                                <label for="<?= e($id); ?>"><?= e($brand); ?></label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php foreach ($context['attributeFilterKeys'] as $attributeKey): ?>
                <?php
                if ($context['isLockedFlavorCollection'] && $attributeKey === 'saveur_famille') {
                    continue;
                }

                $options = $context['attributeFilterOptions'][$attributeKey] ?? [];

                if (empty($options)) {
                    continue;
                }
                ?>
                <div class="filter-group">
                    <h3><?= e($context['attributeFilterLabels'][$attributeKey] ?? strtoupper(str_replace('_', ' ', $attributeKey))); ?></h3>
                    <ul>
                        <?php foreach ($options as $index => $option): ?>
                            <?php $id = $attributeKey . '_' . $index; ?>
                            <li>
                                <input
                                    type="checkbox"
                                    name="<?= e($attributeKey); ?>[]"
                                    id="<?= e($id); ?>"
                                    value="<?= e($option['value']); ?>"
                                    <?= in_array($option['value'], $context['selectedAttributeFilters'][$attributeKey] ?? [], true) ? 'checked' : ''; ?>
                                >
                                <label for="<?= e($id); ?>"><?= e($option['value']); ?> (<?= (int) $option['total']; ?>)</label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>

            <?php if ($context['searchQuery'] !== ''): ?>
                <input type="hidden" name="q" value="<?= e($context['searchQuery']); ?>">
            <?php endif; ?>

            <button class="btn-apply-filters" type="submit">APPLIQUER</button>
        </form>
    </aside>

    <main class="products-grid-container">
        <div class="collection-results-loader" id="collectionResultsLoader">Chargement des produits...</div>

        <div id="ajaxCollectionContent">
            <?= renderCollectionResultsHtml($context, $productsData, $_GET); ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.dot');
    const container = document.querySelector('.slides');
    let currentSlide = 0;

    if (container && slides.length >= 2 && dots.length >= 2) {
        window.setInterval(function () {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');

            currentSlide = (currentSlide + 1) % slides.length;
            container.style.transform = `translateX(-${currentSlide * 100}%)`;

            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }, 4000);
    }

    const openBtn = document.getElementById('openFilters');
    const closeBtn = document.getElementById('closeFilters');
    const drawer = document.getElementById('filterDrawer');
    const overlay = document.getElementById('filterOverlay');
    const filtersForm = document.getElementById('filtersForm');
    const ajaxContainer = document.getElementById('ajaxCollectionContent');
    const loader = document.getElementById('collectionResultsLoader');
    const productsContainer = document.querySelector('.products-grid-container');

    let ajaxController = null;
    let filterDebounce = null;

    function openFilters() {
        if (!drawer || !overlay) {
            return;
        }

        drawer.classList.add('is-open');
        overlay.classList.add('is-open');
        document.body.classList.add('filters-open');
    }

    function closeFilters() {
        if (!drawer || !overlay) {
            return;
        }

        drawer.classList.remove('is-open');
        overlay.classList.remove('is-open');
        document.body.classList.remove('filters-open');
    }

    function setLoadingState(isLoading) {
        if (!ajaxContainer || !loader) {
            return;
        }

        ajaxContainer.classList.toggle('ajax-loading', isLoading);
        loader.classList.toggle('is-active', isLoading);
    }

    function buildAjaxUrl(url) {
        const parsedUrl = new URL(url, window.location.origin);
        return 'ajax_collection_products.php?' + parsedUrl.searchParams.toString();
    }

    async function loadProductsFromUrl(url, pushToHistory = true, shouldScrollToProducts = false) {
        if (!ajaxContainer) {
            return;
        }

        if (ajaxController) {
            ajaxController.abort();
        }

        ajaxController = new AbortController();

        setLoadingState(true);

        try {
            const response = await fetch(buildAjaxUrl(url), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: ajaxController.signal
            });

            if (!response.ok) {
                throw new Error('Erreur HTTP');
            }

            const data = await response.json();

            if (!data.success || typeof data.html !== 'string') {
                throw new Error('Réponse AJAX invalide');
            }

            ajaxContainer.innerHTML = data.html;

            if (pushToHistory) {
                window.history.pushState({}, '', url);
            }

            if (typeof data.pageTitle === 'string' && data.pageTitle.trim() !== '') {
                document.title = data.pageTitle + ' | Big Monkey';
            }

            if (shouldScrollToProducts && productsContainer) {
                const rect = productsContainer.getBoundingClientRect();
                const absoluteTop = window.scrollY + rect.top - 20;

                window.scrollTo({
                    top: absoluteTop,
                    behavior: 'smooth'
                });
            }

            closeFilters();
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = url;
            }
        } finally {
            setLoadingState(false);
        }
    }

    function submitFiltersAjax() {
        if (!filtersForm) {
            return;
        }

        const formData = new FormData(filtersForm);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (String(value).trim() !== '') {
                params.append(key, value);
            }
        }

        params.delete('page');
        const url = 'collection.php?' + params.toString();
        loadProductsFromUrl(url, true, false);
    }

    if (filtersForm) {
        filtersForm.addEventListener('submit', function (event) {
            event.preventDefault();
            submitFiltersAjax();
        });

        filtersForm.addEventListener('change', function (event) {
            const target = event.target;

            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) {
                return;
            }

            clearTimeout(filterDebounce);
            filterDebounce = setTimeout(function () {
                submitFiltersAjax();
            }, 180);
        });
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.collection-pagination a, .flavor-shortcuts a');

        if (!link) {
            return;
        }

        const href = link.getAttribute('href');
        if (!href || href.indexOf('collection.php') === -1) {
            return;
        }

        event.preventDefault();
        loadProductsFromUrl(href, true, true);
    });

    window.addEventListener('popstate', function () {
        loadProductsFromUrl(window.location.href, false, false);
    });

    if (openBtn) {
        openBtn.addEventListener('click', openFilters);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeFilters);
    }

    if (overlay) {
        overlay.addEventListener('click', closeFilters);
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            closeFilters();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeFilters();
        }
    });
});
</script>

<?php include 'footer.php'; ?>
