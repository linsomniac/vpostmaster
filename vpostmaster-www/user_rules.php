<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();

   #  setup
   $username = $_SESSION["vpmsession_selecteduser"];
   if (!$username) {
      vpm_header("vPostMaster User Rules");
      echo "<h1>User not selected:</h1>";
      echo "No user has been selected.  Please use the \"Lookup\" item of " .
            "the \"Mail Users\" menu to select a user.";
      die;
   }
   $domain = $_SESSION["vpmsession_selecteddomain"];
   $quotedName = "'" . pg_escape_string($username) . "'";
   $quotedDomain = "'" . pg_escape_string($domain) . "'";

   #  is the user permitted to change this record?
   $canEdit = 0;
   if ($GLOBALS["vpm_issuperuser"]) { $canEdit = 1; }
   if (vpm_isDomainAdmin($domain)) { $canEdit = 1; }
   if ($username == $_SESSION["vpmsession_localname"]
         && $domain == $_SESSION["vpmsession_domainname"]) {
      $canEdit = 1;
   }

   #  check privileges
   if (!$canEdit) {
      echo "<h1>Access error:</h1>";
      echo "You do not have privileges to access this page.";
      die;
      }

   vpm_header("vPostMaster User Rules");

   #  was this a form submission
   $ret = vpm_validateform("submit", "Submit button",
         "/(Add Rule|Delete Selected)/", 0, 20, 1);
   $info = array();
   $errors = array();
   $initialLoad = 0;
   $showCurrent = 1;
   if ($ret[0] == "successful") {
      $submitType = $ret[1];

      #  add rule
      if ($submitType == "Add Rule") {
         #  validate form

         $ret = vpm_validateform("precedence", "Precedence",
               "/^[0-9]+$/", 1, 3, 1);
         $info["precedence"] = $ret[1];
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         $ret = vpm_validateform("action", "Action",
               "/^(accept|reject|continue|quarantine)$/", 1, 20, 1);
         $info["action"] = $ret[1];
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         $ret = vpm_validateform("sender", "Sender Address",
               "", 1, 100, 0);
         $info["sender"] = $ret[1];
         if (substr($info["sender"], 0, 6) != 'regex:') {
            $info["sender"] = strtolower($info["sender"]);
         }
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         $ret = vpm_validateform("recipient", "Recipent Address",
               "", 1, 100, 0);
         $info["recipient"] = $ret[1];
         if (substr($info["recipient"], 0, 6) != 'regex:') {
            $info["recipient"] = strtolower($info["recipient"]);
         }
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         $ret = vpm_validateform("heloname", "Helo Name",
               "", 1, 100, 0);
         $info["heloname"] = $ret[1];
         if (substr($info["heloname"], 0, 6) != 'regex:') {
            $info["heloname"] = strtolower($info["heloname"]);
         }
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         $ret = vpm_validateform("remotename", "Remote Host Name",
               "", 1, 100, 0);
         $info["remotename"] = $ret[1];
         if (substr($info["remotename"], 0, 6) != 'regex:') {
            $info["remotename"] = strtolower($info["remotename"]);
         }
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         $ret = vpm_validateform("remoteip", "Remote IP Address",
               "/^[0-9.]+$/", 1, 15, 0);
         $info["remoteip"] = $ret[1];
         if ($ret[0] != "successful" ) { array_push($errors, $ret[2]); }

         #  add rule
         if (count($errors) < 1) {
            $quotedAction = "'" . pg_escape_string($info["action"]) . "'";
            $quotedPrecedence = pg_escape_string($info["precedence"]);
            $quotedSender = "'" . pg_escape_string($info["sender"]) .  "'";
            if (!$info["sender"]) { $quotedSender = "NULL"; }
            $quotedRecipient = "'" . pg_escape_string($info["recipient"]) .
                  "'";
            if (!$info["recipient"]) { $quotedRecipient = "NULL"; }
            $quotedHeloname = "'" . pg_escape_string($info["heloname"]) .  "'";
            if (!$info["heloname"]) { $quotedHeloname = "NULL"; }
            $quotedRemoteName = "'" . pg_escape_string($info["remotename"]) .
                  "'";
            if (!$info["remotename"]) { $quotedRemoteName = "NULL"; }
            $quotedRemoteIp = "'" . pg_escape_string($info["remoteip"]) .  "'";
            if (!$info["remoteip"]) { $quotedRemoteIp = "NULL"; }

            $result = pg_query("INSERT INTO enveloperules " .
                  "( usersid, action, precedence, heloname, remoteip, " .
                     "remotename, sender, recipient ) " .
                  "VALUES ( ".
                     "(SELECT id FROM users WHERE name = ${quotedName} " .
                        "AND domainsname = ${quotedDomain}), " .
                     "${quotedAction}, " .
                     "${quotedPrecedence}, " .
                     "${quotedHeloname}, " .
                     "${quotedRemoteIp}, " .
                     "${quotedRemoteName}, " .
                     "${quotedSender}, " .
                     "${quotedRecipient})");

            if (!$result) {
               array_push($errors, "SQL Error while adding rule: " .
                     pg_last_error());
            }
            else {
               pg_free_result($result);
               echo "<h2>Successfully Added New Rule</h2>";
               echo "<p />Your new rule has been successfully added.";
               $info = array();
            }
         }
         if (count($errors) > 0) {
            echo "<h2><font color=\"#ff0000\">Rule errors:</font></h2>";
            echo "<p />The following errors were found in your rules.  " .
                  "Please review your rules and try again.";
            echo "<ul>";
            foreach ($errors as $error) { echo "<li />${error}"; }
            echo "</ul>";
            $showCurrent = 0;
         }
      }

      #  delete
      if ($submitType == "Delete Selected") {
         $selectedids = $_POST["selectedids"];
         if ($selectedids) {
            foreach ($selectedids as $id) {
               $quotedId = "'" . pg_escape_string($id) . "'";
               pg_query("DELETE FROM enveloperules WHERE id = ${quotedId} " .
                     "AND usersid = (SELECT id FROM users " .
                        "WHERE name = ${quotedName} " .
                           "AND domainsname = ${quotedDomain})");
            }
         }
      }
   }

   #  load initial array
   if (count($info) < 1) {
      $info["precedence"] = "50";
      $info["sender"] = "";
      $info["recipient"] = "";
      $info["heloname"] = "";
      $info["remotename"] = "";
      $info["remoteip"] = "";
      $info["action"] = "reject";
   }
