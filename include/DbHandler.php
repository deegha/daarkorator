<?php
require 'libs/vendor/autoload.php';

Braintree_Configuration::environment('sandbox');
Braintree_Configuration::merchantId('w3hzrzq84x6f2dmy');
Braintree_Configuration::publicKey('5pbn8wrm8scdpgwy');
Braintree_Configuration::privateKey('68c4222613b1ad435b216b4a2f6813da');

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 */
class DbHandler {

    public function checkLogin($email, $password) {
        try{
            $db = new database();
            $table = 'user';
            $rows = '*';
            $where = 'email= "' . $email . '" AND status = 1  AND password="'.$password.'"';

            $db->select($table, $rows, $where, '', '');

            $logged_User = $db->getResults();

            if ($logged_User != NULL) {
               return true;
            } else {
                return false;
            }
        }catch (Exception $e) {
            return false;
        }
    }

    public function callErrorLog($e){
        error_log(date('Y-M-D  h:i A')." - ".$e->getMessage(). "\n", 3, "./error.log");
    }

    public function getAccessToken($user_id) {
        try{
            $db          = new database();
            $table       = "authentication_table";
            $accesstoken = bin2hex(openssl_random_pseudo_bytes(260));
            $expiration  = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 day'));

            $rows   = 'user_id,access_token,expiration';
            $values = '"'.$user_id.'","'.$accesstoken.'","'.$expiration.'"';

            if($db->insert($table,$values,$rows)){
                return  $accesstoken;
            }else{
                return false;
            }

        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }
    }

