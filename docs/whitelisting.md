# Whitelisting Installed Plugins and Themes

Pressody Records supports standard plugins and themes. These must be whitelisted (managed via Records) to be exposed as Composer packages.

Plugins and themes are cached when they're whitelisted and new releases are downloaded and saved as soon as WordPress is notified they're available.

All cached versions are exposed in `packages.json` so they can be required with Composer -- even versions that haven't yet been installed by WordPress!

[Back to Index](index.md)
