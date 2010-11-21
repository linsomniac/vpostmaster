<?php
   /*
   Copyright (c) 2005-2008 tummy.com, ltd.
   vPostMaster php script
   */

   require_once('version.php');

   #  Location of the file with the database connect string
   $GLOBALS["vpmDbConnectFile"] = "/usr/lib/vpostmaster/etc/wwwdb.conf";

   ######################################
   function vpm_tabindex() {
      if (!isset($GLOBALS['vpm_tabindex'])) $GLOBALS['vpm_tabindex'] = 0;
      return sprintf(' tabindex="%u" ', ++$GLOBALS['vpm_tabindex']);
   }

   ######################################
   function vpm_getpost_false($fieldname)  #{{{1
   {
      #  get the informaiton
      $field = False;
      if (array_key_exists($fieldname, $_GET)) {
         $field = $_GET[$fieldname];
      }
      if (array_key_exists($fieldname, $_POST)) {
         $field = $_POST[$fieldname];
      }
      if ($field != False) {
         if (get_magic_quotes_gpc() == 1) {
            $field = stripslashes($field);
         }
      }
      return($field);
   }


   ################################
   function vpm_getpost($fieldname)  #{{{1
   {
      #  get the informaiton
      $field = vpm_getpost_false($fieldname);
      if ($field === False) { $field = ''; }
      return($field);
   }


   ############################################################################
   function vpm_checkform($fieldname, $fielddesc, $regex, $minlen, $maxlen,
   $required)  #{{{1
   {
      $ret = vpm_validateform($fieldname, $fielddesc, $regex, $minlen,
            $maxlen, $required);
      return($ret[0] == 'successful');
   }


   ###############################
   function vpm_validateform($fieldname, $fielddesc, $regex, $minlen, $maxlen, $required)  #{{{1
   {
      #  get the informaiton
      $field = vpm_getpost($fieldname);

      #  empty field and not required
      if (!$required && $field == "") {
         return(array("successful", $field, ""));
      }

      #  empty field and IS required
      if ($required && $field == "") {
         return(array("fail", $field, "$fielddesc is required."));
      }

      #  length
      if (strlen($field) > $maxlen) {
         return(array("fail", $field, "$fielddesc is too long, it can be at " .
               "most $maxlen character(s)"));
      }
      if (strlen($field) < $minlen) {
         return(array("fail", $field, "$fielddesc is too short, it must be " .
               "at least $minlen character(s)"));
      }

      #  regex
      if ($regex && !preg_match($regex, $field)) {
         return(array("fail", $field, "$fielddesc is incorrectly formatted."));
      }

      return(array("successful", $field, ""));
   }


   ######################################################################
   function _vpm_validsession($adminuserData, $userData, $domainData) {#{{{1
      if (!array_key_exists("vpmsession_cryptedpasswd", $_SESSION)
            || !$_SESSION["vpmsession_cryptedpasswd"]) {
         echo "Session password is empty";
         return(0);
      }
      if ($adminuserData && (!array_key_exists("cryptedpasswd", $adminuserData)
            || !$adminuserData["cryptedpasswd"])) {
         echo "Session password is incorrect";
         return(0);
      }
      if ($userData && (!array_key_exists("cryptedpasswd", $userData)
            || !$userData["cryptedpasswd"])) {
         echo "Session password2 is incorrect";
         return(0);
      }

      #  is an admin user
      $GLOBALS["vpm_isadminuser"] = 0;
      $GLOBALS["vpm_admindomains"] = array();
      $GLOBALS["vpm_issuperuser"] = 0;
      if ($_SESSION["vpmsession_cryptedpasswd"] == $adminuserData["cryptedpasswd"]) {
         $GLOBALS["vpm_isadminuser"] = 1;
         if ($adminuserData["issuperuser"] == "t") {
            $GLOBALS["vpm_issuperuser"] = 1;
         }

         #  look up admin privileges
         $result = pg_query("SELECT * FROM adminprivs " .
               "WHERE adminusersname = '" .
                  pg_escape_string($_SESSION["vpmsession_username"]) . "'");
         while ($adminPrivs = pg_fetch_assoc($result)) {
            $GLOBALS["vpm_admindomains"][$adminPrivs["domainsname"]] = 1;
         }
         pg_free_result($result);

         return(1);
      }

      #  regular user checks
      if ($_SESSION["vpmsession_cryptedpasswd"] != $userData["cryptedpasswd"]) {
         return(0);
      }
      if (!array_key_exists("active", $userData)
            || $userData["active"] != "t") {
         return(0);
      }
      if (!array_key_exists("active", $domainData)
            || $domainData["active"] != "t") {
         return(0);
      }

      return(1);
   }


   ########################
   function vpm_start()  #{{{1
   {
      #  duplicate call protection
      if (array_key_exists("vpm_start_run", $GLOBALS)
            && $GLOBALS["vpm_start_run"]) {
         return(0);
         }
      $GLOBALS["vpm_start_run"] = 1;

      #  set defaults
      $GLOBALS["vpm_issuperuser"] = 0;
      $GLOBALS["vpm_isadminuser"] = 0;

      #  register shutdown procedures
      register_shutdown_function("vpm_footer");
      register_shutdown_function("vpm_finish");

      #  connect to the database
      $fp = fopen($GLOBALS["vpmDbConnectFile"], "r");
      if (!$fp) {
         echo "<h1>Configuration error</h1>";
         echo "Unable to load the database connect string.  See the " .
               "install documentation about \"wwwdb.conf\" for more " .
               "information";
         die;
      }
      $dbConnStr = chop(fgets($fp));
      fclose($fp);
      $GLOBALS["vpm_db"] = pg_connect($dbConnStr);

      #  Set up the session
      session_name("vPostMaster");
      ini_set("session.use_cookies", "1");
      session_start();

      #  set up basic values if they aren't already there
      if (!array_key_exists("vpmsession_selecteddomain", $_SESSION)) {
         $_SESSION["vpmsession_selecteddomain"] = "";
         }
      if (!array_key_exists("vpmsession_selecteduser", $_SESSION)) {
         $_SESSION["vpmsession_selecteduser"] = "";
         }

      #  check to see if the user has a valid login session
      $loggedin = 0;
      if (array_key_exists("vpmsession_username", $_SESSION) && $_SESSION["vpmsession_username"]
            && array_key_exists("vpmsession_username", $_SESSION)
            && $_SESSION["vpmsession_username"]) {

         #  look up the domain in the database
         $result = pg_query("SELECT * FROM domains WHERE name = '" .
               pg_escape_string($_SESSION["vpmsession_domainname"]) . "'");
         $domainData = pg_fetch_assoc($result);
         pg_free_result($result);

         #  look up the adminuser in the database
         $result = pg_query("SELECT * FROM adminusers WHERE name = '" .
               pg_escape_string($_SESSION["vpmsession_username"]) . "'");
         $adminusersData = pg_fetch_assoc($result);
         pg_free_result($result);

         #  look up the user in the database
         $userData = array();
         if ($_SESSION["vpmsession_localname"] && $_SESSION["vpmsession_domainname"]) {
            $result = pg_query("SELECT * FROM users WHERE name = '" .
                  pg_escape_string($_SESSION["vpmsession_localname"]) .
                  "' AND domainsname = '" .
                  pg_escape_string($_SESSION["vpmsession_domainname"]) . "'");
            $userData = pg_fetch_assoc($result);
            pg_free_result($result);
            }

         $loggedin = _vpm_validsession($adminusersData, $userData,
               $domainData);

         if ($domainData['allowuserspamcontrol'] == "t") {
            $_SESSION["vpmsession_allowuserspamcontrol"] = 1;
         } else {
            $_SESSION["vpmsession_allowuserspamcontrol"] = 0;
         }

         if ($domainData['requireforwardwithindomain'] == "t") {
            $_SESSION["vpmsession_requireforwardwithindomain"] = 1;
         } else {
            $_SESSION["vpmsession_requireforwardwithindomain"] = 0;
         }

      }

      #  do the login page
      $GLOBALS["vpm_isloggedin"] = $loggedin;
      if (!$loggedin) {
         include "login.php";
         exit(0);
      }

      #  check the selecteddomain value
      if ($GLOBALS["vpm_issuperuser"] == 0 &&
            $GLOBALS["vpm_isadminuser"] &&
            !array_key_exists($_SESSION["vpmsession_selecteddomain"],
               $GLOBALS["vpm_admindomains"])) {
         #  user is no longer an admin of selected domain
         $_SESSION["vpmsession_selecteddomain"] = "";
         }
      if ($GLOBALS["vpm_issuperuser"] == 0 &&
            $GLOBALS["vpm_isadminuser"] == 0) {
         #  a regular user login has domain and account selected
         $_SESSION["vpmsession_selecteddomain"] = $_SESSION["vpmsession_domainname"];
         $_SESSION["vpmsession_selecteduser"] = $_SESSION["vpmsession_localname"];
         }
   }

   #########################
   function vpm_finish()  #{{{1
   {
      pg_close($GLOBALS["vpm_db"]);
   }


   ####################################################
   function vpm_process_login($username, $password)  #{{{1
   {
      $errors = array();

      #  validate information
      if (!$username) {
         array_push($errors, "Username must be entered.");
         }
      if (!$password) {
         array_push($errors, "Password must be entered.");
         }
      if (strlen($username) > 500) {
         array_push($errors, "Username must be at most 500 characters");
         }
      if (strlen($password) > 500) {
         array_push($errors, "Password must be at most 500 characters");
         }
      if (count($errors) > 0) { return($errors); }

      #  split the user name into user+domain
      $domainname = "";
      $localname = "";
      $foo = strstr($username, "@");
      if ($foo) {
         $domainname = substr($foo, 1, strlen($foo) - 1);
         $localname = substr($username, 0, strlen($username) - strlen($foo));
         }

      #  look up the domain in the database
      $result = pg_query("SELECT * FROM domains WHERE name = '" .
            pg_escape_string($domainname) . "'");
      $domainData = pg_fetch_assoc($result);
      pg_free_result($result);

      #  look up the adminuser in the database
      $result = pg_query("SELECT * FROM adminusers WHERE name = '" .
            pg_escape_string($username) . "'");
      $adminusersData = pg_fetch_assoc($result);
      pg_free_result($result);

      #  look up the user in the database
      $result = pg_query("SELECT * FROM users WHERE name = '" .
            pg_escape_string($localname) . "' AND domainsname = '" .
            pg_escape_string($domainname) . "'");
      $usersData = pg_fetch_assoc($result);
      pg_free_result($result);

      #  check the login
      $isLoggedIn = 0;
      if ($adminusersData) {
         $tryPw = crypt($password, $adminusersData["cryptedpasswd"]);
         if ($adminusersData["cryptedpasswd"] == $tryPw) {
            $_SESSION["vpmsession_cryptedpasswd"] = $tryPw;
            $isLoggedIn = 1;
            }
         }
      if (!$isLoggedIn && $usersData) {
         if ($domainData['active'] != "t") {
            array_push($errors, "Domain is disabled");
            return($errors);
            }
         if ($usersData['active'] != "t") {
            array_push($errors, "Account is disabled");
            return($errors);
            }
         $tryPw = crypt($password, $usersData["cryptedpasswd"]);
         if ($usersData["cryptedpasswd"] == $tryPw) {
            $_SESSION["vpmsession_cryptedpasswd"] = $tryPw;
            $isLoggedIn = 1;
            $GLOBALS["vpm_redirect_destination"] = "user_view.php";
            }
         }
      if (!$isLoggedIn) {
         array_push($errors, "Invalid login or password");
         return($errors);
         }

      #  set up the session
      $_SESSION["vpmsession_localname"] = $localname;
      $_SESSION["vpmsession_username"] = $username;
      $_SESSION["vpmsession_domainname"] = $domainname;

      return($errors);
   }


   #########################
   function vpm_logout()  #{{{1
   {
      session_name("vPostMaster");
      ini_set("session.use_cookies", "1");
      session_start();
      $_SESSION["vpmsession_cryptedpasswd"] = "";
      $_SESSION = array();
      session_destroy();
   }


   ###############################
   function vpm_header($title)  #{{{1
   {
      #  duplicate call protection
      if (array_key_exists("vpm_header_run", $GLOBALS)
            && $GLOBALS["vpm_header_run"]) {
         return(0);
         }
      $GLOBALS["vpm_header_run"] = 1;

      #  check for updates
      $GLOBALS["vpm_update_available"] = '';
      $GLOBALS["vpm_using_development"] = '';
      if ($GLOBALS["vpm_issuperuser"] == 1
            || $GLOBALS["vpm_isadminuser"] == 1) {
         $now = time();
         if (!array_key_exists("vpmsession_vpm_update_nextcheck", $_SESSION)
               || !array_key_exists("vpmsession_vpm_latest_version", $_SESSION)
               || $_SESSION["vpmsession_vpm_update_nextcheck"] < $now) {
            $_SESSION["vpmsession_vpm_update_nextcheck"] = $now + (3600 * 4);
            $currentVersion = file_get_contents(
                  'http://updates.vpostmaster.com/currentversion.txt');
            $_SESSION['vpmsession_vpm_latest_version'] = trim($currentVersion);
         }

         #  set global update available flag
         if (array_key_exists("vpmsession_vpm_latest_version", $_SESSION)) {
            $diff = strcmp(trim($GLOBALS['vpm_version']),
                  trim($_SESSION["vpmsession_vpm_latest_version"]));
            if ($diff > 0) {
               #  Running a development release?
               $GLOBALS["vpm_using_development"] = ("You are running a<br />" .
                     "development version: ${GLOBALS['vpm_version']}.<br />" .
                     "No newer stable version is available.");
               }
            if ($diff < 0) {
               #  running an olde version
               $GLOBALS["vpm_update_available"] = ("Updated version " .
                     "${_SESSION['vpmsession_vpm_latest_version']} available.");
            }
         }
      }

      #  load header
      $GLOBALS["vpm_page_title"] = $title;
      require_once("header.php");
   }


   #########################
   function vpm_footer()  #{{{1
   {
      #  duplicate call protection
      if (array_key_exists("vpm_footer_run", $GLOBALS)
            && $GLOBALS["vpm_footer_run"]) {
         return(0);
         }
      $GLOBALS["vpm_footer_run"] = 1;

      require_once("footer.php");
   }


   ##############################
   function vpm_admindomainlist()  #{{{1
   {
      #  look up all domains if a superuser
      $domainList = array();
      if ($GLOBALS["vpm_issuperuser"]) {
         $result = pg_query("SELECT * FROM domains ORDER BY name");
         while ($domainData = pg_fetch_assoc($result)) {
            array_push($domainList, $domainData["name"]);
         }
         pg_free_result($result);
      }
      else if ($GLOBALS["vpm_isadminuser"]) {
         $domainList = array_keys($GLOBALS["vpm_admindomains"]);
         sort($domainList);
      }

      return($domainList);
   }


   ################################################
   function vpm_admindomainlistactivecount($domain)  #{{{1
   {
      $quoteddomain = "'" . pg_escape_string($domain) . "'";
      $result = pg_query("SELECT count(id) FROM users " .
            "WHERE active = 't' AND domainsname = ${quoteddomain}");
      $count = pg_fetch_row($result);
      pg_free_result($result);
      return($count[0]);
   }


   ###################################
   function vpm_isDomainAdmin($domain)  #{{{1
   {
      if ($GLOBALS["vpm_isadminuser"] &&
            array_key_exists($domain, $GLOBALS["vpm_admindomains"])) {
         return(TRUE);
      }

      return(FALSE);
   }


   ######################################
   function vpm_lookupdomainbyname($name)  #{{{1
   {
      $result = pg_query("SELECT * FROM domains WHERE name = '" .
            pg_escape_string($name) . "'");
      $domainData = pg_fetch_assoc($result);
      pg_free_result($result);
      return($domainData);
   }


   ##########################################################
   function vpm_textorinput($requires, $data, $field, $extra)  #{{{1
   {
      if (!array_key_exists($requires, $GLOBALS) || !$GLOBALS[$requires]) {
         echo $data[$field];
      }
      else {
         echo "<input type=\"text\" name=\"${field}\" value=\"" .
               $data[$field] . "\" $extra " . vpm_tabindex() . " />";
      }
   }


   ###############################################################
   function vpm_call_helper($command, $asusername = 'vpostmaster',
         $stdin_data = FALSE, $stdin_file = FALSE)  #{{{1
   {
      $status = 'FAILED';
      $data = array();

      $out = array("pipe", "w");
      $err = array("pipe", "w");
      $descriptorspec = array(0 => array("pipe", "r"),
            1 => $out, 2 => $err);
      $process = proc_open(
            "/usr/bin/sudo -u $asusername " .
            "/usr/lib/vpostmaster/bin/vpm-wwwhelper 2>&1",
            $descriptorspec, $pipes);
      fwrite($pipes[0], $command);
      if ($stdin_data) { fwrite($pipes[0], $stdin_data); }
      if ($stdin_file) {
         $copyFp = fopen($stdin_file, 'r');
         if ($copyFp === FALSE) {
            $status = 'ERROR';
            $data[] = 'Unable to open file.';
            return(array($status, $data));
         }

         #  copy file to child
         while (!feof($copyFp)) {
            $fpdata = fread($copyFp, 8192);
            if ($fpdata) { fwrite($pipes[0], $fpdata); }
         }
      }
      fclose($pipes[0]);
      $pipeData = '';
      while (!feof($pipes[1]) and count($pipeData) < 10240) {
         $pipeData .= fread($pipes[1], 10240);
         }
      fclose($pipes[1]);
      $pipeData2 = fread($pipes[2], 1024);
      fclose($pipes[2]);
      $return_value = proc_close($process);

      #  process results
      foreach (explode("\n", $pipeData) as $line) {
         if (preg_match("/^\s*SUCCESSFUL\s*$/", $line)) {
            $status = "SUCCESSFUL";
            break;
            }
         $data[] = $line;
      }

      return(array($status, $data));
   }


   ################################
   function vpm_processDomainForm()  #{{{1
   {
      $errors = array();

      #  check submit value
      if (!vpm_getpost_false('submit')) {
         return(array('onlylooking', $errors));
      }
      $submit = vpm_getpost("submit");

      #  exit if not super-user
      if (!$GLOBALS["vpm_issuperuser"]) {
         return(array("failed", array("You have no privileges to edit " .
               "this domain.")));
      }

      #  VALIDATE
      #  domain name
      if (!vpm_getpost_false('name')) {
         array_push($errors, "Domain name must not be blank.");
         }
      else {
         $name = strtolower(vpm_getpost("name"));
         if (strlen($name) <= 3 || strlen($name) > 200) {
            array_push($errors, "Domain must be more than 3 and fewer than ".
               "200 characters");
            }

         #  create must be done on a domain that does not exist
         if ($submit == "Create") {
            $result = pg_query("SELECT * FROM domains " .
                  "WHERE name = '" . pg_escape_string($name) . "'");
            $foo = pg_fetch_assoc($result);
            pg_free_result($result);
            if ($foo) {
               array_push($errors, "Domain already exists.");
            }
         }
      }

      $ret = vpm_validateform("aliasedto", "Aliased to",
            "/^[-a-zA-Z0-9._]+$/", 1, 100, 0);
      $aliasedto = $ret[1];
      if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }
      if (count(vpm_invalidDomains($aliasedto)) > 0) {
         array_push($errors, "Domain '${aliasedto}' does not exist.");
      }

      #  extensionchar
      $extensionchar = "";
      if (vpm_getpost_false('extensionchar')) {
         $extensionchar = vpm_getpost("extensionchar");
         }
      if ($extensionchar) {
         if ($extensionchar != "-" && $extensionchar != "+"
               && $extensionchar != "") {
            array_push($errors, "Extension character must be either empty or " .
                  "one of the characters \"-\" or \"+\".");
            }
         }

      #  catch-all address
      $catchalladdress = "";
      if (vpm_getpost_false('catchalladdress')) {
         $catchalladdress = strtolower(vpm_getpost("catchalladdress"));
         }
      if ($catchalladdress) {
         if (strlen($catchalladdress) > 200) {
            array_push($errors, "The catchall address must be fewer than " .
                  "200 characters long.");
            }
         else {
            $result = pg_query("SELECT * FROM users WHERE name = '" .
                  pg_escape_string($catchalladdress) .
                  "' AND domainsname = '" .
                  pg_escape_string($name) . "'");
            $foo = pg_fetch_assoc($result);
            pg_free_result($result);
            if (!$foo) {
               array_push($errors, "The catch-all address must be an " .
                     "address within this domain that currently exists.  " .
                     "It must not contain an '@' symbol.");
               }
            }
         }

      #  max users
      $maxusers = "";
      if (vpm_getpost_false('maxusers')) {
         $maxusers = vpm_getpost("maxusers");
         }
      if ($maxusers) {
         if (!preg_match('/^\d+$/', $maxusers)) {
            array_push($errors, "The Maximum Users field must contain " .
                  "only digits.");
         }
      }

      #  max per user quota
      $maxperuserquota = "";
      if (vpm_getpost_false('maxperuserquota')) {
         $maxperuserquota = vpm_getpost("maxperuserquota");
         }
      if ($maxperuserquota) {
         if (!preg_match('/^\d+$/', $maxperuserquota)) {
            array_push($errors, "The Maximum Users field must contain " .
                  "only digits.");
         }
      }

      #  active
      $ret = vpm_validateform("active", "Active",
            "/^t?$/", 1, 2, 0);
      $active = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($active != "t") { $active = "f"; }

      #  allowextraattributes
      $ret = vpm_validateform("allowextraattributes", "Allow Extra Attributes",
            "/^t?$/", 1, 2, 0);
      $allowextraattributes = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($allowextraattributes != "t") { $allowextraattributes = "f"; }

      #  allowuserspamcontrol
      $ret = vpm_validateform("allowuserspamcontrol", "Allow User Spam Control",
            "/^t?$/", 1, 2, 0);
      $allowuserspamcontrol = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($allowuserspamcontrol != "t") { $allowuserspamcontrol = "f"; }

      #  requireforwardwithindomain
      $ret = vpm_validateform("requireforwardwithindomain",
            "Only Forward Within Domain", "/^t?$/", 1, 2, 0);
      $requireforwardwithindomain = $ret[1];
      if ($ret[0] != "successful") { array_push($errors, $ret[2]); }
      if ($requireforwardwithindomain != "t") {
         $requireforwardwithindomain = "f";
         }

      #  return if there are validation errors
      if (count($errors) > 0) { return(array("fail", $errors)); }

      #  make values into SQL strings
      $quotedname = "'" . pg_escape_string($name) . "'";
      if ($aliasedto) {
         $aliasedto = "'" . pg_escape_string($aliasedto) . "'";
         }
      else { $aliasedto = "NULL"; }
      if ($extensionchar) {
         $extensionchar = "'" . pg_escape_string($extensionchar) . "'";
         }
      else { $extensionchar = "NULL"; }
      if ($catchalladdress) {
         $catchalladdress = "'" . pg_escape_string($catchalladdress) . "'";
         }
      else { $catchalladdress = "NULL"; }
      if ($maxusers) {
         $maxusers = pg_escape_string($maxusers);
         }
      else { $maxusers = "NULL"; }
      if ($maxperuserquota) {
         $maxperuserquota = pg_escape_string($maxperuserquota);
         }
      else { $maxperuserquota = "NULL"; }
      $active = "'" . $active . "'";
      $allowextraattributes = "'" . $allowextraattributes . "'";
      $allowuserspamcontrol = "'" . $allowuserspamcontrol . "'";
      $requireforwardwithindomain = "'" . $requireforwardwithindomain . "'";

      #  update domain
      if ($submit == "Update") {
         $result = pg_query("UPDATE domains SET " .
               "aliasedto = " . $aliasedto . ", " .
               "extensionchar = " . $extensionchar . ", " .
               "catchalladdress = " . $catchalladdress .  ", " .
               "maxusers = " . $maxusers . ", " .
               "maxperuserquota = " . $maxperuserquota .  ", " .
               "active = " . $active . ", " .
               "allowextraattributes = " . $allowextraattributes . ", " .
               "allowuserspamcontrol = " . $allowuserspamcontrol . ", " .
               "requireforwardwithindomain = " .
                     $requireforwardwithindomain . " " .
               "WHERE name = " . $quotedname . "");
         pg_free_result($result);
      }
      #  create domain
      if ($submit == "Create") {
         #  get meta-table information
         $result = pg_query("SELECT * FROM meta");
         $metaData = pg_fetch_assoc($result);
         pg_free_result($result);
         if (!$metaData) {
            array_push($errors, "The meta table doesn't seem to exist.  " .
                  "This is probably a configuration error and needs to be " .
                  "reported to the site owner.");
            return(array("fail", $errors));
         }
         #  name domain directory
         $domaindir = $metaData["basedir"] . "/domains/";
         if ($metaData["domaindirsplit"]) {
            $domaindir = $domaindir . substr($name, 0,
                  $metaData["domaindirsplit"]) . "/";
         }
         $domaindir = $domaindir . $name;
         $domaindir = "'" . pg_escape_string($domaindir) . "'";

         #  create domain
         $result = pg_query("INSERT INTO domains " .
               "( name, extensionchar, catchalladdress, maxusers, domaindir, " .
               " active, maxperuserquota, aliasedto ) VALUES ( " .
               $quotedname . ", " .
               $extensionchar . ", " .
               $catchalladdress . ", " .
               $maxusers . ", " .
               $domaindir . ", " .
               $active . ", " .
               $maxperuserquota . ", " .
               $aliasedto . " " .
               ")");
         $lastError = pg_last_error();
         if ($result) { pg_free_result($result); }
         if ($lastError) {
            array_push($errors, "Error creating domain: " .  pg_last_error());
            }

         #  load default settings
         $result = pg_query("INSERT INTO domaindefaults " .
               "(domainsid, key, value, force) " .
               "SELECT (SELECT id FROM domains " .
                     "WHERE name = ${quotedname}), key, value, 'f' " .
                  "FROM domaindefaults WHERE domainsid is NULL");
         $lastError = pg_last_error();
         if ($result) { pg_free_result($result); }
         if ($lastError) {
            array_push($errors, "NOTE: The following error did not prevent " .
                  "the domain from being created.  To correct this, please " .
                  "use the 'Defaults' item in the 'Domain' menu on the left.");
            array_push($errors, "Error setting up domain defaults: " .
                  pg_last_error());
            }

         $_SESSION['vpmsession_selecteddomain'] = $name;
         $_SESSION["vpmsession_selecteduser"] = "";
      }

      #  call the www-helper
      $ret = vpm_call_helper("newdomain\0\n", "root");
      if ($ret[0] != "SUCCESSFUL") {
         $errors = $ret[1];
      }

      #  return results
      if (count($errors) > 0) {
         return(array("fail", $errors));
      } else {
         return(array("successful", $errors));
      }
   }


   ###############################################
   function vpm_loadDomainSettings($user, $domain)  #{{{1
   {
      $quoteduser = "'" . pg_escape_string($user) . "'";
      $quoteddomain = "'" . pg_escape_string($domain) . "'";
      $result = pg_query("SELECT domaindefaults.key AS key, " .
               "domaindefaults.value AS value " .
            "FROM domaindefaults, domains " .
            "WHERE domains.name = ${quoteddomain} " .
               "AND domaindefaults.domainsid::INTEGER = domains.id");
      if ($result) {
         #  get the user id
         $userresult = pg_query("SELECT id FROM users " .
               "WHERE name = ${quoteduser}");
         if ($userresult) {
            $usersId = pg_fetch_row($userresult);
            $usersId = $usersId[0];     #  cannot be done on the above line

            #  add the key/value pairs
            pg_query("BEGIN;");
            pg_query("DELETE FROM usersettings WHERE usersid = ${usersId}");
            while (($row = pg_fetch_array($result))) {
               $key = "'" . pg_escape_string($row[0]) . "'";
               if ($row[0] == NULL) { $key = "NULL"; }
               $value = "'" . pg_escape_string($row[1]) . "'";
               if ($row[1] == NULL) { $value = "NULL"; }

               pg_query("INSERT INTO usersettings " .
                     "( usersid, key, value ) " .
                     "VALUES ( ${usersId}, ${key}, ${value} )");
               }
            pg_query("END;");
            pg_free_result($userresult);
            }

         pg_free_result($result);
         }

      return("");
   }


   ################################
   function vpm_deleteDomain($name)  #{{{1
   {
      #  call the www-helper
      $ret = vpm_call_helper("rmdomain\0${name}\n", "root");
      if ($ret[0] != "SUCCESSFUL") {
         return($ret[1]);
      }

      $quotedname = "'" . pg_escape_string($name) . "'";
      $result = pg_query("DELETE FROM domains " .
            "WHERE name = " . $quotedname);
      if (!$result) { return(pg_last_error()); }
      pg_free_result($result);

      return("");
   }


   #######################################
   function vpm_deleteUser($name, $domain)  #{{{1
   {
      #  create user home directory
      $out = array("pipe", "w");
      $err = array("pipe", "w");
      $descriptorspec = array(0 => array("pipe", "r"),
            1 => $out, 2 => $err);
      $process = proc_open(
            "/usr/bin/sudo -u vpostmaster /usr/lib/vpostmaster/bin/vpm-wwwhelper",
            $descriptorspec, $pipes);
      fwrite($pipes[0], "rmuser\0${domain}\0${name}\n");
      fclose($pipes[0]);
      $pipeData = fread($pipes[1], 1024);
      fclose($pipes[1]);
      $pipeData2 = fread($pipes[2], 1024);
      fclose($pipes[2]);
      $return_value = proc_close($process);

      $quotedName = "'" . pg_escape_string($name) . "'";
      $quotedDomain = "'" . pg_escape_string($domain) . "'";
      $result = pg_query("DELETE FROM users " .
            "WHERE name = ${quotedName} AND domainsname = ${quotedDomain}");
      if (!$result) { return(pg_last_error()); }
      pg_free_result($result);

      return("");
   }


   ###############################
   function vpm_getAllAdminUsers()  #{{{1
   {
      $userList = array();
      $result = pg_query("SELECT * FROM adminusers ORDER BY name");
      while (($userData = pg_fetch_assoc($result))) {
         $userList[$userData['name']] = $userData;
      }
      pg_free_result($result);

      return($userList);
   }


   #########################################
   function vpm_lookupAdminUserByName($name)  #{{{1
   {
      #  get user information
      $quotedname = "'" . pg_escape_string($name) . "'";
      $result = pg_query("SELECT * FROM adminusers  WHERE name = " .
            $quotedname);
      $userData = pg_fetch_assoc($result);
      pg_free_result($result);

      #  get domain information
      $domains = "";
      $result = pg_query("SELECT domainsname FROM adminprivs " .
            "WHERE adminusersname = " .  $quotedname);
      while (($row = pg_fetch_assoc($result))) {
         $domains = $domains . $row["domainsname"] . "\n";
      }
      $userData["domains"] = $domains;

      return($userData);
   }


   ######################################
   function vpm_doesAdminUserExist($name)  #{{{1
   {
      $quotedname = "'" . pg_escape_string($name) . "'";
      $result = pg_query("SELECT * FROM adminusers WHERE name = " .
            $quotedname);
      $data = pg_fetch_assoc($result);
      pg_free_result($result);

      if ($data) { return(1); }
      return(0);
   }


   ###################################
   function vpm_deleteAdminUser($name)  #{{{1
   {
      #  create user home directory
      $quotedName = "'" . pg_escape_string($name) . "'";
      $result = pg_query("DELETE FROM adminusers " .
            "WHERE name = ${quotedName}");
      if (!$result) { return(pg_last_error()); }
      pg_free_result($result);

      return("");
   }


   ##############################################
   function vpm_doesMailUserExist($name, $domain)  #{{{1
   {
      $quotedname = "'" . pg_escape_string($name) . "'";
      $quoteddomain = "'" . pg_escape_string($domain) . "'";
      $result = pg_query("SELECT * FROM users WHERE name = ${quotedname} " .
            "AND domainsname = ${quoteddomain}");
      $data = pg_fetch_assoc($result);
      pg_free_result($result);

      if ($data) { return(1); }
      return(0);
   }


   ###################################
   function vpm_getDomainByName($name)  #{{{1
   {
      $quotedname = "'" . pg_escape_string($name) . "'";
      $result = pg_query("SELECT * FROM domains WHERE name = ${quotedname}");
      $data = pg_fetch_assoc($result);
      pg_free_result($result);

      return($data);
   }


   ####################################
   function vpm_domainCountUsers($name)  #{{{1
   {
      $quotedname = "'" . pg_escape_string($name) . "'";
      $result = pg_query("SELECT COUNT(*) FROM users " .
            "WHERE domainsname = ${quotedname}");
      $data = pg_fetch_row($result);
      pg_free_result($result);

      return($data[0]);
   }


   #####################################
   function vpm_explodeDomains($domains)  #{{{1
   {
      $list = array();
      foreach (explode("\n", $domains) as $name) {
         $name = trim($name);
         if ($name == '') { continue; }
         array_push($list, $name);
         }
      return($list);
   }


   #####################################
   function vpm_invalidDomains($domains)  #{{{1
   {
      $badDomains = array();
      foreach (vpm_explodeDomains($domains) as $domain) {
         $quotedname = "'" . pg_escape_string($domain) . "'";
         $result = pg_query("SELECT * FROM domains  WHERE name = " .
               $quotedname);
         $data = pg_fetch_assoc($result);
         pg_free_result($result);

         if (!$data) { array_push($badDomains, $domain); }
      }

      return($badDomains);
   }

   ###################################################
   function vpm_adminUserDomainUpdate($name, $domains)  #{{{1
   {
      $quotedName = "'" . pg_escape_string($name) . "'";
      $result = pg_query("DELETE FROM adminprivs WHERE adminusersname = " .
            $quotedName);

      foreach (vpm_explodeDomains($domains) as $domain) {
         $quotedDomain = "'" . pg_escape_string($domain) . "'";
         $result = pg_query("INSERT INTO adminprivs " .
               "( adminusersname, domainsname ) VALUES ( " .
               $quotedName . ", " . $quotedDomain . " )");
         if ($result) { pg_free_result($result); }
         else { echo pg_last_error() . "<br />"; }
      }
   }


   #####################################
   function vpm_cryptPassword($password)  #{{{1
   {
      $jumble = md5(time() . getmypid());
      $salt = substr($jumble, 0, 2);
      return(crypt($password, $salt));
   }


   #################################################
   function vpm_checkprivs($allowUser, $allowDomain)  #{{{1
   {
      #  Abort if the user does not have privileges for the page.
      #  Authentication is checked using either the "username" and
      #  "domainname" values from the form, or the currently selected
      #  values if form fields are not available.
      #
      #  When $allowUser is TRUE, authentication allows a user to act on
      #  their own selected user.  When $allowDomain is TRUE, domain admins
      #  are allowed.  Otherwise, only superuser is allowed.
      #
      #  On failure, processing is terminated.
      #  On success, an array is returned with "username" and "domainname"
      #  set to the user and domain name being acted on.

      #  get the user/domain values
      $info = array();
      $info["username"] = $_SESSION["vpmsession_selecteduser"];
      $info["domainname"] = $_SESSION["vpmsession_selecteddomain"];
      if (array_key_exists("username", $_GET) && $_GET["username"]) {
         $info["username"] = vpm_getpost("username");
      }
      if (vpm_getpost_false('username')) {
         $info["username"] = vpm_getpost("username");
      }
      if (vpm_getpost_false('domainname')) {
         $info["domainname"] = vpm_getpost("domainname");
      }

      #  short-circuit if superuser
      if ($GLOBALS["vpm_issuperuser"]) { return($info); }

      #  check for domain privileges
      if ($info["domainname"] && vpm_isDomainAdmin($info["domainname"])) {
         return($info);
      }

      #  check for user privileges
      if ($_SESSION["vpmsession_localname"] == $info["username"] &&
            $info["domainname"] && $info["username"] &&
            $_SESSION["vpmsession_domainname"] == $info["domainname"]) {
         return($info);
      }

      #  failure
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
   }

   ############################################
   function vpm_user_moreinfo_link($url, $body)  #{{{1
   {
      return("<a href=\"$url\" style=\"color: #FFFFFF\" " .
      "onClick=\"return(helpwindow('$url'))\">$body</a>");

   }

   #################################################################
   function vpm_display_settings($vpm_settings, $errorfields, $info)  #{{{1
   {
      foreach ($vpm_settings as $setting) {
         #  figure out the class
         $class = "formname";
         if (array_key_exists($setting[0], $errorfields)) {
            $class = "formname_error";
         }

         #  display the field
         echo "<tr><td class=\"${class}\"><label for=\"${setting[0]}\">" .
               "${setting[1]}</label>:</td><td>";
         if ($setting[2] != NULL) {
            echo "<select id=\"${setting[0]}\" name=\"${setting[0]}\" " . vpm_tabindex() . ">";
            foreach ($setting[2] as $option) {
               echo "<option value=\"${option[0]}\"";
               if ($info[$setting[0]] == $option[0]) { echo "selected"; }
               echo ">${option[1]}</option>";
            }
            echo "</select>";
         } else {
            echo "<input id=\"${setting[0]}\" name=\"${setting[0]}\" " .
                  "value=\"${info[$setting[0]]}\" ${setting[4]} " . vpm_tabindex() . "/>";
         }
         echo "</td><td class=\"formdesc\">${setting[5]}</td></tr>";
      }
   }

   #####################################
   function vpm_checkprivs_requireuser()  #{{{1
   #  Require that the user, domain admin, or superuser privileges be
   #  set in order to access this page.  Abort via "die" if not.
   {
      #  is the user permitted to change this record?
      $canEdit = 0;
      $username = $_SESSION["vpmsession_selecteduser"];
      $domain = $_SESSION["vpmsession_selecteddomain"];
      if ($GLOBALS["vpm_issuperuser"]) { $canEdit = 1; }
      if (vpm_isDomainAdmin($domain)) { $canEdit = 1; }
      if ($username == $_SESSION["vpmsession_localname"]
            && $domain == $_SESSION["vpmsession_domainname"]) {
         $canEdit = 1;
      }

      #  check privileges
      if (!$canEdit) {
         echo "<h1>Access error:</h1>";
         echo "You do not have privileges to access this page.";
         die;
         }

      #  successful
      return(1);
   }


   #################################
   function vpm_check_userselected()  #{{{1
   #  Check that a user is selected, if not abort via "die".
   {
      $username = $_SESSION["vpmsession_selecteduser"];
      if (!$username) {
         vpm_header("vPostMaster User Rules");
         echo "<h1>User not selected:</h1>";
         echo "No user has been selected.  Please use the \"Lookup\" item of " .
               "the \"Mail Users\" menu to select a user.";
         die;
      }
   }


   ###################################
   function vpm_get_max_upload_bytes()  #{{{1
   {
      $size = ini_get('upload_max_filesize');
      $size = trim($size);
      switch (strtolower($size{strlen($size)-1})) {
         case 'g': $size *= 1024;
         case 'm': $size *= 1024;
         case 'k': $size *= 1024;
      }
      return($size);
   }


   ###################################
   function vpm_add_alertmessage($msg)  #{{{1
   {
      $oldmsg = '';
      if (array_key_exists("vpmsession_alertmessage", $_SESSION)) {
         $oldmsg = $_SESSION["vpmsession_alertmessage"];
         }
      $_SESSION["vpmsession_alertmessage"] = $oldmsg . $msg;
   }


   ###############################
   function vpm_get_alertmessage()  #{{{1
   {
      if (array_key_exists("vpmsession_alertmessage", $_SESSION)
            && $_SESSION["vpmsession_alertmessage"]) {
         $msg = $_SESSION["vpmsession_alertmessage"];
         $_SESSION["vpmsession_alertmessage"] = '';
         return($msg);
         }
      return('');
   }


   ###########################################################################
   function vpm_extra_entry($info, $domainInfo, $extraAttributes, $isUser = 0)  #{{{1
   {
      if ($domainInfo['allowextraattributes'] == 't') {
         ksort($extraAttributes['byname']);
         foreach ($extraAttributes['byname'] as $attributeName => $attribute) {
            echo "<tr><td><label for=\"${attributeName}\">" .
                  "${attribute['label']}</label></td><td>";
            if ($attribute["class"] == 'BOOLEAN') {
               $attributeChecked = "";
               if (array_key_exists("extra_${attribute['id']}", $info)
                     && $info["extra_${attribute['id']}"] == 'true') {
                  $attributeChecked = "checked=\"checked\"";
               }
               echo "<input TYPE=\"checkbox\" " .
                     "id=\"extra_${attribute['id']}\" " .
                     "NAME=\"extra_${attribute['id']}\" VALUE=\"true\" " .
                     ($isUser? "disabled=\"disabled\" readonly=\"readonly\""
                           : "") .
                     "${attributeChecked} />";
            } else if ($attribute["class"] == 'TEXT') {
               $attributeValue = "";
               if (array_key_exists("extra_${attribute['id']}", $info)) {
                  $attributeValue = $info["extra_${attribute['id']}"];
               }
               echo "<input TYPE=\"text\" id=\"extra_${attribute['id']}\" " .
                     "NAME=\"extra_${attribute['id']}\" " .
                     "SIZE=\"30\" MAXLEN=\"4096\" " .
                     ($isUser? "disabled=\"disabled\" readonly=\"readonly\""
                           : "") .
                     "VALUE=\"${attributeValue}\" />";
            }
            echo "</td><td class=\"formdesc\">" .
                  "${attribute['description']}</td></tr>\n";
         }
      }
   }

?>
