# Hh PsMigrationUpgradeDb

This module try to answer to the current need :

When upgrading your prestashop instance using the autoupgrade module you need to run it on the current instance.

With a standard workflow management with CI/CD it's not possible to work this way.  
The changed files can be tracked easily with git, but you still need to apply the db migration.

As the time of the creation of this module there no easy way to do it.  
With this module you can now apply your db migration easily with a simple command

```
bin/console hhennes:psmigration:upgrade-db fromVersion toVersion'
```

so for example

```
bin/console hhennes:psmigration:upgrade-db 8.1.3 8.1.5
```