<?php
      /*
      Copyright (c) 2005-2008 tummy.com, ltd.
      vPostMaster php script
      */

   require_once("code.php");
   vpm_start();
   $loginSuccessful = 0;

   #  load the form values
   $username = "";
   if (array_key_exists("username", $_GET) && $_GET["username"]) {
      $username = $_GET["username"];
   }
   if (array_key_exists("username", $_POST) && $_POST["username"]) {
      $username = $_POST["username"];
   }
   $password = "";
   if (array_key_exists("password", $_GET) && $_GET["password"]) {
      $password = $_GET["password"];
   }
   if (array_key_exists("password", $_POST) && $_POST["password"]) {
      $password = $_POST["password"];
   }
   $loginPresent = 0;
   if (array_key_exists("login", $_GET) && $_GET["login"]) {
      $loginPresent = 1;
   }
   if (array_key_exists("login", $_POST) && $_POST["login"]) {
      $loginPresent = 1;
   }

   #  login form submitted
   $loginErrors = array();
   if ($loginPresent) {
      #  process login
      $GLOBALS["vpm_redirect_destination"] = "index.php";
      $loginErrors = vpm_process_login($username, $password);

      #  redirect back to another page
      if (count($loginErrors) == 0) {
         header("Location: ${GLOBALS["vpm_redirect_destination"]}");
         header("HTTP/1.0 301 Moved Permanently");
         die;
      }
   }

   vpm_header("vPostMaster Login page");

   #  show login errors
   if (count($loginErrors) > 0) {
      echo "<h2><font color=\"#ff0000\">Login errors:</font></h2>";
      echo "<ul>";
      foreach ($loginErrors as $error) {
         echo "<li />" . $error;
      }
      echo "</ul>";
   }
   else {
      $loginSuccessful = 1;
      }
?>

<form method="POST"><table>
   <tr><td><label for="login_username">User name:</label></td>
         <td><input id="login_username" type="text" name="username"
         <?php echo "value=\"$username\""; ?> <?php echo vpm_tabindex(); ?> /></td></tr>
   <tr><td><label for="login_password">Password:</label></td>
         <td><input id="login_password" type="password" name="password"
         <?php echo "value=\"$password\""; ?> <?php echo vpm_tabindex(); ?> /></td></tr>
   <tr><td colspan="2"><input type="submit" name="login" value="Login" <?php echo vpm_tabindex(); ?> /></td></tr>
</table></form>
