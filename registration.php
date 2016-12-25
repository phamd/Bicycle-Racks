<?php
    session_start();
    require_once 'php/common.inc.php';

    // Server response message to the user; Messages will be appended to this string.
    $response_message = '';

    /**
     * Compare the credentials with the database. Update session on successful login.
     * Return whether the action was successful.
     */
    function loginUser($username, $password)
    {
        // Database::execute uses fetchAll, so $results[0] is the found user.
        $results = Database::execute('SELECT * FROM Users WHERE Username = ?', array($username));

        // Verify if inputted user password is correct.
        if (isset($results[0]) && password_verify($password, $results[0]['passwordhash']))
        {
            // Set session variables for an authencated user.
            $_SESSION['user_id'] = $results[0]['id'];
            $_SESSION['user_name'] = $results[0]['username'];
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Register a new user in the database.
     * Return whether the action was successful.
     */
    function registerUser($username, $password, $email, $age)
    {
        // Check if user already exists.
        $results = Database::execute('SELECT * FROM Users WHERE username = ? OR email = ?', array($username, $email));
        if (count($results)) {
            $response_message .= 'Error: Username or email already exists in database.<br/>';
            return false;
        }

        // Salt is automatically generated and stored along-side (concatenated to) the hash.
        $passhash = password_hash($password, PASSWORD_BCRYPT);
        // Note: The user's age isn't stored in the database.
        $results = Database::execute('INSERT INTO Users (username, passwordhash, email) VALUES (?, ?, ?)', array($username, $passhash, $email));
        return true;
    }

    // These variables are here to be available for re-filling in the HTML forms on errors.
    // For example, when the user attempts to log in with an incorrect password, their username will stay in the form.
    // However, if the field is invalid (contains illegal characters), it is replaced with null.
    $username = isset($_POST['user-name']) ? $_POST['user-name'] : null;
    $password = isset($_POST['user-password']) ? $_POST['user-password'] : null; // Note: Passwords wont go back into the form.
    $email = isset($_POST['user-email']) ? $_POST['user-email'] : null;
    $age = isset($_POST['user-age']) ? $_POST['user-age'] : null;

    // Validate fields that are common to both forms.
    if (isset($_POST['login']) || isset($_POST['register']))
    {
        $valid = true;
        if (!Validate::username($username)) {
            $response_message .= 'Username invalid: Enter 1-20 alpha-numeric characters.<br/>';
            $username = null; // Set to null so it doesn't go back into the form; this also means htmlspecialchars() isn't needed later.
            $valid = false;
        }
        if (!Validate::password($password)) {
            $response_message .= 'Password invalid: Length should be between 6 and 72.<br/>';
            $password = null;
            $valid = false;
        }
    }

    if (isset($_POST['logout'])) // Logout is a POST or else some browsers may pretetch it.
    {
        // Unset since the session data would still exist until the user reloads.
        session_unset();
        // Destroy the current session.
        session_destroy();
    }
    else if (isset($_POST['login'])) // Login form.
    {
        // If validation passed, attempt to log in the user.
        if ($valid) {
            if (loginUser($username, $password)) {
                $response_message .= 'Successfully logged in.<br/>';
            } else {
                $response_message .= 'Login unsuccessful: incorrect username or password.<br/>';
            }
        }
    }
    else if (isset($_POST['register'])) // Register form.
    {
        // Validate registration specific fields.
        if (!Validate::email($email)) {
            $response_message .= 'Email invalid: Enter a valid email (e.g. a@b.com).<br/>';
            $email = null;
            $valid = false;
        }
        if (!Validate::date($age)) {
            $response_message .= 'Age invalid: Enter a valid date (yyyy-mm-dd).<br/>';
            $valid = null;
            $valid = false;
        }
        // If validation passed, attempt to register the user.
        if ($valid) {
            if (registerUser($username, $password, $email, $age)) {
                $response_message .= 'Successfully registered. You may now log in.<br/>';
            } else {
                $response_message .= 'Failed to register new user.<br/>';
            }
        }
    }
?>

<?php
    // BEGIN HTML
    $extra_head_tags = '
        <script type="text/javascript" src="js/registration.js"></script>
    ';
    require_once 'php/header_nav.inc.php';
?>

    <main>
        <?php
            // The server responses (success, error) to the user are written here.
            echo '<div><center><p>';
            echo $response_message;
            echo '</p></center></div>';

            // If the user is logged in, show a logout button.
            if (isset($_SESSION['user_id']))
                echo '
                <form name="logout" action="registration.php" method="post"">
                    <ul>
                        <li class="form-row">
                            <input type="hidden" name="logout" value="true"/>
                            <input class="form-right" type="submit" value="Logout"/>
                        </li>
                    </ul>
                </form>
                ';

            // The following div is hidden if the user is logged in, as well.
        ?>

        <div class="left-right-split" <?php if (isset($_SESSION['user_id'])) echo 'style="display: none;"'; ?>>
            <div class="left">
                <!-- "registration.validate_reg()" is jsFileName.functionName() -->
                <form name="register" method="post" onsubmit="return registration.validate_reg();">
                    <ul>
                        <li class="form-row">
                            <h2>
                                Register a new account
                            </h2>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="reg-user-name">Username</label>
                            <!-- Added the "required" attribute and client-side validation for alphanumeric username less than 15 characters long.
                                 I'll use javascript (this.setCustomValidity()) to change the message that pops up so the user knows this limitation. -->
                            <!-- Part 2: Removed html validation (pattern="^[a-zA-Z0-9]{1,15}$" required) to add javascript validation -->
                            <div class="form-right">
                                <input class="flex-column" type="text" name="user-name" id="reg-user-name" placeholder="username" <?php if(isset($_POST['register'])) echo 'value="'.$username.'"'; ?>/>
                                <span class="flex-column form-hint"></span>
                            </div>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="reg-user-email">Email</label>
                            <div class="form-right">
                                <input class="flex-column" type="email" name="user-email" id="reg-user-email" placeholder="email" <?php if(isset($_POST['register'])) echo 'value="'.$email.'"'; ?>/>
                                <span class="flex-column form-hint"></span>
                            </div>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="reg-user-password">Password</label>
                            <div class="form-right">
                                <input class="flex-column" type="password" name="user-password" id="reg-user-password" placeholder="password"/>
                                <span class="flex-column form-hint"></span>
                            </div>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="reg-user-age">Age</label>
                            <div class="form-right">
                                <input class="flex-column" type="date" name="user-age" id="reg-user-age" <?php if(isset($_POST['register'])) echo 'value="'.$age.'"'; ?>/>
                                <span class="flex-column form-hint"></span>
                            </div>
                        </li>
                        <li class="form-row">
                            <input type="hidden" name="register" value="true"/>
                            <input class="form-right" type="submit" value="Register"/>
                        </li>
                    </ul>
                </form>
            </div>
            <div class="right">
                <form name="login" method="post" onsubmit="return registration.validate_login();">
                    <ul>
                        <li class="form-row">
                            <h2>
                                Log-in
                            </h2>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="login-user-name">Username</label>
                            <div class="form-right">
                                <input class="flex-column" type="text" name="user-name" id="login-user-name" placeholder="username" <?php if(isset($_POST['login'])) echo 'value="'.$username.'"'; ?>/>
                                <span class="flex-column form-hint"></span>
                            </div>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="login-user-password">Password</label>
                            <div class="form-right">
                                <input class="flex-column" type="password" name="user-password" id="login-user-password" placeholder="password"/>
                                <span class="flex-column form-hint"></span>
                            </div>
                        </li>
                        <li class="form-row">
                            <label class="form-left" for="login-user-remember">Remember me</label>
                            <div class="form-right">
                                <input class="flex-column" type="checkbox" name="user-remember" id="login-user-remember"/>
                            </div>
                        </li>
                        <li class="form-row">
                            <input type="hidden" name="login" value="true"/>
                            <input class="form-right" type="submit" value="Log-in"/>
                        </li>
                    </ul>
                </form>
            </div>
        </div>
    </main>

<?php
    require_once 'php/footer.inc.php';
?>