1.4.0 / 2014-11-20
------------------
- added `jshint.json` and `benchmark/` to `.npmignore`
- added `isProbablePrime()` https://github.com/cryptocoinjs/bigi/pull/16

1.3.0 / 2014-08-27
------------------
* added method `byteLength()`, #13

1.2.1 / 2014-07-03
-----------------
* added duck-typed BigInteger.isBigInteger(), #12

1.2.0 / 2014-06-10
------------------
* removed semicolons, cleanup, added basic tests, jshint [Daniel Cousens](https://github.com/cryptocoinjs/bigi/pull/9)
* added TravisCI
* added Coveralls
* added Testling

1.1.0 / 2014-05-13
-------------------
* extend test data and include DER integers
* fix *ByteArrayUnsigned implementation
* add tests for *ByteArrayUnsigned
* rename toByteArraySigned -> toDERInteger
* rework toBuffer/toHex for performance

1.0.0 / 2014-04-28
------------------
* added methods `toBuffer()`, `fromBuffer()`, `toHex()`, `fromHex()`, #1
* removed `bower.json` and `component.json` support
* http://cryptocoinjs.com/modules/misc/bigi/
* renamed test file

0.2.0 / 2013-12-07
------------------
* renamed from `cryptocoin-bigint` to `bigi`

0.1.0 / 2013-11-20
------------------
* removed AMD support

0.0.1 / 2013-11-03
------------------
* initial release
