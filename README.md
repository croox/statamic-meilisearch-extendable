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
        'meilisearch' => [
            'query_options' => [
                'distinct' => 'title'
            ] 
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

### `Filtering`

By default, statamic fetches *all* results from the search index and implements filtering logic in PHP. This is not
ideal, as properly optimized search backends such as meilisearch are preferrable for this task. This option modifier
allows moving of filtering logic to meilisearch, which is much more efficient.

```php
'indexes' => [
    'default' => [
        'meilisearch' => [
            'filtering' => [
                'filtering' => [
                    'type' => 'split', // Or 'statamic' (default) or `meilisearch`
                    'attributes' => [ 'site' ],
                ]
            ]
        ],
    ],
]
```

Attributes found in `attributes` can be processed by meilisearch, so be sure to add them accordingly.

NOTE: Make sure to update the search index by using `please search:update` after changing the `attributes` config
      option in order to configure the meilisearch index correctly.

The following filter types are available:

* `statamic` (default): All filtering logic is done in PHP. This is the default behaviour of statamic.
* `split`: Filtering that can be done in meilisearch (when the property is found in `filterableAttributes`) is done
           in meilisearch. The rest is done in PHP.
* `meilisearch`: All filtering logic is done in meilisearch. If an unhandled where-case is found, then an exception is
                 thrown.

### `Pagination`

By default, statamic fetches *all* results from the search index and implements pagination logic in PHP. This is not ideal
in most cases, as it leads to unnecessary data transfer and processing. This option modifier allows moving of pagination logic to
meilisearch, which is much more efficient.

```php
'indexes' => [
    'default' => [
        'meilisearch' => [
            'pagination' => [
                'type' => 'meilisearch', // or 'statamic' (default)
            ]
        ],
    ]
]
```

The following pagination types are available:

* `meilisearch`: Pagination is done in meilisearch. Requires `filtering.type` to be set to `meilisearch`
* `statamic` (default): Pagination is done in PHP. This is the default behaviour of statamic. Because this fetches all data
             from the search index and the meilisearch query must have a limit, `pagination.statamic_hits` can be used in
             order to control the limit that is sent to meilisearch. It is set to `1000` by default.

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

### `Facets`
Enables the use of facets for faceted search, allowing you to filter the results based on specific attributes.
In order to use facets, you need to configure the `facets` key in the config file and update the search index.

**Note: The search index must be updated every time you change the facets.**
```php
'indexes' => [
    'default' => [
        'meilisearch' => [
            'facets' => [
                'tags',
                'number_trained_pets',
                'date',
            ],
        ]
    ],
]
```

The facet values can be acessed in the template using the `meilisearch_facets` tag and are expected to be provided as
`facet[NAME]` in the request. The following subkeys are available:

* `facet[NAME][values][]`: To filter for one or more specific values
* `facet[NAME][min]`: To filter for values greater than or equal to the given value
* `facet[NAME][max]`: To filter for values less than or equal to the given value
* `facet[NAME][date_min]`: To filter for dates greater than or equal to the given date
* `facet[NAME][date_max]`: To filter for dates less than or equal to the given date

```html
{{ search:results paginate="20" as="results" }}
  {{ meilisearch_facets as="facets" }}
    
    <!-- "Selection" facets, where the user can select one or more values to filter by -->
    {{ if facets.tags }}
        <select>
            <option value="" {{ if facets.tags.active.values | is_empty }}selected{{ /if }}>all</option>
            {{ foreach :array="facets.tags.distribution" as="value|valueCount" }}
                <option value="{{ value }}" {{ if facets.tags.active.values | in_array(value) }}selected="selected"{{ /if }} >
                  {{ value }} ({{ valueCount }})
                </option>
            {{ /foreach }}
        </select>
    {{ /if }}

    <!-- Numeric facets, that are filtered as a range -->
    {{ if facets.number_trained_pets }}
        <input type="number" name="facet[number_trained_pets][min]" value="{{ facets.number_trained_pets.active.min }}" />
        <input type="number" name="facet[number_trained_pets][max]" value="{{ facets.number_trained_pets.active.max }}" />
    {{ /if }}

    <!-- Date facets, that are filtered as a range -->
    {{ if facets.date }}
        <input type="date" name="facet[date][date_min]" value="{{ facets.date.active.date_min | format('Y-m-d') }}" />
        <input type="date" name="facet[date][date_max]" value="{{ facets.date.active.date_max | format('Y-m-d') }}" />
    {{ /if }}
  {{ /meilisearch_facets }}

  <!-- ... -->
{{ /search:results }}
```

### `QueryTime`

Allows using the `meilisearch_query_time` tag in order to print information about the query runtime.

```html
{{ search:results paginate="20" as="results" }}
    <p>Query took {{ meilisearch_query_time }}ms</p>
{{ /search:results }}
```

### `SortOrder`

Allows the user to change the sort order of the results.
**Note: The search index must be updated every time you change the config.**



```php
'indexes' => [
    'default' => [
        'meilisearch' => [
            'sort_order' => [
                // The attributes available for sorting must be listed under `available_fields`
                'available_fields' => [ 'date', 'name' ],
                
                // The sort order that is applied by default. If not specified, then the results
                // are returned in an order that meilisearch deems most fitting to the search term.
                'default_sort' => [ 'date:desc' ],
                
                // Optional
                'ranking_rules' => [ /* See https://www.meilisearch.com/docs/learn/filtering_and_sorting/sort_search_results#customize-ranking-rule-order-optional */ ]
            ],
        ]
    ],
]
```

```html
{{ meilisearch_sort_order as="sort_order" }}
    <select name="sort_order[]">
        <option value="" {{ if sort_order | is_empty }} selected {{ /if }}>
            Default
        </option>
        <option value="date:desc" {{ if sort_order | in_array('date:desc') }} selected {{ /if }}>
            Newest first
        </option>
        <option value="date:asc" {{ if sort_order | in_array('date:asc') }} selected {{ /if }}>
            Oldest first
        </option>
        <option value="name:asc" {{ if sort_order | in_array('name:asc') }} selected {{ /if }}>
            By name ascending
        </option>
        <option value="name:desc" {{ if sort_order | in_array('name:desc') }} selected {{ /if }}>
            By name descending
        </option>
    </select>
{{ /meilisearch_sort_order }}
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

NOTE: Some query related methods will be called twice for a single query - once in order to fetch the total number of results
      and then a second time to fetch the actual results. If you want to have different behaviour for the two queries, you can
      can use `$options['_is_count']`.
