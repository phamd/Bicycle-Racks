<?php
    session_start();
    require_once 'php/common.inc.php';

    // Server response message to the user; Messages will be appended to this string.
    $response_message = '';

    /**
     * Return average rating from objectid.
     */
    function getAvgRating($objectid)
    {
        return Database::execute('SELECT AVG(rating) FROM Reviews WHERE objectid = ?', array($objectid))[0][0];
    }

    /**
     * Return username from userid.
     */
    function getUserName($userid)
    {
        return Database::execute('SELECT username FROM Users WHERE id = ?', array($userid))[0]['username'];
    }

    /**
     * Get all reviews from objectid.
     */
    function getReviews($objectid)
    {
        return Database::execute('SELECT * FROM Reviews WHERE objectid = ?', array($objectid));
    }

    /**
     * Get an object from objectid.
     */
    function getObjectData($id)
    {
        return Database::execute('SELECT * FROM Objects WHERE id = ?', array($id))[0];
    }

    /**
     * Insert a review into the database. Validation is done before calling this.
     */
    function addReview($objectid, $userid, $rating, $comment)
    {
        return Database::execute('INSERT INTO Reviews (objectid, userid, rating, comment) VALUES (?, ?, ?, ?)',
            array($objectid, $userid, $rating, $comment), Database::GET_LAST_INSERT_ID);
    }

    /**
     * Return the HTML of all of the reviews for the objectid.
     */
    function writeReviews($objectid)
    {
        // HTML output.
        $output = '';
        // Get Reviews from object id.
        foreach (getReviews($objectid) as $review)
        {
            // Get username from user id.
            $username = getUsername($review['userid']);
            // Parse the timestamp in to a formatted date string.
            $date = (new DateTime($review['date'], new DateTimeZone('America/New_York')))->format('M j Y g:i A');
            // Parse the rating number into a rating string with stars.
            $rating = Common::ratingToAscii($review['rating']);
            $comment = htmlspecialchars($review['comment']);

            // Append a review using the variables set.
            $output .= '<div class="review">'.
                '<span class="review-username">'.$username.'</span>'.
                '<span class="review-date">'.$date.'</span>'.
                '<div class="review-rating">'.$rating.'</div>'.
                '<p class="review-comment">'.$comment.'</p>'.
                '</div>';
        }
        return $output;
    }

    $userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $objectid = Validate::id($_GET['id']) ? $_GET['id'] : null; // Validate ID.
    $objdata = isset($objectid) ? getObjectData($objectid) : null; // Get object from database, if the id is valid.

    // If the object is non-existant, redirect them back to the search page.
    if (!$objdata)
    {
        header('Location: search.php');
        die;
    }

    // If the user is submitting a new review.
    if (isset($_POST['new-review']))
    {
        // Strip characters before validating; important to do for comments.
        $rating = isset($_POST['new-review-rating']) ? htmlspecialchars($_POST['new-review-rating']) : null;
        // Quietly chop comments down to 255 characters.
        $comment = isset($_POST['new-review-text']) ? substr(htmlspecialchars($_POST['new-review-text']), 0, 255) : null;

        // Validation checks.
        $valid = true;
        if (!Validate::id($objectid) || !$objdata)
        {
            $response_message .= "Rack doesn't exist.<br/>";
            $valid = false;
        }
        if (!isset($userid))
        {
            $response_message .= "Not logged in.<br/>";
            $valid = false;
        }
        if (!Validate::rating($rating))
        {
            $response_message .= "No rating provided.<br/>";
            $valid = false;
        }
        if (!isset($comment) || !$comment) // If the POST isn't set or the comment is empty.
        {
            $response_message .= "No comment provided.<br/>";
            $valid = false;
        }
        // If all the validation checks above pass, add the review to the database.
        if ($valid)
        {
            if (addReview($objectid, $userid, $rating, $comment)) {
                $response_message .= "Successfully added the review.<br/>";
            } else {
                $response_message .= "Failed to add the review.<br/>";
            }
        }
    }

    // Initialize the data for the page, these will be used inline in the HTML.
    $title = htmlspecialchars($objdata['name'], ENT_QUOTES);
    $imgsrc = Config::aws_s3_prefix."rack/$objectid/image.jpg";
    $latitude = $objdata['latitude'];
    $longitude = $objdata['longitude'];
    $location = $latitude.", ".$longitude;
    $rating = Common::ratingToAscii(getAvgRating($objectid));
?>

<?php
    // BEGIN HTML
    $extra_head_tags = '
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBIe88J9Eupdcf5dRg91aoiODszMpeKxKM"></script>
        <script type="text/javascript" src="js/gmaps.js"></script>
        <script type="text/javascript" src="js/individual.js"></script>
    ';
    require_once 'php/header_nav.inc.php';
?>

    <main>
        <div class="return"><a href="search.php">Return to search page</a></div>
        <?php
            // The server responses (success, error) to the user are written here.
            echo '<div><center><p>';
            echo $response_message;
            echo '</p></center></div>';
        ?>

        <div class="individual-title"><?php echo $title; ?></div>
        <div class="individual-info">
            <div class="search-result">
                <div class="individual-info-left">
                    <div class="label">Rating:</div>
                    <div class="rating"><?php echo $rating; ?></div>
                    <div class="label">Location:</div>
                    <div class="address">
                        <?php echo $location; ?>
                    </div>
                </div>
                <div class="individual-picture">
                    <img src="<?php echo $imgsrc; ?>" width="200" height="200" alt="<?php echo $title; ?>">
                </div>
            </div>
            <div id="individual-map">
                <?php
                    echo "<script>individual.setMap('$title', $latitude, $longitude);</script>";
                ?>
            </div>
        </div>

        <form method="post" class="new-review" name="new-review-form" id="new-review-form">
            <div class="label">Add review:</div>
            <textarea name="new-review-text" form="new-review-form" maxlength="255"></textarea>
            <div>
                <span class="">Rating:</span>
                <!-- use javascript to click the stars to rate. NVM. -->
                <!--<div class="new-rating">&#9734;&#9734;&#9734;&#9734;&#9734;</div>-->
                <select id="rating" name="new-review-rating" form="new-review-form">
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
                <input type="hidden" name="new-review" value="true"/>
                <input type="submit"/>
            </div>
        </form>

        <?php
            // Echo the HTML for the reviews.
            echo writeReviews($objectid);
        ?>

    </main>

<?php
    require_once 'php/footer.inc.php';
?>