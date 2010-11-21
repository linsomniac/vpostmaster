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

   #  determine if extras should be set above or below
   $extras_above = false;
   $include_file = "/usr/lib/vpostmaster/etc/config_local.php";
   if (file_exists($include_file)) { include($include_file); }

   $ret = vpm_validateform("submit", "Submit button", "/Create/", 0, 10, 1);
   $info = array();
   $info["domain"] = $_SESSION["vpmsession_selecteddomain"];
   $domainInfo = vpm_getDomainByName($info["domain"]);
   $errors = array();
   $showForm = 1;
   $initialLoad = 0;

   #  load extraattributes
   $extraAttributes = array('byid' => array(), 'byname' => array());
   $result = pg_query("SELECT * FROM extraattributes ORDER BY name");
   if ($result) {
      while ($row = pg_fetch_assoc($result)) {
         $extraAttributes['byname'][$row['name']] = $row;
         $extraAttributes['byid'][$row['id']] = $row;
      }
      $dbInfo = pg_fetch_assoc($result);
      pg_free_result($result);
   } else {
      array_push($errors, "SQL Error getting extra attribute information: " .
            pg_last_error());
   }

   if ($ret[0] == "successful") {
      #  validate form

      $ret = vpm_validateform("name", "Name", "/^[-a-z0-9+._]+$/",
            1, 50, 1);
      $info["name"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      if (vpm_doesMailUserExist($info["name"], $info["domain"])) {
         array_push($errors, "User '${info['name']}' already exists.");
      }

      $emailMultiRegex = "/^(([-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64}@" .
            "[-A-Za-z0-9.]+\.[-A-Za-z0-9]+\s+)*" .
            "([-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64}@" .
            "[-A-Za-z0-9.]+\.[-A-Za-z0-9]+\s*))|" .
            "(@mailman(:[-A-Za-z0-9+._!#\$%&'*\/=?^`{|}~]{1,64})?@\s*)$/";
      $ret = vpm_validateform("forwardto", "Forward To",
            $emailMultiRegex, 1, 10000, 0);
      $info["forwardto"] = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

      #  Create a properly escaped regex out of the username.
      $emailLoopRegex = "/^(" . preg_quote($info["username"] . "@" .
            $info["domainname"]) . ")$/i";
      $ret = vpm_validateform("forwardto", "Forward To",
            $emailLoopRegex, 1, 10000, 1);
      $info["forwardto"] = $ret[1];
      #  Normal meaning of the return value of vpm_validateform reversed.
      #  If the forward matches the username, it is an error.
      if ($ret[0] == "successful" ) { array_push($errors, 
            'Forward To would cause a mail loop.'); }

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

      $ret = vpm_validateform("active", "Active",
            "/^t?$/", 1, 2, 0);
      $info["active"] = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($info["active"] != "t") { $info["active"] = "f"; }

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
      if (($maxQuota != "" && $maxQuota != NULL)
            && ($maxQuota < $info["quotainmegabytes"]
               || $info["quotainmegabytes"] == "")) {
         array_push($errors, "Quota field exceeds maximum allowed: $maxQuota.");
      }

      $maxUsers = $domainInfo["maxusers"];
      if ($maxUsers != "" && $maxUsers != NULL
            && $maxUsers < vpm_domainCountUsers($info["domain"])) {
         array_push($errors, "You have reached the maximum allowed users in " .
               "this domain");
         }

      $result = pg_query("SELECT * FROM meta");
      $metaData = pg_fetch_assoc($result);
      pg_free_result($result);
      if (!$metaData) {
         array_push($errors, "The meta table doesn't seem to exist.  " .
            "This is probably a configuration error and needs to be " .
            "reported to the site owner.");
         }
      else {
         $info["userdir"] = $domainInfo["domaindir"] . "/mailboxes/";
         if (((int) $metaData["userdirsplit"]) > 0) {
            $info["userdir"] = $info["userdir"] .
                  substr($info["name"], 0, $metaData["userdirsplit"]) .  "/";
         }
         $info["userdir"] = $info["userdir"] . $info["name"];
         }

      #  validate/copy extra settings
      if ($domainInfo['allowextraattributes'] == 't') {
         foreach ($extraAttributes['byid'] as $attributeId => $attribute) {
            if ($GLOBALS["vpm_issuperuser"] || $GLOBALS["vpm_isadminuser"]) {
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

      #  create new user
      if (count($errors) < 1) {
         $quotedName = "'" . pg_escape_string($info["name"]) . "'";
         $quotedDomain = "'" . pg_escape_string($info["domain"]) . "'";
         $quotedActive = "'" . pg_escape_string($info["active"]) .  "'";
         $quotedLocalDeliveryEnabled = "'" .
               pg_escape_string($info["localdeliveryenabled"]) .  "'";
         $quotedUserDir = "'" . pg_escape_string($info["userdir"]) .  "'";
         $quotedQuotaInMegabytes =
               pg_escape_string($info["quotainmegabytes"]);
         if (!$info["quotainmegabytes"]) { $quotedQuotaInMegabytes = "NULL"; }
         $quotedForwardTo = "'" .
               pg_escape_string($info["forwardto"]) . "'";
         if (!$info["forwardto"]) { $quotedForwardTo = "NULL"; }
         $quotedCryptedPw = "'" . pg_escape_string(
               vpm_cryptPassword($info["password1"])) . "'";
         $quotedPlainPw = "'" . pg_escape_string($info["password1"]) . "'";
         $result = pg_query("INSERT INTO users " .
               "( name, domainsname, active, cryptedpasswd, plaintextpasswd, " .
                  "userdir, quotainmegabytes, forwardto, " .
                  "localdeliveryenabled ) " .
               "VALUES ( ${quotedName}, ${quotedDomain}, ${quotedActive}, " .
                  "${quotedCryptedPw}, ${quotedPlainPw}, ${quotedUserDir}, " .
                  "${quotedQuotaInMegabytes}, ${quotedForwardTo}, " .
                  "${quotedLocalDeliveryEnabled} )");
         if (!$result) {
            array_push($errors, "SQL Error while adding user: " .
                  pg_last_error());
         }
         else {
            pg_free_result($result);
            $_SESSION["vpmsession_selecteduser"] = $info["name"];

            #  create user home directory
            $out = array("pipe", "w");
            $err = array("pipe", "w");
            $descriptorspec = array(0 => array("pipe", "r"),
                  1 => $out, 2 => $err);
            $process = proc_open(
                  "/usr/bin/sudo -u vpostmaster /usr/lib/vpostmaster/bin/vpm-wwwhelper",
                  $descriptorspec, $pipes);
            fwrite($pipes[0], "newuser\0${info['domain']}\0${info['name']}\n");
            fclose($pipes[0]);
            $pipeData = fread($pipes[1], 1024);
            fclose($pipes[1]);
            $pipeData2 = fread($pipes[2], 1024);
            fclose($pipes[2]);
            $return_value = proc_close($process);

            #  process results
            if (!strstr($pipeData, "SUCCESSFUL")) {
               array_push($errors, "Error while calling useradd helper: " .
                     $pipeData . $pipeData2);
               array_push($errors, "NOTE: Helper errors leave user in " .
                     "partially added state.  You will need to delete and " .
                     "re-create the user after resolving the problem.");
            }

            #  update extra settings
            if ($domainInfo['allowextraattributes'] == 't') {
               foreach ($extraAttributes['byid'] as $attributeId => $attribute)
               {
                  if ($info["extra_${attributeId}"]) {
                     $valueQuoted = "'" .
                           pg_escape_string($info["extra_${attributeId}"]) .
                           "'";
                     $result = pg_query("INSERT INTO extrasettings " .
                           "( usersid, attributesid, value_text ) " .
                           "VALUES ( " .
                              "(SELECT id FROM users " .
                                    "WHERE name = ${quotedName} " .
                                    "AND domainsname = ${quotedDomain}), " .
                           "${attributeId}, ${valueQuoted} )");
                     if (!$result) {
                        array_push($errors, "SQL Error while inserting " .
                              "extra settings: " . pg_last_error());
                     }
                     else {
                        pg_free_result($result);
                     }
                  }
               }
            }
         }

         vpm_loadDomainSettings($info["name"], $info["domain"]);
      }
   }
   else {
      #  new form load
      $initialLoad = 1;
      $info["name"] = "";
      $info["active"] = "t";
      $info["localdeliveryenabled"] = "t";
      $info["password1"] = "";
      $info["password2"] = "";
      $info["quotainmegabytes"] = "";
      $info["forwardto"] = "";
   }

   vpm_header("vPostMaster Create Mail User");
?>

<h1>Create Mail User</h1>

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

<form method="POST" action="user_create.php"><table>
   <tr><td><label for="name">Login:</label></td><td>
      <input type="text" id="name" name="name", value="<?php echo
            $info["name"]; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">
            Name of the user.
            <?php echo
               vpm_user_moreinfo_link('popup_usercreate.php#name',
               '(more...)');
            ?>
      </td></tr>

      <?php
      if ($extras_above) { vpm_extra_entry($info, $domainInfo, 
            $extraAttributes); }
      ?>

      <tr><td><label for="password1">Password:</label></td><td>
      <input type="password" id="password1" name="password1", value="<?php echo
      $info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">Password for the new user.
      </td></tr>

      <tr><td><label for="password2">Password (again):</label></td><td>
      <input type="password" id="password2" name="password2", value="<?php echo
      $info['password1']; ?>" size="30" maxlen="64" <?php echo vpm_tabindex(); ?> />
      </td><td class="formdesc">Verify password for the user.
      </td></tr>

      <tr><td><label for="active">Active:</label></td><td>
      <input TYPE="checkbox" id="active" NAME="active" VALUE="t"
            <?php if ($info["active"] == "t") {
               echo "checked=\"checked\""; } echo vpm_tabindex(); ?> />
      </td><td class="formdesc">
            Accept (checked; default) or reject email for user.
            <?php echo
               vpm_user_moreinfo_link('popup_usercreate.php#active',
               '(more...)');
            ?>
      </td></tr>

      <tr><td><label for="ld">Local Delivery:</label></td><td>
      <input TYPE="checkbox" id="ld" NAME="localdeliveryenabled" VALUE="t"
            <?php if ($info["localdeliveryenabled"] == "t")
               { echo "checked=\"checked\""; } echo vpm_tabindex(); ?> />
      </td><td class="formdesc">
            Deliver mail locally (checked); forward (unchecked + set
            forwarding address below) or store and forward (checked + set
            forwarding address below).
            <?php echo
               vpm_user_moreinfo_link('popup_usercreate.php#local',
               '(more...)');
            ?>
      </td></tr>

      <tr><td><label for="forwardto">Forward To:</label></td><td>
      <textarea id="forwardto" name="forwardto" rows="5" cols="30" <?php echo vpm_tabindex(); ?> ><?php echo
            $info["forwardto"]; ?></textarea>
      </td><td class="formdesc">
            Forward to email addresses, one per line.
            <?php echo
               vpm_user_moreinfo_link('popup_usercreate.php#forward',
               '(more...)');
            ?>
      </td></tr>

      <tr><td><label for="quota">Quota:</label></td><td>
      <input type="text" id="quota" name="quotainmegabytes", value="<?php echo
            $info["quotainmegabytes"]; ?>" size="4" maxlen="4" <?php echo vpm_tabindex(); ?> />MB
      </td><td class="formdesc">
            Optionally, the maximum size of the users mailbox in megabytes.
            <?php echo
               vpm_user_moreinfo_link('popup_usercreate.php#quota',
               '(more...)');
            ?>
      </td></tr>

      <?php
      if (!$extras_above) { vpm_extra_entry($info, $domainInfo,
            $extraAttributes); }
      ?>

      <tr><td colspan="3"><input type="submit" name="submit" value="Create"
             <?php echo vpm_tabindex(); ?> /></td></tr>
</form></table>

<?php } #  end of showForm  ?>
