<?php

//Include utility files and public API
require("../erikslist_include/erikslist_utilities.inc");
include("erikslist_public.inc");

$db = public_db_connect();

//Start the session cookie
session_start();
$member_id = $_SESSION['member_id'];
$member_login = $_SESSION['member_login'];
$_SESSION['navigation'] = basename($_SERVER['PHP_SELF']);
$_SESSION['back'] = htmlentities($_SERVER['HTTP_REFERER']);

//Set default page to 1 when searching, unless otherwise specified
$page = 1;
if (is_numeric($_GET['page'])) {
   $page = intval($_GET['page']);
}

//Call on the flag_post function if a post has been reported
if (is_numeric($_GET['flag_post'])) {
   flag_post($_GET['flag_post'], $db, $member_id);
   $flag_message = "<span class='info' style='float:right;'>You have reported this post as inappropriate. " .
              "You will no longer see this post in your catalog.</span>"; 
}


//Use "sort" as an arugment for the fetch_all_posts function

if ($_GET['sort_by'] == "price_asc") {
   $sort = $_GET['sort_by'];
}
else if ($_GET['sort_by'] == "price_desc") {
   $sort = $_GET['sort_by'];
}
else if ($_GET['sort_by'] == "post_date") {
   $sort = $_GET['sort_by'];
}
else {
   $sort = "all";
}



##################
#                #
# Contact Seller #
#                #
##################

//Processing contacting a seller here
if ($_POST['send_contact']) {

   $to = $_POST['to'];
   $from = $_POST['from_email'];
   $message = trim($_POST['message']);
   $this_product = $_POST['product_id'];
  
   
   /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
   $valid_text  = "^(?=.*[A-Za-z])[A-Za-z0-9-\'\"\=\+\?\$\!\(\)\.\:\;\,\/\@\s]+$";
   
   /*Ensure that the email address being sent to is valid. There is no reason why is shouldn't be, 
   since it is being pulled from the database, however, this will prevent a email being sent 
    to an invalid email address in the rare event in that it is. */   
    if (!(filter_var($to, FILTER_VALIDATE_EMAIL))) {
        $error_message = "We're sorry. This seller's email address is not valid.  ";
    }
    
    else if (!(filter_var($from, FILTER_VALIDATE_EMAIL))) {
        $error_message = "We're sorry. The email address you entered is not valid.  ";
    }
    
    else if (!$message || strlen($message) > 500) {
        $error_message = "Please include a message to the seller. Character limit is 500. ";
    }
 
    else if (!(valid_input($message, $valid_text))) {
        $error_message = "Please use valid characters in your message and/or post title. <br>
                         Special characters allowed include hyphens, apostrophes, quotations, and punctuation marks. ";
    }
   
    else {
          /*Call the function "contact_seller", passing the array of data and the location 
           of the email template */
           if (contact_seller($_POST, "../erikslist_include/contact_template.txt") == true) {
              
              header("Location: catalog.php?product_id=". $this_product . "&contact_confirm=1");
           }
           else {
             $error_message = "We're sorry. An internal error has occurred. Please try again later.";
           }
   }
}

if ($_GET['contact_confirm']) {

   $success_message = "Your message has been successfully sent to the seller.<br><br>";

}


##################
#                #
# Recommend Post #
#                #
##################

//Process post recommendations here
if ($_POST['send_recommendation']) {

   $to = $_POST['to'];
   $from = $_POST['from'];
   $message = trim($_POST['message']);
   $this_product = $_POST['product_id'];
  
   
   /* Limit the post to alphanumeric characters, digits, and simple punctuation. */
   $valid_text  = "^(?=.*[A-Za-z])[A-Za-z0-9-\'\"\=\+\?\$\!\(\)\.\:\;\,\/\@\s]+$";
   
   /*Ensure that the email address being sent to is valid. There is no reason why is shouldn't be, 
   since it is being pulled from the database, however, this will prevent a email being sent 
    to an invalid email address in the rare event in that it is. */   
    if (!(filter_var($to, FILTER_VALIDATE_EMAIL))) {
        $error_message = "We're sorry. The recipient's email address is not valid.  ";
    }
    
    else if (!(filter_var($from, FILTER_VALIDATE_EMAIL))) {
        $error_message = "We're sorry. The \"From\" email address you entered is not valid.  ";
    }
    
    else if (!$message || strlen($message) > 500) {
        $error_message = "Please include a message to the seller. Character limit is 500. ";
    }
 
    else if (!(valid_input($message, $valid_text))) {
        $error_message = "Please use valid characters in your message and/or post title. <br>
                         Special characters allowed include hyphens, apostrophes, quotations, and punctuation marks. ";
    }
   
    else {
          /*Call the function "recommend_post", passing the array of data and the location 
           of the email template */
           if (recommend_post($_POST, "../erikslist_include/recommendation_template.txt") == true) {
              
              header("Location: catalog.php?product_id=". $this_product . "&recommend_confirm=1");
           }
           else {
             $error_message = "We're sorry. An internal error has occurred. Please try again later.";
           }
   }
}

