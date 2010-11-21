#!/bin/sh
#
# (c) 2005, 2006 tummy.com, ltd.
# vPostMaster database script


DBNAME=vpostmaster
[ "$#" -gt 0 ] && DBNAME="$1"

#  drop old database
dropdb "$DBNAME"

#  create the new database
createdb "$DBNAME"
echo 'Loading schema...'

TMPFILE=`mktemp -tp /tmp schema.XXXXXXXXXX`
if [ -z "$TMPFILE" ]
then
	echo "*****************************************************************"
	echo "Could not create tmpfile $TMPFILE"
	echo "*****************************************************************"
	exit 1
fi

psql -d "$DBNAME" <<@EOF >"$TMPFILE" 2>&1
-- #########################################################################

-- meta information
CREATE TABLE meta (
   id INTEGER UNIQUE NOT NULL
      CHECK (id = 1),

   -- version of the database schema currently running
   databaseversion INTEGER NOT NULL,

   -- base directory path to vpostmaster directory
   basedir TEXT NOT NULL,

   -- if not 0, domain directories exist under a directory named after the
   -- first this many characters of the domain name
   domaindirsplit INTEGER NOT NULL DEFAULT 2,

   -- if not 0, user directories exist under a directory named after the
   -- first this many characters of the user name
   userdirsplit INTEGER NOT NULL DEFAULT 2,

   -- The command which runs the Mailman mail program for reading messages
   -- sent via e-mail.  For example: /usr/lib/mailman/mail/mailman
   mailmanmailcmd TEXT DEFAULT NULL,

   -- The mailman "var" directory, which has the "lists" directory under it.
   mailmanvardir TEXT DEFAULT NULL,

   -- The directory where mailman commands such as "newlist" and "rmlist" are
   mailmanbindir TEXT DEFAULT NULL,

   -- The name of the mailman user on this system
   mailmanusername TEXT NOT NULL DEFAULT 'mailman',

   -- The clamav command to run
   clamscancommand TEXT DEFAULT 'clamdscan --stdout -'
   );

INSERT INTO meta ( id, databaseversion, basedir, domaindirsplit, userdirsplit )
      VALUES ( 1, 19, '/var/spool/vpostmaster', 2, 2 );


-- domain information
CREATE TABLE domains (
   id SERIAL UNIQUE,

   -- domain name
   name TEXT UNIQUE NOT NULL PRIMARY KEY,

   -- address extension character
   extensionchar CHAR(1) DEFAULT '-'
      CONSTRAINT extensionchar_constraint
      CHECK (extensionchar = '-' OR extensionchar = '+'),

   -- if this domain is an alias, this is the real domain name
   aliasedto TEXT DEFAULT NULL
      REFERENCES domains(name)
      ON DELETE CASCADE ON UPDATE CASCADE,

   -- if not null, e-mail to unknown accounts gets forwarded here
   catchalladdress TEXT DEFAULT NULL,

   -- maximum number of users
   maxusers INTEGER DEFAULT NULL,

   -- domain base directory name
   domaindir TEXT DEFAULT NULL,

   -- is the domain active
   active BOOLEAN DEFAULT 't',

   -- can this domain use extra attributes
   allowextraattributes BOOLEAN DEFAULT 't' NOT NULL,

   -- can users of this domain view/modify their own anti-spam settings?
   allowuserspamcontrol BOOLEAN DEFAULT 't' NOT NULL,

   -- maximum per-user quota
   maxperuserquota INTEGER DEFAULT NULL,

   -- only allow forwarding within this domain?
   requireforwardwithindomain BOOLEAN DEFAULT 'f' NOT NULL
   );


-- settings defaults for the domains
CREATE TABLE domaindefaults (
   id SERIAL UNIQUE,

   domainsid INTEGER
      REFERENCES domains(id)
      ON DELETE CASCADE ON UPDATE CASCADE,

   -- if true, this value overrides user settings
   force BOOLEAN DEFAULT 'f' NOT NULL,

   -- key/value pairs
   key TEXT NOT NULL,
   value TEXT,

   UNIQUE(domainsid, key)
   );

