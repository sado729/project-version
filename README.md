## Project Version

The package automatically updates the version of your project based on the changes you make on git. Versioning is marked as `"major.minor.patch"`. The patch value increases with each file change you make. If a new file is added, the minor part is incremented and the patch is reset.

---
## Installation
```sh
composer require sado729/project-version
```
After
```sh
php artisan vendor:publish --provider="Sado729\ProjectVersion\ProjectVersionServiceProvider"
php artisan vendor:publish --tag=config
```
to publish the Assets and Config.
After that, you need to edit the configuration file `(config/project-version.php)` according to your project. In the git_repository_name section, you need to enter the name of your project's github repository

---
## Usage
To use the package, you must first have an `informations` table with a `version` column. If not, you can create it with the following command:
```sh
php artisan vendor:publish --tag=migration
php artisan migrate
```
Everything is ready! You should run the following command instead of issuing the git pull command. At this time, the git pull command is automatically executed and the version is changed:
```sh
php artisan git:pull
```
![image](https://github.com/sado729/project-version/assets/22997209/7afd3521-1416-4ae9-9e05-eeffa523b788)

---

## License
The MIT License (MIT). Please see [License File](license.md) for more information.

---

## More from me

- Visit my website [MrSadiq.info](https://mrsadiq.info)
