/**
 * Component Library: "Components" panel in Grav 2's page editor.
 *
 * Admin2 mounts this element when the editor's Components button is pressed,
 * with `route`, `lang` and `type` attributes for the page being edited. It
 * lists the library components the page's template offers, which of them have
 * content on this page, the tab each lives on, and how the theme renders it.
 *
 * Data: GET {api}/component-library/page?route=... (classes/Api/UsageController.php).
 * Events: `badge` ({ count }) keeps the toolbar count current; `close`.
 * Colours come from Admin2's theme variables, so light and dark mode follow it.
 */
(() => {
    const TAG = window.__GRAV_PANEL_TAG || 'grav-component-library--panel';
    if (customElements.get(TAG)) {
        return;
    }

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const STYLE = `
        :host { display: flex; flex-direction: column; height: 100%; color: var(--foreground, #09090b); font: inherit; font-size: 14px; }
        header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 16px 20px 12px; border-bottom: 1px solid var(--border, #e4e4e7); }
        h2 { margin: 0; font-size: 16px; font-weight: 600; }
        .sub { margin: 4px 0 0; color: var(--muted-foreground, #71717a); font-size: 12px; }
        .close { border: 0; background: transparent; color: var(--muted-foreground, #71717a); cursor: pointer; font-size: 20px; line-height: 1; padding: 2px 6px; border-radius: var(--radius-sm, 4px); }
        .close:hover { background: var(--accent, #f4f4f5); color: var(--foreground, #09090b); }
        .body { flex: 1; overflow-y: auto; padding: 12px 20px 20px; }
        .group { margin: 8px 0 4px; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted-foreground, #71717a); }
        ul { list-style: none; margin: 0 0 12px; padding: 0; }
        li { border: 1px solid var(--border, #e4e4e7); border-radius: var(--radius-md, 6px); background: var(--card, #fff); padding: 10px 12px; margin-top: 8px; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .name { font-weight: 600; }
        .pill { flex-shrink: 0; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
        .pill.on { background: color-mix(in srgb, #22c55e 15%, transparent); color: color-mix(in srgb, #16a34a 85%, var(--foreground, #09090b)); }
        .pill.off { background: var(--muted, #f4f4f5); color: var(--muted-foreground, #71717a); }
        .desc { margin-top: 4px; color: var(--foreground, #09090b); font-size: 12px; line-height: 1.45; }
        .meta { margin-top: 4px; color: var(--muted-foreground, #71717a); font-size: 12px; line-height: 1.5; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; background: var(--muted, #f4f4f5); padding: 1px 5px; border-radius: 4px; }
        a { color: var(--primary, #2563eb); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .note { color: var(--muted-foreground, #71717a); font-size: 12px; line-height: 1.5; }
        .error { color: var(--destructive, #dc2626); }
        footer { padding: 12px 20px; border-top: 1px solid var(--border, #e4e4e7); font-size: 12px; color: var(--muted-foreground, #71717a); }
    `;

    class ComponentLibraryPanel extends HTMLElement {
        static get observedAttributes() {
            return ['route', 'lang'];
        }

        constructor() {
            super();
            this.attachShadow({ mode: 'open' });
            this.request = 0;
        }

        connectedCallback() {
            this.load();
        }

        attributeChangedCallback(name, before, after) {
            if (before !== after && this.isConnected) {
                this.load();
            }
        }

        async api(path) {
            const base = (window.__GRAV_API_SERVER_URL || '') + (window.__GRAV_API_PREFIX || '/api/v1');
            const headers = { Accept: 'application/json' };
            if (window.__GRAV_API_TOKEN) headers['X-API-Token'] = window.__GRAV_API_TOKEN;
            if (window.__GRAV_ENVIRONMENT) headers['X-Grav-Environment'] = window.__GRAV_ENVIRONMENT;
            const response = await fetch(base + path, { headers, credentials: 'same-origin' });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(body.detail || body.title || `The request failed (${response.status}).`);
            }
            return body.data;
        }

        async load() {
            const route = this.getAttribute('route');
            const id = ++this.request;
            if (!route) {
                this.render('<p class="note">Save the page first to see its components.</p>');
                return;
            }
            this.render('<p class="note">Loading…</p>');
            try {
                const data = await this.api('/component-library/page?route=' + encodeURIComponent(route));
                if (id !== this.request) return;
                const count = data.components.filter((c) => c.has_content).length;
                this.dispatchEvent(new CustomEvent('badge', { detail: { count } }));
                this.render(this.list(data), data);
            } catch (error) {
                if (id !== this.request) return;
                this.render(`<p class="note error">Couldn't load the components: ${esc(error.message)}</p>`);
            }
        }

        item(c) {
            const parts = [`<code>${esc(c.id)}</code> ${esc(c.version)}`];
            if (c.tab) parts.push(`on the <strong>${esc(c.tab)}</strong> tab`);
            const meta = [parts.join(' · ')];
            if (c.has_content && (c.filled || []).length) {
                meta.push(`Filled: ${c.filled.map((f) => `<code>${esc(f.replace(/^header\./, ''))}</code>`).join(', ')}`);
            }
            const short = (file) => `<code title="${esc(file)}">${esc(file.replace(/^templates\//, '').split('/').slice(-1)[0])}</code>`;
            const custom = (c.own_copies || []).filter((copy) => !copy.identical);
            if (custom.length) {
                meta.push(`The theme has its own template for it: ${custom.map((copy) => short(copy.file)).join(', ')}`);
            } else if ((c.rendered_by || []).length) {
                meta.push(`Included in ${c.rendered_by.map(short).join(', ')}`);
            }
            meta.push(`<a href="${esc(c.docs)}" target="_blank" rel="noopener noreferrer">About this component</a>`);
            return `<li>
                <div class="row"><span class="name">${esc(c.name)}</span>
                <span class="pill ${c.has_content ? 'on' : 'off'}">${c.has_content ? 'Has content' : 'Empty'}</span></div>
                ${c.summary ? `<div class="desc">${esc(c.summary)}</div>` : ''}
                <div class="meta">${meta.join('<br>')}</div>
            </li>`;
        }

        list(data) {
            if (!data.components.length) {
                return `<p class="note">The <code>${esc(data.template)}</code> template doesn't include any library components. Fields here are the theme's own.</p>`;
            }
            const used = data.components.filter((c) => c.has_content);
            const empty = data.components.filter((c) => !c.has_content);
            let html = '';
            if (used.length) html += `<div class="group">With content on this page (${used.length})</div><ul>${used.map((c) => this.item(c)).join('')}</ul>`;
            if (empty.length) html += `<div class="group">Available, not filled in (${empty.length})</div><ul>${empty.map((c) => this.item(c)).join('')}</ul>`;
            return html;
        }

        render(body, data) {
            const config = window.__GRAV_CONFIG__ || {};
            const reports = `${config.basePath || '/admin2'}/tools#reports`;
            const sub = data ? `${esc(data.title)} · template <code>${esc(data.template)}</code>` : 'Shared components from the Component Library';
            this.shadowRoot.innerHTML = `<style>${STYLE}</style>
                <header><div><h2>Components</h2><p class="sub">${sub}</p></div>
                <button class="close" type="button" aria-label="Close">×</button></header>
                <div class="body">${body}</div>
                <footer>Where every component is used across the site: <a href="${esc(reports)}">Tools → Reports</a></footer>`;
            this.shadowRoot.querySelector('.close').addEventListener('click', () => this.dispatchEvent(new CustomEvent('close')));
        }
    }

    customElements.define(TAG, ComponentLibraryPanel);
})();
