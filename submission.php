<?php
    session_start();
    require_once 'php/common.inc.php';
    require 'php/aws.phar';
    use Aws\S3\S3Client;

    // Server response message to the user; Messages will be appended to this string.
    $response_message = '';

    /**
     * Inserts the object into the database and returns the id of the object just added.
     */
    function addObject($name, $latitude, $longitude)
    {
        return Database::execute('INSERT INTO Objects (name, latitude, longitude) VALUES (?, ?, ?)', array($name, $latitude, $longitude), Database::GET_LAST_INSERT_ID);
    }

    /**
     * Inserts the review into the database and returns the id of the review just added.
     */
    function addReview($objectid, $userid, $rating, $comment)
    {
        return Database::execute('INSERT INTO Reviews (objectid, userid, rating, comment) VALUES (?, ?, ?, ?)', array($objectid, $userid, $rating, $comment), Database::GET_LAST_INSERT_ID);
    }

    /**
     * Converts the image to a jpg then uploads it to S3.
     * Returns the S3 key of the image.
     */
    function addPicture($objectid, $filepath)
    {
        $keyname = "rack/$objectid/image.jpg";

        /* Convert image to jpg; supported formats are jpg, png, gif.
         * http://stackoverflow.com/questions/8550015/convert-jpg-gif-image-to-png-in-php/8550030#8550030
         */
        switch (exif_imagetype($filepath)) {
        // These conversion functions require the GD library:
        // yum -y install php56-gd
        case IMAGETYPE_JPEG:
            $img = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $img = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $img = imagecreatefromgif($filepath);
            break;
        default:
            $img = null;
        }

        if ($img == null) {
            return null;
        }

        // Create a temporary place to store the converted image.
        $newfilepath = tempnam(sys_get_temp_dir(), 'tempimage');
        imagejpeg($img, $newfilepath, 70);
        imagedestroy($img); // Delete the intermediate image data.

        // Instantiate the client with the proper credentials.
        $s3client = S3Client::factory(Config::aws_s3_cred);

        // Upload a file.
        $result = $s3client->putObject(array(
            'Bucket'       => Config::aws_s3_bucket,
            'Key'          => $keyname,
            'SourceFile'   => $filepath,
            'ACL'          => 'public-read',
            'StorageClass' => 'REDUCED_REDUNDANCY' // Not critical if the file gets lost.
        ));
        unlink($newfilepath); // Delete the file after uploading it.

        // Return whether the S3 upload is successful.
        return ($result['statusCode'] == 200);
    }

    if (isset($_POST['newobject']))
    {
        $userid = $_SESSION['user_id'];
        $name = $_POST['name'];
        $rating = $_POST['rating'];
        $location = $_POST['location'];
        $picture = $_FILES['picture'];
        $picturefile = isset($picture) ? $picture['tmp_name'] : null;
        // Comments are cleaned instead of denying users from using special chars.
        $description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : "";

        // Validation checks; all fields are checked before aborting.
        $valid = true;
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
        if (!isset($description) || !$description)
        {
            $response_message .= "No description provided.<br/>";
            $valid = false;
        }
        if (!Validate::location($location))
        {
            $response_message .= "Invalid location.<br/>";
            $valid = false;
        }
        if (!isset($picturefile))
        {
            $response_message .= "No picture provided.<br/>";
            $valid = false;
        }
        // If validation passed, attempt to submit the object.
        if ($valid)
        {
            // Parse location into lat,lng, knowing that $location is valid.
            sscanf($location, "%f, %f", $latitude, $longitude);

            // Add the object into the database and get its id.
            $objectid = addObject($name, $latitude, $longitude);
            if (!$objectid)
            {
                $response_message .= 'Failed to add the rack.<br/>';
            }
            else
            {
                $response_message .= 'Successfully added the rack: <a href="rack.php?id='.$objectid.'">'.htmlspecialchars($name).'</a>.<br/>';

                // Add picture to S3 using the object id as a reference.
                if ($objectid != null) {
                    $imagekey = addPicture($objectid, $picturefile);
                }
                // Add a rating to the Comments table using the object id as a reference.
                if ($objectid != null) {
                    $reviewid = addReview($objectid, $userid, $rating, $description);
                }
            }
        }
    }

?>

<?php
    // BEGIN HTML
    $extra_head_tags = '
        <link rel="stylesheet" href="css/submission.css">
    ';
    require_once 'php/header_nav.inc.php';
?>

    <main>
        <?php
            // The server responses (success, error) to the user are written here.
            echo '<div><center><p>';
            echo $response_message;
            echo '</p></center></div>';
        ?>

        <!-- a form with each row represented as a list -->
        <form method="post" name="newobject" id="newobjectform" enctype="multipart/form-data">
            <ul>
                <li class="form-row">
                    <h2>Submit a new rack</h2>
                </li>

                <li class="form-row">
                    <label class="form-left" for="name">Name</label>
                    <div class="form-right">
                        <input class="flex-column" type="text" id="name" name="name"
                           pattern="[a-zA-Z0-9!@#$%^&*()':;,.\/\- ]{1,32}"
                           maxlength="32"
                           placeholder="Name of the location" required/>
                        <span class="flex-column form-hint"></span>
                    </div>
                </li>

                <li class="form-row">
                    <label class="form-left" for="rating">Rating</label>
                    <div class="form-right">
                        <select class="flex-column" id="rating" name="rating" form="newobjectform">
                            <option value="5">5</option>
                            <option value="4">4</option>
                            <option value="3">3</option>
                            <option value="2">2</option>
                            <option value="1">1</option>
                        </select>
                    </div>
                </li>

                <li class="form-row">
                    <label class="form-left" for="location">Location</label>
                    <!-- Takes any input that Google Maps would be able to handle. -->
                    <div class="form-right">
                        <input type="text" class="flex-column" id="location" name="location"
                            pattern="^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$"
                            placeholder="43.2609, -79.9192" required/>
                        <span class="flex-column form-hint"></span>
                    </div>
                </li>

                <li class="form-row">
                    <label class="form-left" for="description">Description</label>
                    <div class="form-right">
                        <textarea class="flex-column" id="description" name="description" form="newobjectform" placeholder="Describe the location"></textarea>
                    </div>
                </li>

                <li class="form-row">
                    <label class="form-left" for="picture">Upload Picture</label>
                    <div class="form-right">
                        <input type="file" class="flex-column" id="picture" name="picture" accept="image/jpeg,image/png,image/gif" required/>
                    </div>
                </li>

                <li class="form-row">
                    <input type="hidden" name="newobject" value="true"/>
                    <input class="form-right" type="submit" value="Submit"/>
                </li>
            </ul>
        </form>
    </main>

<?php
    require_once 'php/footer.inc.php';
?>