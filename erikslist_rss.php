<?php
//erikslist_rss.php - retreives the most recent 20 postings in rss format

header("Content-Type: application/rss+xml; charset=ISO-8859-1");

include("erikslist_public.inc");

$db = public_db_connect();

     
$rssfeed = '<?xml version="1.0" encoding="ISO-8859-1"?>';
$rssfeed .= '<rss version="2.0">';
$rssfeed .= '<channel>';
$rssfeed .= '<title>Eriks List RSS</title>';
$rssfeed .= '<link>http://ejohnson4.userworld.com/erikslist/index.php</link>';
$rssfeed .= '<description>Knoxvilles finest site for sharing classified ads. Subscribe to receive our latest postings!</description>';

//Retrieve top 20 latest postings for erikslist members.
$command = "SELECT product_id, product_name, " . 
           "UNIX_TIMESTAMP(date_posted) AS date_posted, " . 
           "description, price, product_condition, category " . 
           "FROM product_catalog " . 
           "WHERE date_deleted <= 0 AND date_sold <= 0 ORDER BY date_posted DESC LIMIT 20;";
              
      $result = $db->query($command);
       
      $rss_array = array();
      while ($this_post_array = $result->fetch_assoc()) {
           array_push($rss_array, $this_post_array);
      }
      
      
      while (list($key, $this_post) = each($rss_array)) {
           
      $rssfeed .= "<item>";
      $rssfeed .="<title>" . $this_post['product_name'] . "</title>";
      $rssfeed .="<link>http://ejohnson4.userworld.com/erikslist/catalog.php?product_id=" . $this_post['product_id'] . "</link>";
      $rssfeed .="<pubDate>" . date('D, d M Y H:i:s O', $this_post['date_posted']) . "</pubDate>";
      $rssfeed .="<description>" . $this_post['description'] . "</description>";
      $rssfeed .="</item>";
      
      }

    $rssfeed .= '</channel>';
    $rssfeed .= '</rss>';
 
   echo $rssfeed;

?>