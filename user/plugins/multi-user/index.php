<?php
session_start();
include("../../../includes/load-yourls.php");
include("muhtmlfunctions.php");

if(YOURLS_PRIVATE === false) {
	die(); // NO DIRECT CALLS IF PUBLIC!
}

$act = $_GET['act'];
if($act == "logout") {
	$_SESSION['user'] = "";
	unset($_SESSION);
	unset($_SESSION["user"]);
	$error_msg = "Signed off.";
}

if(!isLogged()) {
	yourls_html_head( 'login' );
	mu_html_menu();
	// Login form
	switch($act) {
		case "login":
			$username = yourls_escape($_POST['username']);
			$password = $_POST['password'];
			if(!empty($username) && !empty($password)) {
				if(isValidUser($username, $password)) {
					$token = getUserTokenByEmail($username);
					$id = getUserIdByToken($token);
					$_SESSION['user'] = array("id" => $id, "user" => $username, "token" => $token);
					yourls_redirect("index.php");
				} else {
					$error_msg = "Problems to login.";
					require_once 'form.php';
				}
			}
			break;
		case "joinform":
			require_once 'formjoin.php';
			break;
		case "join":
			$username = yourls_escape($_POST['username']);
			$password = $_POST['password'];
			if(captchaEnabled()) {
				require_once('recaptchalib.php');
				$privatekey = YOURLS_MULTIUSER_CAPTCHA_PRIVATE_KEY;
				$resp = recaptcha_check_answer ($privatekey,$_SERVER["REMOTE_ADDR"],$_POST["recaptcha_challenge_field"],$_POST["recaptcha_response_field"]);
				if (!$resp->is_valid) {
					$error_msg = "Captch is incorrect.";
					require_once 'formjoin.php';
					break;
				}
			}
			if(!empty($username) && !empty($password)) {
				if(validEmail($username) === false) {
					$error_msg = "E-mail not recognized!";
					require_once 'formjoin.php';
				} else {
					$table = YOURLS_DB_TABLE_USERS;
					$results = $ydb->get_results("select user_email from `$table` where `user_email` = '$username'");
					if($results) {
						$error_msg = "Please choose other username.";
						require_once 'formjoin.php';
					} else {
						$token = createRandonToken();
						$password = md5($password);
						$ydb->query("insert into `$table` (user_email, user_password, user_token) values ('$username', '$password', '$token')");
						$results = $ydb->get_results("select user_token from `$table` where `user_email` = '$username'");
						if (!empty($results)) {
							$token = $results[0]->user_token;
							$error_msg = "User $username added with token $token.";
							require_once 'form.php';
						} else {
							require_once 'formjoin.php';
						}
					}
				}
			} else {
				$error_msg = "Please fill all fields.";
				require_once 'formjoin.php';
			}

			break;

		default:
			require_once 'form.php';

	}

	yourls_html_footer();
	die();
} else {
	include("admin.php");
}
