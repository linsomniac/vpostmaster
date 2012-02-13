<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   #  check privileges
   if (!$GLOBALS["vpm_issuperuser"] && !$GLOBALS["vpm_isadminuser"]) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

?>

<?php
   #  process the form input
   $name = vpm_getpost('name');

   #  check permissions
   if (!$GLOBALS["vpm_issuperuser"]) {
      vpm_header("vPostMaster Domain Delete");
?>
      <h2>No privileges</h2>
      You have no privileges to delete this domain.

<?php
      die;
      }
?>

<?php
   #  check that they're sure
   $positive = 0;
   if (vpm_getpost("positive") == "yes") {
      $positive = 1;
   }
   if (!$positive) {
      vpm_header("vPostMaster Domain Delete: ${name}");
?>
      <h1>Delete <?php echo $name; ?>?</h1>

      You are about to delete this domain and all users within it.  Once
      this is done, there is no way to reverse it.  Are you <b>sure</b> you
      want to delete it?

      <form method="POST" action="domain_delete.php">
         <input type="hidden" name="name"
               value="<?php echo $name ?>" />
         <input type="hidden" name="positive"
               value="yes" />
         <input type="submit" name="submit"
            value="Click here if you are SURE you want to delete <?php
               echo $name; ?>"
            class="delete_button" <?php echo vpm_tabindex(); ?> />
      </form>
<?php
   }

   #  they are positive
   if ($positive) {
      $errors = vpm_deleteDomain($name);
      if ($errors != "") {
         vpm_header("vPostMaster Domain Delete Failed");
         echo "<h1>Delete ${name} Failed</h1>";
         echo "<font color=\"#ff0000\">Error</font> deleting domain:";

         if (count($errors) > 0) {
            echo "<ul>";
            foreach ($errors as $error) {
               echo "<li />$error";
            }
            echo "</ul>";
         }
      }
      else {
         $_SESSION["vpmsession_selecteddomain"] = "";
         $_SESSION["vpmsession_selecteduser"] = "";
         vpm_header("vPostMaster Domain Deleted");
         echo "<h1>Deleted ${name}</h1>";
?>
         <p />This domain has been successfully deleted.  Please use the
         menu at the left to navigate.
<?php
      }
   }
?>
