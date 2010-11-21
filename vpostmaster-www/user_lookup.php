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

   vpm_header("vPostMaster User Lookup");

   #  get form information
   $queryLimit = 50;
   $ret = vpm_validateform("offset", "Offset", "/^\d+$/", 0, 20, 1);
   $listingOffset = 0;
   if ($ret[0] == "successful") { $listingOffset = $ret[1]; }
   $ret = vpm_validateform("search", "Search", "/^.+$/", 0, 80, 1);
   $searchString = "";
   if ($ret[0] == "successful") { $searchString = $ret[1]; }

?>

<h1>vPostMaster User Lookup</h1>

<?php
   #  do query
   $escapedDomain = pg_escape_string($_SESSION['vpmsession_selecteddomain']);
   $query = "SELECT * FROM users WHERE domainsname = '${escapedDomain}' ";
   if ($searchString) {
      $escapedSearchString = pg_escape_string($searchString);
      $query = $query . "AND name ~ '${escapedSearchString}' ";
   }
   $newLimit = $queryLimit + 1;
   $query = ($query . "ORDER BY name ASC LIMIT ${newLimit} " .
         "OFFSET ${listingOffset}");
   $result = pg_query($query);

   if ($result) {
      #  get data from database
      $list = array();
      for ($i = 0; $i < $queryLimit; $i++) {
         $row = pg_fetch_assoc($result);
         if (!$row) { break; }

         #  load extra settings from database
         $attrresult = pg_query("SELECT extraattributes.name AS name, " .
               "   extrasettings.value_text AS value_text " .
               "FROM extrasettings, extraattributes " .
               "WHERE extraattributes.id = extrasettings.attributesid AND " .
               "  usersid = ${row['id']}");
         if ($attrresult) {
            while ($setting = pg_fetch_assoc($attrresult)) {
               $row["extra_${setting['name']}"] =
                     $setting['value_text'];
            }
            pg_free_result($attrresult);
         }

         array_push($list, $row);
      }
      $showNext = 0;
      if (pg_fetch_assoc($result)) { $showNext = 1; }
      $offsetNext = $listingOffset + count($list);
      $showPrevious = 0;
      if ($listingOffset > 0) { $showPrevious = 1; }
      $offsetPrevious = $listingOffset - $queryLimit;
      if ($offsetPrevious < 0) { $offsetPrevious = 0; }
      pg_free_result($result);

      #  search header
?>
      <form method="POST" action="user_lookup.php">
         <label for="search">Search for User</label>
         <input id="search" type="text" name="search"
               value="<?php echo $searchString; ?>" <?php echo vpm_tabindex(); ?> />
         <input type="submit" name="submit" value="Search" <?php echo vpm_tabindex(); ?> />
      </form><br />

<?php
      #  display header
      if ($showPrevious) {
         echo ("<a href=\"user_lookup.php?offset=${offsetPrevious}" .
               "&search=${searchString}\" " . vpm_tabindex() . ">&lt;&lt;&lt; Previous</a>" .
               "&nbsp;&nbsp;&nbsp;");
         }
      if ($showNext) {
         echo ("&nbsp;&nbsp;&nbsp;<a href=\"user_lookup.php?" .
               "offset=${offsetNext}&search=${searchString}\" " . vpm_tabindex() . ">Next " .
               "&gt;&gt;&gt;</a>");
         }

      #  display table
      echo "<table border=\"1\">";
      echo "<tr><!-- <th></th> --><th>User</th><th>Status</th>";
      echo "<th>Account Type</th><th>Quota</th><th>Forwards To</th></tr>";
      $count = 0;
      foreach ($list as $row) {
         $count = $count + 1;
         $tridstr = 'id="odd"';
         if ($count % 2 == 0) { $tridstr = 'id="even"'; }

         $status = "Active";
         if ($row['active'] != 't') { $status = "Disabled"; }
         $quota = $row['quotainmegabytes'] . "MB";
         if (!$row['quotainmegabytes']) { $quota = "&lt;Unlimited&gt;"; }

         $forwardto = "";
         foreach (explode("\n", $row['forwardto']) as $foo) {
            $foo = trim($foo);
            if ($forwardto) { $forwardto = $forwardto . "<br />"; }
            $forwardto = $forwardto . $foo;
         }

         if ($row['forwardto']) {
            if ($row['localdeliveryenabled'] == 't') {
               $accttype = "Local and Forward";
            } else {
               $accttype = "Forward";
            }
         } else {
            if ($row['localdeliveryenabled'] == 't') {
               $accttype = "Local";
            } else {
               $accttype = "<font color=\"#ff0000\">Discard</font>";
            }
         }
         echo ("<tr ${tridstr}><!-- <td><input " .
               "title=\"Select this user for a multiple action\" " .
               "type=\"checkbox\" name=\"multiselect\" " .
               "value=\"${row['name']}\" /></td> --><td><a " .
               "href=\"user_view.php?username=${row['name']}\" " . vpm_tabindex() . ">" .
               "${row['name']}@${row['domainsname']}</a></td>" .
               "<td>$status</td><td>$accttype</td><td>${quota}</td>" .
               "<td>$forwardto</td></tr>");
         }
      echo "</table>";

      #  display footer
      if ($showPrevious) {
         echo ("<a href=\"user_lookup.php?offset=${offsetPrevious}" .
               "&search=${searchString}\">&lt;&lt;&lt; Previous</a>" .
               "&nbsp;&nbsp;&nbsp;");
         }
      if ($showNext) {
         echo ("&nbsp;&nbsp;&nbsp;<a href=\"user_lookup.php?" .
               "offset=${offsetNext}&search=${searchString}\">Next " .
               "&gt;&gt;&gt;</a>");
         }
   }
   else {
      echo "Error in SQL query: '" . pg_last_error() . "'";
      }
?>
