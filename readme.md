# GraphQL Many Many Join

## Introduction
Adds support for many many join data by adding it to the scaffolded type

## Requirements
* SilverStripe CMS 4.0

## Usage
Define exactly which Scaffolded DataObjects need to have their `Join` object made available

```
Internetrix\GraphQLManyManyJoin\DataObjectScaffolderExtension:
  list_items:
    <list object>: <through object>
```

Don't forget to allow read operations and make the necessary fields available on both the list object and the through object

```
SilverStripe\GraphQL\Manager:
  schemas:
    default:    #default schema
      scaffolding:
        types:
          <list object>:
            fields: '*'
            operations:
              read: true
          <through object>:
            fields: '*'
            operations:
              read: true
```

####Todo:
* Expose the fields defined in `many_many_extraFields`. (only handles through objects at the moment)

#### Notes:
This is pretty hacky, but it works.
 