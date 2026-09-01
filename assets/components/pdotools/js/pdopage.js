(function (window, document) {
    'use strict';

    if (typeof window.pdoPage === 'undefined') {
        window.pdoPage = {callbacks: {}, keys: {}, configs: {}, instances: {}};
    }

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return Array.from((root || document).querySelectorAll(selector));
    }

    function pageFromHref(href, key) {
        try {
            const value = new URL(href, window.location.origin).searchParams.get(key);
            return value ? Number(value) : 1;
        } catch (e) {
            return 1;
        }
    }

    function matchesSelector(el, selector) {
        return qsa(selector).includes(el);
    }

    function isMoreTarget(target, moreSelector) {
        const more = qs(moreSelector);
        if (!more) {
            return null;
        }
        if (target === more || more.contains(target)) {
            return more;
        }
        return null;
    }

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

    Controller.prototype.getWrapper = function () {
        return qs(this.config.wrapper);
    };

    Controller.prototype.getRows = function () {
        return qs(this.config.rows);
    };

    Controller.prototype.getPagination = function () {
        return qs(this.config.pagination);
    };

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

    Controller.prototype.appendHtml = function (el, html) {
        if (!el || !html) {
            return;
        }
        const template = document.createElement('template');
        template.innerHTML = html;
        el.appendChild(template.content);
    };

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

    pdoPage.addPage = function (config) {
        const controller = pdoPage.instances[config.pageVarKey];
        if (controller) {
            controller.addPage();
        }
    };

    pdoPage.loadPage = function (href, config, mode) {
        const controller = pdoPage.instances[config.pageVarKey];
        if (controller) {
            controller.load(href, mode || 'replace');
        }
    };

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
