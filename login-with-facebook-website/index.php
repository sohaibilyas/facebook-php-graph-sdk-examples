<?php
if (!session_id()) {
	session_start();
}

// getting all required facebook graph sdk files
require('./vendor/autoload.php');

// app configurations
define('APP_ID', '123456789987654312');
define('APP_SECRET', 'qwerty123456789qwerty123456789');
define('GRAPH_VERSION', 'v3.0');
define('APP_URL', 'https://yourwebsite.com/');

$fb = new Facebook\Facebook([
  'app_id' => APP_ID,
  'app_secret' => APP_SECRET,
  'default_graph_version' => GRAPH_VERSION
]);

$helper = $fb->getRedirectLoginHelper();

$accessToken = $helper->getAccessToken();

if (isset($accessToken) || isset($_SESSION['fb_token'])) {
	$_SESSION['fb_token'] = isset($accessToken) ? (string) $accessToken : $_SESSION['fb_token'];

	// redirect the user back to the same page if it has "code" GET variable
	if (isset($_GET['code'])) {
		header('Location: ./');
	}

	// checking if user access token is not valid then ask user to login again
	$debugToken = $fb->get('/debug_token?input_token='. $_SESSION['fb_token'], APP_ID . '|' . APP_SECRET)
	->getGraphNode()
	->asArray();

	if (isset($debugToken['error']['code'])) {
		unset($_SESSION['fb_token']);
		$loginUrl = $helper->getLoginUrl(APP_URL);
		echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
		exit;
	}

	// setting default user access token for future requests
	$fb->setDefaultAccessToken($_SESSION['fb_token']);

	// printing public user info as an array
	$user = $fb->get('/me')
		->getGraphNode()
		->asArray();
	print_r($user);
} else {
	// making login with facebook url
	$loginUrl = $helper->getLoginUrl(APP_URL);
	echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
}