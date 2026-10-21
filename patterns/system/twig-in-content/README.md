<!-- Generated from pattern.yaml by `bin/plugin component-library catalog`. Edit pattern.yaml, not this file. -->
# Twig in page content on Grav 2

`system/twig-in-content` · v1.0.0 · stable · Grav 2.0 (tested on 2.2.11)

Keep Twig out of page content by moving dynamic parts into header fields and templates; when content Twig is genuinely needed, widen the sandbox narrowly in security.yaml or from a plugin.

## Use it when

- Migrating a site whose Markdown contains {{ }} or {% %}.
- A page shows raw Twig, or a security.log "sandbox blocked" entry, after moving to Grav 2.

## Apply

**Config**

```yaml
# user/config/security.yaml: widen, never disable the sandbox
twig_sandbox:
  allowed_functions: [my_safe_function]
```

**Php**

```php
// Plugin: allow its own safe function inside page content
public function onBuildTwigSandboxPolicy(Event $event): void
{
    $functions = $event['functions'];
    $functions[] = 'example_gallery';
    $event['functions'] = $functions;
}
```

## Problems this solves

| Problem | Fix | Seen in |
|---|---|---|
| After migrating to Grav 2, pages with Twig in their Markdown showed raw {{ ... }} (before 2.0.19 content Twig was off by default) or had expressions silently blocked by the sandbox. | Move the dynamic part into a header field and render it from the template (page/embed-block, page/tiles, page/map-location), so no Twig runs in content. Admin's Tools → Reports → Twig in Content lists affected pages. | Grav 2 migration |
| Building embeds and maps with Twig in content mixed presentation into writing and let any page editor run template code. | Header fields plus templates; editors fill in values, templates decide markup. | community-guide theme (maps via &lt;div id="map"&gt;, no Twig) |
| A site copied the whole default sandbox list into security.yaml to add one function, freezing it against later core security fixes (lists merge by replacement). | In Grav 2.2 the allowed_* keys are additive over built-in defaults; add only your entry. Tighten with denied_*. | Grav 2 security configuration changes |
| Turning the sandbox off to make one page work removed SSTI protection for every editor-written page. | Leave twig_sandbox.enabled true; allow the specific member instead. |  |

## Verify

- [ ] Admin → Tools → Reports → Twig in Content shows no pages leaking raw Twig.
- [ ] logs/security.log has no new sandbox-blocked entries after visiting the affected pages.

## Files

- [`security-snippet.yaml`](security-snippet.yaml) (snippets)
- [`SandboxPolicy.php`](SandboxPolicy.php) (snippets)
