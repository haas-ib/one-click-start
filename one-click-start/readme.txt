=== One Click Start ===
Contributors: haaas
Donate link: https://haas-ib.github.io/one-click-start/#donate
Tags: one click start, one click setup, automate task, one click, wordpress setup
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, reliable tool to automate your initial WordPress setup tasks like cleanup, permalink changes, and bulk plugin installation.

== Description ==

One Click Start is a time-saving tool for WordPress developers, agencies, and freelancers. Instead of repeating the same boring setup tasks on every new site—deleting default content, setting permalinks, configuring discussion settings, and installing your favorite set of plugins—you can create a single "recipe" and deploy it in seconds.

This plugin helps you enforce consistency and best practices across all your projects. It is built with a modern interface and a robust batch-processing engine to handle tasks reliably without server timeouts.

**Core Features Include:**

* **Initial Cleanup:** Delete default posts, pages, and bundled plugins like Hello Dolly.
* **Core Settings:** Configure permalinks, search engine visibility, discussion settings, and security options like XML-RPC.
* **Plugin & Theme Installation:** Select from a curated list of popular developer themes and plugins to install and activate automatically.
* **Content Setup:** Automatically create a Primary Menu to get started.
* **Import/Export:** Save your perfect recipe as a JSON file and import it into any new site for instant setup.
* **Safe & Reliable:** A progress modal shows you the status of every task, and the plugin is built to handle errors gracefully without crashing your site.

== Installation ==

1.  Upload the `one-click-start` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to the "One Click Start" menu in your admin sidebar to configure your recipe.

== Frequently Asked Questions ==

= Can this break my site? =

The plugin is built with safety in mind. Plugin activations are run in a "sandbox" mode to prevent fatal errors from crashing your site, and it checks for existing content before deleting anything. However, as it performs major actions, it is always recommended to run it on a fresh WordPress installation or have a backup of your site.

= How do I add my own plugins or themes to the list? =

Currently, the plugin uses a curated list of popular tools for reliability. The ability to add custom plugins and themes is being considered for a future version.

= How does the Import/Export work? =

The "Export" button will save a `.json` file of your currently configured recipe to your computer. On another site, you can use the "Import" button to upload that file, and it will instantly load all the saved settings.

= Does this plugin work on WordPress Multisite? =

The current version is designed for standard, single-site WordPress installations. While some features like content cleanup and settings configuration will work on a sub-site, plugin and theme installation will fail. Full Multisite support from the Network Admin dashboard is being considered for a future version.

== Screenshots ==

1.  The main recipe builder interface.
2.  The deployment progress modal with the live action.

== Changelog ==

= 1.0.0 =
* Initial public release.