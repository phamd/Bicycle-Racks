<?php
    session_start();
    require_once 'php/common.inc.php';

    // Server response message to the user; Messages will be appended to this string.
    $response_message = '';

    /**
     * Get the results from the database.
     * Inputs should be validated before calling this.
     * $searchQuery is either a coordinate pair, or an object name.
     */
    function getResults($searchQuery, $searchRating)
    {
        $results = null;

        // If the query is a valid coordinate pair.
        if (Validate::location($searchQuery))
        {
            // Split coords into lat,lng.
            sscanf($searchQuery, "%f, %f", $latitude, $longitude);

            // The Objects table is JOINed to the Reviews table to aggregate on average rating.
            // Search precision here is 3 decimals which corresponds to about a street distance.
            // DECIMAL(7,3) means 3 after decimal, 4 before.
            $results = Database::execute(
                'SELECT Objects.id as id, Objects.name, Objects.latitude,
                Objects.longitude, AVG(Reviews.rating) as avgrating
                FROM Objects
                LEFT JOIN Reviews
                ON Objects.id = Reviews.objectid
                WHERE cast(Objects.latitude AS DECIMAL(7,3)) = cast(? AS DECIMAL(7,3))
                AND cast(Objects.longitude AS DECIMAL(7,3)) = cast(? AS DECIMAL(7,3))
                GROUP BY Objects.id
                HAVING avgrating >= ?
                ORDER BY avgrating DESC',
                array($latitude, $longitude, $searchRating));
        }
        // Else if the query is an object name.
        else {
            // Search for the query anywhere within the object name.
            $results = Database::execute(
                "SELECT Objects.id as id, Objects.name, Objects.latitude,
                Objects.longitude, AVG(Reviews.rating) as avgrating
                FROM Objects
                LEFT JOIN Reviews
                ON Objects.id = Reviews.objectid
                WHERE name LIKE concat('%',?,'%')
                GROUP BY Objects.id
                HAVING avgrating >= ?
                ORDER BY avgrating DESC",
                array($searchQuery, $searchRating));
        }
        return $results;
    }

    /**
     * Return the HTML of all the results formatted.
     */
    function writeResults($results)
    {
        $output = '';
        $index = 0;
        foreach ($results as $objdata)
        {
            $index++; // Index starts from 1.
            $id = $objdata['id'];
            $name = htmlspecialchars($objdata['name'], ENT_QUOTES); // Convert symbols, just in case.
            $location = $objdata['latitude'].', '.$objdata['longitude']; // Combine lat,lng.
            $rating = Common::ratingToAscii($objdata['avgrating']); // Write the rating as stars.
            $imageurl = Config::aws_s3_prefix."rack/$id/image.jpg";
            $objurl = "rack.php?id=$id";

            $output .= '
                <div class="search-result">
                    <div class="search-result-info-logo">
                        <img src="'.$imageurl.'" alt="'.$name.'" width="100" height="100">
                    </div>
                    <div class="search-result-info-main">
                        <div class="search-result-info-name">'.$index.'. <a href="'.$objurl.'">'.$name.'</a></div>
                        <div class="search-result-info-rating">'.$rating.'</div>
                    </div>
                    <div class="search-result-info-location">
                        <div class="address">
                            '.$location.'
                        </div>
                    </div>
                </div>
            ';
        }
        return $output;
    }

    /**
     * Instead of validating and aborting a search on invalid characters,
     * Remove the characters not allowed in an object name from the string.
     */
    function parseObjName($objName)
    {
        return preg_replace("/[^a-zA-Z0-9!@#$%^&*()':;,.\/\- ]/", '', $objName);
    }

    /**
     * Remove invalid characters and saturate the rating to 1-5.
     */
    function parseRating($rating)
    {
        $rating = preg_replace("/[^0-9]/", '', $rating);
        if (!$rating) {
            $rating = 1;
        }
        if ($rating > 5) {
            $rating = 5;
        }
        return $rating;
    }

    $searchQuery = isset($_GET['searchquery']) ? parseObjName($_GET['searchquery']) : '';
    $searchRating = isset($_GET['searchrating']) ? parseRating($_GET['searchrating']) : 1;

    $results = getResults($searchQuery, $searchRating);

    if (!$results) {
        $response_message .= 'No results found.<br/>';
    }

    // Format the data required for the JavaScript to generate a map.
    $mapData = array('results' => array());
    for ($i = 0; $i < count($results); $i++)
    {
        // The format is structured so that it can be encoded to JSON.
        $mapData['results'][$i+1] = array( // count starts from 1
            'name' => htmlspecialchars($results[$i]['name'], ENT_QUOTES), // Symbols such as quotes are converted to &#039;.
            'location' => array('lat' => (float)$results[$i]['latitude'], 'lng' => (float)$results[$i]['longitude']),
            'url' => 'rack.php?id='.$results[$i]['id'],
            'rating' => Common::ratingToAscii($results[$i]['avgrating'])
        );
    }

?>

<?php
    // BEGIN HTML
    $extra_head_tags = '
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBIe88J9Eupdcf5dRg91aoiODszMpeKxKM"></script>
        <script type="text/javascript" src="js/gmaps.js"></script>
        <script type="text/javascript" src="js/results.js"></script>
    ';
    require_once 'php/header_nav.inc.php';
?>

    <main>
        <div class="return"><a href="search.php">Return to search page</a></div>
        <h2>Results</h2>
        <?php
            // The server responses (success, error) to the user are written here.
            echo '<div><center><p>';
            echo $response_message;
            echo '</p></center></div>';

            // Draw map if results present.
            if ($results) {
                echo '<div id="results-map">';
                echo '<script>results.setMap(\''.json_encode($mapData).'\') ;</script>';
                echo '</div>';

                // Write the results table.
                echo '<div id="results-tabular">';
                echo writeResults($results);
                echo '</div>';
            }
        ?>
    </main>

<?php
    require_once 'php/footer.inc.php';
?>