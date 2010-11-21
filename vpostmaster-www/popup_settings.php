<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

?>

<h1>vPostMaster Settings Documentation</h1>

<a name='addheaders'><h2>Add Spam Headers</h2>
<p />If enabled, information describing how the message was classified are
added to the header of all messages.

<h3>Enabled</h3>
<p />Modify the headers of messages which have been identified as
spam.  This setting requires an additional copy of all messages be done,
which on a heavily loaded mail server may cause performance problems.
<h3>Disabled <b>(Recommended)</b></h3>
<p />Do not modify the headers of messages which have been identified as
spam.

<p /><hr />
<a name='prefix'><h2>Spam Subject Prefix</h2>
<p />If set to a non-blank string, this string will be prefixed to the
subject of any messages which the system quarantines as spam.  A typical
setting for this might be '[SPAM] ' (with a trailing space).  Some e-mail
clients can only filter based on the subject and not arbitrary headers.
This allows you to have your e-mail client file spam into a separate
folder after downloading, instead of folders on the server.

<p />Enabling this may decrease delivery performance because all messages
must be copied an extra time during delivery.  Note that "Add Spam Headers"
also requires a copy, but that enabling both options does not result in two
copies.  The copies are combined, so there is no additional performance
penalty.

<?php
   if ($GLOBALS["vpm_isadminuser"] != 1 
         && $_SESSION["vpmsession_allowuserspamcontrol"]) {
?>

<p /><hr />
<a name='clamav'><h2>ClamAV</h2>
<p />ClamAV Anti-virus engine. Scans the body of email messages for
viruses. This option identifies the action which is taken on messages
which match virus signatures. "Drop" is the default.

<p />Because the message body is required, this check is done at delivery
time, not during the initial SMTP phase.

<h3>Disabled</h3>
<p />Email is not scanned for viruses.  You may want to choose this option
if you have a high volume of email deliveries and your email server
can't keep up with the load.

<h3>Drop <b>(Recommended. Default)</b></h3>
<p />An email message which is identified as containing a virus is
discarded without notifying the sender or the recipient.  This is the
recommended ClamAV setting because most viruses are sent with forged or
invalid From addresses.

<p />The message receipt has been logged in the system mail logs. If
you believe a message is getting improperly dropped, the mail system
administrator can see if it was dropped via ClamAV in the system mail
logs. The mail log should also contain how ClamAV classified the email
(as clean or as virus containing and the identified virus).

<p />In general, ClamAV doesn't have many false positives (where a message
is mistakenly thought to be a virus), so this setting is relatively safe.

<h3>Quarantine</h3>
<p />An email message which is identified as containing a virus is placed
in the user's Quarantine folder (IMAP and Webmail). In the case of POP3,
the folder is created, but the user would have to use IMAP or Webmail
to access the folder.

<p />If forwarding is enabled, quarantined messages are not forwarded to
the destination account.

<p />Quarantine folders require regular maintenance to clean them
out. This has to be set up manually, vPostMaster doesn't currently
include any automatic maintenance of Quarantine folders.

<p />This setting is not a recommended setting for two reasons: the
maintenance requirement and the increased risk of a virus getting through
to a vulnerable computer.

<p />The message receipt has been logged in the system mail logs. If
you believe a message is getting improperly quarantined, the mail system
administrator can see if it was delivered to be scanned by ClamAV in the
system mail logs. The mail log should also contain how ClamAV classified
the email (the identified virus signature).

<h3>Reject (see note) <b>(Not recommended</b>, see note)</h3>
<p />An email message which is identified as containing a virus is
returned to the sender in the envelope From address.  Note that if you
enable this, you should at a minimum also enable <a href="#SPF">SPF</a>, to
reduce the number of forged sender addresses you get.  However, using this
setting is a very, very bad idea.

