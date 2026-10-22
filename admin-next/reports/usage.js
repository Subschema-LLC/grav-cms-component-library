/**
 * Component Library: usage report under Tools → Reports in Grav 2's Admin.
 *
 * Admin2 sets `report` to the object built in onApiGenerateReports
 * (component-library.php): `items` holds one entry per component in use,
 * `unused` the rest, and `lint` the template checks on the active theme.
 * Colours come from Admin2's theme variables, so light and dark mode follow it.
 */
(() => {
    const TAG = window.__GRAV_REPORT_TAG || 'grav-component-library--usage';
    if (customElements.get(TAG)) {
        return;
    }

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;

    const STYLE = `
        :host { display: block; color: var(--foreground, #09090b); font: inherit; font-size: 13px; }
        .summary { display: flex; flex-wrap: wrap; gap: 8px 20px; padding: 10px 16px; border-bottom: 1px solid var(--border, #e4e4e7); color: var(--muted-foreground, #71717a); }
        .summary strong { color: var(--foreground, #09090b); }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        col.c1 { width: 24%; } col.c2 { width: 22%; } col.c3 { width: 26%; } col.c4 { width: 28%; }
        td { overflow-wrap: anywhere; }
        th { text-align: left; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted-foreground, #71717a); padding: 10px 16px 6px; border-bottom: 1px solid var(--border, #e4e4e7); }
        td { vertical-align: top; padding: 10px 16px; border-bottom: 1px solid var(--border, #e4e4e7); line-height: 1.5; }
        tr:last-child td { border-bottom: 0; }
        .name { font-weight: 600; }
        .desc { margin-top: 2px; font-size: 12px; line-height: 1.4; }
        .muted { color: var(--muted-foreground, #71717a); }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; background: var(--muted, #f4f4f5); padding: 1px 5px; border-radius: 4px; }
        pre { margin: 4px 0 8px; padding: 8px 10px; background: var(--muted, #f4f4f5); border-radius: var(--radius-sm, 4px); overflow-x: auto; font-size: 11px; line-height: 1.45; }
        pre code { background: transparent; padding: 0; }
        .unused-item { margin: 6px 0; }
        a { color: var(--primary, #2563eb); text-decoration: none; }
        a:hover { text-decoration: underline; }
        details summary { cursor: pointer; color: var(--primary, #2563eb); }
        details ul { margin: 4px 0 0; padding-left: 18px; }
        .tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 1px 7px; border-radius: 999px; background: var(--muted, #f4f4f5); color: var(--muted-foreground, #71717a); }
        .tag.custom { background: color-mix(in srgb, #f59e0b 15%, transparent); color: color-mix(in srgb, #b45309 85%, var(--foreground, #09090b)); }
        h3 { margin: 0; padding: 14px 16px 6px; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted-foreground, #71717a); border-top: 1px solid var(--border, #e4e4e7); }
        .block { padding: 4px 16px 14px; line-height: 1.6; }
        .lint li { margin: 2px 0; }
        .error { color: var(--destructive, #dc2626); font-weight: 600; }
        .warning { color: color-mix(in srgb, #d97706 85%, var(--foreground, #09090b)); font-weight: 600; }
        @media (max-width: 720px) { thead { display: none; } td { display: block; border: 0; padding: 4px 16px; } tr { display: block; border-bottom: 1px solid var(--border, #e4e4e7); padding: 8px 0; } }
    `;

    class ComponentLibraryUsageReport extends HTMLElement {
        constructor() {
            super();
            this.attachShadow({ mode: 'open' });
            this.data = null;
        }

        set report(value) {
            this.data = value;
            this.render();
        }

        get report() {
            return this.data;
        }

        connectedCallback() {
            this.render();
        }

        editLink(route) {
            const base = (window.__GRAV_CONFIG__ || {}).basePath || '/admin2';
            return `${base}/pages/edit${route.startsWith('/') ? route : '/' + route}`;
        }

        list(items, limit, render) {
            if (!items.length) return '';
            if (items.length <= limit) return items.map(render).join(', ');
            return `${items.slice(0, limit).map(render).join(', ')} <details><summary>and ${items.length - limit} more</summary><ul>${items.slice(limit).map((i) => `<li>${render(i)}</li>`).join('')}</ul></details>`;
        }

        where(c) {
            const out = [];
            if (c.templates.length) {
                const tabs = [...new Set(c.templates.map((t) => t.tab).filter(Boolean))];
                out.push(`${plural(c.templates.length, 'page type', 'page types')}${tabs.length ? ` (${tabs.map(esc).join(', ')} tab)` : ''}
                    <details><summary class="muted">which</summary><ul>${c.templates.map((t) => `<li><code>${esc(t.template)}</code></li>`).join('')}</ul></details>`);
            }
            if (c.theme_settings) {
                out.push(`Theme settings${c.theme_settings.tab ? ` (${esc(c.theme_settings.tab)})` : ''}, ${c.theme_settings.configured ? 'filled in' : '<span class="muted">not filled in</span>'}`);
            }
            return out.join('<br>') || '<span class="muted">No fields; rendered from templates only</span>';
        }

        pages(c) {
            if (!c.templates.length) return '<span class="muted">—</span>';
            if (!c.pages.length) return '<span class="muted">None yet</span>';
            return `<strong>${c.pages.length}</strong>: ` + this.list(c.pages, 4, (p) => `<a href="${esc(this.editLink(p.route))}" title="${esc(p.route)}">${esc(p.title || p.route)}</a>${c.pages.filter((q) => q.title === p.title).length > 1 ? ` <span class="muted">(${esc(p.route)})</span>` : ''}`);
        }

        theme(c) {
            const out = [];
            for (const copy of c.own_copies) {
                out.push(`<span class="tag ${copy.identical ? '' : 'custom'}">${copy.identical ? 'Own copy, unchanged' : 'Own copy, customized'}</span> <code title="${esc(copy.file)}">${esc(copy.file.split('/').slice(-1)[0])}</code>`);
            }
            if (c.rendered_by.length) {
                out.push('Included in ' + this.list(c.rendered_by, 3, (f) => `<code title="${esc(f)}">${esc(f.replace(/^templates\//, ''))}</code>`));
            }
            if (!out.length) {
                return c.has_template === false
                    ? '<span class="muted">Fields only; no template of its own</span>'
                    : '<span class="muted">The theme doesn\'t include the library\'s template</span>';
            }
            return out.join('<br>');
        }

        render() {
            const r = this.data;
            if (!r || !this.shadowRoot) return;
            const items = r.items || [];
            const unused = r.unused || [];
            const lint = r.lint || [];
            const withPages = items.filter((c) => c.pages && c.pages.length).length;

            const rows = items.map((c) => `<tr>
                <td><div class="name">${esc(c.name)}</div><code>${esc(c.id)}</code> <span class="muted">${esc(c.version)}</span> · <a href="${esc(c.docs)}" target="_blank" rel="noopener noreferrer">Docs ↗</a>${c.summary ? `<div class="desc muted">${esc(c.summary)}</div>` : ''}</td>
                <td>${this.where(c)}</td>
                <td>${this.pages(c)}</td>
                <td>${this.theme(c)}</td>
            </tr>`).join('');

            const unusedBlock = unused.map((u) => {
                const applyEntries = Object.entries(u.apply || {});
                const howToAdd = applyEntries.length
                    ? ` · <details style="display:inline"><summary>How to add</summary>${applyEntries.map(([k, v]) => `<div><strong class="muted">${esc(k)}</strong><pre><code>${esc(String(v).trim())}</code></pre></div>`).join('')}</details>`
                    : '';
                return `<div class="unused-item"><a href="${esc(u.docs)}" target="_blank" rel="noopener noreferrer"><strong>${esc(u.name)}</strong></a> <code>${esc(u.id)}</code>${u.summary ? ` — <span class="muted">${esc(u.summary)}</span>` : ''}${howToAdd}</div>`;
            }).join('');

            const lintBlock = lint.length
                ? `<ul class="lint">${lint.map((i) => `<li><span class="${i.level === 'error' ? 'error' : 'warning'}">${esc(i.level)}</span> <code>${esc(i.file)}:${esc(i.line)}</code> ${esc(i.rule)}: ${esc(i.message)}</li>`).join('')}</ul>${r.lint_total > lint.length ? `<p class="muted">Showing ${lint.length} of ${r.lint_total}. Run <code>bin/plugin component-library lint</code> for all.</p>` : ''}`
                : 'No problems found in the theme\'s templates.';

            this.shadowRoot.innerHTML = `<style>${STYLE}</style>
                <div class="summary">
                    <span>Theme <strong>${esc(r.theme)}</strong></span>
                    <span><strong>${items.length}</strong> components in use</span>
                    <span><strong>${withPages}</strong> with content on pages</span>
                    <span><strong>${esc(r.pages_scanned)}</strong> pages checked</span>
                </div>
                ${items.length ? `<table><colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"></colgroup><thead><tr><th>Component</th><th>Fields appear in</th><th>Pages with content</th><th>In the theme</th></tr></thead><tbody>${rows}</tbody></table>` : '<div class="block muted">The active theme doesn\'t use any library components yet.</div>'}
                ${unused.length ? `<h3>Not used by this theme (${unused.length})</h3><div class="block">${unusedBlock}</div>` : ''}
                <h3>Template checks</h3><div class="block">${lintBlock}</div>`;
        }
    }

    customElements.define(TAG, ComponentLibraryUsageReport);
})();
