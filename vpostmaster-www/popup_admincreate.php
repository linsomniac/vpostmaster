<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

?>

<?php if (strstr($_SERVER["REQUEST_URI"], "create.php") == FALSE) { ?>
   <h1>vPostMaster Admin Edit/View Documentation</h1>
<?php } else { ?>
   <h1>vPostMaster Admin Create Documentation</h1>
<?php } ?>

<a name='name'><h2>Admin Login Name</h2>

<p />Login name of the admin user.  This may be a simple user name,
or may be a full e-mail address.  If a full e-mail address is used, it will
mask the ability of a user to log in as just that user account, but will
instead give them administrative privileges.

<p>Non-superuser admin users can change their admin user password,
and manage domains which are assigned to them. Domain management tasks
include adding, deleting and editing users. Domain admins can modify
the maximum mailbox size for users within their domains, up to the limit
set by the superuser for that domain, if any.

<p />If you administer only one domain, you may prefer to use your
email address (user@example.com).

<p />If you administer multiple domains, it will be simpler if you simply
use a login name (such as 'domainadmin').

<p />The email address does not have to be an address within this domain,
though that is recommended.

<a name='issuperuser'><h2>Enable Superuser Role</h2>

<p />There are two types of admin users: Superusers and Domain Admins.

<p />Superusers have the following extra abilities: they can create and
delete admin users and they can create and delete domains. Superusers
can also set limits on the number of users in a domain, and the maximum
mailbox quota for uses within a domain.

<p />All admin users have the ability to change their admin user password
and manage domains which are assigned to them. All admins can add, delete
and edit user accounts within domains that they manage.

<p />Domain admins cannot change the maximum number of users in a domain,
or set the per user quota larger than the domain maximum quota.

<a name='domains'><h2>List of Managed Domains (Optional)</h2>

<p />For Domain Admins, this is the list of domains, one name per line,
that they are authorized to administer.

<p />Leave this blank if you are creating a superuser account.  It has no
effect for superusers.
