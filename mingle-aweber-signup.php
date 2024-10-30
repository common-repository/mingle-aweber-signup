<?php
/*
Plugin Name: Mingle AWeber Signup
Plugin URI: http://blairwilliams.com/mingle
Description: Enables you to optionally push users to your <a href="http://blairwilliams.com/aweber">AWeber</a> (aff) email list when they signup through mingle...
Version: 0.0.01
Author: Blair Williams
Author URI: http://blairwilliams.com
Text Domain: mingle
Copyright: 2010, Blair Williams

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
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

if(!function_exists('is_plugin_active'))
  require_once(ABSPATH . '/wp-admin/includes/plugin.php');
  
if(is_plugin_active('mingle/mingle.php'))
{
  class MnglAweberSignup
  {
    function MnglAweberSignup()
    {
      add_action('mngl_custom_fields',    array( &$this, 'display_option_fields' ));
      //add_filter('mngl_validate_options', array( &$this, 'validate_option_fields'));
      //add_action('mngl_update_options',   array( &$this, 'update_option_fields'));
      add_action('mngl_store_options',    array( &$this, 'store_option_fields'));

      add_action('mngl-user-signup-fields', array( &$this, 'display_signup_field' ));
      //add_filter('mngl-validate-signup',    array( &$this, 'validate_signup_fields' ));
      add_action('mngl-process-signup',     array( &$this, 'process_signup' ));

      add_action('mngl_signup_thankyou_message', array( &$this, 'thank_you_message'));
    }

    function display_option_fields()
    {
      if(isset($_POST['mnglaweber_listname']) and !empty($_POST['mnglaweber_listname']))
        $aweber_listname = $_POST['mnglaweber_listname'];
      else
      {
        $aweber_listname = get_option('mnglaweber_listname');
      }
        
      if(isset($_POST['mnglaweber_text']) and !empty($_POST['mnglaweber_text']))
        $aweber_text = $_POST['mnglaweber_text'];
      else
      {
        $aweber_text = get_option('mnglaweber_text');
      }

      ?>
        <h4><?php _e('AWeber Signup Integration', 'mingle'); ?></h4>
        <div class="mngl-options-pane">
          <p>
            <label><?php _e('AWeber List Name', 'mingle'); ?>:&nbsp;
            <input type="text" name="mnglaweber_listname" id="mnglaweber_listname" value="<?php echo $aweber_listname; ?>" class="mngl-text-input form-field" size="20" tabindex="19" /></label><br/>
            <span class="description"><?php _e('Enter the AWeber mailing list name that you want users signed up for when they sign up for mingle.','mingle'); ?></span>
          </p>
          <p>
            <label><?php _e('Signup Checkbox Label', 'mingle'); ?>:&nbsp;
            <input type="text" name="mnglaweber_text" id="mnglaweber_text" value="<?php echo $aweber_text; ?>" class="form-field" size="75" tabindex="20" /></label><br/>
            <span class="description"><?php _e('This is the text that will display on the signup page next to your mailing list opt-out checkbox.','mingle'); ?></span>
          </p>
        </div>
      <?php
    }
    
    function validate_option_fields($errors)
    {
      // Nothing to validate yet -- if ever
    }
    
    function update_option_fields()
    {
      // Nothing to do yet -- if ever
    }
    
    function store_option_fields()
    {
      update_option('mnglaweber_listname', $_POST['mnglaweber_listname']);
      update_option('mnglaweber_text', stripslashes($_POST['mnglaweber_text']));
    }
    
    function display_signup_field()
    {
      global $mngl_user, $mngl_blogname;
      
      if(isset($_POST['mnglaweber_opt_in_set']))
        $checked = isset($_POST['mnglaweber_opt_in'])?' checked="checked"':'';
      else
        $checked = ' checked="checked"';

      $message = get_option('mnglaweber_text');
      
      if(!$message or empty($message))
        $message = sprintf(__('Sign Up for the %s Newsletter'), $mngl_blogname);

      ?>
      <tr>
        <td valign="top" colspan="2">
          <input type="hidden" name="mnglaweber_opt_in_set" value="Y" />
          <input type="checkbox" name="mnglaweber_opt_in" tabindex="2000" style="width: 25px;" id="mnglaweber_opt_in"<?php echo $checked; ?>/><?php echo $message; ?><br/><small><a href="http://www.aweber.com/permission.htm" target="_blank"><?php _e('We Respect Your Privacy','mingle'); ?></a></small><br/>
        </td>
      </tr>
      <?php
    }
    
    function validate_signup_field($errors)
    {
      // Nothing to validate -- if ever
    }
    
    function process_signup($user_id)
    {
      if(isset($_POST['mnglaweber_opt_in']))
      {
        // TODO: Send Post to AWeber
        $aweber_listname = get_option('mnglaweber_listname');
        $aweber_url      = "http://www.aweber.com/scripts/addlead.pl";
        $user            =& MnglUser::get_stored_profile($user_id);
        
        if( !class_exists( 'WP_Http' ) )
          include_once( ABSPATH . WPINC. '/class-http.php' );
        
        $aweber_body = array(
          'listname' => $aweber_listname,
          'redirect' => 'http://www.aweber.com/thankyou-coi.htm?m=text',
          'meta_adtracking' => 'mingle',
          'meta_message' => '1',
          'meta_forward_vars' => '1',
          'name'  => $user->full_name,
          'email' => $user->email
        );
        
        $request = new WP_Http();
        $result = $request->request( $aweber_url, array( 'method' => 'POST', 'body' => $aweber_body) );
      }
      
      // $result['response'] -- nothing really to do with this either -- right?
    }
  
    function thank_you_message()
    {
      if(isset($_POST['mnglaweber_opt_in']))
      {
      ?>
        <h3><?php _e("You're Almost Done - Activate Your Newsletter Subscription!", 'mingle'); ?></h3>
        <p><?php _e("You've just been sent an email that contains a <strong>confirm link</strong>.", 'mingle'); ?></p>
        <p><?php _e("In order to activate your subscription, check your email and click on the link in that email.
           You will not receive your subscription until you <strong>click that link to activate it</strong>.", 'mingle'); ?></p>
        <p><?php _e("If you don't see that email in your inbox shortly, fill out the form again to have another copy of it sent to you.", 'mingle'); ?></p>
      <?php
      }
    }
  }

  new MnglAweberSignup();
}
?>
