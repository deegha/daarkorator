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

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        $user_accessToken = $headers['Authorization'];
        $access = $db->isValidAccessToken($user_accessToken);
        if (!$access) {
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Access Token";
            echoRespnse(401, $response);
            $app->stop();
        }else{
            if($access['expiration'] < date('Y-m-d H:i:s')){
                $response["error"] = true;
	            $response["message"] = "Access token has expired"; 
	            echoRespnse(200, $response);
	            $app->stop();
            }

            global $user_id;
			$user_id = $access['user_id'];
        }        
    } else {
        $response["error"] = true;
        $response["message"] = "invalid Request. Please login to the system";
        echoRespnse(400, $response);
        $app->stop();
    }
}

$app->post('/login', function() use ($app){
	$response = array();
	$request = $app->request();

	if($app->request()){
		$params = $app->request()->getBody();

		$email= $params['email'];
		$password = $params['password'];
	
		$db = new DbHandler();	
		if ($db->checkLogin($email, $password)) {

			$logged_User = $db->getUserByEmail($email);
			if ($logged_User != NULL) {
				$access_token = $db->getAccessToken($logged_User['id']);
				if(!$access_token) {
					$response['error'] = true;
					$response['message'] = "An error occurred. Please try again";
					echoRespnse(200, $response);
				} 
				$response["error"] = false;
				$response['accessToken'] 	= $access_token;
				$response['username'] 		= $logged_User['first_name'];
				$response['user_type']		= $logged_User['user_type'];
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
 * Get user allowed features
 * url 		- /userFeatures
 * method 	- GET
 * params 	- $user_id */	
$app->get('/userFeatures', 'authenticate', function() {
		$response = array();
		$DbHandler = new DbHandler();
		global $user_id;			
		$result = $DbHandler->getUserFeatures($user_id);

        if ($result != NULL) {
        
        	$response["error"] = false;
			$response['features'] = $result;
			echoRespnse(200	, $response);
		} else {
			$response["error"] = true;
			$response["message"] = "The requested resource doesn't exists";
			echoRespnse(404, $response);
		}
	});	


/**
 * Get user by user id
 * url - /userlist
 * method - GET
 * params -user id*/		
$app->get('/user/:id', 'authenticate', function($user_id) {
		$response = array();
		$DbHandler = new DbHandler();	
		$result = $DbHandler->GetUserDetail($user_id);
        if ($result != NULL) {
        	$response["error"] = false;
			$response['user'] = json_decode($result);
			echoRespnse(200	, $response);
		} else {
			$response["error"] = true;
			$response["message"] = "The requested resource doesn't exists";
			echoRespnse(404, $response);
		}
	});	 


$app->post('/user', function() use ($app){
	$response 	= array();
	if($app->request()){
		$params 	=  $app->request()->getBody();

		$DbHandler 	= new DbHandler();
		$result = $DbHandler->createUser($params);

		if($result) {
			$response["error"] = false;
			$response['user_id'] = $result;
			echoRespnse(200	, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "An error occurred. Please try again";
			echoRespnse(500, $response);
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
