<?php 
//http://www.withings.com/us/api-developer/documentation
//http://oauth.withings.com/de/api/oauthguide
//http://oauth.withings.com/api/doc

/*
 * Include the Oauth Library
*/
require_once("/var/www/WithingsApi/example_code/OAuth.php");

session_start();

/*
 * Config Section
 */
$domain = "oauth.withings.com";
$base = "/account/";
$base_url = "https://$domain$base";

$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
$rsa_method = new OAuthSignatureMethod_RSA_SHA1(); //Not supported Yet


/*
 * Defining Consumer Object
 */
//callback url bei withings anpassen!!!
 $consumer = new OAuthConsumer("FILL", "FILL", "http://withingsapi.com");

/* 
 * Choosing preferred Signing Method
 */

$sig_method = $hmac_method;

// unset($_SESSION['req_token']);

/*
 * Forge request to obtain non-authorized request token
 */
if (! $_GET ['userid']) { // skip this code if the request was already send
	$req_req = OAuthRequest::from_consumer_and_token ( $consumer, NULL, "GET", $base_url . "request_token?oauth_callback=" . urlencode ( "http://withingsapi.com" ) );
	$req_req->sign_request ( $sig_method, $consumer, NULL );
	
	/*
	 * Send the request
	 */
	$response = file_get_contents ( $req_req );
	echo "--response from TOKEN REQUEST--<br>";
	var_dump ( $response );
	
	parse_str ( $response, $req_token_tab );
	
	/*
	 * Create the Request Token Object
	 */
	$req_token = new OAuthToken ( $req_token_tab ["oauth_token"], $req_token_tab ["oauth_token_secret"] );
	$_SESSION ['req_token'] = $req_token;
	echo "--REQUEST TOKEN--<br>";
	var_dump ( $req_token );
	
	/*
	 * Forge the authorization request, with the newly obtained request token
	 */
	$auth_req = OAuthRequest::from_consumer_and_token ( $consumer, $req_token, "GET", $base_url . "authorize" );
	$auth_req->sign_request ( $sig_method, $consumer, $req_token );
	
	/*
	 * Here, redirect the user to the above request URL
	 */
	
	?>
<div class="box">
	<div class="request"> 
	    	 	<?php  if (isset($auth_req)) { ?>
	       		<a class='login' href='<?php echo $auth_req; }?>'>Get
			Permission from User</a>

	</div>
</div>

<?php
		echo "<br>";
}



/*
 * Forge the request to obtain Access Token, thanks to authorized request token
 * Don't forget to add the USER ID 
 */

 if ($_GET ['userid']) {
	$userId = $_GET ['userid'];
	
	echo "USER ID = " . $userId;
	echo "<br> <br>";
	
	if ($_GET ['oauth_token']) {
		$oauthToken = $_GET ['oauth_token'];
	}
	echo "OAUTH TOKEN = " . $oauthToken;
	echo "<br> <br>";
	
	if ($_GET ['oauth_verifier']) {
		$oauthVerifier = $_GET ['oauth_verifier'];
	}
	
	echo "OAUTH VERIFIER = " . $oauthVerifier;
	echo "<br> <br>";
	
	echo "SESSION[req_token] = " . $_SESSION ['req_token'];
	
	$acc_req = OAuthRequest::from_consumer_and_token ( $consumer, $_SESSION ['req_token'], "GET", $base_url . "access_token?userid=" . $userId );
	$acc_req->sign_request ( $sig_method, $consumer, $_SESSION ['req_token'] );
	
	echo "<br><br>--ACCESS TOKEN REQUEST--<br>";
	echo "<br> ";
	echo $acc_req;
	// var_dump($acc_req);
	echo "<br><br>";
	
	/*
	 * Send the request
	 */
	$response = file_get_contents ( $acc_req );
	
	echo "--Response from ACCESS TOKEN REQUEST--<br>";
	
	var_dump ( $response );
	
	parse_str ( $response, $access_token_tab );
	
	/*
	 * Forge request to get protected data
	 */
	
	$acc_tok = new OAuthToken ( $access_token_tab ["oauth_token"], $access_token_tab ["oauth_token_secret"] );
	
	// hier war falsch: http://wbsapi.withings.com/cgi-bin/
	// devtype is deprecated : use meastype instead
	$req = OAuthRequest::from_consumer_and_token ( $consumer, $acc_tok, "GET", "http://wbsapi.withings.net/measure?action=getmeas&meastype=1&userid=" . $userId );
	$req->sign_request ( $sig_method, $consumer, $acc_tok );
	
	$response_data_request = file_get_contents ( $req );
	
	$result_data_request = json_decode ( $response_data_request, true );
	
	echo "-- Response from DATA REQUEST <br><br>";
	
	echo "DATE: " . date ( 'm/d/Y', $result_data_request ['body'] ['measuregrps'] ['0'] ['date'] ) . "<br>";
	echo "Gewicht: " . $result_data_request ['body'] ['measuregrps'] ['1'] ['measures'] ['0'] ['value'] / 1000 . "<br>";
	var_dump ( $result_data_request ['body'] ['measuregrps'] );
}
 