<p />ClamAV needs to scan the message body in order to identify viruses.
If the email message contains a virus, and Reject is set, a rejection mail
message will be sent back to the address in the envelope From header.
This means your server has to accept the mail, and resend it to the
envelope From sender. If the envelope From sender is a forged or invalid
address, the rejection message will either reach an innocent third party,
or it will bounce back to your server (double bounce). Because of the
increased overhead of double bounces and the risk of propagating viruses
to innocent recipients, this is not a recommended sending.

<p />The message receipt has been logged in the system mail logs. If
you believe a message is getting improperly rejected, the mail server
administrator can see if it was delivered to be scanned by ClamAV in the
system mail logs. The mail log should also contain how ClamAV classified
the email (the identified virus signature).

<p /><hr />
<a name='SPF'><h2>SPF (Sender Policy Framework)</h2>

<p />Action taken on an SPF match. Sender Policy Framework (SPF) is an
authentication mechanism for email servers.  Sender Policy Framework
(SPF) makes it easier to identify spoofed email (email with forged
From addresses).

<p />SPF is not recommended on mailboxes which are the destination for
other forwarded mailboxes, except when those intermediate mail servers
support SPF-compatible forwarding.  This mechanism is called "SRS" (Sender
Rewriting Scheme), see the <a
href="http://www.openspf.org/srs.html">OpenSPF SRS page</a> for more
information.

<p />This check is done during the SMTP phase, and so "reject" can safely
be done without risk of generating a message to an innocent third party.

<p />SPF checking results in three possible outcomes.

<p />1. Not enough information: The incoming mail server domain doesn't
publish an SPF record. SPF check is skipped. In this case, there isn't
enough information to deny delivery, so the email is passed onto the
next stage of SMTP authorization for handling.

<p />2. Forged email address detected: The incoming mail server domain
publishes SPF, but the the incoming mail server is not listed in the SPF
record. In this case, when Reject is selected, the remote email server
is notified that the email has been rejected due to SPF failure. The
email is not processed further after this.

<p />3. SPF check passes: The incoming mail server domain publishes
SPF and the incoming mail server is on that list.  This means that mail
passes this stage and can be processed by the next SMTP phase.

<h3>Disabled</h3>

<p />SPF checking is not performed on incoming mail.

<p />SPF is a very inexpensive test to perform on incoming mail because
it doesn't take many server or network resources to do.  We recommend that
you enable SPF unless you have a specific reason not to enable it.  If SPF
is blocking particular messages which you wish to receive, it's best to
white-list the sender which is having problems via the Rules settings.

<p />vPostMaster mail servers do <b>not</b> automate the configuration
of your outgoing mail to use SPF.  To configure SPF for your outgoing
e-mail, please go to <a href="http://spf.pobox.com/">The SPF Homepage
(http://spf.pobox.com)</a> and use their wizard to create the SPF record
for your domain.  You will then need to set it up in your DNS server.

<h3>Quarantine</h3>
<p />SPF Quarantine means that when SPF checking detects a forged email
address, the message is accepted by the local server and delivered to
the users Quarantine email folder.

<p />An email message which doesn't pass SPF authentication is placed in
the user's Quarantine folder (IMAP and Webmail). In the case of POP3,
the folder is created, but the user would have to use IMAP or Webmail
to access the folder.

<p />Quarantine folders require regular maintenance to clean them
out. This has to be set up manually, vPostMaster doesn't include any
automatic maintenance of Quarantine folders.  This is not a recommended
setting for two reasons: the maintenance requirement and the increased
risk of unwanted getting through.

<p />The message receipt has been logged in the system mail logs. If you
believe a message is getting improperly quarantined, you can see if it
was delivered to be authenticated by SPF in the system mail logs. The
mail log should also contain how SPF classified the email (rejected,
passed, or not able to check).

<h3>Reject <b>(Recommended. Default)</b></h3>
<p />SPF Reject means that when SPF checking detects a forged email
address, the message is not accepted by the local server and the remote
server must handle the bounce.

<p /><hr />

<?php } #  user spam control  ?>

