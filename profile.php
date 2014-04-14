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


//Find out which member's profile to display
//(profileID is this member's customer_id)
$profileID = $_GET['profileID'];

if (!(is_numeric($member_id) || is_numeric($profileID))) {
   //Nothing to display. Redirect to the home page
   header("Location: index.php");
}

//Gather information about who is logged in and which profile to display
$myprofile_array = array();
if ($profileID) {
   $myprofile_array = fetch_profile($profileID, $db);
}
if ((count($myprofile_array) <= 0) && $member_id) {
   //check for this profile
   $myprofile_array = fetch_profile($member_id, $db);
   $profileID = $member_id;
}
if (count($myprofile_array) <= 0) {
   //cannot find profile
   header("Location: index.php");
}  


//Set sort variable to the fetch_my_posts function
$sort_by = $_GET['sort_by'];



####################
#                  #
# POST Processing  #
#                  #
####################

//Process updated product posts here
if ($_POST['publish']) {

   $description = trim($_POST['description']);
   $postal_code = trim($_POST['postal_code']);
   $category = $_POST['category'];
   $condition = $_POST['product_condition'];
   $product_name = trim($_POST['product_name']);
   $price = trim($_POST['price']);
   $image = $_POST['images'];
   $postedby_id = $_POST['member_id'];
   $update_product_id = $_POST['product_id'];
   
   $tags = explode(" ", $_POST['tags']);
   for ($i = 0; $i < count($tags); $i++) {
      $tags[$i] = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $tags[$i]));
   }

   //Strip out any commas or dollar signs that the user inserted
   $price = str_replace( ',', '', $price );
   $price = str_replace( '$', '', $price );   
  
   /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
   $valid_text  = "^(?=.*[A-Za-z])[A-Za-z0-9-\'\"\=\+\?\$\!\&\(\)\.\:\;\,\/\@\s]+$";
   $valid_postal_code = "^\d{5}(-\d{4})?$";

   /*Ensure that all field are completed*/
    if (!($description && $postal_code && $category && $condition && $product_name)) {
        $error_message = "Please complete all form fields. ";
    }
    
    else if (!$price) {
        $error_message = "Please complete all form fields. For free items, please indicate list price as \"0.01\"";
    }
    
    /*Check for proper formats for all inputs*/
    else if (strlen($description) > 500) {
        $error_message = "Please limit your description to 500 characters.  ";
    }
    
    else if (strlen($product_name) > 100) {
        $error_message = "Please limit your post title to 100 characters.  ";
    }
    
    else if (!(valid_input($description, $valid_text)) || !(valid_input($product_name, $valid_text))) {
        $error_message = "Please use valid characters in your description and/or post title. <br>
                         Special characters allowed include hyphens, apostrophes, quotations, and punctuation marks.  ";
    }
    else if (!(valid_input($postal_code, $valid_postal_code))) {
        $error_message = "Please include a valid postal code.  ";
    }    
    else if (!(is_numeric($price))) {
         $error_message = "Please enter a valid price.";
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
    
         //First, update the product catalog 
         $command = "UPDATE product_catalog " . 
                    "SET product_name = '". $db->real_escape_string($product_name) . "', " . 
                    "category = '". $db->real_escape_string($category) . "', " . 
                    "description = '". $db->real_escape_string($description) . "', " . 
                    "product_condition = '". $db->real_escape_string($condition) . "', " . 
                    "price = '". $db->real_escape_string($price) . "', " . 
                    "postal_code = '". $db->real_escape_string($postal_code) . "' " .
                    "WHERE member_id = '". $db->real_escape_string($postedby_id) . "' " . 
                    "AND product_id = '". $db->real_escape_string($update_product_id) . "';";
                    
          $result = $db->query($command);
          
          if ($result == false) {
              $success = false;
          }
          else {

             if ($image) {
             
             /*Now update the image, if a new one has been posted. First check to see if 
             product has any existing images posted. If this is the case, delete the old 
             image and insert the new one. */  
                         
             $command = "SELECT image FROM product_images " . 
                        "WHERE product_id = '".  $db->real_escape_string($update_product_id) ."' " . 
                        "AND date_deleted <= 0 ;";
                                         
             $result = $db->query($command);
                          
             /*If an existing image was found for this post, delete it here and insert the new one*/
             if ($result->num_rows > 0) {

                
                $command = "UPDATE product_images SET date_deleted = now() " . 
                           "WHERE product_id = '".  $db->real_escape_string($update_product_id) ."';";
                        
                        
                $result = $db->query($command);
                 
                if (($result == false) || $db->affected_rows == 0) {
                    $success = false;
                 }
               }
 
                 
               //Now insert the new image reference into the database
              $command = "INSERT INTO product_images (image_id, product_id, image) " . 
                          "VALUES ('', '". $db->real_escape_string($update_product_id) ."', '". 
                                           $db->real_escape_string($image) ."');";
                      
                        
               $result = $db->query($command);
              
               if (($result == false) || $db->affected_rows == 0) {
                       $success = false;
               }
             }
             
              //Now update the tags table first delete all previous tags
              $command = "UPDATE post_tags SET date_deleted=now() " . 
                           "WHERE member_id='". $db->real_escape_string($member_id) ."' " . 
                           "AND product_id='". $db->real_escape_string($update_product_id) ."' " . 
                           "AND date_deleted <=0;";
                           $result = $db->query($command);
      
               //now, go through the tag array to add or update
      
               for ($j = 0; $j < count($tags); $j++) {
                   if ($success && $tags[$j]) {
            
                   //check the database for an existing tag_id for this tag
                   $command = "SELECT tag_id FROM erikslist_tags WHERE tag='". $db->real_escape_string($tags[$j]) ."';";
                   $result = $db->query($command);
            
                   if ($data = $result->fetch_object()) {
                        //add an entry in post_tags using this tag_id
                        $tag_id = $data->tag_id;
                    }
             
                    else {
                    //we need to create a erikslist_tags entry
                    $command = "INSERT INTO erikslist_tags (tag_id, tag) VALUES " . 
                              "('', '". $db->real_escape_string($tags[$j]) ."');";
                    $result = $db->query($command);
               
               
                       if (($result == false) || ($db->affected_rows == 0)) {
                          $success = false;
                          break;
                        }
                        else {
                          $tag_id = $db->insert_id;
                        }
                     }
             
                    if ($success && is_numeric($tag_id)) {
                       //must also check the database for an existing tag with this tag_id
               
                        $command = "SELECT tag_id FROM post_tags WHERE " . 
                                   "member_id='". $db->real_escape_string($member_id) ."' " . 
                                   "AND product_id='". $db->real_escape_string($update_product_id) ."' " . 
                                   "AND tag_id='". $db->real_escape_string($tag_id) ."';";
                          
                                   $result = $db->query($command);
               
                        if ($data = $result->fetch_object()) {
                             $command = "UPDATE post_tags SET date_deleted=0 " . 
                                        "WHERE member_id='". $db->real_escape_string($member_id) ."' " . 
                                        "AND product_id='". $db->real_escape_string($update_product_id) ."' " . 
                                        "AND tag_id='". $db->real_escape_string($tag_id) ."';";
                             
                             $result = $db->query($command);
                   
                             if ($result == false) {
                               $success = false;
                             }
                         }
               
                        else {
                            $command = "INSERT INTO post_tags (tag_id, member_id, product_id) VALUES " . 
                                       "('". $db->real_escape_string($tag_id) ."', '". $db->real_escape_string($member_id) ."', " .
                                       "'". $db->real_escape_string($update_product_id) ."');";
                            $result = $db->query($command);
               
                            if (($result == false) || ($db->affected_rows == 0)) {
                                $success = false;
                            }
                        }
                    }
                 }
              }        


             
          /*If there was an error with any of the commands, do not proceed with saving. Otherwise, save all
          changes to the database and redirect the user to a confimation page.*/ 
          if (!$success) {
             $command = "ROLLBACK";
             $result = $db->query($command);
             $error_message = "We're sorry, there has been an error on our end. Please try again later.  ";
          }
          else {
             $command = "COMMIT";
             $result = $db->query($command);
             $success_message = "This post has been successfully updated. Return to <a href='profile.php'>your account</a> ";
             

            
          }
          
            $command = "SET AUTOCOMMIT=1";  //Return to autocommit
            $result = $db->query($command);
       }      
    }
}
     

