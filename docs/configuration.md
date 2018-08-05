# apiCore configuration

All system-wide configuration is located in file __"config.php"__ in **ApiCore**'s root directory.

Configuration file is required to return a PHP array.

## Database connection

Current version supports only MySQL database. Connection information may be set via `database` key.

- `host` - Database host, by default set to `localhost`.
- `name` - Database name.
- `user` - User for connecting to database.
- `pass` - Password for connecting to database.

### Example configuration:

    return [
        // ...
        "database" => [
            "host" => "localhost",
            "name" => "mydb",
            "user" => "mysqluser",
            "pass" => "123pass"
        ]
        // ...
    ];
