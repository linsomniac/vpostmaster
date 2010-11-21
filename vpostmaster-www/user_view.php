<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();
   $errors = array();
   $info = vpm_checkprivs(TRUE, TRUE);

   #  is the user permitted to change this record?
   $canEdit = 0;
   $canDelete = 0;
   $isUser = 0;
   if ($GLOBALS["vpm_issuperuser"] || $GLOBALS["vpm_isadminuser"]) {
      $canEdit = 1;
      $canDelete = 1;
   } else {
      $canEdit = 1;
      $isUser = 1;
   }

   #  check privileges
   if (!$canEdit) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

   $_SESSION["vpmsession_selecteduser"] = $info["username"];
   vpm_header("vPostMaster User Edit");

   #  load extraattributes
   $extraAttributes = array('byid' => array(), 'byname' => array());
   $result = pg_query("SELECT * FROM extraattributes ORDER BY name");
   if ($result) {
      while ($row = pg_fetch_assoc($result)) {
         $extraAttributes['byname'][$row['name']] = $row;
         $extraAttributes['byid'][$row['id']] = $row;
      }
      pg_free_result($result);
   } else {
      array_push($errors, "SQL Error getting extra attribute information: " .
            pg_last_error());
   }

   #  load data from database
   $domainInfo = vpm_getDomainByName($info["domainname"]);
   $quotedName = "'" . pg_escape_string($info["username"]) . "'";
   $quotedDomain = "'" . pg_escape_string($info["domainname"]) . "'";
   $result = pg_query("SELECT * FROM users WHERE name = ${quotedName} ".
         "AND domainsname = ${quotedDomain}");
   if (!$result) {
      $dbInfo == NULL;
      array_push($errors, "SQL Error getting user informaiton: " .
            pg_last_error());
   }
   else {
      $dbInfo = pg_fetch_assoc($result);
      pg_free_result($result);
   }

   #  load extra settings from database
   $result = pg_query("SELECT * FROM extrasettings " .
         "WHERE usersid = '${dbInfo['id']}';");
   if (!$result) {
      $dbInfo == NULL;
      array_push($errors, "SQL Error getting user extra settings: " .
            pg_last_error());
   }
   else {
      while ($row = pg_fetch_assoc($result)) {
         $dbInfo["extra_${row['attributesid']}"] = $row['value_text'];
      }
      pg_free_result($result);
   }

   #  determine if extras should be set above or below
   $extras_above = false;
   $include_file = "/usr/lib/vpostmaster/etc/config_local.php";
   if (file_exists($include_file)) { include($include_file); }


   $ret = vpm_validateform("submit", "Submit button", "/Update/", 0, 10, 1);
   $showForm = 1;
   $initialLoad = 0;
   if ($ret[0] == "successful") {
      #  validate form

      if (!vpm_doesMailUserExist($info["username"], $info["domainname"])) {
         array_push($errors, "User '${info['username']}' does not exist.");
      }

      if ($GLOBALS["vpm_isadminuser"] != 1 
            && $_SESSION["vpmsession_requireforwardwithindomain"]) {
         #  skip if no change in forwardto
         if (vpm_getpost("forwardto") != $dbInfo["forwardto"]) {
            $domainQuoted = preg_quote($info["domainname"]);
            $forwardInDomainRegex = "/^(([-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]" .
                  "{1,64}@" . $domainQuoted . ")*" .
                  "([-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64}@" .
                  $domainQuoted . "\s*))|(@mailman" .
                  "(:[-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64})?@\s*)$/";
            $ret = vpm_validateform("forwardto", "Forward To",
                  $forwardInDomainRegex, 1, 10000, 0);
            $info["forwardto"] = $ret[1];
            if ($ret[0] != "successful" ) {
               array_push($errors, "Forward To not correctly formatted, " .
                     "or not in this domain.");
            }
         }
      } else {
         $emailMultiRegex = "/^(([-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64}@" .
               "[-A-Za-z0-9.]+\.[-A-Za-z0-9]+\s+)*" .
               "([-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64}@" .
               "[-A-Za-z0-9.]+\.[-A-Za-z0-9]+\s*))|" .
               "(@mailman(:[-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64})?@\s*)$/";
         $ret = vpm_validateform("forwardto", "Forward To",
               $emailMultiRegex, 1, 10000, 0);
         $info["forwardto"] = $ret[1];
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      }

      #  Create a properly escaped regex out of the username.
      $emailLoopRegex = "/^(" . preg_quote($info["username"] . "@" .
            $info["domainname"]) . ")\s*$/i";
      $ret = vpm_validateform("forwardto", "Forward To",
            $emailLoopRegex, 1, 10000, 1);
      $info["forwardto"] = $ret[1];
      #  Normal meaning of the return value of vpm_validateform reversed.
      #  If the forward matches the username, it is an error.
      if ($ret[0] == "successful" ) { array_push($errors, 
            'Forward To would cause a mail loop.'); }

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

      if (!$isUser) {
         $ret = vpm_validateform("active", "Active", "/^t?$/", 1, 2, 0);
         $info["active"] = $ret[1];
         if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
         if ($info["active"] != "t") { $info["active"] = "f"; }
      } else {
         $info["active"] = $dbInfo["active"];
         $info["quotainmegabytes"] = $dbInfo["quotainmegabytes"];
      }

      #  validate/copy extra settings
      if ($domainInfo['allowextraattributes'] == 't') {
         foreach ($extraAttributes['byid'] as $attributeId => $attribute) {
            if (!$isUser) {
               #  validate the form information
               if ($attribute["class"] == 'BOOLEAN') {
                  $ret = vpm_validateform("extra_${attributeId}",
                        "${attribute['label']}", "/^(true|false|)$/", 1, 10, 0);
               } else if ($attribute["class"] == 'TEXT') {
                  $ret = vpm_validateform("extra_${attributeId}",
                        "${attribute['label']}", "/^.*$/", 1, 4096, 0);
               }

               #  save off the field or report error
               $info["extra_${attributeId}"] = $ret[1];
               if ($ret[0] != "successful") { array_push($errors, $ret[2]); }

               #  convert the field
               if ($attribute["class"] == 'BOOLEAN') {
                  if ($info["extra_${attributeId}"] != "true") {
                     $info["extra_${attributeId}"] = "false";
                  }
               }
            } else {
               if (array_key_exists("extra_${attributeId}", $dbInfo)) {
                  $info["extra_${attributeId}"] =
                        $dbInfo["extra_${attributeId}"];
               }
            }
         }
      }

      $ret = vpm_validateform("localdeliveryenabled", "Local Delivery",
            "/^t?$/", 1, 2, 0);
      $info["localdeliveryenabled"] = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($info["localdeliveryenabled"] != "t") {
         $info["localdeliveryenabled"] = "f";
      }

      $ret = vpm_validateform("quotainmegabytes", "Quota",
            "/^\d+$/", 1, 10, 0);
      $info["quotainmegabytes"] = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      $maxQuota = $domainInfo["maxperuserquota"];
      if (!$isUser && ($maxQuota != "" && $maxQuota != NULL)
            && ($maxQuota < $info["quotainmegabytes"]
               || $info["quotainmegabytes"] == "")) {
         array_push($errors, "Quota field exceeds maximum allowed: $maxQuota.");
      }

      #  update database
      if (count($errors) < 1) {
         #  update user
         $quotedName = "'" . pg_escape_string($info["username"]) . "'";
         $quotedDomain = "'" . pg_escape_string($info["domainname"]) . "'";
         $setActive = "active = '" . pg_escape_string($info["active"]) .
               "', ";
         if ($isUser) { $quotedActive = ""; }
         $quotedLocalDeliveryEnabled = "'" .
               pg_escape_string($info["localdeliveryenabled"]) .  "'";
         $setQuota = "quotainmegabytes = '" .
               pg_escape_string($info["quotainmegabytes"]) . "', ";
         if (!$info["quotainmegabytes"]) {
            $setQuota = "quotainmegabytes = NULL, ";
         }
         if ($isUser) { $setQuota = ''; }
         $quotedForwardTo = "'" .
               pg_escape_string($info["forwardto"]) . "'";
         if (!$info["forwardto"]) { $quotedForwardTo = "NULL"; }
         $cryptedPw = vpm_cryptPassword($info["password1"]);
         $setCryptedPw = "cryptedpasswd = '" . pg_escape_string($cryptedPw) .
               "', plaintextpasswd = '" .
               pg_escape_string($info["password1"]) . "', ";
         if (!$info["password1"]) {
            $setCryptedPw = "";
            $CryptedPw = "";
         }
         $result = pg_query("UPDATE users " .
               "SET " .
                  $setActive .
                  $setCryptedPw .
                  $setQuota .
                  "forwardto = ${quotedForwardTo}, " .
                  "localdeliveryenabled = ${quotedLocalDeliveryEnabled} " .
               "WHERE name = ${quotedName} AND domainsname = ${quotedDomain} ");
         if (!$result) {
            array_push($errors, "SQL Error while updating user: " .
                  pg_last_error());
         }
         else {
            pg_free_result($result);
            $info["password1"] = "";
            $info["password2"] = "";

            #  set session password if this is the user session
            if ($cryptedPw && $isUser) {
               $_SESSION["vpmsession_cryptedpasswd"] = $cryptedPw;
            }
         }

         #  update extra settings
         if ($domainInfo['allowextraattributes'] == 't') {
            foreach ($extraAttributes['byid'] as $attributeId => $attribute) {
               if (!array_key_exists("extra_${attributeId}", $dbInfo) &&
                     array_key_exists("extra_${attributeId}", $info) &&
                     $info["extra_${attributeId}"]) {
                  $valueQuoted = "'" .
                        pg_escape_string($info["extra_${attributeId}"]) . "'";
                  $result = pg_query("INSERT INTO extrasettings " .
                        "( usersid, attributesid, value_text ) " .
                        "VALUES ( ${dbInfo['id']}, ${attributeId}, " .
                        "${valueQuoted} )");
                  if (!$result) {
                     array_push($errors, "SQL Error while inserting " .
                           "extra settings: " . pg_last_error());
                  }
                  else {
                     pg_free_result($result);
                  }
               } else if (array_key_exists("extra_${attributeId}", $dbInfo) &&
                     array_key_exists("extra_${attributeId}", $info) &&
                     $info["extra_${attributeId}"]
                        != $dbInfo["extra_${attributeId}"]) {
                  $valueQuoted = "'" .
                        pg_escape_string($info["extra_${attributeId}"]) . "'";
                  $result = pg_query("UPDATE extrasettings " .
                        "SET value_text = ${valueQuoted} " .
                        "WHERE usersid = ${dbInfo['id']} " .
                           "AND attributesid = ${attributeId}");
                  if (!$result) {
                     array_push($errors, "SQL Error while updating " .
                           "extra settings: " . pg_last_error());
                  }
                  else {
                     pg_free_result($result);
                  }
               }
            }
         }
      }
   } else {
      if ($dbInfo == NULL) {
         echo "<h1>User does not exist.</h1>";
         echo "This user does not exist.  Perhaps it was deleted by another " .
               "user?  Please use the menu on the left to continue.";
         die;
      }
      $initialLoad = 1;
      $oldInfo = $info;
      $info = $dbInfo;
      $info["username"] = $info["name"];
      $info["domainname"] = $oldInfo["domainname"];
      $info["password1"] = "";
      $info["password2"] = "";
   }

   $isDiscarding = 0;
   if (!$info['forwardto'] && $info['localdeliveryenabled'] != 't') {
      $isDiscarding = 1;
   }
