<?php
echo '<!DOCTYPE html>';
echo '<html>';
echo '<head>';
echo '<title></title>';
echo '</head>';
echo '<body>';
echo    '<div style="font-family:Helvetica, Arial, sans-serif; color: #808080">';
echo    '<div style="text-align:center; background: #ffffff url(https://ci5.googleusercontent.com/proxy/JgeBFXWk8-xkjRN1aLvhjJyq0WfTZovDQVJt8acaPhjKuswk3E-Jer0NQ9lR_oIcfMjRVmcGa4hOFqGW5PNK4JeU5Oh612Ty9vzvvuDbda1X0JPwppjKZBpl6-gKiHVq4NoY35k_xVFRoGmiXGhjCenq8c_KpMNVcWhgbqg=s0-d-e1-ft#https://gallery.mailchimp.com/adf52779fc5374a1eff5c5e38/images/ef67b5c5-9743-4bdd-b946-89f89710c8b1.png) no-repeat center/contain; padding:50px; margin-bottom:40px;"></div>';
echo    '<h2 style="text-align: center; font-size:18px; margin-bottom:40px;">Change Password Request </h2>';
echo    '<div style="width:600px; margin:auto;">';
echo    '<p>Hi '.$message_first_name.', </p>';
echo    '<p>You recently made a request to reset your password. Please click the link below to complete the process.</p>';
echo    '<a href="'.$message_text.'" style="color:#00add8">Reset now</a>';
echo    '<p>If you did not perform this request, you can safely ignore this email.</p>';
echo    '<p>Regards,<br/>Your friends at Daakor</p><br/><br/>';
echo    '</div>';
echo    '<div style="background:#333333; color: #fff; font-size: 12px; text-align:center; padding:20px;">';
echo    '<i>Copyright ©  2017 Daakor, All rights reserved.</i><br/><br/>';
echo    '<p>Our mailing address is:<br/>11 St. Joseph Street, Toronto, ON, M4Y 3G4</p><br/>';
echo    '<p>Want to change how you receive these emails?<br/>You can <u>update your preferences</u> or <u>unsubscribe from this list</u>.</p>';
echo    '</div>';
echo    '</div>';
echo '</body>';
echo '</html>';
?>