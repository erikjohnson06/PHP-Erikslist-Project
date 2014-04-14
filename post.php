<?php
//post.php -- interface for posting a new item

//Include utility files and public API
require("../erikslist_include/erikslist_utilities.inc");
include("erikslist_public.inc");

$db = member_db_connect();

//Start the session cookie
session_start();
$member_id = $_SESSION['member_id'];
$member_login = $_SESSION['member_login'];
$_SESSION['navigation'] = basename($_SERVER['PHP_SELF']);

//Ensure that the user is logged in
if (!$member_id) {
    //If the user is not logged in, redirect them to the home page:
    header("Location: login.php");
}

##################
#                #
# Post Handling  #
#                #
##################

//Process the initial post by storing the information in a session variable until all information is confirmed
if ($_POST['post_new_item']) {

   $description = trim($_POST['description']);
   $postal_code = trim($_POST['postal_code']);
   $category = $_POST['category'];
   $condition = $_POST['condition'];
   $product_name = trim($_POST['product_name']);
   $price = trim($_POST['price']);
   $tags = explode(" ", $_POST['tags']);
   $tag_array = array();
   for ($i = 0; $i < count($tags); $i++) {
      $tags[$i] = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $tags[$i]));
      array_push($tag_array, $tags[$i]);
   }
   
   //Remove any double quotes in the description as this has been problematic
   $description = preg_replace("/\"/","'", $description);   
   
   //Strip out any commas or dollar signs that the user inserted. Format to two decimal places
   //$price = number_format($price, 2);
   $price = str_replace( ',', '', $price );  
   $price = str_replace( '$', '', $price );  
   

   /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
   $valid_text  = "^(?=.*[A-Za-z])[A-Za-z0-9-\'\"\$\=\+\?\!\&\(\)\.\:\;\,\/\@\s]+$";
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
         $error_message = "Please enter a valid price. ";
    }
    
    else {
    
    
    //If the input data has passed validation, set session variables to retain during the posting process
    $_SESSION['product_name'] = $product_name; 
    $_SESSION['postal_code'] = $postal_code; 
    $_SESSION['category'] = $category ;
    $_SESSION['description'] = $description; 
    $_SESSION['price'] = $price;
    $_SESSION['condition'] = $condition;
    $_SESSION['tag_array'] = $tag_array;
    
    header("Location: post.php?images=upload");
    
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



//Process published posts here and store in the database
if ($_POST['publish']) {

   $description = $_POST['description'];
   $postal_code = $_POST['postal_code'];
   $category = $_POST['category'];
   $condition = $_POST['condition'];
   $product_name = $_POST['product_name'];
   $price = $_POST['price']; 
   $images = $_POST['images']; 
   $tags = explode(" ", $_POST['tags']);
   for ($i = 0; $i < count($tags); $i++) {
      $tags[$i] = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $tags[$i]));
   }
   
   
   /*Double check all fields are completed (except for images and tags, as these are optional)*/
    if (!($description && $postal_code && $category && $condition && $product_name && $price)) {
        $error_message = "An internal error has occurred. ";
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
    
         $command = "INSERT INTO product_catalog (product_id, member_id, product_name, category, description, " . 
                   "product_condition, price, postal_code, date_posted) " .
                    "VALUES ('', '". $db->real_escape_string($member_id) . "', " . 
                    "'". $db->real_escape_string($product_name) . "', " . 
                    "'". $db->real_escape_string($category) . "', " . 
                    "'". $db->real_escape_string($description) . "', " . 
                    "'". $db->real_escape_string($condition) . "', " . 
                    "'". $db->real_escape_string($price) . "', " . 
                    "'". $db->real_escape_string($postal_code) . "', now());";
                    
          $result = $db->query($command);
          
          if ($result == false) {
              $success = false;
          }
          else {
              
              //Now, product_images
             $product_id = $db->insert_id;
                 
             if ($images) {
             
                  $command = "INSERT INTO product_images (image_id, product_id, image) " . 
                            " VALUES ('', '" . $product_id . "', '". $db->real_escape_string($images) ."');";
                        
                  $result = $db->query($command);
      
                  if (($result == false) || $db->affected_rows == 0) {
                     $success = false;
                   }
             }    
                          


              //Now update the tags table first delete all previous tags
              $command = "UPDATE post_tags SET date_deleted=now() " . 
                           "WHERE member_id='". $db->real_escape_string($member_id) ."' " . 
                           "AND product_id='". $db->real_escape_string($product_id) ."' " . 
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
                    //we need to create an erikslist_tags entry
                    $command = "INSERT INTO erikslist_tags (tag_id, tag) VALUES " . 
                              "('', '". $db->real_escape_string($tags[$j]) ."');";
                    $result = $db->query($command);
               
                    echo $command;               
               
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
                                   "AND product_id='". $db->real_escape_string($product_id) ."' " . 
                                   "AND tag_id='". $db->real_escape_string($tag_id) ."';";
                          
                                   $result = $db->query($command);
               
                        if ($data = $result->fetch_object()) {
                             $command = "UPDATE post_tags SET date_deleted=0 " . 
                                        "WHERE member_id='". $db->real_escape_string($member_id) ."' " . 
                                        "AND product_id='". $db->real_escape_string($product_id) ."' " . 
                                        "AND tag_id='". $db->real_escape_string($tag_id) ."';";
                             
                             $result = $db->query($command);
                   
                             if ($result == false) {
                               $success = false;
                             }
                         }
               
                        else {
                            $command = "INSERT INTO post_tags (tag_id, member_id, product_id) VALUES " . 
                                       "('". $db->real_escape_string($tag_id) ."', '". $db->real_escape_string($member_id) ."', " .
                                       "'". $db->real_escape_string($product_id) ."');";
                            $result = $db->query($command);
               
                            if (($result == false) || ($db->affected_rows == 0)) {
                                $success = false;
                            }
                        }
                    }
                 }
              }        
           
          //Commit updates to database if all commands were executed successfully
          if (!$success) {
             $command = "ROLLBACK";
             $result = $db->query($command);
             $error_message = "We're sorry, there has been an error on our end. Please try again later.  ";
          }
          else {
             $command = "COMMIT";
             $result = $db->query($command);
             
             //Publish was successful, so remove session variables for post and redirect to a confirmation page
             
             unset($_SESSION['product_name']); 
             unset($_SESSION['postal_code']); 
             unset($_SESSION['category']);
             unset($_SESSION['description']); 
             unset($_SESSION['price']);
             unset($_SESSION['condition']);
             unset($_SESSION['images']);
             unset($_SESSION['tag_array']);
             
             header("Location: post.php?confirmation=true");
            
          }
          
            $command = "SET AUTOCOMMIT=1";  //Return to autocommit
            $result = $db->query($command);
             
             
             
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


