// Copyright 2015 Andrei Karpushonak

"use strict";

var DOT_SEPARATOR = ".";
var _ = require('lodash');

var deleteKeysFromObject = function (object, keys, options) {
  var keysToDelete;

  // deep copy by default
  var isDeep = true;

  // to preserve backwards compatibility, assume that only explicit options means shallow copy
  if (_.isUndefined(options) == false) {
    if (_.isBoolean(options.copy)) {
      isDeep = options.copy;
    }
  }

  // do not modify original object if copy is true (default)
  var finalObject;
  if (isDeep) {
    finalObject = _.clone(object, isDeep);
  } else {
    finalObject = object;
  }

  if (typeof finalObject === 'undefined') {
    throw new Error('undefined is not a valid object.');
  }
  if (arguments.length < 2) {
    throw new Error("provide at least two parameters: object and list of keys");
  }

  // collect keys
  if (Array.isArray(keys)) {
    keysToDelete = keys;
  } else {
    keysToDelete = [keys];
  }

  keysToDelete.forEach(function(elem) {
    for(var prop in finalObject) {
      if(finalObject.hasOwnProperty(prop)) {
        if (elem === prop) {
          // simple key to delete
          delete finalObject[prop];
        } else if (elem.indexOf(DOT_SEPARATOR) != -1) {
          // dealing with nested key provided by full path
          var parts = elem.split(DOT_SEPARATOR);
          var pathWithoutLastEl = _.dropRight(parts, 1);
          var lastAttribute = _.drop(parts, 1);
          var nestedObjectRef = finalObject[pathWithoutLastEl];
          delete nestedObjectRef[lastAttribute];
        } else {
          // check nested attributes, if it's an object, and not array (thank you, Javascript)
          if (_.isObject(finalObject[prop]) && !_.isArray(finalObject[prop])) {
            finalObject[prop] = deleteKeysFromObject(finalObject[prop], keysToDelete, options);
          }
        }
      }

    }
  });

  return finalObject;

};

module.exports = deleteKeysFromObject;

