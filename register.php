<?php

//Include utility files and public API
require("../erikslist_include/erikslist_utilities.inc");
include("erikslist_public.inc");

$db = member_db_connect();

//Start the session cookie
session_start();
$member_id = $_SESSION['member_id'];
$_SESSION['navigation'] = basename($_SERVER['PHP_SELF']);

##################
#                #
# Post Handling  #
#                #
##################

if (count($_POST) > 0) {
	   
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $verify_password = trim($_POST['password2']);
    
    /*Regular expressions for emails and password. Password must includ 6-12 characters, 
    must have both alphanumberic characters AND numbers, and may allow a few special characters
    as well. Email must conform to a standard email format, and any name may contain dashes 
    and apostrophes, but must contain at least some letters. */
    $valid_pass = "^(?=.{6,12})(?=.*[A-Za-z])(?=.*\d)[a-zA-Z\d_!]+$";
    $valid_name = "^(?=.*[A-Za-z])[A-Za-z][A-Za-z-']+$";
    $valid_username = "(?=.*[A-Za-z])[a-zA-Z0-9_]{6,12}";
    
    /*First, ensure that all field are completed*/
    if (!($first_name && $last_name && $email && $username && $password && $verify_password)) {
        $error_message = "Please make sure you've filled in all the form fields. ";
    }
    
    /*Check for proper formats for all inputs*/
    else if (strlen($first_name) > 50 || strlen($last_name) > 50) {
        $error_message = "Please make sure both your first and last names are fewer than 50 characters each.  ";
    }
    
    else if (!(valid_input($first_name, $valid_name)) || !(valid_input($last_name, $valid_name))) {
        $error_message = "Please make sure you enter a valid name, which contains letters, hyphens or apostrophes only.  ";
    }
    
    else if (strlen($email) > 50 || !(filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $error_message = "Please make sure you enter a valid email address, which is less than 50 characters.  ";
    }
    
    else if (strlen($password) > 12 || strlen($password) < 6 || !(valid_input($password, $valid_pass))) {
       $error_message = "Please make sure your password is between 6 and 12 characters, and is a combination of 
                         contains letters and numbers. Special characters allowed include underscores (_) and exclamation marks (!).  ";
    }
    
    /*Makes sure the both passwords match each other*/
    else if (!($password == $verify_password)) {
       $error_message = "Passwords do not match.";
    }
    
    else {
       /*Check the database for an existing member with this email*/
       $command = "SELECT member_id FROM member_info WHERE email = '". $db->real_escape_string($email)."';";
       $result = $db->query($command);
       
       if ($data = $result->fetch_object()) {
          $error_message = "We have found an existing member with that email address.  <br>
                            Please contact us if you have forgotten your password.";
       }
       else  {
              /*Check the database for an existing member with this login id */
         $command = "SELECT member_id FROM member_login WHERE login = '". $db->real_escape_string($username)."';";
         $result = $db->query($command);
       
         if ($data = $result->fetch_object()) {
            $error_message = "This username is already taken.";
         }
       
         else {
       
         //Process the membership once all checks have passed
         $success = true;
         $memberID = '';
         
          //Start the transaction
          $command = "SET AUTOCOMMIT=0";
          $result = $db->query($command);
          $command = "BEGIN";
          $result = $db->query($command);
          
          //First, member login
          $command = "INSERT INTO member_login (member_id, login, password) " . 
                      "VALUES ('', '". $db->real_escape_string($username)."', password('".$db->real_escape_string($password)."'));";
          $result = $db->query($command);
          if ($result == false) {
              $success = false;
          }
          else {
              //Now, member info
             $memberID  = $db->insert_id;
             $command = "INSERT INTO member_info (member_id, email, first_name, last_name, date_enrolled) VALUES 
                        ('".$db->real_escape_string($memberID). "','" .
                            $db->real_escape_string($email). "','".
                            $db->real_escape_string($first_name). "','".
                            $db->real_escape_string($last_name)."', now());";
             $result = $db->query($command);
             if ($result == false) {
                $success = false;
             }  
          }
          
          if (!$success) {
             $command = "ROLLBACK";
             $result = $db->query($command);
             $error_message = "We're sorry, there has been an error on our end. Please try again later.  ";
          }
          else {
             $command = "COMMIT";
             $result = $db->query($command);
            
             //Set session variable
             $_SESSION['member_id'] = $member_id;
          }
          $command = "SET AUTOCOMMIT=1";  //Return to autocommit
          $result = $db->query($command);
          
            //If successful, then redirect
            if ($success) {
            //header("Location: /social/profile.php?profileID=" . $_SESSION['memberID']);
          }
       }
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

if ($_SESSION['member_id']) {
   
     echo "<div class='login_left'>";
     echo "<h3>Welcome " .  $_SESSION['member_login'] . "</h3>";
     echo "<h4><a href='profile.php'>Click here</a> to go your account, " . 
          "or <a href='logout.php'>Click here</a> to log out</h4>";
     echo "</div>";
}
else {

?>
<div class="register">
<h3>Register with erikslist.com</h3>
<p>Please enter the following information:</p>
<span style="color:red;font-size:12px;">
<?
if ($error_message) {
  echo $error_message;
}
?>
</span>


<form method="POST" action="">
  <table>
    <tr>
     <td align="right">
       First Name:
     </td>
     <td align="left">
        <input type="text" size="25" maxlength="25" name="first_name" value="<? echo $_POST['first_name'] ?>">
     </td>
    </tr>

    <tr>
     <td align="right">
      Last Name:
     </td>
     <td align="left">
       <input type="text" size="25" maxlength="25" name="last_name" value="<? echo $_POST['last_name'] ?>">
     </td>
    </tr>
 
    <tr>
      <td align="right">
       Email address:
      </td>
      <td align="left">
       <input type="text" size="25" maxlength="50" name="email" value="<? echo $_POST['email'] ?>">
      </td>
    </tr>

    <tr>
      <td align="right">
       Choose a username:
      </td>
      <td align="left">
       <input type="text" size="25" maxlength="50" name="username" value="<? echo $_POST['username'] ?>">
      </td>
    </tr>


    <tr>
     <td align="right">
       Choose a Password:
     </td>
     <td align="left">
       <input type="password" size="25" maxlength="12" name="password" value="">
     </td>
    </tr>

     <td align="right">
       Please retype your password:
     </td>
     <td align="left">
       <input type="password" size="25" maxlength="12" name="password2" value="">
     </td>
    </tr>

    <tr>
     <td colspan="2">&nbsp;</td>
    </tr>

    <tr>
     <td>&nbsp;</td>
     <td align="left">
       <input type="submit" value="Register">
     </td>
    </tr>
    
     <tr>
     <td colspan="2">&nbsp;</td>
    </tr>
    
    <tr>
     <td colspan="2" align="center">
     <? 
       if ($success) { 
         echo "<span style='color:#191970;'>Registration successful! <a href='login.php'>Click here</a> to login</span>";
         } 
        else {
          echo "Already a member?  <a href='login.php'>Login here</a>";
        } 
         
         ?>
      </td>
    </tr>   
    
    
 </table>
</form>


<br><br>

</div>
<?



}


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