"use strict";

/*global describe, it, before, beforeEach, after, afterEach */

var assert = require('assert');
var should = require('should');
var delKey = require('../index');
var _      = require('lodash');

describe('del-key', function() {
  it('shall check parameters', function() {
    try {
      delKey();
      assert.fail('Empty parameter shall throw the error');
    } catch (ex) {
      ex.should.be.not.equal(null);
    }
  });

  it('shall provide two parameters', function() {
    var param = { a: 1};
    try {
      delKey(param);
      assert.fail('One parameter shall not be enough');
    } catch (ex) {
      ex.should.be.not.equal(null);
    }
  });

  it('shall accept single string as a key to delete', function() {
    var objectToDeleteKeyFrom = { a: 1, b: 2};
    var keyToDelete = 'a';
    var result = delKey(objectToDeleteKeyFrom, keyToDelete);

    assert.equal(result.a, undefined, 'key shall be deleted');
  });

  it('shall not modify the original object', function() {
    var objectToDeleteKeyFrom = { a: 1, b: 2};
    var keyToDelete = 'a';
    var result = delKey(objectToDeleteKeyFrom, keyToDelete);

    assert.equal(result.a, undefined, 'key shall be deleted');
    assert.equal(objectToDeleteKeyFrom.a, 1, 'original key shall not be deleted');
  });

  it('shall accept an array as a keys to delete', function() {
    var objectToDeleteKeyFrom = { key1: 1, key2: 2, key3: 3};
    var keyToDelete = ['key1', 'key2'];
    var result = delKey(objectToDeleteKeyFrom, keyToDelete);

    assert.equal(result.key1, undefined, 'key shall be deleted');
    assert.equal(result.key2, undefined, 'key shall be deleted');
    assert.equal(result.key3, 3, 'key shall be kept');
  });

  it('shall delete nested keys', function() {
    var objectToDeleteKeyFrom = { b: 2, c: {d: 5, e: 6}};
    var keyToDelete = ['b', 'd'];
    var result = delKey(objectToDeleteKeyFrom, keyToDelete);

    assert.equal(result.b, undefined, 'first level key shall be deleted');
    assert.equal(result.c.d, undefined, 'nested key shall be deleted');
    assert.equal(result.c.e, 6, 'key shall be kept');
  });

  it('shall delete deeply nested keys', function() {
    var objectToDeleteKeyFrom = { b: 2, c: {d: 5, e: {f: 6, g: 8}}};
    var keyToDelete = ['b', 'f'];
    var result = delKey(objectToDeleteKeyFrom, keyToDelete);

    assert.equal(result.b, undefined, 'first level key shall be deleted');
    assert.equal(result.c.e.f, undefined, 'nested key shall be deleted');
    assert.equal(result.c.e.g, 8, 'key shall be kept');
  });

  it('shall handle deep copy', function() {

    var objectToDeleteKeyFrom = [{ "one": "first", "two": "second"}];
    var keyToDelete = ['does not exist'];
    var deep = delKey(objectToDeleteKeyFrom, keyToDelete, {copy:true});
    assert.equal(objectToDeleteKeyFrom[0] === deep[0], false, 'objects are cloned explicetely');
    var deepByDefault = delKey(objectToDeleteKeyFrom, keyToDelete);
    assert.equal(objectToDeleteKeyFrom[0] === deepByDefault[0], false, 'object are cloned by default');
  });

  it('shall handle shallow copy', function() {

    var objectToDeleteKeyFrom = { "one": "first", "two": "second"};
    var shallow = delKey(objectToDeleteKeyFrom, 'two', {copy:false});
    assert.equal(objectToDeleteKeyFrom === shallow, true, 'should be the same object');
  });

  it('shall delete nested keys by full path', function() {
    var objectToDeleteKeyFrom = { one: 1, two: 2, nested: {two: 2, three: 3}};
    var keyToDelete = 'nested.two';
    var result = delKey(objectToDeleteKeyFrom, keyToDelete);

    assert.equal(result.one, 1, 'attribute one should be untouched');
    assert.equal(result.two, 2, 'attribute three should be untouched');
    assert.equal(result.nested.two, undefined, 'nested three should be deleted');
    assert.equal(result.nested.three, 3, 'nested three should be untouched');
  });

});