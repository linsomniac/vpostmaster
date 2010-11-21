<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   vpm_header("vPostMaster Getting Started Guide ");
?>

<h1>vPostMaster Getting Started Guide</h1>

<a name="BasicConcepts"><h2>Basic Concepts</h2>

<p />The vPostMaster Web Control Panel usage varies depending on your level of
privileges.  E-mail user accounts only have access to functions relating to
their own account, while domain administrators have access to all users in
a given domain, and super-users have access to domain management
information.

<p />Mail users have fairly limited options in the menu-bar on the left.
This document is largely targeted administrators and super-users.

<a name="MenuBar"><h2>Menu Bar</h2>

<p />On the left of the browser page is a menu.  Available options will be
displayed in this bar.  Any additional information is displayed on the
right part of the page (where you are viewing this document).

<p />Note that the menu bar changes depending on the user role (super-user,
administrator, or user account), and also depending on whether a domain and
account are selected.

<a name="SelectionIndicator"><h2>Selection Indicator</h2>

<p />In the upper left corner of the page is the text "vPostMaster Control
Panel".  Below this, but above the menu bar, is text indicating the
selected target.  When a domain has been selected, this will indicate the
domain name.  In the event that you have also selected a user (through the
"Mail Users -&gt; Lookup" page), this will display
"&lt;username&gt;@&lt;domain&gt;".

<a name="DomainSelection"><h2>Domain Selection</h2>

<p />If this is your first use of a newly installed vPostMaster system, you
will first need to create a domain.  Simply go to the "Create" item under
"Domains", and fill in the requested information.

<p />Next you will need to select a domain to operate on.  This is the first
option under the "Domains" menu.  A list of available domains will be
shown.  Click on the domain name you wish to manage.  This will take you to
the domain detail page.  Changes to the domain can be made here.  At any
time, you can make changes to the domain by clicking on the "View/Edit"
item under "Domains" (which is available any time a domain is selected).

<p />Note that when you select a domain, text is displayed in the <a
href="#SelectionIndicator">Selection Indicator</a>, indicating which
domain is selected.

<a name="UserAccountCreation"><h2>User Account Creation</h2>

<p />Once a domain has been selected <a href="#DomainSelection">(see the
previous section)</a>, the "Mail Users" menu is available.  To create
a new account, click on the "Create" button.  Fill in the required
information and click the "Create" button to create the account.

<a name="UserAccountList"><h2>User Account List</h2>

<p />To locate a particular user account (in the selected domain), click on
the "Lookup" item under "Mail Users".  This will provide a search box and a
navigable list of user accounts.

<p />The search box may be used to search for particular user accounts.
You may use regular expressions to search for particular accounts.  For
example, to search for all accounts which start with the letter "a", use
"^a".  Accounts which end with the letter "b" can be found using "b$".  For
all accounts with "smith" in the name, simply use "smith".

<p />Note that the "." (dot) character matches any character unless it is
prefixed with the "\" (backslash) character.  For example, if you want to
search for exactly the string "first.last", you will need to use
"first\.last".

<p />Below the search box is a list of user accounts, and "Next" and
"Previous" buttons if more than 50 matching accounts are available.<!--  This
list of accounts has a check-box next to each account name, which can be
used for deleting/activating/deactivating multiple accounts.-->  To modify a
particular account, click on the account name.

<a name="UserAccountSelection"><h2>User Account Selection</h2>

<p />If a user has been selected, the "Mail Users" menu section has items
available for editing a users Black/White listing rules, and also for
changing the users settings.  The <a href="#SelectionIndicator">Selection
Indicator</a> will show a user name followed by "@" if a user is
currently selected.  To select a user, go to the "Lookup" item under
"Mail Users" and click on the link in the <a href="#UserAccountList">User
Account List</a>.
