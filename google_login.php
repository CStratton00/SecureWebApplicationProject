<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once('vendor/autoload.php');

$client_id = '775891253213-gd1lgfa4q3ajnt2e1lm6hlircgiq2rsv.apps.googleusercontent.com';
$client_secret = 'AQD_r_9XeW3dec-AbeBAQx1Y';
$redirect_uri = 'http://localhost:8888/Jokes_Website/google_login.php';

$db_username = "root";
$db_password = "root";
$host_name = "localhost";
$db_name = 'test';


$guzzle = new GuzzleHttp\Client(['verify'=>false]);

$client = new Google_Client();
$client->setHttpClient($guzzle);
$client->setClientID($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("email");
$client->addScope("profile");

$service = new Google_Service_Oauth2($client);

if(isset($_GET['logout'])) {
  $client->revokeToken($_SESSION['access_token']);
  session_destroy();
  header('Location: index.php');
}

if(isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  exit;
}

if(isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  $client->setAccessToken($_SESSION['access_token']);
} else {
  $authUrl = $client->createAuthUrl();
}

echo '<div style="margin:20px">';
if(isset($authUrl)) {
  echo '<div align="center">';
  echo '<h3>Login</h3>';
  echo '<div>You will need a Google account to sign in.</div>';
  echo '<a class="login" href="' .$authUrl . '">Login here</a>';
  echo '</div>';
} else {
  $user = $service->userinfo->get();

  $mysqli = new mysqli($host_name, $db_username, $db_password, $db_name);

  if($mysqli->connect_error) {
    die('Error : (' . $mysqli->connect_errno .') ' . $mysqli->connect_error);
  }

  $result = $mysqli->query("SELECT COUNT(google_id) as usercount FROM google_users WHERE google_id = $user->id");
  $user_count = $result->fetch_object()->usercount;

  echo '<img src="' .$user->picture. '" style="float: right;margin-top: 33px" />';

  if($user_count) {
    echo 'Welcome back ' . $$user->name . '! [<a href="' . $redirect_uri . '?logout=1">Log Out</a>]';
  } else {
    echo 'Hi ' . $user->name . ', Thanks for Registering! [<a href="' . $redirect_uri . '?logout=1">Log Out</a>]';
    $statement = $mysqli->prepare("INSERT INTO google_users (google_id, google_name, google_email, google_link, google_picture_link) VALUES (?,?,?,?,?)");
    $statement->bind_param('issss'. $user->id, $user->email, $user->link, $user->picture);
    $statement->execute();
    echo $mysqli->error;
  }

  echo "<p>Data about this user. <ul><li>Username: " . $user->name . "</li> <li>user id: " . $user->id . "</li> <li>email: " . $user->email . "</li></ul></p>";

  $_SESSION['username']=$user->name;
  $_SESSION['userid']=$user->id;
  $_SESSION['useremail']=$user->email;

}
echo '</div>';
 ?>