?>

<h1>User Rules</h1>

   <?php
   if ($showCurrent) {
      $result = pg_query("SELECT enveloperules.* FROM enveloperules, users " .
            "WHERE users.name = ${quotedName} " .
               "AND users.domainsname = ${quotedDomain} " .
               "AND enveloperules.usersid = users.id " .
            "ORDER BY enveloperules.precedence DESC ");
      if ($result) {
         $sentRow = 0;
         $count = 0;
         while (($row = pg_fetch_assoc($result))) {
            $count = $count + 1;
            $tridstr = 'id="odd"';
            if ($count % 2 == 0) { $tridstr = 'id="even"'; }

            if (!$sentRow) {
               echo "<h2>Current rules</h2><table border=\"1\">";
               echo "<form action=\"user_rules.php\" method=\"POST\">";
               echo "<tr><th></th><th><acronym title=\"Precedence\">P" .
                     "</acronym></th><th>Action</th><th>Sender</th>" .
                     "<th>Recipient</th><th>Helo Name</th>" .
                     "<th>Remote IP</th><th>Remote Name</th></tr>";
               $sentRow = 1;
            }
            echo "<tr ${tridstr}>";
            echo "<td><input type=\"checkbox\" name=\"selectedids[]\" " .
                  "title=\"Check to delete this rule\" " .
                  "value=\"${row['id']}\" " . vpm_tabindex() . "/></td>";
            echo "<td>${row['precedence']}</td>";
            echo "<td>${row['action']}</td>";
            echo "<td>${row['sender']}</td>";
            echo "<td>${row['recipient']}</td>";
            echo "<td>${row['heloname']}</td>";
            echo "<td>${row['remoteip']}</td>";
            echo "<td>${row['remotename']}</td>";
            echo "</tr>";
         }
         if ($sentRow) {
            echo "<tr><td colspan=\"8\"><input type=\"submit\" " .
                  "name=\"submit\" value=\"Delete Selected\" " . vpm_tabindex() . "/></td></tr>";
            echo "</form></table>";
            }
         pg_free_result($result);
      }
   }
   ?>

