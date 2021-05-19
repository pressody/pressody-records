# Settings

At _Settings &rarr; LT Records_, you'll find the settings page:

## Vendor

When requiring a package from PixelgradeLT Records, the default would be a package name like `pixelgradelt-records/genesis`.

The "Vendor" field allows this to be changed; a value of `mypremiumcode` would mean the `require` package name would be like `mypremiumcode/genesis`.

## Github OAuth Token

Github has a rate limit of 60 requests/hour on their API for requests not using an OAuth Token.

Since most packages on Packagist.org have their source on Github, and you may be using actual Github repos as sources, you should definitely generate a token and save it here.

## Access

See the document on [Security](security.md) for more information.

[Back to Index](index.md)
