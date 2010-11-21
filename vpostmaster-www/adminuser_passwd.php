<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   #  check privileges
   if (!$GLOBALS["vpm_isadminuser"]) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

   vpm_header("vPostMaster Admin User Password Change");

   $ret = vpm_validateform("submit", "Submit button", "/Change Password/",
         0, 20, 1);
   $info = array();
   $errors = array();
   $showForm = 1;
   $initialLoad = 0;
   if ($ret[0] == "successful") {
      #  validate form

      $ret = vpm_validateform("password1", "Password", "/^.+$/", 1, 50, 1);
      $info["password1"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

      $ret = vpm_validateform("password2", "Password (Again)",
            "/^.+$/", 1, 50, 1);
      $info["password2"] = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($info["password1"] != $info["password2"]) {
         array_push($errors, "Both password fields must match.");
      }

      #  update password
      if (count($errors) < 1) {
         $quotedName = "'" . pg_escape_string($_SESSION["vpmsession_username"]) . "'";
         $quotedCryptedPw = "'" . pg_escape_string(
               vpm_cryptPassword($info["password1"])) . "'";
         $result = pg_query("UPDATE adminusers SET cryptedpasswd = " .
               $quotedCryptedPw . " WHERE name = " . $quotedName);
         if (!$result) {
            array_push($errors, "SQL Error while changing password: " .
                  pg_last_error());
         }
         else { pg_free_result($result); }
         }

   }
   else {
      #  new form load
      $initialLoad = 1;
      $info["password1"] = "";
      $info["password2"] = "";
   }
?>

<h1>Change Admin User Password</h1>

<?php if (!$initialLoad && count($errors) > 0) { ?>
   <h2><font color="#ff0000">Errors:</font></h2>
   <p />The following errors were found in your form.  Please correct them
   and submit the form again.

   <ul>
<?php
      foreach ($errors as $error) { echo "<li />$error"; }
      echo "</ul>";
      $showForm = 1;
   }

if (!$initialLoad && count($errors) == 0) { ?>
   <h2><font color="#00ff00">Successfully changed</font></h2>
   <p />Your password has successfully been changed.
<?php
   $showForm = 0;
}  # end of successful status ?>

<?php if ($showForm) { ?>

<form method="POST" action="adminuser_passwd.php"><table>
   <tr><td><label for="pw1">Password:</label></td><td>
      <input type="password" id="pw1" name="password1", value="<?php echo
            $info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?>/>
      </td><td class="formdesc">Your new password.
      </td></tr>

   <tr><td><label for="pw2">Password (again):</label></td><td>
      <input type="password" id="pw2" name="password2", value="<?php echo
            $info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?>/>
      </td><td class="formdesc">Verify your new password.
      </td></tr>

      <tr><td colspan="3"><input type="submit" name="submit"
            value="Change Password" <?php echo vpm_tabindex(); ?>/></td></tr>
</form></table>

<?php } #  end of showForm  ?>
