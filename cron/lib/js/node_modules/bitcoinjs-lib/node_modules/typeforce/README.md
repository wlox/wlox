# typeforce

[![build status](https://secure.travis-ci.org/dcousens/typeforce.png)](http://travis-ci.org/dcousens/typeforce)
[![Coverage Status](https://coveralls.io/repos/dcousens/typeforce/badge.png)](https://coveralls.io/r/dcousens/typeforce)
[![Version](http://img.shields.io/npm/v/typeforce.svg)](https://www.npmjs.org/package/typeforce)

Another biased type checking solution for Javascript.

## Examples

``` javascript
var typeforce = require('typeforce')

var unknown = [{ prop: 'foo' }, { prop: 'bar' }, { prop: 2 } ]
typeforce('Array', unknown)
// supported primitives 'Array', 'Boolean', 'Buffer', 'Number', 'Object', 'String'

// array types only support 1 element type
typeforce(['Object'], unknown)

// pop the last element
var element = unknown.pop()

// supports recursive type templating
typeforce({ prop: 'Number' }, element)

// works for array types too (remember, we popped off the non-conforming element)
typeforce([{ prop: 'String' }], unknown)

// will also pass as an Array is an Object
typeforce('Object', unknown)

// THROWS 'TypeError: Expected Number, got Array [object Object],[object Object]'
typeforce('Number', unknown)
```

## License

This library is free and open-source software released under the MIT license.

