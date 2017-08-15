<?php
session_start();
require_once 'include/database.php';
require_once 'include/DbHandler.php';
require_once 'include/PassHash.php';
require_once 'include/functions.php';
require_once 'include/SimpleImage.php';
require 'libs/Slim/Slim.php';

//test 
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app->add(new \Slim\Middleware\ContentTypes());

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Content-Type: application/json');

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
    // header("HTTP/1.1 200 OK");
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

}

$app->map('/:x+', function($x) {
    http_response_code(200);
})->via('OPTIONS');

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
            if($access['expiration'] <= date('Y-m-d H:i:s')){
                $response["error"] = true;
	            $response["message"] = "Access token has expired"; 
	            echoRespnse(400, $response);
	            $app->stop();
            }
            global $user_id;
			global $features;
			global $loged_user_type;
			$user_id = $access['user_id'];
			$features = $access['features'];
			$loged_user_type = $access['type'];

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
	$db = new DbHandler();	
	try{
		if($app->request() && $app->request()->getBody()){
			$params = $app->request()->getBody();
			$email= $params['email'];
			$password = $params['password'];
			
			if ($db->checkLogin($email, $password)) {

				$logged_User = $db->getUserByEmail($email);
				if ($logged_User != NULL) {
					$access_token = $db->getAccessToken($logged_User['id']);
					if(!$access_token) {
						$response['error'] = true;
						$response['message'] = "An error occurred. Please try again";
						echoRespnse(400, $response);
					} 
					$response["error"] = false;
					$response['accessToken'] 	= $access_token;
					$response['username'] 		= $logged_User['first_name'];
					$response['message'] = "Successfully authenticated";
					echoRespnse(200, $response);
				} else {
					$response['error'] = true;
					$response['message'] = "An error occurred. Please try again";
					echoRespnse(400, $response);
				}
			} else {
				$response['error'] = true;
				$response['message'] = 'Login failed. Incorrect credentials';
				echoRespnse(400, $response);
			}
		}else {
			$response["error"] = true;
			$response["message"] = "An error occurred. No request body";
			echoRespnse(500, $response);
		}
	}catch(Exception $e) {
        $db->callErrorLog($e);
        return false;
    }
});	


/**
 * Get user allowed features
 * url 		- /userFeatures
 * method 	- GET
 * params 	- $user_id */	
$app->get('/userFeatures', 'authenticate', function() use ($app) {
		global $features;
		global $loged_user_type;
		$response = array();
		$DbHandler = new DbHandler();	

        if ($features != NULL) {
        	$response["error"] = false;	
			$response['features'] = json_decode($features);
			$response['loged_user_type'] = json_decode($loged_user_type);
			echoRespnse(200	, $response);
		} else {
			$response["error"] = true;
			$response["message"] = "The requested resource doesn't exists";
			echoRespnse(404, $response);
		}
	});	

/**
 * Create user
 * url - /user
 * method - POST
 * params -user object
 */	
$app->post('/user', 'authenticate', function() use ($app){
	global $features;
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->create) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();
		$DbHandler 	= new DbHandler();
		if(!$DbHandler->validate($params)) {
			$response["error"] = false;
			$response["message"] = "Validation failed";
			echoRespnse(400	, $response);
		}

        if(array_key_exists("password", $params) ){
			$response["error"] = true;
			$response["message"] = "Unauthorized request";
			echoRespnse(401, $response);
        }
		if($DbHandler->getUserByEmail($params['email'])) {
			$response["error"] = true;
			$response["message"] = "Email already exist";
			echoRespnse(400, $response);
		}
		$result = $DbHandler->createUser($params);

		if($result) {
			$resetKey = $DbHandler->generateResetKey($result);
			$url = 'http://daakor.dhammika.me/reset-password?k='.$resetKey;

			$message['text'] = 'Click the following link to activate your account '.$url;
			$message['to']	 = $params['email'];
			$message['subject']	= 'Activate your account';

			if(!send_email ('resetpassword', $message)) {
				$response["error"] = true;
				$response["message"] = "An error occurred. Please try again";
				echoRespnse(400, $response);
			}

			$response["error"] = false;
			$response["message"] = "user created successfully";
			echoRespnse(200	, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "An error occurred. Please try again";
			echoRespnse(400, $response);
		}
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(400, $response);
	}
});	

