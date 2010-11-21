<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<!-- Copyright (c) 2005-2008 tummy.com, ltd.  vPostMaster script -->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US">
   <head>
      <title><?php echo $GLOBALS["vpm_page_title"]; ?></title>
      <link rel="SHORTCUT ICON" href="favicon.ico">
      <!-- <link rel="STYLESHEET" href="/normal.css" type="text/css"> -->
      <SCRIPT language="JavaScript">
      <!--hide

      function helpwindow(target)
      {
         window.open(target,'help','width=400,height=400,resizable=yes,scrollbars=yes,status=no,location=no');
         return(false);
      }

      function verify_action(message)
      {
         if (confirm(message)) { return(true); }
         return(false);
      }
      //-->
      </SCRIPT>
   </head>

   <body>

<style type="text/css">
<!--
div#header {
   }
div#usingdevelopmentversion {
   margin: 0;
   padding: 0em 0em 0;
   background-color: #0000ff;
   color: #ffffff;
   letter-spacing: 2px;
   font: bold 1.0em Arial, sans-serif;
   }
div#updateavailable {
   margin: 0;
   padding: 0em 0em 0;
   background-color: #ff0000;
   color: #ffffff;
   letter-spacing: 2px;
   font: bold 1.0em Arial, sans-serif;
   }
div#header_logo_line1 {
   margin: 0;
   padding: 0em 0em 0;
   color: #666699;
   letter-spacing: 2px;
   font: bold 2.0em Arial, sans-serif;
   }
div#header_logo_line2 {
   margin: 0;
   padding: 0em 0em 0;
   border-bottom: 1px solid #333366;
   letter-spacing: 2px;
   color: #9999cc;
   }
div#header_logo {
   line-height: 0.8em;
   text-align: left;
   }
div#sidebar {
   border-top: 1px solid silver;
   border-right: 1px solid silver;
   border-bottom: 1px solid silver;
   }
div#sidebar_menu ul {
   list-style-type: none;
   padding: 0px 0px 0px 1em;
   padding-bottom: 5px;
   }
ul#sidebar_submenu_visible {
   display: block;
   list-style-type: none;
   padding: 0px 0px 0px 1em;
   }
ul#sidebar_submenu_visible li {
   padding: 0px 1em 0px 0px;
   }
div#sidebar_menu li {
   background: #666699;
   color: white;
   }
div#sidebar_menu li a:link { color: white; }
div#sidebar_menu li a:visited { color: white; }
div#sidebar_menu li:hover { color: white; background: #9999cc; }
div#sidebar_menu li a:hover { color: white; }
ul#sidebar_submenu_visible li { background: white; }
ul#sidebar_submenu_visible li a:link { color: black; }
ul#sidebar_submenu_visible li a:visited { color: black; }
ul#sidebar_submenu_visible li a:hover { color: white; background: #9999cc; }
ul#sidebar_submenu_hidden {
   display: none;
   }

div#body {
   }
.delete_button {
   color: #ff0000;
}
.footer {
   margin: 40px auto 20px auto;
   background: #9999cc;
   color: #000000;
   width: 95%;
   text-align: center;
   clear: both;
   padding: 10px;
   border: 1px solid #333366;
   font-size: x-small;
   }
.footer a {
   color: #000000;
   text-decoration : underline;
   }
.footer a:hover {
   color: #ffffff;
   text-decoration : underline;
   }
h1 {
   background: #666699;
   color: white;
   }
h2 {
   background: #9999cc;
   color: black;
   }
td {
   vertical-align: top;
   }
td .formdesc {
   color: #000000;
   background: #9999cc;
   border: 1px solid #333366;
   }
td .formname {
   color: #000000;
   }
td .formname_error {
   color: #ff0000;
   }
tr#even {
   background-color: #d0d0ff;
   }
tr#even td {
   border-width: 1px 1px 1px 1px;
   padding: 1px 1px 1px 1px;
   border-style: solid solid solid solid;
   }
tr#odd td {
   border-width: 1px 1px 1px 1px;
   padding: 1px 1px 1px 1px;
   border-style: solid solid solid solid;
   }
tr#even:hover td {
   border-color: #ffa500 black #ffa500 black;
   border-width: 2px 1px 2px 1px;
   padding: 0px 1px 0px 1px;
   border-style: solid solid solid solid;
   }
tr#odd:hover td {
   border-color: #ffa500 black #ffa500 black;
   border-width: 2px 1px 2px 1px;
   padding: 0px 1px 0px 1px;
   border-style: solid solid solid solid;
   }
.tableseparator {
   background: #d0d0ff;
   color: black;
   }

-->
</style>

<table>
   <tr valign="top"><td><table>
      <tr><td>
<?php
         if ($GLOBALS["vpm_update_available"]) {
            echo "<div id=\"updateavailable\">";
            echo $GLOBALS["vpm_update_available"];
            echo "<br /><a href=\"update_information.php\">(Click here for " .
                  "more information)</a>";
            echo "</div>";
         }
         if ($GLOBALS["vpm_using_development"]) {
            echo "<div id=\"usingdevelopmentversion\">";
            echo $GLOBALS["vpm_using_development"];
            echo "</div>";
         }
