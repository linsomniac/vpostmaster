<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */


   ######################################################
   function vpm_validate_greylisttimeoutminutes($value) {   #{{{1
      if (!preg_match("/^[0-9]+$/", $value)) {
         return("Invalid value, must be a number from 1 to 120.");
         }
      if (((int) $value) < 1 || ((int) $value) > 120) {
         return("Invalid range, must be a number from 1 to 120.");
         }
      return("");
   }


   ##############################################
   function vpm_validate_addspamheaders($value) {   #{{{1
      if (!preg_match("/^(disabled|enabled)+$/", $value)) {
         return("Invalid value, please try again.");
         }
      return("");
   }


   #################################################
   function vpm_validate_spamsubjectprefix($value) {   #{{{1
      if (strlen($value) > 20) {
         return("Subject prefix must be 20 characters or less.");
         }
      return("");
   }


   ############################################
   function vpm_validate_clamavaction($value) {   #{{{1
      if (!preg_match("/^(disabled|quarantine|drop|reject)+$/", $value)) {
         return("Invalid value, please try again.");
         }
      return("");
   }


   #########################################
   function vpm_validate_spfaction($value) {   #{{{1
      if (!preg_match("/^(disabled|quarantine|reject)+$/", $value)) {
         return("Invalid value, please try again.");
         }
      return("");
   }


   ##############################################
   function vpm_validate_greylistaction($value) {   #{{{1
      if (!preg_match("/^(disabled|quarantine|learn|enabled)+$/", $value)) {
         return("Invalid value, please try again.");
         }
      return("");
   }


   ###################################################
   function vpm_validate_spamassassin1action($value) {   #{{{1
      if (!preg_match("/^(quarantine|drop|reject|disabled|accept)+$/",
            $value)) {
         return("Invalid value, please try again.");
         }
      return("");
   }


   ######################################################
   function vpm_validate_spamassassin1threshold($value) {   #{{{1
      if ($value == "") { return(""); }
      if (!preg_match("/^-?[0-9]+(\.[0-9])?$/", $value)) {
         return("Invalid value, must be a number from -30.0 to 30.0.");
         }
      if (((float) $value) < -30.05 || ((float) $value) > 30.05) {
         return("Invalid range, must be a number from -30.0 to 30.0.");
         }
      return("");
   }


   ###################################################
   function vpm_validate_spamassassin2action($value) {   #{{{1
      return(vpm_validate_spamassassin1action($value));
   }


   ######################################################
   function vpm_validate_spamassassin2threshold($value) {   #{{{1
      return(vpm_validate_spamassassin1threshold($value));
   }


   ###################################################
   function vpm_validate_spamassassin3action($value) {   #{{{1
      return(vpm_validate_spamassassin1action($value));
   }


   ######################################################
   function vpm_validate_spamassassin3threshold($value) {   #{{{1
      return(vpm_validate_spamassassin1threshold($value));
   }


   ######################
   $vpm_settings = array();

	$vpm_settings[] = array("addspamheaders",   #{{{1
			"Add Spam Status Headers",
			array(
				array("disabled", "Disabled"),
				array("enabled", "Enabled"),
			),
			1,
			NULL,
			"If set to 'Enabled', headers are added to the message on delivery".
			" to indicate the results of the various tests run on the message.".
			"  This makes debugging why a message was classified as it was" .
			" easier, but does decrease mail delivery performance.  ".
			vpm_user_moreinfo_link('popup_settings.php#addheaders',
			'(more...)'),
		);

	$vpm_settings[] = array("spamsubjectprefix",   #{{{1
			"Spam Subject Prefix",
			NULL,
			0,
			"size=10 maxlength=20",
			"If set to a non-blank string, this string will be prefixed to the".
			" subject of any messages which the system believes are spam." .
			"  Often this might be '[SPAM] ' (with a trailing space).  Some" .
			" e-mail clients can only filter based on the subject and not" .
			" arbitrary headers.  This allows you to have your e-mail client" .
			" file spam into a separate folder after downloading, instead of" .
			" folders on the server.  Enabling this may decrease delivery" .
			" performance.  " .
			vpm_user_moreinfo_link('popup_settings.php#prefix',
			'(more...)'),
		);

	$vpm_settings[] = array("clamavaction",   #{{{1
			"ClamAV Action",
			array(
				array("disabled", "Disabled"),
				array("drop", "Drop"),
				array("quarantine", "Quarantine"),
				array("reject", "Reject (See note)"),
			),
			1,
			NULL,
			"ClamAV Anti-virus engine.  This action is taken on messages " .
			"which are identified as viruses.  Most viruses specify false " .
			"sender addresses, so 'Drop' is recommended over 'Reject' (which " .
			"will typically result in the virus being sent to an innocent " .
			"third-party).  " .
			vpm_user_moreinfo_link('popup_settings.php#clamav',
			'(more...)'),
		);

	$vpm_settings[] = array("spfaction",   #{{{1
			"SPF Action",
			array(
				array("disabled", "Disabled"),
				array("quarantine", "Quarantine"),
				array("reject", "Reject"),
			),
			1,
			NULL,
			"Action taken on an SPF match.  " .
			vpm_user_moreinfo_link('popup_settings.php#SPF',
			'(more...)'),
		);

	$vpm_settings[] = array("greylistaction",   #{{{1
			"Greylist Action",
			array(
				array("disabled", "Disabled"),
				array("quarantine", "Quarantine"),
				array("learn", "Learn"),
				array("enabled", "Enabled"),
			),
			1,
			NULL,
			"Action taken on a greylist match.  'Disabled' disables " .
			"greylisting, " .
			"'Quarantine' causes initial greylisted messages to be delivered " .
			"to your quarantine folder.  'Learn' will seed the greylist " .
			"database without rejecting messages.  'Enabled' fully enables" .
			"greylisting.  " .
			vpm_user_moreinfo_link('popup_settings.php#greylist',
			'(more...)'),
		);

	$vpm_settings[] = array("greylisttimeoutminutes",   #{{{1
			"Greylist Timeout",
			NULL,
			1,
			"size=3 maxlength=3",
			"Timeout in minutes for greylisting ban.  Recommend between 1 " .
			"minute and 60 minutes.  Should never be more than 120 minutes.  " .
			"Messages from new senders are temporarily disabled for at least " .
			"this many minutes.  Messages from regular correspondents are not ".
			"delayed.  Delay times are dependant on how frequently remote " .
			"mail servers attempt delivery, so delays may be longer than this.".
			"  " .
			vpm_user_moreinfo_link('popup_settings.php#greylist_timeout',
			'(more...)'),
		);

	$vpm_settings[] = array("spamassassin1action",   #{{{1
			"SpamAssassin Action 1",
			array(
				array("disabled", "Disabled"),
				array("accept", "Accept"),
				array("quarantine", "Quarantine"),
				array("drop", "Drop"),
				array("reject", "Reject"),
			),
			1,
			NULL,
			"Action taken on SpamAssassin threshold match.  " .
			vpm_user_moreinfo_link('popup_settings.php#spamassassin',
			'(more...)'),
		);

	$vpm_settings[] = array("spamassassin1threshold",   #{{{1
			"SpamAssassin Threshold 1",
			NULL,
			0,
			"size=5 maxlength=5",
			"Score threshold above which this rule matches.  " .
			"Messages which score below any threshold will be accepted.  " .
			"5.0 is a good balance between catching most spam while not " .
			"marking too many non-spams.  10.0 rarely matches non-spam and " .
			"15.0 almost never matches non-spam.  Higher values let through " .
			"more spam, while not catching as much non-spam (false " .
			"positives).  " .
			vpm_user_moreinfo_link('popup_settings.php#spamassassin',
			'(more...)'),
		);

	$vpm_settings[] = array("spamassassin2action",   #{{{1
			"SpamAssassin Action 2",
			array(
				array("disabled", "Disabled"),
				array("accept", "Accept"),
				array("quarantine", "Quarantine"),
				array("drop", "Drop"),
				array("reject", "Reject"),
			),
			1,
			NULL,
			"Second SpamAssassin threshold rule.  " .
			vpm_user_moreinfo_link('popup_settings.php#spamassassin',
			'(more...)'),
		);

	$vpm_settings[] = array("spamassassin2threshold",   #{{{1
			"SpamAssassin Threshold 2",
			NULL,
			0,
			"size=5 maxlength=5",
			"Score threshold above which this rule matches.  " .
			vpm_user_moreinfo_link('popup_settings.php#spamassassin',
			'(more...)'),
		);

	$vpm_settings[] = array("spamassassin3action",   #{{{1
			"SpamAssassin Action 3",
			array(
				array("disabled", "Disabled"),
				array("accept", "Accept"),
				array("quarantine", "Quarantine"),
				array("drop", "Drop"),
				array("reject", "Reject"),
			),
			1,
			NULL,
			"Third SpamAssassin threshold rule.  " .
			vpm_user_moreinfo_link('popup_settings.php#spamassassin',
			'(more...)'),
		);

	$vpm_settings[] = array("spamassassin3threshold",   #{{{1
			"SpamAssassin Threshold 3",
			NULL,
			0,
			"size=5 maxlength=5",
			"Score threshold above which this rule matches.  " .
			vpm_user_moreinfo_link('popup_settings.php#spamassassin',
			'(more...)'),
		);

?>
