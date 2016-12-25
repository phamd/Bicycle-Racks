/* global google, gmaps */

/* namespacing using an IIFE to prevent global scope pollution */
var individual = (function () {
    'use strict';

    var map;

    var setMap = function (name, latitude, longitude) {
        // Init the map.
        var location = {lat: latitude, lng: longitude};
        map = gmaps.initMap('individual-map', location, 15); // calls initMap from the gmaps.js file

        // The label is only supposed to hold one character, so 'Example Location' overflows on purpose.
        // Not using an infoWindow here since the marker doesn't need to be clickable on the individual page.
        gmaps.addMarker(location, map, name);
    }

    // On DOM load.
    document.addEventListener('DOMContentLoaded', function (event) {

    });

    return {
        setMap: setMap
    };
})();