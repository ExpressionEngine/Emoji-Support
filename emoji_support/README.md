# Emoji Support

This add-on makes the necessary changes to an existing ExpressionEngine install's database so that it will support emoji! 🎉

## Requirements

- ExpressionEngine 3+
- PHP 5.4+

## Installation

1. Download the [latest release](https://github.com/ExpressionEngine/Emoji-Support/releases/latest).
2. Copy the `emoji_support` folder to your `system/user/addons` folder (you can ignore the rest of this repository's files).
3. In your ExpressionEngine control panel, visit the Add-On Manager and click Install next to "Emoji Support".

## Instructions

Visit the Emoji Support add-on in your control panel, and follow the onscreen instructions (including making a database backup!). Click to run. Party time! 🎊💃🕺

### Third-party table warnings

The add-on will perform a pre-flight check to make sure that your environment can support emoji, as well as check if any third-party database tables need to be modified before the conversion can run. If you get a warning that some tables need modifying before you can convert your database for emoji support, you should contact the developer who created the table for instructions.

**Warning: technical content ahead!**

Emoji and other 4-byte characters take up, well, 4 bytes each instead of 3 bytes like other characters. Character limits in MySQL are expressed in terms of 3-byte characters, so a character limit of 255 can only actually store 191 4-byte characters (255 * 3 / 4). Maths, oy.

This is normally fine, except where table indexes are concerned. If a column has a char/text-based index in MySQL, MySQL needs to be instructed to only use a max of 191 characters for the key. You can often fix this yourself using the typical pattern:

```
DROP INDEX `column_name` ON `exp_my_table`;,
CREATE INDEX `column_name` ON `exp_my_table` (`column_name`(191));
```

It's always good idea to check with the developer though, as it is possible that this modification would have unintended consequences depending on how the add-on is using those keys.

## Change Log

### 2.0.0

- Relicensing under the Apache 2.0 Software License

### 1.0.2

- Fixed a PHP error when uninstalling this add-on.

### 1.0.1

- Fixed the link for database backups based on which version of ExpressionEngine is installed

### 1.0.0

- Initial release. Boom!

## Additional Files

You may be wondering what the rest of the files in this package are for. They are solely for development, so if you are forking the GitHub repo, they can be helpful. If you are just using the add-on in your ExpressionEngine installation, you can ignore all of these files.

- **.editorconfig**: [EditorConfig](http://editorconfig.org) helps developers maintain consistent coding styles across files and text editors.
- **.gitignore:** [.gitignore](https://git-scm.com/docs/gitignore) lets you specify files in your working environment that you do not want under source control.
- **.travis.yml:** A [Travis CI](https://travis-ci.org) configuration file for continuous integration (automated testing, releases, etc.).
- **.composer.json:** A [Composer project setup file](https://getcomposer.org/doc/01-basic-usage.md) that manages development dependencies.
- **.composer.lock:** A [list of dependency versions](https://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file) that Composer has locked to this project.

## Copyright / License Notice

This project is copyright (c) 2018 EllisLab, Inc ([https://ellislab.com](https://ellislab.com)) and is licensed under Apache License, Version 2.0.

Complete license terms and copyright information can be found in [LICENSE.txt](LICENSE.txt) in the root of this repository.

"ExpressionEngine" is a registered trademark of EllisLab, Inc. in the United States and around the world. Refer to EllisLab's [Trademark Use Policy](https://ellislab.com/trademark-use-policy) for access to logos and acceptable use.
