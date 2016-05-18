<?php
    // Include required libraries
    require_once('config.php');
    require_once('classes/request.class.php');
    require_once('classes/mail.class.php');

    // Init objects of Request and MailClass
    $requestObj = new Request();
    $mailObj = new MailClass($requestObj);
    // Run application
    $mailObj->run_application();