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
			global $logged_user_type;
			global $user_fname;
			global $user_lname;
			$user_id = $access['user_id'];
			$features = $access['features'];
			$logged_user_type = $access['type'];
			$user_fname = $access['first_name'];
			$user_lname = $access['last_name'];

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
		global $logged_user_type;
		global $user_fname;
		global $user_lname;

		$response = array();
		$DbHandler = new DbHandler();	

        if ($features != NULL) {
        	$response["error"] = false;	
			$response['features'] = json_decode($features);
			$response['features']->logged_user_type=json_decode($logged_user_type);
			$response['features']->user_fname = $user_fname;
			$response['features']->user_lname = $user_lname;
			
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
			$response["message"] = "Email already exists";
			echoRespnse(400, $response);
		}
		$result = $DbHandler->createUser($params);

		if($result) {
			$resetKey = $DbHandler->generateResetKey($result);
			$url = getBaseUrl().'set-password;k='.$resetKey;

			$message['text'] = $url;
			$message['to']	 = $params['email'];
			// $message['subject']	= 'Activate your account';

			if(!send_email ('new_user_set_password', $message)) {
				$response["error"] = true;
				$response["message"] = "User created, Could not sent email";
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

			$user =  $DbHandler->checkEmailExist($params['email']);
			$user_id = $user->id;
			if(!$user_id){
				$response["error"] = true;
				$response["message"] = "Invalid email address";
				echoRespnse(404, $response);
			}
			$resetKey = $DbHandler->generateResetKey($user_id);
			if(!$resetKey ){
				$response["error"] = true;
				$response["message"] = "An error occurred while generating reset key Please try again";
				echoRespnse(500, $response);
			}

			$url = getBaseUrl().'reset-password;k='.$resetKey;

			$message['text'] = $url;
			$message['to']	 = $params['email'];
			$message['subject']	= 'Reset your password';
			$message['first_name']	 = $user->first_name;
			$message['last_name']	 = $user->last_name;

			if(!send_email ('resetpassword', $message)) {
				$response["error"] = true;
				$response["message"] = "An error occurred while sending the rest key email. Please try again";
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
			$response["message"] = "Email already exists";
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
			$url = getBaseUrl().'activate-user;key='.$activationKey;
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
	if(!$capabilities->manageUsers->view) {
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
				if($result['status'] == 1) {
		    		$response["error"] = true;
	                $response["message"] = "This link has already been used";
	                echoRespnse(200, $response);
				}
				$update_params = array('password' => $params['password']);
				if(isset($params['new_user']) && $params['new_user'] == true){
					$update_params['status'] = 1;
				}
				if(!$DbHandler->updateResetKeyStates($resetKey)) {
					$response["error"] = true;
					$response["message"] = "Couldnt update the reset the key";
					echoRespnse(200, $response);
				}
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
	$has_room_images = false;
	$has_furniture_images = false;
	$draft  = false;

	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->create) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}


	/*if(isset($_FILES['room_images']) && $_FILES['room_images'] != null && $_FILES['room_images'] != ""){
		$room_images = $_FILES['room_images'];
		$has_room_images = true;
	}

	if(isset($_FILES['furniture_images']) && $_FILES['furniture_images'] != null && $_FILES['furniture_images'] != "")	{
		$furniture_images = $_FILES['furniture_images'];
		$has_furniture_images = true;
	}*/

	if(isset($_POST['draft']) && $_POST['draft'] == true)
		$draft = true;

	$response 	= array();
	$DbHandler 	= new DbHandler();
	$params 	= json_decode($_POST['project'] , True);
	$result 	= false;

	$result = $DbHandler->createProject($params, $user_id, $draft);
	
	if(! empty($_FILES['room_images'])) {
		foreach ($_FILES['room_images']['tmp_name'] as $key => $tmp_name) {
			$file['name'] = $_FILES['room_images']['name'][$key];
			$file['type'] = $_FILES['room_images']['type'][$key];
			$file['tmp_name'] = $_FILES['room_images']['tmp_name'][$key];
			$file['error'] = $_FILES['room_images']['error'][$key];
			$file['size'] = $_FILES['room_images']['size'][$key];

			$generated_name = uploadProjectImages($file);

			if($generated_name == "") {
				$response["error"] = true;
				$response["message"] = "An error occurred while uploading images";
				echoRespnse(500, $response);
			}

			if(!$DbHandler->saveImageName($result,$generated_name,3)){
				$response["error"] = true;
				$response["message"] = "An error occurred while saving images";
				echoRespnse(500, $response);
			}
		}
	}

	if(! empty($_FILES['furniture_images'])) {
		foreach ($_FILES['furniture_images']['tmp_name'] as $key => $tmp_name) {
			$file['name'] = $_FILES['furniture_images']['name'][$key];
            $file['type'] = $_FILES['furniture_images']['type'][$key];
            $file['tmp_name'] = $_FILES['furniture_images']['tmp_name'][$key];
            $file['error'] = $_FILES['furniture_images']['error'][$key];
            $file['size'] = $_FILES['furniture_images']['size'][$key];

			$generated_name = uploadProjectImages($file);

			if($generated_name == "") {
				$response["error"] = true;
				$response["message"] = "An error occurred while uploading images";
				echoRespnse(500, $response);
			}

			if(!$DbHandler->saveImageName($result,$generated_name,4)){
				$response["error"] = true;
				$response["message"] = "An error occurred while saving images";
				echoRespnse(500, $response);
			}
		}
	}
	if (!$result) {
		$response["error"] = true;
		$response["message"] = "An error occurred while create the project";
		echoRespnse(500, $response);
	} else {

		$payment = $DbHandler->getPackage(1);
		if(!$payment) {
			$response["error"] = true;
			$response["message"] = "An error occurred Couldn't get the package";
			echoRespnse(500, $response);
		}

		$response["error"] = false;
		$response["message"] = "Project successfully created.";
		$response["project_id"] = $result;
		$response["price"] = $payment['price'];
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
	if($user_id['status'] == 1) {
		$response["error"] = true;
		$response["message"] = "This link has already been used";
		echoRespnse(200, $response);
	}

	$params = array("status" => 1);

	if(!$DbHandler->updateResetKeyStates($changeRequestCode)) {
		$response["error"] = true;
		$response["message"] = "Couldnt update the reset the key";
		echoRespnse(200, $response);
	}
	if(!$DbHandler->updateUser($params, $user_id['id'])) {
		$response["error"] = true;
        $response["message"] = "something went wrong while updating user";
        echoRespnse(500, $response);
	}
	
	$user = $DbHandler->getUser($user_id['id'])[0];
	$message['to']	 = $user->email;
	$message['subject']	= 'Your account activated successfully';
	$message['first_name']	 = $user->first_name;

	if(!send_email ('signup-complete', $message)) {
		$response["error"] = true;
		$response["message"] = "User created, Coundn't send an activation email";
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
	$result = (array)$DbHandler->getUser($user_id);

	$result = (array)$result[0];

	if(array_key_exists("id", $result))
                 unset($result['id']);

    if(array_key_exists("status", $result))
         unset($result['status']);

    if($result['user_type'] != 3) {
		if(array_key_exists("company_name", $result))
		unset($result['company_name']);

		if(array_key_exists("about", $result))
			unset($result['about']);
		if(array_key_exists("tranings", $result))
			unset($result['tranings']);
		if(array_key_exists("tools", $result))
			unset($result['tools']);
		if(array_key_exists("instagrame", $result))
			unset($result['instagrame']);
		if(array_key_exists("website", $result))
			unset($result['website']);
    }

    if(array_key_exists("user_type", $result))
		unset($result['user_type']);
	if(array_key_exists("type_id", $result))
		unset($result['type_id']);

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
	$response["price"]	 = $package['price'];
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

		if(!isset($params['project_id'])  ||
			!isset($params['amount']) ||
			!isset($params['first_name']) ||
			!isset($params['last_name']) ||
			!isset($params['payment_method']))
		{
			$response["error"] = true;
	        $response["message"] = "required parameters are missing";
	        echoRespnse(400, $response);
		}

		if( $params["project_id"] == ""
			|| $params["amount"] == ""
			|| $params["first_name"] == ""
			|| $params["last_name"] == ""
			|| $params["payment_method"] == ""
			) {
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

		//Sending notifications to daarkorators on new project
		$baseUrl = getBaseUrl();
		$daa = $DbHandler->getAllDaarkorators();
		$values = prepareBulkNotifications($daa, getNotificationText("project"),getNotificationUrl("project", $params["project_id"]) , "3");
		
		if(!$DbHandler->createNotification($values)){
			$response["error"] = false;
			$response['message'] = "Payment successful, error in creating notifications";
			echoRespnse(200	, $response);
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

		if(isset($params['password']) && isset($params['oldPassword']) && isset($params['repeatPassword'])) {
			if($params['password'] != $params['repeatPassword']) {
				$response["error"] = true;
				$response["message"] = "Confirm password mismatch";
				echoRespnse(400, $response);
			}

			if(!$DbHandler->chekOldPassword($params['oldPassword'], $user_id)) {
				$response["error"] = true;
				$response["message"] = "wrong old password";
				echoRespnse(400, $response);
			}
		}

		if(array_key_exists("oldPassword", $params))
			unset($params['oldPassword']);
		if(array_key_exists("repeatPassword", $params))
				unset($params['repeatPassword']);

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
 * List all Projects
 * url - /project/:limit/:bidding/:status
 * method - GET
 */
$app->get('/project(/:limit(/:bidding(/:status)))', 'authenticate', function($limit=null, $bidding=null, $status=null) use ($app) {
	global $features;
	global $user_id;
	global $logged_user_type;
	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->view) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getProjects($user_id, $logged_user_type, $limit, $status, $bidding);

	if(count($result) == null ) {
		$response["error"] = false;
		$response['projects'] = [];
		echoRespnse(200	, $response);
	}
	if ($result) {
		$response["error"] = false;
		$response['projects'] = $result;
		echoRespnse(200	, $response);		
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});


/**
 * get notification count
 * url - /notificationcount
 * method - GET
 */
$app->get('/notificationcount', 'authenticate', function() use ($app) {
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getNotificationCount($user_id);
	if ($result || $result == 0) {
		$response["error"] = false;
		$response['count'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});


/**
 * get notifications list
 * url - /notifications
 * method - GET
 */
$app->get('/notifications(/:limit(/:status))', 'authenticate', function($limit=null, $status=null) use ($app) {
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getNotifications($user_id, $limit, $status);
	if ($result) {
		$response["error"] = false;
		$response['notifications'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});



/*$app->post('/test', function() use ($app) {
    if($app->request() && $app->request()->getBody()){

		$response 	= array();
		$DbHandler 	= new DbHandler();
		$params 	= $app->request()->getBody();
		//print_r($params);
		$values = $params['values'];
		if($tmp = $DbHandler->createNotification($values)){
            $response["error"] = false;
            $response['message'] = "Notification created !";
            $response['tmp'] = $tmp;
            echoRespnse(200	, $response);
		}else{
		    $response["error"] = true;
            $response["message"] = "An error occurred.";
            echoRespnse(500, $response);
		}

	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}

});*/


/**
 * get Project details
 * url - /projectdetails/:project_id
 * method - GET
 */
$app->get('/projectdetails/:project_id', 'authenticate',function($project_id) use ($app) {
	global $user_id;
	global $logged_user_type;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getProjectDetails($project_id, $logged_user_type, $user_id);
	if(count($result) == 0 ) {
		$response["error"] = false;
		$response['Project_Detail'] = $result;
		echoRespnse(200	, $response);
	}
	if ($result) {
		$response["error"] = false;
		$response['Project_Detail'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

/**
 * create message
 * url - /messages
 * method - POST
 * params -message object
 */
$app->post('/message', 'authenticate', function() use ($app){
	global $user_id;
	global $logged_user_type;

    $response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();

        $db = new DbHandler();
        $tmp['styleboard_id']   = $params['styleboard_id'];
        $tmp['sender_id']       = $user_id;
        $tmp['message_text']    = $params['message_text'];

		$DbHandler 	= new DbHandler();
		$result = $DbHandler->createMessage($tmp, $logged_user_type);
		if(!$result) {
          		$response["error"] = true;
          		$response["message"] = "An error occurred while sending message";
          		echoRespnse(500, $response);
          	} else {
          		$response["error"] = false;
          		$response["message"] = "Message sent successfully!";
          		echoRespnse(200	, $response);
            }
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(400, $response);
	}
});


/**
 * get message list
 * url - /message/:styleboard(/:limit(/:status))
 * method - GET
 */
$app->get('/message(/:limit(/:status))', 'authenticate', function($limit=null, $status=null) use ($app) {
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getMessageList($user_id, $limit, $status);

	if ($result) {
		$response["error"] = false;
		$response['messages'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = false;
		$response["messages"] = [];
		echoRespnse(200, $response);
	}
});


/**
 * get message detail
 * url - /messagedetail(/:message_id)
 * method - GET
 */
$app->get('/messagedetail(/:message_id)', 'authenticate', function($message_id) use ($app) {
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->messageDetail($message_id, $user_id);
	if ($result) {
		$response["error"] = false;
		$response['message'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = false;
		$response["message"] = [];
		echoRespnse(200, $response);

	}
});

/*
* Style boards
* Url : /styleboard
* method - POST
*/
$app->post('/styleboard','authenticate'  ,function() use ($app) {
	global $user_id;
	global $features;
	$response 	= array();
	$DbHandler 	= new DbHandler();	
	$params 	= $_POST;

	// $capabilities = json_decode($features);
	// if(!$capabilities->manageProjects->addStyleBoard) {
	// 	$response["error"] = true;
    //     $response["message"] = "Unauthorized access";
    //     echoRespnse(401, $response);
	// }

	if(!isset($params['project_id']) ||  $params['project_id'] == ""
		|| !isset($params['style_board_name']) ||  $params['style_board_name'] == "" )
	{
		$response["error"] = true;
		$response["message"] = "Required Feilds are missing";
		echoRespnse(400, $response);
	}
	if(!empty($_FILES['style_board'])) {	
		$file['name'] = $_FILES['style_board']['name'];
		$file['type'] = $_FILES['style_board']['type'];
		$file['tmp_name'] = $_FILES['style_board']['tmp_name'];
		$file['error'] = $_FILES['style_board']['error'];
		$file['size'] = $_FILES['style_board']['size'];

		$generated_name = uploadPdf($file);
		if($generated_name == "") {
			$response["error"] = true;
			$response["message"] = "An error occurred while uploading images";
			echoRespnse(500, $response);		
		}

		if(!$DbHandler->saveStyleBoard($params,$generated_name,$user_id)){
			$response["error"] = true;
			$response["message"] = "An error occurred while saving Styleboard";
			echoRespnse(500, $response);
		}

		// Sending notifications to customer on new project
		$baseUrl = getBaseUrl();
		$customer = $DbHandler->getCustomerByProject($params['project_id']) ;
		$values = $customer['customer_id'].', 
				 		"'.getNotificationText("styleboard").'", "'.getNotificationUrl("styleboard",$params["project_id"]).'",
				 "2"';
				 
		if(!$DbHandler->createNotification($values, "true")){
			$response["error"] = false;
			$response['message'] = "Error in sending notifications to the customer ";
			echoRespnse(200	, $response);
		}

		$response["error"] = false;
		$response["message"] = "Style board successfully attached";
		echoRespnse(200, $response);
		
	}else{
		$response["error"] = true;
		$response["message"] = "No styleboard attached";
		echoRespnse(400, $response);
	}
});

/**
 * get All style boards for all projects 
 * url - /styleboard/project_id
 * method - GET
 */
 $app->get('/styleboards', 'authenticate', function() use ($app) {
	global $user_id;
	
	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getAllStyleboards(null,null, $user_id);
	if(count($result) == 0 ) { 
		$arr = array();
		$response["error"] = false;
		$response['styleboards'] = [];
		echoRespnse(200	, $response);
	}

	if ($result) { 
		$response['styleboards'] = $result;
		$response["error"] 		 = false;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

/**
 * get All style boards
 * url - /styleboard/project_id
 * method - GET
 */
 $app->get('/styleboards/:project_id', 'authenticate', function($project_id) use ($app) {
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getAllStyleboards(null,$project_id, $user_id);
	if(count($result) == 0 ) {
		$response["error"] = false;
		$response['styleboards'] = [];
		echoRespnse(200	, $response);
	}
	if ($result) {
		$response["error"] = false;
		$response['styleboards'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});


/**
 * get All style board by id
 * url - /styleboard
 * method - GET
 */
 $app->get('/styleboard/:id', 'authenticate', function($id) use ($app) {
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getAllStyleboards($id, null, $user_id);
	if(count($result) == 0 ) {
		$response["error"] = false;
		$response['styleboard'] = $result;
		echoRespnse(200	, $response);
	}
	if ($result) {
		$response["error"] = false;
		$response['styleboard'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

$app->put('/styleboard/:id', 'authenticate', function($styleboard_id) use ($app){
    global $user_id;
    $response = array();
    $DbHandler = new DbHandler();
    $results = $DbHandler->getAllStyleboards($styleboard_id, null, $user_id);

    $result = $DbHandler->updateStyleboard($styleboard_id, 1);
    if(!empty($results)){
        if($results) {
            $project_id = $results['project_id'];
            $updateArr = array(
                'status' => 2
            );
            $DbHandler = new DbHandler();
            $projectUpdate = $DbHandler->updateProject($updateArr, $results['project_id']);
            // Sending notifications to daakor on styleboard selection
			$baseUrl = getBaseUrl();
			$daakor = $DbHandler->getDaakorByStyleboard($styleboard_id);
			$values = $daakor.', 
					 		"'.getNotificationText("styleboardSelect" ).'", 
					 		"'.getNotificationUrl("styleboard",$project_id).'",
					 		"1"';
					
			if(!$DbHandler->createNotification($values, null)){
				$response["error"] = false;
				$response['message'] = "Error in sending notifications to the Daarkorator ";
				echoRespnse(200	, $response);
			} 
            if($projectUpdate){
                $response["error"] = false;
                $response["message"] = "Style board finalized successfully!";
                echoRespnse(200	, $response);
            }
        }else{
            $response["error"] = true;
            $response["message"] = "An error occurred! Please try again";
            echoRespnse(500	, $response);
        }
    }else{
      $response["error"] = true;
      $response["message"] = "An error occurred! Please try again";
      echoRespnse(500	, $response);
    }
    /**/
});

/**
 * Update messages 
 * url - /message/:id
 * method - PUT
 * params - */
 $app->put('/message/:id', 'authenticate', function($mgs_id) use ($app) {
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
 * Daarkorator Signup
 * url - /daarkoratorSignUp
 * method - POST
 * params -user object
 */	
 $app->post('/daarkoratorSignUp',  function() use ($app){
	
	$response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();
		$DbHandler 	= new DbHandler();
		if($DbHandler->getUserByEmail($params['email'])) {
			$response["error"] = true;
			$response["message"] = "Email already exists";
			echoRespnse(400, $response);
		}
		$user_type = 3;
		
		if(!$DbHandler->validate($params)) {
			$response["error"] = false;
			$response["message"] = "Validation failed";
			echoRespnse(400	, $response);
		}

		$result = $DbHandler->createUser($params, $user_type, 5);

		if($result) {
			$message['to']	 = $params['email'];
			$message['first_name'] = $params['first_name'];
			$message['subject']	= 'Your Daakor application has been received ';

			if(!send_email ('new_daarkorator_created', $message)) {
				$response["error"] = true;
				$response["message"] = "User created successfully, Coundn't send an email";
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
 * Approve Darrkorator
 * url - /approveDaarkoratorer
 * method - PUT
 * params - non
 */	
 $app->put('/approveDaarkorator/:id', 'authenticate', function($id) use ($app){
	global $features;
	
	$capabilities = json_decode($features);
	if(!$capabilities->manageUsers->update) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response 	= array();
	
	$params 	=  array('status' => 0);
	$DbHandler 	= new DbHandler();

	$result = $DbHandler->updateUser($params, $id);
	// die($result);
	if($result) {
		$user = $DbHandler->getUser($id);
		// print_r($user)
		if(!$user[0]->email) {
			$response["error"] = true;
			$response["message"] = "User approved successfully, Coundn't find the email address";
			echoRespnse(500, $response);	
		}
		$resetKey = $DbHandler->generateResetKey($id);
		$url = getBaseUrl().'set-password;k='.$resetKey;

		$message['text'] = $url;
		$message['to']	 = $user[0]->email;
		$message['subject']	= 'Set your password';

		if(!send_email ('new_user_set_password', $message)) {
			$response["error"] = true;
			$response["message"] = "User approved successfully, Could not sent an email";
			echoRespnse(400, $response);
		}
		$response["error"] = false;
		$response['message'] = "User approved Successfully";
		echoRespnse(200	, $response);
	}else{
		$response["error"] = true;
		$response["message"] = "An error occurred. Please try again";
		echoRespnse(500, $response);
	}
});


/**
 * List new projects
 * url - /newProject
 * method - GET
 */
 $app->get('/newProjects(/:limit)', 'authenticate', function($limit=null) use ($app) {
	global $features;
	global $user_id;
	global $logged_user_type;
	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->view) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->getProjects($user_id, 3, $limit, null, "yes");
	if(count($result) == 0 ) {
		$response["error"] = false;
		$response['projects'] = [];
		echoRespnse(200	, $response);
	}
	if ($result) {
		$response["error"] = false;
		$response['projects'] = $result;
		echoRespnse(200	, $response);
	} else {
		$response["error"] = true;
		$response["message"] = "The requested resource doesn't exists";
		echoRespnse(404, $response);
	}
});

/**
 * Add to my projects
 * url - /addToMyProjects
 * method - POST
 * params - NA
 */
 $app->post('/addToMyProjects', 'authenticate', function() use ($app){
	global $features;
	global $user_id;

	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->update) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	if($app->request() && $app->request()->getBody()){
		$params 	= $app->request()->getBody();
		$project_id = $params['project_id'];
		$response 	= array();
		$DbHandler 	= new DbHandler();
		if($DbHandler->checkProjectExcist($project_id, $user_id)) {
			$response["error"] = true;
			$response["message"] = "Project already exist on my projects";
			echoRespnse(200, $response);
		}
		if(!$DbHandler->addToMyProjects($project_id, $user_id)) {
			$response["error"] = true;
			$response["message"] = "Something went wrong while adding the project";
			echoRespnse(500, $response);
		}
		$response["error"] = false;
		$response["message"] = "Added to my projects successfully";
		echoRespnse(200, $response);
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}	
});

/**
 * Cancel project
 * url - /projectCancel
 * method - PUT
 * params - NA
 */
 $app->put('/projectCancel/:id', 'authenticate', function($id) use ($app){
	global $features;
	global $user_id;

	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->remove) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}
	$response 	= array();
	$DbHandler 	= new DbHandler();

	$updateArr = array(
		'status' => 4
	);
	$result = $DbHandler->checkProjectStatus($id);
	if(!$result) {
		$response["error"] = true;
		$response["message"] = "An error occurred, You cannot cancel this project";
		echoRespnse(400, $response);
	}

	if(!$DbHandler->updateProject($updateArr, $id)){
		$response["error"] = true;
		$response["message"] = "An error occurred while canceling project";
		echoRespnse(500, $response);
	}

	$response["error"] = false;
	$response["message"] = "Project successfully canceled";
	echoRespnse(200, $response);
});

/**
 * read notifications
 * url - /readNotifications
 * method - PUT
 * params - 
 */
 $app->put('/readNotifications/:id', 'authenticate', function($id) use ($app){

	$response 	= array();
	$DbHandler 	= new DbHandler();
	$result = $DbHandler->updateNotifications($id);

	if(!$result){
		$response["error"] = true;
		$response["message"] = "An error occurred while updating notifications";
		echoRespnse(500, $response);
	}
	$response["error"] = false;
	$response["message"] = "Notification updated successfully";
	echoRespnse(200, $response);
});




 $app->post('/sendEmail', function() use ($app) {
	
	$url = getBaseUrl().'reset-password;k=';

	$message['text'] = $url;
	$message['to']	 = "shuboothi@gmail.com";
	$message['subject']	= 'Testing emails';

	if(!send_email ('resetpassword', $message)) {
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
 * delete styleboard
 * url - /project/:id
 * method - DELETE
 */
 $app->delete('/styleboard/:id', 'authenticate', function($id) use ($app) {
	global $features;
	global $user_id;

	$response = array();
	$DbHandler = new DbHandler();
	$result = $DbHandler->deleteStyleboard($id);

	if(!$result) {
		$response["error"] = true;
		$response["message"] = "An error occurred. Please try again";
		echoRespnse(500, $response);
	}else{
		$response["error"] = false;
		$response["message"] = "Successfully deleted styleboard";
		echoRespnse(200	, $response);
	}
	
});

/**
 * Update Project
 * url - /updateProject
 * method - post
 **/		
 $app->post('/updateProject/:id', 'authenticate', function($project_id) use ($app) {
	global $features;
	global $user_id;
	$has_room_images = false;
	$has_furniture_images = false;
	$draft  = false;

	
	if(isset($_POST['draft']) && $_POST['draft'] == true)
		$draft = true;
	
	$response 	= array();
	$DbHandler 	= new DbHandler();
	$params 	= json_decode($_POST['project'] , True);
	$result 	= false;
	
	$result = $DbHandler->updateProjectDetails($params,$project_id);
	
	if(!empty($_FILES['room_images'])) {
		foreach ($_FILES['room_images']['tmp_name'] as $key => $tmp_name) {
			$file['name'] = $_FILES['room_images']['name'][$key];
			$file['type'] = $_FILES['room_images']['type'][$key];
			$file['tmp_name'] = $_FILES['room_images']['tmp_name'][$key];
			$file['error'] = $_FILES['room_images']['error'][$key];
			$file['size'] = $_FILES['room_images']['size'][$key];

			$generated_name = uploadProjectImages($file);

			if($generated_name == "") {
				$response["error"] = true;
				$response["message"] = "An error occurred while uploading images";
				echoRespnse(500, $response);
			}

			if(!$DbHandler->saveImageName($project_id,$generated_name,3)){
				$response["error"] = true;
				$response["message"] = "An error occurred while saving images";
				echoRespnse(500, $response);
			}
		}
	}

	if(!empty($_FILES['furniture_images'])) {
		foreach ($_FILES['furniture_images']['tmp_name'] as $key => $tmp_name) {
			$file['name'] = $_FILES['furniture_images']['name'][$key];
            $file['type'] = $_FILES['furniture_images']['type'][$key];
            $file['tmp_name'] = $_FILES['furniture_images']['tmp_name'][$key];
            $file['error'] = $_FILES['furniture_images']['error'][$key];
            $file['size'] = $_FILES['furniture_images']['size'][$key];

			$generated_name = uploadProjectImages($file);

			if($generated_name == "") {
				$response["error"] = true;
				$response["message"] = "An error occurred while uploading images";
				echoRespnse(500, $response);
			}

			if(!$DbHandler->saveImageName($project_id,$generated_name,4)){
				$response["error"] = true;
				$response["message"] = "An error occurred while saving images";
				echoRespnse(500, $response);
			}
		}
	}
	if (!$result) {
		$response["error"] = true;
		$response["message"] = "An error occurred while updating the project";
		echoRespnse(500, $response);
	} else {
		$payment = $DbHandler->getPackage(1);
		if(!$payment) {
			$response["error"] = true;
			$response["message"] = "An error occurred Couldn't get the package";
			echoRespnse(500, $response);
		}

		$response["error"] = false;
		$response["message"] = "Project successfully Updated.";
		$response["price"] = $payment['price'];
		echoRespnse(200	, $response);
	}
});


$app->put('/selectStyleboard', function() use ($app) {

	if($app->request() && $app->request()->getBody()){
		$params 	= $app->request()->getBody();
		$project_id = $params['project_id'];
		$styleboard_id = $params['styleboard_id'];

		$response 	= array();
		$DbHandler 	= new DbHandler();

		$result = $DbHandler->selectStyleboard($project_id, $styleboard_id);
		
		if(!$result || $result === 0 ) {
			$response["error"] = true;
			$response["message"] = "Something went wrong while adding the stlyboard to the project";
			echoRespnse(500, $response);
		}

		if($result === 3 ) {
			$response["error"] = true;
			$response["message"] = "This stlyboard is deleted from the portal";
			echoRespnse(200, $response);
		}

		if($result === 1) {
			$response["error"] = true;
			$response["message"] = "A styleboard is already selected";
			echoRespnse(200, $response);
		}

		$response["error"] = false;
		$response["message"] = "Styleboard added to projects successfully";
		echoRespnse(200, $response);
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(500, $response);
	}	
});

/**
 * Submit Deliverables
 * url - /deliverables
 * method - POST
 * params - NA
 */
 $app->post('/deliverables', 'authenticate', function() use ($app){
	global $features;
	global $user_id;

	$capabilities = json_decode($features);
	if(!$capabilities->manageProjects->deliverablesUpload) {
		$response["error"] = true;
        $response["message"] = "Unauthorized access";
        echoRespnse(401, $response);
	}

	$params = $_POST;

	if(!$params) {
		$response["error"] = true;
		$response["message"] = "Required feilds are missing";
		echoRespnse(300, $response);
	}
	$project_id 	= $_POST['project_id'];

	$response 	= array();
	$DbHandler 	= new DbHandler();

	if(! empty($_FILES['deliverables_1'])) {
	
		$file['name'] = $_FILES['deliverables_1']['name'];
		$file['type'] = $_FILES['deliverables_1']['type'];
		$file['tmp_name'] = $_FILES['deliverables_1']['tmp_name'];
		$file['error'] = $_FILES['deliverables_1']['error'];
		$file['size'] = $_FILES['deliverables_1']['size'];

		if($file['type'] == 'application/pdf') 
			$generated_name = uploadPdf($file);	
		else
			$generated_name = uploadProjectImages($file);	

		if($generated_name == "") {
			$response["error"] = true;
			$response["message"] = "An error occurred while uploading ".$file['name']." ";
			echoRespnse(500, $response);
		}

		if(!$DbHandler->saveDeliverableFile($project_id,$generated_name, $file['name'] ,1)) {
			$response["error"] = true;
			$response["message"] = "An error occurred while saving ".$file['name']." ";
			echoRespnse(500, $response);
		}
	}

	if(! empty($_FILES['deliverables_2'])) {
	
		$file['name'] = $_FILES['deliverables_2']['name'];
		$file['type'] = $_FILES['deliverables_2']['type'];
		$file['tmp_name'] = $_FILES['deliverables_2']['tmp_name'];
		$file['error'] = $_FILES['deliverables_2']['error'];
		$file['size'] = $_FILES['deliverables_2']['size'];

		if($file['type'] == 'application/pdf') 
			$generated_name = uploadPdf($file);	
		else
			$generated_name = uploadProjectImages($file);	

		if($generated_name == "") {
			$response["error"] = true;
			$response["message"] = "An error occurred while uploading ".$file['name']." ";
			echoRespnse(500, $response);
		}

		if(!$DbHandler->saveDeliverableFile($project_id,$generated_name, $file['name'] ,2)) {
			$response["error"] = true;
			$response["message"] = "An error occurred while saving ".$file['name']." ";
			echoRespnse(500, $response);
		}
	}

	if(! empty($_FILES['deliverables_3'])) {
	
		$file['name'] = $_FILES['deliverables_3']['name'];
		$file['type'] = $_FILES['deliverables_3']['type'];
		$file['tmp_name'] = $_FILES['deliverables_3']['tmp_name'];
		$file['error'] = $_FILES['deliverables_3']['error'];
		$file['size'] = $_FILES['deliverables_3']['size'];

		if($file['type'] == 'application/pdf') 
			$generated_name = uploadPdf($file);	
		else
			$generated_name = uploadProjectImages($file);	

		if($generated_name == "") {
			$response["error"] = true;
			$response["message"] = "An error occurred while uploading ".$file['name']." ";
			echoRespnse(500, $response);
		}

		if(!$DbHandler->saveDeliverableFile($project_id,$generated_name, $file['name'],3)) {
			$response["error"] = true;
			$response["message"] = "An error occurred while saving ".$file['name']." ";
			echoRespnse(500, $response);
		}
	}

	if(! empty($_FILES['deliverables_4'])) {
	
		$file['name'] = $_FILES['deliverables_4']['name'];
		$file['type'] = $_FILES['deliverables_4']['type'];
		$file['tmp_name'] = $_FILES['deliverables_4']['tmp_name'];
		$file['error'] = $_FILES['deliverables_4']['error'];
		$file['size'] = $_FILES['deliverables_4']['size'];

		if($file['type'] == 'application/pdf') 
			$generated_name = uploadPdf($file);	
		else
			$generated_name = uploadProjectImages($file);	

		if($generated_name == "") {
			$response["error"] = true;
			$response["message"] = "An error occurred while uploading ".$file['name']." ";
			echoRespnse(500, $response);
		}

		if(!$DbHandler->saveDeliverableFile($project_id,$generated_name, $file['name'],4)) {
			$response["error"] = true;
			$response["message"] = "An error occurred while saving ".$file['name']." ";
			echoRespnse(500, $response);
		}
	}

	$response["error"] = false;
	$response["message"] = "Deliverable uploaded successfully ";
	echoRespnse(200, $response);
});

 /**
 * Get Deliverables
 * url - /deliverables
 * method - GET
 * params - NA
 */
 $app->get('/deliverables/:project_id', 'authenticate', function($project_id) use ($app){

	$response 	= array();
	$DbHandler 	= new DbHandler();
	$result		= $DbHandler->getDeliverables($project_id);

	if(empty($result)) {
		$response["error"] = false;
		$response["deliverables"] = [];
		echoRespnse(200, $response);
	}

	if(!$result) {
		$response["error"] = true;
		$response["message"] = "An error occurred while fetching data";
		echoRespnse(500, $response);
	}


	$response["error"] = false;
	$response["deliverables"] = $result;
	echoRespnse(200, $response);
});

/**
 * Accept Deliverables
 * url - /deliverables
 * method - PUT
 * params - non
 */	
 $app->put('/deliverables/:styleboard_id', 'authenticate', function($styleboard_id) use ($app){
	global $features;
	
	$capabilities = json_decode($features);
	// if(!$capabilities->manageUsers->update) {
	// 	$response["error"] = true;
 //        $response["message"] = "Unauthorized access";
 //        echoRespnse(401, $response);
	// }

	$response 	= array();
	
	$params 	=  array('status' => 4);
	$DbHandler 	= new DbHandler();

	if(!$DbHandler->updateStyleboard($styleboard_id, 4)) {
		$response["error"] = true;
		$response["message"] = "An error occurred. Please try again";
		echoRespnse(500, $response);
	}

	$response["error"] = fales;
	$response["message"] = "Deliverables accepted successfully";
	echoRespnse(200, $response);
	
}); 

/**
 * External messages
 * url - /newMessages
 * method - POST
 * params -message object
 */
$app->post('/newMessage', 'authenticate', function() use ($app){
	global $user_id;
	global $logged_user_type;

    $response 	= array();
	if($app->request() && $app->request()->getBody()){
		$params 	=  $app->request()->getBody();

        $db = new DbHandler();
        $tmp['sender_id']       = $user_id;
        $tmp['reciever_id']      = $params['reciever_id'];
        $tmp['message_text']    = $params['message_text'];
        $tmp['project_id']		= $params['project_id'];
        $tmp['message_reff']	= 1;
      
		$DbHandler 	= new DbHandler();
		$result = $DbHandler->createExternalMessage($tmp);
		if(!$result) {
          		$response["error"] = true;
          		$response["message"] = "An error occurred while sending message";
          		echoRespnse(500, $response);
          	} else {
          		$response["error"] = false;
          		$response["message"] = "Message sent successfully!";
          		echoRespnse(200	, $response);
            }
	}else {
		$response["error"] = true;
		$response["message"] = "An error occurred. No request body";
		echoRespnse(400, $response);
	}
});

$app->run();
		
?>

