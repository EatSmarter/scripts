<?php

define('API_BASE_URL', 'https://freistil.zendesk.com/api/v2/');
define('API_USERNAME', '');
define('API_PASSWORD', '');
define('VERSION', '1.0-beta');
define('NL' , "\n");

/******************************
 ********* EXCEPTIONS *********
 ******************************/

class ZenDeskCommunicationException extends Exception {
	private $_apiErrorMessage;

	public function setAPIErrorMessage($errorMessage) {
		$this->_apiErrorMessage = $errorMessage;
	}

	public function getAPIErrorMessage() {
		return $this->_apiErrorMessage;
	}

	public function __toString() {
		return 'ZenDeskCommunicationException - ' . $this->getMessage() . ' - API Error: ' . $this->getAPIErrorMessage();
	}
}

class ZenDeskApiResponseException extends Exception {
	private $_apiResponse;

	public function setAPIResponse($response) {
		$this->_apiResponse = $response;
	}

	public function getAPIResponse() {
		return $this->_apiResponse;
	}

	public function __toString() {
		return 'ZenDeskApiResponseException - ' . $this->getMessage() . ' - API response: ' . $this->getAPIResponse();
	}
}

class ZenDeskApiException extends Exception {
	private $_apiErrorTitle;
	private $_apiErrorMessage;

	public function setAPIErrorTitle($title) {
		$this->_apiErrorTitle = $title;
	}

	public function getAPIErrorTitle() {
		return $this->_apiErrorTitle;
	}

	public function setAPIErrorMessage($message) {
		$this->_apiErrorMessage = $message;
	}

	public function getAPIErrorMessage() {
		return $this->_apiErrorMessage;
	}

	public function __toString() {
		return 'ZenDeskApiException - ' . $this->getMessage() . ' - API Error (' . $this->getAPIErrorTitle() . '): ' . $this->getAPIErrorMessage();
	}
}

/******************************
 ***** TERMINAL UTILITIES *****
 ******************************/

/**
 * Writes a line to the console.
 *
 * @param string $message
 *	 The message to print out.
 */
function printLine($message) {
	echo $message . NL;
}

/**
 * Gets the answer to given question.
 *
 * @param string $question
 *	 The question to ask the user.
 *
 * @return bool
 *	 BOOL if the users has answered "yes", else FALSE.
 */
function getYesNoAnswer($question) {
	$result = FALSE;

	while (TRUE) {
		$message = $question . ' [Y/N] ';
		$userInput = getUserInput($message);
		$userInput = trim($userInput);
		$userInput = strtolower($userInput);

		if (in_array($userInput, array('y', 'n'))) {
			$result = ($userInput === 'y');
			break;
		}
	}

	return $result;
}

/**
 * Gets the choice of a list.
 *
 * @param array $options
 *	 An array of options to chose from.
 *	 The key is returned while the value is displayed. The list is not shown
 *	 with the keys as options for the user to input.
 * @param bool $showNext
 *	 Show next page option.
 * @param bool $showPrevious
 *	 Show previous page option.
 *
 * @return mixed
 *
 * @TODO: Finish this.
 */
function getListAnswer($question) {
	// $result = FALSE;

	// while (TRUE) {
	// 	$message = $question . ' [Y/N] ';
	// 	$userInput = getUserInput($message);
	// 	$userInput = trim($userInput);
	// 	$userInput = strtolower($userInput);

	// 	if (in_array($userInput, array('y', 'n'))) {
	// 		$result = ($userInput === 'y');
	// 		break;
	// 	}
	// }

	// return $result;
}

/**
 * Forces the user to input something.
 *
 * @param string $message
 *	 A message to be printed before the user input.
 *
 * @return string
 *	 The string that has been put in.
 */
function getForcedUserInput($message) {
	while (TRUE) {
		$line = getUserInput($message);

		if (strlen($line) !== 0) {
			break;
		}
	}

	return $line;
}

/**
 * Gets user input from shell.
 *
 * @param string $message
 *	 A message to be printed before the user input.
 *
 * @return string
 *	 The string that has been put in.
 */
function getUserInput($message) {
	if (in_array(PHP_OS, array('WINNT', 'Darwin'))) {
	  echo $message;
	  $line = stream_get_line(STDIN, 1024, PHP_EOL);
	}
	else {
	  $line = readline($message);
	}

	return $line;
}

/******************************
 ******* API UTILITIES ********
 ******************************/

/**
 * Makes the curl call to the API.
 *
 * @param string $endPoint
 *	 The API endpoint to call. For example 'users/me'.
 * @param array $data
 *	 The data to send to the API. This parameter is optional, but if given
 *	 the request is automatically turned in to a POST request.
 *
 * @return string
 *	 The response of the API.
 */
