# Delete (nested) keys from JSON object 

[![Build Status](https://travis-ci.org/avrora/key-del.svg?branch=master)](https://travis-ci.org/avrora/key-del) [![Dependency Status](https://david-dm.org/avrora/key-del.svg)](https://david-dm.org/avrora/key-del)

[![NPM](https://nodei.co/npm/key-del.png?downloads=true&downloadRank=true)](https://nodei.co/npm/key-del/)

[![NPM](https://nodei.co/npm-dl/key-del.png)](https://nodei.co/npm-dl/key-del/)

## Assumptions
* original object shall not be modified by default
* modified object is returned
* nested keys shall be deleted as well

## Usage
* takes two parameters (object, and keys to delete)
* second parameter is a string (for single key), or array (for multiple keys)

## Installation

`npm install key-del`

## Usage

```javascript

var deleteKey = require('key-del')
var objWithoutOneAttribute = deleteKey({one: 1, two: 2}, "one")
```

## Examples

```javascript

var deleteKey = require('key-del')

var originalObject = {
	one: 1,
	two: 2,
	three: {
	  nestedOne: 3,
	  nestedTwo: 4
	}
}

var result = deleteKey(originalObject, ['one', 'nestedOne'])

console.log(result)
// {two: 2, three: {nestedTwo: 4}}

// Delete nested key by full path
var objectToDeleteKeyFrom = { one: 1, two: 2, nested: {two: 2, three: 3}}
var keyToDelete = 'nested.two'
var result = delKey(objectToDeleteKeyFrom, keyToDelete)
console.log(result)
// { one: 1, two: 2, nested: {three: 3}}
```

## Options

To delete attribue from the original object, set `copy` parameter to false (its true by default)

```javascript

deleteKey(originalObject, 'one', {copy: false})
console.log(originalObject)
// original object is modified
// { one: 1, two: 2, three: { nestedOne: 3, nestedTwo: 4 } }

```

## Licence

The MIT License (MIT)

Copyright (c) 2015, Andrei Karpushonak aka @miktam, http://avrora.io