//Process image uploads here
if ($_POST['upload_images']) {

  //For security, verify that the file type uploaded is an acceptable image format
  $allowed_extensions = array("gif", "jpeg", "jpg", "png");
  $temp = explode(".", $_FILES['file']['name']);
  $extension = end($temp);
  $extension = strtolower($extension);

  //Set variables with the files array
  $image_name =  $_FILES["file"]["name"];
  $image_type =  $_FILES["file"]["type"];
  $image_size =  $_FILES["file"]["size"];
  $image_tmp_name = $_FILES["file"]["tmp_name"];
  $image_error = $_FILES["file"]["error"];


  //Check  to make sure the image types are acceptable formats
 if ((($image_type == "image/gif")  || ($image_type == "image/jpeg") ||
      ($image_type == "image/jpg")  || ($image_type == "image/pjpeg") || 
      ($image_type == "image/x-png")|| ($image_type == "image/png")))  {
     
     
    //Check to make sure that the size does not exceed 1MB
    if  (($_FILES["file"]["size"] < 1048576) && in_array($extension, $allowed_extensions))  {

       //Check to make sure there were no errors in the uploading process
       if ($image_error > 0)  {
         $error_message = "An error has occurred. Error Code: " . $image_error . "<br>";
       }
           
      /*If the file is the correct type AND is under 1 MB in size, begin resizing and storing process */
      else  {
    
        //Create new file depending one what extension is being used
        switch ($extension) {
            case 'jpg': 
            $uploadedfile = $image_tmp_name;
            $src = imagecreatefromjpeg($uploadedfile);
            break;
            
            case 'jpeg':
            $uploadedfile = $image_tmp_name;
            $src = imagecreatefromjpeg($uploadedfile);
            break;
            
            case 'png':
            $uploadedfile = $image_tmp_name;
            $src = imagecreatefrompng($uploadedfile);
            break;
            
            case 'gif':
            $uploadedfile = $image_tmp_name;
            $src = imagecreatefromgif($uploadedfile);
            break;
        }    
      
        //Get the image's aspect ratio and resize to 300 px in width
        list($width,$height) = getimagesize($uploadedfile);

        $newwidth = 300;
        $newheight = ($height/$width) * $newwidth;
        $tmp = imagecreatetruecolor($newwidth,$newheight);

        imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);

        //Rename new file and destroy working copies
        $path = "product_images/";
        $filename = time() . "." . $extension;

        $new_file = imagejpeg($tmp, $path . $filename);
        imagedestroy($src);
        imagedestroy($tmp);
      
        //Store files in the product_images folder, and make a reference to it in the database
         move_uploaded_file($new_file, $path . $filename);
         
         
         //Store filename in session variable to reference in database later
         $_SESSION['images'] = $filename;
     
        //To allow uploading of multiple images
        //$image_counter =  count($_SESSION['images']);
        
        //if ($image_counter >= 3) {
            //Store filename in session variable to reference in database later
           // $_SESSION['images'][$image_counter]['image'] = $filename;
        //}
       // else {
          // $error_message = "You are allowed a maximum of 1 image per post."; 
       // }       
    }
  }
  else {
      $error_message = "Images must not exceeed 1MB in size, " . 
                       "and must be in .gif, .jpg, .jpeg, or .png format. <br>";
      $error_message .= "Type: " . $_FILES["file"]["type"] . "<br>";
      $error_message .= "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
  }
}
else {
      $error_message = "Invalid file type. Images must be in .gif, .jpg, .jpeg, or .png format. ";
  }

}


