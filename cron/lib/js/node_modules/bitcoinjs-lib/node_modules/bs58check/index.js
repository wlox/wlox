'use strict';

var base58 = require('bs58')
var createHash = require('create-hash')

// SHA256(SHA256(buffer))
function sha256x2(buffer) {
  buffer = createHash('sha256').update(buffer).digest()
  return createHash('sha256').update(buffer).digest()
}

// Encode a buffer as a base58-check encoded string
function encode(payload) {
  var checksum = sha256x2(payload).slice(0, 4)

  return base58.encode(Buffer.concat([
    payload,
    checksum
  ]))
}

// Decode a base58-check encoded string to a buffer
function decode(string) {
  var buffer = new Buffer(base58.decode(string))

  var payload = buffer.slice(0, -4)
  var checksum = buffer.slice(-4)
  var newChecksum = sha256x2(payload).slice(0, 4)

  if (!Array.prototype.every.call(newChecksum, function(x, i) {
    return x === checksum[i]
  })) {
    throw new Error('Invalid checksum')
  }

  return payload
}

module.exports = {
  encode: encode,
  decode: decode
}
