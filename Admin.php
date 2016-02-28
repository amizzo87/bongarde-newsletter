<?php

class OHSNewsletterAdmin
{
    protected $file, $path;

    private static $tableName = "ohs_newsletter";

    function __construct($file)
    {
        $this->file = $file;
        $this->path = dirname(__FILE__);
        register_activation_hook($this->file, array(&$this, 'install'));
        add_action('admin_menu', array($this, 'addPages'));
        if (!is_admin())
            add_action('wp_enqueue_scripts', array($this, 'loadStyles'));


        add_action('rest_api_init', function () {
            register_rest_route('ohsnewsletter/v1', '/subscribe/', array(
                'methods' => 'POST',
                'callback' => array($this, 'postSubscribe'),
            ));

            register_rest_route('ohsnewsletter/v1', '/validate/(?P<code>.+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'getValidate'),
            ));
        });
    }

    /**
     * triggered upon activation, creates tables and default data
     */
    public function install()
    {
        if (!get_option('ohs_newsletter_installed')) {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $fullTableName = $wpdb->prefix . self::$tableName;

            $sql = "CREATE TABLE $fullTableName (
              id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
              first_name varchar(100) NOT NULL,
              last_name varchar(100) NOT NULL,
              email varchar(100) NOT NULL,
              validation_code varchar(200) NOT NULL,
              PRIMARY KEY id (id)
            ) $charset_collate;";

            dbDelta($sql);
            add_option('ohs_newsletter_installed', 1);

            add_option('ohs_newsletter_sendgrid_api', "");
            add_option('ohs_newsletter_sendgrid_list', "");
            add_option('ohs_newsletter_redirect', "");
        }
    }

    /**
     * load styles
     */
    public function loadStyles()
    {
        wp_enqueue_style('ohsnewsletter', plugins_url('/ohsnewsletter/css/ohsnewsletter.css'));
    }

    /**
     * register admin menu pages
     */
    public function addPages()
    {
        $settings = add_options_page(
            'OHS Newsletter',
            'OHS Newsletter',
            'manage_options',
            'ohs-newsletter',
            array($this, 'optionsPage')
        );
    }

    /**
     * renders the options page
     */
    public function optionsPage()
    {
        $this->updateOptions();
        include_once($this->path . '/views/options.php');
    }

    /**
     * stores the options in DB on update
     */
    public function updateOptions()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return;

        update_option('ohs_newsletter_sendgrid_api', $_POST['ohs_newsletter_sendgrid_api']);
        update_option('ohs_newsletter_sendgrid_list', $_POST['ohs_newsletter_sendgrid_list']);
        update_option('ohs_newsletter_redirect', $_POST['ohs_newsletter_redirect']);
    }

    /**
     * API route for subscribing
     */
    public function postSubscribe(WP_REST_Request $request)
    {
        global $wpdb;
        $first_name = $request['first_name'];
        $last_name = $request['last_name'];
        $email = $request['email'];
        $errors = [];

        if (!$first_name || strlen($first_name) > 100)
            array_push($errors, "First name is required");

        if (!$last_name || strlen($last_name) > 100)
            array_push($errors, "Last name is required");

        if (!is_email($email) || strlen($email) > 100)
            array_push($errors, "Please enter a valid email");

        if (count($errors) > 0)
            return new WP_REST_Response($errors, 400);

        $validation_code = md5($email);
        $wpdb->insert(
            $wpdb->prefix . self::$tableName,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'validation_code' => $validation_code
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s'
            )
        );

        $link = "<a href='" . get_home_url() . "/wp-json/ohsnewsletter/v1/validate/" . $validation_code . "'>" . get_home_url() . "/wp-json/ohsnewsletter/v1/validate/" . $validation_code . "</a>";
        $message = "Thank you for your interest in OHS Insider newsletter. Please confirm your email address by clicking the following link: " . $link;

        add_filter( 'wp_mail_content_type', array($this, 'wpse27856_set_content_type') );
        wp_mail($email, "Please confirm your OHS Insider newsletter subscription", $message);
        remove_filter( 'wp_mail_content_type', array($this, 'wpse27856_set_content_type') );

        return new WP_REST_Response("We have sent you a confirmation email. Please check your email and verify it to complete your subscription. Thank you", 200);
    }

    /**
     * API route for email validation
     */
    public function getValidate(WP_REST_Request $request)
    {
        global $wpdb;
        $code = $request['code'];
        $redirectURI = get_option('ohs_newsletter_redirect');

        $sendgridHeader = "Bearer  " . get_option('ohs_newsletter_sendgrid_api');
        $sendgridRecepientAPI = "https://api.sendgrid.com/v3/contactdb/recipients";
        $sendgridListAPI = "https://api.sendgrid.com/v3/contactdb/lists/".get_option('ohs_newsletter_sendgrid_list')."/recipients/";


        $c = $wpdb->get_results($wpdb->prepare(
            "
                    SELECT * FROM " . $wpdb->prefix . self::$tableName . "
                    WHERE `validation_code` = %s
                    ORDER BY id DESC
                    LIMIT 1
                    ", $code
        ));

        if (count($c) == 0) {
            return $this->OHSAPIError();
        }

        unset($c[0]->validation_code);
        unset($c[0]->id);
        $body = json_encode($c);
        //send API request to Sendgrid
        $response = wp_remote_post($sendgridRecepientAPI, [
                'method' => 'POST',
                'headers' => ["Authorization" => $sendgridHeader, 'Content-Type' => 'application/json'],
                'body' => $body
            ]
        );

        if ( is_wp_error( $response ) ) {
            //$error_message = $response->get_error_message();
            return $this->OHSAPIError();
        } else {
            if (!isset(json_decode($response['body'])->persisted_recipients[0])) {
                return $this->OHSAPIError();
            }
            $recipientID = json_decode($response['body'])->persisted_recipients[0];
        }

        $response = wp_remote_post($sendgridListAPI . $recipientID, [
            'method' => 'POST',
            'headers' => ["Authorization" => $sendgridHeader, 'Content-Type' => 'application/json'],
            'body' => ""
        ]);

        if ( is_wp_error( $response ) || $response['response']['code'] != '201' ) {
            //$error_message = $response->get_error_message();
            return $this->OHSAPIError();
        }

        $wpdb->delete($wpdb->prefix . self::$tableName, ['validation_code' => $code], ['%s']);
        $response = new WP_REST_Response();
        $response->set_status(302);
        $response->header('Location', $redirectURI);
        return $response;
    }

    private function OHSAPIError() {
        $response = new WP_REST_Response();
        $response->set_status(302);
        $response->header('Location', get_home_url());
        return $response;
    }

    private function wpse27856_set_content_type(){
        return "text/html";
    }
}