/*If the form has been submitted, proceed with checking the input for errors*/
if ($_POST['update_profile']) {

    /*Set post variables here. Use the trim() function to remove any white spaces
    on either end of the input.*/	   
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $member = $_POST['member_id'];
    
    /*Regular expressions for all text input. Names must contain dashes and apostrophes, 
    but must contain at least some letters. */
    $valid_name = "^(?=.*[A-Za-z])[A-Za-z][A-Za-z-']+$";
    
    
    /*Ensure that all fields are completed upon submission*/
    if (!($first_name && $last_name && $email)) {
        $error_message = "Please make sure you've filled in all the form fields. <br>";
    }
    
    /*Check all input for length and validity based on the regular expressions above*/
    if (strlen($first_name) > 50 || strlen($last_name) > 50) {
        $error_message  = $error_message. "Please make sure both your first and last names are fewer than 50 characters each.  <br>";
    }
    
    if (!(valid_input($first_name, $valid_name)) || !(valid_input($last_name, $valid_name))) {
        $error_message = $error_message.  "Please enter a valid name, which contains letters, hyphens or apostrophes only. <br> ";
    }
    
    if (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message  = $error_message. "Please enter a valid email address, which is less than 50 characters.  <br>";
    }
    

    
   /*If any error messages occurred as a result of not passing validation, display them as messages 
   to the user and do not proceed further. If the data passed the validation, perform database constraint
   checks, and then insert the data into the database. */
   if (!$error_message) {
   
     /*Check the email address and name to make sure it doesn't already exist and remains unique in the database. */
     if (check_email($member_id, $email, $db) != true) {
         $error_message  = $error_message. "Email address already exists! <br>"; 
     }

     /*If the database validation has passed, begin a transaction to update the member_info and member_login tables. */
     else {
            
       if ((update_profile($first_name, $last_name, $email, $member_id, $db)) == true) {
              $success_message = "<br>Profile was updated successfully!<br>";         
       }
       else {
              $error_message  = "<br>We're sorry. An error occured in updating your profile.<br>"; 
       }

   }
 } 
}


