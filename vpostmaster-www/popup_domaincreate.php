<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

?>

<?php if (strstr($_SERVER["REQUEST_URI"], "create.php") == FALSE) { ?>
   <h1>vPostMaster Domain Edit/View Documentation</h1>
<?php } else { ?>
   <h1>vPostMaster Domain Create Documentation</h1>
<?php } ?>
<h1>vPostMaster Domain Create Documentation</h1>

<a name='name'><h2>Domain Name</h2>

<p />The name of the domain part you wish to handle e-mail for.  Usually
this would be at least a name followed by a "." and a <acronym
title="Top Level Domain">TLD</acronym>.  For example, "example.com".

<p />You need to have registered the domain name with a registrar in order
to be able to use it. You can register domains at
<a href="http://www.tummy.com/Hosting/Addons/domains.html">tummy.com</a> or
other registrars for a small fee.

<p />You need to have a DNS server set up in order for mail to be
delivered.  You can set up your own DNS server using <a
href="http://www.isc.org/sw/bind/"><acronym
title="Berkeley Internet Name Domain">BIND</acronym></a>
or similar software.  Hosting your own DNS usually requires two different
servers or IP addresses, and is recommended only for advanced users.

<p />Alternately, you can use third-party domain name service such as:

<ul>
<li><a href="http://www.tummy.com/Hosting/Addons/dns.html">tummy.com, ltd.
Commercial DNS Service</a>
<li><a href="http://soa.granitecanyon.com/">Granite Canyon Public DNS</a>
<li><a href="http://www.google.com/search?q=free+dns">Search Google for a
Free DNS provider (Note: Many listed services may have "strings attached")</a>
</ul>

<p />Once you have your domain registered and configured, vPostMaster can
be used as the mail server.

<a name='aliasedto'><h2>Domain Alias (Optional)</h2>

<p />
The domain specified in the "Name" field is treated exactly like the domain
specified in this field.  This is convenient if you have multiple domains
which you want to act exactly the same.  Note that users cannot POP/IMAP
from this domain.  This only works if the domain name listed here is on the
same vPostMaster server as the domain listed under "Name" above.

<p />Example:
You have a domain named "example.com" which your company e-mail
users exist in.  You want to make mail sent to "example.net" to also reach
these same users.  To do this, you create "example.net" with the <b>Aliased
to</b> field set to example.com.

<p />You don't want to set this if you only have one domain name.  This may
only be set to domains which exist on this same server under the control of
vPostMaster.

<p />If set, all e-mail coming in to the domain listed in "Name" above is
delivered as if it had been addressed to users in the specified domain.

<p />If you set this field, all settings below have no effect,
because the settings of the other domain are in effect.  In other words, the
settings for example.com are used, following the example above.

<a name='extensionchar'><h2>Extension Character (Optional, Recommended)</h2>

<p />Must be either "-" (hyphen), or "+" (plus) character, without the
quotes.

<p />If set, users may receive e-mail to any address beginning with their
user name, followed by the extension character and any additional string.
This is typically used for "uniquely identifiable" e-mail addresses.  For
example, if a company named "example.com" requests your e-mail address, you
can give them "user-example.com@mydomain.example.org".  If they begin
abusing or sell this address, it is easy using the "Rules" to block any
attempt at sending e-mail to this address and only this address.

<a name='catchalladdress'><h2>Catch-all Address (Optional)</h2>

<p />If set, any e-mail delivered for an unknown account in this domain
will be delivered to the named account.  This account must exist in the
current domain , it may not be an address in another domain.  In other
words, it may not contain an "@".  However, that account may specify an
address to forward to.

<p />This allows e-mail that would otherwise be bounced to be read by
a user.  We do not recommend you set up a catch-all account because it can
be a huge problem if spammers try a "dictionary attack" on your domain.

<p />If you do decide to create a catch all account, please use an account
you check regularly.

<a name='maxusers'><h2>Maximum Number of Users (Optional)</h2>

<p />If not blank, this specifies the maximum number of users that are
allowed to be created in the domain.  This value may only be set by a
superuser-level account.  Domain administrators may not modify it, and are
constrained to creating at most this many users within the domain.

<a name='maxperuserquota'><h2>Maximum User Mailbox Size (Optional)</h2>

<p />The maximum number of megabytes in a user mailbox.  This value may
only be set by a superuser-level account.  Domain administrators cannot
set per-user quotas to be greater than this value.


<a name='active'><h2>Active</h2>

<p />If this box is not checked, mail for any user in this domain will be
refused.

<p />Clear this checkbox to disable mail delivery for this domain.  This is
like deleting a domain, without losing the settings, user accounts, and
e-mail within the domain.  For example, if you run a commercial e-mail
server and the domain owner has missed a payment, you may wish to
De-activate their domain for 30 days, in case they submit a payment.

<p />Deleting the domain, in contrast, would require all user accounts be
re-created.

<a name='allowextraattributes'><h2>Allow Extra Attributes</h2>

<p />Extra settings are values that the system administrator can set which
are set on a per-user basis.  These values are not used by the mail system
at all, directly, and can be used for administrative or other functions.

<p />Extra settings show up in the user management page, and are only
able to be changed by a system or domain administrator.

<p />This setting on the domain enables or disables the domain from being
able to edit or see these extra attributes.  Extra attributes cannot be
defined by the web interface, they have to be done by direct manipulation
of the database.  See the "README.extras" file in the vPostMaster
distribution for more information on creating extra attributes.

<a name='allowuserspamcontrol'><h2>Allow User Spam Control</h2>

<p />This setting controls whether users within this domain can make changes
to their anti-spam Settings or Rules.  If disabled, users do not see
the controls for changing their anti-spam settings and only the domain
administrator can make changes.

<a name='requireforwardwithindomain'><h2>Only Forward Within Domain</h2>

<p />This setting restricts users from adding forwards to email addresses
outside this domain.  If enabled, users will only able to setup forwarding
to valid email addresses within this domain, however, administrators
can still setup forwards to any domain.  This setting does not affect
existing forwards.

