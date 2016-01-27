var assert = require('assert')
var base58check = require('../')

var fixtures = require('./fixtures')

describe('bs58check', function() {
  describe('decode', function() {
    fixtures.valid.forEach(function(f) {
      it('can decode ' + f.string, function() {
        var actual = base58check.decode(f.string).toString('hex')

        assert.equal(actual, f.payload)
      })
    })

    fixtures.invalid.forEach(function(f) {
      it('throws on ' + f, function() {
        assert.throws(function() {
          base58check.decode(f)
        }, /Invalid checksum/)
      })
    })
  })

  describe('encode', function() {
    fixtures.valid.forEach(function(f) {
      it('can encode ' + f.string, function() {
        var actual = base58check.encode(new Buffer(f.payload, 'hex'))

        assert.strictEqual(actual, f.string)
      })
    })
  })
})

