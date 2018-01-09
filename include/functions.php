<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'libs/PHPMailer/src/Exception.php';
require_once 'libs/PHPMailer/src/PHPMailer.php';
require_once 'libs/PHPMailer/src/SMTP.php';


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
        if(isset($message['first_name']))
            $message_first_name = $message['first_name'];
        if(isset($message['last_name']))
            $message_last_name = $message['last_name'];

        $messagebody = file_get_contents('email/'.$template.'.html');

        if(isset($message['text']))
        $messagebody = str_replace('%message_text%', $message_text, $messagebody);
        if(isset($message['first_name']))
        $messagebody = str_replace('%message_first_name%', $message_first_name, $messagebody);
        if(isset($message['last_name']))
        $messagebody = str_replace('%message_last_name%', $message_last_name, $messagebody);


        //$mail = new PHPMailer(true);

        //$mail->isSMTP();
        //$mail->Host = 'email-smtp.us-west-2.amazonaws.com';
        //$mail->SMTPAuth = true;
        //$mail->Username = 'AKIAJUNQKA3CXCI7YIZQ';
        //$mail->Password = 'AiaY558KBBlcf8ls3nVT1F7bl3eQfZAtyBoDSlPaahGq';
        //$mail->SMTPSecure = 'tls';
        //$mail->Port = 587;

        //Recipients
        //$mail->setFrom('no-reply@dhammika.me', 'Daakor');
        //$mail->addAddress($message['to']);
        //$mail->addReplyTo('info@daakor.com', 'Information');

        //Content
        //$mail->isHTML(true);
        //$mail->Subject = $message['subject'];
        //$mail->Body    = $messagebody;
        //if(!$mail->send()){
        //    return false;
        //}
        //print_r($messagebody);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        $headers[] = 'From: Daakor-noreply@daakor.com' ;
        $headers[] = 'Reply-To: info@daakor.com';
        $headers[] = 'X-Mailer: PHP/' . phpversion();

        if(!mail($message['to'],$message['subject'],$messagebody, implode("\r\n", $headers))) {
        	return false;
        }

        return true;
    }catch(Exception $e){
        callErrorLog($e);
        return false;
    }

    function getSiteBaseUrl() {
        return true;
    }
}


function gnerateTransactionId($user_id) {
    try{
        $transactionId = md5(time()); 
        return $transactionId;
    }catch(Exception $e){
        callErrorLog($e);
        return false;
    }
}

function uploadProjectImages($file) {
    // print_r( $file);
    $path = 'uploads/';
    $unique = strtoupper(md5(uniqid(rand(), true)));
    $image = new SimpleImage();
    $image->load($file['tmp_name']);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $generatedFileName = $unique . '.' . $ext;
    
    $image->save($path.$generatedFileName);
    return $generatedFileName;   
}


function prepareBulkNotifications($daarkors, $notificationsText, $url=null, $type=null) {
    $values = array();
    $inc = 0;
    foreach ($daarkors as $daarkor) {
        $values[$inc] = "(".$daarkor['id'].", '".$notificationsText."', '".$url."', '".$type."')";
        $inc++;
    }
    
    return implode(",",$values);
}

function uploadPdf($file) {
    $path = 'uploads/';
    $unique = strtoupper(md5(uniqid(rand(), true)));
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $generatedFileName = $unique . '.' . $ext;

    if(move_uploaded_file($file['tmp_name'], $path.$generatedFileName)) {
        return $generatedFileName;
    }
    return false;
}

function getNotificationText($notificationType, $params=null) {
    switch ($notificationType) {
        case "project" :
            return "A new room design contest has kicked off";
        break;
        case "styleboard" :
            return "Check out the new style board added to ".$params;
        break;
        case "styleboardSelect" :
            return "Congratulations! ".$params['customer_name']." selected your style board as the winner for ".$params['project_name'];
        break;
        case "message" :
            return "You received a new message on ".$params;
        break;
    }    
}

function getNotificationUrl($notificationType, $projectId=null) {
    switch ($notificationType) {
        case "project" :
            $data = array(
                "id" => $projectId,
                "showAddProject" => true,
                "isCancelled" => false
            );
            
            $ecoded = base64_encode(json_encode($data));
            return "project-details/".$ecoded;
        break;
        case "styleboard" :
            $data = array(
                "id" => $projectId,
                "showAddProject" => false,
                "isCancelled" => false
            );
            
            $ecoded = base64_encode(json_encode($data));
            return "project-details/".$ecoded;
        break;
    }    
}

function getBaseUrl() {
    //production
    // return "http://app.daakor.com/#/";

    //test
    return "http://daakor.dhammika.me/#/";
}

?>