<h2>Add New Rule</h2>

<p />Enter new rule information below.  Any fields which are left blank
are ignored and not used to make a match determination.  Note that these
rules relate to the message envelope, and that sender and recipient
addresses (in particular) may be different than what is shown in the "From"
and "To" headers of the message.  See the "Received" and "Return-Path"
headers for the envelope information.

<p />
<form action="user_rules.php" method="POST"><table>
   <tr><td><label for="precedence">Precedence:</label></td><td><input
         type="text" name="precedence" id="precedence"
         value="<?php echo $info["precedence"]; ?>"
         size="3" maxlength="3" <?php echo vpm_tabindex(); ?> /></td><td class="formdesc">
      Rules are evaluated from highest priority to lowest.
            Multiple rules can exist at the same precedence.  The highest
            precedence rule determines the action taken.
      </td></tr>
      <tr><td><label for="action">Action:</label></td><td>
         <select id="action" name="action" <?php echo vpm_tabindex(); ?> >
         <option value="reject" <?php if ($info["action"] == "reject") {
            echo "selected"; } ?>>Reject</option>
         <option value="accept" <?php if ($info["action"] == "accept") {
            echo "selected"; } ?>>Accept</option>
         <option value="continue" <?php if ($info["action"] == "continue") {
            echo "selected"; } ?>>Continue</option>
         <option value="quarantine" <?php if ($info["action"] == "quarantine") {
            echo "selected"; } ?>>Quarantine</option>
         </select></td><td class="formdesc">
            What happens if this rule matches?<br />
            - Reject unconditionally refuses the message.<br />
            - Accept unconditionally accepts the message.  This bypasses all
                  account settings and spam/virus filtering.<br />
            - Continue aborts the rule lookups but will subject the message
                  to other tests based on your settings.<br />
            - Quarantine will unconditionally accept the message by deliver
                  it to your Quarantine folder.<br />
      </td></tr>
   <tr><td><label for="sender">Sender Address:</label></td>
      <td><input type="text" name="sender" id="sender"
         value="<?php echo $info["sender"]; ?>" <?php echo vpm_tabindex(); ?> /></td><td class="formdesc">
            The envelope sender address.  This must be a full e-mail address
            (user@example.com).  May also include the prefix "regex:" for a
            regular expression match.
      </td></tr>
   <tr><td><label for="recipient">Recipient Address:</label></td>
      <td><input type="text" name="recipient" id="recipient"
         value="<?php echo $info["recipient"]; ?>" <?php echo vpm_tabindex(); ?> /></td><td class="formdesc">
               The envelope recipient address.  This is your
               address, including any extension informaiton.  This must be
               a full e-mail address (user@example.com).  May also include
               the prefix "regex:" for a regular expression match.
      </td></tr>
   <tr><td><label for="heloname">Helo Name</label></td>
      <td><input type="text" name="heloname" id="heloname"
         value="<?php echo $info["heloname"]; ?>" <?php echo vpm_tabindex(); ?> /></td><td class="formdesc">
            The HELO name sent by the client.  May also include the prefix
            "regex:" for a regular expression match.
      </td></tr>
   <tr><td><label for="remotename">Remote Host Name:</label></td>
      <td><input type="text" name="remotename" id="remotename"
         value="<?php echo $info["remotename"]; ?>" <?php echo vpm_tabindex(); ?> /></td><td class="formdesc">
            The remote host name (if available).  May also include the
            prefix "regex:" for a regular expression match.
      </td></tr>
   <tr><td><label for="remoteip">Remote IP Address:</label></td>
      <td><input type="text" name="remoteip" id="remoteip"
         value="<?php echo $info["remoteip"]; ?>"
            size="15" maxlength="15" <?php echo vpm_tabindex(); ?> /></td><td class="formdesc">
            The remote host's IP address.
      </td></tr>
   <tr><td colspan="3"><input type="submit" name="submit" value="Add Rule"
         <?php echo vpm_tabindex(); ?>/></td></tr>
</table></form>
