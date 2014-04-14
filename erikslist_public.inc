<?php
//erikslist_public.inc -- a public library of functions for erikslist

//returns a database handle which allows ONLY SELECT permissions on certain erikslist tables
function public_db_connect() {


    $host = "localhost";
    $socket = "/users/ejohnson4/mysql-php/data/mysql.sock";      
    $port = 0;
    $user = "el_public";
    $pw = "public123";
    $database = "erikslist";
    
    $db = new mysqli($host,$user,$pw,$database,$port,$socket) 
       or die("Cannot connect to mySQL.");
       
    return $db;
}

//a function to check inputs against a regular expression representing the proper input format.
function valid_input($myinput, $good_input) {
   
   if (preg_match("/$good_input/", $myinput, $regs)) {
       return true;
   }
   else {
       return false;
   }
}

//Retrieve the profile for a member based on their profile ID
function fetch_profile($profileID, $db) {
    $profile_array = array();
    if (is_numeric($profileID)) {
      
      //make sure that the profile exists
      $command = "SELECT mi.member_id, mi.email, mi.first_name, mi.last_name, UNIX_TIMESTAMP(mi.date_enrolled) as date_enrolled, ml.login " . 
                 "FROM member_info mi, member_login ml WHERE mi.member_id = ml.member_id " . 
                 "AND mi.date_deactivated <= 0 AND mi.member_id = '". $db->real_escape_string($profileID) ."';";
      $result = $db->query($command);
           
      if ($result && $result->num_rows > 0) {
         $profile_array = $result->fetch_assoc();
         
      }
    }
    return $profile_array;
}

//Retrieve the posts for a member's profile, and allows for sorting 
function fetch_my_posts($profileID, $sort_by, $db) {
     $posts_array = array();
     
     if (is_numeric($profileID)) {
      
      $command = "SELECT pc.product_id, pc.product_name, pc.category, pc.description, pc.product_condition, pc.price, " . 
                 "UNIX_TIMESTAMP(pc.date_posted) as date_posted, UNIX_TIMESTAMP(pc.date_sold) as date_sold, ml.login " . 
                 "FROM product_catalog pc " . 
                 "INNER JOIN member_login ml ON ml.member_id = pc.member_id " . 
                 "WHERE ml.member_id = '". $db->real_escape_string($profileID) ."' " . 
                 "AND pc.date_deleted <= 0 " ;
                 
                 
      if ($sort_by == "post_date") {
         $command .= "ORDER BY date_posted DESC;"; 
      }          
      else if ($sort_by == "sold_only") {
           $command .= "AND date_sold > 0 ORDER BY date_sold;"; 
      }         
      else if ($sort_by == "unsold_only") {
           $command .= "AND date_sold <= 0 ORDER BY date_sold;"; 
      }   
      else if ($sort_by == "alpha_asc") {
           $command .= "ORDER BY pc.product_name ASC;"; 
      }   
      else if ($sort_by == "alpha_desc") {
           $command .= "ORDER BY pc.product_name DESC;"; 
      }   
      else {
           $command .= "ORDER BY date_posted DESC; "; 
      }                 
                               
      $result = $db->query($command);
           
      if ($result && $result->num_rows > 0) {
      
        while ($this_post_array = $result->fetch_assoc()) {
          array_push($posts_array, $this_post_array);
        }
      }
    }
      return $posts_array; 
}

//Retrieve a single post for editing by its product_id
function fetch_post($profileID, $product_id, $db) {
     $post_array = array();
     
     if (is_numeric($product_id)) {
      
      $command = "SELECT pc.member_id, pc.product_id, pc.product_name, pc.category, pc.description, " . 
                 "pc.product_condition, pc.postal_code, pc.price, UNIX_TIMESTAMP(pc.date_posted) as date_posted, ".
                 "pc.product_condition, ml.login " . 
                 "FROM product_catalog pc " . 
                 "INNER JOIN member_login ml ON ml.member_id = pc.member_id " . 
                 "WHERE pc.member_id = '". $db->real_escape_string($profileID) ."' " .
                 "AND pc.product_id = '". $db->real_escape_string($product_id) ."';";
           
      $result = $db->query($command);
           
      if ($result && $result->num_rows > 0) {
         $post_array = $result->fetch_assoc();
         
      }
    }
      return $post_array; 
}

