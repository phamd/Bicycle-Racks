/* namespacing using an IIFE to prevent global scope pollution */
var registration = (function () {
    'use strict';

    // Checks if an input field has a value matching the regex pattern.
    // Displays the hint_message if false.
    function validate_field(id, pattern, hint_message) {
        var field = document.getElementById(id);
        var hint = field.parentNode.getElementsByClassName("form-hint")[0]; // assuming there is only one sibling element with class "form-hint"

        if (field.value !== undefined && pattern.test(field.value)) { // compare text to regex
            hint.innerHTML = ""; // hide message if valid
            return true;
        } else {
            hint.innerHTML = hint_message; // show hint message if invalid
            return false;
        }
    }

    // Returns true when all fields validate to true
    function validate_reg() {
        return !!( // boolean cast so that "true" or "false" is returned instead of "0" or "1"
            validate_field("reg-user-name", /^[a-zA-Z0-9]{1,20}$/, "Enter 1-20 alpha-numeric characters.")
            // Email regex from w3c https://www.w3.org/TR/html-markup/datatypes.html#form.data.emailaddress
            & validate_field("reg-user-email", /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/, "Enter a valid email (e.g. a@b.com).")
            & validate_field("reg-user-password", /^.{6,}$/, "Enter at least 6 characters.")
            // Chrome displays the date as mm/dd/yyyy but parses it into yyyy-mm-dd
            & validate_field("reg-user-age", /^\d{4}-\d{2}-\d{2}$/, "Enter a valid age (yyyy-mm-dd).")
        );
    }

    // Returns true when all fields validate to true
    function validate_login() {
        return !!( // boolean cast
            validate_field("login-user-name", /^[a-zA-Z0-9]{1,20}$/, "Enter 1-20 alpha-numeric characters.")
            & validate_field("login-user-password", /^.{6,}$/, "Enter at least 6 characters.")
        );
    }

    document.addEventListener("DOMContentLoaded", function (event) {

        /* Disable HTML5 validation to use Javascript validation. Snippet from http://novalidate.com/ */
        for (var f=document.forms, i=f.length; i--;) {
            f[i].setAttribute("novalidate", i); // apply the novalidate attribute to all forms (registration and login)
            // Could have just done this manually without a loop since there are only two forms on the page anyway.
        }

    });

    /* list of public functions from this file */
    return {
        validate_reg: validate_reg,
        validate_login: validate_login
    };
})();