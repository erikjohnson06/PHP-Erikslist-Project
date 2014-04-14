<?php
//index.php -- interface for the home page of erikslist

//Include utility files and public API
require("../erikslist_include/erikslist_utilities.inc");
include("erikslist_public.inc");

$db = public_db_connect();

//Start the session cookie
session_start();
$member_id = $_SESSION['member_id'];
$member_login = $_SESSION['member_login'];
$_SESSION['navigation'] = basename($_SERVER['PHP_SELF']);


//Include header
include("../erikslist_include/erikslist_header.inc");

?>

<div class="home_navigation">


  <?
    //If user is not logged in, provide a link to sign in or join
   if (!$member_id) {
      echo "<table align='center'>";
        echo "<tr>";
          echo "<td colspan='2' height='50'>Not a member? Join today!</td>";
        echo "</tr>";
        echo "<tr>";
          echo "<td align='center'><a class='button' href='login.php'>Sign In</a></td>";
          echo "<td align='center'><a class='button' href='register.php'>Join</a></td>" ;
        echo "</tr>";
      echo "</table>";
        echo "<br><br>";
      
   }

  ?>


  <fieldset>
  <legend>Quick Search</legend>
      <form method="GET" action="catalog.php">
        <table align="center">
	
        <tr>
               
          <td align="right">
            <input type="text" size="20" maxlength="50" name="searchstring" value="<? echo $_GET['searchstring']; ?>" placeholder="keyword">
          </td>
          
        </tr>
        
        <tr>
         <td align="left">
         
           <input type="hidden" name="min_price" value="">
           <input type="hidden" name="max_price" value="">
           <input type="hidden" name="category" value="all">
           <input class="default" type="submit" value="Search">
           
         </td>
       </tr>   
       </table>
       </form>
       
  </fieldset>

  <br>
  
  <fieldset>
  <legend>Categories</legend>
    <?
      
     //Cycle through the category array, and display the number of items listed in that category
     foreach ($menu_array as $key => $value) {
        $this_category = fetch_by_category($value, $db);
      
        echo "<a href='catalog.php?category=" . $value . "'>" . $value . "  (" . count($this_category) . ")</a><br>";
      
     }
  
  ?>  
  </fieldset>

  
</div>

<div class="home">
  <fieldset>
  <legend>Latest Posts on Erik's List</legend>
  
  <?
    
    //Display latest posts 
    $sort_by = "post_date";  
    $catalog_array = fetch_catalog($sort_by, $db, 1, "recent");
    
           echo "<ul style='list-style-type:none;margin-top:-5px;margin-left:-25px;'>";
              while (list($key, $this_post) = each($catalog_array)) {
      
                echo "<br><li style='border-bottom:1px dashed #BAF395;margin-bottom:5px;'>";
                echo "<a href='catalog.php?product_id=" . $this_post['product_id'] .  "'/>" . $this_post['product_name'] . "</a>  <br/>" ;
                echo "<span class='info'>$" . $this_post['price'] . "</span>";
                
                $posted_days_ago = ceil(abs(($this_post['date_posted'] - time())) / ( 24 * 60 * 60 ));
        
                if (abs(($this_post['date_posted'] - time())) / ( 24 * 60 * 60 ) < '1') {
                  echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted today </span>";
                }
   
                else if ($posted_days_ago == '1') {
                  echo "&nbsp;&nbsp;&nbsp; <span class='info'>posted " . $posted_days_ago  . " day ago</span>";
                 }

                else if ($posted_days_ago > '30') {
                  echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted on: " . date("M j, Y", $this_post['date_posted']) . "</span>";
                }
                else {
                  echo "&nbsp;&nbsp;&nbsp; <span class='info'>posted " . $posted_days_ago  . " days ago</span>";
                }
                                
                echo "&nbsp;&nbsp;&nbsp;  <span class='info'>posted by <a href='profile.php?profileID=" . $this_post['member_id'] . "'>" . 
                $this_post['login'] . "</a></span>" ;
             } 
    
          echo "</ul>"; 
    
  
  
  ?>
  
  </fieldset>
</div>




<div class="tag_cloud">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="css/tag_styles.css" />

<?

//Include the tag_cloud file to display a list of tags for all of the posts
include("../erikslist_include/tag_cloud.inc");
$tag_array = fetch_all_tags($db);
$cloud = new wordCloud($tag_array);

echo $cloud->showCloud();



?>

</div>
<div class="clear_both"></div>

<?

//Include footer
include("../erikslist_include/erikslist_footer.inc");

?>