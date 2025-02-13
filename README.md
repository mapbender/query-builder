# Mapbender QueryBuilder

The QueryBuilder is a Mapbender element. It is a query tool and enables the integration, display and editing of SQL queries and their result visualisation and export via the application interface. 

## Mapbender
See [Official site](https://mapbender.org/?q=en) | [Live demo](https://demo.mapbender.org/) | [News on Fosstodon](https://fosstodon.org/@mapbender)

[![DOI](https://zenodo.org/badge/DOI/10.5281/zenodo.5887014.svg)](https://doi.org/10.5281/zenodo.5887014)
![Packagist License](https://img.shields.io/packagist/l/mapbender/mapbender)

## Installation
Use `composer require mapbender/query-builder` and register MapbenderQueryBuilderBundle with the Symfony application kernel.


## Contributing

Please read official [contibuting documentation](https://github.com/mapbender/mapbender-starter/blob/master/CONTRIBUTING.md)


## QueryBuilder Functionality

You can use the QueryBuilder element in the sidepane. 

Depending on the settings, the QueryBuilder allows you to 

* create, edit and delete queries
* execute queries 
* output as Html
* Display in dialog
* Export to Excel
* Search 

The respective authorizations for the query options can be assigned in the configuration of the element


### Configuration

You need an administration table in your database where you store your sql definitions (see example below).

* **Title:** Title of the element.
* **Configuration:** YAML-Field to define the connection and settings for the administration table:
  * **connection:** database connection name (as defined in `doctrine.yaml`, default: `default`)
  * **table:** administration table name where the queries are stored (required)
  * **uniqueId:** refer to the unique id of the table (default: `id`)
  * **titleFieldName:** name to display for the SQL query. Appears later in the SQL query table as the title. (default: `name`)
  * **sqlFieldName:** table column with the SQL query (default: `sql_definition`)
  * **orderByFieldName:** table column for specifying the order in the SQL query table (default: `id`)
  * **connectionFieldName:** table column with the database connection for the SQL query (default: the same connection as given in `connection`)
  * **filter:** definition of a filter if not all queries should be visible as sql WHERE query, e.g. `active = 1` (optional)
  * **export_format:**: file type for the file export: `xlsx` (default), `xls` or `csv`

Example:

```yaml
   connection: geodata_db    
   table: query_builder_data   
   uniqueId: id
   titleFieldName: name
   sqlFieldName: sql_definition
   orderByFieldName: ordering
   connectionFieldName: connection_field_name
   filter: 'active is not null'
   export_format: xlsx
```

There are also some options that you can configure:

* **Allow execute:** If checked, users can execute the query to show the results in a popup (default: `true`).
* **Allow search:** If checked, users can filter the query list and popup results by a search string (default: `false`).
* **Allow HTML export:** If checked, users can view the data as a printable HTML page in a new window (default: `true`)
* **Allow file export:** If checked, users can download the query results as a spreadsheet file (xlsx, xls or csv depending on configuration; default: `true`)

You can either modify the queries directly in the database, the query builder also supports manipulating the data directly in mapbender. Since allowing users to modify arbitrary SQL is a potential security threat, in addition to checking the respective checkboxes, users with edit rights also need to be granted a global permission (Security - Global Permissions - QueryBuilder). 

In addition to the permissions, the databases that allow executing query builder queries need to be defined in the `parameters.yaml` file using the parameter `querybuilder_allowed_connections`, e.g.

```yaml
parameters:
    querybuilder_allowed_connections:
        - default
        - geodata
```

The following configuration entries toggle the availability of editing functionality:

* **Allow save:** If checked, users with the permission QueryBuilder.edit can modify existing queries (default: `false`). 
* **Allow create:** If checked, users with the permission QueryBuilder.create can add new queries (default: `false`).
* **Allow remove:** If checked, users with the permission QueryBuilder.remove can remove existing queries (default: `false`).


### Administration table to store the query definitions and metadata

This administration table can be created in an existing database using the following SQL command. This is just an example, you can choose the column names freely, just adjust the configuration accordingly.

```
CREATE TABLE public.query_builder_data (
   id serial PRIMARY KEY,
   name character varying,
   sql_definition text,
   active integer,
   ordering integer,
   connection_field_name character varying
);
```

The following demodata with SQL queries can be used for the exemplary use.
The SQL commands for the creation of the queried tables can be found in the documentation for the digitizer element.

```                
INSERT INTO public.query_builder_data (id, name, sql_definition, active, ordering, connection_field_name) VALUES (1, 'Report 1: Time and Date', 'Select now() as "Zeit", date(now()) as "Datum";', 1, 1, 'geodata_db');
INSERT INTO public.query_builder_data (id, name, sql_definition, active, ordering, connection_field_name) VALUES (3, 'Report 2: Mapbender applications', 'Select id, title, slug from mb_core_application;', 1, 4, 'default');
INSERT INTO public.query_builder_data (id, name, sql_definition, active, ordering, connection_field_name) VALUES (4, 'Report 3: Mapbender users', 'Select id, username from fom_user;', 1, 3, 'default');
INSERT INTO public.query_builder_data (id, name, sql_definition, active, ordering, connection_field_name) VALUES (2, 'Hello world', 'Select now() as "Zeit", date(now()) as "Datum", ''Hello world!'' as gruss;', 1, 2, 'default');
```
 
