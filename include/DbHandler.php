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
            $accesstoken = md5(uniqid($user_id, true));
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
            $rows        = 'a.user_id, a.expiration, t.features';
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
        $where = 'email= "' . $email . '"';

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

            //$id = $db->insert($user_table,$values,$rows);
            if(!$db->insert($user_table,$values,$rows)) {
                return false;
            }

            $daarkorator_details["user_id"] = $db->getInsertId();
            if($is_daarkorator) {
                $result_d = $this->getInsertSting($daarkorator_details );
                $values_d = '"'.$result_d['values'].'"';
                if(!$db->insert("daarkorator_details",$values_d,$result_d['rows'])) {
                    return false;
                }
            }
            return true;
        
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
            $table = 'user u left join daarkorator_details du on u.id = du.user_id';
            $rows = 'u.id, u.first_name ,u.last_name, u.email, u.user_image, u.contact_number, u.status ,du.company_name, du.about, du.tranings, du.tools, du.instagrame, du.website ';
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

            if(array_key_exists("daarkorator_details", $params) ){
                $daarkorator_details = $params['daarkorator_details'];
                
                unset($params['daarkorator_details']);
                $is_daarkorator = true;
            }
            if(array_key_exists("update_password", $params))
                 unset($params['update_password']);

            $where = "id=".$id;
          
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
            $rows   = 'id';
            $where  = 'email="'.$email.'"';
            $db->selectJson($table, $rows, $where, '', '');
            $result = $db->getJson();
            if(!$result){
                return false; 
            }
                
            $id = json_decode($result)  ;           
            return $id[0]->id ;

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

    public function createProject($params, $customer_id) {
        try{
            $db            = new database();
            $project_table = 'project';
            $rows          = 'customer_id, status';
            $values        = $customer_id.', 1';
            
            if(!isset($params['roomDetails'])) {
                return false;
            }

            $db->insert($project_table,$values,$rows);

            if($project_id = $db->getInsertId()) {
            return $project_id;
                
                $insert_params['title']          = $params['roomDetails']['projectName'];
                $insert_params['room_types']     = json_encode($params['room']);
                $insert_params['desing_styles']  = json_encode($params['designStyle']);
                $insert_params['color_palettes'] = json_encode($params['colorChoice']['likeColors']);
                $insert_params['color_exceptions'] = json_encode($params['colorChoice']['dislikeColors']);
                $insert_params['dimensions']     = json_encode( array(
                    "length" => $params['roomDetails']['length'],
                    "width"  => $params['roomDetails']['width'],
                    "height" => $params['roomDetails']['height'],
                    "unit"   => $params['roomDetails']['unit']  
                ));
                $insert_params['description']        = $params['inspirations']['description'];
                $insert_params['social_media_links'] = json_encode($params['inspirations']['urls']);

                $result = $this->getInsertSting($insert_params); 

                $rows_detials   = $result['rows'];
                $values_details = '"'.$result['values'].'"';
                $project_table  = "project_details";

                $values_details = "'".$insert_params['title'] ."',".
                                "'".$insert_params['room_types'] ."',".
                                "'".$insert_params['desing_styles'] ."',".
                                "'".$insert_params['color_palettes'] ."',".
                                "'".$insert_params['color_exceptions'] ."',".
                                "'".$insert_params['dimensions'] ."',".
                                "'".$insert_params['description'] ."',".
                                "'".$insert_params['social_media_links'] ."'";




                if($db->insert($project_table,$values_details,$rows_detials))
                    return true;

                return false;     
            }
        }catch (Exception $e){
            $this->callErrorLog($e);
            return false;
        }
    }
}

?>