//Display a success message if the recommendation was successfully sent
if ($_GET['recommend_confirm']) {

   $success_message = "Your recommendation has been successfully sent.<br><br>";

}



###############
#             #
#  Search     #
#             #
###############

//Process searches here
if (isset($_GET['searchstring']) || isset($_GET['category'])) {

    /*Set GET variable for the search string here. Use the trim() function to remove any white spaces
    on either end of the input. Also, make the searchstring all upper case to make it easier to find 
    more consistent matches in the database (all of which will also be converted to upper case for 
    the search)*/	   
    $searchstring = trim($_GET['searchstring']);
    $searchstring = strtoupper($searchstring); 
    
    //Price parameters are optional, but if they were entered, process them here to ensure they are valid
    if ($_GET['min_price'] || $_GET['max_price']) {
      /*Strip out any commas or dollar signs that the user 
      inserted for price and format the number to two decimal places*/
      $min_price = trim($_GET['min_price']);
      $min_price = str_replace(',', '', $min_price);
      $min_price = str_replace('$', '', $min_price);   
    
      $max_price = trim($_GET['max_price']);
      $max_price = str_replace(',', '', $max_price);
      $max_price = str_replace('$', '', $max_price);   
    }
    
    if ($max_price) {
    
        if (!is_numeric($max_price)) {
           $error_message = "Please enter a valid maximum price. <br>";
        }
    }
    
    if ($min_price) {
    
        if (!is_numeric($min_price)) {
           $error_message = "Please enter a valid minimum price. <br>";
        }
    }
    
    /*Regular expression for search input. We want to at least limit the search to alphanumeric characters
    and common symbols. */
    $valid_search = "^[A-Za-z0-9- '\?\!\.\:\;\,\@\s]+$";

    /*The search field is also optional, but if it is filled in, the validate the input*/
    if ($searchstring) {
     
         /*Search input should not exceed 50 characters*/
         if (strlen($searchstring) > 50) {
            $error_message  =  "Search may not exceed 50 characters.  <br>";
         }
    
         else if (!(valid_input($searchstring, $valid_search))) {
           $error_message =  "Please enter a valid keyword. <br> ";
         }
    }
    
    
    //If everything passed validation, proceed with searching in the database
    if (!$error_message) {
    
         /*Filter by keyword. An empty keyword search will result in whatever the category was set to.*/
         if ($searchstring) {
             $filter_by_keyword =  " WHERE (upper(product_name) LIKE '%" . $db->real_escape_string($searchstring) ."%' " . 
                   "OR upper(description) LIKE '%" . $db->real_escape_string($searchstring) ."%') ";
         }
         else {
             $filter_by_keyword =  " WHERE product_name IS NOT NULL " ;
         }
         
    
    
        /*Filter by price. User may enter either a minimum price or a maximum price, 
        or both. If both are enter use the "between" clause. */
        if ($min_price && $max_price) {
           $filter_by_price = "AND price BETWEEN " .  $min_price . " AND " . $max_price . " ";
        }
        else if ($min_price) {
           $filter_by_price = "AND price > " .  $min_price . " ";
        }
        else if ($max_price)  {
           $filter_by_price = "AND price < " .  $max_price . " ";
        }
        else {
           $filter_by_price = "";
        }
    
        /*Filter by category. If "all" is selected, return all results. Otherwise, 
        just return the results that match the selected category */
        if ($_GET['category'] == "all"){
            $category = $_GET['category'];
            $filter_by_category = "";
        }
        else {
            $filter_by_category = "AND category = '" . $db->real_escape_string($_GET['category']) . "' ";
        }
              
        //Search the product_catalog table for matching items, and place the results in an array
        $command = "SELECT DISTINCT product_id, product_name, description, UNIX_TIMESTAMP(date_posted) as date_posted, " . 
                   "price FROM product_catalog " .
                   $filter_by_keyword . " " .
                   $filter_by_category . " " .
                   $filter_by_price . " " .
                   "AND date_sold <= 0 AND date_deleted <= 0 " .
                   "ORDER BY date_posted DESC; ";
                                         
        $result = $db->query($command);
        
        $search_array = array(); 
        
        if ($result->num_rows > 0) {
          
           while ($data_array = $result->fetch_assoc()) {
              array_push($search_array, $data_array);
           }
                      
           $search_count = count($search_array);
           
           if ($search_count == 1) {
              $matches_found = $search_count . " match was found. <br>";
           }
           else {
              $matches_found = $search_count . " matches were found. <br>";
           }
        }
        
        else {
           $matches_found = "No matches were found.<br>";
        }
    }
    else {
        $matches_found = "No matches were found.<br>";
    }
}


