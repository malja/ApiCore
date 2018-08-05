# First run

1) Create index.php file with following content:

    <?php

    // Require all necessary files
    require_once "./core/App.php";

    // Create application
    $app = new \core\App(
        realpath(dirname(__FILE__))
    );

    // Run apiCore app in DEBUG mode
    $app->run(true);

2) Create main configuration file (more in [Configuration](./configuration.md)).
3) Create app directory and inside routes.php file with routing settings.