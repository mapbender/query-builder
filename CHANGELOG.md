## 2.0.2
* Show message when trying to export results to file with 0 results ([PR#9](https://github.com/mapbender/query-builder/pull/9))

## 2.0.1
* Fix xlsx export was faulty when exporting results with exactly 0 or 1 result

## 2.0.0
* Update to Mapbender 4.1
* Removed dataStores entry in `parameters.yaml`
* Changed element configuration to YAML, see README.md for new configuration syntax
* Added additional permission for edit, create and delete
* Removed allowSave - use allowEdit

## 1.2.4
* Fix query result dialog table styling

## 1.2.3
* Fix randomly missing table(s) with multiple QueryBuilder elements in the same application

## 1.2.2
* Fix incompatibility with current doctrine versions
* Resolve Symfony 4.2 templating deprecation

## 1.2.1
* Fix Composer 2 autoloading errors

## 1.2.0
* Rebase on Mapbender 3.2.6+ service element API for Symfony 4 compatibility
* Resolve legacy CSS hacks

## 1.1.0
* Fix internal server error executing / manipulating non-existant query (use http 404)
* Use http GET method for read-only requests
* Update internal Element APIs for Mapbender 3.0.8
* Fix misc Symfony 4 conformance violations
* Remove "public" form field (no associated function)

## 1.0.6
* Fix initialization errors if MapbenderDataSourceBundle is not registered in kernel

## 1.0.5
* Fix functional incompatibility with Mapbender 3.2
* Fix styling conflicts with / styling dependencies on specific Mapbender versions
* Fix backend form type incompatibility with Symfony 3+
* Fix unescaped HTML / XSS vulnerability in Query results
* Fix error displaying empty Query result
* Fix outdated table state after editing existing Query
* Fix table not showing row for newly created Query until reload
* Fix no working interactions with newly created item until reload
* Fix edit dialog staying open for Query deleted via table interaction
* Fix broken text messages after successful Query save and delete
* Fix memory leaks when opening / closing dialogs
* Add explanatory tooltips to Query listing table
* Mark table-embedded delete button in red
* Remove all usages of (undeclared dependency) vis-ui.js