//Include header
include("../erikslist_include/erikslist_header.inc");


#################
#               #
#  Main Page    #
#               #
#################

//Many GET parameters can be used in this page, as it acts as a single page application. Process any searches here
if (!$_GET || isset($_GET['searchstring']) || isset($_GET['page']) || isset($_GET['sort_by']) || isset($_GET['tag']) || isset($_GET['category'])) {
?>

   <div class='search'>

      <form method="GET" action="">
        <table align="center">
	
        <tr>
               
          <td align="right">
            <input type="text" size="35" maxlength="50" name="searchstring" value="<? echo $_GET['searchstring']; ?>" placeholder="keyword">
          </td>
          
          
          <td align="right">
            Price:
          </td>
          <td>
            <input type="text" size="5" maxlength="10" name="min_price" value="<? echo $_GET['min_price']; ?>" placeholder="min">
          </td>
          <td>
            <input type="text" size="5" maxlength="10" name="max_price" value="<? echo $_GET['max_price']; ?>" placeholder="max">
          </td>

        <td align="right">
          Category:
         </td>
         <td align="left">
        <select name="category">
         <option value="all">all</option>
           <?
           //Cycle through the menu array and populate the categories
            foreach ($menu_array as $key => $value) { 

	     echo "<option value='" . $value . "'";
	   
                if ($_GET['category'] == $value)
		  { echo 'selected';} 
		echo ">" . $value . "</option>";

           }
         ?>
        </select>
        </td>

         <td colspan="2" align="left">
           <input type="submit" value="Search">
         </td>
       </tr>

       </table>
       </form>
       
       
       <?
       
       if ($error_message) {
          echo "<span style='color:red;font-size:12px;'>" . $error_message. "</span>";
       }
       ?>
       </span>
   </div>



     <?
     
      //Diplay search results if either category or searchstring exists as a GET variable
       if (isset($_GET['searchstring']) || isset($_GET['category'])) {


	if ($search_count > 0) {
	 echo "<div class='post'>";
         echo "<fieldset>";
         echo "<legend>Results:</legend>";
         echo "<div class='browse'>";
      
         //Display search result count
         if ($matches_found > 0) {
            echo "<div class='page_nav'>";
            echo "<span>". $matches_found . "</span>";
                      
            echo "<span style='float:right;margin-top:-15px;'><a href='catalog.php'>Return to Classifieds</a></span>";        
                      
                      
            echo "</div>";
          }


          //Cycle through each of the posts and create a list in descending order
           echo "<ul style='list-style-type:none;'>";
              while (list($key, $this_post) = each($search_array)) {
      
                echo "<br><li>";
                echo "<a href='catalog.php?product_id=" . $this_post['product_id'] .  "'/>" . $this_post['product_name'] . "</a>  " ;
                echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted on: " . date("M j, Y", $this_post['date_posted']) . "</span><br/>";
                echo "Description: " . substr($this_post['description'], 0,80) . "...<br>";
                echo "Price: $" . number_format($this_post['price'], 2) . "<br>";
                echo "<br><hr>"; 
             } 
    
          echo "</ul>"; 
          echo "</div>";
          echo "</fieldset>";
          
          
          
          echo "</div>";
        }
       else {
	 echo "<div class='post'>";
           echo "<fieldset>";
            echo "<legend>Results:</legend>";
             echo "<div class='browse'>";
            echo "<span style='color:black;font-size:12px;'>" . $matches_found . "</span>";
            echo "<span style='float:right;margin-top:-15px;'><a style='color:black;' href='catalog.php'>Return to Classifieds</a></span>";        
          echo "</div>";
         echo "</div>";
        
        }
       }
      
      
      //If searched by tag...
       else if ($_GET['tag']) {

      $tag = htmlentities($_GET['tag']);

         /*Use the fetch_this_tag function to retreive all bookmarks by the tag name and display them in pages of 20 each*/
          $tag_array = fetch_this_tag($tag, $db, $page, null);
          $tag_array_all = fetch_this_tag($tag, $db, $page, "all");

          $array_count = count($tag_array);
          $array_count_all = count($tag_array_all);
         

	 if ($array_count > 0) {
	 echo "<div class='post'>";
         echo "<fieldset>";
         echo "<legend>Posts tagged as \"" .$tag . "\":</legend>";
         echo "<div class='browse'>";
      


        /*Find the total number of pages by dividing the total count of the array by 5 and rounding up. Display page navigation. */
        $page_count = ceil($array_count_all / 5);
        if ($page > $page_count) {
          $page = 1;
        }
        
        echo "<div class='page_nav'>";
        if ($page <= 1) {
          ?><span>&#10094;&#10094;  previous |</span><?
        }
        else {
          ?><span><a href="catalog.php?tag=<? echo $tag; ?>&page=<? echo ($page - 1); ?>">&#10094;&#10094; previous</a> |</span><?
        }
        if ($array_count < 5 || $page == $page_count) {
          ?><span> next &#10095;&#10095; <?
        }
        else {
          ?><span><a href="catalog.php?tag=<? echo $tag; ?>&page=<? echo ($page + 1); ?>"> next &#10095;&#10095;  </a></span><?
        }
    

       echo "<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; page " . $page . " out of " .  $page_count . "</span>";

       
       echo "</div>";
      
          //Cycle through each of the posts and create a list in descending order
           echo "<ul style='list-style-type:none;'>";
              while (list($key, $this_post) = each($tag_array)) {
      
                echo "<br><li>";
                echo "<a href='catalog.php?product_id=" . $this_post['product_id'] .  "'/>" . $this_post['product_name'] . "</a>  " ;
                echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted on: " . date("M j, Y", $this_post['date_posted']) . "</span><br/>";
                echo "Description: " . substr($this_post['description'], 0,80) . "...<br>";
                echo "Price: $" . number_format($this_post['price'], 2) . "<br>";
                echo "<br><hr>"; 
             } 
    

    
          echo "</ul>"; 
          echo "</div>";
          echo "</fieldset>";
                    
          echo "</div>";
        }
        
        //If no search results were found by this tag name..
       else {
	 echo "<div class='post'>";
           echo "<fieldset>";
            echo "<legend>Posts tagged as \"" .$tag . "\":</legend>";
             echo "<div class='browse'>";
            echo "<span style='color:black;font-size:12px;'>No posts are associated with this tag</span>";
            echo "<span style='float:right;margin-top:-5px;'><a style='color:black;' href='catalog.php'>Return to Classifieds</a></span>";        
          echo "</div>";
         echo "</div>";
        
        }
       }
      
      //If no search was specified, display the catalog, along with an option to sort by price, date, etc..
      else {
      
       echo "<div class='post'>";
       echo "<fieldset>";
       echo "<div class='browse'>";
      
       $catalog_array = fetch_catalog($sort, $db, $page, null);
       $array_count = count($catalog_array);
    
       $all_catalog_array = fetch_catalog($sort, $db, $page, "all");
       $array_count_all = count($all_catalog_array);
      
        /*Find the total number of pages by dividing the total count of the array by 5 and rounding up. */
        $page_count = ceil($array_count_all / 10);
        if ($page > $page_count) {
        $page = 1;
        }
        
        echo "<div class='page_nav'>";
        if ($page <= 1) {
          ?><span>&#10094;&#10094;  previous |</span><?
        }
        else {
          ?><span><a href="catalog.php?page=<? echo ($page - 1); ?>">&#10094;&#10094; previous</a> |</span><?
        }
        if ($array_count < 10 || $page == $page_count) {
          ?><span> next &#10095;&#10095; <?
        }
        else {
          ?><span><a href="catalog.php?page=<? echo ($page + 1); ?>"> next &#10095;&#10095;  </a></span><?
        }
    
       
       echo "<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; page " . $page . " out of " .  $page_count . "</span>";
       
       //Allow users to sort the info by price, post date, etc.
       
       ?>
       <form action="" method="GET">
        <table align="right" style="margin-top:-25px;">
         <td align="left">
          <select name="sort_by">
            <option value="post_date" <?if ($_GET['sort_by'] == "post_date") {echo "selected";} ?>>Date</option>
            <option value="price_asc" <?if ($_GET['sort_by'] == "price_asc") {echo "selected";} ?>>Price: high to low</option>
            <option value="price_desc" <?if ($_GET['sort_by'] == "price_desc") {echo "selected";} ?>>Price: low to high</option>
         </select>
       </td>
       
       <td width="50">
          <input class="default" type="submit" value="Sort">
       </td>
       </tr>
       
       </table>
       </form>
       <?
       
       
       
       
       echo "</div>";
      
          //Cycle through each of the posts and create a list in descending order
           echo "<ul style='list-style-type:none;'>";
              while (list($key, $this_post) = each($catalog_array)) {
      
                echo "<br><li>";
                echo "<a href='catalog.php?product_id=" . $this_post['product_id'] .  "'/>" . $this_post['product_name'] . "</a>  " ;
                echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted on: " . date("M j, Y", $this_post['date_posted']) . "</span><br/>";
                echo "Description: " . substr($this_post['description'], 0,80) . "...<br>";
                echo "Price: $" . number_format($this_post['price'], 2) . "<br>";
                echo "<br><hr>"; 
             } 
    
          echo "</ul>"; 
      
       echo "</div>";
       echo "</fieldset>";
       echo "</div>";
      
      }
}

