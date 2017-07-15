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
            $table       = "authentication_table";
            $rows        = 'user_id,   expiration';
            $where       = 'access_token= "' . $user_accessToken .'"';

            $db->select($table, $rows, $where, '', '');
            $access = $db->getResults();
            if(!$access){
                return false;
            } 

            $this->updateAccesstokenExpiry($user_accessToken);

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

            return $features;
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

        return $logged_User;
    }

    private function callErrorLog($e){
         error_log($e->getMessage(). "\n", 3, "./error.log");

    }

}

?>
