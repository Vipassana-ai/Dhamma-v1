Export tools
============

# What is Export Tool module ?

This modules allows you to configure exports from YML Drupal configuration files.

This module also provide tools for developpers to export very custom data.

# Installation

Install as usual for D8 with composer.

This modules require the `phpoffice/phpspreadsheet` library which will be automatically downloaded thanks to composer.

# How to use
There is no UI for the moment, and module is still in development.

An `ExportExecutable` class help you to launch your configured export.

Code example :

First you have to create your config file in your `config/install` module directory or in your `sync` global config directory.

File name example : `export_tools.export_definition.a_csv_export_definition.yml`

```yml
id: a_csv_export_definition
label: 'Export orders as CSV'
fields:
  order_id>order_number:
    label: 'Order number'
  order_id:
    label: 'Order id'
  order_item_id:
    label: 'Line item id'
  quantity:
    label: Quantity
  unit_price/number:
    label: 'Unit price'
  total_price/number:
    label: 'Subtotal line item price'
  'purchased_entity>product_id>sku':
    label: 'Product SKU'
  'purchased_entity>product_id>field_label':
    label: 'Product Title'
destination:
  plugin: spreadsheet.csv
```

And in your PHP code, this is how to launch the export.

```php
// Load the Export Storage definition thanks to entity type manager (\Drupal::service('entity_type.manager')).
$export_storage = $this->entityTypeManager()->getStorage('export_definition');
// Load the export definition config file you create.
$exportDefinition = $export_storage->load('a_csv_export_definition');
// Execute this export passing your entities.
$exportExecutable = new ExportExecutable($exportDefinition);
// Output is the rendered output.
$output = $exportExecutable->export($entities);
// You can also provide file informations to generate export files.
$output = $exportExecutable->export($entities, 'my-export.csv', 'public://exports/my-export.csv');
```

## Destination plugins available

For the moment the destination plugin available to export are :

- CSV
- Excel (XLS and XLSX)
- PDF (spreadsheet format)
- HTML (spreadsheet format)

## Field process plugins

Take a look on `src/Plugin/export_tools/FieldProcess` to see all field processed availables and how to use them. Documentation is available in plugin annotations.

## Export definition explained

The export definition config file is composed of required keys :

- id : The export definition id.
- label : The export definition verbose label.
- fields : The export definition fields to exports.
- destination : The destination plugin and configuration to use.

### Fields

Fields define which fields has to be exported and which processed are available.

Key can be more than field name, so it will help you to reach your data without writing plugins :

- `field_name/property_name`: On the left part of `/` is the field name to get data, and the right is the property name to get data. Example : `field_name/value`.
- `field_name[1]`: `[1]` means value for delta 1 is used.
- `field_name{3}` : `{3}` means the 3 first delta values will be used.
- `field_entity_reference>field_label` : On the left part of `>` is the entity reference field name, and the right is the field of the referenced entity to get value.

It's possible to combine each one.
Example :

```
field_entity_reference[1]>field_text/value
field_entity_reference[1]>field_text[0]/value
field_entity_reference[1]>field_products[0]>field_text[0]/value
```

By default, if no property is defined, the `->getString()` property is used.
