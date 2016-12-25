/* global google */

/* Since I'm going to need to initalize a map on two pages, results and individual,
   I've created wrapper functions here to init and add markers.
*/

/* namespacing using an IIFE to prevent global scope pollution */
var gmaps = (function () {
    'use strict';

    var debug = false;
    // https://developers.google.com/maps/documentation/javascript/examples/marker-labels

    /* Inititalize the Google map. */
    function initMap(id, centerLocation, zoom) {
        var mapOptions = {
            center: centerLocation,
            zoom: 15
        };
        return new google.maps.Map(document.getElementById(id), mapOptions);
    }

    /* Adds a marker to the map.
       infoWindow is optional.   */
    function addMarker(location, map, label, infoWindow, infoWindowContent) {
        var marker = new google.maps.Marker({
            position: location,
            label: label,
            map: map
        });

        // Add popover infomation on click.
        // infoWindow - https://developers.google.com/maps/documentation/javascript/infowindows
        if (infoWindow) { // optional
            marker.addListener('click', function () {
                infoWindow.close(); // close infoWindow
                infoWindow.setContent(infoWindowContent); // change infoWindow content
                infoWindow.open(map, marker); // move infoWindow to the clicked marker
            });
        }

        if (debug) {
            console.log(marker);
        }

        return marker;
    }

    // public functions
    return {
        initMap: initMap,
        addMarker: addMarker
    };
})();