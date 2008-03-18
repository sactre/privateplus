<?php
/* 
Plugin Name:  Private Plus
Version: .9
Plugin URI:  http://tech.brandonpetersen.com/privateplus/
Description:  Allow the administrator to choose which groups can see private posts
Author:  Brandon Petersen
Author URI:  http://www.brandonpetersen.com/

 License:
 ==============================================================================
 Copyright 2008 Brandon Petersen (email : brandon@gxconcepts.com )

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

// The filter will adjust the WHERE clause in obtaiing Posts
add_filter('posts_where', 'privatePlus_where');
add_filter('user_has_cap', 'privatePlus_add_read_ability', 10, 3);
add_action('admin_menu','privatePlus_menu');
register_activation_hook(__FILE__,"privatePlus_activate");
register_deactivation_hook(__FILE__,"privatePlus_deactivate");

/**
 * privatePlus_activate() - Actions taken when activating the Plugin
 *
 */
function privatePlus_activate() 
{
	add_option("privacyPlus_user_level", "7", "", "yes");
}

/**
 * privatePlus_deactivate() - Actions taken when disabling the Plugin
 *
 */
function privatePlus_deactivate() 
{
	remove_filter('posts_where', 'privatePlus_where');
	delete_option("privacyPlus_user_level"); 
}

/**
 * privatePlus_add_read_ability() - Function that gives users the ability to read a private post
 * 
 * This function will give a user the ability to read a private post, if their user's level is high 
 * enough to view a private posts.
 * 
 * @param    array    $allcaps    Defines the capabilities of a user
 * @param    array    $caps       Defines the capabilities that is being test
 * @param    array    $args       Defines details about the current post 
 *                                With $args[0] = capability being requested
 *                                With $args[1] = user_ID of the current user
 *                                With $args[2] = post_ID of the post/page record that is being requested
 * @return   array                The adjusted $allcaps array that defines new capabilities of the user
 */
function privatePlus_add_read_ability($allcaps, $caps, $args)
{
	// The user_has_caps filter can be called multiple times.  We are only concerned
	// about the call that pertains to reading a private post.  All other calls are ignored. 
	if ( (in_array('read_private_posts', $caps) OR in_array('read_private_pages', $caps)) AND is_user_logged_in())
	{
		// Obtain the user_ID of the currently logged in user
		global $user_ID;

		// Obtain User Details
		$user = new WP_User($user_ID);

		// If the User's user level is higher than what is set in the privacyPlus_user_level, 
		// then we can show the private post.  This field is found inside the wp_options table
		if ($user->wp_user_level >= get_option("privacyPlus_user_level")) 
		{
			// Add the capabilities
			$allcaps['read_private_posts'] = $allcaps['read_private_pages'] = 1;
			return $allcaps;
		}
	}
	else 
	{
		// This user_has_caps call has nothing to do with reading a private post. 
		// Allow it to proceed as normal.
		return $allcaps;
	}
}

/**
 * privatePlus_where() - Function that does the work in adjusting the WHERE clause that obtains lists of posts
 * 
 * This function will determine if the user's level is high enough to see private posts in a list of posts.
 * If this is the cause, it then adjusts the WHERE clause to obtain both public and private posts.
 *
 * @param    string    $where    The WHERE clause used to obtain the posts that will be displaed
 * @return   string              The adjusted WHERE clause
 */
function privatePlus_where(&$where) 
{
	// We only show Private Posts to Authenticated Users.
	// Otherwise there is no purpose, it would basically be a Public Post
	if (is_user_logged_in()) 
	{ 
		// Obtain User Details
		global $user_ID;
		$user = new WP_User($user_ID);

		// If the User's user level is higher than what is set in the privacyPlus_user_level, 
		// then we can show the private post.  This field is found inside the wp_options table
		if ($user->wp_user_level >= get_option("privacyPlus_user_level")) 
		{
			/** @todo use regular expressions for this mess of IF Statements */

			// Replace the post_status clause and add PRIVATE posts into the query results
			if (strstr($where, 'post_status = "publish"')) 
			{
				$where = str_replace('post_status = "publish"', ' ( post_status = "private" OR post_status = "publish" ) ', $where);
			}
			elseif (strstr($where, "post_status = 'publish'")) 
			{
				$where = str_replace("post_status = 'publish'", ' ( post_status = "private" OR post_status = "publish" ) ', $where);
			}
			else 
			{
				// Nothing happens, we didn't find the right post_status clause
			}
		}
	}

	// Return the adjusted WHERE statement
	return $where;
}

