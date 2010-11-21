<?php
      /*
      Copyright (c) 2006-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   #  setup
   $ret = vpm_validateform("name", "Name", "/^[-a-z0-9+@._]+$/",
         1, 50, 1);
   $name = $ret[1];
   if ($ret[0] != "successful" ) {
      echo "<h1>Admin user name contains invalid characters.</h1>";
      echo "May only contain lower alphanumeric, dash, dot, and underscore.";
      die;
   }

   #  is the user permitted to change this record?
   $canDelete = 0;
   if ($GLOBALS["vpm_issuperuser"]) { $canDelete = 1; }

   #  check privileges
   if (!$canDelete) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

   vpm_header("vPostMaster Admin User Delete "
         . "(${name})");

   echo "<h1>Deleting ${name}...</h1>\n";
   $ret = vpm_deleteAdminUser($name);
   if ($ret != "") {
      echo "<font color=\"#ff0000\">Error</font> deleting admin user: $ret";
   }
   else {
?>
      <p />This admin user has been successfully deleted.  Please use the
      menu at the left to navigate.
<?php
   }
?>