/**
 * List all users
 * url - /user
 * method - GET
 */		
$app->get('/user', 'authenticate', function() use ($app) {
	global $features;
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->view) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response = array();
	$DbHandler = new DbHandler();	
	$result = $DbHandler->getUser();
	if ($result != NULL) {
		$response["error"] = false;
		$response['users'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});	

/**
 * Delete user
 * url - /user/:user_id
 * method - DELETE
 * params -user object
 */	
$app->delete('/user/:user_id', 'authenticate', function($user_id) use ($app){
	global $features;
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->remove) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}
	$response 	= array();
	if($app->request()){

		$DbHandler 	= new DbHandler();
		$result = $DbHandler->deleteUser($user_id);

		if(!$result) {
			$response["error"] = true;
			$response["message"] = "An error occurred. Please try again";
			echoRespnse(500, $response);	
		}

		$response["error"] = false;
		$response["message"] = "User Successfully Deleted";
		echoRespnse(200	, $response);
	}
});	

/**
 * Update price 
 * url - /package/:id
 * method - PUT
 * params - */
$app->put('/package/:id', 'authenticate', function($pkg_id) use ($app) {
        //print_r($app->request()->getBody());
		global $features;
		$capabilities = json_decode($features);

		if(!$capabilities->manageProjects->priceSetup) {
			$response["error"] = true;
	        $response["message"] = "Unauthorized access";
	        echoRespnse(401, $response);
		}

		if($app->request() && $app->request()->getBody()){
			$request = $app->request();
			$DbHandler = new DbHandler();
			$response = array();
			$pkg =  $request->getBody();

	        $results = $DbHandler->updatePackage($pkg, $pkg_id);
	        if($results) {
	            $response["error"] = false;
	            $response['message'] = "Package updated successfully";
	            echoRespnse(200	, $response);
	        }else{
	            $response["error"] = true;
	            $response["message"] = "An error occurred. Please try again";
	            echoRespnse(500, $response);
	        }
    	}else {
			$response["error"] = true;
			$response["message"] = "An error occurred. No request body";
			echoRespnse(500, $response);
		}
});

/**
 * Update user
 * url - /user
 * method - PUT
 * params -user object
 */	
$app->put('/user/:id', 'authenticate', function($id) use ($app){
	global $features;
	if(!isset($id)) {
		global $user_id;
		$id = $user_id;
	}
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->update) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();
		$DbHandler 	= new DbHandler();

		if(isset($params['email'])) {
			$response["error"] = true;
			$response['message'] = "Unauthorized request, email cannot be changed";
			echoRespnse(401	, $response);
		}

		if(isset($params['user_type'])) {
			$response["error"] = true;
			$response['message'] = "Unauthorized request, user type cannot be changed";
			echoRespnse(401	, $response);
		}

		if(!isset($params['update_password']) 
				&&  isset($params['password']) 
				|| isset($params['update_password']) && $params['update_password'] == false && isset($params['password']) ) {
			$response["error"] = true;
			$response['message'] = "Unauthorized request, password cannot be changed on this request";
			echoRespnse(401	, $response);
		}

		if(isset($params['update_password']) && $params['update_password'] == true &&  !isset($params['password'])){
			$response["error"] = true;
			$response['message'] = "Update password is set, but no password provided";
			echoRespnse(400	, $response);
		}
		if(isset($params['update_password']) &&  isset($params['password']) && strlen(trim($params['password'])) <= 0){
			$response["error"] = true;
			$response['message'] = "password cannot be empty";
			echoRespnse(400	, $response);
		}

		$result = $DbHandler->updateUser($params, $id);

		if($result) {
			$response["error"] = false;
			$response['message'] = "User updated Successfully";
			echoRespnse(200	, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "An error occurred. Please try again";
			echoRespnse(500, $response);
		}
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}
});

/**
 * forgot password 
 * url - /forgotPassword
 * method - POST
 * params - */
