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
Command options
---

Several options are available to tailor the behavior of the `hhennes:psmigration:upgrade-db` command:

- `--dry-run`  
  This option allows you to preview the list of database upgrades that would be applied, without making any actual changes. It's a safe way to check what will happen before running a real migration.

- `--get-version`  
  Use this option to simply retrieve and display the current database version, without performing any upgrade.

- `--no-db-config-update`  
  If you do not want the `PS_VERSION_DB` value in the configuration table to be updated after a successful upgrade, add this option.

Each of these options can be combined with the main arguments to adapt the command to your needs.


Development Environment
---

This module includes a Docker-based development environment for easy testing across multiple PrestaShop versions.

### Quick Start

1. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

2. Start the environment:
   ```bash
   make up
   ```

3. Access PrestaShop at `http://localhost:8080`

### Available Commands

- `make up` - Start the PrestaShop environment
- `make down` - Stop the environment
- `make logs` - View container logs
- `make shell` - Access the PrestaShop container shell
- `make install` - Install composer dependencies
- `make clean` - Remove all containers and volumes

### Testing Multiple PrestaShop Versions

Switch between PrestaShop versions easily:

```bash
# Switch to PrestaShop 1.7.8
make switch-1.7.8 && make up

# Switch to PrestaShop 8.1
make switch-8.1 && make up

# Switch to PrestaShop 9
make switch-9 && make up

# Switch to PrestaShop nightly
make switch-nightly && make up
```

Or run multiple versions simultaneously:

```bash
make multi
```

This will start:
- PrestaShop 1.7.8 on `http://localhost:8080`
- PrestaShop 8.1 on `http://localhost:8082`
- PrestaShop 9 on `http://localhost:8084`
- PrestaShop nightly on `http://localhost:8086`

### PHPStorm Integration

The environment includes Xdebug support for debugging with PHPStorm:

1. Go to **Settings → PHP → Servers**
2. Add a new server with name `localhost`
3. Set path mapping: `/var/www/html/modules/hhpsmigrationupgradedb` → project root
4. Start listening for debug connections

### Database Access

- **phpMyAdmin**: `http://localhost:8081`
- **MySQL Host**: `localhost:3306`
- **Username**: `prestashop`
- **Password**: `prestashop`


Compatibility
---

The prestashop ecosystem must move forward to modern versions.  
So this tool is only available with recent Prestashop versions and needs at least php7.4

Feel free to adapt it if you are using older versions.

| Prestashop Version | Compatible |
|--------------------| ---------|
| 1.7.8.x            | :heavy_check_mark: |
| 8.x                | :heavy_check_mark: |
| 9.x                | :heavy_check_mark: |



| Php Version | Compatible                         |
|-------------|------------------------------------|
| Under 7.4   | :x:                                |
| 7.4         | :heavy_check_mark:     until 0.1.4 |
| 8.1         | :heavy_check_mark:                 |
| 8.2         | :interrobang: Not yet tested       |
| 8.3         | :interrobang: Not yet tested       |
| 8.4         | :interrobang: Not yet tested       |
| 8.5         | :interrobang: Not yet tested       |
