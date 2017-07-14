<?php
session_start();
require_once 'include/database.php';
require_once 'include/DbHandler.php';
require_once 'include/PassHash.php';
require_once 'include/functions.php';
require 'libs/Slim/Slim.php';
//test 
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->add(new \Slim\Middleware\ContentTypes());

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Content-Type: application/json');

$app->post('/login', function() use ($app){
	$response = array();
	$request = $app->request();

	if($app->request()){
		$params = $app->request()->getBody();
		
		$email= $params['email'];
		$password = $params['password'];
        $type = $params['type'];
	
		$db = new DbHandler();	
		if ($db->checkLogin($email, $password, $type)) {

			$logged_User = $db->getUserByEmail($email);
			if ($logged_User != NULL) {
				$access_token = $db->getAccessToken($logged_User['id']);
				if(!$access_token) {
					$response['error'] = true;
					$response['message'] = "An error occurred. Please try again";
					echoRespnse(200, $response);
				} 
				$response["error"] = false;
				$response['accessToken'] = $access_token;
				$response['username'] = $logged_User['first_name'];
				$response['message'] = "Successfully authenticated";
				echoRespnse(200, $response);
			} else {
				$response['error'] = true;
				$response['message'] = "An error occurred. Please try again";
				echoRespnse(200, $response);
			}
		} else {
			$response['error'] = true;
			$response['message'] = 'Login failed. Incorrect credentials';
			echoRespnse(200, $response);
		}
	}
});	 


/**
 * Create user 
 * url - /userlist
 * method - POST
 * params -user object*/

// $app->post('/user', 'authenticate', function() use ($app) {
// 		$users  = array();
// 		$response = array();
// 		$request = $app->request();
// 		$DbHandler = new DbHandler();

// 		$users = $request->getBody();		
// 		if($DbHandler->createUser($users)){
// 			$response["error"] = false;
// 			$response["message"] = "user created successfully";
// 			echoRespnse(200, $response);				
// 			}else{
// 			$response["error"] = true;
// 			$response["message"] = "user creation failed!";
// 			echoRespnse(400, $response);
// 		}
// });
	
/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
// function authenticate(\Slim\Route $route) {
//     // Getting request headers
//     $headers = apache_request_headers();
//     $response = array();
//     $app = \Slim\Slim::getInstance();
// 	//echo $headers['Authorization'];
//     // Verifying Authorization Header
//     if (isset($headers['Authorization'])) {
//         $db = new DbHandler();

//         // get the access token
//         $user_accessToken = $headers['Authorization'];
//         // validating Access Token
//         if (!$db->isValidAccessToken($user_accessToken)) {
//             // acess token not present in users table
//             $response["error"] = true;
//             $response["message"] = "Access Denied. Invalid Access Token";
//             echoRespnse(401, $response);
//             $app->stop();
//         }else{
//             global $user_id;
//             // get user primary key id
//             $user = $db->getUserId($user_accessToken);
// 			$user_id = $user['user_id'];
// 			//$user_id = $user['user_id'];
//         }        
//     } else {
//         // User Access Token is missing in header
//         $response["error"] = true;
//         $response["message"] = "invalid Request. Please login to the system";
//         echoRespnse(400, $response);
//         $app->stop();
//     }
// }


/**
 * Get user by checking the Accesstoken passed in header
 * url 		- /GetUserDetail
 * method 	- GET
 * params 	- '' */	
// $app->get('/GetUserDetail', 'authenticate', function() {
// 		$response = array();
// 		$DbHandler = new DbHandler();
// 		global $user_id;			
// 		$result = $DbHandler->GetUserDetail($user_id);
		
//         if ($result != NULL) {
//         	$response["error"] = false;
// 			$response['user'] = json_decode($result);
// 			echoRespnse(200	, $response);
// 		} else {
// 			$response["error"] = true;
// 			$response["message"] = "The requested resource doesn't exists";
// 			echoRespnse(404, $response);
// 		}
// 	});	 


/**
 * Create user 
 * url - /userlist
 * method - POST
 * params -user object*/

// $app->post('/user', 'authenticate', function() use ($app) {
// 		$users  = array();
// 		$response = array();
// 		$request = $app->request();
// 		$DbHandler = new DbHandler();

// 		$users = $request->getBody();		
// 		//verifyRequiredParams(array("user_email", "user_password"));
// 		if($DbHandler->createUser($users)){
// 			$response["error"] = false;
// 			$response["message"] = "user created successfully";
// 			echoRespnse(200, $response);				
// 			}else{
// 			$response["error"] = true;
// 			$response["message"] = "user creation failed!";
// 			echoRespnse(400, $response);
// 		}
// });

		
$app->run();
		
		
?>
