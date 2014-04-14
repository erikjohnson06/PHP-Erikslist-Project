<?php
//erikslist_utilities.inc -- a private library of functions for erikslist

//Create array of menu options for user to categorize items
$menu_array = array("appliances", "electronics", "sporting", "tickets", "wanted", 
                    "music instruments", "automobiles", "other", "free", "books", 
                    "bicycles", "motorcycles", "general", "art", "clothing", "tools", 
                    "furniture", "boats", "jewelry", "collectibles", "lawncare");


//Sort options alphabetically                   
sort($menu_array);

/*returns a database handle which allows select, inserts, and updates to erikslist tables */
function member_db_connect() {

    $host = "localhost";
    $socket = "/users/ejohnson4/mysql-php/data/mysql.sock";      
    $port = 0;
    $user = "el_member";
    $pw = "member123";
    $database = "erikslist";
    
    $db = new mysqli($host,$user,$pw,$database,$port,$socket) 
       or die("Cannot connect to mySQL.");
       
    return $db;
}


/*Ensure that each email in the database is unique. If not, 
do not allow the user to update his/her email address to that email address */
function check_email($member_id, $email, $db) {
     
   $command = "SELECT * from member_info where email = '" . $db->real_escape_string($email) . 
                   "' and member_id != '" . $db->real_escape_string($member_id) . "';"; 

   $result = $db->query($command ); 
                        
   if ($result->num_rows > 0) {
       return false;
   }
   else {
       return true;
   }
}

/*This function can be called upon to verify that the password being entered matches 
the user's current password*/
function verify_password($member_id, $password, $db) {
      
   $command = "SELECT * FROM member_login WHERE member_id = '".$db->real_escape_string($member_id)."' ". 
              "AND password = password('".$db->real_escape_string($password)."');";
   $result = $db->query($command);
   
   if ($data = $result->fetch_object()) {
      return true;
   }
   else {
      return false;
   }
}

//Verify the username is not already taken when updating login information
function check_username($member_id, $username, $db) {
     
   $command = "SELECT * from member_login where login = '" . $db->real_escape_string($username) . 
                   "' and member_id != '" . $db->real_escape_string($member_id) . "';"; 

   $result = $db->query($command); 
                                               
   if ($result->num_rows > 0) {
       return false;
   }
   else {
       return true;
   }
}

/*This function will accept four parameters: the data array
from the user's shopping session, the email template, the 
date timestamp, and order number, and then send an email to the user */
function send_new_password($data_array, $template_file, $temp_password, $name) {
   
       /*Use the email template and replace the strings located within
       with the values from the data_array */  
       $email_message = file_get_contents($template_file);
       $email_message = str_replace("#NAME#", $name, $email_message);
       $email_message = str_replace("#PASSWORD#", $temp_password, $email_message);
 
       //Construct the email headers
       $to = $data_array['email'];
       $from = "no-reply@erikslist.com";
       $email_subject = "No Reply - Temporary Password Request";

       $headers  = "From: " . $from . "\r\n";
       $headers .= 'MIME-Version: 1.0' . "\n"; 
       $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";   
   
       //Send a confirmation email message to the user
       mail($to, $email_subject, $email_message, $headers);   
}

//This function will generate a random temporary password for a user
function generate_password($length = 8){
  // given a string length, returns a random password of that length
  $password = "";
  // define possible characters
  $possible = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $i = 0;
  // add random characters to $password until $length is reached
  while ($i < $length) {
    // pick a random character from the possible ones
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
    // we don't want this character if it's already in the password
    if (!strstr($password, $char)) {
      $password .= $char;
      $i++;
    }
  }
  return $password;
}