//Process deleting an image by unsetting the session variable
if ($_GET['delete_image']) {

   //if (file_exists("product_images/" . $_SESSION['images'])) {
     //unlink($value);
   //  echo "file exists!";
   //}


   if ($_SESSION['images']) {
     unset($_SESSION['images']);
   }
      
    header("Location: post.php?images=upload");
}

//After publishing the post successfully, confirm this to the user 
if ($_GET['confirmation']) {

?>
    <div class="post">
    <span >
       <h3 style="color:black;text-align: center;">Your post has been published successfully! </h3>
       <h4 style="color:black;text-align: center;">You may edit the details of your posts in your <a href="profile.php">account profile</a>.</h4>
    </span>

    </div>
<?
}

//Initial page when posting a new item
if (!($_GET) || ($_GET['post_item'])) {

    if ($_SESSION['tag_array']) {
       $tag_string = implode(" ", $_SESSION['tag_array']);
     }

?>
<div class="post">
<form action="" method="POST">
<fieldset>
<legend>Post New Item:</legend>
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
  <td align="right" width="150">
  Posted by:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="member_login" value="<? echo $_SESSION['member_login']; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Posted on:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="post_date" value="<? echo date("n/j/Y"); ?>" disabled>
  </td>
</tr>


<tr>
  <td align="right">
  Post Title:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="product_name" value="<? if ($_POST) {echo $_POST['product_name'];} else {echo $_SESSION['product_name'];} ?>">
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
	   
                if ($_POST && $_POST['category'] == $value)
		  { echo 'selected';} 
		else if ($_SESSION['category'] == $value) 
		  { echo 'selected';}
		echo ">" . $value . "</option>";

      }
    ?>
    </select>
  </td>
</tr>

<tr>
  <td align="right">
  Condition:
  </td>
  <td align="left">
   <select name="condition">
    <option value="">--Select---</option>
    <option value="new" <? 
                 if ($_POST && $_POST['condition'] == "new") 
                      {echo 'selected';} 
                 else if ($_SESSION['condition'] == "new") 
                 {echo 'selected';} ?>>New</option>
		               
    <option value="excellent" <? 
                 if ($_POST && $_POST['condition'] == "excellent") 
                      {echo 'selected';} 
                 else if ($_SESSION['condition'] == "excellent") 
                 {echo 'selected';} ?>>Excellent</option>
		              
    <option value="good" <? 
                 if ($_POST && $_POST['condition'] == "good") 
                      {echo 'selected';} 
                 else if ($_SESSION['condition'] == "good") 
                 {echo 'selected';} ?>>Good</option>
                 
    <option value="fair" <? 
                 if ($_POST && $_POST['condition'] == "fair") 
                      {echo 'selected';} 
                 else if ($_SESSION['condition'] == "fair") 
                 {echo 'selected';} ?> >Fair</option>
                 
    <option value="poor" <? 
                 if ($_POST && $_POST['condition'] == "poor") 
                      {echo 'selected';} 
                 else if ($_SESSION['condition'] == "poor") 
                 {echo 'selected';} ?> >Poor</option>
   </select>
  </td>
</tr>


