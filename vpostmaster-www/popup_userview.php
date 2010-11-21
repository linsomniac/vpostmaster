<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

?>

<?php if (strstr($_SERVER["REQUEST_URI"], "create.php") == FALSE) { ?>
   <h1>vPostMaster Edit Mail User Documentation</h1>
<?php } else { ?>
   <h1>vPostMaster Edit Mail User Documentation</h1>
<?php } ?>

<a name='active'><h2>Active Mail Acceptance</h2>
<p />If checked, the user may receive e-mail. Default setting.
<p />If unchecked, e-mail to the user will be rejected. This can be used
to temporarily de-activate a mail user for some period before deleting
their account completely.

<a name='local'><h2>Local Delivery Options</h2>
<p />Checked (Default).  If checked, e-mail to this user will be stored on the server for the user to read.  
<p />Forward. If this is a forward-only account, set the "Forward To" field and uncheck this box.
<p />Store and Forward. To forward but also save a copy on this account, check this box <b>and</b> set the "Forward To" field.

<a name='forward'><h2>Forward To Address</h2>
<p />Optional.
<p />E-mail is forwarded to the addresses listed here.  List the full e-mail address, including "@", one address per line.  
<p /><strong>Note:</strong> When forwarding, vpostmaster will try to use
the destination account rules.  However, any account in the forwarding
chain which is forwarding to multiple destionations or has local delivery
enabled as well as forwarding will stop the redirection and that account's rules will apply.  This can lead to confusion where spam "sneaks by" because a forwarding account's rules are applied to the incoming message delivered to another box.  

<a name='quota'><h2>Mailbox Size Quota</h2>
<p />Optional.
<p />If set, specifies the maximum size of the users mail-box, in megabytes.  This may be limited to a maximum value by the system administrator.  
<p />If left blank, the system does not impose a limit on mailbox size.  Note that this is only a rough size limitation, in some cases a mailbox which is not yet over quota may be allowed to receive a message which will push the mailbox over quota.  
<p />The per user mailbox quota will be no larger than the domain maximum quota, if the domain maximum quota is set.

