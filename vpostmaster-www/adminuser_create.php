<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   #  check privileges
   if (!$GLOBALS["vpm_issuperuser"]) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

   vpm_header("vPostMaster Create Admin User");

   $ret = vpm_validateform("submit", "Submit button", "/Create/", 0, 10, 1);
   $info = array();
   $errors = array();
   $showForm = 1;
   $initialLoad = 0;
   if ($ret[0] == "successful") {
      #  validate form

      $ret = vpm_validateform("name", "Name", "/^[-a-z0-9+@._]+$/",
            1, 50, 1);
      $info["name"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      if (vpm_doesAdminUserExist($info["name"])) {
         array_push($errors, "User '${info['name']}' already exists.");
      }

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

      #  create new user
      if (count($errors) < 1) {
         $quotedName = "'" . pg_escape_string($info["name"]) . "'";
         $quotedIsSuperUser = "'" . pg_escape_string($info["issuperuser"]) .
               "'";
         $quotedCryptedPw = "'" . pg_escape_string(
               vpm_cryptPassword($info["password1"])) . "'";
         $result = pg_query("INSERT INTO adminusers " .
               "( name, issuperuser, cryptedpasswd ) " .
               "VALUES ( " . $quotedName . ", " . $quotedIsSuperUser .
                  ", " . $quotedCryptedPw . " )");
         if (!$result) {
            array_push($errors, "SQL Error while adding user: " .
                  pg_last_error());
         }
         else { pg_free_result($result); }

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

<h1>Create Admin User</h1>

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
   <h2><font color="#00ff00">Successfully created</font></h2>
   <p />The user account was successfully created.  Please use the menu on
   the left to continue.
<?php
   $showForm = 0;
}  # end of successful status ?>

<?php if ($showForm) { ?>

<form method="POST" action="adminuser_create.php"><table>
   <tr><td><label for="name">Login:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $info,
            'name', "size=30 maxlength=100 id=\"name\""); ?>
      </td><td class="formdesc">
         Admin user name.  This may be a short name, or a full e-mail
         address.
         <?php
             echo vpm_user_moreinfo_link('popup_admincreate.php#name',
             '(more...)');
         ?>
      </td></tr>

   <tr><td><label for="password1">Password:</label></td><td>
      <input id="password1" type="password" name="password1", value="<?php echo
            $info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">Password for the new user.
      </td></tr>

   <tr><td><label for="password2">Password (again):</label></td><td>
      <input id="password2" type="password" name="password2", value="<?php echo
            $info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">Verify password.
      </td></tr>

   <tr><td><label for="issuperuser">Superuser:</label></td><td>
      <input id="issuperuser" type="checkbox" name="issuperuser" value="t"
            <?php if ($info["issuperuser"] == "t") {
               echo "checked=\"checked\""; } echo vpm_tabindex(); ?> />
      </td><td class="formdesc">
         If checked, account has super-user privileges.
         <?php
             echo vpm_user_moreinfo_link('popup_admincreate.php#issuperuser',
             '(more...)');
         ?>
      </td></tr>
      </td></tr>

   <tr><td><label for="domains">Domains:</label></td><td>
      <textarea id="domains" name="domains" cols="30" rows="8" <?php echo vpm_tabindex(); ?> ><?php
            echo $info['domains']; ?></textarea>
      </td><td class="formdesc">
         One per line list of domains managed. Leave blank if superuser.
         <?php
             echo vpm_user_moreinfo_link('popup_admincreate.php#domains',
             '(more...)');
         ?>
      </td></tr>

      <tr><td colspan="3"><input type="submit" name="submit" value="Create"
            <?php echo vpm_tabindex(); ?>/></td></tr>
</form></table>

<?php } #  end of showForm  ?>