#################
#               #
#  Diplay Post  #
#               #
#################

//This section displays a particular post rather than search results. 
else if ($_GET['product_id'] || isset($_GET['flag_post'])) {

?>


<div class='post'>

<h3>&nbsp;<span style="float:right;"><a class="button" href="<? echo $_SESSION['back']; ?>">Previous Page</a></span></h3>
<fieldset>
<div class='browse'>

<?
  $product_id = $_GET['product_id'];

  //Retrieve the post and its image for display
  $post_array = fetch_post_detail($product_id, $db);
  $post_image = fetch_post_image($product_id, $db);
  $tag_array = fetch_tags($product_id, $db);
  $tag_string = implode(" ", $tag_array);
    
    //Cycle through each of the posts and create a list in descending order

    while (list($key, $this_post) = each($post_array)) {
      
        echo "<br>";
        echo "<h2>" . $this_post['product_name'] . "</h2>";
        
        $posted_days_ago = ceil(abs(($this_post['date_posted'] - time())) / ( 24 * 60 * 60 ));
        
        if (abs(($this_post['date_posted'] - time())) / ( 24 * 60 * 60 ) < '1') {
              echo "<span class='info'>posted today </span><br/>";
        }
        
        else if ($posted_days_ago == '1') {
           echo "<span class='info'>posted " . $posted_days_ago  . " day ago</span><br/>";
        }
        else if ($posted_days_ago > '30') {
           echo " <span class='info'>posted on: " . date("M j, Y", $this_post['date_posted']) . "</span><br/>";
        }
        else {
           echo "<span class='info'>posted " . $posted_days_ago  . " days ago</span><br/>";
        }
                
        echo "<p>Category: " . $this_post['category'] . "</p>";
        
        echo "<p>Condition: " . $this_post['product_condition'] . "</p>";
        
        echo "<p>Price: $" . number_format($this_post['price'],2) . "</p>";
        
        echo "<p>Postal Code: " . $this_post['postal_code'] . "</p>";
        
        echo "<p>Description: " . $this_post['description'] . "</p>";
        
               
        if ($post_image) {
           echo "<br><img class='preview' src='product_images/" . $post_image . "' alt='image' /><br>";
        }
        else {
           echo "No images uploaded";
        }
             
      echo "<p>Posted by: <a href='profile.php?profileID=" . $this_post['member_id'] ."'>" . $this_post['login'] . "</a></p>"; 
      
      echo "<p>Post ID: " . $this_post['product_id'] . "</p>"; 
      
      echo "<p>Tags: ";
      
            //Cycle through the tags array and display them as links to be followed
            if (count($tag_array > 0)) {
      
                foreach ($tag_array as $key => $value) { 

	            echo "  <span><a class='tags' href='catalog.php?tag=" . $value . "'>" . $value . "</a></span>  ";

                }
             }
             else {
                 echo "none";
             }
      
      
       echo "</p><br/>";
      
               
      if ($success_message) {
              echo  "<p><span style='color:#191970;font-size:12px;'>" . $success_message . "</span></p>";
      }          
      

      

      
      /*If the user is browsing the site as a visitor who has not signed in, do not display the "contact seller"
      button". This will help to regulate correspondence. If, however, the member is signed in, display an option
      to contact the seller. When clicked, a small form will appear at the bottom of the page with contact 
      information pre-filled. Once submitted, an email will be sent to the seller containing contact info
      and the message. Similar to contacting the seller, the user may also recommend a post to a friend. */
      if  (!$member_id && !$_GET['recommend']) {
      
            echo "<br><br><span class='info'>Please sign in to contact this seller.</span>";
            
            if (is_numeric($_GET['flag_post']) && $_GET['flag_post'] == $_GET['product_id']) {
               
                echo $flag_message;
      
            }
            else {
                 echo "<span class='action'><a class='last' href='catalog.php?product_id=". $this_post['product_id'] ."&flag_post=" . 
                 $this_post['product_id'] . "'>report this post</a></span>";
                 echo "<span class='action'><a class='next' href='catalog.php?product_id=". $this_post['product_id'] ."&recommend=" . 
                 $this_post['product_id'] . "'>recommend this post to a friend</a></span>";
            }
            

      }
      
      else if  ($member_id && $member_id == $this_post['member_id'] && !$_GET['recommend']) {
      
            echo "<br><br><span class='info'>You have posted this item. " . 
                 "Click<a href='profile.php?edit_post=" . $this_post['product_id']."'> here </a> to edit.</span>";
            echo "<span class='action'><a class='last' href='catalog.php?product_id=". $this_post['product_id'] ."&recommend=" . 
                 $this_post['product_id'] . "'>recommend this post to a friend</a></span>";
                 
                 
      }

      else if ($member_id && $member_id != $this_post['member_id'] && !$_GET['contact'] && !$_GET['recommend']) {
       
          echo "<a class='button' href='catalog.php?product_id=". $this_post['product_id'] ."&contact=true'> Contact Seller</a>";
          
            if (is_numeric($_GET['flag_post']) && $_GET['flag_post'] == $_GET['product_id']) {
               
                echo $flag_message;
      
            }
            
            else {
                 echo "<span class='action'><a class='last' href='catalog.php?product_id=". $this_post['product_id'] ."&flag_post=" . 
                 $this_post['product_id'] . "'>report this post</a></span>";
                 echo "<span class='action'><a class='next' href='catalog.php?product_id=". $this_post['product_id'] ."&recommend=" . 
                 $this_post['product_id'] . "'>recommend this post to a friend</a></span>";
            }
      }
      
      else if ($member_id != $this_post['member_id'] && $_GET['contact'] && !$_GET['recommend']){
      
          ?>
          <fieldset>
          <legend>Contact Seller</legend>
          <form action="" method="POST">
            <table>
            
               <tr>
                 <td>&nbsp;</td>
                 <td colspan="3" align="left">
                <span style="color:red;font-size:12px;">
               <?
                //Display error / success messages here
                if ($error_message) {
                   echo $error_message . "<br>";
                }
                ?>
                </span>
               </td>
             </tr>
                       
          <tr>
           <td align="right">
              From:
           </td>
           <td align="left" colspan="3">
              <input type="text" size="50" maxlength="50" value="<?  echo $_SESSION['email']; ?>" disabled>
           </td>
           </tr>
            
            <tr>
             <td align="right">
                Subject:
            </td>
           <td align="left" colspan="3">
             <input type="text" size="50" maxlength="50" value="<? echo 'EriksList Post Inquiry: ' . $this_post['product_name']; ?>" disabled>
           </td>


          <tr>
           <td align="right" valign="top" colspan="1">
               Message:
           </td>
           <td align="left" colspan="3">
               <textarea rows="5" cols="61" maxlength="500" name="message" value="<? echo $_POST['message']; ?>"><? echo $_POST['message']; ?></textarea>
          </td>
        </tr>
        
        <tr>
          <td>&nbsp;</td>
          <td colspan="3" align="left"><span class='info'>Please limit messase to 500 characters.</span></td>
       </tr>
        
      <tr>
       <td width="25%">&nbsp;</td>
       <td width="25%">&nbsp;</td>
       <td align="center" width="25%"><a class="button" href="catalog.php?product_id=<? echo $this_post['product_id']; ?>">Cancel</a></td>
       <td align="right" width="25%">

          <input type="hidden" name="from_email" value="<?  echo $_SESSION['email']; ?>">
          <input type="hidden" name="from_login" value="<?  echo $_SESSION['member_login']; ?>">
          <input type="hidden" name="sender_id" value="<? echo $_SESSION['member_id']; ?>">
          <input type="hidden" name="to" value="<? echo $this_post['email']; ?>">
          <input type="hidden" name="subject" value="<? echo 'EriksList Post Inquiry: ' . $this_post['product_name']; ?>">
          <input type="hidden" name="product_id" value="<? echo $this_post['product_id']; ?>">
          <input type="hidden" name="product_name" value="<? echo  $this_post['product_name']; ?>">
                   
          <input type="submit" name="send_contact" value="Send">
          
      </td>
      </tr>
        
          </table>
          </form>
          </fieldset>
          <?
      
      }


      else if ($_GET['recommend']){
      
          ?>
          <div id=recommend>
          <fieldset>
          <legend>Recommend this post to a friend</legend>
          <form action="" method="POST">
            <table>
            
               <tr>
                 <td>&nbsp;</td>
                 <td colspan="3" align="left">
                <span style="color:red;font-size:12px;">
               <?
                //Display error / success messages here
                if ($error_message) {
                   echo $error_message . "<br>";
                }
                ?>
                </span>
               </td>
             </tr>
                       
          <tr>
           <td align="right">
              From:
           </td>
           <td align="left" colspan="3">
              <input type="text" size="50" name="from" maxlength="50" value="<?  echo $_POST['from']; ?>" >
           </td>
           </tr>
            
          <tr>
           <td align="right">
              To:
           </td>
           <td align="left" colspan="3">
              <input type="text" size="50" name="to" maxlength="50" value="<?  echo $_POST['to']; ?>" >
           </td>
           </tr>
            
            <tr>
             <td align="right">
                Subject:
            </td>
           <td align="left" colspan="3">
             <input type="text" size="50" maxlength="50" value="<? echo 'EriksList Recommendation: ' . $this_post['product_name']; ?>" disabled>
           </td>


          <tr>
           <td align="right" valign="top" colspan="1">
               Message:
           </td>
           <td align="left" colspan="3">
               <textarea rows="5" cols="61" maxlength="500" name="message" value="<? echo $_POST['message']; ?>"><? echo $_POST['message']; ?></textarea>
          </td>
        </tr>
        
        <tr>
          <td>&nbsp;</td>
          <td colspan="3" align="left"><span class='info'>Please limit messase to 500 characters.</span></td>
       </tr>
        
      <tr>
       <td width="25%">&nbsp;</td>
       <td width="25%">&nbsp;</td>
       <td align="center" width="25%"><a class="button" href="catalog.php?product_id=<? echo $this_post['product_id']; ?>">Cancel</a></td>
       <td align="right" width="25%">

          <input type="hidden" name="subject" value="<? echo 'EriksList Post Inquiry: ' . $this_post['product_name']; ?>">
          <input type="hidden" name="product_id" value="<? echo $this_post['product_id']; ?>">
          <input type="hidden" name="product_name" value="<? echo  $this_post['product_name']; ?>">
                   
          <input type="submit" name="send_recommendation" value="Send">
          
      </td>
      </tr>
        
          </table>
          </form>
          </fieldset>
          </div>
          <?
      
      }

      echo "<br>"; 
    } 
    
echo "</div>";
echo "</fieldset></div>";
    
}



//Include footer
include("../erikslist_include/erikslist_footer.inc");

/*
echo "GET array: <br>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "Search array: <br>";
echo "<pre>";
print_r($search_array);
echo "</pre>";

echo "Product post array: <br>";
echo "<pre>";
print_r($post_array);
echo "</pre>";

echo "Post array: <br>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "Session array: <br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
*/

$db->close();

?>