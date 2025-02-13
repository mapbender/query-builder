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


###Configuration

You need an administration table in your database where you store your sql definitions (see example below).

* **Title:** Title of the element.
* **Configuration:** YAML-Field to define the connection and settings for the administration table

```
   connection: geodata_db    
   table: query_builder_data   
   uniqueId: id
   titleFieldName: name
   sqlFieldName: sql_definition
   orderByFieldName: ordering
   connectionFieldName: connection_field_name
   filter: 'display is not null'
   export_format: xlsx
```

* **connection:** database connection 
* **table:** administration table where the queries are stored
* **uniqueId:** refer to the unique id of the table
* **titleFieldName:** name to display for the SQL query. Appears later in the SQL query table as the title.
* **sqlFieldName:** table column with the SQL query
* **orderByFieldName:** table column for specifying the order in the SQL query table
* **connectionFieldName:** table column with the database connection for the SQL query
* **Allow create:** gives the permission to create new queries. Not activated per default. 
* **filter:** definition of a filter if not all queries should be published
* **export_format:** activate export to xlsx

There are also some options that you can configure:

* **Allow save:** Gives the permission to save changes in the queries (default: false).
* **Allow remove:** Gives the permission to remove queries (default: false).
* **Allow execute:** Gives the permission to execute queries (default: true).
* **Allow print:** Gives the permission to print the results of queries (default: true).
* **Allow export:** Gives the permission to export the results of queries (default: true).
* **Allow search:** Gives the permission to search for a query/ results of queries (default: false).



### Administration table to store the query definitions and metadata

This administration table can be created in an existing database using the following SQL command.


```
   CREATE TABLE public.query_builder_data (
      id serial PRIMARY KEY,
      name character varying,
      sql_definition text,
      display integer,
      ordering integer,
      connection_field_name character varying
   );
```

The following demodata with SQL queries can be used for the exemplary use.
The SQL commands for the creation of the queried tables can be found in the documentation for the digitizer element.

```                
   INSERT INTO public.query_builder_data (id, name, sql_definition, display, ordering, connection_field_name) VALUES (1, 'Report 1: Time and Date', 'Select now() as "Zeit", date(now()) as "Datum";', 1, 1, 'geodata_db');
   INSERT INTO public.query_builder_data (id, name, sql_definition, display, ordering, connection_field_name) VALUES (3, 'Report 2: Mapbender applications', 'Select id, title, slug from mb_core_application;', 1, 4, 'default');
   INSERT INTO public.query_builder_data (id, name, sql_definition, display, ordering, connection_field_name) VALUES (4, 'Report 3: Mapbender users', 'Select id, username from fom_user;', 1, 3, 'default');
   INSERT INTO public.query_builder_data (id, name, sql_definition, display, ordering, connection_field_name) VALUES (2, 'Hello world', 'Select now() as "Zeit", date(now()) as "Datum", ''Hello world!'' as gruss;', 1, 2, 'default');
```
 