/**
 * privatePlus_menu() - Function that creates the Administration Menu for privatePlus
 * 
 */
function privatePlus_menu() {
     add_options_page(
                      'privatePlus',		//Title
                      'privatePlus',		//Sub-menu title
                      'manage_options',		//Security
                      __FILE__,			//File to open
                      'privatePlus_options'	//Function to call
                     );  
}

/**
 * privatePlus_options() - 
 * 
 */
function privatePlus_options () {
     echo '<div class="wrap"><h2>privatePlus Options</h2>';
     if ($_REQUEST['submit']) {
	privatePlus_updateOptions();
     }
     privatePlus_form();
     echo '</div>';
}

/**
 * privatePlus_form() - 
 * 
 */
function privatePlus_form () {
	$userLevel = get_option('privacyPlus_user_level');
?>
<div style="width: 200px; float: right; border: 1px solid #14568A;">
  <div style="width: 195px; background: #0D324F; color: white; padding: 0 0 0 5px;">About this Plugin:</div>
  <div style="width: 180px; padding: 10px;">
    <a href="http://tech.brandonpetersen.com/privateplus/" target="_blank">Plugin Homepage</a><br>
    <a href="http://tech.brandonpetersen.com/redir/privatePlus.php" target="_blank">Donate with PayPal</a><br>
    <a href="http://www.amazon.com/gp/registry/wishlist/ref=pd_ys_qtk_wl_more?pf_rd_p=186413001&pf_rd_s=center-1&pf_rd_t=1501&pf_rd_i=home&pf_rd_m=ATVPDKIKX0DER&pf_rd_r=0YDYCSNGWE35ERKM7RNE" target="_blank">Amazon Wishlist</a><br>
  </div>
</div>

<p>By default, WordPress only allows administrators and editors to view private posts. This WordPress plugin will allow you to determine which groups 
are able to see private posts by default.  Select the lowest user level that you want to see private posts, all user levels that are higher will also 
be able to see private posts.</p>

<?php
	echo ' <form method="post"> ';
	echo ' <label><b>Lowest User Level to View Private Posts:</b></label><br>';
	echo ' <select name="privacyPlus_user_level"> ';

	$selected = $userLevel == 10 ? "SELECTED" : "";
	echo ' <option value="10" '. $selected .' >Administrator</option> ';

	$selected = $userLevel == 7 ? "SELECTED" : "";
	echo ' <option value="7" '. $selected .' >Editor</option> ';

	$selected = $userLevel == 2 ? "SELECTED" : "";
	echo ' <option value="2" '. $selected .' >Author</option> ';

	$selected = $userLevel == 1 ? "SELECTED" : "";
	echo ' <option value="1" '. $selected .' >Contributor</option> ';

	$selected = $userLevel == 0 ? "SELECTED" : "";
	echo ' <option value="0" '. $selected .' >Subscriber</option> ';

	echo ' </select> ';
	echo ' <br><br> ';
	echo ' <input type="submit" name="submit" value="Submit" /> ';
	echo ' </form> ';
}

/**
 * privatePlus_updateOptions() - 
 * 
 */
function privatePlus_updateOptions() {
     $updated = false;
     if (isset($_REQUEST['privacyPlus_user_level'])) {
          update_option('privacyPlus_user_level', $_REQUEST['privacyPlus_user_level']);
          $updated = true;
     }
     if ($updated) {
           echo '<div id="message" class="updated fade">';
           echo '<p>Options Updated</p>';
           echo '</div>';
      } else {
           echo '<div id="message" class="error fade">';
           echo '<p>Unable to update options</p>';
           echo '</div>';
      }
 }


?>
