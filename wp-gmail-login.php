<?php

/**
 * Plugin Name: Login With Gmail
 * Description: A Plugin For Login With Gmail
 * Plugin URI:  https://realwp.net
 * Version:     1.0.0
 * Author:      Mehrshad Darzi
 * Author URI:  https://realwp.net
 * License:     MIT
 * Text Domain: wp-gmail-login
 * Domain Path: /languages
 *
 * See: https://www.webslesson.info/2019/09/how-to-make-login-with-google-account-using-php.html?m=1
 * See: https://code.tutsplus.com/tutorials/create-a-google-login-page-in-php--cms-33214
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Gmail_Login
{
    public static $plugin_url;
    public static $plugin_path;

    public function __construct()
    {

        // Set Plugin Url and Version
        self::$plugin_url = plugins_url('', __FILE__);
        self::$plugin_path = plugin_dir_path(__FILE__);

        // Init Redirect Url
        add_action('wp_loaded', array($this, 'redirectGmailLogin'));
    }

    public static function getClientID()
    {
        if (defined('WP_GMAIL_CLIENT_ID')) {
            return WP_GMAIL_CLIENT_ID;
        }

        return apply_filters('wp_gmail_client_id', null);
    }

    public static function getClientSecret()
    {
        if (defined('WP_GMAIL_CLIENT_SECRET')) {
            return WP_GMAIL_CLIENT_SECRET;
        }

        return apply_filters('wp_gmail_client_secret', null);
    }

    public static function getRedirectParam()
    {
        if (defined('WP_GMAIL_REDIRECT_PARAM')) {
            return WP_GMAIL_REDIRECT_PARAM;
        }

        return apply_filters('wp_gmail_redirect_param', 'gmail_login_redirect');
    }

    public static function getGmailRedirectUrl()
    {
        return add_query_arg(
            array(
                self::getRedirectParam() => 'yes'
            ),
            trailingslashit(home_url())
        );
    }

    public static function prepareClientGmail()
    {
        // Check Before Exist
        if (isset($GLOBALS['wp_google_client'])) {
            return $GLOBALS['wp_google_client'];
        }

        // include Vendor
        require_once 'vendor/autoload.php';

        // init configuration
        $clientID = self::getClientID();
        $clientSecret = self::getClientSecret();
        $redirectUri = self::getGmailRedirectUrl();

        // Create Client Request to access Google API
        $GLOBALS['wp_google_client'] = new Google_Client();
        $GLOBALS['wp_google_client']->setClientId($clientID);
        $GLOBALS['wp_google_client']->setClientSecret($clientSecret);
        $GLOBALS['wp_google_client']->setRedirectUri($redirectUri);
        $GLOBALS['wp_google_client']->addScope("email");
        $GLOBALS['wp_google_client']->addScope("profile");

        // Return Client
        return $GLOBALS['wp_google_client'];
    }

    public static function getAuthUrl()
    {
        $client = self::prepareClientGmail();
        return $client->createAuthUrl();
    }

    public static function revokeToken($token = '')
    {
        $client = self::prepareClientGmail();
        $client->revokeToken($token);
    }

    public static function redirectGmailLogin()
    {
        // Disable in Admin Area
        if (is_admin()) {
            return;
        }

        // Disable if User Logged in
        if (is_user_logged_in()) {
            return;
        }

        // Check Isset Global Param
        if (isset($_GET[self::getRedirectParam()]) and isset($_GET['code']) and !empty($_GET['code'])) {

            // Prepare Client
            $client = self::prepareClientGmail();
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            $client->setAccessToken($token['access_token']);

            // get profile info
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            $accessToken = $token['access_token'];
            $email = $google_account_info->email;
            $name = $google_account_info->name;
            $familyName = $google_account_info->familyName;
            $gender = $google_account_info->gender;
            $locale = $google_account_info->locale;
            $avatar = $google_account_info->picture;

            // Do Action WordPress (Register or SignIn)
            do_action('wp_gmail_login_success', $accessToken, $email, $name, $familyName, $gender, $locale, $avatar, $google_account_info);
        }
    }

}

new WP_Gmail_Login();
