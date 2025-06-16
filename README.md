# Extendable Statamic Meilisearch Driver

This addon provides a [Meilisearch](https://www.meilisearch.com/) search driver for Statamic sites that provides additional
meilisearch centric features such as facetting, filtering and sorting. Additionally, the driver is extendable, allowing you
to add custom behaviour in your own application.

## Requirements

* PHP 8.1+
* Laravel 10+
* Statamic 5
* Meilisearch 1.0+

## Installation

```bash
composer require croox/statamic-meilisearch-extendable
```

## Usage

```php
// config/statamic/search.php
return [
    'drivers' => [
        'meilisearch' => [
            'credentials' => [
                'url' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
                'secret' => env('MEILISEARCH_KEY', ''),
            ],
        ],
    ],
    
    'indexes' => [
        'default' => [
            'driver' => 'meilisearch',
            'searchables' => [ 'all' ],
            'snippet_length' => 90, // Optional
            'meilisearch' => [
                // 'filtering' => [
                //     // `type` can be one of the following:
                //     // - `statamic` (default): All filtering logic is done in PHP by statamic itself. Meilisearch fetches all results
                //     //                         without filtering or pagination. This can be inefficient for large datasets.
                //     // - `split`: Filtering that can be done in meilisearch (when the property is found in `filterableAttributes`) is done
                //     //            in meilisearch, the rest is done in PHP.
                //     // - `meilisearch`: All filtering logic is done in meilisearch. If an unhandled where-case is found, then an exception
                //     //                  is thrown. This is the most efficient way to filter results and is required for pagination to work.
                //     'type' => 'meilisearch',
                //     'attributes' => [ 'date', 'tags' ]
                // ],
                // 'pagination' => [
                //     // `type` can be one of the following:
                //     // - `meilisearch`: Pagination is done in meilisearch. Requires `filtering.type` to be set to `meilisearch`
                //     // - `statamic` (default): Pagination is done in PHP. This is the default behaviour of statamic.
                //     'type' => 'meilisearch',
                // ],
                // 'query_options' => [
                //     // See https://www.meilisearch.com/docs/reference/api/search
                //     'distinct' => 'title'
                // ],
                // 'facets' => [
                //     'tags',
                //     'date',
                // ],
                // 'sort_order' => [
                //     // The attributes available for sorting must be listed under `available_fields`
                //     'available_fields' => [ 'date', 'name' ],
                //     
                //     // The sort order that is applied by default. If not specified, then the results
                //     // are returned in an order that meilisearch deems most fitting to the search term.
                //     'default_sort' => [ 'date:desc' ],
                //     
                //     // Optional
                //     'ranking_rules' => [ /* See https://www.meilisearch.com/docs/learn/filtering_and_sorting/sort_search_results#customize-ranking-rule-order-optional */ ]
                // ],
                // 'synonyms' => [
                //      // Searches for 'Dog' should also yield results containing 'Labrador'
                //     'Dog' => [ 'Labrador' ],
                //     
                //     // Searches for 'Labrador' should not yield results containing 'Dog',
                //     // therefor the inverse synonym is not added.
                //     // 'Labrador' => [ 'Dog' ],
                //     
                //     // A shorthand is available by not using a key. This is equivalent to
                //     // 'Bed' => [ 'Matrace', 'Futon' ],
                //     // 'Matrace' => [ 'Bed', 'Futon' ],
                //     // 'Futon' => [ 'Bed', 'Matrace' ], 
                //     [
                //         'Bed',
                //         'Matrace',
                //         'Futon'
                //     ],
                // ],
            ],
        ],
    ]
]
```

### Metadata

In addition to the search results provided through statamic, this driver also provides a `meilisearch_metadata` tag, that
contains additional information about the search results. The following metadata may be available:

* `runtime_ms`: The time it took to execute the search in milliseconds.
* `facets`: An array of facets that can be displayed to the user. For more details, check the facets section below.
* `active_sort`: Information about the currently active sort, if any.

```html
{{ search:results paginate="20" as="results" }}
  {{ meta = {meilisearch_metadata} }}

  Took {{ meta.runtime_ms }}ms
{{ /search }}
```


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

The facet values can be accessed in the template using the `facets` metadata key and are expected to be provided as
`facet[NAME]` in the request. The following subkeys are available:

* `facet[NAME][values][]`: To filter for one or more specific values
* `facet[NAME][min]`: To filter for values greater than or equal to the given value
* `facet[NAME][max]`: To filter for values less than or equal to the given value
* `facet[NAME][date_min]`: To filter for dates greater than or equal to the given date
* `facet[NAME][date_max]`: To filter for dates less than or equal to the given date

```html
{{ search:results paginate="20" as="results" }}
    {{ meta = {meilisearch_metadata} }}
    
    <!-- "Selection" facets, where the user can select one or more values to filter by -->
    {{ if meta.facets.tags }}
        <select>
            <option value="" {{ if meta.facets.tags.active.values | is_empty }}selected{{ /if }}>all</option>
            {{ foreach :array="meta.facets.tags.distribution" as="value|valueCount" }}
                <option value="{{ value }}" {{ if meta.facets.tags.active.values | in_array(value) }}selected="selected"{{ /if }} >
                  {{ value }} ({{ valueCount }})
                </option>
            {{ /foreach }}
        </select>
    {{ /if }}
    
    <!-- Numeric facets, that are filtered as a range -->
    {{ if meta.facets.number_trained_pets }}
        <input type="number" name="facet[number_trained_pets][min]" value="{{ meta.facets.number_trained_pets.active.min }}" />
        <input type="number" name="facet[number_trained_pets][max]" value="{{ meta.facets.number_trained_pets.active.max }}" />
    {{ /if }}
    
    <!-- Date facets, that are filtered as a range -->
    {{ if meta.facets.date }}
        <input type="date" name="facet[date][date_min]" value="{{ meta.facets.date.active.date_min | format('Y-m-d') }}" />
        <input type="date" name="facet[date][date_max]" value="{{ meta.facets.date.active.date_max | format('Y-m-d') }}" />
    {{ /if }}

  <!-- ... -->
{{ /search:results }}
```

### `QueryTime`

Allows using the `runtime_ms` metadata in order to print information about the query runtime.

```html
{{ search:results paginate="20" as="results" }}
    {{ meta = {meilisearch_metadata} }}
    <p>Query took {{ meta.runtime_ms }}ms</p>
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
<select name="sort_order[]">
    <option value="" {{ if meta.active_sort | is_empty }} selected {{ /if }}>
        Default
    </option>
    <option value="date:desc" {{ if meta.active_sort | in_array('date:desc') }} selected {{ /if }}>
        Newest first
    </option>
    <option value="date:asc" {{ if meta.active_sort | in_array('date:asc') }} selected {{ /if }}>
        Oldest first
    </option>
    <option value="name:asc" {{ if meta.active_sort | in_array('name:asc') }} selected {{ /if }}>
        By name ascending
    </option>
    <option value="name:desc" {{ if meta.active_sort | in_array('name:desc') }} selected {{ /if }}>
        By name descending
    </option>
</select>
```

### `Synonyms`

In order to improve search result ranking, the `SynonymsOptionModifier` allows specifying a list of
synonyms that are passed to meilisearch.

Synonyms can either be defined in the [default meilisearch format](https://www.meilisearch.com/docs/learn/relevancy/synonyms)
for more precision or in a shorthand format that automatically adds the inverse

```php
'indexes' => [
    'default' => [
        'meilisearch' => [
            'synonyms' => [
                // Searches for 'Dog' should also yield results containing 'Labrador'
                'Dog' => [ 'Labrador' ],
                
                // Searches for 'Labrador' should not yield results containing 'Dog',
                // therefor the inverse synonym is not added.
                // 'Labrador' => [ 'Dog' ],
                
                // A shorthand is available by not using a key. This is equivalent to
                // 'Bed' => [ 'Matrace', 'Futon' ],
                // 'Matrace' => [ 'Bed', 'Futon' ],
                // 'Futon' => [ 'Bed', 'Matrace' ], 
                [
                    'Bed',
                    'Matrace',
                    'Futon'
                ]
            ],
        ]
    ],
]
```


### Implementing your own `MeilisearchOptionModifier`
In order to extend the behaviour of the meilisearch addon, you can create your own class extending `MeilisearchOptionModifier`
and register it in the config file under `meilisearch_modifiers`.

NOTE: You will probably want to use `...Meilisearch::DEFAULT_MODIFIERS` in order to keep the default modifiers enabled.

```php
'indexes' => [
    'default' => [
        'meilisearch_modifiers' => [
            ...\Croox\StatamicMeilisearchExtendable\Meilisearch::DEFAULT_MODIFIERS,
            \App\Modifiers\MyCustomModifier::class,
        ],
    ],
],
 ```

NOTE: Some query related methods will be called twice for a single query - once in order to fetch the total number of results
and then a second time to fetch the actual results. If you want to have different behaviour for the two queries, you can
can use `$options['_is_count']`.


### Few words about Document IDs in meilisearch

When you index your Statamic Entries, the driver will always transform the ID. This is required because meilisearch only allows `id` to be a string containing alphanumeric characters (a-Z, 0-9), hyphens (-) and underscores (_). You can read more about this in the [meilisearch documentation](https://www.meilisearch.com/docs/learn/core_concepts/primary_key#invalid_document_id)

As an Entry, Asset, User or Taxonomy reference is a combination of the type, handle/container and ID separated with a `::` (e.g. assets::heros/human01.jpg, categories::cats) this could not be indexed by meilisearch.

As a Workaround, we take care add reference while indexing your entries automatically 🎉.

Internally Statamic will use `\Statamic\Facades\Data::find($reference)` to resolve the corresponding Statamic Entry, Asset, User or Taxonomy.
