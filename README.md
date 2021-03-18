# LibreSign GLPI plugin

Validate tickets with LibreSign

## Documentation

Read [Docs](docs/README.md)

## Translations

If you want contribute with translations, follow the steps:

Pre-requirements:

* Setup gettext. In Debian based Linux distributions, run `sudo apt update;sudo apt install gettext`.

### Update translations:

> **PS**: If is a new translation language, before, create a file called `lang.po` in folder `locales` on `lang` following the current filename pattern.

In root folder of plugin run:

```bash
vendor/bin/robo update_locales
```

### Building translations

After you update locales and maked translations, build translations

```bash
vendor/bin/robo compile_locales
```

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins/index.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
