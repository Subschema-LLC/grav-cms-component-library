/*
 * Pattern: theme/datalayer
 *
 * A small window.dataLayer helper for tag managers. Load it in the HEAD asset
 * group (before any tag-manager snippet) so it exists before tags run.
 *
 * Configuration comes from <script type="application/json" id="cl-datalayer-config">,
 * written by datalayer.html.twig from theme settings, so this file never
 * changes per site and contains no inline values.
 *
 *   window.emitToDatalayer(eventName, extra)  push an event with page + app data
 *   <div data-datalayer-event="map_view">     emits once when the DOM is ready
 *
 * On full page load it emits "metadata".
 */
(function () {
    "use strict";

    var dataLayer = (window.dataLayer = window.dataLayer || []);

    var config = null;

    // The config block may come after this script tag, so it is read lazily and
    // cached only once it has been found.
    function readConfig() {
        if (config) {
            return config;
        }
        var el = document.getElementById("cl-datalayer-config");
        if (!el) {
            return {};
        }
        try {
            config = JSON.parse(el.textContent) || {};
        } catch (e) {
            config = {};
        }
        return config;
    }

    function emit(eventName, extra) {
        if (!eventName) {
            return;
        }
        var config = readConfig();
        var entry = {
            event: String(eventName),
            page: window.location.pathname,
            customData: config.customData || {}
        };
        if (extra && typeof extra === "object") {
            for (var key in extra) {
                if (Object.prototype.hasOwnProperty.call(extra, key)) {
                    entry[key] = extra[key];
                }
            }
        }
        dataLayer.push(entry);
    }

    window.emitToDatalayer = emit;

    function emitDeclared() {
        var seen = {};
        var nodes = document.querySelectorAll("[data-datalayer-event]");
        for (var i = 0; i < nodes.length; i++) {
            var name = nodes[i].getAttribute("data-datalayer-event");
            if (name && !seen[name]) {
                seen[name] = true;
                emit(name);
            }
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", emitDeclared);
    } else {
        emitDeclared();
    }

    if (document.readyState === "complete") {
        emit("metadata");
    } else {
        window.addEventListener("load", function () {
            emit("metadata");
        });
    }
})();