-- default values for the defaults
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'addspamheaders', 'enabled');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamsubjectprefix', '');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'clamavaction', 'disabled');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spfaction', 'reject');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'greylistaction', 'enabled');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'greylisttimeoutminutes', '45');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamassassin1action', 'quarantine');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamassassin1threshold', '5');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamassassin2action', 'drop');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamassassin2threshold', '15');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamassassin3action', 'disabled');
INSERT INTO domaindefaults ( domainsid, key, value )
      VALUES ( NULL, 'spamassassin3threshold', '');


-- admin user table
CREATE TABLE adminusers (
   id SERIAL UNIQUE,

   -- admin user name
   name TEXT NOT NULL UNIQUE PRIMARY KEY,

   -- are they a super-user
   issuperuser BOOLEAN NOT NULL DEFAULT 'n',

   -- encrypted password
   cryptedpasswd TEXT NOT NULL
   );


-- admin user privileges
CREATE TABLE adminprivs (
   id SERIAL UNIQUE,

   -- link to the admin users name
   adminusersname TEXT NOT NULL
      REFERENCES adminusers(name)
      ON DELETE CASCADE ON UPDATE CASCADE,

   -- link to domains
   domainsname TEXT NOT NULL
      REFERENCES domains(name)
      ON DELETE CASCADE ON UPDATE CASCADE,

   UNIQUE(adminusersname, domainsname)
   );


-- user information
CREATE TABLE users (
   id SERIAL UNIQUE,

   -- user base account name
   name TEXT NOT NULL,

   -- encrypted password
   cryptedpasswd TEXT NOT NULL,

   -- plain-text password
   plaintextpasswd TEXT NOT NULL DEFAULT '',

   -- link to domains
   domainsname TEXT NOT NULL
      REFERENCES domains(name)
      ON DELETE CASCADE ON UPDATE CASCADE,

   -- is the user active
   active BOOLEAN DEFAULT 't',

   -- the quota for this user in megabytes or NULL for no quota
   quotainmegabytes INTEGER DEFAULT NULL,

   -- user base directory name
   userdir TEXT NOT NULL,

   -- perform local delivery?
   localdeliveryenabled BOOLEAN DEFAULT 't',

   -- if not null, a white-space separatedlist of addresses to
   -- forward messages to
   forwardto TEXT DEFAULT NULL,

   -- users must be unique within a domain
   UNIQUE(name, domainsname)
   );


-- settings for each user
CREATE TABLE usersettings (
   id SERIAL UNIQUE,

   usersid INTEGER
      REFERENCES users(id)
      ON DELETE CASCADE ON UPDATE CASCADE,

   -- key/value pairs
   key TEXT NOT NULL,
   value TEXT,

   UNIQUE(usersid, key)
   );


-- auto responder information
CREATE TABLE autoresponders (
   id SERIAL UNIQUE,

   -- link to the users record
   usersid INTEGER
      REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,

   -- auto-responder information
   description TEXT,
   message TEXT NOT NULL
   );


-- Per-responder settings.
CREATE TABLE autorespondersettings (
   id SERIAL UNIQUE,

   -- link to the autoresponders record
   autorespondersid INTEGER
      REFERENCES autoresponders(id) ON DELETE CASCADE ON UPDATE CASCADE,

   -- key/value pairs
   key TEXT NOT NULL,
   value TEXT,

   UNIQUE(autorespondersid, key)
   );


-- response limits on replies
CREATE TABLE autorespondershistory (
   id SERIAL UNIQUE,

   -- link to the autoresponder record
   autorespondersid INTEGER
      REFERENCES autoresponders(id) ON DELETE CASCADE ON UPDATE CASCADE,

   -- when this record expires
   expires TIMESTAMP NOT NULL,

   -- sender address
   sender TEXT NOT NULL,

   UNIQUE(autorespondersid, sender)
   );


-- greylist database
CREATE TABLE greylist (
   id SERIAL UNIQUE,

   -- greylist information
   sender TEXT NOT NULL,
   recipient TEXT NOT NULL,
   remoteip TEXT NOT NULL,
   allowafter TIMESTAMP NOT NULL,
   expireafter TIMESTAMP NOT NULL,

   -- unique constraint
   UNIQUE(sender, recipient, remoteip)
   );
CREATE INDEX greylist_expireafter ON greylist (expireafter);