<a name='greylist'><h2>Greylist</h2>
<p />Action taken on a greylist match. Greylisting is distinct
from white-listing (always allowing email that matches a pattern) or
blacklisting (always banning email that matches a pattern).  It is an
automatic, self-learning system for detecting certain common spam and virus
delivery mechanisms.

<p />Greylisting delays delivery of mail the first time an email sender
attempts to deliver to an email address by returning a soft bounce
(please try again later) to the sending email server.

<p />Most legitimate email senders will respect the soft bounce and
retry later. Spam email senders most often do not resend. In the
case where they do retry, the time delay has allowed enough time for
the spam signature to be added to an RBL (real-time blacklist) or to
Distributed Checksum databases, thus preventing delivery.

<p />Some hosts are not compatible with greylisting, either because
they use a different IP address or sender address on every delivery
attempt.  This means that greylisting will slow delivery from these
hosts repeatedly, and have the practical effect of blocking email from
being delivered.  In many cases, vPostMaster knows about these servers
which are greylist incompatible, but have strong anti-spam policies.

<p />In cases where your legitimate senders continue to have their e-mail
delayed, you may wish to use the "Rules" configuration to allow e-mail from
these users unconditionally.

<p />Greylisting will have no effect on email from most of your
regular correspondents. It will have the effect of blocking up to 90%
of incoming e-mail prior to other, more expensive, anti-spam filters
such as SpamAssassin and ClamAV. This can significantly reduce the load
on your mail server, and can make the difference between being able to
keep up with your email load, and not being able to get email at all.

<h3>Disabled</h3>
<p />Greylisting is disabled

<p />This setting is recommended for anyone who cannot tolerate a delay in
incoming e-mail delivery.

<h3>Quarantine</h3>
<p />This setting allows you immediate access to messages that are
greylisted, but it also accepts a huge volume of other spam and viruses
into the quarantine folder as well.  So, initial messages are not delayed,
but the result may be that your quarantine folder grows extremely quickly.

<p />An email message which is greylisted is placed in the user's
Quarantine folder (IMAP and Webmail). In the case of POP3, the folder
is created, but the user would have to use IMAP or Webmail to access
the folder.

<p />Quarantine folders require regular maintenance to clean them
out. This has to be set up manually, vPostMaster doesn't include any
automatic maintenance of Quarantine folders.  This is not a recommended
setting for two reasons: the maintenance requirement and the increased
risk of unwanted email getting through.

<p />The message receipt has been logged in the system mail logs. If you
believe a message is getting improperly quarantined, you can see if it
was greylisted in the system mail logs.

<h3>Learn <b>(Recommended)</b></h3>
<p />Seed the greylist database without rejecting messages.  This is the
recommended setting for a new email system for the first several days
or weeks. This setting builds up the greylisting database of sender
addresses to learn your regular correspondence patterns, without
delaying any mail. Once the greylisting database has been primed, you
can enable greylisting.  This initial learning phase will reduce the delays
on e-mail from regular correspondents.  You can leave the greylisting
setting at 'Learn' for as long as you like.

<h3>Enabled <b>(Recommended, Default)</b></h3>
<p />Greylisting is fully enabled.

<p />Greylisting delays the delivery of mail from new senders in order
to block spam.

<p />Each incoming message is looked up in the greylisting database by
the sender email address, the recipient email address and the sending
email server. If there is no record matching those 3 items, an entry
is created, and a soft bounce (please try again later) is sent back to
the sending email server. If there is a record matching those fields,
the time is checked.  If the timeout has not been reached another soft
bounce is sent (please try to send the email again later).  If the time
has been longer than the greylist timeout since the initial delivery
attempt, the email is passed through for further processing.

<p /><hr />
<a name='greylist_timeout'><h2>Greylist Timeout</h2>
<p /><b>Default is 60 minutes.</b>

<p /><b>Recommend</b> between 1 minute and 60 minutes. Should never be
more than 120 minutes. Messages from new senders are temporarily delayed
for at least this many minutes. Messages from regular correspondents are
not delayed. This is the earliest time that mail from a new sender can
be received. Mail delivery may take longer than this time, depending on
how frequently remote mail senders attempt delivery.

