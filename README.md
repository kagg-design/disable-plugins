# WordPress Plugin Disable Plugins

Disable Plugins allows to disable unneeded plugins on certain urls/ajax calls to improve site performance.

# Description

This is an must-use plugin (mu-plugin) which shall reside in mu-plugins folder.

Plugin is developed for internal use, mainly on wpml.org and toolset.com

## Usage

Define filters in
`wp-content/mu-plugins/filters.json`, using `wp-content/mu-plugins/disable-plugins/filters.sample.json` as a sample. 

Filters to have the following structure

```
[
  {
    "patterns": [
      ".*(showcase|escaparate|vorzeigeprojekte|sites-vitrine|mostruario|ショーケース|展示|vetrina|المعرض)\\/$",
      ".*showcase\\/.*",
    ],
    "locations": [
      "frontend"
    ],
    "disabled_plugins": [
      "wordpress-seo/wp-seo.php",
    ]
  },
  { ...
  }
]
```

or

```
[
  {
    "patterns": [
      ".*(showcase|escaparate|vorzeigeprojekte|sites-vitrine|mostruario|ショーケース|展示|vetrina|المعرض)\\/$",
      ".*showcase\\/.*",
    ],
    "locations": [
      "frontend"
    ],
    "enabled_plugins": [
      "sitepress-multilingual-cms/sitepress.php",
      "wordpress-seo/wp-seo.php",
      "wpml-string-translation/plugin.php"
    ]
  },
  { ...
  }
]
```

where `patterns` is an array of regular expressions to compare with page slug (for frontend filters), ajax action (for ajax filters), etc.

`locations` is an array of locations. Allowed locations are `frontend`, `backend`, `ajax`, `rest`, `cli`.

`disabled_plugins` can contain the list of plugins to disable.

`enabled_plugins` can contain the list of plugins to leave enabled. Only these plugins will be enabled, all others will be disabled.

## Installation

In `wp-content/mu-plugins` folder:
```
git clone https://github.com/kagg-design/disable-plugins
cd disable-plugins
composer install --no-dev
cp disable-plugins.php ..
```

## Development

In `wp-content/mu-plugins` folder:
```
git clone https://github.com/kagg-design/disable-plugins
cd disable-plugins
composer install
cp disable-plugins.php ..
```

## License

The WordPress Plugin Disable Plugins is licensed under the GPL v2 or later.

> This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, version 2, as published by the Free Software Foundation.

> This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

> You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

A copy of the license is included in the root of the plugin’s directory. The file is named `LICENSE`.