//Retrieve a particular post in detail (rather than just including its basic information) by its product ID
function fetch_post_detail($product_id, $db) {
     $posts_array = array();
     
     if (is_numeric($product_id)) {
      
      $command = "SELECT pc.product_id, pc.member_id, pc.product_name, pc.category, pc.description, pc.product_condition, " . 
                 "pc.price, UNIX_TIMESTAMP(pc.date_posted) as date_posted, pc.postal_code, ml.login, mi.email FROM product_catalog pc " . 
                 "INNER JOIN member_login ml ON ml.member_id = pc.member_id " . 
                 "LEFT OUTER JOIN member_info mi ON ml.member_id = mi.member_id " . 
                 "WHERE pc.product_id = '". $db->real_escape_string($product_id) ."' " . 
                 "AND pc.date_deleted <= 0 ORDER BY date_posted DESC;";
      
      $result = $db->query($command);
           
      if ($result && $result->num_rows > 0) {
      
        while ($this_post_array = $result->fetch_assoc()) {
          array_push($posts_array, $this_post_array);
        }
      }
    }
      return $posts_array; 
}

//Retrieve a single post's image for editing by its product_id
function fetch_post_image($product_id, $db) {
     
     if (is_numeric($product_id)) {
      
      $command = "SELECT image FROM product_images " . 
                 "WHERE product_id = '". $db->real_escape_string($product_id) ."' " .
                 "AND date_deleted <=0 LIMIT 1;";
           
      $result = $db->query($command);
                
         if ($data = $result->fetch_object()) {
            $posted_image = $data->image;
           
         }
    }
      return $posted_image; 
}


//Remove a post if deleted by a member
function remove_post($member_id, $db, $remove_id) {

   if (is_numeric($remove_id)) {
   
      $command = "UPDATE product_catalog SET date_deleted = now() WHERE " . 
                 "product_id = '". $db->real_escape_string($remove_id) ."' " . 
                 "AND member_id = '". $db->real_escape_string($member_id) ."';";
                 
      $result = $db->query($command); 
      
      if ($result == true) {
         return true;
       }
       else {
         return false;
       }         
   }
}

//Remove an image associated with a post
function delete_post_image($product_id, $db) {

   if (is_numeric($product_id)) {

       $command = "UPDATE product_images SET date_deleted = now() " . 
                  "WHERE product_id = '".  $db->real_escape_string($product_id) ."';";
                        
       $result = $db->query($command);
   
   }
}

//Retrieve all products from the catalog for display that have not been flagged by more than 10 users
function fetch_catalog($sort, $db, $page, $option, $member_id ='') {

   $catalog_array = array();
   $ip_address = getenv("REMOTE_ADDR");

   $command = "SELECT pc.member_id, pc.product_id, pc.product_name, pc.category, pc.description, " . 
              "pc.product_condition, pc.postal_code, pc.price, UNIX_TIMESTAMP(pc.date_posted) as date_posted, ".
              "pc.product_condition, ml.login, " . 
              "count(fp.product_id) AS flag_count " . 
              "FROM product_catalog pc " . 
              "INNER JOIN member_login ml ON ml.member_id = pc.member_id " . 
              "LEFT OUTER JOIN flagged_posts fp ON pc.product_id = fp.product_id ".
              "WHERE (fp.product_id IS NULL OR " . 
              " !(fp.member_id='". $db->real_escape_string($member_id) . "' "  .
              "OR fp.ip_address='" . $db->real_escape_string($ip_address) . "')) " .
              "AND date_sold <= 0 AND date_deleted <= 0 " .
              "GROUP BY pc.product_id ";
              
    if ($sort == "price_desc") {
         $command .= "ORDER BY pc.price ASC"; 
    }          
    else if ($sort == "price_asc") {
         $command .= "ORDER BY pc.price DESC "; 
    }         
    else if ($sort == "post_date") {
         $command .= "ORDER BY date_posted DESC "; 
    }   
    else {
         $command .= "ORDER BY date_posted DESC "; 
    }                 
              
   
   /*In order to get a count of all the posts, check for the "all" option. If set, then get an array
   of the most recent 200 posts*/
   if ($option == "all") {
    $command .= " LIMIT 200;";
   }
   else if ($option == "recent") {
    $command .= " LIMIT 12;";
   
   }
   else {
     if (!(is_numeric($page)) || $page < 1) {
        $page = 1; 
     }
     $command .= " LIMIT " . (($page - 1) * 10) . ",10;";
   }
         
   
   $result = $db->query($command);
           
      if ($result && $result->num_rows > 0) {
      
        while ($this_post_array = $result->fetch_assoc()) {
          if ($this_post_array['flag_count'] < 10) {
             array_push($catalog_array, $this_post_array);
          }
        }
      }
      return $catalog_array; 
}

