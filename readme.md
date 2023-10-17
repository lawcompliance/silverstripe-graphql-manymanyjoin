# GraphQL Many Many Join

## Introduction
Adds support for exposing `many_many` join data via a plugin

## Requirements
* SilverStripe CMS 4.0

## Usage
Activate the plugin on the desired model read operation e.g

```
<Namespace>\<DataObject>:
  operations:
    read:
      plugins:
        exposeManyManyJoin: true
```

Don't forget to allow the read operation on the many many relationship and/or through object model

```
<Namespace>\<ManyManyObject>:
  operations:
    read: true
<Namespace>\<ThroughObject>:
  operations:
    read: true
```

By enabling the `exposeManyManyJoin` plugin a new field `_join` will be added to the model. This new `_join` field will
contain a seperate field for each valid `many_many` relation (that contains valid join data).

## Example
Given the following code example, please see the example queries below

### Code
```php
class Category extends DataObject implements PermissionProvider
{
    private static $many_many = [
        'Products' => Product::class,
        'FeaturedProducts' => Product::class
    ];

    private static $many_many_extraFields = [
        'Products' => [
            'Quantity' => 'Int'
        ],
        'FeaturedProducts' => [
            'TopSeller' => 'Boolean'
        ]
    ];
}
```
```php
class Package extends DataObject
{
    private static $many_many = [
        'Items' => [
            'through' => PackageProduct::class,
            'from' => 'Package',
            'to' => 'Product',
        ]
    ];
}
```

```php
class PackageProduct extends DataObject
{
    private static $db = [
        'SalePrice' => 'Currency(19,4)'
    ];

    private static $has_one = [
        'Package' => Package::class,
        'Product' => Product::class
    ];

}
```

### Queries
```
query {
  readCategories {
    edges {
      node {
        id
        title
        products {
          id
          _join {
            categoryProducts {
              quantity
            }
          }
        }
        featuredProducts {
          _join {
            categoryFeaturedProducts {
              topSeller
            }
          }
        }
      }
    }
  }
}
```

```
query {
  readPackages {
    edges {
      node {
        id
        title
        items {
          id
          _join {
            packageProduct {
              salePrice
            }
          }
        }
      }
    }
  }
}
```
