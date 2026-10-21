/*
 * Pattern: theme/map-here
 *
 * Renders a HERE map into the element with id="map". Settings come from
 * <script type="application/json" id="cl-map-config">, written by
 * map-here.html.twig from theme settings (and per-page here_map overrides).
 *
 * Per-map overrides on the element:
 *   <div id="map" data-lat="44.98" data-lng="-93.27" data-zoom="14"></div>
 *
 * Markers (page-type/map-page): data-markers="<id of a JSON block>" listing
 * places as { title, url, latitude, longitude, summary }. Each becomes a pin
 * whose popup links to its page; the map fits all pins unless the element sets
 * its own view. Buttons with data-marker-index="<n>" pan to and open marker n,
 * and are revealed only once that marker exists.
 *
 * Pin color: the CSS custom property --cl-map-pin (falls back to #2f5fb3).
 */
document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    var mapElement = document.getElementById("map");
    if (!mapElement || !window.H || !H.service) {
        return;
    }

    function readJson(id) {
        var source = id && document.getElementById(id);
        if (!source) {
            return null;
        }
        try {
            return JSON.parse(source.textContent);
        } catch (error) {
            return null;
        }
    }

    function firstNumber() {
        for (var i = 0; i < arguments.length; i++) {
            if (arguments[i] === null || arguments[i] === undefined || arguments[i] === "") {
                continue;
            }
            var value = parseFloat(arguments[i]);
            if (isFinite(value)) {
                return value;
            }
        }
        return null;
    }

    function validCoords(lat, lng) {
        return isFinite(lat) && isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
    }

    function pinIcon() {
        var color = getComputedStyle(document.documentElement).getPropertyValue("--cl-map-pin").trim() || "#2f5fb3";
        // Only allow simple color values inside the SVG markup.
        if (!/^(#[0-9a-f]{3,8}|[a-z]+|rgba?\([\d\s.,%]+\)|hsla?\([\d\s.,%deg]+\))$/i.test(color)) {
            color = "#2f5fb3";
        }
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="40" viewBox="0 0 32 40">' +
            '<path d="M16 1C7.7 1 1 7.6 1 15.8 1 27 16 39 16 39s15-12 15-23.2C31 7.6 24.3 1 16 1z" fill="' + color + '" stroke="#fff" stroke-width="2"/>' +
            '<circle cx="16" cy="15.5" r="5.5" fill="#fff"/></svg>';
        return new H.map.Icon(svg, { size: { w: 32, h: 40 }, anchor: { x: 16, y: 39 } });
    }

    var config = readJson("cl-map-config") || {};
    if (!config.apiKey) {
        if (window.console) {
            console.warn("HERE map: no API key is set in the theme settings (Map section).");
        }
        return;
    }

    var places = readJson(mapElement.getAttribute("data-markers"));
    if (!Array.isArray(places)) {
        places = [];
    }

    var hasExplicitView = mapElement.hasAttribute("data-lat") || mapElement.hasAttribute("data-zoom");
    var lat = firstNumber(mapElement.getAttribute("data-lat"), config.latitude);
    var lng = firstNumber(mapElement.getAttribute("data-lng"), config.longitude);
    var hasCenter = lat !== null && lng !== null && validCoords(lat, lng);
    // HERE centers on Europe when given no center. Use the world view instead,
    // and let the markers (if any) set the view below.
    var center = hasCenter ? { lat: lat, lng: lng } : { lat: 20, lng: 0 };
    var zoom = firstNumber(mapElement.getAttribute("data-zoom"), config.zoom, hasCenter ? 12 : 2);
    var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (!hasCenter && !places.length && window.console) {
        console.warn("HERE map: no default center is set in the theme settings, so the map shows the whole world.");
    }

    if (!mapElement.hasAttribute("role")) {
        mapElement.setAttribute("role", "region");
    }
    if (!mapElement.hasAttribute("aria-label")) {
        mapElement.setAttribute("aria-label", config.label || "Interactive map");
    }

    var platform = new H.service.Platform({ apikey: config.apiKey });
    var defaultLayers = platform.createDefaultLayers();
    var map = new H.Map(mapElement, defaultLayers.vector.normal.map, {
        center: center,
        zoom: zoom,
        pixelRatio: window.devicePixelRatio || 1
    });
    window.addEventListener("resize", function () {
        map.getViewPort().resize();
    });
    var behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(map));
    var ui = H.ui.UI.createDefault(map, defaultLayers);

    if (config.legacyGlobals) {
        // Older inline scripts (for example weather output calling map.addObject()
        // in window.onload) expect these globals. Without window.map, "map"
        // resolves to the <div id="map"> element and those scripts throw.
        window.platform = platform;
        window.defaultLayers = defaultLayers;
        window.map = map;
        window.behavior = behavior;
        window.ui = ui;
        window.moveMap = function (mapInstance, coordinates, zoomLevel) {
            mapInstance.setCenter(coordinates);
            mapInstance.setZoom(zoomLevel);
        };
    }

    var icon = pinIcon();
    var group = new H.map.Group();
    var markers = {};
    var markerCount = 0;

    places.forEach(function (place, index) {
        var pLat = parseFloat(place && place.latitude);
        var pLng = parseFloat(place && place.longitude);
        if (!validCoords(pLat, pLng)) {
            return;
        }
        var marker = new H.map.Marker({ lat: pLat, lng: pLng }, { icon: icon });
        marker.setData(index);
        group.addObject(marker);
        markers[index] = marker;
        markerCount++;
    });

    if (!markerCount) {
        // A plain map: pin the center, unless a page script added its own
        // objects by the time the page finished loading.
        if (!hasCenter) {
            return;
        }
        var markCenter = function () {
            window.setTimeout(function () {
                if (!map.getObjects().length) {
                    map.addObject(new H.map.Marker(center, { icon: icon }));
                }
            }, 0);
        };
        if (document.readyState === "complete") {
            markCenter();
        } else {
            window.addEventListener("load", markCenter);
        }
        return;
    }

    map.addObject(group);

    function openPlace(marker) {
        var place = places[marker.getData()];
        var content = document.createElement("div");
        var title = document.createElement(place.url ? "a" : "strong");
        content.className = "cl-map-bubble";
        title.className = "cl-map-bubble__title";
        title.textContent = place.title || "";
        if (place.url) {
            title.href = place.url;
        }
        content.appendChild(title);
        if (place.summary) {
            var summary = document.createElement("p");
            summary.className = "cl-map-bubble__summary";
            summary.textContent = place.summary;
            content.appendChild(summary);
        }
        ui.getBubbles().forEach(function (bubble) {
            ui.removeBubble(bubble);
        });
        ui.addBubble(new H.ui.InfoBubble(marker.getGeometry(), { content: content }));
    }

    group.addEventListener("tap", function (event) {
        openPlace(event.target);
    });
    group.addEventListener("pointerenter", function () {
        mapElement.style.cursor = "pointer";
    });
    group.addEventListener("pointerleave", function () {
        mapElement.style.cursor = "";
    });

    if (!hasExplicitView) {
        if (markerCount === 1) {
            map.setCenter(group.getObjects()[0].getGeometry());
            if (!hasCenter) {
                map.setZoom(14);
            }
        } else {
            map.getViewPort().setPadding(48, 48, 48, 48);
            map.getViewModel().setLookAtData({ bounds: group.getBoundingBox() });
            if (map.getZoom() > 16) {
                map.setZoom(16);
            }
        }
    }

    Array.prototype.slice.call(document.querySelectorAll("[data-marker-index]")).forEach(function (button) {
        var marker = markers[button.getAttribute("data-marker-index")];
        if (!marker) {
            return;
        }
        button.hidden = false;
        button.addEventListener("click", function () {
            map.setCenter(marker.getGeometry(), !reduceMotion);
            if (map.getZoom() < 13) {
                map.setZoom(13, !reduceMotion);
            }
            openPlace(marker);
            mapElement.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "center" });
        });
    });
});
