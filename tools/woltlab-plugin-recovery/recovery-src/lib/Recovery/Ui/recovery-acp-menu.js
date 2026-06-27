/**
 * ACP-Seitenmenü für das Recovery Tool (ohne vollständiges WCF-Bootstrap).
 * Desktop: Toggle wie WoltLabSuite/Core/Acp/Ui/Page/Menu.js
 * Mobil: Overlay aus acpPageMenu / acpPageSubMenu (wie AcpUiPageMenuMainBackend)
 */
(function () {
    'use strict';

    var pageContainer = document.getElementById('pageContainer');
    var acpPageMenu = document.getElementById('acpPageMenu');
    var acpPageSubMenu = document.getElementById('acpPageSubMenu');
    if (!pageContainer || !acpPageMenu || !acpPageSubMenu) {
        return;
    }

    var menuItems = new Map();
    var menuContainers = new Map();
    var activeMenuItem = '';

    document.querySelectorAll('.acpPageMenuLink').forEach(function (link) {
        var menuItem = link.dataset.menuItem;
        if (!menuItem) {
            return;
        }
        if (link.classList.contains('active')) {
            activeMenuItem = menuItem;
        }
        link.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            toggleMenuItem(menuItem, link);
        });
        menuItems.set(menuItem, link);
    });

    document.querySelectorAll('.acpPageSubMenuCategoryList').forEach(function (container) {
        var menuItem = container.dataset.menuItem;
        if (menuItem) {
            menuContainers.set(menuItem, container);
        }
    });

    if (activeMenuItem && menuContainers.has(activeMenuItem)) {
        pageContainer.classList.add('acpPageSubMenuActive');
    }

    function toggleMenuItem(menuItem, link) {
        var subMenuActive = false;
        if (activeMenuItem) {
            var prev = menuItems.get(activeMenuItem);
            if (prev) {
                prev.classList.remove('active');
            }
            var prevList = menuContainers.get(activeMenuItem);
            if (prevList) {
                prevList.classList.remove('active');
            }
        }
        if (activeMenuItem === menuItem) {
            activeMenuItem = '';
        } else {
            link.classList.add('active');
            var list = menuContainers.get(menuItem);
            if (list) {
                list.classList.add('active');
            }
            activeMenuItem = menuItem;
            subMenuActive = true;
        }
        pageContainer.classList.toggle('acpPageSubMenuActive', subMenuActive);
    }

    /* —— Mobil: pageHeaderMenuMobile + #mainMenu —— */
    var mainMenuRoot = document.querySelector('.mainMenu');
    var mobileBtn = document.querySelector('.pageHeaderMenuMobile');
    if (!mainMenuRoot || !mobileBtn) {
        return;
    }

    var mobileOverlay = null;
    var mobileOpen = false;

    function buildMobileMenu() {
        var container = document.createElement('div');
        container.className = 'pageMenuMainContainer';
        container.setAttribute('role', 'navigation');
        container.setAttribute('aria-label', 'Plugin Recovery');

        var nav = document.createElement('nav');
        nav.className = 'pageMenuMainNavigation';

        var ul = document.createElement('ul');
        ul.className = 'pageMenuMainItemList';

        menuItems.forEach(function (link, menuItem) {
            var sectionLi = document.createElement('li');
            sectionLi.className = 'pageMenuMainItem pageMenuMainItemExpandable';
            sectionLi.dataset.depth = '0';

            var label = document.createElement('a');
            label.className = 'pageMenuMainItemLabel';
            label.href = '#';
            var icon = link.querySelector('fa-icon');
            if (icon) {
                label.appendChild(icon.cloneNode(true));
                var span = document.createElement('span');
                span.textContent = link.querySelector('.acpPageMenuItemLabel')?.textContent || '';
                label.appendChild(span);
            } else {
                label.textContent = link.querySelector('.acpPageMenuItemLabel')?.textContent || menuItem;
            }

            var toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'pageMenuMainItemToggle';
            toggleBtn.setAttribute('aria-expanded', 'false');

            var subUl = document.createElement('ul');
            subUl.className = 'pageMenuMainItemList';
            subUl.hidden = true;

            var categoryList = menuContainers.get(menuItem);
            if (categoryList) {
                categoryList.querySelectorAll('.acpPageSubMenuCategory').forEach(function (category) {
                    var catTitle = category.querySelector('span');
                    if (catTitle && catTitle.textContent.trim() !== '') {
                        var catLi = document.createElement('li');
                        catLi.className = 'pageMenuMainItem';
                        catLi.dataset.depth = '1';
                        var catLabel = document.createElement('span');
                        catLabel.className = 'pageMenuMainItemLabel';
                        catLabel.textContent = catTitle.textContent.trim();
                        catLi.appendChild(catLabel);
                        subUl.appendChild(catLi);
                    }
                    category.querySelectorAll('.acpPageSubMenuItemList > li').forEach(function (itemLi) {
                        var a = itemLi.querySelector('a.acpPageSubMenuLink');
                        if (!a) {
                            return;
                        }
                        var entry = document.createElement('li');
                        entry.className = 'pageMenuMainItem';
                        entry.dataset.depth = '2';
                        var entryLink = document.createElement('a');
                        entryLink.className = 'pageMenuMainItemLink';
                        entryLink.href = a.href;
                        entryLink.textContent = a.textContent.trim();
                        if (itemLi.classList.contains('active') || a.classList.contains('active')) {
                            entryLink.setAttribute('aria-current', 'page');
                        }
                        entry.appendChild(entryLink);
                        subUl.appendChild(entry);
                    });
                });
            }

            label.addEventListener('click', function (e) {
                e.preventDefault();
                toggleBtn.click();
            });

            toggleBtn.addEventListener('click', function () {
                var expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                toggleBtn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                subUl.hidden = expanded;
            });

            sectionLi.appendChild(label);
            sectionLi.appendChild(toggleBtn);
            sectionLi.appendChild(subUl);
            ul.appendChild(sectionLi);

            if (menuItem === activeMenuItem) {
                toggleBtn.setAttribute('aria-expanded', 'true');
                subUl.hidden = false;
            }
        });

        nav.appendChild(ul);
        container.appendChild(nav);
        return container;
    }

    function ensureOverlay() {
        if (mobileOverlay) {
            return mobileOverlay;
        }
        mobileOverlay = document.createElement('div');
        mobileOverlay.id = 'recoveryMobileMenuOverlay';
        mobileOverlay.className = 'recovery-mobile-menu-overlay';
        mobileOverlay.hidden = true;
        mobileOverlay.addEventListener('click', function (e) {
            if (e.target === mobileOverlay) {
                closeMobileMenu();
            }
        });
        document.body.appendChild(mobileOverlay);
        return mobileOverlay;
    }

    function openMobileMenu() {
        var overlay = ensureOverlay();
        overlay.innerHTML = '';
        overlay.appendChild(buildMobileMenu());
        overlay.hidden = false;
        mobileOpen = true;
        mobileBtn.setAttribute('aria-expanded', 'true');
        mobileBtn.classList.add('active');
        document.body.classList.add('recovery-mobile-menu-open');
    }

    function closeMobileMenu() {
        if (!mobileOverlay) {
            return;
        }
        mobileOverlay.hidden = true;
        mobileOpen = false;
        mobileBtn.setAttribute('aria-expanded', 'false');
        mobileBtn.classList.remove('active');
        document.body.classList.remove('recovery-mobile-menu-open');
    }

    mobileBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (mobileOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mobileOpen) {
            closeMobileMenu();
        }
    });
})();
