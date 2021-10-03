# WordPress Plugin Disable Plugins

Disable Plugins allows disabling unneeded plugins on certain urls/ajax/wc-ajax/wp-cli/xml-rpc calls to improve site performance.

# Description

This is a must-use plugin (mu-plugin) which shall reside in mu-plugins folder.

## Usage

Define filters in
`wp-content/mu-plugins/filters.json`, using `wp-content/mu-plugins/disable-plugins/filters.sample.json` as a sample.

Filters to have the following structure

```
[
  {
    "patterns": [
      ".*"
    ],
    "locations": [
      "frontend"
    ],
    "disabled_plugins": [
      "jetpack/jetpack.php",
      "tablepress/tablepress.php"
    ]
  },
  {
    "patterns": [
      ".*\\/solutions\\/bale-packing\\/bale-packing-system",
      ".*\\/news\\/to-pack-better-round-bales-vs-square"
    ],
    "locations": [
      "frontend"
    ],
    "enabled_plugins": [
      "tablepress/tablepress.php"
    ]
  },
  {
    "patterns": [
      ".*\\/news\\/.+$"
    ],
    "locations": [
      "frontend"
    ],
    "enabled_plugins": [
      "jetpack/jetpack.php"
    ]
  },
  {
    "patterns": [
      ".*admin\\.php\\?page=(support-queues|bbps_management_dashboard|bbps-stats-dashboard.*)"
    ],
    "locations": [
      "backend"
    ],
    "disabled_plugins": [
      "woothemes-updater/woothemes-updater.php",
      "wp-polls/wp-polls.php"
    ]
  },
  {
    "patterns": [
      "wpv_get_archive_query_results"
    ],
    "locations": [
      "ajax"
    ],
    "disabled_plugins": [
      "wp-cron-cleaner/wp-cron-cleaner.php",
      "wp-polls/wp-polls.php"
    ]
  },
  {
    "patterns": [
      "cron event run kagg_w2f_update_ban"
    ],
    "locations": [
      "cli"
    ],
    "disabled_plugins": [
      "akismet/akismet.php"
    ]
  },
  {
    "patterns": [
      "wp.getAuthors"
    ],
    "locations": [
      "xml-rpc"
    ],
    "disabled_plugins": [
      "quform/quform.php"
    ]
  }
]
```

where `patterns` is an array of regular expressions to compare with page slug (for frontend filters), ajax or WooCommerce ajax action (for ajax filters), xml-rpc function (for xml-rpc filters) etc.

`locations` is an array of locations. Allowed locations are `frontend`, `backend`, `ajax`, `rest`, `cli`, `xml-rpc`.

`disabled_plugins` can contain the list of plugins to disable.

`enabled_plugins` can contain the list of plugins to leave enabled.

Each disabled/enabled plugin is described by its `folder/plugin-file.php` string.

In the example above, there are several set of filters: 3 for frontend, 1 for backend, 1 for ajax, 1 for wp-cli and 1 for xml-rpc.

By the first frontend filter, we disable 2 plugins (Jetpack and TablePress) for all urls on the frontend. By the second filter, we enable TablePress plugin for 2 urls on the frontend. By the third filter, we enable Jetpack on some other urls on the frontend.

By the backend filter, we disable 2 plugins (WooThemes Updater and WP Polls) on certain admin pages.

By the ajax filer, we disable 2 plugins (WP Cron Cleaner and WP Polls) during the `wpv_get_archive_query_results` ajax request.

By the wp-cli filter, we disable Akismet plugin during the execution of the wp-cli command `cron event run kagg_w2f_update_ban`.

By the xml-rpc filter, we disable plugin QuForm on xml-rpc request with the `wp.getAuthors` function.

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

A copy of the license is in the root of the plugin’s directory. The file name is `LICENSE`.
