# Issues

If you find a bug or have a feature request, please open an issue on the [GitHub repository](https://github.com/croox/statamic-meilisearch-extendable/issues).

# Pull Requests

If you want to contribute code, please ensure that your code passes tests and static analysis.
The following checks should be run before submitting a pull request:

* Unit tests: Can be run from any PHP environment using `./vendor/bin/phpunit --testsuite Unit`
* Integration tests: Requires a running Meilisearch instance and the `MEILISEARCH_HOST` / `MEILISEARCH_KEY environment variables to be set.
  You can use the `./run-integration-tests-docker.sh` script in order to start a meilisearch instance and run the tests using docker.
* Static analysis: `./vendor/bin/psalm`
* Code style: `composer analyze`
