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
        if(isset($message['transaction_number']))
            $transaction_number = $message['transaction_number'];
        if(isset($message['total_paid']))
            $message_amount = $message['total_paid'];
        if(isset($message['sub_total']))
            $message_subtotal = $message['sub_total'];
        if(isset($message['tax']))
            $message_tax = $message['tax'];
        if(isset($message['project_name']))
            $message_tax = $message['project_name'];


        $messagebody = file_get_contents('email/'.$template.'.html');

        if(isset($message['text']))
        $messagebody = str_replace('%message_text%', $message_text, $messagebody);
        if(isset($message['first_name']))
        $messagebody = str_replace('%message_first_name%', $message_first_name, $messagebody);
        if(isset($message['last_name']))
        $messagebody = str_replace('%message_last_name%', $message_last_name, $messagebody);
        if(isset($message['transaction_number']))
        $messagebody = str_replace('%transaction_number%', $transaction_number, $messagebody);

     if(isset($message['sub_total']))
        $messagebody = str_replace('%sub_total%', $message['sub_total'], $messagebody);
     if(isset($message['discount']))
        $messagebody = str_replace('%discount%', $message['discount'], $messagebody);
     if(isset($message['tax']))
        $messagebody = str_replace('%tax%', $message['tax'], $messagebody);
     if(isset($message['total_paid']))
        $messagebody = str_replace('%total_paid%', $message['total_paid'], $messagebody);
    if(isset($message['project_name']))
       $messagebody = str_replace('%project_name%', $message['project_name'], $messagebody);

    if(isset($message['cur']))
         $messagebody = str_replace('%cur%', $message['cur'], $messagebody);

        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'email-smtp.us-west-2.amazonaws.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'AKIAIMT3WCCARULI3WLA';
        $mail->Password = 'AjKrou2iDw/jcFVAgfVIql/xe4ppfb+lrce+d3Fe8kmG';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        //Recipients
        $mail->setFrom('info@daakor.com', 'Daakor-noreply@daakor');
        $mail->addAddress($message['to']);
        $mail->addReplyTo('info@daakor.com', 'Information');

        //Content
        $mail->isHTML(true);
        $mail->Subject = $message['subject'];
        $mail->Body    = $messagebody;
        if(!$mail->send()){
            return false;
        }
        //print_r($messagebody);
        //$headers[] = 'MIME-Version: 1.0';
        //$headers[] = 'Content-type: text/html; charset=iso-8859-1';
        //$headers[] = 'From: Daakor-noreply@daakor.com' ;
        //$headers[] = 'Reply-To: info@daakor.com';
        //$headers[] = 'X-Mailer: PHP/' . phpversion();

        //if(!mail($message['to'],$message['subject'],$messagebody, implode("\r\n", $headers))) {
        //	return false;
        //}

        return true;
    }catch(Exception $e){
        callErrorLog($e);
        return false;
    }
}


function gnerateTransactionId($user_id) {
    try{
        $transactionId = "DAA-".substr(time(),6).rand(1000,9999);
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
            return "A new room design contest has kicked off!";
        break;
        case "styleboard" :
            return "Check out the new style board added to ".$params.".";
        break;
        case "styleboardSelect" :
            return "Congratulations! ".$params['customer_name']." has selected your style board as the winner for ".$params['project_name'].".";
        break;
        case "message" :
            return "You received a new message on ".$params.".";
        break;
        case "styleboardDidntwWin" :
            return $params['customer_name']." has selected a winning style board for ".$params['project_name'].". Thank you for your participation in this contest.";
        break;
        case "finalDeliverables" :
            return $params['customer_name']." has accepted your final deliverables for ".$params['project_name'].". Congratulations on a successful gig!";
        break;
        case "projectCancel" :
            return "Customer has decided to cancel ".$params['project_name'].".";
        break;
        case "sumbmitFinalDeliverablesCustomer" :
            return "Check out the final deliverables added to your project.";
        break;
         case "newDaakorSignUp" :
            return "A new daakorator has signed up";
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
        case "projectCancel" :
            $data = array(
                "id" => $projectId,
                "showAddProject" => false,
                "isCancelled" => true
            );

            $ecoded = base64_encode(json_encode($data));
            return "project-details/".$ecoded;
        break;
        case "daakorSignUp" :
            return "user-manage";
        break;
    }
}

function getBaseUrl() {
    //production
     return "http://18.218.149.78/#/";

    //test
    //return "http://testapp.daakor.com/#/";
}

function sendEmailsToDaakors ($daa) {
    foreach ($daa as $key => $value) {
        $message['to']   = $value['email'];
        $message['first_name'] = $value['first_name'];
        $message['subject'] = 'A new room design contest has kicked off';

        send_email ('new_project_daakor', $message);
    }
    return true;
}

?>
