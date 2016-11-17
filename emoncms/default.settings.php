<?php

    /*

    Database connection settings

    */

    $username = "";
    $password = "";
    $server   = "";
    $database = "";

    $redis_enabled = true;
    $redis_server = "localhost";

    $https_enable = true;

    $feed_settings = array(
        'phpfiwa'=>array(
            'datadir'=>'/var/lib/phpfiwa/'
        ),
        'phpfina'=>array(
            'datadir'=>'/var/lib/phpfina/'
        ),
        'phptimeseries'=>array(
            'datadir'=>'/var/lib/phptimeseries/'
        )
    );

    // (OPTIONAL) Used by password reset feature
    $smtp_email_settings = array(
      'host'=>"",
      'username'=>"",
      'password'=>"",
      'from'=>array('' => ''),
      'port'=>465
    );

    $enable_password_reset = true;

    // Checks for limiting garbage data?
    $max_node_id_limit = 32;

    /*

    Default router settings - in absence of stated path

    */

    // Default controller and action if none are specified and user is anonymous
    $default_controller = "user";
    $default_action = "login";

    // Default controller and action if none are specified and user is logged in
    $default_controller_auth = "user";
    $default_action_auth = "view";

    // Public profile functionality
    $public_profile_enabled = TRUE;
    $public_profile_controller = "dashboard";
    $public_profile_action = "view";

    /*

    Other

    */

    // Theme location
    $theme = "basic";

    // Error processing
    $display_errors = TRUE;

    // Allow user register in emoncms
    $allowusersregister = TRUE;

    // Enable remember me feature - needs more testing
    $enable_rememberme = TRUE;

    // Skip database setup test - set to false once database has been setup.
    $dbtest = FALSE;

    // Log4PHP configuration
    $log4php_configPath = 'logconfig.xml';
