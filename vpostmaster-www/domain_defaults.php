<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   require_once("settings.php");
   vpm_start();
   vpm_header("vPostMaster Domain Defaults");
   $info = vpm_checkprivs(FALSE, TRUE);

?>

<?php
   #  setup
   $quotedDomainname = "'" . pg_escape_string($info["domainname"]) . "'";
   $initialLoad = 1;
   $errors = array();
   $errorfields = array();

   #  get domain id
   $result = pg_query("SELECT id FROM domains " .
         "WHERE name = ${quotedDomainname} ");
   $domainId = NULL;
   if ($result) {
      if (($row = pg_fetch_array($result))) { $domainId = $row[0]; }
      pg_free_result($result);
   }
   if ($domainId == NULL) {
      array_push($errors, "This domain no longer exists.");
   }

   #  process form or load initial settings
   $ret = vpm_validateform("submit", "Submit button", "/Update/", 0, 10, 1);
   if ($ret[0] == "successful") {
      $initialLoad = 0;

      #  load/validate form information
      foreach ($vpm_settings as $setting) {
         #  load from form
         $ret = vpm_validateform($setting[0], $setting[1], "", 0,
               30, $setting[3]);
         $info[$setting[0]] = $ret[1];
         if ($ret[1] != "") {
            $info["quoted_" . $setting[0]] = "'" . pg_escape_string($ret[1]) .
                  "'";
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

      #  update the settings
      if (count($errors) < 1) {

         #  delete existing rows
         pg_query("BEGIN;");
         if ($domainId == 'NULL') {
            pg_query("DELETE FROM domaindefaults WHERE domainsid " .
                  "IS ${domainId};");
         } else {
            pg_query("DELETE FROM domaindefaults WHERE domainsid::INTEGER " .
                  "= ${domainId};");
         }


         #  add the new records
         foreach ($vpm_settings as $setting) {
            $key = "'" . $setting[0] . "'";
            $value = $info["quoted_" . $setting[0]];
            $result = pg_query("INSERT INTO domaindefaults " .
                  "(domainsid, key, value) " .
                  "VALUES (${domainId}, ${key}, ${value})");
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
      #  initial page load, look up domain settings
      $result = pg_query("SELECT * FROM domaindefaults " .
            "WHERE domainsid::INTEGER = ${domainId}");
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

   echo "<h1>Domain ${info['domainname']} Defaults</h1>";

   echo "<p />These defaults are applied to newly-created users.  Existing " .
         "users are not modified.";
   echo "<p />";

   #  display form
   echo "<table><form action=\"domain_defaults.php\" method=\"POST\">";
   vpm_display_settings($vpm_settings, $errorfields, $info);
   echo "<tr><td colspan=\"3\"><input type=\"submit\" name=\"submit\" " .
         "value=\"Update\" " . vpm_tabindex() . " /></td></tr>";
   echo "<form></table>";
?>
