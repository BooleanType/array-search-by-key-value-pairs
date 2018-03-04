# array-search-by-key-value-pairs

#### Simple functionality, that allows to searches the array for a given key-value pair(s). For instance, it can be useful, when you need to filter result from database with some conditions.

Function gets an array (`$array`), searches for multiple key/value pairs (`$search`) in `$array`'s second dimension and returns filtered
`$array` (with those values, that contain matches with `$search`).

- The minimum required PHP version is PHP 5.4.

### How-to
Consider the following two-dimensional array:
    
```php
$cars = [
   ['id' => 10, 'vendor' => 'Chevrolet', 'model' => 'Corvett', 'price' => 25965, 'color' => 'red', 'is_reserved' => true],
   ['id' => 12, 'vendor' => 'Chevrolet', 'model' => 'Corvett', 'price' => 34229, 'color' => 'blue', 'is_reserved' => false],
   ['id' => 15, 'vendor' => 'Chevrolet', 'model' => 'Camaro', 'price' => 27982, 'color' => 'blue', 'is_reserved' => true],
   ['id' => 22, 'vendor' => 'Chevrolet', 'model' => 'Camaro', 'price' => 30000, 'color' => '', 'is_reserved' => null],
   ['id' => 23, 'vendor' => 'Chevrolet', 'model' => 'Malibu', 'price' => 29999, 'color' => 'white', 'is_reserved' => true],
   ['id' => 105, 'vendor' => 'Ford', 'model' => 'Fiesta', 'price' => 15000, 'color' => 'gray', 'is_reserved' => false],
   ['id' => 1005, 'vendor' => 'Ford', 'model' => 'Mustang', 'price' => '50000', 'color' => 'white', 'is_reserved' => true],
];
```
    
Data, retrieved from database, very often looks like that, isn't it?

For example, we need to get all elements in `$cars`, where `'vendor`' is `'Ford'`. All we need to do, is to call provided **`getElemsByKeyValPairs()`** function:

```php
$filteredCars = getElemsByKeyValPairs($cars, ['vendor' => 'Ford']);
 ```
Result is an indexed array, which contains elements, that match the search condition.
    
----
### Arguments:

| Name               | Type     | Constraint  |Description
| -------------      | ------   | ----------- |---
| `$array`           | array    | required    |Where to search
| `$search`          | array    | required    |What to search (has 2 modes, see below) 
| `$logicOperator`   | string   | optional    |Logic operator for `$search`. `'and'`, `'or'` can be passed. `'and'` by default
| `$reindex`         | boolean  | optional    |If `true`, result array will be reindexed. `true` by default



##### `$search` MODES:

##### MODE 1. $search is **one-dimensional** array. For example:

```php
//search WHERE 'model' == 'Corvett' AND 'color' == 'blue'
$search = ['model' => 'Corvett', 'color' => 'blue'];
$filteredCars = getElemsByKeyValPairs($cars, $search);
```

`$filteredCars` contains `$car` elements with `IDs` 12, 22.

##### MODE 2. $search is **2-dimensional** array. 

Every array in second dimension contains **3 elements**: first is comparison operator, 2nd and 3rd - key and value respectively. For ex.:

```php
//search WHERE 'vendor' == 'Chevrolet' AND 'price' >= 30000
$search = [ 
    ['==', 'vendor', 'Chevrolet'], 
    ['>=', 'price', 30000] 
];

$filteredCars = getElemsByKeyValPairs($cars, $search);
```

`$filteredCars` contains `$car` element with `ID` = 12.

**Available comparison operators**: `==`, `===`, `>`, `<`, `>=`, `<=`, `!=`, `!==`, `<>`, `in` (SQL `IN` operator analogue), `preg` (`preg_match()` is used).
If you input unsupported operator (for ex., `=`), `==` will be used instead.

----

### Explanatory examples

`$cars` array from **"How-to"** section is taken as a basis.

```php
    //'vendor' == 'Ford'. Result contains elemnts with IDs 105, 1005
    $filteredCars = getElemsByKeyValPairs($cars, ['vendor' => 'Ford']);


    //'vendor' == 'Ford'. IDs 105, 1005 ($filteredCars keys are 5,6 instead of 0, 1)
    $filteredCars = getElemsByKeyValPairs($cars, ['vendor' => 'Ford'], 'and', false);


    //'model' == 'Corvett' AND 'color' == 'blue'. ID 12
    $filteredCars = getElemsByKeyValPairs($cars, ['model' => 'Corvett', 'color' => 'blue']);
    
    //'vendor' == 'Chevrolet' AND 'price' >= 30000. IDs 12, 22
    $filteredCars = getElemsByKeyValPairs($cars, [ ['==', 'vendor', 'Chevrolet'], ['>=', 'price', 30000] ]);
    
    //'vendor' == 'Ford' AND 'model' != 'Fiesta'. ID 1005
    $filteredCars = getElemsByKeyValPairs($cars, [ ['==', 'vendor', 'Ford'], ['!=', 'model', 'Fiesta'] ]);


    //two calls below do the same thing
    //'price' == 30000 OR 'price' == 50000. IDs 22, 1005
    $filteredCars = getElemsByKeyValPairs($cars, [ ['==', 'price', 30000], ['==', 'price', 50000] ], 'or');
    
    //'price' IN (30000, 50000). IDs 22, 1005
    $filteredCars = getElemsByKeyValPairs($cars, [ ['in', 'price', [30000, 50000]] ]);


    //'color' IN ('blue', 'red') AND 'is_reserved' == true. IDs 10, 15
    $filteredCars = getElemsByKeyValPairs($cars,  [ ['in', 'color', ['blue', 'red']], ['==', 'is_reserved', true] ]);
    
    //'color' IN ('blue', 'red') OR 'is_reserved' == true. IDs 10, 12, 15, 23, 1005
    $filteredCars = getElemsByKeyValPairs($cars,  [ ['in', 'color', ['blue', 'red']], ['==', 'is_reserved', true] ], 'or');


    //all models, which begin with 'M' symbol. 3rd argument must be a valid regexp. IDs 23, 1005
    $filteredCars = getElemsByKeyValPairs($cars,  [ ['preg', 'model', '/^M/'] ]);


    //'price' === 50000. Result is empty array, because comparison is strict
    $filteredCars = getElemsByKeyValPairs($cars,  [ ['===', 'price', 50000] ]);
```
