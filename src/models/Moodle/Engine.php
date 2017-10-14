<?php
/**
 * The webservice API engine that helps the API object connect and call
 *
 * @package     moodle-web-service-wrapper
 * @author      Liam Kelly <https://github.com/likel>
 * @copyright   2017 Liam Kelly
 * @license     MIT License <https://github.com/likel/moodle-web-service-wrapper/blob/master/LICENSE>
 * @link        https://github.com/likel/moodle-web-service-wrapper
 * @version     1.0.0
 */
namespace Likel\Moodle;

class Engine
{
    private $url; // Your moodle site URL
    private $webservice_token; // The webservice token generated in Moodle
    private $rest_format = "json"; // Default to json, otherwise leave blank for XML

    /**
     * Construct the moodle Engine object
     * Set the url, token and rest_format
     *
     * @param string $credentials_location The credentials.ini file location
     * @return void
     */
    function __construct($credentials_location  = __DIR__ . '/../../ini/credentials.ini')
    {
        // Attempt to get the engine parameters from the credentials.ini file
        try {
            $this->loadCredentials($credentials_location);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

    /**
	 * Handle curl calls made to the moodle API
	 *
	 * @param string $function_name The function name from the webservice API
	 * @param array $payload The array of parameters to pass to the webservice
	 * @return array
	 */
	public function call($function_name, $payload)
    {
        // Generate the URL
        $server_url = $this->url . '/webservice/rest/server.php?wstoken=' . $this->webservice_token . '&wsfunction=' . $function_name;
		$rest_format = ($this->rest_format == 'json') ? '&moodlewsrestformat=' . $this->rest_format : '';

        // Create the curl request
        $curl_request = curl_init();
        curl_setopt($curl_request, CURLOPT_URL, $server_url . $rest_format);
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
     * Supply us with a friendly success message
     *
     * @param array $response The response from the call
     * @return array
     */
    private function parseResponse($response)
    {
        if(is_array($response)) {
            if(!empty($response['exception'])) {
                return array(
                    "success" => false,
                    "message" => $response['message']
                );
            } else {
                return $response;
            }
        } else {
            return array(
                "success" => false,
                "message" => "Response was not an array"
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

                if(!empty($credentials["rest_format"])) {
                    $this->rest_format = ($credentials["rest_format"] == "xml") ? "xml" : "json";
                }
            } else {
                throw new \Exception('The \'moodle_api\' parameter in the credentials file is empty');
            }
        } else {
            throw new \Exception('The credentials file could not be located at ' . $credentials_location);
        }
    }
}