?>

         <div id="header_logo">
            <div id="header_logo_line1"><img src="logo_sm.png"
               alt="vPostMaster" /></div>
            <div id="header_logo_line2"
               >Control&nbsp;Panel&nbsp;&nbsp;&nbsp;<small>(v<?php
                  echo $GLOBALS['vpm_version'];?>)</small></div>
         </div>
      </td></tr>

      <tr valign="top"><td>

      <?php
         if (array_key_exists("vpmsession_selecteduser", $_SESSION)
               && $_SESSION["vpmsession_selecteduser"]) {
            echo "${_SESSION["vpmsession_selecteduser"]}@${_SESSION["vpmsession_selecteddomain"]}";
            }
         else if (array_key_exists("vpmsession_selecteddomain", $_SESSION)
               && $_SESSION["vpmsession_selecteddomain"]) {
            echo "${_SESSION["vpmsession_selecteddomain"]}";
            }
         else {
            echo "(No domain selected)";
            }
      ?>

<?php if ($GLOBALS["vpm_isloggedin"] == 1) { ?>
         <div id="sidebar">

            <div id="sidebar_menu">
               <ul>
                  <li>System
                     <ul id="sidebar_submenu_visible">
                        <li><a href="logout.php">Logout</a></li>
<?php if ($GLOBALS["vpm_issuperuser"] == 1) { ?>
                        <li><a href="system_defaults.php">Defaults</a></li>
<?php } #  superuser menu  ?>
<?php if ($GLOBALS["vpm_issuperuser"] == 1 ||
      $GLOBALS["vpm_isadminuser"] == 1) { ?>
                        <li><a href="system_bulkuser.php">Bulk User Maint</a></li>
<?php } #  admin/superuser menu  ?>
                     </ul>
                  </li>

<?php if ($GLOBALS["vpm_isadminuser"] == 1) { ?>
                  <li><a href="domain_select.php">Domains</a>
                     <ul id="sidebar_submenu_visible">
                        <li><a href="domain_select.php">Select</a></li>
<?php if ($_SESSION["vpmsession_selecteddomain"] != "") { ?>
                        <li><a href="domain_view.php">View/Edit/Delete</a></li>
                        <li><a href="domain_defaults.php">Defaults</a></li>
<?php } #  domain selected  ?>
<?php if ($GLOBALS["vpm_issuperuser"] == 1) { ?>
                        <li><a href="domain_create.php">Create</a></li>
<?php } #  superuser menu  ?>
                     </ul>
                  </li>
<?php } #  admin menu  ?>

<?php if ($GLOBALS["vpm_issuperuser"] == 1) { ?>
                  <li>Admin Users
                     <ul id="sidebar_submenu_visible">
                        <li><a href="adminuser_lookup.php">View/Edit/Delete</a></li>
                        <li><a href="adminuser_create.php">Create</a></li>
                     </ul>
                  </li>
<?php } #  superuser menu  ?>
<?php if ($GLOBALS["vpm_issuperuser"] != 1 &&
      $GLOBALS["vpm_isadminuser"] == 1) { ?>
                  <li>Admin Users
                     <ul id="sidebar_submenu_visible">
                        <li><a href="adminuser_passwd.php">Change Password</a></li>
                     </ul>
                  </li>
<?php } #  admin menu  ?>

<?php if ($_SESSION["vpmsession_selecteddomain"]) { ?>
                  <li>Mail Users
                     <ul id="sidebar_submenu_visible">
<?php if ($GLOBALS["vpm_isadminuser"] == 1) { ?>
                        <li><a href="user_lookup.php">Lookup</a></li>
                        <li><a href="user_create.php">Create</a></li>
<?php } #  admin menu  ?>
<?php if (array_key_exists("vpmsession_selecteduser", $_SESSION)
      && $_SESSION["vpmsession_selecteduser"]) { ?>
                        <li><a href="user_view.php">Account</a></li>
<?php
   if ($GLOBALS["vpm_isadminuser"] == 1 
         || $_SESSION["vpmsession_allowuserspamcontrol"]) {
?>
                        <li><a href="user_settings.php">Settings</a></li>
                        <li><a href="user_rules.php">Rules</a></li>
<?php } #  user spam control  ?>
<?php } #  user is selected  ?>
                     </ul>
                  </li>
<?php } #  domain is selected  ?>

                  <li><!-- <a href="user.php">-->Help<!-- </a> -->
                     <ul id="sidebar_submenu_visible">
                        <li><a href="help_gettingstarted.php">Getting
                           Started</a></li>
<?php
   if ($GLOBALS["vpm_isadminuser"] == 1 
         || $_SESSION["vpmsession_allowuserspamcontrol"]) {
?>
                        <li><a href="help_settings.php">Settings Help</a></li>
                        <li><a href="http://lists.tummy.com/mailman/listinfo/vpostmaster/">Mailing List</a></li>
<?php } #  user spam control  ?>
                     </ul>
                  </li>

               </ul>
            </div>
         </div>
<?php } #  logged in menu  ?>
      </td></tr>
   </table></td>

   <td valign="top">
      <?php echo vpm_get_alertmessage(); ?>

      <!-- Body -->