<p />This timeout is only used if greylisting is enabled or set to
quarantine. If greylisting is disabled, or learning this timeout has
no effect.

<p /><hr />
<a name='spamassassin'><h2>SpamAssassin</h2>
<h3>Action</h3>

<p />Action taken on a SpamAssassin match. 

<p />SpamAssassin is rule-based email filtering software. SpamAssassin
scores each incoming email on many different rules. Each rule has a
point value, based on whether it indicates likely spam or non-spam
patterns.  The higher the score, the more likely the message is spam.

<p />5 is the typical threshold between spam and non-spam.  However, some
"spammy sounding" non-spam and non-spam sounding spam may be classified
incorrectly.  Messages scoring above 10 or 15 are almost always spam.

<p />You can set up 3 ranges of SpamAssassin scores, and have different
actions for each range. Very low scoring email is most likely to be
legitimate, and so should be delivered. Intermediate scoring email may
be legitimate or it may be unwanted; you may want to quarantine this
email. High scoring email is likely to be unwanted, you most likely will
discard this email as spam.

<p />If you prefer to quarantine a lot of spam, and let some spam through
to your main box these are the <b>Recommended</b> settings:

<ul>
   <li>Rule 1: Quarantine greater than 5
   <li>Rule 2: Drop greater than 15
   <li>Rule 3: Disabled (no effect)
</ul>

<p />If you prefer not to quarantine spam, and to let some spam through
to your mailbox these are the <b>Recommended</b> aggressive settings
for each level are:

<ul>
   <li>Rule 1: Drop greater than 7
   <li>Rule 2: Disabled (no effect)
   <li>Rule 3: Disabled (no effect)
</ul>

<p />If you prefer not to quarantine spam, and to not let spam through
to your mailbox, but can tolerate losing some legitimate email, these
are the very aggressive settings for each level are:

<ul>
   <li>Rule 1: Drop greater than 3
   <li>Rule 2: Disabled (no effect)
   <li>Rule 3: Disabled (no effect)
</ul>

<p />In this setup, email which scores less than 5 will be delivered,
email which scores between 5 and 15 will be quarantined and email which
scores over 15 will be dropped.

<h3>Action: Disabled</h3>
<p />No impact on message classification, accept message unless other
classifiers decide otherwise.  Email messages at or above this score
threshold less than the next highest threshold, if any, will not receive
a SpamAssassin score.

<h3>Action: Quarantine</h3>
<p />Email messages which score at or above the threshold for this rule
level (and less for the threshold for the next highest rule threshold,
if any) will be Quarantined.

<h3>Action: Drop</h3>
<p />Email messages which score at or above the threshold for this rule
level (and less for the threshold for the next highest rule threshold,
if any) will be Dropped. No notice is sent back to the sender.

<p />This setting should be used at higher score settings, as they are
most likely spam. Dropping spam email prevents innocent email boxes from
being flooded with rejected spam.

<h3>Action: Reject <b>(Not Recommended)</b></h3>

<p />Email messages which score at or above the threshold for this rule
level (and less for the threshold for the next highest rule threshold,
if any) will be Rejected. The sender is notified that their email was
not delivered.  However, this warning is sent to the envelope sender, and
therefore may end up going to an innocent third-party.  If you use Reject,
it is recommended that you also enable <a href="#SPF">SPF</a>.

<p />This setting may be used at lower score settings, as they are
more likely to be legitimate email. At higher scores, this setting will
cause email to be rejected to innocent third parties, as email addresses
are often forged by spammers.

<h3>Threshold</h3>
<p />A threshold is the SpamAssassin score at or above which the
associated level action will apply. If the next highest numbered rule
is set, this threshold will apply up to that score.  This value should be
left blank if you do not want the associated action to apply.

<p />Reasonable threshold levels are:

<p /><ul>
   <li>Rule 1: 5
   <li>Rule 2: 15
   <li>Rule 3: disabled, no threshold set
</ul>
