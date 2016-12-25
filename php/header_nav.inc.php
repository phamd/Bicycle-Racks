<?php
    // Before including this file, optionally,
    //  1) Set $extra_head_tags to include extra javascript files or css.
    //  2) Set $append_to_title to append the page name to the title.

    /**
     * The element with the "nav-here" class will have it's background turned white
     */
    function highlightPage($page)
    {
        return ($_SERVER['PHP_SELF'] == $page) ? 'class="nav-here"' : '';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- From https://developers.google.com/web/fundamentals/getting-started/your-first-multi-screen-site/responsive?hl=en -->
    <!-- Sets the viewport to 100% and fixes zoom when switching to landscape (on mobile devices). -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/main.css">
    <?php
        echo $extra_head_tags;
    ?>
    <title>Bicycle Racks<?php echo (isset($append_to_title)) ? $append_to_title : ''; ?></title>
</head>
<body>

    <header>
        Bicycle Racks
    </header>

    <nav>
        <ul id="nav-list">
            <!-- The nav is just a list of links which gets styled by css. -->
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php" <?php echo highlightPage('/search.php'); ?>>Search</a></li>
            <li><a href="submission.php" <?php echo highlightPage('/submission.php'); ?>>Submit</a></li>

            <?php
                echo '<li><a href="registration.php"' . highlightPage('/registration.php') . '>';
                // Change the Login text if the user is logged in.
                echo (isset($_SESSION['user_name'])) ? "User: " . $_SESSION['user_name'] : 'Login';
                echo '</a></li>';
            ?>
        </ul>
    </nav>