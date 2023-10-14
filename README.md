# Croox Statamic Meilisearch

This addon wraps the meilisearch statamic addon, adding
a couple of croox-specific features.

## Installation

1. Install the addon via composer
    ```json
    {
        "repositories": [
            {
                "type": "github",
                "url": "https://github.com/croox/statamic-meilisearch"
            }
        ],
        "require": {
            "croox/statamic-meilisearch": "dev-main"
        }
    }
    ```
2. Follow the setup instructions of the [meilisearch addon](https://github.comelvenstar/statamic-meilisearch)
3. Use the additional configuration options described below

### Usage of meilisearch.croox.com

If you want to use the meilisearch instance hosted at meilisearch.croox.com, check 1Password
for the required environment variables.

## Features

### Unique index names
In order to support multiple environments on the same meilisearch
instance, the index names are prefixed with the app name and environment.
If this does not satisfy your needs, you can override the index name
by setting the `index_name` config value.

### Custom Query Options
You can set custom query options in the config file by using the 
`query_options` key. These query options will be sent to meilisearch
on every search request.
