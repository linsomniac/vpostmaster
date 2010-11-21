<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   vpm_header("vPostMaster Admin User Lookup");

   #  check privileges
   if (!$GLOBALS["vpm_issuperuser"]) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }
?>

<h1>Admin Users</h1>

<table>
<?php
   $userList = vpm_getAllAdminUsers();
   foreach ($userList as $userData) {
?>
      <tr><td><form action="adminuser_view.php" method="POST">
         <input type="hidden" value="<?php echo $userData['name']; ?>"
            name="name" />
         <input type="submit" name="submit"
            value="<?php echo $userData['name']; ?>" <?php echo vpm_tabindex(); ?>/>
      </form></td></tr>
<?php
   }
?>
</table>
