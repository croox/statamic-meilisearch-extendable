# Croox Statamic Meilisearch

This addon wraps the meilisearch statamic addon, adding the ability to extend it's
behaviour and configuration.

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

## Usage

This addon can be used exactly the same way as the original meilisearch addon - however it does
have some additional features and configuration options. Additionally, it is possible to extend
the behaviour of meilisearch by creating your own classes extending `MeilisearchOptionModifier`
(see below).

### `IndexNamePrefix`
In order to support multiple environments on the same meilisearch  instance, the index names are prefixed with the
app name and environment. If this does not satisfy your needs, you can override the index name by setting the
`index_name` config value.

### `EnsureKeyIsNotMaster`
In order to prevent the use of a master key in production, the addon will check if the key is a master key.
If it is, it will throw an exception with information on how to create a new, restricted key.

### `AdditionalQueryOptions`
You can set custom query options in the config file by using the `query_options` key. These query options will be
sent to meilisearch on every search request.

```php
// config/statamic/search.php
'indexes' => [
    'default' => [
        // See https://www.meilisearch.com/docs/reference/api/search
        'query_options' => [
            'sort' => 'title:desc'
        ] 
    ]
]
```

### `RawResults`
You can access the raw result from meilisearch by accessing `rawResult`. This can
be helpful to get formatted results combined with `query_options`.

```html
{{ search:results as="results"}}
    {{ results }}
        <h1>{{ rawResult._formatted.title }}</h1>
    {{ /results }}
{{ /search:results }}
```

### `SearchSnippets`
If configured, the search results will contain a `search_snippets` array that mimics the
`search_snippets` of the `local` driver: It contains highlighted matches of the search term
in the results. This can be activated by using `snippet_length` in the configuration.

```php
// config/statamic/search.php
'indexes' => [
    'default' => [
        'snippet_length' => 50
    ]
]
```

NOTE 1: This feature is implemented by leveraging meilisearches highlighting feature. Enabling this implies the following
        `query_options`: `attributesToHighlight=['*']`, `highlightPreTag='<mark>'`, `highlightPostTag='</mark>'`. You can
        customize them if you want to.

NOTE 2: In contrast to the `local` driver, the `search_snippets` array will contain the Tag marking the search
      query already, making `| mark` in the template unnecessary.

```html
{{ search:results as="results"}}
    {{ results }}
        <h1>{{ title }}</h1>
        {{ search_snippets | implode('...') | substr(0, 300) }}
    {{ /results }}
{{ /search:results }}
```

### Implementing your own `MeilisearchOptionModifier`
In order to extend the behaviour of the meilisearch addon, you can create your own class extending `MeilisearchOptionModifier`
and register it in the config file under `meilisearch_modifiers`.

NOTE: You will probably want to use `...Meilisearch::DEFAULT_MODIFIERS` in order to keep the default modifiers enabled.

```php
'indexes' => [
    'default' => [
        'meilisearch_modifiers' => [
            ...\Croox\StatamicMeilisearch\Meilisearch::DEFAULT_MODIFIERS,
            \App\Modifiers\MyCustomModifier::class,
        ],
    ],
],
 ```