//Retrieve the count for each category to be displayed on the home page
function fetch_by_category($category, $db) {

   $category_array = array();
   $ip_address = getenv("REMOTE_ADDR");

   $command = "SELECT count(pc.product_id) as category_count, " . 
              "count(fp.product_id) AS flag_count " . 
              "FROM product_catalog pc " . 
              "LEFT OUTER JOIN flagged_posts fp ON pc.product_id = fp.product_id ".
              "WHERE (fp.product_id IS NULL OR " . 
              " !(fp.member_id='". $db->real_escape_string($member_id) . "' "  .
              "OR fp.ip_address='" . $db->real_escape_string($ip_address) . "')) " .
              "AND date_sold <= 0 AND date_deleted <= 0 " .
              "AND category = '" . $db->real_escape_string($category) . "' " . 
              "GROUP BY pc.product_id; ";
              
   
      $result = $db->query($command);
           
      if ($result && $result->num_rows > 0) {
      
        while ($this_post_array = $result->fetch_assoc()) {
          if ($this_post_array['flag_count'] < 10) {
             array_push($category_array, $this_post_array);
          }
        }
      }
      return $category_array; 
}


//Process a post being marked as "Sold" here
function mark_post_as_sold($member_id, $db, $sold_item) {

   if (is_numeric($sold_item)) {
   
      $command = "UPDATE product_catalog SET date_sold = now() WHERE " . 
                 "product_id = '". $db->real_escape_string($sold_item) ."' " . 
                 "AND member_id = '". $db->real_escape_string($member_id) ."';";
                 
      $result = $db->query($command); 
      
      if ($result == true) {
         return true;
       }
       else {
         return false;
       }         
   }
}


//function used to flag a post if deemed inappropriate
function flag_post($product_id, $db, $member_id = '') {
 
   /*Because we are allowing non-members to also flag posts, use their IP address
   in leiu of their member ID to identify them */
   $ip_address = getenv("REMOTE_ADDR");
   
   //First, check if there is an existing flag by this member or visitor's ip_address
   if (is_numeric($product_id)) {
       $command = "SELECT product_id FROM flagged_posts WHERE product_id = '". $product_id ."' ".
                  "AND (ip_address='". $db->real_escape_string($ip_address) ."'";
      if (intval($member_id) > 0) {
         $command .= " OR member_id='". $member_id ."'";
      }
      $command .= ");";
      
      $result = $db->query($command);
            
      //If no matches are found, create a new flag for that post     
      if ($result->num_rows <= 0) {

        $command = "INSERT INTO flagged_posts (product_id, member_id, ip_address, date_flagged) " . 
                   "VALUES ('". $product_id ."',  '". 
                    $db->real_escape_string($member_id) ."', '" . 
                    $db->real_escape_string($ip_address) ."', now());";
               
        $result = $db->query($command);
        
      }
   }
}

//This function can be used to retrieve all the tags for one particular post
function fetch_tags($product_id, $db) {

  $tag_array = array();
  
  if(is_numeric($product_id)) {
    $command = "SELECT DISTINCT pt.tag_id, et.tag FROM post_tags pt, erikslist_tags et " . 
               "WHERE pt.tag_id = et.tag_id " . 
               "AND pt.date_deleted <= 0 " .
               "AND pt.product_id ='" . $db->real_escape_string($product_id) ."'; ";
                     
    $result = $db->query($command);
    
    while ($this_tag_array = $result->fetch_assoc()) {
       array_push($tag_array, $this_tag_array['tag']);
    }
  }
  
  return $tag_array;
}


//This function will search for products by their tag name
function fetch_this_tag($tag, $db, $page, $option='') {

  $tag_array = array();
  if($tag) {
    $command = "SELECT pc.product_name, pc.product_id, pc.price, pc.description, " . 
               "UNIX_TIMESTAMP(pc.date_posted) as date_posted, pt.tag_id, et.tag FROM post_tags pt " . 
               "INNER JOIN erikslist_tags et ON pt.tag_id = et.tag_id " . 
               "LEFT OUTER JOIN product_catalog pc ON pc.product_id = pt.product_id " . 
               "WHERE et.tag = '" . $db->real_escape_string($tag) . "' " . 
               "AND pt.date_deleted <= 0 GROUP BY pt.product_id ORDER BY date_posted DESC ";
                     
   /*In order to get a count of all the posts, check for the "all" option. If set, then get an array
   of the first 100 posts */
   if ($option == "all") {
    $command .= " LIMIT 100;";
   }
   else {
     if (!(is_numeric($page)) || $page < 1) {
        $page = 1; 
     }
     $command .= " LIMIT " . (($page - 1) * 5) . ", 5;";
   }
                     
                     
    $result = $db->query($command);
       
    while ($this_tag_array = $result->fetch_assoc()) {
       array_push($tag_array, $this_tag_array);
    }
  }
  
  return $tag_array;
}


?>