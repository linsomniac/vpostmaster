%define name    vpostmaster
%define version 1.00
%define release 1
%define prefix  %{_prefix}

%define needsdovecotpgsql %(if grep -q -e 'Fedora Core release 2' /etc/redhat-release; then echo 1; else echo 0; fi)

Summary:       A full-featured, easy to install and manage, virtual mail server system.
Name:          %{name}
Version:       %{version}
Release:       %{release}
License:       Commercial, Free for non-commercial and 30-day trial use.
Group:         System Environment/E-mail
Source:        %{name}-%{version}.tar.gz
Packager:      Sean Reifschneider <jafo-rpms@tummy.com>
BuildRoot:     %{_tmppath}/%{name}-%{version}-%{release}-root
BuildArch:     noarch
Requires:      postfix+pgsql
%if %{needsdovecotpgsql}
Requires:      dovecot+pgsql
%else
Requires:      dovecot
%endif
Requires:      php-pgsql
Requires:      python
Requires:      pydns
Requires:      pyspf
Requires:      python-psycopg
Requires:      squirrelmail
Requires:      postgresql
Requires:      sudo
Requires:      spamassassin
Requires:      clamav

%description
vPostMaster is a full-featured, easy to install and maintain system for
handling virtual mail domains with Postfix.  It provides an extremely
comprehensive set of features including Webmail, POP, IMAP, SMTP, SMTP
AUTH, SSL versions of the above, virtual e-mail domains, spam and virus
filtering, user-controlled mail rules, SPF, Greylisting, SpamAssassin,
ClamAV, White/Black lists, and more.

Users are able to control their own spam, filter, and mailbox settings,
on an individual basis.  In other words, you don't have to disable
greylisting for your entire domain just because a single user wants it
disabled.

Domains and users are set up in such a way that system user accounts are
not required for mail access, preventing mail users from gaining access to
the system.

A web-based control panel is installed which allows superusers to create
new domains and users, domain admins to manage users, and users to manage
their own spam and mail system settings.  In this way, a vPostMaster
administrator may delegate management of users and domains to third
parties.

More information can be found at:
   http://www.tummy.com/Products/vpostmaster/

%prep
%setup
%build

%install
[ -n "$RPM_BUILD_ROOT" -a "$RPM_BUILD_ROOT" != / ] && rm -rf "$RPM_BUILD_ROOT"
mkdir -p "$RPM_BUILD_ROOT"/etc/postfix
mkdir -p "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/bin
mkdir -p "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/lib
mkdir -p "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/etc/wwwhelper.d/useradd
mkdir -p "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/etc/wwwhelper.d/userdel
mkdir -p "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/postfix
mkdir -p "$RPM_BUILD_ROOT"/var/spool/vpostmaster/domains
mkdir -p "$RPM_BUILD_ROOT"/var/www/html/vpostmaster/
mkdir -p "$RPM_BUILD_ROOT"%{_mandir}/man1/

install -m 755 vpmsupp.py "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/lib/
install -m 755 vpm-pfpolicy vpm-pftransport \
      "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/postfix/
install -m 755 vpm-wwwhelper scripts/setup-fc3 vpmuser vpm-pgmaintain \
      vpm-dbupgrade vpm-backup scripts/vpm-cpvpopmail \
      scripts/vpm-restartsasl scripts/setup-mailman \
      "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/bin/
