<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   #  setup
   $ret = vpm_validateform("username", "Name", "/^[-a-z0-9+._]+$/",
         1, 50, 1);
   $username = $ret[1];
   if ($ret[0] != "successful" ) {
      echo "<h1>Unknown user:</h1>";
      echo "User '${username}' does not exist.";
      die;
   }
   $domain = $_SESSION["vpmsession_selecteddomain"];

   #  is the user permitted to change this record?
   $canDelete = 0;
   if ($GLOBALS["vpm_issuperuser"]) { $canDelete = 1; }
   if (vpm_isDomainAdmin($domain)) { $canDelete = 1; }

   #  check privileges
   if (!$canDelete) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

   vpm_header("vPostMaster User Delete "
         . "(${username})");
?>

<?php
   #  check that they're sure
   $positive = 0;
   if (vpm_getpost("positive") == "yes") {
      $positive = 1;
   }
   if (!$positive) {
?>
      <h1>Delete "<?php echo $username; ?>"?</h1>

      You are about to delete this user and all associated data.  Once
      this is done, there is no way to reverse it.  Are you <b>sure</b> you
      want to delete this account?

      <form method="POST" action="user_delete.php">
         <input type="hidden" name="username"
               value="<?php echo $username ?>" />
         <input type="hidden" name="positive" value="yes" />
         <input type="submit" name="submit"
            value="Click here if you are SURE you want to delete '<?php
               echo $username; ?>'"
            class="delete_button" <?php echo vpm_tabindex(); ?> />
      </form>
<?php
   }

   #  they are positive
   if ($positive) {
?>
      <h1>Deleted <?php echo $username; ?></h1>
<?php
      $ret = vpm_deleteUser($username, $domain);
      if ($ret != "") {
         echo "<font color=\"#ff0000\">Error</font> deleting user: $ret";
      }
      else {
?>
         <p />This user has been successfully deleted.  Please use the
         menu at the left to navigate.
<?php
         $_SESSION["vpmsession_selecteduser"] = "";
      }
   }
?>
