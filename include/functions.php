<?php

function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    http_response_code($status_code);
    // setting response content type to json
    $app->contentType('application/json');
    
    echo json_encode($response);
    die();       
}
 
function callErrorLog($e){
    error_log($e->getMessage(). "\n", 3, "./error.log");
}


function verifyRequiredParams($required_fields,$params) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $params;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

function send_email ($template, $message=null) {

    try{
        if(isset($message['text']))
            $message_text = $message['text'];
        ob_start();
        include 'email/'.$template.'.php';
        $msg_body = ob_get_clean();

        if(!mail($message['to'],$message['subject'],$msg_body)) {
            return false;
        }

        return true;
    }catch(Exception $e){
        callErrorLog($e);
        return false;
    }
}
?>