install -m 644 vpostmaster-www/*.php "$RPM_BUILD_ROOT"/var/www/html/vpostmaster/
install -m 644 vpostmaster-www/*.png "$RPM_BUILD_ROOT"/var/www/html/vpostmaster/
install -m 644 vpostmaster-www/*.ico "$RPM_BUILD_ROOT"/var/www/html/vpostmaster/
install -m 640 wwwdb.conf-dist "$RPM_BUILD_ROOT"/usr/lib/vpostmaster/etc/wwwdb.conf
cp vpmuser.man "$RPM_BUILD_ROOT"%{_mandir}/man1/vpmuser.1
mkdir -p "$RPM_BUILD_ROOT"/etc/cron.d
echo '0 * * * * postgres /usr/lib/vpostmaster/bin/vpm-pgmaintain' \
      >"$RPM_BUILD_ROOT"/etc/cron.d/vpostmaster
echo '10 * * * * root /usr/lib/vpostmaster/bin/vpm-backup' \
		>"$RPM_BUILD_ROOT"/etc/cron.d/vpostmaster-backup
echo '* * * * * root /usr/lib/vpostmaster/bin/vpm-restartsasl' \
		>"$RPM_BUILD_ROOT"/etc/cron.d/vpostmaster-restartsasl

%clean
[ -n "$RPM_BUILD_ROOT" -a "$RPM_BUILD_ROOT" != / ] && rm -rf "$RPM_BUILD_ROOT"

%pre
grep -q '^vpostmaster:' /etc/passwd || /usr/sbin/useradd \
      -G mailman -m -r vpostmaster
true

%post
[ "`postconf -h mail_name`" != vPostMaster ] && postconf -e mail_name=vPostMaster
if [ "$1" -eq 1 ]
then
   #  initial install
   grep -q 'imap_server_type.*fcourier' /etc/squirrelmail/config_local.php
   if [ "$?" -ne 0 ]
   then
      echo '$imap_server_type = "courier";' >>/etc/squirrelmail/config_local.php
   fi
   grep -q 'optional_delimiter.*\.' /etc/squirrelmail/config_local.php
   if [ "$?" -ne 0 ]
   then
      echo '$optional_delimiter = ".";' >>/etc/squirrelmail/config_local.php
   fi
   grep -q 'default_folder_prefix.*""' /etc/squirrelmail/config_local.php
   if [ "$?" -ne 0 ]
   then
      echo '$default_folder_prefix = "";' >>/etc/squirrelmail/config_local.php
   fi
else
   #  upgrade done after initial install
   /sbin/service postfix stop
   su postgres -c "/usr/lib/vpostmaster/bin/vpm-dbupgrade --force"
   /sbin/service postfix start

   #  add new sudoers lines
   egrep -q 'root.*vpm-wwwhelper' /etc/sudoers || \
      echo "apache   ALL=(root) NOPASSWD:" \
         "/usr/lib/vpostmaster/bin/vpm-wwwhelper" >>/etc/sudoers
   egrep -q '^vpostmaster.*mailman.*mailman$' /etc/sudoers || \
      echo "vpostmaster  ALL=(mailman) NOPASSWD:" \
         "/usr/lib/mailman/mail/mailman" >>/etc/sudoers

   #  add to mailman group
   if [ ! -z "`grep '^mailman:' /etc/group`" \
         -a -z "`grep '^mailman:' /etc/group | grep vpostmaster`" ]
   then
      usermod -G mailman vpostmaster
   fi
fi
true

%preun
if [ "$1" -eq 0 ]
then
   #  final removal, not removal as part of an upgrade
   [ "`postconf -h mail_name`" = vPostMaster ] && postconf -e mail_name=Postfix
fi
true

%files
%defattr(-, root, root, 0755)
%doc WHATSNEW INSTALL schema.sql License-Community.html
%doc README README.procmail README.vpopmail
/var/www/html/vpostmaster
/usr/lib/vpostmaster/bin
/usr/lib/vpostmaster/lib
/usr/lib/vpostmaster/postfix
%config /etc/cron.d/vpostmaster*
%dir /usr/lib/vpostmaster/etc
%attr(0700, vpostmaster, root) %dir /var/spool/vpostmaster
%attr(0700, vpostmaster, root) %dir /var/spool/vpostmaster/domains
%attr(0640, vpostmaster, apache) %config(noreplace) /usr/lib/vpostmaster/etc/wwwdb.conf
%doc wwwdb.conf-dist
%{_mandir}/man1/*
