<?php


	 function setUserAccessToken () {    
			$_SESSION['user_access_token']=md5(uniqid());			
    }    
	
	function verifyUserAccessToken ($user_access_token) {
       if($user_access_token !=null && $_SESSION['user_access_token'] == $user_access_token){	
			return true;	
		} else	{
			$_SESSION['user_access_token']=md5(uniqid());		
			return false;
		}  
    }    

	function destroyUserAccessToken () {		
			destroy($_SESSION['user_access_token']);
		}    
?>