<tr>
  <td align="right">
  Postal Code:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="postal_code" value="<? 
        if ($_POST) {
           echo $_POST['postal_code'];
        } 
        else {
           echo $_SESSION['postal_code'];
        } 
        ?>">
  </td>
</tr>

<tr>
  <td align="right">
  Price:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="price" value="<? if ($_POST) {echo $_POST['price'];} else {echo $_SESSION['price'];} ?>">
  </td>
</tr>

<tr>
  <td align="right" valign="top">
  Description:
  </td>
  <td align="left">
    <textarea rows="6" cols="40" maxlength="500" name="description"><? if ($_POST) {echo $_POST['description'];} else {echo $_SESSION['description'];} ?></textarea>
  </td>
</tr>


<tr>
 <td align="right">
    Tags:
 </td>
 <td align="left">
    <input type="text" size="50" maxlength="250" name="tags" value="<? echo htmlentities($tag_string); ?>" placeholder="separate tags with spaces"/>
  </td>
</tr>

<tr>
   <td colspn="2">&nbsp;</td>
</tr>

<tr>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
   <td align="right" width="200">
     <input type="submit" name="post_new_item" value="Next">
   </td>
</tr>


</table>
</fieldset>
</form>
</div>

<?

}

//Next page to upload images
if ($_GET['images']) {
?>
<div class="post">
  <form action="" method="POST" enctype="multipart/form-data">
    <fieldset>
    <legend>Upload an Image:</legend>
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
     <td width="600">
         <input type="file" name="file" id="file">
      </td>
      <td align="right">
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
          
          echo "<span style='float:left;'><a class='button' href='post.php?images=true&delete_image=" . 
                $_SESSION['images'] . "'>Delete Image?</a></span>";
      }
     ?>
       
     <span style='float:right;'><a class="button" href="post.php?review=true">Review >></a></span>
  </div>

</div>
<?
}


//Final page to review and publis post
if ($_GET['review']) {

    if ($_SESSION['tag_array']) {
       $tag_string = implode(" ", $_SESSION['tag_array']);
     }

?>
<div class="post">
<form action="" method="POST">
<fieldset>
<legend>Review Your Post:</legend>
<table width="650" style="padding:20px 0px;" align="center">

<tr>
  <td align="right" width="150">
  Posted by:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="member_login" value="<? echo $_SESSION['member_login']; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Posted on:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="post_date" value="<? echo date("n/j/Y"); ?>" disabled>
  </td>

  <td align="right">
  Post Title:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="50" name="product_name" value="<? echo $_SESSION['product_name']; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Category:
  </td>
  <td align="left">
    <input type="text" size="25" name="category" value="<? echo $_SESSION['category']; ?>" disabled>
  </td>


  <td align="right">
  Condition:
  </td>
  <td align="left">
    <input type="text" size="25" name="condition" value="<? echo $_SESSION['condition']; ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right">
  Postal Code:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="postal_code" value="<? echo $_SESSION['postal_code']; ?>" disabled>
  </td>

  <td align="right">
  Price:
  </td>
  <td align="left">
    <input type="text" size="25" maxlength="10" name="price" value="<? echo number_format($_SESSION['price'],2); ?>" disabled>
  </td>
</tr>

<tr>
  <td align="right" valign="top" colspan="1">
  Description:
  </td>
  <td align="left" colspan="3">
    <textarea rows="5" cols="61" maxlength="500" name="description" disabled><? echo $_SESSION['description']; ?></textarea>
  </td>
</tr>

<tr>
 <td align="right">
    Tags:
 </td>
 <td align="left" colspan="3">
    <input type="text" size="50" maxlength="250" name="tags" value="<? echo htmlentities($tag_string); ?>" disabled>
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
     ?>
   

<tr>
   <td colspan="4">&nbsp;</td>
</tr>

<tr>
  <td width="25%"><a class="button" href="post.php?post_item=edit">Edit Post</a></td>
  <td width="25%"><a class="button" href="post.php?images=edit">Edit Image</a></td>
  <td width="25%">&nbsp;</td>
  <td align="right" width="25%">
     <input type="hidden" name="price" value="<? echo $_SESSION['price']; ?>">
     <input type="hidden" name="description" value="<? echo $_SESSION['description']; ?>">
     <input type="hidden" name="postal_code" value="<? echo $_SESSION['postal_code']; ?>">
     <input type="hidden" name="condition" value="<? echo $_SESSION['condition']; ?>">
     <input type="hidden" name="category" value="<? echo $_SESSION['category']; ?>">
     <input type="hidden" name="product_name" value="<? echo $_SESSION['product_name']; ?>">
     <input type="hidden" name="images" value="<? echo $_SESSION['images']; ?>">
     <input type="hidden" name="tags" value="<? echo htmlentities($tag_string); ?>">
     
     
     <input type="submit" name="publish" value="Publish">
   </td>
</tr>


</table>
</fieldset>
</form>
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

echo "FILES array: <br>";
echo "<pre>";
print_r($_FILES);
echo "</pre>";

echo $_SERVER['DOCUMENT_ROOT'];
*/

$db->close();


?>