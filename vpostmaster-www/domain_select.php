<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();
   vpm_header("vPostMaster Domain Selection");
?>

<h1>Domain Selection</h1>

<?php
   if (!$GLOBALS["vpm_issuperuser"] && !$GLOBALS["vpm_isadminuser"]) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }
?>

<p />Select a domain from the list below:

<table border=1>
   <tr><th>Select Domain</th><th>Active Users</th></tr>

   <?php
      $totalcount = 0;
      $count = 0;
      foreach (vpm_admindomainlist() as $domain) {
         $count = $count + 1;
         $tridstr = 'id="odd"';
         if ($count % 2 == 0) { $tridstr = 'id="even"'; }

         $activecount = vpm_admindomainlistactivecount($domain);
         $totalcount = $totalcount + intval($activecount);
         echo "<tr ${tridstr}><td><form action=\"domain_view.php\" " .
               "method=\"POST\"><input type=\"hidden\" " .
               "value=\"${domain}\" /><input type=\"submit\" " .
               "name=\"selectdomain\" value=\"${domain}\" " . vpm_tabindex() .
               "/></form></td><td align=center>${activecount}</td></tr>\n";
         }
         echo "<tr><td align=\"right\"><b>Total Users:</b></td>";
         echo "<td align=\"center\"><b>${totalcount}</b></td></tr>\n";
   ?>
</table>
