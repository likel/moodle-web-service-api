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

class API
{
    private $url; // Your moodle site URL
    private $webservice_token; // The webservice token generated in Moodle

    /**
     * Construct the Moodle API object
     * Setup the parent Engine class
     *
     * @param array $parameters An assoc. array that holds the Moodle parameters
     * @return void
     */
    function __construct($parameters = array())
    {
        if(!is_array($parameters)) {
            $parameters = array();
        }

        $parameters["credentials_location"] = empty($parameters["credentials_location"]) ? __DIR__ . '/../../ini/credentials.ini' : $parameters["credentials_location"];

        // Attempt to get the engine parameters from the credentials.ini file
        try {
            $this->loadCredentials($parameters["credentials_location"]);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
     * core_user_create_users
     *
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
     * core_user_create_users
     *
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
     * core_user_update_users
     *
     * Update users from a supplied array of users
     *
     * Expects the following format as a minimum:
     *
     *     array(
     *         array(
     *             "id" => 2,
     *             "firstname" => "Liam"
     *         ),
     *         array(
     *             "id" => 3,
     *             "firstname" => "Liam"
     *         )
     *     )
     *
     * @param array $users The array of users
     * @return array
     */
    public function updateUsers($users = null)
    {
        if(!is_array($users)) {
            return false;
        }

        $final_user_list = array();

        foreach($users as $user) {
            if(empty($user["id"])) {
                continue;
            } else {
                $final_user_list[] = array(
                    "id" => $user["id"],
                    "username" => !empty($user["username"]) ? $user["username"] : "",
                    "firstname" => !empty($user["firstname"]) ? $user["firstname"] : "",
                    "lastname" => !empty($user["lastname"]) ? $user["lastname"] : "",
                    "email" => !empty($user["email"]) ? $user["email"] : "",
                    "idnumber" => !empty($user["idnumber"]) ? $user["idnumber"] : "",
                    "lang" => !empty($user["lang"]) ? $user["lang"] : "en",
                    "mailformat" => !empty($user["mailformat"]) ? $user["mailformat"] : 1,
                    "auth" => !empty($user["auth"]) ? $user["auth"] : "manual"
                );
            }
        }

        $response = $this->call('core_user_update_users', array('users' => $final_user_list));

        if($update_user["short"] == "not_array") {
            return array(
                "success" => "true",
                "response" => "updated"
            );
        } else {
            return $response;
        }
    }

    /**
     * core_user_update_users
     *
     * Update a singular user, extends the updateUser function
     *
     * @param array $users The singular user as an array
     * @return array
     */
    public function updateUser($user = null)
    {
        $update_user = $this->updateUsers(array($user));

        if($update_user["short"] == "not_array") {
            return array(
                "success" => "true",
                "response" => "updated"
            );
        } else {
            return $update_user;
        }
    }

    /**
     * core_user_get_users
     *
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
     * core_user_get_users
     *
     * Search and return a user profile
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
    public function getUser($search_params = null)
    {
        $user_search = array();

        if(!empty($search_params["id"])) {
            $user_search["id"] = $search_params["id"];
        }

        if(!empty($search_params["username"])) {
            $user_search["username"] = $search_params["username"];
        }

        return $this->getUsers($user_search);
    }

    /**
     * core_user_delete_users
     *
     * Delete users from a supplied array of users
     *
     * Expects the following format:
     *
     *     array(
     *         array(
     *             "id" => 2
     *         ),
     *         array(
     *             "id" => 3
     *         )
     *     )
     *
     * @param array $users The array of users
     * @return array
     */
    public function deleteUsers($users = null)
    {
        if(!is_array($users)) {
            return false;
        }

        $response = $this->call('core_user_delete_users', array('userids' => $users));

        if($response["short"] == "not_array") {
            return array(
                "success" => "true",
                "response" => "deleted"
            );
        } else {
            return $response;
        }
    }

    /**
     * core_user_delete_users
     *
     * Delete a user with a supplied id
     *
     * @param int $id The id of a user
     * @return array
     */
    public function deleteUser($id = null)
    {
        if(!is_numeric($id)) {
            return false;
        }

        return $this->deleteUsers(array($id));
    }

    /**
     * core_user_get_users
     *
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
     * Allow the user to call any method that isn't supported in this wrapper
     * https://docs.moodle.org/dev/Web_service_API_functions
     *
     * @param string $function_name The function name from the webservice API
     * @param array $payload The array of parameters to pass to the webservice
     * @return array
     */
    public function any($function_name, $payload)
    {
        return $this->call($function_name, $payload);
    }

    /**
     * Handle curl calls made to the moodle API
     *
     * @param string $function_name The function name from the webservice API
     * @param array $payload The array of parameters to pass to the webservice
     * @return array
     */
    private function call($function_name, $payload)
    {
        // Generate the URL
        $server_url = $this->url . '/webservice/rest/server.php?wstoken=' . $this->webservice_token .
        '&wsfunction=' . $function_name . '&moodlewsrestformat=json';

        // Create the curl request
        $curl_request = curl_init();
        curl_setopt($curl_request, CURLOPT_URL, $server_url);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query($payload));

        // Return the result
        $response = curl_exec($curl_request);
        curl_close($curl_request);
        return $this->parseResponse(json_decode($response, true));
    }

    /**
     * Extend the userExists function to search for username or email
     * Will return false if neither are found
     *
     * @param string $username The username to search for
     * @param string $email The email to search for
     * @return mixed
     */
    private function checkUsernameAndEmail($username, $email)
    {
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
     * Supply us with a friendly success message
     *
     * @param array $response The response from the call
     * @return array
     */
    private function parseResponse($response)
    {
        if(is_array($response)) {
            if(!empty($response['exception'])) {
                if($response['message'] == "Access control exception") {
                    return array(
                        "success" => false,
                        "message" => 'The function has not been added to the webservice on Moodle',
                        "short" => "function_not_added"
                    );
                } else {
                    return array(
                        "success" => false,
                        "message" => $response['message'],
                        "short" => "generic_error"
                    );
                }
            } else {
                return array(
                    "success" => true,
                    "response" => $response
                );
            }
        } else {
            return array(
                "success" => false,
                "message" => "Response was not an array",
                "short" => "not_array"
            );
        }
    }

    /**
     * Attempt to retrieve the credentials from the credentials file
     *
     * @param array $credentials_location The credentials.ini file location
     * @return void
     * @throws \Exception If credentials are empty or not found
     */
    private function loadCredentials($credentials_location)
    {
        if(file_exists($credentials_location)) {
            $moodle_credentials = parse_ini_file($credentials_location, true);
            $credentials = $moodle_credentials["moodle_api"];

            if(!empty($credentials)){
                if(!empty($credentials["url"])) {
                    $this->url = $credentials["url"];
                } else {
                    throw new \Exception('The \'url\' variable is empty');
                }

                if(!empty($credentials["token"])) {
                    $this->webservice_token = $credentials["token"];
                } else {
                    throw new \Exception('The \'token\' variable is empty');
                }
            } else {
                throw new \Exception('The \'moodle_api\' parameter in the credentials file is empty');
            }
        } else {
            throw new \Exception('The credentials file could not be located at ' . $credentials_location);
        }
    }

    /**
     * Return or echo our supported webservice functions
     *
     * @param bool $echo Should we echo or return the list
     * @return array|string
     */
    public function available($echo = false)
    {
        if(file_exists(__DIR__ . "/available_functions.json")) {
            if($echo) {
                echo file_get_contents(__DIR__ . "/available_functions.json");
            } else {
                return json_decode(file_get_contents(__DIR__ . "/available_functions.json"), true);
            }
        } else {
            return false;
        }
    }
}
