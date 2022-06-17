# Installation

As a standard WordPress plugin, installation is the same as most other plugins. This can be done by uploading the plugin as a zip file, or cloning this Git repo in the `wp-content/plugins` directory and running `composer install` from the new directory.

After you install the plugin, you need to copy the `.env.example` file into a `.env` file and fill in the needed details according to instructions present in the `.env` file. Of course, if you provide those ENV variables through other means, that is fine by us.

*__Note__: Pressody Records requires PHP 7.4 or later.*

## Zip File

1. Download the [latest release](https://github.com/pressody/pressody-records/releases/latest) from GitHub (use the asset named `pressody-records-{version}.zip`).
2. Go to the _Plugins &rarr; Add New_ screen in your WordPress admin panel and click the __Upload Plugin__ button at the top.
3. Upload the zipped archive.
4. Click the __Activate Plugin__ link after installation completes.

### Updates

[GitHub Updater](https://github.com/afragen/github-updater) can be used to receive notifications and install updates when new releases are available.

*__Note__: If you're using GitHub Updater to install Pressody Records, copy the full URL to the [latest release asset](https://github.com/pressody/pressody-records/releases/latest) (the asset named `pressody-records-{version}.zip`). Using the repository URL alone won't work.*

[Back to Index](index.md)
