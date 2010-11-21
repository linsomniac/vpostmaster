<?php
      /*
      Copyright (c) 2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();
   require_once("settings.php");
   $info = vpm_checkprivs(FALSE, TRUE);

   $initialLoad = 1;
   $errors = array();
   $errorfields = array();

   $info["textareadata"] = '';
   $info["filename"] = '';
   $info["tmp_name"] = '';
   $info["mode"] = 'invalid';

   $ret = vpm_validateform("submit", "Submit button", "/Apply/", 0, 10, 1);
   if ($ret[0] == "successful") {
      $initialLoad = 0;

      $ret = vpm_validateform("textareadata", "Manual CSV Entry", "", 1, 4000,
            1);
      $info["textareadata"] = $ret[1];
      if ($ret[0] != "successful" ) {
         array_push($errors, $ret[2]);
         $errorfields['textareadata'] = 1;
      } else {
         $info["mode"] = 'textarea';
      }

      if (array_key_exists('filedata', $_FILES)
            && $_FILES['filedata']['error'] != UPLOAD_ERR_NO_FILE) {
         if (array_key_exists('name', $_FILES['filedata'])
               && $_FILES['filedata']['name']) {
            $info["filename"] = $_FILES['filedata']['name'];
         }
         if (array_key_exists('tmp_name', $_FILES['filedata'])
               && $_FILES['filedata']['tmp_name']) {
            $info["mode"] = 'file';
            $info['tmp_name'] = $_FILES['filedata']['tmp_name'];
         }
      }

      if ($info['mode'] == 'invalid') {
         $errors[] = 'You must either upload a file or enter CSV data in ' .
               'the text area.';
      }
   }

   #  get list of domains they can administer
   $domainInfo = '';
   if ($GLOBALS["vpm_issuperuser"]) { $domainInfo = "*\0"; }
   else {
      foreach (vpm_admindomainlist() as $adminDomainName) {
         $domainInfo = $domainInfo . $adminDomainName . "\0";
      }
   }

   if (!$initialLoad && count($errors) == 0) {
      if ($info['mode'] == 'textarea') {
         $ret = vpm_call_helper("bulkadd\0" . $domainInfo . "\n",
               "vpostmaster", $info['textareadata']);
      } else {
         $ret = vpm_call_helper("bulkadd\0" . $domainInfo . "\n",
               "vpostmaster", FALSE, $info['tmp_name']);
      }
      if ($ret[0] == "SUCCESSFUL") {
         vpm_add_alertmessage('Successfully bulk managed users!');
         header('Location: system_bulkuser_success.php');
         exit;
      }
      $errors = $ret[1];
   }

   vpm_header("vPostMaster Bulk User Maintenance");

   if (!$initialLoad && count($errors) > 0) {
      echo "<h2><font color=\"#ff0000\">Errors:</font></h2>";
      echo "<p />The following errors were found in your form.  Please " .
            "correct them and submit the form again.";
      echo "<ul>";
      foreach ($errors as $error) { echo "<li />$error"; }
      echo "</ul>";
   }

?>

<form enctype="multipart/form-data" action="system_bulkuser.php" method="POST">
   <input type="hidden" name="MAX_FILE_SIZE" value="1000000" />

   <h1>Bulk User Maintenance</h1>

   <h2>User Data:</h2>
   Upload CSV Format File: <input name="filedata" type="file"
         value="<?php echo $info['filename']; ?>" /><br />

   <hr />
      <b>Upload a file above, OR manually type CSV information below:</b>
   <hr />

   <p /><textarea id="" name="textareadata" cols="60"
         rows="8"><?php echo $info['textareadata']; ?></textarea>

   <p />Simple Example:
   <p /><pre>action,address,password
newuser,user1@a.example.com,qwertyuiop
newuser,user2@a.example.com,wertyu
newuser,user1@b.example.com,ertyuiop
rmuser,user2@b.example.com,</pre>

   <p />More complicated example with locally-defined "fullname" field:
   <p /><pre>action,domain,user,crypted-password,quota,extra_fullname
newuser,a.example.com,user5,1D/Yuy4Kgn2jM,,User Number 5
newuser,a.example.com,user6,o1bKXe4JncAUE,128,User Six
rmuser,a.example.com,user5,,,
newuser,c.example.com,user7,gdhrR968jWuBs,100,Seven of Seven</pre>

   <h2>Submit:</h2>
   <p /><input type="submit" name="submit" value="Apply" />
</form>