//Updates a member's profile and returns a boolean value
function update_profile($first_name, $last_name, $email, $member, $db) {
 
       //Update the customer_info table with the data entered in the form
       $command = "UPDATE member_info SET first_name = '" . $db->real_escape_string($first_name) . 
                   "', last_name = '" . $db->real_escape_string($last_name) . 
                   "', email = '" . $db->real_escape_string($email) . 
                   "'  where member_id = '" . $db->real_escape_string($member) . "';"; 
       
       $result = $db->query($command);
       
       if ($result == true) {
         return true;
       }
       else {
         return false;
       }
}

//Updates a member's login information and returns a boolean value
function update_login_info($member_id, $username, $new_password, $db) {

       //Update password in database, display a success message, and close the connection.
       $command = "UPDATE member_login SET password = password('". $db->real_escape_string($new_password) ."'), " .
                  "login = '" . $db->real_escape_string($username) ."' " . 
                  "WHERE member_id = '" . $db->real_escape_string($member_id) ."';";
       $result = $db->query($command);
              
       if ($result == true) {
         return true;
       }
       else {
         return false;
       }
}

/*This function will accept two parameters: the data array from the post information
and the location of the email template,and then email the seller */
function contact_seller($data_array, $template_file) {
   
       /*Use the email template and replace the strings located within
       with the values from the data_array */  
       $email_message = file_get_contents($template_file);
       $email_message = str_replace("#USERNAME#", $data_array['from_login'], $email_message);
       $email_message = str_replace("#SENDER_ID#", $data_array['sender_id'], $email_message);
       $email_message = str_replace("#EMAIL#", $data_array['from_email'], $email_message);
       $email_message = str_replace("#MESSAGE#", $data_array['message'], $email_message);
       $email_message = str_replace("#PRODUCT_NAME#", $data_array['product_name'], $email_message);
       $email_message = str_replace("#PRODUCT_ID#", $data_array['product_id'], $email_message);
 
       //Construct the email headers
       $to = $data_array['to'];
       $from = "no-reply@erikslist.com";
       $email_subject = "Post Inquiry From EriksList Member: ". $data_array['product_name'];

       $headers  = "From: " . $from . "\r\n";
       $headers .= 'MIME-Version: 1.0' . "\n"; 
       $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";   
   
       //Send a confirmation email message to the user
       if (mail($to, $email_subject, $email_message, $headers) == true) {
          return true;
       }
       else {
         return false;
       }
}


/*This function will accept two parameters: the data array
from the post and location of the email template, and send an email to the user */
function recommend_post($data_array, $template_file) {
   
       /*Use the email template and replace the strings located within
       with the values from the data_array */  
       $email_message = file_get_contents($template_file);
       $email_message = str_replace("#EMAIL#", $data_array['from'], $email_message);
       $email_message = str_replace("#MESSAGE#", $data_array['message'], $email_message);
       $email_message = str_replace("#PRODUCT_NAME#", $data_array['product_name'], $email_message);
       $email_message = str_replace("#PRODUCT_ID#", $data_array['product_id'], $email_message);
 
       //Construct the email headers
       $to = $data_array['to'];
       $from = $data_array['from'];
       $email_subject = "Recommendation From EriksList Member: ". $data_array['product_name'];

       $headers  = "From: " . $from . "\r\n";
       $headers .= 'MIME-Version: 1.0' . "\n"; 
       $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";   
   
       //Send a confirmation email message to the user
       if (mail($to, $email_subject, $email_message, $headers) == true) {
          return true;
       }
       else {
         return false;
       }
}

//This function will retreive the latest 100 tags in the database
function fetch_all_tags($db) {

  $tag_array = array();

    $command = "SELECT pt.tag_id, et.tag FROM post_tags pt INNER JOIN erikslist_tags et " . 
               "ON pt.tag_id = et.tag_id " . 
               "WHERE pt.date_deleted <= 0 ORDER BY et.tag_id DESC LIMIT 100;";
               
       
    $result = $db->query($command);
    
    while ($this_tag_array = $result->fetch_assoc()) {
       array_push($tag_array, $this_tag_array['tag']);
    }
 
  
  return $tag_array;
}

?>