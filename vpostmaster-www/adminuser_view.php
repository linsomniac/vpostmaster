<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   $name = vpm_getpost('name');

   #  set up privileges
   $canEdit = $GLOBALS["vpm_issuperuser"];
   $canDelete = $GLOBALS["vpm_issuperuser"];
   $canChangePassword = $GLOBALS["vpm_issuperuser"];

   vpm_header("vPostMaster Admin Edit User $name");

   #  setup
   $showForm = 1;
   $initialLoad = 0;
   $info = array();
   $errors = array();

   #  delete
   $ret = vpm_validateform("submit", "Submit button", "/^.*Delete.*/",
         0, 500, 1);
   if ($ret[0] == "successful" && $canEdit) {
      #  load data from form
      $ret = vpm_validateform("name", "Name", "/^[-a-z0-9@._]+$/", 1, 50, 1);
      $info["name"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      if (!vpm_doesAdminUserExist($info["name"])) {
         array_push($errors, "User '${info['name']}' does not exist.");
      }

      #  delete user
      $quotedName = "'" . pg_escape_string($info["name"]) . "'";
      $result = pg_query("DELETE FROM adminusers WHERE name = " .
            $quotedName);
      if (!$result) {
         array_push($errors, "SQL Error while deleting admin user: " .
               pg_last_error());
      }
      else {
         pg_free_result($result);
?>
         <h1><font color="#00ff00">Successfully deleted</font></h1>

         <p />User successfully deleted.  Please use the menu to the left
         to continue.
<?php
      die;
      }
   }

   #  update
   $ret = vpm_validateform("submit", "Submit button", "/Update/", 0, 20, 1);
   if ($ret[0] == "successful" && $canEdit) {
      #  validate form

      $ret = vpm_validateform("name", "Name", "/^[-a-z0-9@._]+$/", 1, 50, 1);
      $info["name"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      if (!vpm_doesAdminUserExist($info["name"])) {
         array_push($errors, "User '${info['name']}' does not exist.");
      }

      $ret = vpm_validateform("password1", "Password", "/^.+$/", 1, 50, 0);
      $info["password1"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

      $ret = vpm_validateform("password2", "Password (Again)",
            "/^.+$/", 1, 50, 0);
      $info["password2"] = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($info["password1"] && $info["password1"] != $info["password2"]) {
         array_push($errors, "Both password fields must match.");
         $info["password1"] = "";
         $info["password2"] = "";
      }

      $ret = vpm_validateform("issuperuser", "Superuser",
            "/^t?$/", 1, 2, 0);
      $info["issuperuser"] = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($info["issuperuser"] != "t") { $info["issuperuser"] = "f"; }

      $ret = vpm_validateform("domains", "Domains",
            "", 1, 4000, 0);
      $info["domains"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      else {
         foreach (vpm_invalidDomains($info["domains"]) as $baddomain) {
            array_push($errors, "Unknown domain: '$baddomain'");
         }
      }

      #  update user
      if (count($errors) < 1) {
         #  get the current password or the new password
         if ($info["password1"]) {
            $cryptedPw = vpm_cryptPassword($info["password1"]);
         }
         else {
            $userData = vpm_lookupAdminUserByName($info["name"]);
            $cryptedPw = $userData["cryptedpasswd"];
         }
         $quotedCryptedPw = "'" . pg_escape_string($cryptedPw) . "'";

         $quotedName = "'" . pg_escape_string($info["name"]) . "'";
         $quotedIsSuperUser = "'" . pg_escape_string($info["issuperuser"]) .
               "'";
         $result = pg_query("UPDATE adminusers SET " .
               "cryptedpasswd = ${quotedCryptedPw}, " .
               "issuperuser = ${quotedIsSuperUser} " .
               "WHERE name = ${quotedName}");
         if (!$result) {
            array_push($errors, "SQL Error while updating user: " .
                  pg_last_error());
         }
         else {
            pg_free_result($result);
            $info["password1"] = "";
            $info["password2"] = "";
         }

         vpm_adminUserDomainUpdate($info["name"], $info["domains"]);
         }

   }
   else {
      #  new form load
      $initialLoad = 1;
      $info["name"] = "";
      $info["issuperuser"] = "f";
      $info["password1"] = "";
      $info["password2"] = "";
      $info["domains"] = "";
   }
?>

<?php
   #  load user data
   if ($initialLoad && $showForm) {
      $info = vpm_lookupAdminUserByName($name);
   }

   if (!$info) {
?>
      <h1>Unknown Admin User</h1>

      <p />The selected user does not exist.  Please use the menu at the
      left to try again.
<?php
      die;
      }
   if ($info["issuperuser"] != "t") { $info["issuperuser"] = "f"; }
?>

<h1>Edit Admin User "<?php echo $name; ?>"</h1>

<?php if (!$initialLoad && count($errors) > 0) { ?>
   <h2><font color="#ff0000">Errors:</font></h2>
   <p />The following errors were found in your form.  Please correct them
   and submit the form again.

   <ul>
<?php
      foreach ($errors as $error) { echo "<li />$error"; }
      echo "</ul><p />";
      $showForm = 1;
   }

if (!$initialLoad && count($errors) == 0) { ?>
   <h2><font color="#00ff00">Successfully updated</font></h2>
   <p />The user account was successfully updated.  Please use the menu on
   the left to continue.
   <p />
<?php
   $showForm = 1;
}  # end of successful status ?>

<?php if ($showForm) { ?>

   <form method="POST"><table>
      <input type="hidden" name="name" value="<?php echo $info["name"] ?>" />
      <tr><td>Name:</td><td><?php echo $info["name"]; ?>
          </td><td class="formdesc">
            Admin user name.  This may be a short name, or a full e-mail
            address.
            <?php echo vpm_user_moreinfo_link('popup_adminview.php#name',
                  '(more...)');
            ?>
            </td></tr>

   <?php if ($canChangePassword) { ?>
      <tr><td><label for="password1">Password:</label></td><td>
            <input id="password1" type="password" name="password1"
                  value="<?php if
                  (!$initialLoad) { echo $info["password1"]; } echo vpm_tabindex(); ?>" />
          </td><td class="formdesc">
            Change the password. Otherwise, leave blank.
         </td></tr>
      <tr><td><label for="password2">Password (Again):</label></td><td>
            <input id="password2" type="password" name="password2"
                  value="<?php if
                  (!$initialLoad) { echo $info["password2"]; } echo vpm_tabindex(); ?>" />
         </td><td class="formdesc">
            Verify the changed password. Otherwise, leave blank.
         </td></tr>
      <tr><td><label for="issuperuser">Superuser:</label></td><td>
         <input id="issuperuser" type="checkbox" name="issuperuser" value="t"
               <?php if ($info["issuperuser"] == "t") {
                  echo "checked=\"checked\""; } echo vpm_tabindex(); ?> />
         </td><td class="formdesc">
              Enable superuser role.  Be careful not to lock yourself out
              of the system by changing this.
              <?php
                  echo vpm_user_moreinfo_link('popup_adminview.php#issuperuser', 
                  '(more...)');
              ?>
          </td></tr>
      <tr><td><label for="domains">Domains:</label></td><td>
            <textarea id="domains" name="domains" cols="30" rows="8" <?php echo vpm_tabindex(); ?> ><?php
                  echo $info['domains']; ?></textarea>
            </td><td class="formdesc">
               List of managed domains, one domain per line.  Leave blank for
               superusers.
               <?php
                  echo vpm_user_moreinfo_link('popup_adminview.php#domains',
                  '(more...)');
               ?>
             </td></tr>

      <?php if ($canChangePassword || $canEdit) { ?>
         <tr><td colspan="1"><input type="submit" name="submit" value="Update"
               <?php echo vpm_tabindex(); ?> /></form>
              </td>
              <td colspan="1">&nbsp;</td>
              <td colspan="1">
                  <?php if ($canDelete) { ?>
                     <form method="POST" action="adminuser_delete.php">
                        <input type="hidden" name="name"
                           value="<?php echo $info["name"] ?>" />
                        <input type="submit" name="submit"
                           value="&lt;&lt;&lt;Delete <?php
                        echo $info['name']; ?>&gt;&gt;&gt;"
                        class="delete_button"
                        onClick="return verify_action('This action will permanently remove this admin user.  Are you sure you want to continue?');"
                        <?php echo vpm_tabindex(); ?> />
                      </form>
                  <?php } ?>
              </td>
         </tr>
      <?php } ?>
   <?php } ?>
   </table>

<?php }  #  if can show form  ?>