/*If the form to update login information has been submitted, proceed with the validation checks */
if ($_POST['update_login']) {
	   
    $username =  trim($_POST['username']);
    $current_password = trim($_POST['current_password']);
    $new_password1 = trim($_POST['new_password1']);
    $new_password2 = trim($_POST['new_password2']);
    $current_login = $_POST['current_login'];
    
    
    /*Regular expressions for emails and password. Password must includ 6-12 characters, 
    must have both alphanumberic characters AND numbers, and may allow a few special characters
    as well. Email must conform to a standard email format, and any name may contain dashes 
    and apostrophes, but must contain at least some letters. */
    $valid_pass = "^(?=.{6,12})(?=.*[A-Za-z])(?=.*\d)[a-zA-Z\d_!]+$";
    $valid_name = "^(?=.*[A-Za-z])[A-Za-z][A-Za-z-']+$";
    $valid_username = "(?=.*[A-Za-z])[a-zA-Z0-9_]{6,12}";
    
    /*Ensure that all field are completed and meet te complexity requirements */
    if (!($username && $current_password && $new_password1 && $new_password2)) {
        $error_message = "Please complete all fields. ";
    }
        
    else if (strlen($username) > 12 || strlen($username) < 6 || !(valid_input($username, $valid_username))) {
       $error_message = "Make sure that your login is between 6 and 12 characters, " .  
                        "and that it only contains letters, numbers, or an underscore (_).  ";
    }   
        
    else if (strlen($new_password1) > 12 || strlen($new_password1) < 6 || !(valid_input($new_password1, $valid_pass))) {
       $error_message = "Please make sure your new password is between 6 and 12 characters, and is a combination of 
                         contains letters and numbers. Special characters allowed include underscores (_) and exclamation marks (!).  ";
    }
    
    /*Makes sure the both passwords match each other*/
    else if (!($new_password1 == $new_password2)) {
       $error_message = "Passwords do not match.";
    }
    
    else {
       /*Now, check the database to make sure the current password is correct*/
       
       
        /*Check the username to name to make sure it doesn't already exist and remains unique in the database. */
       if (check_username($member_id, $username, $db) != true) {
           $error_message = "Username already exists! <br>"; 
       }
       //Ensure that the current password is correct before proceeding
       else if (verify_password($member_id, $current_password, $db) != true) {
           $error_message = "Invalid current password. Please make sure you are entering your password correctly.";
       }
 
       else  {
          
          //Update the login database and display a message 
          if ((update_login_info($member_id, $username, $new_password1, $db)) == true) {
               $success_message = "Your login information has been successfully updated.";  
               
               //Set new session variable so that the change to the login appears immediately
               $_SESSION['member_login'] = $username;
               
          }
          else {
              $error_message  = "<br>We're sorry. An error occured in updating your login information.<br>"; 
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

################
#              #
# Edit Image   #
#              #
################

/* This is called upon when the user wants to delete a new temporary 
image and revert back to the original */
if ($_GET['delete_image'] && $_GET['edit_post']) {

   //if (file_exists("product_images/" . $_SESSION['images'])) {
     //unlink($value);
   //  echo "file exists!";
   //}


   if ($_SESSION['images']) {
     unset($_SESSION['images']);
   }
      
    header("Location: profile.php?edit_post=" . $_GET['edit_post']);
}

/*This is called upon when the user wants to delete the image associated 
with the post (rather than just edit it)*/
else if ($_GET['delete_all_images'] && $_GET['edit_post'] ) {

   $product_id = $_GET['edit_post'];

   if ($member_id == $profileID) {

      delete_post_image($product_id, $db);
   
   }

   header("Location: profile.php?edit_post=" . $_GET['edit_post']);
}


/*This section will display the option to upload a new image to the post*/
else if ($_GET['edit_post'] && $_GET['new_image']) {

  $product_id = $_GET['edit_post'];
  
  //Ensure that the edit_post ID is a number and fetch the information for this post
  if (is_numeric($product_id)) {

    $post_array = fetch_post($profileID, $product_id, $db);
   
    //Ensure that the member is able to their own posts only
    if ($member_id == $post_array['member_id']) {

      ?>
      <div class="upload">
        <form action="" method="POST" enctype="multipart/form-data">
          <fieldset>
           <legend>Upload a New Image:</legend>
          <table>
    
         <tr>
          <td colspan="3">
            <span style="color:red;font-size:12px;">
            <?
               if ($error_message) {
               echo $error_message;
              }
             ?>
            </span>
           </td>
        </tr>
    
        <tr>
         <td width="300">
            <input type="file" name="file" id="file">
         </td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>
            <input type="submit" name="upload_images" value="Upload">
         </td>
      </tr>
      </table>
     </fieldset>
     </form>
     
  <div class="preview_images">

    <?   
      if ($_SESSION['images']) {
          echo "<img class='preview' src='product_images/" . $_SESSION['images'] . "' alt='image' />";
          
      }
     ?>

  </div>
  
  <div class="preview_images">
    <?   
      if ($_SESSION['images']) {
          echo "<span style='float:right;margin:5px;'><a class='button' href='profile.php?edit_post=" . $_GET['edit_post'] . 
               "&delete_image=" .  $_SESSION['images'] . "'>Cancel</a></span>";
          echo "<span style='float:right;margin:5px;'><a class='button' href='profile.php?edit_post=" .  $_GET['edit_post'] ."'>Use this image?</a></span>";
      }
      else {
          echo "<span style='float:right;margin:5px;'><a class='button' href='profile.php?edit_post=" . $_GET['edit_post'] . 
               "&delete_image=false'>Cancel</a></span>";
      }
     
    
     ?>


  </div>
          
  </div>
      
   <? 
  }  
  else {
     echo "<h3>You are not allowed to edit this post.</h3>";
  }  
 }
}


################
#              #
# Edit Post    #
#              #
################

//Allow the user to edit posts they have made
else if ($_GET['edit_post']) {

  $product_id = $_GET['edit_post'];
  
  //Ensure that the edit_post ID is a number and fetch the information for this post
  if (is_numeric($product_id)) {

    $post_array = fetch_post($profileID, $product_id, $db);
    $post_image = fetch_post_image($product_id, $db);
    $tag_array = fetch_tags($product_id, $db);
    $tag_string = implode(" ", $tag_array);
   
   
    //Ensure that the member is able to their own posts only
    if ($member_id == $post_array['member_id']) {
  ?>
<div class="post">

<h3>&nbsp;<span style="float:right;"><a class="button" href="profile.php?default=true">Return to My Profile</a></span></h3>

<form action="" method="POST">
<fieldset>
<legend>Edit Post Details:</legend>
<table width="650" style="padding:20px 0px;" align="center">
 <tr>
  <td colspan="4" align="center">
   <span style="color:red;font-size:12px;">
    <?
    //Display error / success messages here
     if ($error_message) {
      echo $error_message . "<br>";
     }
    ?>
    </span>

   <span style="color:#191970;font-size:12px;">
   <?
   if ($success_message) {
     echo $success_message . "<br>";
   }
   ?>
   </span>
  </td>
</tr>

<tr>
   <td colspan="4">&nbsp;</td>
</tr>

<tr>
  <td align="right" width="150">
  Posted by:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="member_login" value="<? echo $post_array['login']; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Posted on:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="post_date" value="<? echo date("n/j/Y", $post_array['date_posted']); ?>" disabled>
  </td>

  <td align="right">
  Post Title:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="product_name" value="<? 
          if ($_POST) {echo $_POST['product_name'];} 
          else { echo $post_array['product_name'];} ?>">
  </td>
</tr>

<tr>
  <td align="right">
  Category:
  </td>
  <td align="left">
   <select name="category">
    <option value="">--Select---</option>
    <?
     //Cycle through the menu array and populate the categories
      foreach ($menu_array as $key => $value) { 

	   echo "<option value='" . $value . "'";
	   
                if ($post_array['category'] == $value)
		  { echo 'selected';} 
		echo ">" . $value . "</option>";

      }
    ?>
    </select>
  </td>

  <td align="right">
  Condition:
  </td>
  <td align="left">
   <select name="product_condition">
    <option value="">--Select---</option>
    <option value="new" <? if ($post_array['product_condition'] == "new") { echo 'selected';} ?> >New</option>
    <option value="excellent" <? if ($post_array['product_condition'] == "excellent") { echo 'selected';} ?>>Excellent</option>
    <option value="good" <? if ($post_array['product_condition'] == "good") { echo 'selected';} ?>>Good</option>
    <option value="fair" <? if ($post_array['product_condition'] == "fair") { echo 'selected';} ?> >Fair</option>
    <option value="poor" <? if ($post_array['product_condition'] == "poor") { echo 'selected';} ?> >Poor</option>
   </select>
  </td>
</tr>

<tr>
  <td align="right">
  Postal Code:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="postal_code" value="<? 
       if ($_POST) {echo $_POST['postal_code'];} 
          else { echo $post_array['postal_code'];} ?>">
  </td>

  <td align="right">
  Price:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="price" value="<? 
       if ($_POST) {echo $_POST['price'];} 
          else { echo $post_array['price'];} ?>">
  </td>
</tr>

<tr>
  <td align="right" valign="top" colspan="1">
  Description:
  </td>
  <td align="left" colspan="3">
    <textarea rows="5" cols="61" maxlength="500" name="description"><? 
       if ($_POST) {echo $_POST['description'];} 
          else { echo $post_array['description'];} ?></textarea>
  </td>
</tr>

<tr>
 <td align="right">
    Tags:
 </td>
 <td align="left" colspan="3">
    <input type="text" size="50" maxlength="250" name="tags" value="<? echo htmlentities($tag_string); ?>" placeholder="separate tags with spaces"/>
  </td>
</tr>

<tr>
   <td colspan="4">&nbsp;</td>
</tr>
    <?  
    
      if ($_SESSION['images']) {
      
         echo "<tr>";
            echo "<td colspan='4'>";
             echo "<img class='preview' src='product_images/" . $_SESSION['images'] . "' alt='image' />";
            echo "</td>";
         echo "</tr>";

      }

      else if ($post_image) {
      
      echo "<tr>";
         echo "<td colspan='4'>";
          echo "<img class='preview' src='product_images/" . $post_image . "' alt='image' />";
          echo "</td>";
      echo "</tr>";
      }
      
      else {
      echo "<tr>";
         echo "<td colspan='4'>";
          echo "No images uploaded";
          echo "</td>";
      echo "</tr>";
      }
     ?>
   

<tr>
   <td colspan="4">&nbsp;</td>
</tr>

<tr>
  <td width="25%" align="center"><a class="button" href="profile.php?edit_post=<? echo $post_array['product_id']; ?>&new_image=true">Edit Image</a></td>
  <td width="25%" align="center"><a class="button" href="profile.php?edit_post=<? echo $post_array['product_id']; ?>&delete_all_images=true">Delete Image</a></td>
  <td width="25%"> &nbsp;</td>
  <td align="right" width="20%">

     <input type="hidden" name="images" value="<? if ($_SESSION['images']) {echo $_SESSION['images'];}?>">
     <input type="hidden" name="member_id" value="<? echo $post_array['member_id']; ?>">
     <input type="hidden" name="product_id" value="<? echo $post_array['product_id']; ?>">
     <input type="submit" name="publish" value="Update">
   </td>
</tr>


</table>
</fieldset>
</form>


</div>

<?

}
else {
   echo "<h3>You are not allowed to edit this member's posts.</h3>";
}
}
}



################
#              #
# Delete Post  #
#              #
################

//Allow the user to remove any posts they have made
if ($_GET['remove_post']) {
 
   ?>
   <div class="profile">

<h3>Remove Post</h3>
    <fieldset>
    <legend>Confirm</legend>
    
    <h4 style="text-align:center;">Are you sure you want to delete this post?</h4>
    <table align="center">
    
      <tr>
       <td colspan="2">&nbsp;</td>
     </tr>
      <tr>
 
       <td align="center" width="100">
         <a class="button" href="profile.php?confirm_remove=1&remove_post=<? echo $_GET['remove_post'] ; ?>">Confirm</a>
       </td>
       <td align="center" width="100">
          <a class="button" href="profile.php?default=true">Cancel</a>
       </td>
        
     </tr>

    </table>
   </fieldset>
</div>
   
  <? 
   
}

//Process post removals
if ($_GET['remove_post'] && $_GET['confirm_remove']) {

   $remove_id = $_GET['remove_post'];

   if (remove_post($member_id, $db, $remove_id) == true) {
       
       header("Location: profile.php?default=true");
   
  }
}

//Process items being marked as "sold"
if ($_GET['mark_as_sold']) {

     $sold_item = $_GET['mark_as_sold'];
     
   if (mark_post_as_sold($member_id, $db, $sold_item) == true) {
       
       header("Location: profile.php?default=true");
   
  }
     
}


################
#              #
# Main Profile #
#              #
################

//Display the default profile profile page
if (!($_GET) || $_GET['default'] || $_GET['profileID'] || $_GET['sort_by']) {

   //Remove any existing temporary images uploaded
   if ($_SESSION['images']) {
     unset($_SESSION['images']);
   }


?>

<div class="profile">

<?
   if ($member_id == $profileID) {
     echo "<h3>My Profile</h3><fieldset><legend>My Account Information</legend>";
   }
   else {
     echo "<h3>Profile: " . $myprofile_array['first_name'] . " " . $myprofile_array['last_name'] . 
          "</h3><fieldset><legend>Account Information</legend>";
   }
?>

     <table style='padding:20px 0px;'>
     
     <tr>
       <td align="right" width="120">
         Name:
        </td>
        <td align="left">
          <? echo $myprofile_array['first_name'] . " " . $myprofile_array['last_name']; ?>
        </td>
       <td>&nbsp;</td>
     </tr>

     <tr>
       <td align="right">
         Email:
        </td>
        <td align="left">
          <? echo $myprofile_array['email'] ; ?>
        </td>
        <td>&nbsp;</td>
     </tr>

     <tr>
       <td align="right">
         Username:
        </td>
        <td align="left">
          <? echo $myprofile_array['login'] ; ?>
        </td>
        <td>&nbsp;</td>
     </tr>


     <tr>
       <td align="right">
         Member Since:
        </td>
        <td align="left">
          <? echo date("F jS, Y", $myprofile_array['date_enrolled']); ?>
        </td>
       <td>&nbsp;</td>
     </tr>
     
     <tr>
      <td colspan="4">&nbsp;</td>
    </tr>
     
     <?
     
     //Give the option to edit your profile, but only IF it is your profile
     if ($member_id == $profileID) {
       ?>
      <tr>
       <td>&nbsp;</td>
       <td>&nbsp;</td>
       <td align="right" width="450">
          <a class="button" href="profile.php?edit_profile=<? echo $myprofile_array['member_id'] ; ?>">Edit Profile</a>
       </td>
        
     </tr>
     <?
     }
    ?>
    </table>
   </fieldset>
</div>


<div class="profile">

<?
if ($member_id == $profileID) {
   echo "<fieldset><legend>My Posts</legend>";
}
else {
   echo "<fieldset><legend>Posts</legend>";
}


  //Retrieve all posts made by this member
  $my_posts = fetch_my_posts($profileID, $sort_by, $db);
  $array_count = count($my_posts);
  
  //Display a sorting function for user's post items
    echo "<div style='margin-top:20px;'>";
    
       //Allow users to sort the info by price, post date, etc on their own profile page.


       ?>
       <form action="" method="GET">
        <table align="right" style="margin-top:-25px;">
         <td align="left">
          <select name="sort_by">
            <option value="post_date" <?if ($_GET['sort_by'] == "post_date") {echo "selected";} ?>>Date</option>
            <option value="sold_only" <?if ($_GET['sort_by'] == "sold_only") {echo "selected";} ?>>Sold Items</option>
            <option value="unsold_only" <?if ($_GET['sort_by'] == "unsold_only") {echo "selected";} ?>>Unsold Items</option>
            <option value="alpha_asc" <?if ($_GET['sort_by'] == "alpha_asc") {echo "selected";} ?>>A-Z sort</option>
            <option value="alpha_desc" <?if ($_GET['sort_by'] == "alpha_desc") {echo "selected";} ?>>Z-A sort</option>
         </select>
       </td>
       
       <td width="50">
          <input type="hidden" name="profileID" value="<?  echo $profileID; ?>">
          <input class="default" type="submit" value="Sort">
       </td>
       </tr>
       
       </table>
       </form>
       <?
    
    echo "</div>";
  
  
  if ($array_count <= 0) {
    echo "No posts yet.";
  }
  else {
      
    
    //Cycle through each of the posts and create a list in descending order
    echo "<ul style='list-style-type:none;'>";
    while (list($key, $this_post) = each($my_posts)) {
      
        echo "<br><li>";
        echo "<a href='catalog.php?product_id=" . $this_post['product_id'] .  "'/>" . $this_post['product_name'] . "</a>  " ;
        echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted on: " . date("M j, Y, g:i a", $this_post['date_posted']) . "</span><br/>";
        echo "Description: " . substr($this_post['description'], 0,50) . "...<br>";
        
        //If profile page is the user's own page, then allow them to delete and edit posts
        if ($member_id == $profileID) {
       
          //Indicate whether the item is sold or not. If not, provide a link to mark the item as "sold"
          if ($this_post['date_sold'] > 0) {
                echo "<span class='sold'>sold</span><span class='action'>";
          }
          else {
                echo "<span class='action'><a class='next' href='profile.php?mark_as_sold=" . 
                $this_post['product_id'] . "'>mark as sold</a>";
          }

       
          echo "<a class='next' href='profile.php?remove_post=" . 
                $this_post['product_id'] . "'>delete</a>";
       
          echo "<a class='last' href='profile.php?edit_post=" . 
                $this_post['product_id'] . "'>edit</a></span><br>";
        }
       
      echo "<br><hr>"; 
    } 
    
   echo "</ul>"; 
  }

?>
     
   </fieldset>
</div>

<?
} //End default page

################
#              #
# Edit Profile #
#              #
################

//Display the default profile profile page
if ($_GET['edit_profile']) {

   //Ensure that only the member logged in can edit their own profile
   if ($_GET['edit_profile'] != $member_id) {
      header("Location: profile.php?default=true");
   }
   else {
       ?>

  <div class="profile">

  <h3>Edit Profile <span style="float:right;"><a class="button" href="profile.php?default=true">Return to My Profile</a></span></h3>

<span style="color:red;font-size:12px;">
<?
//Display error / success messages here
if ($error_message) {
   echo $error_message . "<br>";
}
?>
</span>

<span style="color:#191970;font-size:12px;">
<?
if ($success_message) {
   echo $success_message . "<br>";
}
?>
</span>

<form method="POST" action="">
 <fieldset>
 <legend>My Account Information:</legend>
 <table>

  <tr>
   <td align="right" width="150">
    First Name:
   </td>
   <td align="left">
     <input type="text" size="25" maxlength="25" name="first_name" value="<? 
         if ($_POST) {echo $_POST['first_name'];} 
         else {echo $myprofile_array['first_name'];} 
         ?>">
   </td>
</tr>

<tr>
  <td align="right">
    Last Name:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="25" name="last_name" value="<? 
        if ($_POST) {echo $_POST['last_name'];} 
        else {echo $myprofile_array['last_name'];}
        ?>">
  </td>
</tr>

<tr>
  <td align="right">
  Email address:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="email" value="<? 
        if ($_POST) {echo $_POST['email'];} 
        else {echo $myprofile_array['email'];} ?>">
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   &nbsp;
  </td>
</tr>

<tr>
  <td>&nbsp;</td>
  <td>
   <input type="hidden" name="member_id" value="<? echo $member_id ?>">
   <input type="submit" name="update_profile" value="Update">
  </td>
</tr>

</table>
</fieldset>
</form>

<br>
<br>

<form method="POST" action="">
 <fieldset>
 <legend>Login Information:</legend>
<table>

<tr>
  <td align="right" width="150">
  Username:
  </td><td align="left">
  <input type="text" size="24" maxlength="12" name="username" value="<? if ($_POST) {echo $_POST['username'];} else {echo $member_login;} ?>">
  </td>

<tr>
  <td align="right">
  Current Password:
  </td><td align="left">
  <input type="password" size="24" maxlength="12" name="current_password" value="<? echo $_POST['current_password']; ?>">
  </td>
</tr>

<tr>
  <td align="right">
  New Password:
  </td><td align="left">
  <input type="password" size="24" maxlength="12" name="new_password1" value="<? echo $_POST['new_password1']; ?>">
  </td>
</tr>

<tr>
  <td align="right">
  Confirm New Password:
  </td><td align="left">
  <input type="password" size="24" maxlength="12" name="new_password2" value="<? echo $_POST['new_password2']; ?>">
  </td>
</tr>

<tr>
  <td colspan="2" align="center">
   &nbsp;
  </td>
</tr>

<tr>
  <td>&nbsp;</td>
  <td>
    <input type="hidden" name="member_id" value="<? echo $member_id ?>">
    <input type="hidden" name="current_login" value="<? echo $member_login ?>">
    <input type="submit" name="update_login" value="Update">
  </td>
</tr>
</table><br>
</fieldset>
</form>
</div>
       
       
       <?
   }
}   //End edit profile section


//Include footer 
include("../erikslist_include/erikslist_footer.inc");

/*

echo "My posts array: <br>";
echo "<pre>";
print_r($my_posts);
echo "</pre>";

echo "My Profile array: <br>";
echo "<pre>";
print_r($myprofile_array);
echo "</pre>";

echo "Post array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "Post Image: <br>";
echo "<pre>";
print_r($post_image);
echo "</pre>";



echo "Product post array: <br>";
echo "<pre>";
print_r($post_array);
echo "</pre>";

echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

*/

$db->close();

?>