    private function updateAccesstokenExpiry($user_accessToken) {
        try{
            $db          = new database();
            $table       = "authentication_table";
            $rows        =  array("expiration" => date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 day')));
            $where       = "access_token= '" . $user_accessToken ."'";
            if(!$db->update($table,$rows,$where)) {
                return false;
            }

            return true;
        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }
    }

    public function isValidAccessToken($user_accessToken) {
        try{
            $db          = new database();
            $table       = "authentication_table a JOIN user_type t JOIN user u";
            $rows        = 'a.user_id, a.expiration, t.features, t.type, u.first_name, u.last_name, u.contact_number as telephone';
            $where       = 'a.access_token= "' . $user_accessToken .'" AND u.user_type = t.type AND a.user_id = u.id';

            $db->select($table, $rows, $where, '', '');
            $access = $db->getResults();
            if(!$access){
                return false;
            }

            if($access['expiration'] >= date('Y-m-d H:i:s')){
                 $this->updateAccesstokenExpiry($user_accessToken);
            }

        	return $access;

        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getUserFeatures($user_id) {
        try{
            $db          = new database();
            $table       = "user u JOIN user_type t";
            $rows        = 'u.id, t.features';
            $where       = 'u.user_type = t.type AND u.id = "'.$user_id.'"';

            $db->select($table, $rows, $where, '', '');
            $features = $db->getResults();

            if(!$features) {
                return false;
            }

            return json_decode($features[1]);
        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }

    }


    public function getUserByEmail($email) {
        $db = new database();
        $table = 'user';
        $rows = '*';
        $where = 'email= "' . $email . '" and status <> 3';

        $db->select($table, $rows, $where, '', '');
        $logged_User = $db->getResults();

        if(!$logged_User) {
            return false;
        }
        return $logged_User;
    }

    public function createUser($params, $user_type=null, $status=null) {
        try{
            $db           = new database();
            $user_table   = "user";
            $user_rows    = [];
            $user_values  = [];
            $daarkorator_details = [];
            $is_daarkorator = false;

            if(array_key_exists("daarkorator_details", $params) ){
                $daarkorator_details = $params['daarkorator_details'];

                unset($params['daarkorator_details']);
                $is_daarkorator = true;
            }
            if(array_key_exists("confirm_password", $params) ){
                unset($params['confirm_password']);
                $is_daarkorator = false;
            }
            if($user_type!=null)
                $params['user_type'] = $user_type;
            if($status!=null)
                $params['status'] = $status;

            $result = $this->getInsertSting($params);
            $rows   = $result['rows'];
            $values = '"'.$result['values'].'"';
            $db->insert($user_table,$values,$rows);
            $id = $db->getInsertId();
            if(!$id) {
                return false;
            }

            $daarkorator_details["user_id"] = $id ;
            if($is_daarkorator) {
                $result_d = $this->getInsertSting($daarkorator_details );
                $values_d = '"'.$result_d['values'].'"';
                if(!$db->insert("daarkorator_details",$values_d,$result_d['rows'])) {
                    return false;
                }
            }
            return $id;

        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }
    }

    private function getInsertSting($request) {
        $rows           = [];
        $insert_values  = [];
        $result         = [];
        $inc            = 0;

        try {
            foreach ($request as $key => $value) {
                $rows[$inc]          =  $key;
                $insert_values[$inc] =  $value;
                $inc++;
            }

            $result['rows']     =  implode(",", $rows);
            $result['values']   =  implode('","', $insert_values);

            return $result;

        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getUser($id=null) {
        try {
            $db = new database();
            $table = 'user u left join daarkorator_details du on u.id = du.user_id join user_type ut on u.user_type = ut.id';
            $rows = 'u.id, u.user_type, u.first_name ,u.last_name, u.email, u.user_image, u.contact_number, u.status ,du.company_name, du.about, du.tranings, du.tools, du.instagrame, du.website, ut.id as type_id, ut.type_name, case u.status WHEN 0 then "Pending" WHEN 1 then "Active" when 2 then "Inactive" when 5 then "Pending Approval"  END AS status_title';
            $where = ' u.status<>3';

            if($id!=null)
                $where = $where." and u.id=".$id;

            $db->selectJson($table, $rows, $where, '', '');
            $users = $db->getJson();

            if(!$users || $users == "") {
                return false;
            }
            return json_decode($users);

        }catch(Exception $e) {
            $this->callErrorLog($e);
            return false;
        }
    }

    public function deleteUser($user_id) {
        try{
            $db = new database();
            $table  = 'user';
            $rows   =  array("status" => 3);
            $where  = "id=".$user_id;
            if($db->update($table,$rows,$where)) {
                return true;
            }else{
                return false;
            }
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function updateUser($params, $id) {
        try{
            $db           = new database();
            $user_table   = "user";
            $daarkorator_details = [];
            $is_daarkorator = false;
            $where = "id=".$id;

            if(array_key_exists("daarkorator_details", $params)  ){
                $daarkorator_details = $params['daarkorator_details'];

                unset($params['daarkorator_details']);
                $is_daarkorator = true;
            }
            if(array_key_exists("update_password", $params))
                 unset($params['update_password']);

             //print_r($params);die();

            $db->update($user_table,$params,$where);

            if($is_daarkorator) {
            //print_r('daakor');
                $daarkor_details_table = "daarkorator_details";
                $where_daar = "user_id=".$id;
                if(!$db->update($daarkor_details_table,$daarkorator_details,$where_daar)) {
                    return false;
                }
            }
            return true;

        }catch(Exception $e){
            $this->callErrorLog($e);
        }
    }

    public function checkEmailExist($email){
        try{
            $db = new database();
            $table  = 'user';
            $rows   = 'id, first_name, last_name';
            $where  = 'email="'.$email.'" and status = 1';
            $db->selectJson($table, $rows, $where, '', '');
            $result = $db->getJson();
            if(!$result){
                return false;
            }

            $id = json_decode($result)  ;
            return $id[0];

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function generateResetKey($user_id) {
        try{
            $db = new database();
            $resetkey = md5(uniqid(rand(), true));
            $expiry   = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +1 day'));
            $table    = 'password_reset_table';
            $rows     = 'user_id, reset_key, expiry';
            $values   = '"'.$user_id.'", "'.$resetkey.'", "'.$expiry.'"';

            if(!$db->insert($table,$values,$rows)) {
                return false;
            }
            return $resetkey;
        }catch(Exception $e){
            callErrorLog($e);
            return false;
        }
    }

    public function validate($params, $if_signUp=null){

        if(!array_key_exists("first_name", $params) || strlen(trim($params["first_name"])) <= 0  ){
            return false;
        }
        // if(!array_key_exists("last_name", $params) || strlen(trim($params["last_name"])) <= 0 ){
        //     return false;
        // }
        if(!array_key_exists("email", $params) || strlen(trim($params["email"])) <= 0 ){
            return false;
        }
        if($if_signUp!=null) {
            if(!array_key_exists("password", $params) || strlen(trim($params["password"])) <= 0  ){
                return false;
            }

            if(!array_key_exists("confirm_password", $params) || strlen(trim($params["confirm_password"])) <= 0  ){
                return false;
            }

            if(trim($params["password"]) != trim($params["confirm_password"])) {
                return false;
            }
        }
       return true;
    }

    public function updatePackage($params, $id){
        try{
            $db           = new database();
            $table        = "subscription";
            $where        = "id=".$id;
            if(!$db->update($table,$params,$where)){
                return false;
            }
            return true;

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getRoomList(){
        try{
            $db           = new database();
            $table        = "room_types";
            $rows         = "id, title as displayName";
            $db->selectJson($table, $rows, '', '', '');
            $rooms = $db->getJson();

            return json_decode($rooms);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getRoomImages(){
        try{
            $db           = new database();
            $table        = "resources_table";
            $rows         = "id, image_url as imageUrl";
            $where         = "recource_type = 1";
            $db->selectJson($table, $rows, $where, '', '');
            $rooms = $db->getJson();

            return json_decode($rooms);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }


    public function getColorChoices(){
        try{
            $db           = new database();
            $table        = "resources_table";
            $rows         = "id,title as name, image_url as imageUrl";
            $where         = "recource_type = 2";
            $db->selectJson($table, $rows, $where, '', '');
            $rooms = $db->getJson();

            return json_decode($rooms);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getUserRoles(){
        try{
            $db           = new database();
            $table        = "user_type";
            $rows         = "id,type_name as display_name";
            $db->selectJson($table, $rows, '', '', '');
            $rooms = $db->getJson();

            return json_decode($rooms);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }
    public function createProject($params, $customer_id, $draft=null) {
        try{
            $db            = new database();
            $project_table = 'project';

            $status = 0;
            if($draft && $draft != null)
                $status = 0;

            if(isset($params['save_project']) && $params['save_project'] == true)
                $status = 0;

            $rows          = 'customer_id, status';
            $values        = $customer_id.', '.$status;

            if(!isset($params['roomDetails'])) {
                return false;
            }

            $db->insert($project_table,$values,$rows);

            if($project_id = $db->getInsertId()) {

            //print_r('start');

                $insert_params['title']          = $params['roomDetails']['projectName'];
                $insert_params['room_types']     = $params['room'];
                $insert_params['desing_styles']  = json_encode($params['designStyle']);
                $insert_params['color_palettes'] = json_encode($params['colorChoice']['likeColors']);
                $insert_params['color_exceptions'] = $params['colorChoice']['dislikeColors'];
                $insert_params['dimensions']     = json_encode( array(
                    "length" => $params['roomDetails']['length'],
                    "width"  => $params['roomDetails']['width'],
                    "height" => $params['roomDetails']['height'],
                    "unit"   => $params['roomDetails']['unit']
                ));
                $insert_params['description']        = $params['inspirations']['description'];
                $insert_params['social_media_links'] = json_encode($params['inspirations']['urls']);
                $insert_params['budget']        = $params['roomDetails']['budget'];
                $result = $this->getInsertSting($insert_params);

                $rows_detials   = $result['rows'];
                $rows_detials   = $rows_detials.', project_id';
                $values_details = '"'.$result['values'].'"';
                $project_table  = "project_details";

                $values_details = "'".$insert_params['title'] ."',".
                                "'".$insert_params['room_types'] ."',".
                                "'".$insert_params['desing_styles'] ."',".
                                "'".$insert_params['color_palettes'] ."',".
                                "'".$insert_params['color_exceptions'] ."',".
                                "'".$insert_params['dimensions'] ."',".
                                "'".$insert_params['description'] ."',".
                                "'".$insert_params['social_media_links'] ."',".
                                "'".$insert_params['budget'] ."', ".
                                "'".$project_id."'";

                //print_r( $insert_params);
                //print_r($params['roomDetails']['budget']);

                 //echo "insert into project_details (".$rows_detials." ) values (".$values_details.")";

                if($db->insert($project_table,$values_details,$rows_detials)) {
                    $id =  $db->getInsertId();
                    return $project_id;
                }

                return false;
            }
        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getPasswordChangeUser($changeRequestCode){
        try{

            $db = new database();
            $table = "password_reset_table";
            $rows = "user_id as id, expiry, status";
            $where = "reset_key = '".$changeRequestCode."'";
            $db->select($table, $rows, $where, '', '');
            $user = $db->getResults();
            return $user;
        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getPackage($pkg_id){
        try{

            $db = new database();
            $table = "subscription";
            $rows = "price";
            $where = " id = '".$pkg_id."'";

            $db->select($table, $rows, $where, '', '');
            $result = $db->getResults();

            if(!$result){
                return false;
            }

            return $result;
        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function payment($params , $user_id, $transaction_id){
      if(empty($params['payment_method_nonce'])){
          return false;
      }


      $result = Braintree_Transaction::sale([
        'amount' => $params['amount'],
        //'orderId' => 'xyz1245',
        'orderId' => $transaction_id,
        //'merchantAccountId' => 'w3hzrzq84x6f2dmy',
        'paymentMethodNonce' => $params['payment_method_nonce'],
        'customer' => [
          'firstName' => $params['first_name'],
          'lastName' => $params['last_name'],
          'phone' => $params['phone']
        ],
        'billing' => [
          'firstName' => $params['first_name'],
          'lastName' => $params['last_name'],
          'streetAddress' => $params['street_address'],
          'extendedAddress' => $params['address_line_2'],
          'locality' => $params['city'],
          'region' => $params['state_province_region'],
          'postalCode' => $params['zip_code']
        ],
        'options' => [
          'submitForSettlement' => true
        ]
      ]);

      if($result->success === true){

        //print_r($result);
        //return $result;
        try{
            $db           = new database();
            $table        = "payment_table";
            $rows         = "user_id,
                            project_id, amount,
                            transaction_id,
                            first_name,
                            last_name,
                            phone,
                            street_address,
                            address_line_2,
                            city,
                            state_province_region,
                            zip_code,
                            country,
                            payment_method,
                            payment_status";

            $phone = (!isset($params['phone']) || $params['phone'] == "")? "" : $params['phone'] ;
            $street_address = (!isset($params['street_address']) || $params['street_address'] == "")? "" : $params['street_address'] ;
            $address_line_2 = (!isset($params['address_line_2']) || $params['address_line_2'] == "")? "" : $params['address_line_2'] ;
            $state_province_region = (!isset($params['state_province_region']) || $params['state_province_region'] == "")? "" : $params['state_province_region'] ;
            $zip_code = (!isset($params['zip_code']) || $params['zip_code'] == "")? "" : $params['zip_code'] ;
            $city = (!isset($params['city']) || $params['city'] == "")? "" : $params['city'] ;
            $country = (!isset($params['country']) || $params['country'] == "")? "" : $params['country'] ;

            $values       =  "'".$user_id."',
                             '".$params['project_id']."',
                             '".$params['amount']."',
                             '".$transaction_id."',
                             '".$params['first_name']."',
                             '".$params['last_name']."',
                             '".$phone."',
                             '".$street_address."',
                             '".$address_line_2."',
                             '".$city."',
                             '".$state_province_region."',
                             '".$zip_code."',
                             '".$country."',
                             '".$params['payment_method']."',
                              '1'";

            if(!$db->insert($table,$values,$rows)){
                return false;
            }

            $db = new database();
            $table        = "project";
            $rows = array('status' => 1);
            $where = 'id = '  .$params['project_id'];
            $db->update($table,$rows,$where);


            //print_r($response);
            return $result;

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
        //return $result;
      }else{
        //print_r($result->errors);
        return false;
        die();
      }
    }

    public function saveImageName($project_id,$generatedFileNAme,$type){
        try{
            $db           = new database();
            $rows         = "image_url, title, project_id, recource_type";
            $table        = "resources_table";
            $values       =  "'".$generatedFileNAme."', '".$generatedFileNAme."', ".$project_id.", ".$type;

            if(!$db->insert($table,$values,$rows)){
                return false;
            }

            return true;

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function chekOldPassword($oldPassword, $user_id){
        try{
            $db = new database();
            $table = "user";
            $rows = "password";
            $where = " id = '".$user_id."'";

            $db->select($table, $rows, $where);
            $result = $db->getResults();

            if($result['password'] != $oldPassword) {
                return false;
            }

            return true;
        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getProjects($user_id=null, $logged_user_type=null, $limit=null, $status=null, $bidding=null){
        try {
            $db           = new database();

            if($bidding == "yes"){

                $table      = "project p join project_details pd on p.id = pd.project_id join room_types rt on pd.room_types = rt.id";

                $rows       = "p.*, DATE_FORMAT(p.published_date, '%Y-%m-%d') as published_date , rt.image, rt.title room_type, pd.budget as budget, pd.title";

                $where      = "p.id NOT IN (select project_id from daakor_project where daakor_id = ".$user_id.") and status = 1";
            }else{
                if($logged_user_type == 3){

                    $table      = "project p join daakor_project dp on p.id = dp.project_id join project_details pd on p.id = pd.project_id join room_types rt on pd.room_types = rt.id";

                    $rows       = "p.*, DATE_FORMAT(p.published_date, '%Y-%m-%d') as published_date, rt.image, rt.title as room_type, pd.budget as budget, pd.title,
                                    case when(p.status = 0) then 'Draft'
                                    	when (p.status = 1) then 'In Progress'
                                    	when (p.status = 2) then 'Winner Selected'
                                    	when (p.status = 3 and p.won_by = ".$user_id.") then 'Won'
                                    	when (p.status = 3 and p.won_by != ".$user_id.") then 'Completed'
                                    	when (p.status = 4) then 'Cancelled'
                                    	END as status_title";

                    $where      = "dp.daakor_id=".$user_id." and p.status <> 0";

                }elseif($logged_user_type == 2){

                    $table      = "project p join project_details pd on p.id = pd.project_id join room_types rt on pd.room_types = rt.id";

                    $rows       = "p.*, DATE_FORMAT(p.published_date, '%Y-%m-%d') as published_date , rt.image, rt.title room_type, pd.budget as budget, pd.title, case p.status WHEN 0 then 'Draft' WHEN 1 then 'In Progress' WHEN 2 then 'Winner Selected' WHEN 3 then 'Completed' WHEN 4 then 'Cancelled' END AS status_title";

                    $where      = "p.customer_id =".$user_id;
                }else{

                    $table      = "project p join project_details pd on p.id = pd.project_id join room_types rt on pd.room_types = rt.id";
                    $rows       = "p.*, DATE_FORMAT(p.published_date, '%Y-%m-%d') as published_date , rt.image, rt.title room_type, pd.budget as budget,
                                    case p.status WHEN 0 then 'Draft' WHEN 1 then 'In Progress' WHEN 2 then 'Winner Selected' WHEN 3 then 'Completed' WHEN 4 then 'Cancelled' END AS status_title, pd.title";
                    $where      = "";
                }
            }

            $order = "published_date DESC";
            $db->selectJson($table, $rows, $where,  $order, '', $limit);
            $projects = $db->getJson();

            return json_decode($projects);

        } catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getNotificationCount($user_id=null){
        try{
            $db           = new database();
            $table        = "notifications";
            $rows         = "*";
            $where        = "user_id =".$user_id." AND status = 0";

            $db->selectJson($table, $rows, $where);
            $count = $db->getNumRows();

            return $count;

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getNotifications($user_id, $limit=null, $status=null){
        try{
            $db           = new database();
            $table        = "notifications";
            $rows         = "id, notification_text, url, notification_type, status as status, case

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) < 2 then 'Today'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) < 3 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) >= 2 then '2 Days ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) < 4 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) >= 3 then '3 Days ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) < 5 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) >= 4 then '4 Days ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) <= 6 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) >= 5 then '5 Days ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) >= 7 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) <= 14 then '1 week ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) >= 15 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) <= 21 then '2 weeks ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) > 22 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) <= 28 then '3 weeks ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) > 28 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) <= 60 then '1 Month ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) > 61 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) <= 90 then '2 Months ago'

                                                                                                          when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) > 91 and TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) < 120 then '3 Months ago'

                                                               when TIMESTAMPDIFF(DAY, datetime, CURRENT_TIMESTAMP) > 120 then 'more than 3 Months ago'

                                                                                                          end as duration";
            $where        = "user_id = ".$user_id;
            if(isset($status))
            $where .= " AND status = ".$status;

            $db->selectJson($table, $rows, $where, 'datetime DESC', '', $limit);
            $results = $db->getJson();

            return json_decode($results);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function createNotification($values=null, $multivalue=null ){
        try{
            $db           = new database();
            $table        = "notifications";
            $rows         = "user_id, notification_text, url, notification_type";
            $mv = "true";
            if($multivalue == null) {
                $mv = null;
            }

            if($db->insert($table, $values, $rows, $mv)){
                return  true;
            }else{
                return false;
            }

        }catch(Exception $e){
             $this->callErrorLog($e);
             return false;
        }
    }

    private function getUsers($type=null, $status=null){
        try{
            $db           = new database();
            $table        = "user";
            $rows         = "id, email, first_name";
            $where        = "";
            if(isset($type))
            $where .= "user_type = ".$type;

            if(isset($status))
            $where .= " AND status = ".$status;

            $db->selectJson($table, $rows, $where);
            $results      = $db->getJson();
            if($results){
                return  json_decode($results);
            }
        }catch(Exception $e){
             $this->callErrorLog($e);
             return false;
        }
    }

    public function getProjectDetails($project_id, $logged_user_type=null, $user_id=null){
        try{

            $db         = new database();
            $table      = "project p inner join project_details pd on pd.project_id = p.id";
            $table     .= " left outer join resources_table rt on rt.project_id = p.id";
            //$table     .= " left outer join project_styleboard psb on psb.project_id = p.id";
            $table     .= " left outer join room_types rty on rty.id = pd.room_types";
            $rows       = " p.id,	p.status as project_status,";
            $rows      .= "MAX(pd.title) as title, MAX(pd.room_types) as room_types, MAX(rty.title) as room_type_name, MAX(pd.desing_styles) as design_styles, MAX(pd.color_palettes) as color_palettes, MAX(pd.color_exceptions) as color_excemption, MAX(pd.dimensions) as dimensions, MAX(pd.description) as description, MAX(pd.social_media_links) as social_media_links, MAX(pd.budget) as budget, ";
            $rows      .= "group_concat(if(rt.recource_type = '3', rt.image_url, null)) as room_images, group_concat(if(rt.recource_type = '4', rt.image_url, null)) as furniture_images";
            //$rows      .= "group_concat(distinct psb.styleboard) as style_boards";
            $where      = "p.id = ".$project_id;

            if($logged_user_type == 2)
            $where .= " AND p.customer_id = ".$user_id;

            $groupby    = "p.id";
            $db->selectJson($table, $rows, $where, '', $groupby);
            $results = $db->getJson();

            $table  = "project_styleboard psb left outer join user u on u.id = psb.daarkorator_id";
            $rows   = "psb.styleboard as styleboard, psb.daarkorator_id as daarkorator_id, psb.status as status, psb.added_time as added_time, CONCAT_WS(' ', u.first_name, u.last_name) as daakor";
            $where  = "psb.status <> 3 and project_id = ".$project_id;

            if($logged_user_type == 3)
            $where .= " AND daarkorator_id = ".$user_id;

            $db         = new database();
            $db->selectJson($table, $rows, $where);
            $styleBoards = $db->getJson();

            $tmp = json_decode($results);
            foreach($tmp as $tmp){
                $title              = $tmp->title;
                $room_types         = ($tmp->room_types);
                $room_type_name     = ($tmp->room_type_name);
                $design_styles      = json_decode($tmp->design_styles, true);
                $color_palettes     = json_decode($tmp->color_palettes, true);
                $color_excemption   = $tmp->color_excemption;
                $dimensions         = json_decode($tmp->dimensions);
                $description        = $tmp->description;
                $social_media_links = json_decode($tmp->social_media_links, true);
                $budget             = $tmp->budget;
                $room_images        = explode(',', $tmp->room_images);
                $furniture_images   = explode(',', $tmp->furniture_images);
            }
            $tmpStyles = array();
            foreach($design_styles as $design_styles){
                $db = new database();
                $table = "resources_table";
                $rows = "image_url, title";
                $where = "id = ".$design_styles;
                $db->select($table, $rows, $where);
                array_push($tmpStyles, $db->getResults());
            }
            $tmpPalattes = array();
            foreach($color_palettes as $color_palettes){
                $db = new database();
                $table = "resources_table";
                $rows = "image_url, title";
                $where = "id = ".$color_palettes;
                $db->select($table, $rows, $where);
                array_push($tmpPalattes, $db->getResults());
            }
            //print_r($tmpStyles);
            $response['title'] = $title;
            $response['project_id'] = $project_id;
            $response['status'] = $tmp->project_status;
            $response['about'] = array('room_types'=>$room_types, 'room_type_name'=>$room_type_name, 'design_styles'=>$tmpStyles, 'color_palettes'=>$tmpPalattes, 'color_excemption'=>$color_excemption);
            $response['details'] = array('dimensions'=>$dimensions, 'room_images'=>$room_images, 'budget'=>$budget, 'furniture_images'=>$furniture_images);
            $response['inspire'] = array('social_media_links'=>$social_media_links, 'description'=>$description);
            $response['style_boards'] = json_decode($styleBoards);

            return $response;

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function createNotifications($data){
        try{
            if($data['user_id'] == "" || $data['notification_text'] == ""
                || $data['url'] == "" || $data['notification_type'] == "" ) {

                    return false;
            }
            $db         = new database();
            $table  = "notifications" ;
            $values = "'".$data['user_id']."', '".$data['notification_text']."', '".$data['url']."', '".$data['notification_type']."'";
            $rows   = " user_id, notification_text, url, notification_type ";

            if($db->insert($table, $values, $rows)){
                return  true;
            }else{
                return false;
            }
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getAllDaarkorators(){
        try{
            $db     = new database();
            $table  = "user" ;
            $rows   = "id, email, first_name";
            $where  = "user_type = 3 and status = 1";

            $db->select($table, $rows, $where);
            $daarkos =  $db->getResults();

            if(!$daarkos) {
                return false;
            }

            return $daarkos;

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function createMessage($params, $user_type){
        try{
            $db = new database();

            if($user_type == 2) {
                $tb     = 'project_styleboard ps
                           join  project_details pd on ps.project_id = pd.project_id';
                $rows   = 'ps.project_id,
                            ps.daarkorator_id as reciever_id,
                            CONCAT(pd.title,"-",ps.style_board_name) as message_subject';
            }else {
                $tb     = 'project_styleboard ps
                           join project p on ps.project_id = p.id
                           join user u on p.customer_id = u.id
                           join  project_details pd on ps.project_id = pd.project_id';
                $rows   = 'ps.project_id,
                            u.id as reciever_id,
                            CONCAT(pd.title,"-",ps.style_board_name) as message_subject';
            }

            $where = "ps.id=".$params["styleboard_id"];
            $db->select($tb,$rows,$where);
            $results =  $db->getResults();
            $db = new database();

            $table = "messages";
            $rows  = "project_id, styleboard_id, sender_id, reciever_id, message_subject, message_text";
            $values = '"'.$results["project_id"].'",
                    "'.$params["styleboard_id"].'",
                    "'.$params["sender_id"].'",
                    "'.$results["reciever_id"].'",
                    "'.$results["message_subject"].'",
                    "'.$params["message_text"].'"';

            if($db->insert($table, $values, $rows)){
                $msgUrl = getNotificationUrl("styleboard", $results["project_id"]);
                $values = $results["reciever_id"].", 'New comment on styleboard', '".$msgUrl."', 5";


                $this->createNotification($values, null );
                return  true;
            }else{
                return false;
            }

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function saveStyleBoard($params,$generated_name, $user_id) {
        try{
            $db     = new database();
            $table  = "project_styleboard";
            $rows   = "project_id, styleboard, daarkorator_id, note, style_board_name";
            $note = "";
            if(isset($params['note']))
                $note = $params['note'];

            $values = "'".$params['project_id']."',
                        '".$generated_name."',
                        '".$user_id."',
                        '".$note."',
                        '".$params['style_board_name']."'";

            if($db->insert($table, $values, $rows)){
                return  true;
            }else{
                return false;
            }
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getMessageList($user_id, $limit=null, $status=null){
        try{
            $db     = new database();
            $table  =  'messages m left outer join user u on u.id = m.sender_id';
            $table .= " left outer join project_details pd on pd.project_id = m.project_id";
            $table .= " left outer join project_styleboard psb on psb.project_id = m.project_id";
            $rows   = " m.id as id,
                        pd.title as project_name,
                        CONCAT(u.first_name,' ',last_name ) as sender,
                        u.email as email,
                        m.project_id as project_id,
                        m.message_subject as message_subject,
                        DATE_FORMAT(m.date_time, '%Y-%m-%d') as date_time,
                        psb.style_board_name as styleboard_name,
                        psb.id as styleboard_id,
                        m.status as status";
            $where  = "m.reciever_id = ".$user_id." OR m.sender_id = ".$user_id;

            if(isset($status))
            $where .= " AND m.status = ".$status;



            $db->selectJson($table, $rows, $where);
            $results = json_decode($db->getJson());

            return $results;

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getMessageDetail($user_id, $message_id){
        try{
            $results = $this->messageDetail($message_id);
            return $results;
        }catch(Exception $e){
              $this->callErrorLog($e);
              return false;
        }
    }

    public function messageDetail($styleboard_id, $user_id){
        try{
            $db     = new database();
            $table  = "messages m left outer join user u on u.id = m.sender_id";
            $rows   = "m.id as id,
                               u.first_name as sender,
                               m.message_subject as message_subject,
                               m.message_text as message_text,
                               DATE_FORMAT(m.date_time, '%Y-%m-%d') as date_time,
                               m.status as status,
                               if(m.reciever_id = $user_id, 'received', 'sent') as class";
            $where  = "(m.reciever_id = $user_id or m.sender_id = $user_id) and m.styleboard_id = $styleboard_id";

            $order = 'date_time ASC';

            $db->selectJson($table, $rows, $where, $order);
            $results = $db->getJson();

            return json_decode($results);

        }catch(Exception $e){
             $this->callErrorLog($e);
             return false;
        }
    }


    private function getHistory($message_id, $styleboard_id){
        $db = new database();
        $table = "messages";
        $rows = "*";
        $order = "date_time desc";
        $where = "id < ".$message_id." AND styleboard_id = ".$styleboard_id;
        $db->select($table, $rows, $where, $order);
        $results = $db->getResults();
        return $results;
    }

    public function getAllStyleboards($id=null, $project_id=null, $user_id=null) {
        try{
            $db = new database();
            $table = "project_styleboard sb
            join project p
            on sb.project_id = p.id
            join user u
            on sb.daarkorator_id = u.id";
            $rows = "sb.*, DATE_FORMAT(sb.added_time, '%Y-%m-%d')  as added_time,u.first_name as daarkorator_name" ;
            $order = "added_time desc";
            $where = "";

            $user = $this->getUser($user_id);
            $userType = $user[0]->user_type;

            if($project_id != null)
                $where = "project_id =".$project_id;


            if($userType == 3) {
                if($project_id != null)
                    $where = $where." and ";

                $where = $where."  sb.daarkorator_id=".$user_id;
            }
            if($userType == 2) {
                if($project_id != null)
                    $where = $where." and ";

                $where = $where."  p.customer_id=".$user_id;
            }

            if($id != null) {
                if($project_id != null)
                    $where = $where." and ";

                $where = $where." and sb.status <> 3 and sb.id=".$id;

                $db->select($table, $rows, $where, $order);
                $results = $db->getResults();
                return $results;
            }


            $where = $where." and sb.status <> 3 ";

            $db->selectJson($table, $rows, $where, $order);
            $results = $db->getJson();

            return json_decode($results);
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
       }
    }

    public function getCustomerByProject($project_id) {
        try{
            $db = new database();
            $table = "project";
            $rows = "customer_id" ;
            $where = "id =".$project_id;

            $db->select($table, $rows, $where);
            $results = $db->getResults();
            return $results;

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
       }
    }

    public function addToMyProjects($project_id, $daakor_id) {
        try {
            $db = new database();
            $table = "daakor_project";
            $rows  = "project_id, daakor_id";
            $values = $project_id.", ".$daakor_id;

            $result = $db->insert($table,$values,$rows);

            if(!$result) {
                return false;
            }
            return true;
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
       }
    }

    public function checkProjectExcist($project_id, $user_id) {
        try {
            $db = new database();
            $table = "daakor_project";
            $rows  = "id";
            $where = "project_id=".$project_id." and daakor_id =".$user_id;

            $db->select($table, $rows, $where);
            $result = $db->getResults();

            if(!$result) {
                return false;
            }
            return true;
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
       }
    }

    public function updateProject($updateArr, $project_id) {
        try {
            $db = new database();
            $table = "project";
            $where = " id = ".$project_id;

            if(!$db->update($table,$updateArr,$where)) {
                return false;
            }

            return true;
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function checkProjectStatus($id) {
        try {
            $db = new database();
            $table = "project";
            $rows  = "*";
            $where = " id = ".$id;

            $db->select($table,$rows,$where);
            $results = $db->getResults();

            if($results['status'] > 2) {
                return false;
            }
            return  true;
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function updateResetKeyStates($activtionKey) {
        try{
            $db = new database();
            $table = "password_reset_table";
            $rows = array("status" => 1);
            $where = "reset_key='".$activtionKey."'";
            if(!$db->update($table,$rows,$where)) {
                return false;
            }

            return true;
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function updateNotifications($id) {
        try{
            $db = new database();
            $table = "notifications";
            $rows = array('status' => 1);
            $where = 'id = '.$id;

            $result = $db->update($table,$rows,$where);

            return $result;
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function deleteStyleboard($id) {
        try{
            $db = new database();
            $table = "project_styleboard";
            $rows = array('status' => 3);
            $where = 'id = '.$id;

            $result = $db->update($table,$rows,$where);

            return $result;
        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function updateStyleboard($id, $status) {
        try{
            $db = new database();
            $table = "project_styleboard";
            $rows = array('status' => $status);
            $where = 'id = '.$id;

            $result = $db->update($table,$rows,$where);

            // return $result;
            return true;
        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function updateProjectDetails($params, $project_id) {
        try{
            $insert_params['title']          = $params['roomDetails']['projectName'];
            $insert_params['room_types']     = $params['room'];
            $insert_params['desing_styles']  = json_encode($params['designStyle']);
            $insert_params['color_palettes'] = json_encode($params['colorChoice']['likeColors']);
            $insert_params['color_exceptions'] = $params['colorChoice']['dislikeColors'];
            $insert_params['dimensions']     = json_encode( array(
                'length' => $params['roomDetails']['length'],
                'width'  => $params['roomDetails']['width'],
                'height' => $params['roomDetails']['height'],
                'unit'   => $params['roomDetails']['unit']
            ));
            $insert_params['description']        = $params['inspirations']['description'];
            $insert_params['social_media_links'] = json_encode($params['inspirations']['urls']);
            $insert_params['budget']        = $params['roomDetails']['budget'];
            $insert_params['last_updated_time'] = date('Y-m-d H:m:s');



            if(isset($params['roomDetails']['removed_room_images']) && !empty($params['roomDetails']['removed_room_images']) ) {

                foreach($params['roomDetails']['removed_room_images'] as $image) {
                    $db = new database();
                    try{
                        $table     = "resources_table";
                        $where     = 'image_url="'.$image.'"';
                        $db->delete($table,$where);

                    }catch (Exception $e){
                        $this->callErrorLog($e);
                        return false;
                    }
                }
            }

            if(isset($params['roomDetails']['removed_furniture_images']) && !empty($params['roomDetails']['removed_furniture_images']) ) {

                foreach($params['roomDetails']['removed_furniture_images'] as $image) {
                    $db = new database();
                    try{
                        $table     = "resources_table";
                        $where     = 'image_url="'.$image.'"';
                        $db->delete($table,$where);

                    }catch (Exception $e){
                        $this->callErrorLog($e);
                        return false;
                    }
                }
            }


            $db = new database();
            $where = " project_id=".$project_id;
            $table = "project_details";
            if($db->update($table,$insert_params,$where)) {
                return true;
            }
            return false;

        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function selectStyleboard($project_id, $styleboard_id) {
        try {

            $db = new database();
            $table = "project_styleboard";
            $rows  = "status";
            $where = " project_id=".$project_id. " and  id =".$styleboard_id;

            $db->select($table,$rows,$where);
            $results = $db->getResults();

            if(!$results) {
                return 0;
            }

            if($results['status'] == 1) {
                return 1;
            }

            if($results['status'] == 3) {
                return 3;
            }

            $db = new database();
            $where = " project_id=".$project_id. " and id =".$styleboard_id;
            $rows  = array("status" => 1);
            $table = "project_styleboard";
            if($db->update($table,$rows,$where)) {
                return 2;
            }
            return 0;

        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

     public function saveDeliverableFile($project_id,$generatedFileNAme, $title, $type){
        try{
            $db           = new database();
            $rows         = "project_id, deliverable_url, title, deliverable_type";
            $table        = "deliverables";
            $where        = "project_id = ".$project_id." and deliverable_type = ".$type;

            $db->select($table , '*',  $where);

            if($db->getResults()) {
                $db   = new database();

                $rows = array(
                        'deliverable_url' => $generatedFileNAme,
                        'title' => $title);

                if($db->update($table,$rows,$where))
                    return true;
                else
                    return false;
            }

            $values       =  "'".$project_id."', '".$generatedFileNAme."', '".$title."', ".$type;

            if(!$db->insert($table,$values,$rows)){
                return false;
            }

            return true;;

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getDeliverables($project_id) {
        try {
            $db           = new database();
            $rows         = "*";
            $table        = "deliverables";
            $where        = "project_id = ".$project_id;

            $db->selectJson($table,$rows,$where);
            $results = $db->getJson();

            return json_decode($results);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    public function getDaakorByStyleboard($styleboard_id) {
        $db           = new database();
        $rows         = "daarkorator_id";
        $table        = "project_styleboard";
        $where        = "id = ".$styleboard_id;

        $db->select($table,$rows,$where);
        $results = $db->getResults();

        return $results['daarkorator_id'];
    }

    public function createExternalMessage($tmp) {
        try{
            $db = new database();
            $tb = 'project_details';
            $rows = 'title';
            $where = "project_id=".$tmp["project_id"];
            $db->select($tb,$rows,$where);
            $results =  $db->getResults();

            $db = new database();
            $table = "messages";
            $rows  = "project_id, message_reff, sender_id, reciever_id, message_subject, message_text";
            $values = '"'.$tmp["project_id"].'",
                    "'.$tmp["message_reff"].'",
                    "'.$tmp["sender_id"].'",
                    "'.$tmp["reciever_id"].'",
                    "'.$results["title"].'",
                    "'.$tmp["message_text"].'"';

            if($db->insert($table, $values, $rows)){
                $msgUrl = getNotificationUrl("styleboard", $tmp["project_id"]);
                $values = $tmp["reciever_id"].", '".getNotificationText('message', $results["title"])."', '".$msgUrl."', 4";

                $this->createNotification($values, null );
                return  true;
            }else{
                return false;
            }

        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function getExternalMessage ($project_id, $user_id, $user_type) {

        $db = new database();
        $table = "messages m inner join user u on u.id = m.reciever_id inner join user uu on uu.id = m.sender_id";
        $rows  = ' m.id, m.message_reff, m.project_id, m.sender_id, m.message_subject, m.message_text, DATE_FORMAT(m.date_time, "%b %d, %Y") as date_time, if(m.sender_id = '.$user_id.', u.first_name, uu.first_name ) as sender, if(m.sender_id = '.$user_id.', m.reciever_id, m.sender_id ) as sender_id';

        $where = 'message_reff = 1 and project_id = '.$project_id.' and (reciever_id = '.$user_id.' or sender_id = '.$user_id.')';
        $where .= ' group by sender  order by date_time desc';

        $db->selectJson($table,$rows,$where);
        $results = $db->getJson();
        return json_decode($results);
    }

    public function getExternalMessageConversation($project_id, $sender_id, $user_id, $user_type) {
        $db = new database();
        $table = "messages";
        $rows  = "id,
                    message_reff,
                    project_id,
                    sender_id,
                    message_subject,
                    message_text,
                    DATE_FORMAT(date_time, '%Y-%m-%d') as date_time,
                    if(reciever_id = $user_id, 'received', 'sent') as class";

            //$where = '(message_reff = 1 and project_id = '.$project_id.') and ((sender_id = '.$sender_id.' and reciever_id = '.$user_id.') or (sender_id = '.$user_id.' or reciever_id = '.$sender_id.'))';
        $where = '(message_reff = 1 and project_id = '.$project_id.') and (sender_id in ('.$sender_id.', '.$user_id.') and reciever_id in ('.$sender_id.','.$user_id.'))';

        $db->selectJson($table,$rows,$where);
        $results = $db->getJson();

        return json_decode($results);
    }

    public function getDaakorList($projectid){
        //return $projectid;
        $db = new database();
        $table = "daakor_project dp INNER JOIN user u on u.id = dp.daakor_id";
        $rows = "dp.daakor_id as id, u.first_name as name";
        $where = "project_id = ".$projectid;

        $db->selectJson($table, $rows, $where);
        $results = $db->getJson();

        return json_decode($results);
    }

    public function daarkoratorsOnProject($project_id, $selected_daakor=null) {
        $db = new database();
        $table = "daakor_project";
        $rows  = "daakor_id";

        if($selected_daakor){
            $where = "project_id = ".$project_id." and daakor_id <> ".$selected_daakor;
        }else{
            $where = "project_id = ".$project_id;
            $table = 'daakor_project';
        }

        $db->selectJson($table, $rows, $where);
        $results = $db->getJson();
        $results = json_decode($results);
        return ($results)? $results: false;
    }

    public function getAcceptedDaarkor($project_id) {
        $db = new database();
        $table = "project_styleboard";
        $rows  = "daarkorator_id";
        $where = "project_id = ".$project_id." and status =1 ";

        $db->select($table, $rows, $where);
        $results = $db->getResults();

        return ($results)?$results['daarkorator_id']:false;
    }

    public function getTaxCalc($params){
      if(!empty($params)){
        //print_r('test');
        $client = TaxJar\Client::withApiKey("5059d662551e912575fe03c77deefd87");
        try{
          $rates = $client->ratesForLocation($params['zip_code'], [
            'city' => $params['city'],
            'state' => $params['state_province_region'],
            'country' => $params['country']
          ]);

          if($rates){
            $tax = $params['amount'] * $rates->combined_rate;
            $total = $params['amount'] + ($params['amount'] * $rates->combined_rate);
            $response = array();
            $response['tax'] = $tax;
            $response['total'] = $total;
            return $response;
          }else{
            return false;
          }
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
      }else{
        return false;
      }

    }
}

?>
