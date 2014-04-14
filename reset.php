<?php

//Include utility files and public API
require("../erikslist_include/erikslist_utilities.inc");
include("erikslist_public.inc");

$db = member_db_connect();

//Start the session cookie
session_start();
$member_id = $_SESSION['member_id'];
$member_login = $_SESSION['member_login'];
$_SESSION['navigation'] = basename($_SERVER['PHP_SELF']);


##################
#                #
# Post Handling  #
#                #
##################

//Only check if someone has submitted a username or a password
if ($_POST) {
   
$email = trim($_POST['email']);
   
   if (!(filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $error_message = "We're sorry. The email address you entered is not valid.  ";
    }
   
   else {
      //Now, check the database to make sure the email address exists for a correct login
      $command = "SELECT member_id, email, first_name,last_name FROM member_info WHERE email = '".$db->real_escape_string($email)."';";
      $result = $db->query($command);
      
      if ($data = $result->fetch_object()) {
      
         //Generate a temporary password
         $temp = generate_password();
         
         //Set variables for matching member
         $email = $data->email;
         $name = $data->first_name . " " . $data->last_name;
         $member_id = $data->member_id;
         
         //Update password with temporary password
         $command = "UPDATE member_login SET password = password('".$db->real_escape_string($temp)."') " . 
                    "WHERE member_id = '".$db->real_escape_string($member_id)."';";
                    
         $result = $db->query($command);
      
         /*If successful, display a message to the user, and send their temporary password
         to their registered email address */
         if ($result == true) {
               $message = "An temporary password has been sent to " . $email;
               
               /*Call the function "send_new_password", passing the array of data, the location 
               of the email template, the temporary password, and the name of the user */
               send_new_password($_POST, "../erikslist_include/reset_password_template.txt", $temp, $name);
         }
         else {
               $error_message =  "An error has occurred." ;
         }
      }
      
      else {
         //Incorrect username or password.
         $error_message = "Sorry, the email address you entered was not found in our system. <br>" . 
                          "Please contact erikslist.com support personnel for assistance.";
      }
   }
}


##################
#                #
# Begin Content  #
#                #
##################

//Include header
include("../erikslist_include/erikslist_header.inc");

?>
    <div class="register">
    <h3>Please enter the email address you used to register with us:</h3>

<form method="POST" action="">
  <table align="center">
    <tr>
      <td align="right">
         Email:
      </td>
      <td align="left">
         <input type="text" size="24" maxlength="50" name="email" value="<? echo $_POST['email']; ?>">
      </td>
   </tr>



   <tr>
     <td colspan="2">&nbsp;</td>
   </tr>

   <tr>
     <td>&nbsp;</td>
     <td align="center">
       <input type="submit" value="Submit">
    </td>
   </tr>
   
</table>
</form>
 
<br>
     <span style="color:red;font-size:12px;text-align:center;">
     <?
     if ($error_message) {
        echo $error_message;
     }
     ?>
     </span>
     <span style="color:blue;font-size:12px;">
     <?
     if ($message) {
        echo $message;
     }
     ?>
     </span>
 
</div>
<br>

<?



//Include footer
include("../erikslist_include/erikslist_footer.inc");


/*
echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "POST array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
*/

$db->close();

?>