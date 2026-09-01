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
 * Optional hooks: pdoPage.callbacks.before / after (this === pdoPage).
 * After a successful load the script fires CustomEvent 'pdopage:load'
 * on document (detail: {config, response}).
 *
 * @file
 */
(function (window, document) {
    'use strict';

    // PHP startup may create pdoPage without instances. Always normalize.
    const pdoPage = window.pdoPage || {};
    pdoPage.callbacks = pdoPage.callbacks || {};
    pdoPage.keys = pdoPage.keys || {};
    pdoPage.configs = pdoPage.configs || {};
    pdoPage.instances = pdoPage.instances || {};
    window.pdoPage = pdoPage;

    /**
     * @param {string} href
     * @param {string} key
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
     * @param {Element|null} el
     * @param {string} html
     * @param {boolean} replace
     * @returns {void}
     */
    function applyHtml(el, html, replace) {
        if (!el) {
            return;
        }
        if (replace) {
            el.innerHTML = html || '';
            return;
        }
        if (!html) {
            return;
        }
        el.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Public URL helpers used by custom frontend_init_js / filters.
     * Same shape as the old jQuery-era Hash API (query string via pushState).
     */
    pdoPage.Hash = {
        /**
         * @returns {Object<string, *>}
         */
        get: function () {
            const vars = {};
            let raw;
            let splitter;
            if (!this.oldbrowser()) {
                const pos = window.location.href.indexOf('?');
                raw = pos !== -1
                    ? decodeURIComponent(window.location.href.substr(pos + 1)).replace(/\+/g, ' ')
                    : '';
                splitter = '&';
            } else {
                raw = decodeURIComponent(window.location.hash.substr(1)).replace(/\+/g, ' ');
                splitter = '/';
            }
            if (!raw.length) {
                return vars;
            }
            const hashes = raw.split(splitter);
            for (let i = 0; i < hashes.length; i++) {
                const pair = hashes[i].split('=');
                if (typeof pair[1] === 'undefined') {
                    vars.anchor = pair[0];
                    continue;
                }
                const matches = pair[0].match(/\[(.*?|)\]$/);
                if (matches) {
                    const key = pair[0].replace(matches[0], '');
                    if (!Object.prototype.hasOwnProperty.call(vars, key)) {
                        vars[key] = matches[1] === '' ? [] : {};
                    }
                    if (vars[key] instanceof Array) {
                        vars[key].push(pair[1]);
                    } else {
                        vars[key][matches[1]] = pair[1];
                    }
                } else {
                    vars[pair[0]] = pair[1];
                }
            }
            return vars;
        },

        /**
         * @param {Object<string, *>} vars
         * @returns {void}
         */
        set: function (vars) {
            let hash = '';
            Object.keys(vars).forEach(function (i) {
                const value = vars[i];
                if (value && typeof value === 'object') {
                    Object.keys(value).forEach(function (j) {
                        if (value instanceof Array) {
                            hash += '&' + i + '[]=' + value[j];
                        } else {
                            hash += '&' + i + '[' + j + ']=' + value[j];
                        }
                    });
                } else {
                    hash += '&' + i + '=' + value;
                }
            });
            if (!this.oldbrowser()) {
                const query = hash.length ? '?' + hash.substr(1) : '';
                const url = document.location.pathname + query;
                window.history.pushState({pdoPage: url}, '', url);
            } else {
                window.location.hash = hash.substr(1);
            }
        },

        /**
         * @param {string} key
         * @param {*} val
         * @returns {void}
         */
        add: function (key, val) {
            const hash = this.get();
            hash[key] = val;
            this.set(hash);
        },

        /**
         * @param {string} key
         * @returns {void}
         */
        remove: function (key) {
            const hash = this.get();
            delete hash[key];
            this.set(hash);
        },

        /** @returns {void} */
        clear: function () {
            this.set({});
        },

        /** @returns {boolean} */
        oldbrowser: function () {
            return !(window.history && history.pushState);
        }
    };

    /**
     * @param {string} key
     * @param {number|string} page
     * @returns {void}
     */
    function setHistory(key, page) {
        if (Number(page) === 1) {
            pdoPage.Hash.remove(key);
        } else {
            pdoPage.Hash.add(key, page);
        }
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
        this.requestId = 0;
        this.observer = null;
        this.sentinel = null;
        this.bound = false;
        this.previousPage = null;
    }

    /** @returns {Element|null} */
    Controller.prototype.getWrapper = function () {
        return document.querySelector(this.config.wrapper);
    };

    /** @returns {Element|null} */
    Controller.prototype.getRows = function () {
        return document.querySelector(this.config.rows);
    };

    /** @returns {Element|null} */
    Controller.prototype.getPagination = function () {
        return document.querySelector(this.config.pagination);
    };

    /**
     * @returns {void}
     */
    Controller.prototype.bind = function () {
        if (this.bound) {
            return;
        }
        this.bound = true;

        switch (this.config.mode) {
            case 'default':
                this.bindDefault();
                break;
            case 'button':
                this.bindPagerChrome();
                this.bindButton();
                break;
            case 'scroll':
                this.bindPagerChrome();
                this.bindScroll();
                break;
        }
    };

    /**
     * Clickable pager + optional history/popstate.
     * @returns {void}
     */
    Controller.prototype.bindDefault = function () {
        const self = this;
        const config = this.config;
        const key = this.key;

        document.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (!link || !link.matches(config.link)) {
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

        if (!config.history) {
            return;
        }
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.pdoPage) {
                self.load(e.state.pdoPage, 'replace');
            }
        });
        history.replaceState({pdoPage: window.location.href}, '');
    };

    /**
     * Sticky pager when history is on; hide pager when history is off.
     * @returns {void}
     */
    Controller.prototype.bindPagerChrome = function () {
        const pagination = this.getPagination();
        if (!pagination) {
            return;
        }
        if (this.config.history) {
            pagination.classList.add('pdopage-sticky');
        } else {
            pagination.hidden = true;
        }
    };

    /**
     * @returns {void}
     */
    Controller.prototype.bindButton = function () {
        const self = this;
        const config = this.config;
        const key = this.key;
        const rows = this.getRows();
        if (rows && config.moreTpl) {
            rows.insertAdjacentHTML('afterend', config.moreTpl);
        }
        const current = Number(pdoPage.keys[key] || 1);
        const hasMore = Array.from(document.querySelectorAll(config.link)).some(function (link) {
            return pageFromHref(link.href, key) > current;
        });
        const more = document.querySelector(config.more);
        if (more && !hasMore) {
            more.hidden = true;
        }
        document.addEventListener('click', function (e) {
            const btn = e.target.closest(config.more);
            if (!btn) {
                return;
            }
            e.preventDefault();
            self.addPage();
        });
    };

    /**
     * Sentinel under the rows list triggers the next page.
     * @returns {void}
     */
    Controller.prototype.bindScroll = function () {
        const self = this;
        const rows = this.getRows();
        if (!rows || !rows.parentNode) {
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

    /** @returns {void} */
    Controller.prototype.stopScroll = function () {
        this.reached = true;
        if (this.observer && this.sentinel) {
            this.observer.unobserve(this.sentinel);
        }
    };

    /**
     * True when the scroll sentinel is still in the viewport.
     * @returns {boolean}
     */
    Controller.prototype.sentinelVisible = function () {
        if (!this.sentinel) {
            return false;
        }
        const rect = this.sentinel.getBoundingClientRect();
        return rect.top < window.innerHeight && rect.bottom > 0;
    };

    /**
     * After append: either stop, or keep loading while the sentinel stays visible.
     *
     * @param {{page?: number, pages?: number}} data
     * @returns {void}
     */
    Controller.prototype.continueScrollIfNeeded = function (data) {
        if (Number(data.pages) === Number(data.page) || Number(data.pages) === 0) {
            this.stopScroll();
            return;
        }
        this.reached = false;
        if (this.sentinelVisible()) {
            this.reached = true;
            this.addPage();
        }
    };

    /**
     * @returns {Element|null}
     */
    Controller.prototype.findNextLink = function () {
        const key = this.key;
        const current = Number(pdoPage.keys[key] || 1);
        const links = document.querySelectorAll(this.config.link);
        for (let i = 0; i < links.length; i++) {
            if (pageFromHref(links[i].href, key) > current) {
                return links[i];
            }
        }
        return null;
    };

    /**
     * @returns {void}
     */
    Controller.prototype.addPage = function () {
        const next = this.findNextLink();
        if (!next) {
            this.stopScroll();
            return;
        }
        const page = pageFromHref(next.href, this.key);
        if (this.config.history) {
            setHistory(this.key, page);
        }
        this.load(next.href, 'append');
    };

    /**
     * Mode-specific work after the DOM has been updated.
     *
     * @param {Object} data
     * @param {'replace'|'append'|'force'} applyMode
     * @returns {void}
     */
    Controller.prototype.afterApply = function (data, applyMode) {
        const config = this.config;

        if (applyMode === 'force') {
            const page = Number(data.page) || 1;
            pdoPage.keys[this.key] = page;
            if (config.history) {
                setHistory(this.key, page);
            }
        }

        if (config.mode === 'button' && (applyMode === 'append' || applyMode === 'force')) {
            const more = document.querySelector(config.more);
            if (more) {
                more.hidden = Number(data.pages) === Number(data.page) || Number(data.pages) === 0;
            }
            return;
        }

        if (config.mode === 'scroll' && applyMode === 'append') {
            this.continueScrollIfNeeded(data);
        }
    };

    /**
     * Roll back optimistic page state after abort-safe failure.
     * @returns {void}
     */
    Controller.prototype.rollback = function () {
        if (this.previousPage !== null) {
            pdoPage.keys[this.key] = this.previousPage;
            if (this.config.history) {
                setHistory(this.key, this.previousPage);
            }
            this.previousPage = null;
        }
        const wrapper = this.getWrapper();
        if (wrapper) {
            wrapper.classList.remove('loading');
        }
        this.reached = false;
    };

    /**
     * Apply connector JSON to the DOM.
     *
     * @param {Object} data
     * @param {'replace'|'append'|'force'} applyMode
     * @returns {void}
     */
    Controller.prototype.applyResponse = function (data, applyMode) {
        const rows = this.getRows();
        const pagination = this.getPagination();
        applyHtml(pagination, data.pagination || '', true);
        if (applyMode === 'append') {
            applyHtml(rows, data.output || '', false);
        } else {
            applyHtml(rows, data.output || '', true);
        }
        this.afterApply(data, applyMode);
    };

    /**
     * POST to connector.php and update the DOM.
     * Latest request wins: a new load() aborts the previous fetch.
     *
     * @param {string} href
     * @param {'replace'|'append'|'force'} [applyMode='replace']
     * @returns {void}
     */
    Controller.prototype.load = function (href, applyMode) {
        const self = this;
        const config = this.config;
        const key = this.key;
        const wrapper = this.getWrapper();
        const page = pageFromHref(href, key);
        applyMode = applyMode || 'replace';

        if (Number(pdoPage.keys[key]) === page && applyMode !== 'force') {
            return;
        }

        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();
        const requestId = ++this.requestId;

        this.busy = true;
        this.previousPage = Number(pdoPage.keys[key] || 1);
        pdoPage.keys[key] = page;

        if (typeof pdoPage.callbacks.before === 'function') {
            pdoPage.callbacks.before.apply(pdoPage, [config]);
        } else if (wrapper && config.mode !== 'scroll') {
            wrapper.classList.add('loading');
        }

        const params = new URLSearchParams(window.location.search);
        Object.keys(pdoPage.keys).forEach(function (otherKey) {
            if (otherKey !== key) {
                params.delete(otherKey);
            }
        });
        params.set(key, String(page));
        params.set('pageId', String(config.pageId));
        params.set('hash', String(config.hash));

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
            if (requestId !== self.requestId) {
                return;
            }
            if (!data || typeof data !== 'object' || !Object.prototype.hasOwnProperty.call(data, 'total')) {
                self.rollback();
                return;
            }
            // total may be 0 on an empty result set; still apply markup
            self.previousPage = null;
            self.applyResponse(data, applyMode);

            if (typeof pdoPage.callbacks.after === 'function') {
                pdoPage.callbacks.after.apply(pdoPage, [config, data]);
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
            if (requestId !== self.requestId) {
                return;
            }
            if (error && error.name === 'AbortError') {
                return;
            }
            self.rollback();
        }).then(function () {
            if (requestId === self.requestId) {
                self.busy = false;
            }
        });
    };

    /**
     * @param {Object} config
     * @returns {Controller}
     */
    pdoPage.initialize = function (config) {
        const key = config.pageVarKey;
        if (pdoPage.instances[key]) {
            return pdoPage.instances[key];
        }
        const params = pdoPage.Hash.get();
        const current = params[key];
        pdoPage.keys[key] = current !== undefined && current !== null && current !== ''
            ? Number(current)
            : 1;
        pdoPage.configs[key] = config;
        const controller = new Controller(config);
        pdoPage.instances[key] = controller;
        controller.bind();
        return controller;
    };

    /**
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
