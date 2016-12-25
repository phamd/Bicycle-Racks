<?php
// This file holds common functions used throughout the project sorted into Classes.
// It should really be split into multiple files.

/*
  Accessed like this: Config::db_host
*/
Class Config
{
    const debug = false;
    const db_host = '127.0.0.1',
          db_user = '',
          db_pass = '',
          db_name = 'bicycleracks';

    const aws_s3_bucket = 'bicycleracks';
    const aws_s3_cred = array(
        'credentials' => array(
            'key'    => '',
            'secret' => '',
        ),
        'region' => 'us-east-2',
        'version' => 'latest'
    );
    const aws_s3_prefix = 'https://bicycleracks.s3.amazonaws.com/';
}

Class Database
{
    // Enum options for what to return from execute.
    const GET_ROWS = 1; // Fetch all rows from the statement.
    const GET_LAST_INSERT_ID = 2; // Get the ID of the row from the last insert statement.

    /**
     * A wrapper around a prepared PDO statement.
     * Inputs are properly separated from the query.
     * The $getType parameter determines what the function returns.
     */
    function execute($query, $inputs, $getType = self::GET_ROWS)
    {
        $result = null;
        try
        {
            $dbhandle = new PDO("mysql:host=".Config::db_host.";dbname=".Config::db_name, Config::db_user, Config::db_pass);
            $statement = $dbhandle->prepare($query);
            $statement->execute($inputs);

            // Switch to handle what to return.
            switch ($getType) {
                case self::GET_ROWS:
                    // FETCH_BOTH to get numbered indexes as well as named indexes.
                    $result = $statement->fetchAll(PDO::FETCH_BOTH);
                    break;
                case self::GET_LAST_INSERT_ID:
                    // Get the id of the row from the last insert statement.
                    $result = $dbhandle->lastInsertId();
                    break;
                default:
                    break;
            }

            // Explicitly assigning null to destroy the objects and close connections.
            $statement = null;
            $dbhandle = null;
        }
        catch (PDOException $e)
        {
            // Catch database errors and put them in the server logs so the user doesn't see them.
            error_log($e->getMessage());
        }
        return $result;
    }

}

Class Common
{
    /**
     * Convert a rating number (0-5) to a sequence of ascii stars.
     */
    function ratingToAscii($rating)
    {
        $filled = '&#9733;';
        $empty = '&#9734;';
        $output = '';
        for ($i = 0; $i < 5; ++$i) {
            $output .= ($i < $rating) ? $filled : $empty;
        }
        return $output;
    }

    /**
     * Return variable if it's set, otherwise return default.
     * Variable is passed as a reference to prevent errors from passing an undefined variable.
     */
    function ifsetor(&$variable, $default = null)
    {
        return (isset($variable)) ? $variable : $default;
    }
}

Class Validate
{
    /**
     * Return if id is a positive integer.
     */
    function id($id)
    {
        return isset($id) && preg_match('/^[0-9]+$/', $id);
    }

    /**
     * Return if username is alpha-numeric.
     */
    function username($username)
    {
        return isset($username) && preg_match('/^[a-zA-Z0-9]{1,20}$/', $username);
    }

    /**
     * Return if password is between 6 and 72 characters.
     */
    function password($password)
    {
        return isset($password) && strlen($password) >= 6 && strlen($password) <= 72;
    }

    /**
     * Return if email matches the W3C specification for email addresses.
     */
    function email($email)
    {
        return isset($email) && preg_match("/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/", $email);
    }

    /**
     * Return if email matches the W3C specification for email addresses.
     */
    function date($date)
    {
        return isset($date) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $date);
    }

    /**
     * Return if rating is between 1 and 5.
     */
    function rating($rating)
    {
        return isset($rating) && $rating <= 5 && $rating >= 1;
    }

    /**
     * Return if object name matches the pattern.
     */
    function objName($objName)
    {
        return isset($objName) && preg_match("/^[a-zA-Z0-9!@#$%^&*()':;,.\/\- ]{1,32}$/", $objName);
    }

    /**
     * Return if location is a coordinate pair.
     */
    function location($location)
    {
        return isset($location) && preg_match("/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/", $location);
    }
}
?>