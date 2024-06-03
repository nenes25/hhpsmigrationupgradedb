# Hh PsMigrationUpgradeDb

This module try to answer to the current need :

When upgrading your prestashop instance using the autoupgrade module you need to run it on the current instance.

With a standard workflow management with CI/CD it's not possible to work this way.  
The changed files can be tracked easily with git, but you still need to apply the db migration.

As the time of the creation of this module there no easy way to do it.  
With this module you can now apply your db migration easily with a simple command

```
bin/console hhennes:psmigration:upgrade-db fromVersion toVersion
```

so for example

```
bin/console hhennes:psmigration:upgrade-db 8.1.3 8.1.5
```


Compatibility
---

The prestashop ecosystem must moove forward to modern versions.  
So this tool is only available with recent Prestashop versions and needs at least php7.4

Feel free to adapt it if you are using older versions.

| Prestashop Version | Compatible |
|--------------------| ---------|
| 1.7.8.x | :heavy_check_mark: |
| 8.0,8.1                 | :heavy_check_mark: |



| Php Version | Compatible                   |
|-------------|------------------------------|
| Under 7.4   | :x:           |
| 7.4         | :heavy_check_mark:           |
| 8.0         | :interrobang: Not yet tested |
| 8.1         | :heavy_check_mark: |