-- envelope rules
CREATE TABLE enveloperules (
   id SERIAL UNIQUE,

   -- link to the users record
   usersid INTEGER
      REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,

   -- action if there's a match
   action TEXT NOT NULL
      CONSTRAINT action_constraint
      CHECK (action = 'continue' OR action = 'quarantine'
            OR action = 'reject' OR action = 'accept' OR action = 'folder'),

   precedence INTEGER NOT NULL,
   heloname TEXT DEFAULT NULL,
   remoteip TEXT DEFAULT NULL,
   remotename TEXT DEFAULT NULL,
   sender TEXT DEFAULT NULL,
   recipient TEXT DEFAULT NULL,
   folder TEXT DEFAULT NULL
      CONSTRAINT folder_constraint
      CHECK ((action = 'folder' AND folder IS NOT NULL) OR action != 'folder'),

   UNIQUE(usersid, remoteip, remotename, sender, recipient, heloname)
   );


-- extra attributes that can be set on users
CREATE TABLE extraattributes (
   id SERIAL UNIQUE,

   name TEXT UNIQUE NOT NULL,
   class TEXT NOT NULL,
   label TEXT UNIQUE NOT NULL,
   description TEXT
   );


-- extra attributes for users
CREATE TABLE extrasettings (
   id SERIAL UNIQUE,

   usersid INTEGER
      REFERENCES users(id)
      ON DELETE CASCADE ON UPDATE CASCADE,

   attributesid INTEGER
      REFERENCES extraattributes(id)
      ON DELETE CASCADE ON UPDATE CASCADE,

   value_text TEXT,

   UNIQUE(usersid, attributesid)
   );


GRANT SELECT ON enveloperules, users, domains, meta, usersettings
      TO vpostmaster;
GRANT INSERT, DELETE, UPDATE ON users TO vpostmaster;
GRANT UPDATE ON users_id_seq TO vpostmaster;
GRANT SELECT, INSERT, DELETE, UPDATE ON greylist, autorespondershistory
      TO vpostmaster;
GRANT UPDATE ON greylist_id_seq, autorespondershistory_id_seq TO vpostmaster;
GRANT SELECT ON autorespondersettings, autoresponders TO vpostmaster;
GRANT SELECT ON domains TO postfix;
GRANT SELECT ON users, domains TO imapserver;
GRANT ALL ON enveloperules, adminusers, adminprivs, users, domains, meta,
      domaindefaults, usersettings, autorespondersettings, autoresponders
      TO vpostmasterwww;
GRANT UPDATE ON enveloperules_id_seq, adminusers_id_seq, users_id_seq,
      domains_id_seq, adminprivs_id_seq, usersettings_id_seq,
      domaindefaults_id_seq, autorespondersettings_id_seq,
      autoresponders_id_seq TO vpostmasterwww;
GRANT SELECT ON extraattributes, extrasettings TO vpostmasterwww;
GRANT INSERT, UPDATE, DELETE ON extrasettings TO vpostmasterwww;
GRANT UPDATE ON extrasettings_id_seq TO vpostmasterwww;
GRANT SELECT ON extraattributes, extrasettings TO vpostmaster;
GRANT INSERT, UPDATE, DELETE ON extrasettings TO vpostmaster;
GRANT UPDATE ON extrasettings_id_seq TO vpostmaster;
GRANT SELECT ON domaindefaults TO vpostmaster;
GRANT INSERT ON usersettings TO vpostmaster;
GRANT UPDATE ON usersettings_id_seq TO vpostmaster;


-- ALTER TABLE greylist OWNER TO vpostmaster;
-- ALTER TABLE enveloperules OWNER TO vpostmaster;
-- ALTER TABLE usersettings OWNER TO vpostmaster;
-- ALTER TABLE usersettings OWNER TO vpostmaster;
-- ALTER TABLE users OWNER TO vpostmaster;
-- ALTER TABLE adminprivs OWNER TO vpostmaster;
-- ALTER TABLE adminusers OWNER TO vpostmaster;
-- ALTER TABLE domaindefaults OWNER TO vpostmaster;
-- ALTER TABLE domains OWNER TO vpostmaster;
-- ALTER TABLE meta OWNER TO vpostmaster;

-- #########################################################################
@EOF

cat "$TMPFILE"
if grep -q ERROR: "$TMPFILE"
then
   echo
   echo "*****************************************************************"
   echo "Errors found in schema creation.  See above for more information."
   echo "*****************************************************************"
fi
rm -f "$TMPFILE"