function callAPI($endPoint, $data = NULL) {
	$curl = curl_init();

	$credentials = apiCredentials();

	$endPoint = str_replace(API_BASE_URL, '', $endPoint);

	curl_setopt($curl, CURLOPT_URL, API_BASE_URL . $endPoint);
	curl_setopt($curl, CURLOPT_USERPWD, $credentials['username'] . ':' . $credentials['password']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Organization Subscriber Script v' . VERSION);

	if (!empty($data)) {
		$json_data = json_encode($data);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	}

	$result = curl_exec($curl);

	$error = curl_error($curl);

	if (!empty($error)) {
		$exception = new ZenDeskCommunicationException('An error has occurred during calling the API.');
		$exception->setAPIErrorMessage($error);

		throw $exception;
	}

	return $result;
}

/**
 * Stores/Returns credentials.
 *
 * Both parameters are optional. If given the stored credentials are overriden.
 * If available this function uses the values from the constants API_USERNAME
 * and API_PASSWORD as default.
 *
 * @param string $username
 *	 The username.
 * @param string $password
 *	 The password.
 *
 * @return array
 *	 The credentials.
 */
function apiCredentials($username = NULL, $password = NULL) {
	static $storedCredentials;

	if (empty($storedCredentials) && defined('API_USERNAME') && !empty(API_USERNAME) && defined('API_PASSWORD') && !empty(API_PASSWORD)) {
		$storedCredentials = array(
			'username' => API_USERNAME,
			'password' => API_PASSWORD,
		);
	};

	if (!is_null($username) && !is_null($password)) {
		$storedCredentials = array(
			'username' => $username,
			'password' => $password,
		);
	}

	return $storedCredentials;
}

/**
 * Converts the given json string to an Object.
 *
 * @param string $jsonString
 *	 The json string to parse.
 */
function parseJSON($jsonString) {
	$result = json_decode($jsonString);

	if (!$result) {
		throw new Exception('JSON string could not be parsed.');
	}

	return $result;
}

/**
 * Gets data from the API.
 *
 * @param string $endPoint
 *	 The API endpoint to call.
 *
 * @see callAPI()
 */
function getDataFromAPI($endPoint) {
	$api_call_result = callAPI($endPoint);

	try {
		$result = parseJSON($api_call_result);
	}
	catch (Exception $e) {
		$exception = new ZenDeskApiResponseException('The response of the API could not be parsed.');
		$exception->setAPIResponse($api_call_result);

		throw $exception;
	}

	if (!empty($result->error)) {
		$errorTitle = !empty($result->error->title) ? $result->error->title : '';
		$errorMessage = !empty($result->error->message) ? $result->error->title : '';

		if (empty($errorTitle)) {
			$errorTitle = $result->error;
		}

		if (empty($errorMessage)) {
			$errorMessage = $errorTitle;
		}

		$exception = new ZenDeskApiException('An error was returned by the API.');
		$exception->setAPIErrorTitle($errorTitle);
		$exception->setAPIErrorMessage($errorMessage);

		throw $exception;
	}

	return $result;
}

/**
 * Send data to the API.
 *
 * @param string $endPoint
 *	 The API endpoint to call.
 *
 * @see callAPI()
 */
function sendDataToAPI($endPoint, $data) {
	$api_call_result = callAPI($endPoint, $data);

	try {
		$result = parseJSON($api_call_result);
	}
	catch (Exception $e) {
		$exception = new ZenDeskApiResponseException('The response of the API could not be parsed.');
		$exception->setAPIResponse($api_call_result);

		throw $exception;
	}

	if (!empty($result->error)) {
		$exception = new ZenDeskApiException('An error was returned by the API.');
		$error_details = NULL;
		if ($result->details) {
			$error_details = (array) $result->details;
		}
		$exception->setAPIErrorTitle($result->error);
		$exception->setAPIErrorMessage($result->description . NL . 'Details: ' . var_export($error_details, TRUE));

		throw $exception;
	}

	return $result;
}

/**
 * Collects the credentials for the api and saves them.
 */
function getApiCredentialsFromPrompt() {
	$username = getForcedUserInput('Please provide your username/email address: ');
	// @TODO: This is plain text. Find a way to masquerade it.
	$password = getForcedUserInput('Please provide your password/token: ');

	apiCredentials($username, $password);
}

/******************************
 ******** APP SPECIFIC ********
 ******************************/

/**
 * Subscribes the current (authenticated) user to his/her current organization.
 */
function subscribeAuthenticatedUserToItsOrganization() {
	getApiCredentialsFromPrompt();

	// Get user id and organization id from current user.
	$userData = getDataFromAPI('users/me');
	$userId = $userData->user->id;
	$organizationId = $userData->user->organization_id;

	if (isUserSubscribedToOrganization($userId, $organizationId)) {
		printLine('You have already subscribed to your organization.');
	}
	else {
		printLine('You have NOT subscribed to the tickets in your organization.');
		$subscribe = getYesNoAnswer('Do you want to subscribe now?');

		if ($subscribe) {
			$subscribtion_result = subscribeUserToOrganization($userId, $organizationId);
		}
	}
}

/**
 * Checks if the given user is subscribed to the given organization.
 *
 * @param int $userId
 *	 The user id to check.
 * @param int $organizationId
 *
 * @return bool
 *	 TRUE if subscribed, else FALSE.
 */
function isUserSubscribedToOrganization($userId, $organizationId) {
	$result = FALSE;
	$subscriptions = getDataFromAPI('users/' . $userId . '/organization_subscriptions');

	do {
		foreach ($subscriptions->organization_subscriptions as $subscription) {
			if ($subscription->organization_id == $organizationId) {
				$result = TRUE;
				break;
			}
		}

		if ($subscriptions->next_page) {
			$subscriptions = getDataFromAPI($subscriptions->next_page);
		}
	} while ($subscriptions->next_page);

	return $result;
}

/**
 * Subscribes user to organization.
 *
 * @param int $userId
 *	 The user id.
 * @param int $organizationId
 *	 The organization id.
 *
 * @return bool
 */
function subscribeUserToOrganization($userId, $organizationId) {
	$data = array(
		'organization_subscription' => array(
			'user_id' => $userId,
			'organization_id' => $organizationId,
		),
	);

	$result = sendDataToAPI('organization_subscriptions', $data);

	return $result;
}

/**
 * Runs the agent's routine for subscribing a user to an organization.
 *
 * @param string $userId
 *	 The user id to activate subscriptions for.
 * @param string $organizationId
 *	 The organization the user should get subscribed to.
 *
 * @TODO: Finish this. See inline comments.
 */
function subscribeEndUserToOrganizationAsAgent($userId, $organizationId) {
	getApiCredentialsFromPrompt();

	// Get all organizations and let the agent choose from it.
	// $organizationId = getOrganizationIdFromAgentsOrganizations();
	// Get the users from that organization and let the agent choose from it.
	// $userId = getUserIdFromOrganization();

	subscribeUserToOrganization($userId, $organizationId);
}

/**
 * Tries to find the correct organization from the agents assigned ones.
 *
 * @return int
 *	 The organization id.
 *
 * @TODO: Finish this.
 */
function getOrganizationIdFromAgentsOrganizations() {
	$result = 0;
	$organizations = getDataFromAPI('organizations');

	do {
		foreach ($organizations->organizations as $organization) {
			if ($organization->organization_id == $organizationId) {
				$result = TRUE;
				break;
			}
		}

		if ($subscriptions->next_page) {
			$subscriptions = getDataFromAPI($subscriptions->next_page);
		}
	} while ($subscriptions->next_page);

	return $result;
}

/**
 * Main function to process it all.
 *
 * @param array $arguments
 *	 The arguments given to the scripts.
 *
 * @return int
 *	 The status code to exit the shell script with.
 */
function main($arguments) {
	$result = 0;

	$first_argument = !empty($arguments[1]) ? $arguments[1] : '';

	try {
		switch ($first_argument) {
			case 'help':
			default:
				help();
				break;

			case 'user':
				subscribeAuthenticatedUserToItsOrganization();
				break;

			case 'agent':
				if (empty($arguments[2]) || empty($arguments[3])) {
					help();
					$result = 1;
				}
				else {
					subscribeEndUserToOrganizationAsAgent($arguments[2], $arguments[3]);
				}
				break;

		}
	}
	catch (Exception $e) {
		echo $e;
		$result = 1;
	}

	return $result;
}

/**
 * Outputs help for the script.
 */
function help() {
	echo <<<HELP
This script helps you to subscribe yourself or if you are an agent one of your
organization users to its organization in ZenDesk which is currently laking of such funtionality in the frontend.

If you are a user you can simple call this script like this

    $ php addZenDeskSubscriber user


As an agent you have to supply the user id and the organization id you want to use.

    $ php addZenDeskSubscriber agent USER_ID ORGANIZATION_ID


Thank you for using this script.


HELP;
}

return main($argv);
