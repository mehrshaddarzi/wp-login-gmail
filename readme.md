## WordPress Login With Gmail

#### How To install Plugin?

1. run `composer update` in dir
2. install plugin in wordpress.
3. Create Google Client ID and Client Secret by this article:
````
 https://code.tutsplus.com/tutorials/create-a-google-login-page-in-php--cms-33214
````

4.Set `Authorised redirect URIs` in Google Developer:
````
https://site.com/?gmail_login_redirect=yes
````

5. add define in your wp-config.php
````php
define('WP_GMAIL_CLIENT_ID', 'xxx');
define('WP_GMAIL_CLIENT_SECRET', 'xxx');
define('WP_GMAIL_REDIRECT_PARAM', 'gmail_login_redirect');
````

6. use `wp_gmail_login_success` WordPress action for handle user register or login
````php
do_action('wp_gmail_login_success', $accessToken, $email, $name, $familyName, $gender, $locale, $avatar, $google_account_info);
````


#### Example

1.Create Button for Login Url
````php
<a href="<?php echo WP_Gmail_Login::getAuthUrl(); ?>">Login With Gmail</a>
````

2. Handle Return User Success Login With Gmail
````php
add_action('wp_gmail_login_success', 'login_with_gmail', 10, 4);
function login_with_gmail($accessToken, $email, $name, $familyName)
{
    $user_id = email_exists($email);
    if ($user_id === false) {

        $data = array(
            'user_email' => $email,
            'first_name' => $name,
            'show_admin_bar_front' => false
        );
        $user_id = wp_insert_user( $data );
    }

    // Automatic SignIn
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    // Redirect To Home Page Again
    wp_redirect(home_url());
    exit;
}
````
