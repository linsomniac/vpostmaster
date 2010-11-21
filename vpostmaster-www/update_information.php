<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();
   vpm_header("vPostMaster Update Information");
   $info = vpm_checkprivs(FALSE, TRUE);

   if (!$GLOBALS["vpm_update_available"]) {
?>

   <h1>No updates available</h1>

   You are currently running the latest available version of
   <a href="http://www.tummy.com/Products/vpostmaster/">vPostMaster</a>,
   version <?php echo $GLOBALS['vpm_version']; ?>.

<?php
   } else {
?>

   <h1>New Update Available</h1>

   Latest available version: <?php echo $_SESSION["vpmsession_vpm_latest_version"] ?><br />
   Installed version: <?php echo $GLOBALS['vpm_version']; ?><br />

   <p />
   <a href="http://www.tummy.com/Products/vpostmaster/">vPostMaster</a>
   is available.  Please visit the
   <a href="http://www.tummy.com/Products/vpostmaster/releasenotes.html">vPostMaster
   Release Notes Page</a> for more information about the available updates.

<?php
   }
?>
