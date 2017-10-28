# Emoji Support

This add-on makes the necessary changes to an existing ExpressionEngine install's database so that it will support emoji! 🎉

## Requirements

- ExpressionEngine 3+
- PHP 5.4+

## Installation

1. Download the [latest release](https://github.com/EllisLab/Emoji-Support/releases/latest).
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

### 1.0.0

- Initial release. Boom!

## Additional Files

You may be wondering what the rest of the files in this package are for. They are solely for development, so if you are forking the GitHub repo, they can be helpful. If you are just using the add-on in your ExpressionEngine installation, you can ignore all of these files.

- **.editorconfig**: [EditorConfig](http://editorconfig.org) helps developers maintain consistent coding styles across files and text editors.
- **.gitignore:** [.gitignore](https://git-scm.com/docs/gitignore) lets you specify files in your working environment that you do not want under source control.
- **.travis.yml:** A [Travis CI](https://travis-ci.org) configuration file for continuous integration (automated testing, releases, etc.).
- **.composer.json:** A [Composer project setup file](https://getcomposer.org/doc/01-basic-usage.md) that manages development dependencies.
- **.composer.lock:** A [list of dependency versions](https://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file) that Composer has locked to this project.

## License

Copyright (c) 2017 EllisLab, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

### Exclusions

Except as contained in this notice, the name of EllisLab, Inc. shall not be used in advertising or otherwise to promote the sale, use or other dealings in this Software without prior written authorization from EllisLab, Inc.
