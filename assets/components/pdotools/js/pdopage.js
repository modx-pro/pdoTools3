/**
 * Vanilla frontend for the pdoPage snippet (ajax pagination).
 *
 * PHP registers this file and calls pdoPage.initialize(config).
 * Requests go to connector.php with hash + pageId. The snippet only
 * treats the call as ajax when X-Requested-With is XMLHttpRequest.
 *
 * Modes: default (click pager links), button (load more), scroll
 * (IntersectionObserver, with a window scroll fallback).
 *
 * Optional hooks: pdoPage.callbacks.before / after.
 * After a successful load the script fires CustomEvent 'pdopage:load'
 * on document (detail: {config, response}).
 *
 * @file
 */
(function (window, document) {
    'use strict';

    if (typeof window.pdoPage === 'undefined') {
        window.pdoPage = {callbacks: {}, keys: {}, configs: {}, instances: {}};
    }

    /**
     * @param {string} selector
     * @param {ParentNode} [root]
     * @returns {Element|null}
     */
    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    /**
     * @param {string} selector
     * @param {ParentNode} [root]
     * @returns {Element[]}
     */
    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    /**
     * Read the page number from a pagination href.
     * Missing or invalid values become 1.
     *
     * @param {string} href
     * @param {string} key Query param name (usually pageVarKey).
     * @returns {number}
     */
    function pageFromHref(href, key) {
        try {
            const value = new URL(href, window.location.origin).searchParams.get(key);
            return value ? Number(value) : 1;
        } catch (e) {
            return 1;
        }
    }

    /**
     * True when el is one of the nodes matched by selector.
     * Needed because closest() does not accept compound selectors
     * like "#pdopage .pagination a".
     *
     * @param {Element} el
     * @param {string} selector
     * @returns {boolean}
     */
    function matchesSelector(el, selector) {
        return qsa(selector).includes(el);
    }

    /**
     * Resolve a click target to the "load more" element, if any.
     *
     * @param {EventTarget|null} target
     * @param {string} moreSelector
     * @returns {Element|null}
     */
    function isMoreTarget(target, moreSelector) {
        const more = qs(moreSelector);
        if (!more || !(target instanceof Node)) {
            return null;
        }
        if (target === more || more.contains(target)) {
            return more;
        }
        return null;
    }

    /**
     * Push the current page into the query string. Page 1 removes the key.
     *
     * @param {string} key
     * @param {number|string} page
     * @returns {void}
     */
    function setHistory(key, page) {
        const params = new URLSearchParams(window.location.search);
        if (Number(page) === 1) {
            params.delete(key);
        } else {
            params.set(key, String(page));
        }
        const query = params.toString();
        const url = window.location.pathname + (query ? '?' + query : '');
        window.history.pushState({pdoPage: url}, '', url);
    }

    /**
     * One pdoPage block on the page (keyed by pageVarKey).
     *
     * @param {Object} config Config from Paginator::loadJsCss().
     * @constructor
     */
    function Controller(config) {
        this.config = config;
        this.key = config.pageVarKey;
        this.busy = false;
        this.reached = false;
        this.abortController = null;
        this.observer = null;
        this.sentinel = null;
        this.bound = false;
    }

    /** @returns {Element|null} */
    Controller.prototype.getWrapper = function () {
        return qs(this.config.wrapper);
    };

    /** @returns {Element|null} */
    Controller.prototype.getRows = function () {
        return qs(this.config.rows);
    };

    /** @returns {Element|null} */
    Controller.prototype.getPagination = function () {
        return qs(this.config.pagination);
    };

    /**
     * Attach listeners once per instance.
     * @returns {void}
     */
    Controller.prototype.bind = function () {
        if (this.bound) {
            return;
        }
        this.bound = true;

        const self = this;
        const config = this.config;
        const key = this.key;

        switch (config.mode) {
            case 'default':
                document.addEventListener('click', function (e) {
                    const link = e.target.closest('a');
                    if (!link || !matchesSelector(link, config.link)) {
                        return;
                    }
                    e.preventDefault();
                    const page = pageFromHref(link.href, key);
                    if (Number(pdoPage.keys[key]) === page) {
                        return;
                    }
                    if (config.history) {
                        setHistory(key, page);
                    }
                    self.load(link.href, 'replace');
                });

                if (config.history) {
                    window.addEventListener('popstate', function (e) {
                        if (e.state && e.state.pdoPage) {
                            self.load(e.state.pdoPage, 'replace');
                        }
                    });
                    history.replaceState({pdoPage: window.location.href}, '');
                }
                break;

            case 'scroll':
            case 'button':
                // history off: hide the pager; history on: stick it with CSS
                if (config.history) {
                    const pagination = this.getPagination();
                    if (pagination) {
                        pagination.classList.add('pdopage-sticky');
                    }
                } else {
                    const hiddenPagination = this.getPagination();
                    if (hiddenPagination) {
                        hiddenPagination.hidden = true;
                    }
                }

                if (config.mode === 'button') {
                    const rows = this.getRows();
                    if (rows && config.moreTpl) {
                        rows.insertAdjacentHTML('afterend', config.moreTpl);
                    }
                    const hasMore = qsa(config.link).some(function (link) {
                        return pageFromHref(link.href, key) > Number(pdoPage.keys[key] || 1);
                    });
                    const more = qs(config.more);
                    if (more && !hasMore) {
                        more.hidden = true;
                    }
                    document.addEventListener('click', function (e) {
                        const btn = isMoreTarget(e.target, config.more);
                        if (!btn) {
                            return;
                        }
                        e.preventDefault();
                        self.addPage();
                    });
                } else {
                    this.bindScroll();
                }
                break;
        }
    };

    /**
     * Watch a sentinel under the rows list and load the next page.
     * @returns {void}
     */
    Controller.prototype.bindScroll = function () {
        const self = this;
        const rows = this.getRows();
        if (!rows) {
            return;
        }

        this.sentinel = document.createElement('div');
        this.sentinel.className = 'pdopage-sentinel';
        this.sentinel.setAttribute('aria-hidden', 'true');
        rows.parentNode.insertBefore(this.sentinel, rows.nextSibling);

        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && !self.busy && !self.reached) {
                        self.reached = true;
                        self.addPage();
                    }
                });
            }, {root: null, rootMargin: '0px', threshold: 0});
            this.observer.observe(this.sentinel);
            return;
        }

        // Older browsers without IntersectionObserver
        const onScroll = function () {
            if (self.busy || self.reached) {
                return;
            }
            const wrapper = self.getWrapper();
            if (!wrapper) {
                return;
            }
            if (window.scrollY > wrapper.offsetHeight - window.innerHeight) {
                self.reached = true;
                self.addPage();
            }
        };
        window.addEventListener('scroll', onScroll, {passive: true});
        window.addEventListener('load', onScroll);
    };

    /**
     * Find the next pager link and append that page.
     * @returns {void}
     */
    Controller.prototype.addPage = function () {
        const key = this.key;
        const current = Number(pdoPage.keys[key] || 1);
        let next = null;
        const links = qsa(this.config.link);
        for (let i = 0; i < links.length; i++) {
            if (pageFromHref(links[i].href, key) > current) {
                next = links[i];
                break;
            }
        }
        if (!next) {
            // Nothing left: stop observing so we do not spin
            this.reached = true;
            if (this.observer && this.sentinel) {
                this.observer.unobserve(this.sentinel);
            }
            return;
        }
        const page = pageFromHref(next.href, key);
        if (this.config.history) {
            setHistory(key, page);
        }
        this.load(next.href, 'append');
    };

    /**
     * Replace element contents with HTML from the connector response.
     * Same trust model as the old jQuery .html() path: markup is
     * rendered by MODX chunks on the server.
     *
     * @param {Element|null} el
     * @param {string} html
     * @returns {void}
     */
    Controller.prototype.setHtml = function (el, html) {
        if (!el) {
            return;
        }
        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }
        if (!html) {
            return;
        }
        const template = document.createElement('template');
        template.innerHTML = html;
        el.appendChild(template.content);
    };

    /**
     * Append HTML to an element (scroll / button modes).
     *
     * @param {Element|null} el
     * @param {string} html
     * @returns {void}
     */
    Controller.prototype.appendHtml = function (el, html) {
        if (!el || !html) {
            return;
        }
        const template = document.createElement('template');
        template.innerHTML = html;
        el.appendChild(template.content);
    };

    /**
     * POST to connector.php and update the DOM.
     *
     * @param {string} href Pager link used to derive the page number.
     * @param {'replace'|'append'|'force'} [mode='replace']
     * @returns {void}
     */
    Controller.prototype.load = function (href, mode) {
        const self = this;
        const config = this.config;
        const key = this.key;
        const wrapper = this.getWrapper();
        const rows = this.getRows();
        const pagination = this.getPagination();
        const page = pageFromHref(href, key);
        mode = mode || 'replace';

        if (Number(pdoPage.keys[key]) === page && mode !== 'force') {
            return;
        }
        if (this.busy) {
            return;
        }

        this.busy = true;
        pdoPage.keys[key] = page;

        if (typeof pdoPage.callbacks.before === 'function') {
            pdoPage.callbacks.before.apply(this, [config]);
        } else if (wrapper && config.mode !== 'scroll') {
            wrapper.classList.add('loading');
        }

        // Keep other query params; drop sibling pdoPage keys from this page
        const params = new URLSearchParams(window.location.search);
        Object.keys(pdoPage.keys).forEach(function (otherKey) {
            if (otherKey !== key) {
                params.delete(otherKey);
            }
        });
        params.set(key, String(page));
        params.set('pageId', String(config.pageId));
        params.set('hash', String(config.hash));

        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        fetch(config.connectorUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString(),
            credentials: 'same-origin',
            signal: this.abortController.signal
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('pdoPage request failed');
            }
            return response.json();
        }).then(function (data) {
            // Snippet ajax branch always includes total when there is work to show
            if (!data || !data.total) {
                return;
            }
            self.setHtml(pagination, data.pagination || '');
            if (mode === 'append') {
                self.appendHtml(rows, data.output || '');
                if (config.mode === 'button') {
                    const more = qs(config.more);
                    if (more) {
                        more.hidden = Number(data.pages) === Number(data.page) || Number(data.pages) === 0;
                    }
                } else if (config.mode === 'scroll') {
                    if (Number(data.pages) === Number(data.page) || Number(data.pages) === 0) {
                        self.reached = true;
                        if (self.observer && self.sentinel) {
                            self.observer.unobserve(self.sentinel);
                        }
                    } else {
                        self.reached = false;
                    }
                }
            } else {
                self.setHtml(rows, data.output || '');
            }

            if (typeof pdoPage.callbacks.after === 'function') {
                pdoPage.callbacks.after.apply(self, [config, data]);
            } else if (wrapper) {
                wrapper.classList.remove('loading');
                if (config.mode === 'default' && config.scrollTop !== false) {
                    const top = wrapper.getBoundingClientRect().top + window.scrollY - 50;
                    window.scrollTo(0, top > 0 ? top : 0);
                }
            }

            pdoPage.updateTitle(config, data);
            document.dispatchEvent(new CustomEvent('pdopage:load', {
                detail: {config: config, response: data}
            }));
        }).catch(function (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            if (wrapper) {
                wrapper.classList.remove('loading');
            }
            self.reached = false;
        }).then(function () {
            self.busy = false;
        });
    };

    /**
     * Entry point used by PHP frontend_init_js.
     * One Controller per pageVarKey.
     *
     * @param {Object} config
     * @returns {Controller}
     */
    pdoPage.initialize = function (config) {
        const key = config.pageVarKey;
        if (pdoPage.instances[key]) {
            return pdoPage.instances[key];
        }
        const params = new URLSearchParams(window.location.search);
        const current = params.get(key);
        pdoPage.keys[key] = current ? Number(current) : 1;
        pdoPage.configs[key] = config;
        const controller = new Controller(config);
        pdoPage.instances[key] = controller;
        controller.bind();
        return controller;
    };

    /**
     * Public helper: load the next page for an existing instance.
     *
     * @param {Object} config
     * @returns {void}
     */
    pdoPage.addPage = function (config) {
        const controller = pdoPage.instances[config.pageVarKey];
        if (controller) {
            controller.addPage();
        }
    };

    /**
     * Public helper: load a specific href for an existing instance.
     *
     * @param {string} href
     * @param {Object} config
     * @param {'replace'|'append'|'force'} [mode]
     * @returns {void}
     */
    pdoPage.loadPage = function (href, config, mode) {
        const controller = pdoPage.instances[config.pageVarKey];
        if (controller) {
            controller.load(href, mode || 'replace');
        }
    };

    /**
     * Sync document.title with the pdoTitle snippet config, if present.
     *
     * @param {Object} config
     * @param {{page?: number, pages?: number}} response
     * @returns {void}
     */
    pdoPage.updateTitle = function (config, response) {
        if (typeof window.pdoTitle === 'undefined') {
            return;
        }
        const separator = pdoTitle.separator || ' / ';
        const tpl = pdoTitle.tpl;
        const title = [];
        const items = document.title.split(separator);
        const pcre = new RegExp('^' + tpl.split(' ')[0] + ' ');
        for (let i = 0; i < items.length; i++) {
            if (i === 1 && response.page && response.page > 1) {
                title.push(tpl.replace('{page}', response.page).replace('{pageCount}', response.pages));
            }
            if (!items[i].match(pcre)) {
                title.push(items[i]);
            }
        }
        document.title = title.join(separator);
    };
})(window, document);
