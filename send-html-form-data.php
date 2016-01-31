<?php
session_start();
require_once __DIR__ . '/src/Facebook/autoload.php';

$fb = new Facebook\Facebook([
  'app_id' => 'APP_ID',
  'app_secret' => 'APP_SECRET',
  'default_graph_version' => 'v2.5',
  ]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['publish_actions']; // optional
	
try {
	if (isset($_SESSION['localhost_app_token'])) {
		$accessToken = $_SESSION['localhost_app_token'];
	} else {
  		$accessToken = $helper->getAccessToken();
	}
} catch(Facebook\Exceptions\FacebookResponseException $e) {
 	// When Graph returns an error
 	echo 'Graph returned an error: ' . $e->getMessage();

  	exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
 	// When validation fails or other local issues
	echo 'Facebook SDK returned an error: ' . $e->getMessage();
  	exit;
 }

if (isset($accessToken)) {
	if (isset($_SESSION['localhost_app_token'])) {
		$fb->setDefaultAccessToken($_SESSION['localhost_app_token']);
	} else {
		// getting short-lived access token
		$_SESSION['localhost_app_token'] = (string) $accessToken;

	  	// OAuth 2.0 client handler
		$oAuth2Client = $fb->getOAuth2Client();

		// Exchanges a short-lived access token for a long-lived one
		$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['localhost_app_token']);

		$_SESSION['localhost_app_token'] = (string) $longLivedAccessToken;

		// setting default access token to be used in script
		$fb->setDefaultAccessToken($_SESSION['localhost_app_token']);
	}

	// redirect the user back to the same page if it has "code" GET variable
	if (isset($_GET['code'])) {
		header('Location: ./');
	}

	// getting basic info about user
	try {
		$profile_request = $fb->get('/me?fields=name,first_name,last_name');
		$profile = $profile_request->getGraphNode()->asArray();
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		// When Graph returns an error
		echo 'Graph returned an error: ' . $e->getMessage();
		session_destroy();
		// redirecting user back to app login page
		header("Location: ./");
		exit;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		// When validation fails or other local issues
		echo 'Facebook SDK returned an error: ' . $e->getMessage();
		exit;
	}

	if (isset($_POST['message'])) {
			$post = $fb->post('/me/feed', array('message' => $_POST['message']));
			$post = $post->getGraphNode()->asArray();

			echo $post['id'];
	}	
?>
	<form action="" method="POST">
		Message: <input name="message" type="text">
		<br>
		<input type="submit" name="submit" value="Submit">
	</form>
<?php

  	// Now you can redirect to another page and use the access token from $_SESSION['localhost_app_token']
} else {
	// replace your website URL same as added in the developers.facebook.com/apps e.g. if you used http instead of https and you used non-www version or www version of your website then you must add the same here
	$loginUrl = $helper->getLoginUrl('http://localhost.com/fbapp/', $permissions);
	echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
}
