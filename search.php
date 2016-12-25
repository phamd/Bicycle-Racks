<?php
    session_start();
    require_once 'php/common.inc.php';
?>

<?php
    // BEGIN HTML
    $extra_head_tags = '
        <script type="text/javascript" src="js/search.js"></script>
    ';
    require_once 'php/header_nav.inc.php';
?>

    <main>
        <!-- The welcome message is here assuming the search page is the home page. -->
        <!-- elements in this div are either side-by-side or above-below (depending on resolution) -->
        <div class="welcome">
            <div>
                <img src="images/bike_F26D82.png" alt="bicycle">
            </div>
            <div class="welcome-text">
                <h1>Welcome to Bicycle Racks</h1>
                <h2>A mapping of places to park your bike.</h2>
            </div>
        </div>
        <!-- always below the previous welcome div -->
        <div class="welcome">
            <a href="results.php?searchquery=43.2585, -79.9209&searchrating=1">View an example search</a>
        </div>

        <div id="search">
            <!-- a form with labels for each input. the id is to identify the form later when submitting to the server. -->
            <form action="results.php" method="get" id="searchform">
                <label for="searchquery">Search</label>
                <!-- placeholder text is the greyed out text behind the input before the user types something -->
                <!-- the name tags on the form controls are for the server requests -->
                <!-- the id tags are for the labels to link to the form control -->
                <input type="search" id="searchquery" name="searchquery" placeholder="Location"/>
                <label for="searchrating">Rating</label>
                <select id="searchrating" name="searchrating">
                    <option value="5">5</option>
                    <!-- escaping the ampersand to be safe -->
                    <option value="4">4 &amp; up</option>
                    <option value="3">3 &amp; up</option>
                    <option value="2">2 &amp; up</option>
                    <!-- 1 is the defaultly selected option -->
                    <option value="1" selected="selected">1 &amp; up</option>
                </select>
                <input type="submit" value="Go"/>
                <br>
                <input id="getlocation" type='button' value='Get my location'>
                <br>
                <p id="status"></p>
            </form>
        </div>
    </main>

<?php
    require_once 'php/footer.inc.php';
?>