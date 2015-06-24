# bs58check

[![Build Status](https://travis-ci.org/dcousens/bs58check.png?branch=master)](https://travis-ci.org/dcousens/bs58check)
[![Coverage Status](https://coveralls.io/repos/dcousens/bs58check/badge.png)](https://coveralls.io/r/dcousens/bs58check)

[![NPM](https://nodei.co/npm/bs58check.png)](https://nodei.co/npm/bs58check/)

A straight forward implementation of base58check extending upon bs58.


## Example

``` javascript
var bs58check = require('bs58check')

var decoded = bs58check.decode('5Kd3NBUAdUnhyzenEwVLy9pBKxSwXvE9FMPyR4UKZvpe6E3AgLr')

console.log(decoded)
// => <Buffer 80 ed db dc 11 68 f1 da ea db d3 e4 4c 1e 3f 8f 5a 28 4c 20 29 f7 8a d2 6a f9 85 83 a4 99 de 5b 19>

console.log(bs58check.encode(decoded))
// => 5Kd3NBUAdUnhyzenEwVLy9pBKxSwXvE9FMPyR4UKZvpe6E3AgLr
```


## License

This library is free and open-source software released under the MIT license.
