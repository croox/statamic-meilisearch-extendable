<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

class SynonymsOptionModifier extends MeilisearchOptionModifier
{
    public function preProcessConfiguration(array $config): array
    {
        /** @var array<string|int, list<string>> $synonymConfig */
        $synonymConfig = $config['meilisearch']['synonyms'] ?? [ ];
        if (count($synonymConfig) === 0) {
            return $config;
        }

        /** @var array<string, list<string>> $synonyms */
        $synonyms = $config['settings']['synonyms'] ?? [ ];
        foreach ($synonymConfig as $key => $values) {
            if (is_numeric($key)) {
                foreach ($values as $value) {
                    $synonyms[$value] = array_values(array_filter(array_unique([
                        ...($synonyms[$value] ?? [ ]),
                        ...$values,
                    ]), fn ($v) => $v !== $value));
                }
            } else {
                $synonyms[$key] = array_values(array_unique([
                    ...($synonyms[$key] ?? [ ]),
                    ...$values,
                ]));
            }
        }

        $config['settings']['synonyms'] = $synonyms;

        return $config;
    }
}
