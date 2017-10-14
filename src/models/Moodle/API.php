<?php
/**
 * Do better with this PHP based package that manipulates Moodle's API.
 * This wrapper will allow you to easily access those annoying
 * Moodle web service API functions
 *
 * A complete list of available functions can be found here:
 *
 *      https://docs.moodle.org/dev/Web_service_API_functions
 *
 * Can be instantiated like so:
 *
 *      use Likel\Moodle\API as MoodleAPI;
 *      $mdl = new MoodleAPI();
 *
 * @package     moodle-web-service-wrapper
 * @author      Liam Kelly <https://github.com/likel>
 * @copyright   2017 Liam Kelly
 * @license     MIT License <https://github.com/likel/moodle-web-service-wrapper/blob/master/LICENSE>
 * @link        https://github.com/likel/moodle-web-service-wrapper
 * @version     1.0.0
 */
namespace Likel\Moodle;

class API extends Engine
{
    private $db; // Store the database connection
    private $secret_hash; // Hold the secret hash for encryption
    private $session_hash_algorithm = "sha512"; // Algorithm to hash the session variables

    /**
     * Construct the Moodle API object
     * Setup the parent Engine class
     *
     * @param array $parameters An assoc. array that holds the session parameters
     * @return void
     */
    function __construct($parameters = array())
    {
        if(!is_array($parameters)) {
            $parameters = array();
        }

        $parameters["credentials_location"] = empty($parameters["credentials_location"]) ? __DIR__ . '/../../ini/credentials.ini' : $parameters["credentials_location"];

        parent::__construct($parameters["credentials_location"]);
    }

    /**
     * Create users from a supplied array of users
     *
     * Expects the following format as a minimum:
     *
     *     array(
     *         array(
     *             "username" => "likel",
     *             "firstname" => "Liam",
     *             "lastname" => "Kelly",
     *             "email" => "email@email.com"
     *         ),
     *         array(
     *             "username" => "likel2",
     *             "firstname" => "Liam2",
     *             "lastname" => "Kelly2",
     *             "email" => "email2@email.com"
     *         )
     *     )
     *
     * @param array $users The array of users
     * @return array
     */
    public function createUsers($users = null)
    {
        if(!is_array($users)) {
            return false;
        }

        $final_user_list = array();

        foreach($users as $user) {
            if(empty($user["username"]) || empty($user["firstname"]) || empty($user["lastname"]) || empty($user["email"])) {
                continue;
            } else {
                $final_user_list[] = array(
                    "username" => $user["username"],
                    "firstname" => $user["firstname"],
                    "lastname" => $user["lastname"],
                    "email" => $user["email"],
                    "idnumber" => !empty($user["idnumber"]) ? $user["idnumber"] : "",
                    "lang" => !empty($user["lang"]) ? $user["lang"] : "en",
                    "mailformat" => !empty($user["mailformat"]) ? $user["mailformat"] : 1,
                    "auth" => !empty($user["auth"]) ? $user["auth"] : "manual",
                    "createpassword" => 1
                );
            }
        }

        $response = $this->call('core_user_create_users', array('users' => $final_user_list));

        if($response["success"] == false) {
            // Check if the user already exists and that's why there's an error
            $check_exists = $this->checkUsernameAndEmail($user["username"], $user["email"]);
            return ($check_exists == false) ? $response : $check_exists;
        } else {
            return $response;
        }
    }

    /**
     * Get a singular user, extends the createUsers function
     *
     * @param array $user The singular user as an array
     * @return array
     */
    public function createUser($user = null)
    {
        $create_user = $this->createUsers(array($user));

        if($create_user["success"] == true) {
            return array(
                "success" => "true",
                "response" => array(
                    "id" => $create_user["response"][0]["id"],
                    "username" => $create_user["response"][0]["username"]
                )
            );
        } else {
            return $create_user;
        }
    }

    /**
     * Search and return a list of users
     *
     * Expects any permutation of the following format:
     *
     *     array(
     *         'id' => 12,
     *         'firstname' => 'Liam',
     *         'lastname' => 'Kelly',
     *         'idnumber' => '0001',
     *         'username' => 'likel',
     *         'email' => 'email@email.com'
     *     )
     *
     * @param array $search_params The fields to search
     * @return array
     */
    public function getUsers($search_params = null)
    {
        if(!is_array($search_params)) {
            return false;
        }

        $search_list = array();

        foreach($search_params as $key => $value) {
            $search_list[] = array("key" => $key, "value" => $value);
        }

        $response = $this->call('core_user_get_users', array('criteria' => $search_list));
        return $response;
    }

    /**
     * Determine if the user exists from supplied params
     * Extends the getUsers function
     *
     * @param array $search_params The fields to search
     * @return bool
     */
    public function userExists($search_params = null)
    {
        if(!is_array($search_params)) {
            return false;
        }

        $search_list = array();

        foreach($search_params as $key => $value) {
            $search_list[] = array("key" => $key, "value" => $value);
        }

        $response = $this->call('core_user_get_users', array('criteria' => $search_list));

        if($response["success"] == false) {
            return false;
        } else {
            if(!empty($response["response"]["users"])) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Extend the userExists function to search for username or email
     * Will return false if neither are found
     *
     * @param string $username The username to search for
     * @param string $email The email to search for
     * @return mixed
     */
    private function checkUsernameAndEmail($username, $email) {
        // Check if the username exists
        $username_exists = $this->userExists(array('username' => $username));

        if($username_exists) {
            return array(
                "success" => false,
                "message" => "Username already exists",
                "short" => "username_exists"
            );
        } else {
            // Username doesn't exist, check if the email exists
            $email_exists = $this->userExists(array('email' => $email));

            if($email_exists) {
                return array(
                    "success" => false,
                    "message" => "Email already exists",
                    "short" => "email_exists"
                );
            } else {
                return false;
            }
        }
    }

    /**
     * Handle curl calls made to the moodle API
     * Wrapper for the Engine->call() method
     *
     * @param string $function_name The function name from the webservice API
     * @param array $payload The array of parameters to pass to the webservice
     * @return array
     */
    public function call($function_name, $payload)
    {
        return parent::call($function_name, $payload);
    }
}
