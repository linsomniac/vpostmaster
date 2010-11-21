<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   require_once("settings.php");
   vpm_start();
   vpm_header("vPostMaster User Settings");
   $info = vpm_checkprivs(TRUE, TRUE);

?>

<?php
   #  setup
   $quotedUsername = "'" . pg_escape_string($info["username"]) . "'";
   $quotedDomainname = "'" . pg_escape_string($info["domainname"]) . "'";
   $initialLoad = 1;

   #  process form or load initial settings
   $ret = vpm_validateform("submit", "Submit button", "/Update/", 0, 10, 1);
   $errors = array();
   $errorfields = array();
   if ($ret[0] == "successful") {
      $initialLoad = 0;

      if ($GLOBALS["vpm_isadminuser"] != 1 
            && ! $_SESSION["vpmsession_allowuserspamcontrol"]) {
         array_push($errors, 'User spam setting modification is disabled ' .
               'by the administrator.');
      } else {
         #  load/validate form information
         foreach ($vpm_settings as $setting) {
            #  load from form
            $ret = vpm_validateform($setting[0], $setting[1], "", 0,
                  30, $setting[3]);
            $info[$setting[0]] = $ret[1];
            if ($ret[1] != "") {
               $info["quoted_" . $setting[0]] = "'" .
                     pg_escape_string($ret[1]) . "'";
            } else {
               $info["quoted_" . $setting[0]] = "NULL";
            }
            if ($ret[0] != "successful" ) {
               array_push($errors, $ret[2]);
               $errorfields[$setting[0]] = 1;
            }
   
            #  call validator
            $validator = "vpm_validate_${setting[0]}";
            $ret = call_user_func($validator, $info[$setting[0]]);
            if (function_exists($validator)) {
               $ret = call_user_func($validator, $info[$setting[0]]);
               if ($ret != "") {
                  array_push($errors, "${setting[1]}: ${ret}");
               $errorfields[$setting[0]] = 1;
               }
            }
         }
      }

      #  get user id
      $result = pg_query("SELECT id FROM users " .
            "WHERE name = ${quotedUsername} " .
            "AND domainsname = ${quotedDomainname}");
      $id = NULL;
      if ($result) {
         if (($row = pg_fetch_array($result))) { $id = $row[0]; }
         pg_free_result($result);
      }
      if ($id == NULL) {
         array_push($errors, "This user no longer exists.");
      }

      #  update the settings
      if (count($errors) < 1) {

         #  delete existing rows
         pg_query("BEGIN;");
         pg_query("DELETE FROM usersettings WHERE usersid = ${id};");

         #  add the new records
         foreach ($vpm_settings as $setting) {
            $key = "'" . $setting[0] . "'";
            $value = $info["quoted_" . $setting[0]];
            $result = pg_query("INSERT INTO usersettings " .
                  "(usersid, key, value) VALUES (${id}, ${key}, ${value})");
            if ($result) {
               if (pg_result_status($result) != PGSQL_COMMAND_OK) {
                  array_push($errors, "SQL Error when adding new setting " .
                        "for ${setting[1]}: " . pg_result_error($result));
               }
               pg_free_result($result);
            } else {
               array_push($errors, "SQL Error when adding new setting " .
                     "for ${setting[1]}: " . pg_last_error());
            }
         }

         pg_query("END;");
      }
   } else {
      #  initial page load, look up user settings
      $result = pg_query("SELECT * FROM usersettings " .
            "WHERE usersid = (SELECT id FROM users " .
               "WHERE name = ${quotedUsername} " .
               "AND domainsname = ${quotedDomainname})");
      if ($result) {
         while (($row = pg_fetch_assoc($result))) {
            $info[$row["key"]] = $row["value"];
         }
         pg_free_result($result);
      }
   }

   #  show errors
   if (!$initialLoad && count($errors) > 0) {
      echo "<h2><font color=\"#ff0000\">Errors:</font></h2>";
      echo "<p />The following errors were found in your form.  Please " .
            "correct them and submit the form again.";
      echo "<ul>";
      foreach ($errors as $error) { echo "<li />$error"; }
      echo "</ul>";
   }

   #  show successful
   if (!$initialLoad && count($errors) < 1) {
      echo "<h2><font color=\"#00ff00\">Update Successful:</font></h2>";
      echo "<p />Settings have been updated.  Please use the menu on the " .
            "left for more information, or the form below to make further " .
            "changes.";
   }

   echo "<h1>User Settings</h1>";

   #  display form
   if ($GLOBALS["vpm_isadminuser"] == 1 
         || $_SESSION["vpmsession_allowuserspamcontrol"]) {
      echo "<table><form action=\"user_settings.php\" method=\"POST\">";
      vpm_display_settings($vpm_settings, $errorfields, $info);
      echo "<tr><td colspan=\"3\"><input type=\"submit\" name=\"submit\" " .
            "value=\"Update\" " . vpm_tabindex() . "/></td></tr>";
      echo "<form></table>";
   }
?>
