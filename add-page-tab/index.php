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

	// getting all pages owned by user
	$pages = $fb->get('/me/accounts')
		->getGraphEdge()
		->asArray();

?>
	<form action="" method="POST">
		<select name="page" single>
	<?php	foreach ($pages as $key) { ?>
		<option value="<?php echo $key['id']; ?>"><?php echo $key['name']; ?></option>
	<?php	}	?>
	</select>
	<input type="submit" name="submit">
	</form>
	<?php
	if (isset($_POST['submit'])) {
		// getting page access token of the selected page
		$page = $fb->get('/' . $_POST['page'] . '?fields=access_token,name,id')
			->getGraphNode()
			->asArray();

		// adding page tab to selected page using page access token
		$addTab = $fb->post('/' . $page['id'] . '/tabs', ['app_id' => APP_ID], $page['access_token'])
			->getGraphNode()
			->asArray();
		print_r($addTab);
	}
} else {
	// making login with facebook url
	$loginUrl = $helper->getLoginUrl(APP_URL);
	echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
}
