# miner_watchdog_monitor
A REST API server written in PHP for monitoring Claymore miners.

# Used libraries

- [GardenSchema](https://github.com/vanilla/garden-schema) - Model data validation
- [PicORM](https://github.com/iNem0o/PicORM) - Database layer
- [AltoRouter](https://github.com/dannyvankooten/AltoRouter) - Routing
- [php-urljoin](https://github.com/fluffy-critter/php-urljoin) - Joining URL parts
- [php-annotations](https://github.com/pgraham/php-annotations) - Parsing anntotations from comments

# Documentation

Documentation is split between API (this is automatically generated from source code) and tutorials and examples written with markdown saved in docs directory.

This duality is caused by Sami (API documentation generator) which does not support pages and in-source documentation as Doxygen does.

## Documentation generating

For API documentation generating, run:

  php.exe sami.phar update config.php
