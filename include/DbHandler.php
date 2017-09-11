<?php

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
        error_log($e->getMessage(). "\n", 3, "./error.log");
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
            $where       = 'access_token= "' . $user_accessToken .'"';
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
            $rows        = 'a.user_id, a.expiration, t.features, t.type';
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

    public function createUser($params, $user_type=null) {
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
            $rows = 'u.id, u.user_type, u.first_name ,u.last_name, u.email, u.user_image, u.contact_number, u.status ,du.company_name, du.about, du.tranings, du.tools, du.instagrame, du.website, ut.id as type_id, ut.type_name, case u.status WHEN 1 then "Active" when 2 then "Inactive"   END AS status_title';
            $where = ' u.status<>3';

            if($id!=null)
                $where = $where." and u.id=".$id;

            $db->selectJson($table, $rows, $where, '', '');
            $users = $db->getJson();
 
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
            $rows   =  array('status' => 3);
            $where  = 'id='.$user_id;
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

            // print_r($params);die();
          
            $db->update($user_table,$params,$where);

            if($is_daarkorator) {
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
        if(!array_key_exists("last_name", $params) || strlen(trim($params["last_name"])) <= 0 ){
            return false;
        }
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

            $status = 1;
            if($draft && $draft != null)
                $status = 2;

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
            $rows = "user_id as id, expiry";
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
        try{
            $db           = new database();
            $rows         = "user_id, project_id, amount, transaction_id, payment_status";
            $table        = "payment_table";
            $values       =  "'".$user_id."', '".$params['project_id']."', '"
                            .$params['amount']."', '".$transaction_id."', '0'";
            if(!$db->insert($table,$values,$rows)){
                return false;
            }
            return true;

        }catch(Exception $e){
             $this->callErrorLog($e);
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
        try{
            $db           = new database();
            $table        = "project";
            $rows         = "*";
            $where        = "";
            if($bidding == "yes"){
                if($logged_user_type == 3 || $logged_user_type == 1){
                    $where .= "status = 1";
                }else{
                    return false;
                }
            }else{
                if($logged_user_type == 3 ){
                    $where = "";
                    if(isset($status))
                    $where .= " status = ".$status;
                }elseif($logged_user_type == 2){
                    $where = "customer_id=".$user_id;
                    if(isset($status))
                        $where .= " AND status = ".$status;
                }else{
                    $where = "";
                    if(isset($status))
                    $where .= " status = ".$status;
                }
            }


            $db->selectJson($table, $rows, $where, '', '', $limit);
            $projects = $db->getJson();

            return json_decode($projects);

        }catch(Exception $e){
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
            $rows         = "id, notification_text, url, notification_type";
            $where        = "user_id = ".$user_id;
            if(isset($status))
            $where .= " AND status = ".$status;

            $db->selectJson($table, $rows, $where, 'datetime', '', $limit);
            $results = $db->getJson();

            return json_decode($results);

        }catch(Exception $e){
             $this->callErrorLog($e);
        }
    }

    private function createNotification($values=null ){
        try{
            $db           = new database();
            $table        = "notifications";
            $rows         = "user_id, notification_text, url, notification_type";

            if($db->insert($table, $values, $rows, "true")){
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
            $rows       = " p.id,";
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
            $where  = "project_id = ".$project_id;

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
            $response['about'] = array('room_types'=>$room_types, 'room_type_name'=>$room_type_name, 'design_styles'=>$tmpStyles, 'color_palettes'=>$tmpPalattes, 'color_excemption'=>$color_excemption);
            $response['details'] = array('dimensions'=>$dimensions, 'room_images'=>$room_images, 'budget'=>$budget, 'furniture_images'=>$furniture_images);
            $response['inspire'] = array('social_media_links'=>$social_media_links, 'description'=>$description);
            $response['style_boards'] = json_decode($styleBoards);

            if($results){
                return $response;
            }else{
                return false;
            }
        }catch(Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }

    public function createMessage($params){
        try{
            $db = new database();
            $table = "messages";
            if(isset($params["message_reff"])){
                $rows  = "project_id, sender_id, reciever_id, message_subject, 	message_text, message_reff";
                            $values = '"'.$params["project_id"].'","'.$params["sender_id"].'","'.$params["reciever_id"].'","'.$params["message_subject"].'","'.$params["message_text"].'","'.$params["message_reff"].'"';
            }else{
                $rows  = "project_id, sender_id, reciever_id, message_subject, 	message_text";
                $values = '"'.$params["project_id"].'","'.$params["sender_id"].'","'.$params["reciever_id"].'","'.$params["message_subject"].'","'.$params["message_text"].'"';
            }

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
        $table  = "messages m left outer join user u on u.id = m.sender_id";
        $table .= " left outer join project_details pd on pd.project_id = m.project_id";
        $rows   = "m.id as id, pd.title as project_name, u.email as sender, m.message_reff as previous_id, m.message_subject as subject, m.date_time as date, m.status as status";
        $where  = "m.reciever_id = ".$user_id;
        if(isset($status))
        $where .= " AND m.status = ".$status;
        $order  = "date_time desc";

        $db->selectJson($table, $rows, $where, $order, '', $limit);
        $results = json_decode($db->getJson());
        if($results){
            return $results;
        }else{
            return false;
        }

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

    private function messageDetail($message_id){
        $db         = new database();
        $table      = "messages";
        $rows       = "*";
        $where      = "id = ".$message_id;
        $db->select($table, $rows, $where);
        $results = $db->getResults();
        if($results['message_reff']!=0){
            $tmp[0] = $results;
            $tmp[1] = $this->messageDetail($results['message_reff']);
            return $tmp;
        }else{
            return $results;
        }
    }
}

?>
