/* global google, gmaps */

/* namespacing using an IIFE to prevent global scope pollution */
var results = (function () {
    'use strict';

    var map;
    // https://developers.google.com/maps/documentation/javascript/infowindows
    // One infoWindow per map; the single infoWindow gets moved when a user clicks on a different marker.
    var infoWindow = new google.maps.InfoWindow();

    function setMap(serverResultsJson) {
        // Assuming my server backend will return JSON like this.
        //var serverResultsJson = '{"results":{"1":{"name":"Example Location","location":{"lat":43.26,"lng":-79.92},"url":"individual_sample.html"},"2":{"name":"Second Location","location":{"lat":43.260954,"lng":-79.919917},"url":"individual_sample.html"},"3":{"name":"ABB","location":{"lat":43.260314,"lng":-79.921333},"url":"individual_sample.html"}}}';

        // Parse JSON to an Object.
        var serverResults = JSON.parse(serverResultsJson).results;

        // Set default location to center on.
        var defaultLocation = {lat:0,lng:0};
        if (serverResults.length) {
             defaultLocation = serverResults[1].location;
        }
        // Set up bounds to refocus the map after adding markers.
        var bounds = new google.maps.LatLngBounds();

        // Init the map centered on the first location.
        map = gmaps.initMap('results-map', defaultLocation, 15); // modularized initMap and addMarker to gmaps.js

        // Draw each result onto the map as a marker.
        for (var key in serverResults) {

            if (!serverResults.hasOwnProperty(key)) { // Make sure the key is actually a part of the object and not of its prototype.
                continue;
            }
            var item = serverResults[key];
            // The infoWindow contains a link the individual object, and the rating.
            var infoWindowContent = '<a href="' + item.url + '">' + item.name + '</a> <br>' + item.rating; // these are hardcoded stars

            gmaps.addMarker(item.location, map, key, infoWindow, infoWindowContent);
            bounds.extend(item.location);
        }
        // Recenter the map.
        map.fitBounds(bounds);
    }

    return {
        setMap: setMap
    };
})();