<?php
/**
 * Example usage of the moodle-web-service-wrapper package
 *
 * @package     moodle-web-service-wrapper
 * @author      Liam Kelly <https://github.com/likel>
 * @copyright   2017 Liam Kelly
 * @license     GPL-3.0 License <https://github.com/likel/moodle-web-service-wrapper/blob/master/LICENSE>
 * @link        https://github.com/likel/moodle-web-service-wrapper
 * @version     1.0.0
 */

header('Content-Type:text/plain');

// Load the scripts
require_once("autoload.php");

// This looks nicer and makes the session Handler easier to call
use Likel\Moodle\API as MoodleAPI;

// Create a new moodle wrapper. Example parameters include:
//      $mdl = new MoodleAPI(array(
//          'credentials_location' => "/path/to/new/credentials.ini",
//      ));
$mdl = new MoodleAPI();

// Display available functions
$response = $mdl->available(true);

// Create a new user
$new_user = $mdl->createUser(
    array(
        "username" => "test001",
        "firstname" => "Test",
        "lastname" => "Last",
        "email" => "test@test.com"
    )
);

// Successfully created a new user
if($new_user["success"]) {
    // Check if the user exists
    $user_exists = $mdl->userExists(array(
        'username' => $new_user["response"]["username"]
    ));

    // Enrol the user into a course
    $enrolment = $mdl->enrolUser(array(
        "roleid" => "5",
        "userid" => $new_user["response"]["id"],
        "courseid" => "2"
    ));

    // Get the user's course enrolments
    $course_enrolments = $mdl->getUsersCourses($new_user["response"]["id"]);
    var_dump($course_enrolments);

} else {
    // Something went wrong, show informative error
    var_dump($new_user);
}
