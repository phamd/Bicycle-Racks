/* namespacing using an IIFE to prevent global scope pollution */
var search = (function () {
    'use strict';

    /**
      Emphasizes an element for a short amount of time.
     */
    function flashElement(el) {
        el.style.borderColor = "#F26D82"; // change colour of border
        setInterval(function () { // after .5 seconds change colour back
            el.style.borderColor = "initial";
        }, 500);
    }

    /**
      Writes the geolocation position into the search field.
     */
    function showPosition(position) {
        var latlong = position.coords.latitude + "," + position.coords.longitude;
        var el = document.getElementById("searchquery"); // search inputbox element
        el.value = latlong; // set the value of search inputbox to "latitude, longitude"
        flashElement(el); // highlight search inputbox
        el.focus(); // put cursor in search inputbox
    }

    /**
      Error messages taken from lecture slides/examples.
      Except error.UNKOWN_ERROR has been replaced with default since it isn't in the spec anymore (deprecated).
      https://dev.w3.org/geo/api/spec-source.html#positionerror
     */
    function showError(error) {
        var msg = "";
        switch(error.code) {
            case error.PERMISSION_DENIED:
                msg = "User denied the request for Geolocation.";
                break;
            case error.POSITION_UNAVAILABLE:
                msg = "Location information is unavailable.";
                break;
            case error.TIMEOUT:
                msg = "The request to get user location timed out.";
                break;
            default:
                msg = "An unknown error occurred.";
                break;
        }
        document.getElementById("status").innerHTML = msg;
    }

    function getLocation() {
        if (navigator.geolocation) {
            /* https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition */
            navigator.geolocation.getCurrentPosition(showPosition /* success */, showError /* fail */);
        } else {
            document.getElementById("status").innerHTML = "Geolocation is not supported by this browser.";
        }
    }

    // On DOM load
    document.addEventListener("DOMContentLoaded", function(event) {

        // Add a click event to the getlocation button
        document.getElementById("getlocation").addEventListener("click", getLocation, false);

    });

})();