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


$login = $_POST['login'];
$password = $_POST['password'];

//Only check if someone has submitted a username or a password
if ($login || $password) {
   
   /*Regex formats for emails and passwords. Because these formats were used when the
   username and password was created, the same format should also be used when entering
   these values into the fields. This will add an additional step in preventing 
   malicious users from performing an SQL injection*/
   $verify_login = "[a-zA-Z0-9_]{6,12}";
   $verify_password = "^(?=.{6,12})(?=.*[A-Za-z])(?=.*\d)[a-zA-Z0-9_!]+$";
   
   //Make sure both exist and the email is valid 
   if (!($login )) {
       $error_message = "Please enter your username.";
   }
   
   else if (!($password)) {
       $error_message = "Please enter your password.";
   }
   
   /* Check for proper formats for all inputs. */
   else if (!(valid_input($login, $verify_login)) || !(valid_input($password, $verify_password))) {
       $error_message = "Please enter a valid username or password.";
   }
   
   else {
      //Now, check the database for a correct login
      $command = "SELECT ml.member_id, ml.login, mi.email FROM member_login ml " . 
                 "INNER JOIN member_info mi ON mi.member_id = ml.member_id " . 
                 "WHERE ml.login = '".$db->real_escape_string($login)."' ". 
                 "AND ml.password = password('".$db->real_escape_string($password)."');";
      $result = $db->query($command);
      
      if ($data = $result->fetch_object()) {
      
         //Correct login. Set session variables
         $_SESSION['member_id'] = $data->member_id;
         $_SESSION['member_login'] = $data->login;
         $_SESSION['email'] = $data->email;

         header("Location: profile.php");
      }
      
      else {
         //Incorrect username or password.
         $error_message = "Sorry, your login was incorrect.  Please contact us if you've forgotten your password.";
     
      }
   }
}


//Include footer
include("../erikslist_include/erikslist_header.inc");

if (!$member_id) {

//If not logged in already, display a form requesting username and password
?>
    <div class="login_left">
    <h4>Please sign in or register to post to erik's list</h4>
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
         Username:
      </td>
      <td align="left">
         <input type="text" size="24" maxlength="12" name="login" value="<? echo $_POST['login']; ?>">
      </td>
   </tr>

   <tr>
     <td align="right">
        Password:
     </td>
     <td align="left">
        <input type="password" size="24" maxlength="12" name="password" value="">
     </td>
   </tr>

   <tr>
     <td colspan="2">&nbsp;</td>
   </tr>

   <tr>
     <td>&nbsp;</td>
     <td align="center">
       <input type="submit" value="Login">
    </td>
   </tr>
   
    <tr>
     <td colspan="2">&nbsp;</td>
   </tr>
   
   <tr>
     <td>&nbsp;</td>
     <td align="center">Forget your <a href="reset.php">password?</a></td>
   </tr>
</table>
</form>
 
</div>
<br>

  <div class="login_right">
  <table align="center">
   <tr>
     <td align="center" width="150">
       Not a member?  
     </td>
   </tr>
   
   <tr>
     <td>&nbsp;</td>
   </tr>
   
   <tr>
     <td align="center">
       <a class="button" href="register.php">Register</a>
     </td>
     </tr>
    </table>
  </div>
<br>
<br>


<?
}

else {
  echo "<div class='login_left'>";
  echo "<h3>You are currently signed in as " .  $_SESSION['member_login'] . "</h3>";
  echo "<h4><a href='profile.php'>Click here</a> to go your account, " . 
          "or <a href='logout.php'>Click here</a> to log out</h4>";
  echo "</div>";
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

echo $_SESSION['redirected'];

*/

$db->close();

?>