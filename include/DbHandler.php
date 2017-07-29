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
            if($user_type!=null)
                $params['user_type'] = $user_type;

            $result = $this->getInsertSting($params);
            $rows   = $result['rows'];
            $values = '"'.$result['values'].'"';

            $id = $db->insert($user_table,$values,$rows);
            if(!$id) {
                return false;
            }

            $daarkorator_details["user_id"] = $id;
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

    public function usersBytype() {
        try {
            $db = new database();
            $table = 'user u left join daarkorator_details du on u.id = du.user_id';
            $rows = 'u.id, u.first_name ,u.last_name, u.email, u.user_image, u.contact_number, du.company_name, du.about, du.tranings, du.tools, du.instagrame, du.website ';
            $where = ' u.status=1';

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
            $rows   =  array('status' => 0);
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
            unset($params['email']);
            unset($params['id']);

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
        }
       return true;
    }
}

?>
