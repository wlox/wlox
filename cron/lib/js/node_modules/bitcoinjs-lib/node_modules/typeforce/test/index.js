/* global describe, it */

var assert = require('assert')
var typeForce = require('../')

function CustomType () { return 'ensure non-greedy match'.toUpperCase() }
var CUSTOM_TYPES = {
  'Buffer': new Buffer(1),
  'CustomType': new CustomType(),
  'Function': function () {}
}

var fixtures = require('./fixtures')

describe('typeForce', function () {
  fixtures.valid.forEach(function (f) {
    var actualValue = f.custom ? CUSTOM_TYPES[f.custom] : f.value

    it('passes for ' + JSON.stringify(f.type) + ' with ' + (f.custom ? f.custom : JSON.stringify(f.value)), function () {
      typeForce(f.type, actualValue)
    })
  })

  fixtures.invalid.forEach(function (f) {
    var actualValue = f.custom ? CUSTOM_TYPES[f.custom] : f.value

    it('fails for ' + JSON.stringify(f.type) + ' with ' + (f.custom ? f.custom : JSON.stringify(f.value)), function () {
      assert.throws(function () {
        typeForce(f.type, actualValue)
      }, new RegExp(f.exception))
    })
  })
})