$app->post('/forgotPassword', function() use ($app) {

		if($app->request() && $app->request()->getBody()){
			$params =  $app->request()->getBody();
			$DbHandler 	= new DbHandler();
			$message['text'] = 'hello world';	

			$user_id = $DbHandler->checkEmailExist($params['email']);
			if(!$user_id){
				$response["error"] = true;
				$response["message"] = "Email does not exist";
				echoRespnse(404, $response);
			}
			$resetKey = $DbHandler->generateResetKey($user_id);
			if(!$resetKey ){
				$response["error"] = true;
				$response["message"] = "An error occurred while generating reset key Please try again";
				echoRespnse(500, $response);
			}

			$url = 'http://daakor.dhammika.me/#/reset-password?k='.$resetKey;

			$message['text'] = $url;
			$message['to']	 = $params['email'];
			$message['subject']	= 'Reset your password';

			if(!send_email ('resetpassword', $message)) {
				$response["error"] = true;
				$response["message"] = "An error occurred. Please try again";
				echoRespnse(500, $response);	
			}

			$response["error"] = false;
			$response["message"] = "Email sent Successfully";
			echoRespnse(200	, $response);
		}else {
			$response["error"] = true;
			$response["message"] = "An error occurred. No request body";
			echoRespnse(500, $response);
		}	
});

/**
 * User Signup
 * url - /userSignUp
 * method - POST
 * params -user object
 */	
$app->post('/userSignUp',  function() use ($app){

	$response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();
		$DbHandler 	= new DbHandler();
		if($DbHandler->getUserByEmail($params['email'])) {
			$response["error"] = true;
			$response["message"] = "Email already exist";
			echoRespnse(400, $response);
		}
		$user_type = 2;
		
		if(!$DbHandler->validate($params, true)) {
			$response["error"] = false;
			$response["message"] = "Validation failed";
			echoRespnse(400	, $response);
		}

		$result = $DbHandler->createUser($params, $user_type);

		if($result) {
			$activationKey = $DbHandler->generateResetKey($result);
			if(!$activationKey ){
				$response["error"] = true;
				$response["message"] = "An error occurred while generating reset key Please try again";
				echoRespnse(500, $response);
			}
			$url = 'http://daakor.dhammika.me/activate-user;key='.$activationKey;
			$message['text'] = $url;
			$message['to']	 = $params['email'];
			$message['subject']	= 'Activate your account';

			if(!send_email ('new_user_created', $message)) {
				$response["error"] = true;
				$response["message"] = "User created, Coundn't send an activation email";
				echoRespnse(500, $response);	
			}
			$response["error"] = false;
			$response["message"] = "User created successfully";
			echoRespnse(200	, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "An error occurred. Please try again";
			echoRespnse(500, $response);
		}
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}	
});


/**
 * List all rooms
 * url - /rooms
 * method - GET
 * params -

 */
