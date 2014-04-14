<?php

$navigation_flag = $_SESSION['navigation'];

/* This function will test the parameters to see which which page is currently active. 
The active page will be detected by the server variable PHP_SELF, and then set as a session
variable. The session variable will then become the navigation flag, which will be tested
against the navigation title. */
function print_navigation($nav_flag, $nav_title, $nav_url, $position) {

  /*Find out the active navigation title. */
  if ($nav_title == "Home") {
      $action_title = "index.php";
  }
  else if ($nav_title == "Post to Classifieds") {
      $action_title = "post.php";
  }
  else if ($nav_title == "My Account") {
     
     //User is on their own profile page
     if (!$_GET['profileID']) {
           $action_title = "profile.php";
     }
     //User is on another member's profile page
     else if ($_GET['profileID'] && $_GET['profileID']!= $_SESSION['member_id']){
          $action_title = "";
     }
     else {
          $action_title = "profile.php";
     }
  } 
  else if ($nav_title == "Browse Classifieds") {
      $action_title = "catalog.php";
  } 
  
  else if ($nav_title == "Sign In") {
      $action_title = "login.php";
  } 
  

  if (filter_var("http://" . $nav_url, FILTER_VALIDATE_URL)) {
    
    if ($position == "last") {
       $class_name = "last";
    }
    else if ($position == "first") {
       $class_name = "first";
    }
    else {
       $class_name = "nav";
    }
    
    /*Test whether the navigation flag is equivalent to the navigation
    title (or the temporary action title). If so, print bolded text. 
    Otherwise, print a link to those pages.*/
    if ($nav_flag == $nav_title || $nav_flag == $action_title) {
       echo "<b class='" . $class_name . "'>"; 
       echo htmlentities($nav_title); 
       echo "</b>";
    }
    else {
       echo "<a class='" . $class_name. "' href='" . $nav_url . "'>";
       echo htmlentities($nav_title);
       echo "</a>";
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>eriks list</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="erikslist">
  <meta name="author" content="Erik Johnson">
  <link type="text/css" rel="stylesheet" rev="stylesheet" href="css/erikslist.css" />
  <link type="text/css" rel="stylesheet" href="js/jquery-ui-1.10.4.custom.css">
  <script src="js/jquery-1.10.2.js"></script>
  <script src="js/jquery-ui-1.10.4.custom.js"></script>
  <script>
   $(document).ready(function() {
     $('#datepicker').datepicker();
   });
  </script>
  
</head>
<body>
<div id="page">
	<div id="header">
		<div class="top">
                    <table style="width:100%;">
                    <tr>
		        <td rowspan=2 style="padding-right:10px;width:40%;">
		          <a href="index.php"><img style="margin-bottom:-10px;" src="images/logo.jpg" alt="erik's list" /></a>
                        </td>
                        
                        <td rowspan=2 valign="bottom" style="padding-left:10px;align:left;">
		             <i style="font-size:20px;color:#BAF395;text-shadow:-0.5px -0.5px 1px #696969;"> knoxville's source for local classified ads</i>
                        </td>
                        
                        <td rowspan=2  valign="bottom" style="padding-left:10px;align:right;">
                           <span style="float:right;color:#696969;"><?php echo date("F jS, Y");?></span>
                        </td>
	            </tr>
                    
                    <tr>
                    </table>



		</div>
		<div class="bottom">
			<div class="inside">
		            <table style="width:100%;">
		              <tr>
				<td align="left">
				    <?
				    /*Display the navigation bar using a function to identify the user's location */
                                   
                                     print_navigation($navigation_flag, "Home", "index.php", "first");
                                     
                                     if (isset($_SESSION['member_id'])) {
                                       print_navigation($navigation_flag, "Post to Classifieds", "post.php", "nav"); 
                                     }
                                     print_navigation($navigation_flag, "Browse Classifieds", "catalog.php", "last"); 
				  	  
				    ?>
				
                                </td>
                                <td align="right" valign="top">
                                
                                <?
                                //If the user is signed in, display the member login, a link to 
                                //the member's profile, and an option to logout.
                                 if (isset($_SESSION['member_id'])) {
				   echo "<span class='first'>Logged in as <b>" . htmlentities($member_login) . "</b></span>";
                                   print_navigation($navigation_flag, "My Account", "profile.php", "nav"); 
				   echo "<a class='last' href='logout.php'>Logout</a>";	
		                 }
		                 
		                 else {
                                  print_navigation($navigation_flag, "Sign In", "login.php", "last"); 
		                 }    
		                ?>   
				</td>
		               </tr>
		           </table>
			</div>
		</div>
	</div><!-- end of header -->
	
        <div class="clear_both"></div>
    
	<div id="content"><!-- begin content section -->
	


