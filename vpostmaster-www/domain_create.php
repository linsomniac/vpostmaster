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
   $isAdmin = 0;

   #  process the form input
   if (array_key_exists("selectdomain", $_GET)) {
      $_SESSION["vpmsession_selecteddomain"] = $_GET["selectdomain"];
      $_SESSION["vpmsession_selecteduser"] = "";
   }
   if (array_key_exists("selectdomain", $_POST)) {
      $_SESSION["vpmsession_selecteddomain"] = $_POST["selectdomain"];
      $_SESSION["vpmsession_selecteduser"] = "";
   }

   vpm_header("vPostMaster Create Domain");

   $domaininfo = array();
   $domaininfo['name'] = '';
   $domaininfo['active'] = 't';
   $domaininfo['aliasedto'] = '';
   $domaininfo['extensionchar'] = '-';
   $domaininfo['catchalladdress'] = '';
   $domaininfo['maxusers'] = '';
   $domaininfo['maxperuserquota'] = '';
   $domaininfo['allowextraattributes'] = 't';
   $domaininfo['allowuserspamcontrol'] = 't';
   $domaininfo['requireforwardwithindomain'] = 'f';
?>

<h1>Create Domain</h1>

<form method="POST" action="domain_view.php"><table>
   <tr><td><label for="name">Name:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $domaininfo,
            'name', "size=30 id=\"name\""); ?>
      </td><td class="formdesc">
         Name of the domain.  
         <?php
          echo vpm_user_moreinfo_link('popup_domaincreate.php#name',
         '(more...)');
         ?>

      </td></tr>
   <tr><td><label for="aliased">Aliased to:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $domaininfo,
            'aliasedto', "size=30 maxlength=100 id=\"aliased\""); ?>
      </td><td class="formdesc">
         Optional. The domain alias. Email for users at the domain name
         you're creating now is treated as if it had been addressed to the
         domain listed here.
         <?php
          echo vpm_user_moreinfo_link('popup_domaincreate.php#aliasedto',
         '(more...)');
         ?>
      </td></tr>
   <tr><td><label for="extension">Extension character:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $domaininfo,
            'extensionchar', "size=1 maxlength=1 id=\"extension\""); ?>
      </td><td class="formdesc">
         Optional. The separation character for per-user virtual
         sub-accounts. If set, must be be - (hyphen) or + (plus sign).  
         <?php
          echo vpm_user_moreinfo_link('popup_domaincreate.php#extensionchar',
         '(more...)');
         ?>
      </td></tr>
   <tr><td><label for="catchall">Catch-all Address:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $domaininfo,
            'catchalladdress', "size=30 id=\"catchall\""); ?>
      </td><td class="formdesc">
         Optional.  If set, any e-mail delivered for an unknown account will
         be delivered to the account named here.
         <?php 
            echo vpm_user_moreinfo_link('popup_domaincreate.php#catchalladdress',
            '(more...)');
         ?>
      </td></tr>
   <tr><td><label for="max">Maximum Users:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $domaininfo,
            'maxusers', "size=10 maxlength=10 id=\"max\""); ?>
      </td><td class="formdesc">
         Optional. The maximum number of users in the domain.
         <?php 
            echo
            vpm_user_moreinfo_link('popup_domaincreate.php#maxusers',
            '(more...)');
         ?>
      </td></tr>
      </td></tr>
   <tr><td><label for="peruser">Maximum Per-user Quota:</label></td><td>
      <?php vpm_textorinput("vpm_issuperuser", $domaininfo,
            'maxperuserquota', "size=4 maxlength=4 id=\"peruser\""); ?>MB
      </td><td class="formdesc">
         Optional.  Maximum mail account quota, in Megabytes, which users
         of this domain may have.
         <?php 
            echo
            vpm_user_moreinfo_link('popup_domaincreate.php#maxperuserquota',
            '(more...)');
         ?>
      </td></tr>
   <tr><td><label for="active">Active:</label></td><td>
      <input TYPE="checkbox" ID="active" NAME="active" VALUE="t"
            <?php
               if ($domaininfo["active"] == "t") {
                  echo "checked=\"checked\"";
               }
               if ($isAdmin) {
                  echo "disabled=\"disabled\" readonly=\"readonly\"";
               }
               echo vpm_tabindex();
            ?>
            />
      </td><td class="formdesc">
         If not checked, the server acts as if this domain does not exist.
         <?php 
            echo
            vpm_user_moreinfo_link('popup_domaincreate.php#active',
            '(more...)');
         ?>
      </td></tr>
   <tr><td><label for="allowextraattributes">Allow Extra
            Attributes:</label></td><td>
      <input TYPE="checkbox" ID="active" NAME="allowextraattributes" VALUE="t"
            <?php
               if ($domaininfo["allowextraattributes"] == "t") {
                  echo "checked=\"checked\"";
               }
               if ($isAdmin) {
                  echo "disabled=\"disabled\" readonly=\"readonly\"";
               }
               echo vpm_tabindex();
            ?>
            />
      </td><td class="formdesc">
         Extra attributes can be defined in the database, if this field is
         checked then domain admins can view and modify extra attributes.
         <?php 
            echo
            vpm_user_moreinfo_link(
               'popup_domaincreate.php#allowextraattributes', '(more...)');
         ?>
      </td></tr>

   <tr><td><label for="allowuserspamcontrol">Allow User Spam
            Control:</label></td><td>
      <input TYPE="checkbox" ID="active" NAME="allowuserspamcontrol" VALUE="t"
            <?php
               if ($domaininfo["allowuserspamcontrol"] == "t") {
                  echo "checked=\"checked\"";
               }
               if ($isAdmin) {
                  echo "disabled=\"disabled\" readonly=\"readonly\"";
               }
               echo vpm_tabindex();
            ?>
            />
      </td><td class="formdesc">
         Can the users of this domain make changes to their anti-spam
         Settings/Rules?  Administrators can always modify these settings.
         <?php 
            echo
            vpm_user_moreinfo_link(
               'popup_domaincreate.php#allowuserspamcontrol', '(more...)');
         ?>
      </td></tr>

   <tr><td><label for="requireforwardwithindomain">Only Forward
            Within Domain:</label></td><td>
      <input TYPE="checkbox" ID="active" NAME="requireforwardwithindomain"
             VALUE="t"
            <?php
               if ($domaininfo["requireforwardwithindomain"] == "t") {
                  echo "checked=\"checked\"";
               }
               if ($isAdmin) {
                  echo "disabled=\"disabled\" readonly=\"readonly\"";
               }
               echo vpm_tabindex();
            ?>
            />
      </td><td class="formdesc">
         Restrict users of this domain from setting up forwards to other
         addresses outside this domain?
         <?php 
            echo
            vpm_user_moreinfo_link(
               'popup_domaincreate.php#requireforwardwithindomain', 
               '(more...)');
         ?>
      </td></tr>


   <tr><td colspan="3"><input type="submit" name="submit" value="Create"
         <?php echo vpm_tabindex(); ?> /></td></tr>
</form></table>
