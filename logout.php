<?php

//logout.php -- removes session variables and redirects to index.php

session_start();
session_destroy();

//Include header
include("../erikslist_include/erikslist_header.inc");

header("Location: index.php");

//Include footer
include("../erikslist_include/erikslist_footer.inc");

?>