<?php

define('FACEBOOK_SDK_V4_SRC_DIR', __DIR__.'/facebook-php-sdk-v4-5.0-dev/src/Facebook/');
require FACEBOOK_SDK_V4_SRC_DIR.'autoload.php';

use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;

$fb = new Facebook\Facebook([
  'app_id' => '892508447483547',
  'app_secret' => '66b69f812061bca7ac948f1b418d8543',
  'default_graph_version' => 'v2.4',
  ]);

session_start();

$helper = $fb->getRedirectLoginHelper();

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (! isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Error: " . $helper->getError() . "\n";
    echo "Error Code: " . $helper->getErrorCode() . "\n";
    echo "Error Reason: " . $helper->getErrorReason() . "\n";
    echo "Error Description: " . $helper->getErrorDescription() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}

// Logged in
// echo '<h3>Access Token</h3>';
// var_dump($accessToken->getValue());

// The OAuth 2.0 client handler helps us manage access tokens
$oAuth2Client = $fb->getOAuth2Client();

// Get the access token metadata from /debug_token
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
// echo '<h3>Metadata</h3>';
// var_dump($tokenMetadata);

// Validation (these will throw FacebookSDKException's when they fail)
// $tokenMetadata->validateAppId($config['892508447483547']);
// If you know the user ID this access token belongs to, you can validate it here
//$tokenMetadata->validateUserId('123');
$tokenMetadata->validateExpiration();

if (! $accessToken->isLongLived()) {
  // Exchanges a short-lived access token for a long-lived one
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
    exit;
  }

  echo '<h3>Long-lived</h3>';
  var_dump($accessToken->getValue());
}

$_SESSION['fb_access_token'] = (string) $accessToken;


// User is logged in with a long-lived access token.
// You can redirect them to a members-only page.
//header('Location: https://example.com/members.php');

/*** GET THE USER's PROFILE PICTURE ***/
$res = $fb->get( '/me/picture?width=540&height=540&redirect=false', (string) $accessToken );
 
$picture = $res->getGraphObject();

// print_r($res);
$imageUrl = $picture->getProperty('url');

// // Read image path, convert to base64 encoding
// $imageData = base64_encode(file_get_contents($imageUrl));

// // Format the image SRC:  data:{mime};base64,{data};
// $src = 'data: '.mime_content_type($imageUrl).';base64,'.$imageData;


/*** OVERLAY THE PICTURE HERE ***/

// Defining the background image. Optionally, a .png image   // could be used using imagecreatefrompng   

$profPicData = $res->getDecodedBody()['data'];

 
$overlay = imagecreatefrompng( __DIR__.'/resources/overlay3.png'); 
$profpicRaw = imagecreatefromjpeg($imageUrl);  
$profpic = imagecreatefrompng( __DIR__.'/resources/blank.png'); 
$success = imagecopyresized($profpic, $profpicRaw, 0, 0, 0, 0,  540, 540, $profPicData['width'], $profPicData['height']);

$insert_x = imagesx($overlay);   $insert_y = imagesy($overlay);   
// Combine the images into a single output image   
imagecopymerge($profpic,$overlay,0,0,0,0,$insert_x,$insert_y,50);   

// Output the results as a jpg image,   
// imagejpeg($profpic);
//you can also generate output as png, gif as per your requirement    

$uuid = uniqid();
$processedImgPath = __DIR__.'/resources/processed_'.$uuid.'.jpg';

header ("Content-type: image/jpeg");   
imagejpeg($profpic);

// imagejpeg($profpic, $processedImgPath);
imagedestroy($profpic);



?>