$app->get('/rooms', function() use ($app) {

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getRoomList();
	if ($result != NULL) {
		$response["error"] = false;
		$response['rooms'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

/**
 * List all rooms images
 * url - /room-images
 * method - GET
 * params -

 */
$app->get('/room-images', function() use ($app) {

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getRoomImages();
	if ($result != NULL) {
		$response["error"] = false;
		$response['roomImages'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});


/**
 * List all color choices
 * url - /color-choices
 * method - GET
 * params -

 */
$app->get('/color-choices', function() use ($app) {

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getColorChoices();
	if ($result != NULL) {
		$response["error"] = false;
		$response['roomColors'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});


/**
 * List all roles
 * url - /user/types
 * method - GET
 * 

 */
$app->get('/user/types', 'authenticate', function() use ($app) {

    global $features;
    $capabilities = json_decode($features);
    if(!$capabilities->manageUsers->enabled) {
        $response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
    }
	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getUserRoles();
	if ($result != NULL) {
		$response["error"] = false;
		$response['user_types'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

/**
 * List single user
 * url - /user/:id
 * method - GET
 **/		
$app->get('/user/:id', 'authenticate', function($id) use ($app) {
	global $features;
	
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->viewSingleuser) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response = array();
	$DbHandler = new DbHandler();	
	$result = $DbHandler->getUser($id);
	if ($result != NULL) {
		$response["error"] = false;
		$response['users'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});


/**
 * User resetPasword
 * url - /resetpassword
 * method - POST
 * params -user object
 */
$app->post('/resetpassword/:resetKey',  function($resetKey) use ($app){

	$response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();
		$DbHandler 	= new DbHandler();
		if($params['password'] != $params['confirmPassword']){
		    $response["error"] = true;
            $response["message"] = "Password mis-matched or invalid request!";
            echoRespnse(200, $response);
		}else{
		    $result = $DbHandler->getPasswordChangeUser($resetKey);
		    if($result){
		    	if($result['expiry'] <= date('Y-m-d H:i:s')) {
		    		$response["error"] = true;
	                $response["message"] = "Password reset request has been expired!";
	                echoRespnse(400, $response);
		    	}
		    	$update_params = array('password' => $params['password']);
                if($DbHandler->updateUser($update_params, $result['id'])){
                    $response["error"] = false;
                    $response['message'] = "Password updated Successfully";
                    echoRespnse(200	, $response);
                }
		    }else{
		        $response["error"] = true;
                $response["message"] = "Token not found";
                echoRespnse(400, $response);
		    }
		}
	}
});


/**
 * Create Project
 * url - /project
 * method - post
 **/		
$app->post('/project', 'authenticate', function() use ($app) {
	global $features;
	global $user_id;
	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->create) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}
	if($app->request() && $app->request()->getBody()){

		$response 	= array();
		$DbHandler 	= new DbHandler();	
		$params 	= $app->request()->getBody();
		$result 	= false;

		$result = $DbHandler->createProject($params, $user_id);	

		if (!$result) {
			$response["error"] = true;
			$response["message"] = "An error occurred while create the project";
			echoRespnse(500, $response);
		} else {
	
			$response["error"] = false;
			$response["message"] = "Project successfully created.";
			$response["project_id"] = $result;
			echoRespnse(200	, $response);
		}
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}	
});

/**
 * send email
 * url - /sendEmail
 * method - POST
 * params - */
$app->post('/sendEmail', function() use ($app) {

            $url = 'http://daakor.dhammika.me/reset-password?k=';

            $message['text'] = $url;
            $message['to']	 = "dhammika97@gmail.com";
            $message['subject']	= 'Testing emails';

            if(!send_email ('signup-complete', $message)) {
                $response["error"] = true;
                $response["message"] = "An error occurred. Please try again";
                echoRespnse(500, $response);
            }else{
            $response["error"] = false;
            $response["message"] = "Email sent Successfully";
            echoRespnse(200	, $response);
            }
});

/**
 * Activte User
 * url - /activateUser/
 * method - GET
 * params - */
$app->put('/activateUser/:activationKey', function($changeRequestCode) use ($app) {
	$DbHandler 	= new DbHandler();

	$user_id = $DbHandler->getPasswordChangeUser($changeRequestCode);
	if(!$user_id) {
		$response["error"] = true;
        $response["message"] = "The requested activation key does not exist";
        echoRespnse(404, $response);
	}	

	$params = array("status" => 1);

	if(!$DbHandler->updateUser($params, $user_id['id'])){
		$response["error"] = true;
        $response["message"] = "something went wrong while updating user";
        echoRespnse(500, $response);
	}

	$response["error"] = false;
    $response["message"] = "Account was successfully activated";
	echoRespnse(200	, $response);

});

/**
 * My profile
 * url - /user
 * method - GET
 **/		
$app->get('/myprofile', 'authenticate', function() use ($app) {
	global $features;
	global $user_id;
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->view) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response = array();
	$DbHandler = new DbHandler();	
	$result = $DbHandler->getUser($user_id);
	if ($result != NULL) {
		$response["error"] = false;
		$response['users'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

/**
 * Get package
 * url - /user
 * method - GET
 **/	
$app->get('/package/:id', 'authenticate', function($pkg_id) use ($app) {
		global $features;
		$capabilities = json_decode($features);
		$DbHandler = new DbHandler();	
		if(!$capabilities->manageProjects->priceSetup) {
			$response["error"] = true;
	        $response["message"] = "Unauthorized access";
	        echoRespnse(401, $response);
		}
			
		$package = $DbHandler->getPackage($pkg_id);
        if(!$package) {
        	$response["error"] = true;
	        $response["message"] = "No price set";
	        echoRespnse(404, $response);
        }
        $response["error"] 	 = false;
        $response["message"] = "Request successful";
        $response["price"]	 = $package[0];
        echoRespnse(200, $response);
    	
});

/*
* Payment
* Url : /payment
* method - POST
*/

$app->post('/payment','authenticate', function() use ($app) {
	global $user_id;
	
	if($app->request() && $app->request()->getBody()){

		$response 	= array();
		$DbHandler 	= new DbHandler();	
		$params 	= $app->request()->getBody();

		if( $params["project_id"] == "" || $params["amount"] == "" ) {
			$response["error"] = true;
	        $response["message"] = "Validation faild, all feilds are required";
	        echoRespnse(400, $response);
		}

		$transactionId = gnerateTransactionId($user_id);	

		if(!$transactionId) {
			$response["error"] = true;
			$response["message"] = "Error in creating the transactioin Id";
			echoRespnse(500, $response);
		}

		if(!$DbHandler->payment($params, $user_id, $transactionId)) {
			$response["error"] = true;
	        $response["message"] = "Payment unsuccessful";
	        echoRespnse(400, $response);
		}	

		$response["error"] = false;
	    $response["message"] = "Payment successful";
		echoRespnse(200	, $response);

	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}

});


/**
 * Update My profile
 * url - /user
 * method -PUT
 **/		
$app->put('/myprofile', 'authenticate', function() use ($app) {
	global $features;
	global $user_id;
	$capabilities = json_decode($features);
	$id = $user_id;
	if($app->request() && $app->request()->getBody()){

		if(!$capabilities->manageUsers->update) {
			$response["error"] = true;
	        $response["message"] = "Unauthorized access";
	        echoRespnse(401, $response);
		}
		$params 	=  $app->request()->getBody();
		$DbHandler 	= new DbHandler();

		if(isset($params['email'])) {
			$response["error"] = true;
			$response['message'] = "Unauthorized request, email cannot be changed";
			echoRespnse(401	, $response);
		}

		if(isset($params['user_type'])) {
			$response["error"] = true;
			$response['message'] = "Unauthorized request, user type cannot be changed";
			echoRespnse(401	, $response);
		}

		if(!isset($params['update_password']) 
				&&  isset($params['password']) 
				|| isset($params['update_password']) && $params['update_password'] == false && isset($params['password']) ) {
			$response["error"] = true;
			$response['message'] = "Unauthorized request, password cannot be changed on this request";
			echoRespnse(401	, $response);
		}

		if(isset($params['update_password']) && $params['update_password'] == true &&  !isset($params['password'])){
			$response["error"] = true;
			$response['message'] = "Update password is set, but no password provided";
			echoRespnse(400	, $response);
		}
		if(isset($params['update_password']) &&  isset($params['password']) && strlen(trim($params['password'])) <= 0){
			$response["error"] = true;
			$response['message'] = "password cannot be empty";
			echoRespnse(400	, $response);
		}

		$result = $DbHandler->updateUser($params, $id);

		if($result) {
			$response["error"] = false;
			$response['message'] = "User updated Successfully";
			echoRespnse(200	, $response);
		}else{
			$response["error"] = true;
			$response["message"] = "An error occurred. Please try again";
			echoRespnse(500, $response);
		}
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}
});


/**
 * File upload
 * url - /fileUplaod
 * method - POST
 **/		

$app->put('/fileUplaod', 'authenticate', function() use ($app) {
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();
		$path = 'uploads/';
		$DbHandler 	= new DbHandler();

		if (!is_writable($path)) {
			$response["error"] = true;
			$response["message"] = "Image destination directory not writable.";
			echoRespnse(500, $response);
		}

		$unique = strtoupper(md5(uniqid(rand(), true)));
		$image = new SimpleImage();
		$image->load($_FILES['news_image']['tmp_name']);
        $ext = pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION);
        $generatedFileName = $unique . '.' . $ext;
        
		if (!$image->save($path.$generatedFileName)) {
		    $response["error"] = true;
			$response["message"] = "Error in uploading the file";
			echoRespnse(500, $response);
		}

		if(!$DbHandler->saveImageName($params['project_id'],$generatedFileNAme)) {
			$response["error"] = true;
			$response["message"] = "Error while writing the file name to database";
			echoRespnse(500, $response);
		}

		$response["error"] = false;
		$response["message"] = "Imgage uplaod successfully";
		echoRespnse(500, $response);

	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}
});

$app->run();
		
?>
