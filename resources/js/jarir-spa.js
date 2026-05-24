class JarirSpa {
    constructor(options = {}) {
        // mode: 'json' (backend sends JSON via Output Buffering) or 'html' (pure client-side HTML parsing)
        this.mode = options.mode || 'html'; 
        this.contentSelector = options.contentSelector || '[data-spa-content]';
        
        this.contentEl = document.querySelector(this.contentSelector);
        if (!this.contentEl || !window.history || !window.fetch) {
            console.warn("JarirSpa: Browser unsupported or content container not found.");
            return;
        }

        this.navigating = false;
        this.prefetchPromises = {};
        this.prefetchTimer = null;
        this.requestController = null;

        this.init();
    }

    init() {
        history.replaceState({ url: window.location.href }, '', window.location.href);
        this.updateActiveNav();

        document.addEventListener('click', (event) => {
            let link = event.target.closest('a[href]');
            if (!this.shouldHandleClick(event, link)) return;
            event.preventDefault();
            this.navigateTo(link.href, { push: true, scroll: true });
        });

        window.addEventListener('popstate', () => {
            this.navigateTo(window.location.href, { push: false, scroll: true });
        });

        // Hover Prefetching for instant loads
        document.addEventListener('mouseover', (e) => {
            let link = e.target.closest('a[href]');
            if (!this.shouldHandleClick({ defaultPrevented: false, button: 0, metaKey: false, ctrlKey: false, shiftKey: false, altKey: false }, link)) return;
            if (this.prefetchPromises[link.href]) return;
            
            clearTimeout(this.prefetchTimer);
            this.prefetchTimer = setTimeout(() => {
                this.prefetchPromises[link.href] = this.fetchPage(link.href);
            }, 100);
        });

        document.addEventListener('mouseout', () => {
            clearTimeout(this.prefetchTimer);
        });
    }

    shouldHandleClick(event, link) {
        if (!link) return false;
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (!link.hasAttribute('data-spa')) return false;
        if (link.hasAttribute('download') || (link.getAttribute('target') && link.getAttribute('target') !== '_self')) return false;
        let href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return false;
        let targetUrl = new URL(link.href, window.location.origin);
        if (targetUrl.origin !== window.location.origin) return false;
        return true;
    }

    normalizePath(pathname) {
        if (!pathname) return '/';
        if (pathname.length > 1 && pathname.endsWith('/')) return pathname.slice(0, -1);
        return pathname;
    }

    updateActiveNav() {
        let currentPath = this.normalizePath(window.location.pathname);
        document.querySelectorAll('a[data-spa]').forEach((link) => {
            let linkPath = this.normalizePath(new URL(link.href, window.location.origin).pathname);
            link.classList.toggle('active', linkPath === currentPath);
        });
    }

    async fetchPage(url) {
        let headers = { 'Accept': 'text/html, application/json' };
        
        if (this.mode === 'json') {
            headers['X-Frontend-SPA'] = 'true';
            headers['Accept'] = 'application/json';
        }

        let response = await fetch(url, {
            method: 'GET',
            headers: headers,
            credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('Bad response');

        if (this.mode === 'json') {
            return await response.json();
        } else {
            // HTML mode (Client-side parsing)
            let htmlText = await response.text();
            let parser = new DOMParser();
            let doc = parser.parseFromString(htmlText, 'text/html');
            
            let contentEl = doc.querySelector(this.contentSelector);
            let content = contentEl ? contentEl.innerHTML : '';
            
            return {
                title: doc.title,
                content: content,
                style: '', // Extensibility for styles
                script: '' // Extensibility for scripts
            };
        }
    }

    async navigateTo(url, options = {}) {
        let shouldPush = options.push !== false;
        let shouldScroll = options.scroll !== false;

        if (this.navigating) return;
        this.navigating = true;

        if (this.requestController) this.requestController.abort();
        this.requestController = new AbortController();

        try {
            let payload = null;
            if (this.prefetchPromises[url]) {
                payload = await this.prefetchPromises[url];
                delete this.prefetchPromises[url];
            }

            if (!payload) {
                payload = await this.fetchPage(url);
            }

            if (payload.redirect) { window.location.href = payload.redirect; return; }
            if (!payload.content || !payload.content.trim()) { window.location.href = url; return; }

            // Replace content
            this.contentEl.innerHTML = payload.content;
            if (payload.title && payload.title.trim()) document.title = payload.title;

            // In a real robust implementation, we would inject scripts and styles here as well.

            if (shouldPush) history.pushState({ url: url }, '', url);
            if (shouldScroll) window.scrollTo({ top: 0, behavior: 'auto' });
            
            this.updateActiveNav();
        } catch (error) {
            if (error.name !== 'AbortError') window.location.href = url;
        } finally {
            this.navigating = false;
        }
    }
}

window.JarirSpa = JarirSpa;