?>

<h1>Edit User</h1>

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
   <h2><font color="#00ff00">Successfully updated</font></h2>
   <p />The user account was successfully updated.  Please use the menu on
   the left to continue.
   <p />
<?php
   $showForm = 1;
   }

if (count($errors) == 0 && $isDiscarding) { ?>
   <h2><font color="#ff0000">Warning: Discarding E-mail</font></h2>
   <p />The current configuration does not specify local delivery or an
   address to forward to.  This means that all incoming e-mail will be
   dropped.  If this is not what you intended, please alter the values of
   "Local Delivery" or "Forward To" below.
   <hr />
   <p />

<?php
}  # end of successful status ?>

<?php if ($showForm) { ?>

<form method="POST" action="user_view.php"><table>
   <tr><td><label for="username">Login:</label></td><td>
      <?php echo $info["username"]; ?>
      <input type="hidden" id="username" name="username",
            value="<?php echo $info['username']; ?>" />
      </td><td>
      </td></tr>

   <?php
   if ($extras_above) { vpm_extra_entry($info, $domainInfo, $extraAttributes,
         $isUser); }
   ?>

   <tr><td><label for="password1">Password:</label></td><td>
      <input type="password" id="password1" name="password1", value="<?php echo
		$info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">Type a new password if you wish to change it.
      </td></tr>

   <tr><td><label for="password2">Password (again):</label></td><td>
      <input type="password" id="password2" name="password2", value="<?php echo
		$info['password2']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">Verify new password for the user.
      </td></tr>

<?php if (!$isUser) { ?>
   <tr><td><label for="active">Active:</label></td><td>
      <input TYPE="checkbox" id="active" NAME="active" VALUE="t"
            <?php
               if ($info["active"] == "t") { echo "checked=\"checked\""; }
               if ($isUser) {
                  echo "disabled=\"disabled\" readonly=\"readonly\"";
               }
               echo vpm_tabindex();
            ?>
            />
      </td><td class="formdesc">
            Check to receive email. Uncheck to reject all email for this
            user.
            <?php echo
               vpm_user_moreinfo_link('popup_userview.php#active',
               '(more...)');
            ?>
      </td></tr>
<?php } #  isUser  ?>

   <tr><td><label for="ld">Local Delivery:</label></td><td>
      <input TYPE="checkbox" id="ld" NAME="localdeliveryenabled" VALUE="t"
            <?php if ($info["localdeliveryenabled"] == "t")
               { echo "checked=\"checked\""; } echo vpm_tabindex(); ?> />
      </td><td class="formdesc">
             Deliver mail locally (checked); forward (unchecked + set
             forwarding address below) or store and forward (checked + set
             forwarding address below).
            <?php echo
               vpm_user_moreinfo_link('popup_userview.php#local',
               '(more...)');
            ?>
      </td></tr>

      <tr><td><label for="forwardto">Forward To:</label></td><td>
      <textarea id="forwardto" name="forwardto" rows="5" cols="30" <?php echo vpm_tabindex(); ?> ><?php echo
            $info["forwardto"]; ?></textarea>
      </td><td class="formdesc">
            Forward to email addresses, one per line. 
            <?php echo
               vpm_user_moreinfo_link('popup_userview.php#forward',
               '(more...)');
            ?>
      </td></tr>

      <tr><td><label for="quota">Quota:</label></td><td>
      <input type="text" id="quota" name="quotainmegabytes", value="<?php echo
            $info["quotainmegabytes"]; ?>" size="4" maxlen="4"
            <?php if ($isUser) {
               echo "disabled=\"disabled\" readonly=\"readonly\""; } echo vpm_tabindex(); ?>
            />MB
      </td><td class="formdesc">
            Optionally, the mailbox quota in megabytes.
            <?php echo
               vpm_user_moreinfo_link('popup_userview.php#quota',
               '(more...)');
            ?>
      </td></tr>

      <?php
      if (!$extras_above) { vpm_extra_entry($info, $domainInfo, 
            $extraAttributes, $isUser); }
      ?>

      <tr><td colspan="3"><input type="submit" name="submit" value="Update"
            <?php echo vpm_tabindex(); ?> /></td></tr>
</form></table>

<?php if ($canDelete) { ?>
<p /><form method="POST" action="user_delete.php">
   <input type="hidden" name="username"
         value="<?php echo $info["username"] ?>" />
   <input type="submit" name="submit"
      value="&lt;&lt;&lt;Delete '<?php
         echo $info['username']; ?>'&gt;&gt;&gt;"
         class="delete_button" <?php echo vpm_tabindex(); ?> />
</form>
<?php } ?>

<?php } #  end of showForm  ?>
