require=(function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
'use strict';

module.exports = function union(init) {
  if (!Array.isArray(init)) {
    throw new TypeError('arr-union expects the first argument to be an array.');
  }

  var len = arguments.length;
  var i = 0;

  while (++i < len) {
    var arg = arguments[i];
    if (!arg) continue;

    if (!Array.isArray(arg)) {
      arg = [arg];
    }

    for (var j = 0; j < arg.length; j++) {
      var ele = arg[j];

      if (init.indexOf(ele) >= 0) {
        continue;
      }
      init.push(ele);
    }
  }
  return init;
};

},{}],2:[function(require,module,exports){
'use strict'

exports.byteLength = byteLength
exports.toByteArray = toByteArray
exports.fromByteArray = fromByteArray

var lookup = []
var revLookup = []
var Arr = typeof Uint8Array !== 'undefined' ? Uint8Array : Array

var code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
for (var i = 0, len = code.length; i < len; ++i) {
  lookup[i] = code[i]
  revLookup[code.charCodeAt(i)] = i
}

// Support decoding URL-safe base64 strings, as Node.js does.
// See: https://en.wikipedia.org/wiki/Base64#URL_applications
revLookup['-'.charCodeAt(0)] = 62
revLookup['_'.charCodeAt(0)] = 63

function getLens (b64) {
  var len = b64.length

  if (len % 4 > 0) {
    throw new Error('Invalid string. Length must be a multiple of 4')
  }

  // Trim off extra bytes after placeholder bytes are found
  // See: https://github.com/beatgammit/base64-js/issues/42
  var validLen = b64.indexOf('=')
  if (validLen === -1) validLen = len

  var placeHoldersLen = validLen === len
    ? 0
    : 4 - (validLen % 4)

  return [validLen, placeHoldersLen]
}

// base64 is 4/3 + up to two characters of the original data
function byteLength (b64) {
  var lens = getLens(b64)
  var validLen = lens[0]
  var placeHoldersLen = lens[1]
  return ((validLen + placeHoldersLen) * 3 / 4) - placeHoldersLen
}

function _byteLength (b64, validLen, placeHoldersLen) {
  return ((validLen + placeHoldersLen) * 3 / 4) - placeHoldersLen
}

function toByteArray (b64) {
  var tmp
  var lens = getLens(b64)
  var validLen = lens[0]
  var placeHoldersLen = lens[1]

  var arr = new Arr(_byteLength(b64, validLen, placeHoldersLen))

  var curByte = 0

  // if there are placeholders, only get up to the last complete 4 chars
  var len = placeHoldersLen > 0
    ? validLen - 4
    : validLen

  for (var i = 0; i < len; i += 4) {
    tmp =
      (revLookup[b64.charCodeAt(i)] << 18) |
      (revLookup[b64.charCodeAt(i + 1)] << 12) |
      (revLookup[b64.charCodeAt(i + 2)] << 6) |
      revLookup[b64.charCodeAt(i + 3)]
    arr[curByte++] = (tmp >> 16) & 0xFF
    arr[curByte++] = (tmp >> 8) & 0xFF
    arr[curByte++] = tmp & 0xFF
  }

  if (placeHoldersLen === 2) {
    tmp =
      (revLookup[b64.charCodeAt(i)] << 2) |
      (revLookup[b64.charCodeAt(i + 1)] >> 4)
    arr[curByte++] = tmp & 0xFF
  }

  if (placeHoldersLen === 1) {
    tmp =
      (revLookup[b64.charCodeAt(i)] << 10) |
      (revLookup[b64.charCodeAt(i + 1)] << 4) |
      (revLookup[b64.charCodeAt(i + 2)] >> 2)
    arr[curByte++] = (tmp >> 8) & 0xFF
    arr[curByte++] = tmp & 0xFF
  }

  return arr
}

function tripletToBase64 (num) {
  return lookup[num >> 18 & 0x3F] +
    lookup[num >> 12 & 0x3F] +
    lookup[num >> 6 & 0x3F] +
    lookup[num & 0x3F]
}

function encodeChunk (uint8, start, end) {
  var tmp
  var output = []
  for (var i = start; i < end; i += 3) {
    tmp =
      ((uint8[i] << 16) & 0xFF0000) +
      ((uint8[i + 1] << 8) & 0xFF00) +
      (uint8[i + 2] & 0xFF)
    output.push(tripletToBase64(tmp))
  }
  return output.join('')
}

function fromByteArray (uint8) {
  var tmp
  var len = uint8.length
  var extraBytes = len % 3 // if we have 1 byte left, pad 2 bytes
  var parts = []
  var maxChunkLength = 16383 // must be multiple of 3

  // go through the array every three bytes, we'll deal with trailing stuff later
  for (var i = 0, len2 = len - extraBytes; i < len2; i += maxChunkLength) {
    parts.push(encodeChunk(
      uint8, i, (i + maxChunkLength) > len2 ? len2 : (i + maxChunkLength)
    ))
  }

  // pad the end with zeros, but make sure to not forget the extra bytes
  if (extraBytes === 1) {
    tmp = uint8[len - 1]
    parts.push(
      lookup[tmp >> 2] +
      lookup[(tmp << 4) & 0x3F] +
      '=='
    )
  } else if (extraBytes === 2) {
    tmp = (uint8[len - 2] << 8) + uint8[len - 1]
    parts.push(
      lookup[tmp >> 10] +
      lookup[(tmp >> 4) & 0x3F] +
      lookup[(tmp << 2) & 0x3F] +
      '='
    )
  }

  return parts.join('')
}

},{}],3:[function(require,module,exports){
(function (Buffer){
/*!
 * The buffer module from node.js, for the browser.
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */
/* eslint-disable no-proto */

'use strict'

var base64 = require('base64-js')
var ieee754 = require('ieee754')

exports.Buffer = Buffer
exports.SlowBuffer = SlowBuffer
exports.INSPECT_MAX_BYTES = 50

var K_MAX_LENGTH = 0x7fffffff
exports.kMaxLength = K_MAX_LENGTH

/**
 * If `Buffer.TYPED_ARRAY_SUPPORT`:
 *   === true    Use Uint8Array implementation (fastest)
 *   === false   Print warning and recommend using `buffer` v4.x which has an Object
 *               implementation (most compatible, even IE6)
 *
 * Browsers that support typed arrays are IE 10+, Firefox 4+, Chrome 7+, Safari 5.1+,
 * Opera 11.6+, iOS 4.2+.
 *
 * We report that the browser does not support typed arrays if the are not subclassable
 * using __proto__. Firefox 4-29 lacks support for adding new properties to `Uint8Array`
 * (See: https://bugzilla.mozilla.org/show_bug.cgi?id=695438). IE 10 lacks support
 * for __proto__ and has a buggy typed array implementation.
 */
Buffer.TYPED_ARRAY_SUPPORT = typedArraySupport()

if (!Buffer.TYPED_ARRAY_SUPPORT && typeof console !== 'undefined' &&
    typeof console.error === 'function') {
  console.error(
    'This browser lacks typed array (Uint8Array) support which is required by ' +
    '`buffer` v5.x. Use `buffer` v4.x if you require old browser support.'
  )
}

function typedArraySupport () {
  // Can typed array instances can be augmented?
  try {
    var arr = new Uint8Array(1)
    arr.__proto__ = { __proto__: Uint8Array.prototype, foo: function () { return 42 } }
    return arr.foo() === 42
  } catch (e) {
    return false
  }
}

Object.defineProperty(Buffer.prototype, 'parent', {
  enumerable: true,
  get: function () {
    if (!Buffer.isBuffer(this)) return undefined
    return this.buffer
  }
})

Object.defineProperty(Buffer.prototype, 'offset', {
  enumerable: true,
  get: function () {
    if (!Buffer.isBuffer(this)) return undefined
    return this.byteOffset
  }
})

function createBuffer (length) {
  if (length > K_MAX_LENGTH) {
    throw new RangeError('The value "' + length + '" is invalid for option "size"')
  }
  // Return an augmented `Uint8Array` instance
  var buf = new Uint8Array(length)
  buf.__proto__ = Buffer.prototype
  return buf
}

/**
 * The Buffer constructor returns instances of `Uint8Array` that have their
 * prototype changed to `Buffer.prototype`. Furthermore, `Buffer` is a subclass of
 * `Uint8Array`, so the returned instances will have all the node `Buffer` methods
 * and the `Uint8Array` methods. Square bracket notation works as expected -- it
 * returns a single octet.
 *
 * The `Uint8Array` prototype remains unmodified.
 */

function Buffer (arg, encodingOrOffset, length) {
  // Common case.
  if (typeof arg === 'number') {
    if (typeof encodingOrOffset === 'string') {
      throw new TypeError(
        'The "string" argument must be of type string. Received type number'
      )
    }
    return allocUnsafe(arg)
  }
  return from(arg, encodingOrOffset, length)
}

// Fix subarray() in ES2016. See: https://github.com/feross/buffer/pull/97
if (typeof Symbol !== 'undefined' && Symbol.species != null &&
    Buffer[Symbol.species] === Buffer) {
  Object.defineProperty(Buffer, Symbol.species, {
    value: null,
    configurable: true,
    enumerable: false,
    writable: false
  })
}

Buffer.poolSize = 8192 // not used by this implementation

function from (value, encodingOrOffset, length) {
  if (typeof value === 'string') {
    return fromString(value, encodingOrOffset)
  }

  if (ArrayBuffer.isView(value)) {
    return fromArrayLike(value)
  }

  if (value == null) {
    throw TypeError(
      'The first argument must be one of type string, Buffer, ArrayBuffer, Array, ' +
      'or Array-like Object. Received type ' + (typeof value)
    )
  }

  if (isInstance(value, ArrayBuffer) ||
      (value && isInstance(value.buffer, ArrayBuffer))) {
    return fromArrayBuffer(value, encodingOrOffset, length)
  }

  if (typeof value === 'number') {
    throw new TypeError(
      'The "value" argument must not be of type number. Received type number'
    )
  }

  var valueOf = value.valueOf && value.valueOf()
  if (valueOf != null && valueOf !== value) {
    return Buffer.from(valueOf, encodingOrOffset, length)
  }

  var b = fromObject(value)
  if (b) return b

  if (typeof Symbol !== 'undefined' && Symbol.toPrimitive != null &&
      typeof value[Symbol.toPrimitive] === 'function') {
    return Buffer.from(
      value[Symbol.toPrimitive]('string'), encodingOrOffset, length
    )
  }

  throw new TypeError(
    'The first argument must be one of type string, Buffer, ArrayBuffer, Array, ' +
    'or Array-like Object. Received type ' + (typeof value)
  )
}

/**
 * Functionally equivalent to Buffer(arg, encoding) but throws a TypeError
 * if value is a number.
 * Buffer.from(str[, encoding])
 * Buffer.from(array)
 * Buffer.from(buffer)
 * Buffer.from(arrayBuffer[, byteOffset[, length]])
 **/
Buffer.from = function (value, encodingOrOffset, length) {
  return from(value, encodingOrOffset, length)
}

// Note: Change prototype *after* Buffer.from is defined to workaround Chrome bug:
// https://github.com/feross/buffer/pull/148
Buffer.prototype.__proto__ = Uint8Array.prototype
Buffer.__proto__ = Uint8Array

function assertSize (size) {
  if (typeof size !== 'number') {
    throw new TypeError('"size" argument must be of type number')
  } else if (size < 0) {
    throw new RangeError('The value "' + size + '" is invalid for option "size"')
  }
}

function alloc (size, fill, encoding) {
  assertSize(size)
  if (size <= 0) {
    return createBuffer(size)
  }
  if (fill !== undefined) {
    // Only pay attention to encoding if it's a string. This
    // prevents accidentally sending in a number that would
    // be interpretted as a start offset.
    return typeof encoding === 'string'
      ? createBuffer(size).fill(fill, encoding)
      : createBuffer(size).fill(fill)
  }
  return createBuffer(size)
}

/**
 * Creates a new filled Buffer instance.
 * alloc(size[, fill[, encoding]])
 **/
Buffer.alloc = function (size, fill, encoding) {
  return alloc(size, fill, encoding)
}

function allocUnsafe (size) {
  assertSize(size)
  return createBuffer(size < 0 ? 0 : checked(size) | 0)
}

/**
 * Equivalent to Buffer(num), by default creates a non-zero-filled Buffer instance.
 * */
Buffer.allocUnsafe = function (size) {
  return allocUnsafe(size)
}
/**
 * Equivalent to SlowBuffer(num), by default creates a non-zero-filled Buffer instance.
 */
Buffer.allocUnsafeSlow = function (size) {
  return allocUnsafe(size)
}

function fromString (string, encoding) {
  if (typeof encoding !== 'string' || encoding === '') {
    encoding = 'utf8'
  }

  if (!Buffer.isEncoding(encoding)) {
    throw new TypeError('Unknown encoding: ' + encoding)
  }

  var length = byteLength(string, encoding) | 0
  var buf = createBuffer(length)

  var actual = buf.write(string, encoding)

  if (actual !== length) {
    // Writing a hex string, for example, that contains invalid characters will
    // cause everything after the first invalid character to be ignored. (e.g.
    // 'abxxcd' will be treated as 'ab')
    buf = buf.slice(0, actual)
  }

  return buf
}

function fromArrayLike (array) {
  var length = array.length < 0 ? 0 : checked(array.length) | 0
  var buf = createBuffer(length)
  for (var i = 0; i < length; i += 1) {
    buf[i] = array[i] & 255
  }
  return buf
}

function fromArrayBuffer (array, byteOffset, length) {
  if (byteOffset < 0 || array.byteLength < byteOffset) {
    throw new RangeError('"offset" is outside of buffer bounds')
  }

  if (array.byteLength < byteOffset + (length || 0)) {
    throw new RangeError('"length" is outside of buffer bounds')
  }

  var buf
  if (byteOffset === undefined && length === undefined) {
    buf = new Uint8Array(array)
  } else if (length === undefined) {
    buf = new Uint8Array(array, byteOffset)
  } else {
    buf = new Uint8Array(array, byteOffset, length)
  }

  // Return an augmented `Uint8Array` instance
  buf.__proto__ = Buffer.prototype
  return buf
}

function fromObject (obj) {
  if (Buffer.isBuffer(obj)) {
    var len = checked(obj.length) | 0
    var buf = createBuffer(len)

    if (buf.length === 0) {
      return buf
    }

    obj.copy(buf, 0, 0, len)
    return buf
  }

  if (obj.length !== undefined) {
    if (typeof obj.length !== 'number' || numberIsNaN(obj.length)) {
      return createBuffer(0)
    }
    return fromArrayLike(obj)
  }

  if (obj.type === 'Buffer' && Array.isArray(obj.data)) {
    return fromArrayLike(obj.data)
  }
}

function checked (length) {
  // Note: cannot use `length < K_MAX_LENGTH` here because that fails when
  // length is NaN (which is otherwise coerced to zero.)
  if (length >= K_MAX_LENGTH) {
    throw new RangeError('Attempt to allocate Buffer larger than maximum ' +
                         'size: 0x' + K_MAX_LENGTH.toString(16) + ' bytes')
  }
  return length | 0
}

function SlowBuffer (length) {
  if (+length != length) { // eslint-disable-line eqeqeq
    length = 0
  }
  return Buffer.alloc(+length)
}

Buffer.isBuffer = function isBuffer (b) {
  return b != null && b._isBuffer === true &&
    b !== Buffer.prototype // so Buffer.isBuffer(Buffer.prototype) will be false
}

Buffer.compare = function compare (a, b) {
  if (isInstance(a, Uint8Array)) a = Buffer.from(a, a.offset, a.byteLength)
  if (isInstance(b, Uint8Array)) b = Buffer.from(b, b.offset, b.byteLength)
  if (!Buffer.isBuffer(a) || !Buffer.isBuffer(b)) {
    throw new TypeError(
      'The "buf1", "buf2" arguments must be one of type Buffer or Uint8Array'
    )
  }

  if (a === b) return 0

  var x = a.length
  var y = b.length

  for (var i = 0, len = Math.min(x, y); i < len; ++i) {
    if (a[i] !== b[i]) {
      x = a[i]
      y = b[i]
      break
    }
  }

  if (x < y) return -1
  if (y < x) return 1
  return 0
}

Buffer.isEncoding = function isEncoding (encoding) {
  switch (String(encoding).toLowerCase()) {
    case 'hex':
    case 'utf8':
    case 'utf-8':
    case 'ascii':
    case 'latin1':
    case 'binary':
    case 'base64':
    case 'ucs2':
    case 'ucs-2':
    case 'utf16le':
    case 'utf-16le':
      return true
    default:
      return false
  }
}

Buffer.concat = function concat (list, length) {
  if (!Array.isArray(list)) {
    throw new TypeError('"list" argument must be an Array of Buffers')
  }

  if (list.length === 0) {
    return Buffer.alloc(0)
  }

  var i
  if (length === undefined) {
    length = 0
    for (i = 0; i < list.length; ++i) {
      length += list[i].length
    }
  }

  var buffer = Buffer.allocUnsafe(length)
  var pos = 0
  for (i = 0; i < list.length; ++i) {
    var buf = list[i]
    if (isInstance(buf, Uint8Array)) {
      buf = Buffer.from(buf)
    }
    if (!Buffer.isBuffer(buf)) {
      throw new TypeError('"list" argument must be an Array of Buffers')
    }
    buf.copy(buffer, pos)
    pos += buf.length
  }
  return buffer
}

function byteLength (string, encoding) {
  if (Buffer.isBuffer(string)) {
    return string.length
  }
  if (ArrayBuffer.isView(string) || isInstance(string, ArrayBuffer)) {
    return string.byteLength
  }
  if (typeof string !== 'string') {
    throw new TypeError(
      'The "string" argument must be one of type string, Buffer, or ArrayBuffer. ' +
      'Received type ' + typeof string
    )
  }

  var len = string.length
  var mustMatch = (arguments.length > 2 && arguments[2] === true)
  if (!mustMatch && len === 0) return 0

  // Use a for loop to avoid recursion
  var loweredCase = false
  for (;;) {
    switch (encoding) {
      case 'ascii':
      case 'latin1':
      case 'binary':
        return len
      case 'utf8':
      case 'utf-8':
        return utf8ToBytes(string).length
      case 'ucs2':
      case 'ucs-2':
      case 'utf16le':
      case 'utf-16le':
        return len * 2
      case 'hex':
        return len >>> 1
      case 'base64':
        return base64ToBytes(string).length
      default:
        if (loweredCase) {
          return mustMatch ? -1 : utf8ToBytes(string).length // assume utf8
        }
        encoding = ('' + encoding).toLowerCase()
        loweredCase = true
    }
  }
}
Buffer.byteLength = byteLength

function slowToString (encoding, start, end) {
  var loweredCase = false

  // No need to verify that "this.length <= MAX_UINT32" since it's a read-only
  // property of a typed array.

  // This behaves neither like String nor Uint8Array in that we set start/end
  // to their upper/lower bounds if the value passed is out of range.
  // undefined is handled specially as per ECMA-262 6th Edition,
  // Section 13.3.3.7 Runtime Semantics: KeyedBindingInitialization.
  if (start === undefined || start < 0) {
    start = 0
  }
  // Return early if start > this.length. Done here to prevent potential uint32
  // coercion fail below.
  if (start > this.length) {
    return ''
  }

  if (end === undefined || end > this.length) {
    end = this.length
  }

  if (end <= 0) {
    return ''
  }

  // Force coersion to uint32. This will also coerce falsey/NaN values to 0.
  end >>>= 0
  start >>>= 0

  if (end <= start) {
    return ''
  }

  if (!encoding) encoding = 'utf8'

  while (true) {
    switch (encoding) {
      case 'hex':
        return hexSlice(this, start, end)

      case 'utf8':
      case 'utf-8':
        return utf8Slice(this, start, end)

      case 'ascii':
        return asciiSlice(this, start, end)

      case 'latin1':
      case 'binary':
        return latin1Slice(this, start, end)

      case 'base64':
        return base64Slice(this, start, end)

      case 'ucs2':
      case 'ucs-2':
      case 'utf16le':
      case 'utf-16le':
        return utf16leSlice(this, start, end)

      default:
        if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
        encoding = (encoding + '').toLowerCase()
        loweredCase = true
    }
  }
}

// This property is used by `Buffer.isBuffer` (and the `is-buffer` npm package)
// to detect a Buffer instance. It's not possible to use `instanceof Buffer`
// reliably in a browserify context because there could be multiple different
// copies of the 'buffer' package in use. This method works even for Buffer
// instances that were created from another copy of the `buffer` package.
// See: https://github.com/feross/buffer/issues/154
Buffer.prototype._isBuffer = true

function swap (b, n, m) {
  var i = b[n]
  b[n] = b[m]
  b[m] = i
}

Buffer.prototype.swap16 = function swap16 () {
  var len = this.length
  if (len % 2 !== 0) {
    throw new RangeError('Buffer size must be a multiple of 16-bits')
  }
  for (var i = 0; i < len; i += 2) {
    swap(this, i, i + 1)
  }
  return this
}

Buffer.prototype.swap32 = function swap32 () {
  var len = this.length
  if (len % 4 !== 0) {
    throw new RangeError('Buffer size must be a multiple of 32-bits')
  }
  for (var i = 0; i < len; i += 4) {
    swap(this, i, i + 3)
    swap(this, i + 1, i + 2)
  }
  return this
}

Buffer.prototype.swap64 = function swap64 () {
  var len = this.length
  if (len % 8 !== 0) {
    throw new RangeError('Buffer size must be a multiple of 64-bits')
  }
  for (var i = 0; i < len; i += 8) {
    swap(this, i, i + 7)
    swap(this, i + 1, i + 6)
    swap(this, i + 2, i + 5)
    swap(this, i + 3, i + 4)
  }
  return this
}

Buffer.prototype.toString = function toString () {
  var length = this.length
  if (length === 0) return ''
  if (arguments.length === 0) return utf8Slice(this, 0, length)
  return slowToString.apply(this, arguments)
}

Buffer.prototype.toLocaleString = Buffer.prototype.toString

Buffer.prototype.equals = function equals (b) {
  if (!Buffer.isBuffer(b)) throw new TypeError('Argument must be a Buffer')
  if (this === b) return true
  return Buffer.compare(this, b) === 0
}

Buffer.prototype.inspect = function inspect () {
  var str = ''
  var max = exports.INSPECT_MAX_BYTES
  str = this.toString('hex', 0, max).replace(/(.{2})/g, '$1 ').trim()
  if (this.length > max) str += ' ... '
  return '<Buffer ' + str + '>'
}

Buffer.prototype.compare = function compare (target, start, end, thisStart, thisEnd) {
  if (isInstance(target, Uint8Array)) {
    target = Buffer.from(target, target.offset, target.byteLength)
  }
  if (!Buffer.isBuffer(target)) {
    throw new TypeError(
      'The "target" argument must be one of type Buffer or Uint8Array. ' +
      'Received type ' + (typeof target)
    )
  }

  if (start === undefined) {
    start = 0
  }
  if (end === undefined) {
    end = target ? target.length : 0
  }
  if (thisStart === undefined) {
    thisStart = 0
  }
  if (thisEnd === undefined) {
    thisEnd = this.length
  }

  if (start < 0 || end > target.length || thisStart < 0 || thisEnd > this.length) {
    throw new RangeError('out of range index')
  }

  if (thisStart >= thisEnd && start >= end) {
    return 0
  }
  if (thisStart >= thisEnd) {
    return -1
  }
  if (start >= end) {
    return 1
  }

  start >>>= 0
  end >>>= 0
  thisStart >>>= 0
  thisEnd >>>= 0

  if (this === target) return 0

  var x = thisEnd - thisStart
  var y = end - start
  var len = Math.min(x, y)

  var thisCopy = this.slice(thisStart, thisEnd)
  var targetCopy = target.slice(start, end)

  for (var i = 0; i < len; ++i) {
    if (thisCopy[i] !== targetCopy[i]) {
      x = thisCopy[i]
      y = targetCopy[i]
      break
    }
  }

  if (x < y) return -1
  if (y < x) return 1
  return 0
}

// Finds either the first index of `val` in `buffer` at offset >= `byteOffset`,
// OR the last index of `val` in `buffer` at offset <= `byteOffset`.
//
// Arguments:
// - buffer - a Buffer to search
// - val - a string, Buffer, or number
// - byteOffset - an index into `buffer`; will be clamped to an int32
// - encoding - an optional encoding, relevant is val is a string
// - dir - true for indexOf, false for lastIndexOf
function bidirectionalIndexOf (buffer, val, byteOffset, encoding, dir) {
  // Empty buffer means no match
  if (buffer.length === 0) return -1

  // Normalize byteOffset
  if (typeof byteOffset === 'string') {
    encoding = byteOffset
    byteOffset = 0
  } else if (byteOffset > 0x7fffffff) {
    byteOffset = 0x7fffffff
  } else if (byteOffset < -0x80000000) {
    byteOffset = -0x80000000
  }
  byteOffset = +byteOffset // Coerce to Number.
  if (numberIsNaN(byteOffset)) {
    // byteOffset: it it's undefined, null, NaN, "foo", etc, search whole buffer
    byteOffset = dir ? 0 : (buffer.length - 1)
  }

  // Normalize byteOffset: negative offsets start from the end of the buffer
  if (byteOffset < 0) byteOffset = buffer.length + byteOffset
  if (byteOffset >= buffer.length) {
    if (dir) return -1
    else byteOffset = buffer.length - 1
  } else if (byteOffset < 0) {
    if (dir) byteOffset = 0
    else return -1
  }

  // Normalize val
  if (typeof val === 'string') {
    val = Buffer.from(val, encoding)
  }

  // Finally, search either indexOf (if dir is true) or lastIndexOf
  if (Buffer.isBuffer(val)) {
    // Special case: looking for empty string/buffer always fails
    if (val.length === 0) {
      return -1
    }
    return arrayIndexOf(buffer, val, byteOffset, encoding, dir)
  } else if (typeof val === 'number') {
    val = val & 0xFF // Search for a byte value [0-255]
    if (typeof Uint8Array.prototype.indexOf === 'function') {
      if (dir) {
        return Uint8Array.prototype.indexOf.call(buffer, val, byteOffset)
      } else {
        return Uint8Array.prototype.lastIndexOf.call(buffer, val, byteOffset)
      }
    }
    return arrayIndexOf(buffer, [ val ], byteOffset, encoding, dir)
  }

  throw new TypeError('val must be string, number or Buffer')
}

function arrayIndexOf (arr, val, byteOffset, encoding, dir) {
  var indexSize = 1
  var arrLength = arr.length
  var valLength = val.length

  if (encoding !== undefined) {
    encoding = String(encoding).toLowerCase()
    if (encoding === 'ucs2' || encoding === 'ucs-2' ||
        encoding === 'utf16le' || encoding === 'utf-16le') {
      if (arr.length < 2 || val.length < 2) {
        return -1
      }
      indexSize = 2
      arrLength /= 2
      valLength /= 2
      byteOffset /= 2
    }
  }

  function read (buf, i) {
    if (indexSize === 1) {
      return buf[i]
    } else {
      return buf.readUInt16BE(i * indexSize)
    }
  }

  var i
  if (dir) {
    var foundIndex = -1
    for (i = byteOffset; i < arrLength; i++) {
      if (read(arr, i) === read(val, foundIndex === -1 ? 0 : i - foundIndex)) {
        if (foundIndex === -1) foundIndex = i
        if (i - foundIndex + 1 === valLength) return foundIndex * indexSize
      } else {
        if (foundIndex !== -1) i -= i - foundIndex
        foundIndex = -1
      }
    }
  } else {
    if (byteOffset + valLength > arrLength) byteOffset = arrLength - valLength
    for (i = byteOffset; i >= 0; i--) {
      var found = true
      for (var j = 0; j < valLength; j++) {
        if (read(arr, i + j) !== read(val, j)) {
          found = false
          break
        }
      }
      if (found) return i
    }
  }

  return -1
}

Buffer.prototype.includes = function includes (val, byteOffset, encoding) {
  return this.indexOf(val, byteOffset, encoding) !== -1
}

Buffer.prototype.indexOf = function indexOf (val, byteOffset, encoding) {
  return bidirectionalIndexOf(this, val, byteOffset, encoding, true)
}

Buffer.prototype.lastIndexOf = function lastIndexOf (val, byteOffset, encoding) {
  return bidirectionalIndexOf(this, val, byteOffset, encoding, false)
}

function hexWrite (buf, string, offset, length) {
  offset = Number(offset) || 0
  var remaining = buf.length - offset
  if (!length) {
    length = remaining
  } else {
    length = Number(length)
    if (length > remaining) {
      length = remaining
    }
  }

  var strLen = string.length

  if (length > strLen / 2) {
    length = strLen / 2
  }
  for (var i = 0; i < length; ++i) {
    var parsed = parseInt(string.substr(i * 2, 2), 16)
    if (numberIsNaN(parsed)) return i
    buf[offset + i] = parsed
  }
  return i
}

function utf8Write (buf, string, offset, length) {
  return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length)
}

function asciiWrite (buf, string, offset, length) {
  return blitBuffer(asciiToBytes(string), buf, offset, length)
}

function latin1Write (buf, string, offset, length) {
  return asciiWrite(buf, string, offset, length)
}

function base64Write (buf, string, offset, length) {
  return blitBuffer(base64ToBytes(string), buf, offset, length)
}

function ucs2Write (buf, string, offset, length) {
  return blitBuffer(utf16leToBytes(string, buf.length - offset), buf, offset, length)
}

Buffer.prototype.write = function write (string, offset, length, encoding) {
  // Buffer#write(string)
  if (offset === undefined) {
    encoding = 'utf8'
    length = this.length
    offset = 0
  // Buffer#write(string, encoding)
  } else if (length === undefined && typeof offset === 'string') {
    encoding = offset
    length = this.length
    offset = 0
  // Buffer#write(string, offset[, length][, encoding])
  } else if (isFinite(offset)) {
    offset = offset >>> 0
    if (isFinite(length)) {
      length = length >>> 0
      if (encoding === undefined) encoding = 'utf8'
    } else {
      encoding = length
      length = undefined
    }
  } else {
    throw new Error(
      'Buffer.write(string, encoding, offset[, length]) is no longer supported'
    )
  }

  var remaining = this.length - offset
  if (length === undefined || length > remaining) length = remaining

  if ((string.length > 0 && (length < 0 || offset < 0)) || offset > this.length) {
    throw new RangeError('Attempt to write outside buffer bounds')
  }

  if (!encoding) encoding = 'utf8'

  var loweredCase = false
  for (;;) {
    switch (encoding) {
      case 'hex':
        return hexWrite(this, string, offset, length)

      case 'utf8':
      case 'utf-8':
        return utf8Write(this, string, offset, length)

      case 'ascii':
        return asciiWrite(this, string, offset, length)

      case 'latin1':
      case 'binary':
        return latin1Write(this, string, offset, length)

      case 'base64':
        // Warning: maxLength not taken into account in base64Write
        return base64Write(this, string, offset, length)

      case 'ucs2':
      case 'ucs-2':
      case 'utf16le':
      case 'utf-16le':
        return ucs2Write(this, string, offset, length)

      default:
        if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
        encoding = ('' + encoding).toLowerCase()
        loweredCase = true
    }
  }
}

Buffer.prototype.toJSON = function toJSON () {
  return {
    type: 'Buffer',
    data: Array.prototype.slice.call(this._arr || this, 0)
  }
}

function base64Slice (buf, start, end) {
  if (start === 0 && end === buf.length) {
    return base64.fromByteArray(buf)
  } else {
    return base64.fromByteArray(buf.slice(start, end))
  }
}

function utf8Slice (buf, start, end) {
  end = Math.min(buf.length, end)
  var res = []

  var i = start
  while (i < end) {
    var firstByte = buf[i]
    var codePoint = null
    var bytesPerSequence = (firstByte > 0xEF) ? 4
      : (firstByte > 0xDF) ? 3
        : (firstByte > 0xBF) ? 2
          : 1

    if (i + bytesPerSequence <= end) {
      var secondByte, thirdByte, fourthByte, tempCodePoint

      switch (bytesPerSequence) {
        case 1:
          if (firstByte < 0x80) {
            codePoint = firstByte
          }
          break
        case 2:
          secondByte = buf[i + 1]
          if ((secondByte & 0xC0) === 0x80) {
            tempCodePoint = (firstByte & 0x1F) << 0x6 | (secondByte & 0x3F)
            if (tempCodePoint > 0x7F) {
              codePoint = tempCodePoint
            }
          }
          break
        case 3:
          secondByte = buf[i + 1]
          thirdByte = buf[i + 2]
          if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80) {
            tempCodePoint = (firstByte & 0xF) << 0xC | (secondByte & 0x3F) << 0x6 | (thirdByte & 0x3F)
            if (tempCodePoint > 0x7FF && (tempCodePoint < 0xD800 || tempCodePoint > 0xDFFF)) {
              codePoint = tempCodePoint
            }
          }
          break
        case 4:
          secondByte = buf[i + 1]
          thirdByte = buf[i + 2]
          fourthByte = buf[i + 3]
          if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80 && (fourthByte & 0xC0) === 0x80) {
            tempCodePoint = (firstByte & 0xF) << 0x12 | (secondByte & 0x3F) << 0xC | (thirdByte & 0x3F) << 0x6 | (fourthByte & 0x3F)
            if (tempCodePoint > 0xFFFF && tempCodePoint < 0x110000) {
              codePoint = tempCodePoint
            }
          }
      }
    }

    if (codePoint === null) {
      // we did not generate a valid codePoint so insert a
      // replacement char (U+FFFD) and advance only 1 byte
      codePoint = 0xFFFD
      bytesPerSequence = 1
    } else if (codePoint > 0xFFFF) {
      // encode to utf16 (surrogate pair dance)
      codePoint -= 0x10000
      res.push(codePoint >>> 10 & 0x3FF | 0xD800)
      codePoint = 0xDC00 | codePoint & 0x3FF
    }

    res.push(codePoint)
    i += bytesPerSequence
  }

  return decodeCodePointsArray(res)
}

// Based on http://stackoverflow.com/a/22747272/680742, the browser with
// the lowest limit is Chrome, with 0x10000 args.
// We go 1 magnitude less, for safety
var MAX_ARGUMENTS_LENGTH = 0x1000

function decodeCodePointsArray (codePoints) {
  var len = codePoints.length
  if (len <= MAX_ARGUMENTS_LENGTH) {
    return String.fromCharCode.apply(String, codePoints) // avoid extra slice()
  }

  // Decode in chunks to avoid "call stack size exceeded".
  var res = ''
  var i = 0
  while (i < len) {
    res += String.fromCharCode.apply(
      String,
      codePoints.slice(i, i += MAX_ARGUMENTS_LENGTH)
    )
  }
  return res
}

function asciiSlice (buf, start, end) {
  var ret = ''
  end = Math.min(buf.length, end)

  for (var i = start; i < end; ++i) {
    ret += String.fromCharCode(buf[i] & 0x7F)
  }
  return ret
}

function latin1Slice (buf, start, end) {
  var ret = ''
  end = Math.min(buf.length, end)

  for (var i = start; i < end; ++i) {
    ret += String.fromCharCode(buf[i])
  }
  return ret
}

function hexSlice (buf, start, end) {
  var len = buf.length

  if (!start || start < 0) start = 0
  if (!end || end < 0 || end > len) end = len

  var out = ''
  for (var i = start; i < end; ++i) {
    out += toHex(buf[i])
  }
  return out
}

function utf16leSlice (buf, start, end) {
  var bytes = buf.slice(start, end)
  var res = ''
  for (var i = 0; i < bytes.length; i += 2) {
    res += String.fromCharCode(bytes[i] + (bytes[i + 1] * 256))
  }
  return res
}

Buffer.prototype.slice = function slice (start, end) {
  var len = this.length
  start = ~~start
  end = end === undefined ? len : ~~end

  if (start < 0) {
    start += len
    if (start < 0) start = 0
  } else if (start > len) {
    start = len
  }

  if (end < 0) {
    end += len
    if (end < 0) end = 0
  } else if (end > len) {
    end = len
  }

  if (end < start) end = start

  var newBuf = this.subarray(start, end)
  // Return an augmented `Uint8Array` instance
  newBuf.__proto__ = Buffer.prototype
  return newBuf
}

/*
 * Need to make sure that buffer isn't trying to write out of bounds.
 */
function checkOffset (offset, ext, length) {
  if ((offset % 1) !== 0 || offset < 0) throw new RangeError('offset is not uint')
  if (offset + ext > length) throw new RangeError('Trying to access beyond buffer length')
}

Buffer.prototype.readUIntLE = function readUIntLE (offset, byteLength, noAssert) {
  offset = offset >>> 0
  byteLength = byteLength >>> 0
  if (!noAssert) checkOffset(offset, byteLength, this.length)

  var val = this[offset]
  var mul = 1
  var i = 0
  while (++i < byteLength && (mul *= 0x100)) {
    val += this[offset + i] * mul
  }

  return val
}

Buffer.prototype.readUIntBE = function readUIntBE (offset, byteLength, noAssert) {
  offset = offset >>> 0
  byteLength = byteLength >>> 0
  if (!noAssert) {
    checkOffset(offset, byteLength, this.length)
  }

  var val = this[offset + --byteLength]
  var mul = 1
  while (byteLength > 0 && (mul *= 0x100)) {
    val += this[offset + --byteLength] * mul
  }

  return val
}

Buffer.prototype.readUInt8 = function readUInt8 (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 1, this.length)
  return this[offset]
}

Buffer.prototype.readUInt16LE = function readUInt16LE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 2, this.length)
  return this[offset] | (this[offset + 1] << 8)
}

Buffer.prototype.readUInt16BE = function readUInt16BE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 2, this.length)
  return (this[offset] << 8) | this[offset + 1]
}

Buffer.prototype.readUInt32LE = function readUInt32LE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 4, this.length)

  return ((this[offset]) |
      (this[offset + 1] << 8) |
      (this[offset + 2] << 16)) +
      (this[offset + 3] * 0x1000000)
}

Buffer.prototype.readUInt32BE = function readUInt32BE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 4, this.length)

  return (this[offset] * 0x1000000) +
    ((this[offset + 1] << 16) |
    (this[offset + 2] << 8) |
    this[offset + 3])
}

Buffer.prototype.readIntLE = function readIntLE (offset, byteLength, noAssert) {
  offset = offset >>> 0
  byteLength = byteLength >>> 0
  if (!noAssert) checkOffset(offset, byteLength, this.length)

  var val = this[offset]
  var mul = 1
  var i = 0
  while (++i < byteLength && (mul *= 0x100)) {
    val += this[offset + i] * mul
  }
  mul *= 0x80

  if (val >= mul) val -= Math.pow(2, 8 * byteLength)

  return val
}

Buffer.prototype.readIntBE = function readIntBE (offset, byteLength, noAssert) {
  offset = offset >>> 0
  byteLength = byteLength >>> 0
  if (!noAssert) checkOffset(offset, byteLength, this.length)

  var i = byteLength
  var mul = 1
  var val = this[offset + --i]
  while (i > 0 && (mul *= 0x100)) {
    val += this[offset + --i] * mul
  }
  mul *= 0x80

  if (val >= mul) val -= Math.pow(2, 8 * byteLength)

  return val
}

Buffer.prototype.readInt8 = function readInt8 (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 1, this.length)
  if (!(this[offset] & 0x80)) return (this[offset])
  return ((0xff - this[offset] + 1) * -1)
}

Buffer.prototype.readInt16LE = function readInt16LE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 2, this.length)
  var val = this[offset] | (this[offset + 1] << 8)
  return (val & 0x8000) ? val | 0xFFFF0000 : val
}

Buffer.prototype.readInt16BE = function readInt16BE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 2, this.length)
  var val = this[offset + 1] | (this[offset] << 8)
  return (val & 0x8000) ? val | 0xFFFF0000 : val
}

Buffer.prototype.readInt32LE = function readInt32LE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 4, this.length)

  return (this[offset]) |
    (this[offset + 1] << 8) |
    (this[offset + 2] << 16) |
    (this[offset + 3] << 24)
}

Buffer.prototype.readInt32BE = function readInt32BE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 4, this.length)

  return (this[offset] << 24) |
    (this[offset + 1] << 16) |
    (this[offset + 2] << 8) |
    (this[offset + 3])
}

Buffer.prototype.readFloatLE = function readFloatLE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 4, this.length)
  return ieee754.read(this, offset, true, 23, 4)
}

Buffer.prototype.readFloatBE = function readFloatBE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 4, this.length)
  return ieee754.read(this, offset, false, 23, 4)
}

Buffer.prototype.readDoubleLE = function readDoubleLE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 8, this.length)
  return ieee754.read(this, offset, true, 52, 8)
}

Buffer.prototype.readDoubleBE = function readDoubleBE (offset, noAssert) {
  offset = offset >>> 0
  if (!noAssert) checkOffset(offset, 8, this.length)
  return ieee754.read(this, offset, false, 52, 8)
}

function checkInt (buf, value, offset, ext, max, min) {
  if (!Buffer.isBuffer(buf)) throw new TypeError('"buffer" argument must be a Buffer instance')
  if (value > max || value < min) throw new RangeError('"value" argument is out of bounds')
  if (offset + ext > buf.length) throw new RangeError('Index out of range')
}

Buffer.prototype.writeUIntLE = function writeUIntLE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset >>> 0
  byteLength = byteLength >>> 0
  if (!noAssert) {
    var maxBytes = Math.pow(2, 8 * byteLength) - 1
    checkInt(this, value, offset, byteLength, maxBytes, 0)
  }

  var mul = 1
  var i = 0
  this[offset] = value & 0xFF
  while (++i < byteLength && (mul *= 0x100)) {
    this[offset + i] = (value / mul) & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeUIntBE = function writeUIntBE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset >>> 0
  byteLength = byteLength >>> 0
  if (!noAssert) {
    var maxBytes = Math.pow(2, 8 * byteLength) - 1
    checkInt(this, value, offset, byteLength, maxBytes, 0)
  }

  var i = byteLength - 1
  var mul = 1
  this[offset + i] = value & 0xFF
  while (--i >= 0 && (mul *= 0x100)) {
    this[offset + i] = (value / mul) & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeUInt8 = function writeUInt8 (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 1, 0xff, 0)
  this[offset] = (value & 0xff)
  return offset + 1
}

Buffer.prototype.writeUInt16LE = function writeUInt16LE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0)
  this[offset] = (value & 0xff)
  this[offset + 1] = (value >>> 8)
  return offset + 2
}

Buffer.prototype.writeUInt16BE = function writeUInt16BE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0)
  this[offset] = (value >>> 8)
  this[offset + 1] = (value & 0xff)
  return offset + 2
}

Buffer.prototype.writeUInt32LE = function writeUInt32LE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0)
  this[offset + 3] = (value >>> 24)
  this[offset + 2] = (value >>> 16)
  this[offset + 1] = (value >>> 8)
  this[offset] = (value & 0xff)
  return offset + 4
}

Buffer.prototype.writeUInt32BE = function writeUInt32BE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0)
  this[offset] = (value >>> 24)
  this[offset + 1] = (value >>> 16)
  this[offset + 2] = (value >>> 8)
  this[offset + 3] = (value & 0xff)
  return offset + 4
}

Buffer.prototype.writeIntLE = function writeIntLE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) {
    var limit = Math.pow(2, (8 * byteLength) - 1)

    checkInt(this, value, offset, byteLength, limit - 1, -limit)
  }

  var i = 0
  var mul = 1
  var sub = 0
  this[offset] = value & 0xFF
  while (++i < byteLength && (mul *= 0x100)) {
    if (value < 0 && sub === 0 && this[offset + i - 1] !== 0) {
      sub = 1
    }
    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeIntBE = function writeIntBE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) {
    var limit = Math.pow(2, (8 * byteLength) - 1)

    checkInt(this, value, offset, byteLength, limit - 1, -limit)
  }

  var i = byteLength - 1
  var mul = 1
  var sub = 0
  this[offset + i] = value & 0xFF
  while (--i >= 0 && (mul *= 0x100)) {
    if (value < 0 && sub === 0 && this[offset + i + 1] !== 0) {
      sub = 1
    }
    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeInt8 = function writeInt8 (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 1, 0x7f, -0x80)
  if (value < 0) value = 0xff + value + 1
  this[offset] = (value & 0xff)
  return offset + 1
}

Buffer.prototype.writeInt16LE = function writeInt16LE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000)
  this[offset] = (value & 0xff)
  this[offset + 1] = (value >>> 8)
  return offset + 2
}

Buffer.prototype.writeInt16BE = function writeInt16BE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000)
  this[offset] = (value >>> 8)
  this[offset + 1] = (value & 0xff)
  return offset + 2
}

Buffer.prototype.writeInt32LE = function writeInt32LE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000)
  this[offset] = (value & 0xff)
  this[offset + 1] = (value >>> 8)
  this[offset + 2] = (value >>> 16)
  this[offset + 3] = (value >>> 24)
  return offset + 4
}

Buffer.prototype.writeInt32BE = function writeInt32BE (value, offset, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000)
  if (value < 0) value = 0xffffffff + value + 1
  this[offset] = (value >>> 24)
  this[offset + 1] = (value >>> 16)
  this[offset + 2] = (value >>> 8)
  this[offset + 3] = (value & 0xff)
  return offset + 4
}

function checkIEEE754 (buf, value, offset, ext, max, min) {
  if (offset + ext > buf.length) throw new RangeError('Index out of range')
  if (offset < 0) throw new RangeError('Index out of range')
}

function writeFloat (buf, value, offset, littleEndian, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) {
    checkIEEE754(buf, value, offset, 4, 3.4028234663852886e+38, -3.4028234663852886e+38)
  }
  ieee754.write(buf, value, offset, littleEndian, 23, 4)
  return offset + 4
}

Buffer.prototype.writeFloatLE = function writeFloatLE (value, offset, noAssert) {
  return writeFloat(this, value, offset, true, noAssert)
}

Buffer.prototype.writeFloatBE = function writeFloatBE (value, offset, noAssert) {
  return writeFloat(this, value, offset, false, noAssert)
}

function writeDouble (buf, value, offset, littleEndian, noAssert) {
  value = +value
  offset = offset >>> 0
  if (!noAssert) {
    checkIEEE754(buf, value, offset, 8, 1.7976931348623157E+308, -1.7976931348623157E+308)
  }
  ieee754.write(buf, value, offset, littleEndian, 52, 8)
  return offset + 8
}

Buffer.prototype.writeDoubleLE = function writeDoubleLE (value, offset, noAssert) {
  return writeDouble(this, value, offset, true, noAssert)
}

Buffer.prototype.writeDoubleBE = function writeDoubleBE (value, offset, noAssert) {
  return writeDouble(this, value, offset, false, noAssert)
}

// copy(targetBuffer, targetStart=0, sourceStart=0, sourceEnd=buffer.length)
Buffer.prototype.copy = function copy (target, targetStart, start, end) {
  if (!Buffer.isBuffer(target)) throw new TypeError('argument should be a Buffer')
  if (!start) start = 0
  if (!end && end !== 0) end = this.length
  if (targetStart >= target.length) targetStart = target.length
  if (!targetStart) targetStart = 0
  if (end > 0 && end < start) end = start

  // Copy 0 bytes; we're done
  if (end === start) return 0
  if (target.length === 0 || this.length === 0) return 0

  // Fatal error conditions
  if (targetStart < 0) {
    throw new RangeError('targetStart out of bounds')
  }
  if (start < 0 || start >= this.length) throw new RangeError('Index out of range')
  if (end < 0) throw new RangeError('sourceEnd out of bounds')

  // Are we oob?
  if (end > this.length) end = this.length
  if (target.length - targetStart < end - start) {
    end = target.length - targetStart + start
  }

  var len = end - start

  if (this === target && typeof Uint8Array.prototype.copyWithin === 'function') {
    // Use built-in when available, missing from IE11
    this.copyWithin(targetStart, start, end)
  } else if (this === target && start < targetStart && targetStart < end) {
    // descending copy from end
    for (var i = len - 1; i >= 0; --i) {
      target[i + targetStart] = this[i + start]
    }
  } else {
    Uint8Array.prototype.set.call(
      target,
      this.subarray(start, end),
      targetStart
    )
  }

  return len
}

// Usage:
//    buffer.fill(number[, offset[, end]])
//    buffer.fill(buffer[, offset[, end]])
//    buffer.fill(string[, offset[, end]][, encoding])
Buffer.prototype.fill = function fill (val, start, end, encoding) {
  // Handle string cases:
  if (typeof val === 'string') {
    if (typeof start === 'string') {
      encoding = start
      start = 0
      end = this.length
    } else if (typeof end === 'string') {
      encoding = end
      end = this.length
    }
    if (encoding !== undefined && typeof encoding !== 'string') {
      throw new TypeError('encoding must be a string')
    }
    if (typeof encoding === 'string' && !Buffer.isEncoding(encoding)) {
      throw new TypeError('Unknown encoding: ' + encoding)
    }
    if (val.length === 1) {
      var code = val.charCodeAt(0)
      if ((encoding === 'utf8' && code < 128) ||
          encoding === 'latin1') {
        // Fast path: If `val` fits into a single byte, use that numeric value.
        val = code
      }
    }
  } else if (typeof val === 'number') {
    val = val & 255
  }

  // Invalid ranges are not set to a default, so can range check early.
  if (start < 0 || this.length < start || this.length < end) {
    throw new RangeError('Out of range index')
  }

  if (end <= start) {
    return this
  }

  start = start >>> 0
  end = end === undefined ? this.length : end >>> 0

  if (!val) val = 0

  var i
  if (typeof val === 'number') {
    for (i = start; i < end; ++i) {
      this[i] = val
    }
  } else {
    var bytes = Buffer.isBuffer(val)
      ? val
      : Buffer.from(val, encoding)
    var len = bytes.length
    if (len === 0) {
      throw new TypeError('The value "' + val +
        '" is invalid for argument "value"')
    }
    for (i = 0; i < end - start; ++i) {
      this[i + start] = bytes[i % len]
    }
  }

  return this
}

// HELPER FUNCTIONS
// ================

var INVALID_BASE64_RE = /[^+/0-9A-Za-z-_]/g

function base64clean (str) {
  // Node takes equal signs as end of the Base64 encoding
  str = str.split('=')[0]
  // Node strips out invalid characters like \n and \t from the string, base64-js does not
  str = str.trim().replace(INVALID_BASE64_RE, '')
  // Node converts strings with length < 2 to ''
  if (str.length < 2) return ''
  // Node allows for non-padded base64 strings (missing trailing ===), base64-js does not
  while (str.length % 4 !== 0) {
    str = str + '='
  }
  return str
}

function toHex (n) {
  if (n < 16) return '0' + n.toString(16)
  return n.toString(16)
}

function utf8ToBytes (string, units) {
  units = units || Infinity
  var codePoint
  var length = string.length
  var leadSurrogate = null
  var bytes = []

  for (var i = 0; i < length; ++i) {
    codePoint = string.charCodeAt(i)

    // is surrogate component
    if (codePoint > 0xD7FF && codePoint < 0xE000) {
      // last char was a lead
      if (!leadSurrogate) {
        // no lead yet
        if (codePoint > 0xDBFF) {
          // unexpected trail
          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
          continue
        } else if (i + 1 === length) {
          // unpaired lead
          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
          continue
        }

        // valid lead
        leadSurrogate = codePoint

        continue
      }

      // 2 leads in a row
      if (codePoint < 0xDC00) {
        if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
        leadSurrogate = codePoint
        continue
      }

      // valid surrogate pair
      codePoint = (leadSurrogate - 0xD800 << 10 | codePoint - 0xDC00) + 0x10000
    } else if (leadSurrogate) {
      // valid bmp char, but last char was a lead
      if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
    }

    leadSurrogate = null

    // encode utf8
    if (codePoint < 0x80) {
      if ((units -= 1) < 0) break
      bytes.push(codePoint)
    } else if (codePoint < 0x800) {
      if ((units -= 2) < 0) break
      bytes.push(
        codePoint >> 0x6 | 0xC0,
        codePoint & 0x3F | 0x80
      )
    } else if (codePoint < 0x10000) {
      if ((units -= 3) < 0) break
      bytes.push(
        codePoint >> 0xC | 0xE0,
        codePoint >> 0x6 & 0x3F | 0x80,
        codePoint & 0x3F | 0x80
      )
    } else if (codePoint < 0x110000) {
      if ((units -= 4) < 0) break
      bytes.push(
        codePoint >> 0x12 | 0xF0,
        codePoint >> 0xC & 0x3F | 0x80,
        codePoint >> 0x6 & 0x3F | 0x80,
        codePoint & 0x3F | 0x80
      )
    } else {
      throw new Error('Invalid code point')
    }
  }

  return bytes
}

function asciiToBytes (str) {
  var byteArray = []
  for (var i = 0; i < str.length; ++i) {
    // Node's code seems to be doing this and not & 0x7F..
    byteArray.push(str.charCodeAt(i) & 0xFF)
  }
  return byteArray
}

function utf16leToBytes (str, units) {
  var c, hi, lo
  var byteArray = []
  for (var i = 0; i < str.length; ++i) {
    if ((units -= 2) < 0) break

    c = str.charCodeAt(i)
    hi = c >> 8
    lo = c % 256
    byteArray.push(lo)
    byteArray.push(hi)
  }

  return byteArray
}

function base64ToBytes (str) {
  return base64.toByteArray(base64clean(str))
}

function blitBuffer (src, dst, offset, length) {
  for (var i = 0; i < length; ++i) {
    if ((i + offset >= dst.length) || (i >= src.length)) break
    dst[i + offset] = src[i]
  }
  return i
}

// ArrayBuffer or Uint8Array objects from other contexts (i.e. iframes) do not pass
// the `instanceof` check but they should be treated as of that type.
// See: https://github.com/feross/buffer/issues/166
function isInstance (obj, type) {
  return obj instanceof type ||
    (obj != null && obj.constructor != null && obj.constructor.name != null &&
      obj.constructor.name === type.name)
}
function numberIsNaN (obj) {
  // For IE11 support
  return obj !== obj // eslint-disable-line no-self-compare
}

}).call(this,require("buffer").Buffer)
},{"base64-js":2,"buffer":3,"ieee754":8}],4:[function(require,module,exports){
'use strict';

/**
 * Module dependenices
 */

var utils = require('./utils');

/**
 * Recursively clone native types.
 */

function cloneDeep(val, instanceClone) {
  switch (utils.typeOf(val)) {
    case 'object':
      return cloneObjectDeep(val, instanceClone);
    case 'array':
      return cloneArrayDeep(val, instanceClone);
    default:
      return utils.clone(val);
  }
}

function cloneObjectDeep(obj, instanceClone) {
  if (utils.isObject(obj)) {
    var res = {};
    utils.forOwn(obj, function(obj, key) {
      this[key] = cloneDeep(obj, instanceClone);
    }, res);
    return res;
  } else if (instanceClone) {
    return instanceClone(obj);
  } else {
    return obj;
  }
}

function cloneArrayDeep(arr, instanceClone) {
  var len = arr.length, res = [];
  var i = -1;
  while (++i < len) {
    res[i] = cloneDeep(arr[i], instanceClone);
  }
  return res;
}

/**
 * Expose `cloneDeep`
 */

module.exports = cloneDeep;

},{"./utils":5}],5:[function(require,module,exports){
'use strict';

/**
 * Lazily required module dependencies
 */

var utils = require('lazy-cache')(require);
var fn = require;

require = utils;
require('is-plain-object', 'isObject');
require('shallow-clone', 'clone');
require('kind-of', 'typeOf');
require('for-own');
require = fn;

/**
 * Expose `utils`
 */

module.exports = utils;

},{"for-own":7,"is-plain-object":11,"kind-of":13,"lazy-cache":14,"shallow-clone":25}],6:[function(require,module,exports){
/*!
 * for-in <https://github.com/jonschlinkert/for-in>
 *
 * Copyright (c) 2014-2017, Jon Schlinkert.
 * Released under the MIT License.
 */

'use strict';

module.exports = function forIn(obj, fn, thisArg) {
  for (var key in obj) {
    if (fn.call(thisArg, obj[key], key, obj) === false) {
      break;
    }
  }
};

},{}],7:[function(require,module,exports){
/*!
 * for-own <https://github.com/jonschlinkert/for-own>
 *
 * Copyright (c) 2014-2017, Jon Schlinkert.
 * Released under the MIT License.
 */

'use strict';

var forIn = require('for-in');
var hasOwn = Object.prototype.hasOwnProperty;

module.exports = function forOwn(obj, fn, thisArg) {
  forIn(obj, function(val, key) {
    if (hasOwn.call(obj, key)) {
      return fn.call(thisArg, obj[key], key, obj);
    }
  });
};

},{"for-in":6}],8:[function(require,module,exports){
exports.read = function (buffer, offset, isLE, mLen, nBytes) {
  var e, m
  var eLen = (nBytes * 8) - mLen - 1
  var eMax = (1 << eLen) - 1
  var eBias = eMax >> 1
  var nBits = -7
  var i = isLE ? (nBytes - 1) : 0
  var d = isLE ? -1 : 1
  var s = buffer[offset + i]

  i += d

  e = s & ((1 << (-nBits)) - 1)
  s >>= (-nBits)
  nBits += eLen
  for (; nBits > 0; e = (e * 256) + buffer[offset + i], i += d, nBits -= 8) {}

  m = e & ((1 << (-nBits)) - 1)
  e >>= (-nBits)
  nBits += mLen
  for (; nBits > 0; m = (m * 256) + buffer[offset + i], i += d, nBits -= 8) {}

  if (e === 0) {
    e = 1 - eBias
  } else if (e === eMax) {
    return m ? NaN : ((s ? -1 : 1) * Infinity)
  } else {
    m = m + Math.pow(2, mLen)
    e = e - eBias
  }
  return (s ? -1 : 1) * m * Math.pow(2, e - mLen)
}

exports.write = function (buffer, value, offset, isLE, mLen, nBytes) {
  var e, m, c
  var eLen = (nBytes * 8) - mLen - 1
  var eMax = (1 << eLen) - 1
  var eBias = eMax >> 1
  var rt = (mLen === 23 ? Math.pow(2, -24) - Math.pow(2, -77) : 0)
  var i = isLE ? 0 : (nBytes - 1)
  var d = isLE ? 1 : -1
  var s = value < 0 || (value === 0 && 1 / value < 0) ? 1 : 0

  value = Math.abs(value)

  if (isNaN(value) || value === Infinity) {
    m = isNaN(value) ? 1 : 0
    e = eMax
  } else {
    e = Math.floor(Math.log(value) / Math.LN2)
    if (value * (c = Math.pow(2, -e)) < 1) {
      e--
      c *= 2
    }
    if (e + eBias >= 1) {
      value += rt / c
    } else {
      value += rt * Math.pow(2, 1 - eBias)
    }
    if (value * c >= 2) {
      e++
      c /= 2
    }

    if (e + eBias >= eMax) {
      m = 0
      e = eMax
    } else if (e + eBias >= 1) {
      m = ((value * c) - 1) * Math.pow(2, mLen)
      e = e + eBias
    } else {
      m = value * Math.pow(2, eBias - 1) * Math.pow(2, mLen)
      e = 0
    }
  }

  for (; mLen >= 8; buffer[offset + i] = m & 0xff, i += d, m /= 256, mLen -= 8) {}

  e = (e << mLen) | m
  eLen += mLen
  for (; eLen > 0; buffer[offset + i] = e & 0xff, i += d, e /= 256, eLen -= 8) {}

  buffer[offset + i - d] |= s * 128
}

},{}],9:[function(require,module,exports){
/*!
 * Determine if an object is a Buffer
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */

// The _isBuffer check is for Safari 5-7 support, because it's missing
// Object.prototype.constructor. Remove this eventually
module.exports = function (obj) {
  return obj != null && (isBuffer(obj) || isSlowBuffer(obj) || !!obj._isBuffer)
}

function isBuffer (obj) {
  return !!obj.constructor && typeof obj.constructor.isBuffer === 'function' && obj.constructor.isBuffer(obj)
}

// For Node v0.10 support. Remove this eventually.
function isSlowBuffer (obj) {
  return typeof obj.readFloatLE === 'function' && typeof obj.slice === 'function' && isBuffer(obj.slice(0, 0))
}

},{}],10:[function(require,module,exports){
/*!
 * is-extendable <https://github.com/jonschlinkert/is-extendable>
 *
 * Copyright (c) 2015, Jon Schlinkert.
 * Licensed under the MIT License.
 */

'use strict';

module.exports = function isExtendable(val) {
  return typeof val !== 'undefined' && val !== null
    && (typeof val === 'object' || typeof val === 'function');
};

},{}],11:[function(require,module,exports){
/*!
 * is-plain-object <https://github.com/jonschlinkert/is-plain-object>
 *
 * Copyright (c) 2014-2017, Jon Schlinkert.
 * Released under the MIT License.
 */

'use strict';

var isObject = require('isobject');

function isObjectObject(o) {
  return isObject(o) === true
    && Object.prototype.toString.call(o) === '[object Object]';
}

module.exports = function isPlainObject(o) {
  var ctor,prot;

  if (isObjectObject(o) === false) return false;

  // If has modified constructor
  ctor = o.constructor;
  if (typeof ctor !== 'function') return false;

  // If has modified prototype
  prot = ctor.prototype;
  if (isObjectObject(prot) === false) return false;

  // If constructor does not have an Object-specific method
  if (prot.hasOwnProperty('isPrototypeOf') === false) {
    return false;
  }

  // Most likely a plain Object
  return true;
};

},{"isobject":12}],12:[function(require,module,exports){
/*!
 * isobject <https://github.com/jonschlinkert/isobject>
 *
 * Copyright (c) 2014-2017, Jon Schlinkert.
 * Released under the MIT License.
 */

'use strict';

module.exports = function isObject(val) {
  return val != null && typeof val === 'object' && Array.isArray(val) === false;
};

},{}],13:[function(require,module,exports){
var isBuffer = require('is-buffer');
var toString = Object.prototype.toString;

/**
 * Get the native `typeof` a value.
 *
 * @param  {*} `val`
 * @return {*} Native javascript type
 */

module.exports = function kindOf(val) {
  // primitivies
  if (typeof val === 'undefined') {
    return 'undefined';
  }
  if (val === null) {
    return 'null';
  }
  if (val === true || val === false || val instanceof Boolean) {
    return 'boolean';
  }
  if (typeof val === 'string' || val instanceof String) {
    return 'string';
  }
  if (typeof val === 'number' || val instanceof Number) {
    return 'number';
  }

  // functions
  if (typeof val === 'function' || val instanceof Function) {
    return 'function';
  }

  // array
  if (typeof Array.isArray !== 'undefined' && Array.isArray(val)) {
    return 'array';
  }

  // check for instances of RegExp and Date before calling `toString`
  if (val instanceof RegExp) {
    return 'regexp';
  }
  if (val instanceof Date) {
    return 'date';
  }

  // other objects
  var type = toString.call(val);

  if (type === '[object RegExp]') {
    return 'regexp';
  }
  if (type === '[object Date]') {
    return 'date';
  }
  if (type === '[object Arguments]') {
    return 'arguments';
  }
  if (type === '[object Error]') {
    return 'error';
  }

  // buffer
  if (isBuffer(val)) {
    return 'buffer';
  }

  // es6: Map, WeakMap, Set, WeakSet
  if (type === '[object Set]') {
    return 'set';
  }
  if (type === '[object WeakSet]') {
    return 'weakset';
  }
  if (type === '[object Map]') {
    return 'map';
  }
  if (type === '[object WeakMap]') {
    return 'weakmap';
  }
  if (type === '[object Symbol]') {
    return 'symbol';
  }

  // typed arrays
  if (type === '[object Int8Array]') {
    return 'int8array';
  }
  if (type === '[object Uint8Array]') {
    return 'uint8array';
  }
  if (type === '[object Uint8ClampedArray]') {
    return 'uint8clampedarray';
  }
  if (type === '[object Int16Array]') {
    return 'int16array';
  }
  if (type === '[object Uint16Array]') {
    return 'uint16array';
  }
  if (type === '[object Int32Array]') {
    return 'int32array';
  }
  if (type === '[object Uint32Array]') {
    return 'uint32array';
  }
  if (type === '[object Float32Array]') {
    return 'float32array';
  }
  if (type === '[object Float64Array]') {
    return 'float64array';
  }

  // must be a plain object
  return 'object';
};

},{"is-buffer":9}],14:[function(require,module,exports){
(function (process){
'use strict';

/**
 * Cache results of the first function call to ensure only calling once.
 *
 * ```js
 * var utils = require('lazy-cache')(require);
 * // cache the call to `require('ansi-yellow')`
 * utils('ansi-yellow', 'yellow');
 * // use `ansi-yellow`
 * console.log(utils.yellow('this is yellow'));
 * ```
 *
 * @param  {Function} `fn` Function that will be called only once.
 * @return {Function} Function that can be called to get the cached function
 * @api public
 */

function lazyCache(fn) {
  var cache = {};
  var proxy = function(mod, name) {
    name = name || camelcase(mod);

    // check both boolean and string in case `process.env` cases to string
    if (process.env.UNLAZY === 'true' || process.env.UNLAZY === true || process.env.TRAVIS) {
      cache[name] = fn(mod);
    }

    Object.defineProperty(proxy, name, {
      enumerable: true,
      configurable: true,
      get: getter
    });

    function getter() {
      if (cache.hasOwnProperty(name)) {
        return cache[name];
      }
      return (cache[name] = fn(mod));
    }
    return getter;
  };
  return proxy;
}

/**
 * Used to camelcase the name to be stored on the `lazy` object.
 *
 * @param  {String} `str` String containing `_`, `.`, `-` or whitespace that will be camelcased.
 * @return {String} camelcased string.
 */

function camelcase(str) {
  if (str.length === 1) {
    return str.toLowerCase();
  }
  str = str.replace(/^[\W_]+|[\W_]+$/g, '').toLowerCase();
  return str.replace(/[\W_]+(\w|$)/g, function(_, ch) {
    return ch.toUpperCase();
  });
}

/**
 * Expose `lazyCache`
 */

module.exports = lazyCache;

}).call(this,require('_process'))
},{"_process":18}],15:[function(require,module,exports){
/*!
 * merge-deep <https://github.com/jonschlinkert/merge-deep>
 *
 * Copyright (c) 2014-2015, Jon Schlinkert.
 * Licensed under the MIT License.
 */

'use strict';

var union = require('arr-union');
var clone = require('clone-deep');
var typeOf = require('kind-of');

module.exports = function mergeDeep(orig, objects) {
  if (!isObject(orig) && !Array.isArray(orig)) {
    orig = {};
  }

  var target = clone(orig);
  var len = arguments.length;
  var idx = 0;

  while (++idx < len) {
    var val = arguments[idx];

    if (isObject(val) || Array.isArray(val)) {
      merge(target, val);
    }
  }
  return target;
};

function merge(target, obj) {
  for (var key in obj) {
    if (key === '__proto__' || !hasOwn(obj, key)) {
      continue;
    }

    var oldVal = obj[key];
    var newVal = target[key];

    if (isObject(newVal) && isObject(oldVal)) {
      target[key] = merge(newVal, oldVal);
    } else if (Array.isArray(newVal)) {
      target[key] = union([], newVal, oldVal);
    } else {
      target[key] = clone(oldVal);
    }
  }
  return target;
}

function hasOwn(obj, key) {
  return Object.prototype.hasOwnProperty.call(obj, key);
}

function isObject(val) {
  return typeOf(val) === 'object' || typeOf(val) === 'function';
}

},{"arr-union":1,"clone-deep":4,"kind-of":13}],16:[function(require,module,exports){
'use strict';

var isObject = require('is-extendable');
var forIn = require('for-in');

function mixin(target, objects) {
  if (!isObject(target)) {
    throw new TypeError('mixin-object expects the first argument to be an object.');
  }
  var len = arguments.length, i = 0;
  while (++i < len) {
    var obj = arguments[i];
    if (isObject(obj)) {
      forIn(obj, copy, target);
    }
  }
  return target;
}

/**
 * copy properties from the source object to the
 * target object.
 *
 * @param  {*} `value`
 * @param  {String} `key`
 */

function copy(value, key) {
  this[key] = value;
}

/**
 * Expose `mixin`
 */

module.exports = mixin;
},{"for-in":17,"is-extendable":10}],17:[function(require,module,exports){
arguments[4][6][0].apply(exports,arguments)
},{"dup":6}],18:[function(require,module,exports){
// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };

},{}],19:[function(require,module,exports){
/** This could be further improved still (use Proxy and mock behaviour of functions and their errors) */
const getChromeRuntimeMock = window => {
  const installer = { install() {} }
  return {
    app: {
      isInstalled: false,
      InstallState: {
        DISABLED: 'disabled',
        INSTALLED: 'installed',
        NOT_INSTALLED: 'not_installed'
      },
      RunningState: {
        CANNOT_RUN: 'cannot_run',
        READY_TO_RUN: 'ready_to_run',
        RUNNING: 'running'
      }
    },
    csi() {},
    loadTimes() {},
    webstore: {
      onInstallStageChanged: {},
      onDownloadProgress: {},
      install(url, onSuccess, onFailure) {
        installer.install(url, onSuccess, onFailure)
      }
    },
    runtime: {
      OnInstalledReason: {
        CHROME_UPDATE: 'chrome_update',
        INSTALL: 'install',
        SHARED_MODULE_UPDATE: 'shared_module_update',
        UPDATE: 'update'
      },
      OnRestartRequiredReason: {
        APP_UPDATE: 'app_update',
        OS_UPDATE: 'os_update',
        PERIODIC: 'periodic'
      },
      PlatformArch: {
        ARM: 'arm',
        MIPS: 'mips',
        MIPS64: 'mips64',
        X86_32: 'x86-32',
        X86_64: 'x86-64'
      },
      PlatformNaclArch: {
        ARM: 'arm',
        MIPS: 'mips',
        MIPS64: 'mips64',
        X86_32: 'x86-32',
        X86_64: 'x86-64'
      },
      PlatformOs: {
        ANDROID: 'android',
        CROS: 'cros',
        LINUX: 'linux',
        MAC: 'mac',
        OPENBSD: 'openbsd',
        WIN: 'win'
      },
      RequestUpdateCheckStatus: {
        NO_UPDATE: 'no_update',
        THROTTLED: 'throttled',
        UPDATE_AVAILABLE: 'update_available'
      },
      connect: function() {}.bind(function() {}), // eslint-disable-line
      sendMessage: function() {}.bind(function() {}) // eslint-disable-line
    }
  }
}

module.exports = {
  getChromeRuntimeMock
}

},{}],20:[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Stealth mode: Applies various techniques to make detection of headless puppeteer harder. 💯
 *
 * ### Purpose
 * There are a couple of ways the use of puppeteer can easily be detected by a target website.
 * The addition of `HeadlessChrome` to the user-agent being only the most obvious one.
 *
 * The goal of this plugin is to be the definite companion to puppeteer to avoid
 * detection, applying new techniques as they surface.
 *
 * As this cat & mouse game is in it's infancy and fast-paced the plugin
 * is kept as flexibile as possible, to support quick testing and iterations.
 *
 * ### Modularity
 * This plugin uses `puppeteer-extra`'s dependency system to only require
 * code mods for evasions that have been enabled, to keep things modular and efficient.
 *
 * The `stealth` plugin is a convenience wrapper that requires multiple [evasion techniques](./evasions/)
 * automatically and comes with defaults. You could also bypass the main module and require
 * specific evasion plugins yourself, if you whish to do so (as they're standalone `puppeteer-extra` plugins):
 *
 * ```es6
 * // bypass main module and require a specific stealth plugin directly:
 * puppeteer.use(require('puppeteer-extra-plugin-stealth/evasions/console.debug')())
 * ```
 *
 * ### Contributing
 * PRs are welcome, if you want to add a new evasion technique I suggest you
 * look at the [template](./evasions/_template) to kickstart things.
 *
 * ### Kudos
 * Thanks to [Evan Sangaline](https://intoli.com/blog/not-possible-to-block-chrome-headless/) and [Paul Irish](https://github.com/paulirish/headless-cat-n-mouse) for kickstarting the discussion!
 *
 * ---
 *
 * @todo
 * - white-/blacklist with url globs (make this a generic plugin method?)
 * - dynamic whitelist based on function evaluation
 *
 * @example
 * const puppeteer = require('puppeteer-extra')
 * // Enable stealth plugin with all evasions
 * puppeteer.use(require('puppeteer-extra-plugin-stealth')())
 *
 *
 * ;(async () => {
 *   // Launch the browser in headless mode and set up a page.
 *   const browser = await puppeteer.launch({ args: ['--no-sandbox'], headless: true })
 *   const page = await browser.newPage()
 *
 *   // Navigate to the page that will perform the tests.
 *   const testUrl = 'https://intoli.com/blog/' +
 *     'not-possible-to-block-chrome-headless/chrome-headless-test.html'
 *   await page.goto(testUrl)
 *
 *   // Save a screenshot of the results.
 *   const screenshotPath = '/tmp/headless-test-result.png'
 *   await page.screenshot({path: screenshotPath})
 *   console.log('have a look at the screenshot:', screenshotPath)
 *
 *   await browser.close()
 * })()
 *
 * @param {Object} [opts] - Options
 * @param {Set<string>} [opts.enabledEvasions] - Specify which evasions to use (by default all)
 *
 */
class StealthPlugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth'
  }

  get defaults() {
    const availableEvasions = new Set([
      'chrome.runtime',
      'console.debug',
      'iframe.contentWindow',
      'media.codecs',
      'navigator.languages',
      'navigator.permissions',
      'navigator.plugins',
      'navigator.webdriver',
      'user-agent-override',
      'webgl.vendor',
      'window.outerdimensions'
    ])
    return {
      availableEvasions,
      // Enable all available evasions by default
      enabledEvasions: new Set([...availableEvasions])
    }
  }

  /**
   * Requires evasion techniques dynamically based on configuration.
   *
   * @private
   */
  get dependencies() {
    return new Set(
      [...this.opts.enabledEvasions].map(e => `${this.name}/evasions/${e}`)
    )
  }

  /**
   * Get all available evasions.
   *
   * Please look into the [evasions directory](./evasions/) for an up to date list.
   *
   * @type {Set<string>} - A Set of all available evasions.
   *
   * @example
   * const pluginStealth = require('puppeteer-extra-plugin-stealth')()
   * console.log(pluginStealth.availableEvasions) // => Set { 'user-agent', 'console.debug' }
   * puppeteer.use(pluginStealth)
   */
  get availableEvasions() {
    return this.defaults.availableEvasions
  }

  /**
   * Get all enabled evasions.
   *
   * Enabled evasions can be configured either through `opts` or by modifying this property.
   *
   * @type {Set<string>} - A Set of all enabled evasions.
   *
   * @example
   * // Remove specific evasion from enabled ones dynamically
   * const pluginStealth = require('puppeteer-extra-plugin-stealth')()
   * pluginStealth.enabledEvasions.delete('console.debug')
   * puppeteer.use(pluginStealth)
   */
  get enabledEvasions() {
    return this.opts.enabledEvasions
  }

  /**
   * @private
   */
  set enabledEvasions(evasions) {
    this.opts.enabledEvasions = evasions
  }

  async onBrowser(browser) {
    // Increase event emitter listeners to prevent MaxListenersExceededWarning
    browser.setMaxListeners(30)
  }
}

/**
 * Default export, PuppeteerExtraStealthPlugin
 *
 * @param {Object} [opts] - Options
 * @param {Set<string>} [opts.enabledEvasions] - Specify which evasions to use (by default all)
 */
const defaultExport = opts => new StealthPlugin(opts)
module.exports = defaultExport

// const moduleExport = defaultExport
// moduleExport.StealthPlugin = StealthPlugin
// module.exports = moduleExport

},{"puppeteer-extra-plugin":21}],21:[function(require,module,exports){
(function (process){
/*!
 * puppeteer-extra-plugin v3.1.3 by berstend
 * https://github.com/berstend/puppeteer-extra/tree/master/packages/puppeteer-extra-plugin
 * @license MIT
 */
'use strict';

Object.defineProperty(exports, '__esModule', { value: true });

function _interopDefault (ex) { return (ex && (typeof ex === 'object') && 'default' in ex) ? ex['default'] : ex; }

var debug = _interopDefault(require('debug'));

/** @private */
const merge = require('merge-deep');
/**
 * Base class for `puppeteer-extra` plugins.
 *
 * Provides convenience methods to avoid boilerplate.
 *
 * All common `puppeteer` browser events will be bound to
 * the plugin instance, if a respectively named class member is found.
 *
 * Please refer to the [puppeteer API documentation](https://github.com/GoogleChrome/puppeteer/blob/master/docs/api.md) as well.
 *
 * @example
 * // hello-world-plugin.js
 * const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')
 *
 * class Plugin extends PuppeteerExtraPlugin {
 *   constructor (opts = { }) { super(opts) }
 *
 *   get name () { return 'hello-world' }
 *
 *   async onPageCreated (page) {
 *     this.debug('page created', page.url())
 *     const ua = await page.browser().userAgent()
 *     this.debug('user agent', ua)
 *   }
 * }
 *
 * module.exports = function (pluginConfig) { return new Plugin(pluginConfig) }
 *
 *
 * // foo.js
 * const puppeteer = require('puppeteer-extra')
 * puppeteer.use(require('./hello-world-plugin')())
 *
 * ;(async () => {
 *   const browser = await puppeteer.launch({headless: false})
 *   const page = await browser.newPage()
 *   await page.goto('http://example.com', {waitUntil: 'domcontentloaded'})
 *   await browser.close()
 * })()
 *
 */
class PuppeteerExtraPlugin {
    constructor(opts) {
        this._debugBase = debug(`puppeteer-extra-plugin:base:${this.name}`);
        this._childClassMembers = [];
        this._opts = merge(this.defaults, opts || {});
        this._debugBase('Initialized.');
    }
    /**
     * Plugin name (required).
     *
     * Convention:
     * - Package: `puppeteer-extra-plugin-anonymize-ua`
     * - Name: `anonymize-ua`
     *
     * @example
     * get name () { return 'anonymize-ua' }
     */
    get name() {
        throw new Error('Plugin must override "name"');
    }
    /**
     * Plugin defaults (optional).
     *
     * If defined will be ([deep-](https://github.com/jonschlinkert/merge-deep))merged with the (optional) user supplied options (supplied during plugin instantiation).
     *
     * The result of merging defaults with user supplied options can be accessed through `this.opts`.
     *
     * @see [[opts]]
     *
     * @example
     * get defaults () {
     *   return {
     *     stripHeadless: true,
     *     makeWindows: true,
     *     customFn: null
     *   }
     * }
     *
     * // Users can overwrite plugin defaults during instantiation:
     * puppeteer.use(require('puppeteer-extra-plugin-foobar')({ makeWindows: false }))
     */
    get defaults() {
        return {};
    }
    /**
     * Plugin requirements (optional).
     *
     * Signal certain plugin requirements to the base class and the user.
     *
     * Currently supported:
     * - `launch`
     *   - If the plugin only supports locally created browser instances (no `puppeteer.connect()`),
     *     will output a warning to the user.
     * - `headful`
     *   - If the plugin doesn't work in `headless: true` mode,
     *     will output a warning to the user.
     * - `dataFromPlugins`
     *   - In case the plugin requires data from other plugins.
     *     will enable usage of `this.getDataFromPlugins()`.
     * - `runLast`
     *   - In case the plugin prefers to run after the others.
     *     Useful when the plugin needs data from others.
     *
     * @example
     * get requirements () {
     *   return new Set(['runLast', 'dataFromPlugins'])
     * }
     */
    get requirements() {
        return new Set([]);
    }
    /**
     * Plugin dependencies (optional).
     *
     * Missing plugins will be required() by puppeteer-extra.
     *
     * @example
     * get dependencies () {
     *   return new Set(['user-preferences'])
     * }
     * // Will ensure the 'puppeteer-extra-plugin-user-preferences' plugin is loaded.
     */
    get dependencies() {
        return new Set([]);
    }
    /**
     * Plugin data (optional).
     *
     * Plugins can expose data (an array of objects), which in turn can be consumed by other plugins,
     * that list the `dataFromPlugins` requirement (by using `this.getDataFromPlugins()`).
     *
     * Convention: `[ {name: 'Any name', value: 'Any value'} ]`
     *
     * @see [[getDataFromPlugins]]
     *
     * @example
     * // plugin1.js
     * get data () {
     *   return [
     *     {
     *       name: 'userPreferences',
     *       value: { foo: 'bar' }
     *     },
     *     {
     *       name: 'userPreferences',
     *       value: { hello: 'world' }
     *     }
     *   ]
     *
     * // plugin2.js
     * get requirements () { return new Set(['dataFromPlugins']) }
     *
     * async beforeLaunch () {
     *   const prefs = this.getDataFromPlugins('userPreferences').map(d => d.value)
     *   this.debug(prefs) // => [ { foo: 'bar' }, { hello: 'world' } ]
     * }
     */
    get data() {
        return [];
    }
    /**
     * Access the plugin options (usually the `defaults` merged with user defined options)
     *
     * To skip the auto-merging of defaults with user supplied opts don't define a `defaults`
     * property and set the `this._opts` Object in your plugin constructor directly.
     *
     * @see [[defaults]]
     *
     * @example
     * get defaults () { return { foo: "bar" } }
     *
     * async onPageCreated (page) {
     *   this.debug(this.opts.foo) // => bar
     * }
     */
    get opts() {
        return this._opts;
    }
    /**
     *  Convenience debug logger based on the [debug] module.
     *  Will automatically namespace the logging output to the plugin package name.
     *  [debug]: https://www.npmjs.com/package/debug
     *
     *  ```bash
     *  # toggle output using environment variables
     *  DEBUG=puppeteer-extra-plugin:<plugin_name> node foo.js
     *  # to debug all the things:
     *  DEBUG=puppeteer-extra,puppeteer-extra-plugin:* node foo.js
     *  ```
     *
     * @example
     * this.debug('hello world')
     * // will output e.g. 'puppeteer-extra-plugin:anonymize-ua hello world'
     */
    get debug() {
        return debug(`puppeteer-extra-plugin:${this.name}`);
    }
    /**
     * Before a new browser instance is created/launched.
     *
     * Can be used to modify the puppeteer launch options by modifying or returning them.
     *
     * Plugins using this method will be called in sequence to each
     * be able to update the launch options.
     *
     * @example
     * async beforeLaunch (options) {
     *   if (this.opts.flashPluginPath) {
     *     options.args.push(`--ppapi-flash-path=${this.opts.flashPluginPath}`)
     *   }
     * }
     *
     * @param options - Puppeteer launch options
     */
    async beforeLaunch(options) {
        // noop
    }
    /**
     * After the browser has launched.
     *
     * Note: Don't assume that there will only be a single browser instance during the lifecycle of a plugin.
     * It's possible that `pupeeteer.launch` will be  called multiple times and more than one browser created.
     * In order to make the plugins as stateless as possible don't store a reference to the browser instance
     * in the plugin but rather consider alternatives.
     *
     * E.g. when using `onPageCreated` you can get a browser reference by using `page.browser()`.
     *
     * Alternatively you could expose a class method that takes a browser instance as a parameter to work with:
     *
     * ```es6
     * const fancyPlugin = require('puppeteer-extra-plugin-fancy')()
     * puppeteer.use(fancyPlugin)
     * const browser = await puppeteer.launch()
     * await fancyPlugin.killBrowser(browser)
     * ```
     *
     * @param  browser - The `puppeteer` browser instance.
     * @param  opts.options - Puppeteer launch options used.
     *
     * @example
     * async afterLaunch (browser, opts) {
     *   this.debug('browser has been launched', opts.options)
     * }
     */
    async afterLaunch(browser, opts = { options: {} }) {
        // noop
    }
    /**
     * Before connecting to an existing browser instance.
     *
     * Can be used to modify the puppeteer connect options by modifying or returning them.
     *
     * Plugins using this method will be called in sequence to each
     * be able to update the launch options.
     *
     * @param  {Object} options - Puppeteer connect options
     * @return {Object=}
     */
    async beforeConnect(options) {
        // noop
    }
    /**
     * After connecting to an existing browser instance.
     *
     * > Note: Don't assume that there will only be a single browser instance during the lifecycle of a plugin.
     *
     * @param browser - The `puppeteer` browser instance.
     * @param  {Object} opts
     * @param  {Object} opts.options - Puppeteer connect options used.
     *
     */
    async afterConnect(browser, opts = {}) {
        // noop
    }
    /**
     * Called when a browser instance is available.
     *
     * This applies to both `puppeteer.launch()` and `puppeteer.connect()`.
     *
     * Convenience method created for plugins that need access to a browser instance
     * and don't mind if it has been created through `launch` or `connect`.
     *
     * > Note: Don't assume that there will only be a single browser instance during the lifecycle of a plugin.
     *
     * @param browser - The `puppeteer` browser instance.
     */
    async onBrowser(browser, opts) {
        // noop
    }
    /**
     * Called when a target is created, for example when a new page is opened by window.open or browser.newPage.
     *
     * > Note: This includes target creations in incognito browser contexts.
     *
     * > Note: This includes browser instances created through `.launch()` as well as `.connect()`.
     *
     * @param  {Puppeteer.Target} target
     */
    async onTargetCreated(target) {
        // noop
    }
    /**
     * Same as `onTargetCreated` but prefiltered to only contain Pages, for convenience.
     *
     * > Note: This includes page creations in incognito browser contexts.
     *
     * > Note: This includes browser instances created through `.launch()` as well as `.connect()`.
     *
     * @param  {Puppeteer.Target} target
     *
     * @example
     * async onPageCreated (page) {
     *   let ua = await page.browser().userAgent()
     *   if (this.opts.stripHeadless) {
     *     ua = ua.replace('HeadlessChrome/', 'Chrome/')
     *   }
     *   this.debug('new ua', ua)
     *   await page.setUserAgent(ua)
     * }
     */
    async onPageCreated(page) {
        // noop
    }
    /**
     * Called when the url of a target changes.
     *
     * > Note: This includes target changes in incognito browser contexts.
     *
     * > Note: This includes browser instances created through `.launch()` as well as `.connect()`.
     *
     * @param  {Puppeteer.Target} target
     */
    async onTargetChanged(target) {
        // noop
    }
    /**
     * Called when a target is destroyed, for example when a page is closed.
     *
     * > Note: This includes target destructions in incognito browser contexts.
     *
     * > Note: This includes browser instances created through `.launch()` as well as `.connect()`.
     *
     * @param  {Puppeteer.Target} target
     */
    async onTargetDestroyed(target) {
        // noop
    }
    /**
     * Called when Puppeteer gets disconnected from the Chromium instance.
     *
     * This might happen because of one of the following:
     * - Chromium is closed or crashed
     * - The `browser.disconnect` method was called
     */
    async onDisconnected() {
        // noop
    }
    /**
     * **Deprecated:** Since puppeteer v1.6.0 `onDisconnected` has been improved
     * and should be used instead of `onClose`.
     *
     * In puppeteer < v1.6.0 `onDisconnected` was not catching all exit scenarios.
     * In order for plugins to clean up properly (e.g. deleting temporary files)
     * the `onClose` method had been introduced.
     *
     * > Note: Might be called multiple times on exit.
     *
     * > Note: This only includes browser instances created through `.launch()`.
     */
    async onClose() {
        // noop
    }
    /**
     * After the plugin has been registered in `puppeteer-extra`.
     *
     * Normally right after `puppeteer.use(plugin)` is called
     */
    async onPluginRegistered() {
        // noop
    }
    /**
     * Helper method to retrieve `data` objects from other plugins.
     *
     * A plugin needs to state the `dataFromPlugins` requirement
     * in order to use this method. Will be mapped to `puppeteer.getPluginData`.
     *
     * @param name - Filter data by `name` property
     *
     * @see [data]
     * @see [requirements]
     */
    getDataFromPlugins(name) {
        return [];
    }
    /**
     * Will match plugin dependencies against all currently registered plugins.
     * Is being called by `puppeteer-extra` and used to require missing dependencies.
     *
     * @param  {Array<Object>} plugins
     * @return {Set} - list of missing plugin names
     *
     * @private
     */
    _getMissingDependencies(plugins) {
        const pluginNames = new Set(plugins.map((p) => p.name));
        const missing = new Set(Array.from(this.dependencies.values()).filter(x => !pluginNames.has(x)));
        return missing;
    }
    /**
     * Conditionally bind browser/process events to class members.
     * The idea is to reduce event binding boilerplate in plugins.
     *
     * For efficiency we make sure the plugin is using the respective event
     * by checking the child class members before registering the listener.
     *
     * @param  {<Puppeteer.Browser>} browser
     * @param  {Object} opts - Options
     * @param  {string} opts.context - Puppeteer context (launch/connect)
     * @param  {Object} [opts.options] - Puppeteer launch or connect options
     * @param  {Array<string>} [opts.defaultArgs] - The default flags that Chromium will be launched with
     *
     * @private
     */
    async _bindBrowserEvents(browser, opts = {}) {
        if (this._hasChildClassMember('onTargetCreated') ||
            this._hasChildClassMember('onPageCreated')) {
            browser.on('targetcreated', this._onTargetCreated.bind(this));
        }
        if (this._hasChildClassMember('onTargetChanged') && this.onTargetChanged) {
            browser.on('targetchanged', this.onTargetChanged.bind(this));
        }
        if (this._hasChildClassMember('onTargetDestroyed') &&
            this.onTargetDestroyed) {
            browser.on('targetdestroyed', this.onTargetDestroyed.bind(this));
        }
        if (this._hasChildClassMember('onDisconnected') && this.onDisconnected) {
            browser.on('disconnected', this.onDisconnected.bind(this));
        }
        if (opts.context === 'launch' && this._hasChildClassMember('onClose')) {
            // The disconnect event has been improved since puppeteer v1.6.0
            // onClose is being kept mostly for legacy reasons
            if (this.onClose) {
                process.on('exit', this.onClose.bind(this));
                browser.on('disconnected', this.onClose.bind(this));
                if (opts.options.handleSIGINT !== false) {
                    process.on('SIGINT', this.onClose.bind(this));
                }
                if (opts.options.handleSIGTERM !== false) {
                    process.on('SIGTERM', this.onClose.bind(this));
                }
                if (opts.options.handleSIGHUP !== false) {
                    process.on('SIGHUP', this.onClose.bind(this));
                }
            }
        }
        if (opts.context === 'launch' && this.afterLaunch) {
            await this.afterLaunch(browser, opts);
        }
        if (opts.context === 'connect' && this.afterConnect) {
            await this.afterConnect(browser, opts);
        }
        if (this.onBrowser)
            await this.onBrowser(browser, opts);
    }
    /**
     * @private
     */
    async _onTargetCreated(target) {
        if (this.onTargetCreated)
            await this.onTargetCreated(target);
        // Pre filter pages for plugin developers convenience
        if (target.type() === 'page') {
            const page = await target.page();
            if (this.onPageCreated) {
                await this.onPageCreated(page);
            }
        }
    }
    /**
     * @private
     */
    _register(prototype) {
        this._registerChildClassMembers(prototype);
        if (this.onPluginRegistered)
            this.onPluginRegistered();
    }
    /**
     * @private
     */
    _registerChildClassMembers(prototype) {
        this._childClassMembers = Object.getOwnPropertyNames(prototype);
    }
    /**
     * @private
     */
    _hasChildClassMember(name) {
        return !!this._childClassMembers.includes(name);
    }
    /**
     * @private
     */
    get _isPuppeteerExtraPlugin() {
        return true;
    }
}

exports.PuppeteerExtraPlugin = PuppeteerExtraPlugin;


}).call(this,require('_process'))
},{"_process":18,"debug":22,"merge-deep":15}],22:[function(require,module,exports){
(function (process){
/* eslint-env browser */

/**
 * This is the web browser implementation of `debug()`.
 */

exports.log = log;
exports.formatArgs = formatArgs;
exports.save = save;
exports.load = load;
exports.useColors = useColors;
exports.storage = localstorage();

/**
 * Colors.
 */

exports.colors = [
	'#0000CC',
	'#0000FF',
	'#0033CC',
	'#0033FF',
	'#0066CC',
	'#0066FF',
	'#0099CC',
	'#0099FF',
	'#00CC00',
	'#00CC33',
	'#00CC66',
	'#00CC99',
	'#00CCCC',
	'#00CCFF',
	'#3300CC',
	'#3300FF',
	'#3333CC',
	'#3333FF',
	'#3366CC',
	'#3366FF',
	'#3399CC',
	'#3399FF',
	'#33CC00',
	'#33CC33',
	'#33CC66',
	'#33CC99',
	'#33CCCC',
	'#33CCFF',
	'#6600CC',
	'#6600FF',
	'#6633CC',
	'#6633FF',
	'#66CC00',
	'#66CC33',
	'#9900CC',
	'#9900FF',
	'#9933CC',
	'#9933FF',
	'#99CC00',
	'#99CC33',
	'#CC0000',
	'#CC0033',
	'#CC0066',
	'#CC0099',
	'#CC00CC',
	'#CC00FF',
	'#CC3300',
	'#CC3333',
	'#CC3366',
	'#CC3399',
	'#CC33CC',
	'#CC33FF',
	'#CC6600',
	'#CC6633',
	'#CC9900',
	'#CC9933',
	'#CCCC00',
	'#CCCC33',
	'#FF0000',
	'#FF0033',
	'#FF0066',
	'#FF0099',
	'#FF00CC',
	'#FF00FF',
	'#FF3300',
	'#FF3333',
	'#FF3366',
	'#FF3399',
	'#FF33CC',
	'#FF33FF',
	'#FF6600',
	'#FF6633',
	'#FF9900',
	'#FF9933',
	'#FFCC00',
	'#FFCC33'
];

/**
 * Currently only WebKit-based Web Inspectors, Firefox >= v31,
 * and the Firebug extension (any Firefox version) are known
 * to support "%c" CSS customizations.
 *
 * TODO: add a `localStorage` variable to explicitly enable/disable colors
 */

// eslint-disable-next-line complexity
function useColors() {
	// NB: In an Electron preload script, document will be defined but not fully
	// initialized. Since we know we're in Chrome, we'll just detect this case
	// explicitly
	if (typeof window !== 'undefined' && window.process && (window.process.type === 'renderer' || window.process.__nwjs)) {
		return true;
	}

	// Internet Explorer and Edge do not support colors.
	if (typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/(edge|trident)\/(\d+)/)) {
		return false;
	}

	// Is webkit? http://stackoverflow.com/a/16459606/376773
	// document is undefined in react-native: https://github.com/facebook/react-native/pull/1632
	return (typeof document !== 'undefined' && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance) ||
		// Is firebug? http://stackoverflow.com/a/398120/376773
		(typeof window !== 'undefined' && window.console && (window.console.firebug || (window.console.exception && window.console.table))) ||
		// Is firefox >= v31?
		// https://developer.mozilla.org/en-US/docs/Tools/Web_Console#Styling_messages
		(typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31) ||
		// Double check webkit in userAgent just in case we are in a worker
		(typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/applewebkit\/(\d+)/));
}

/**
 * Colorize log arguments if enabled.
 *
 * @api public
 */

function formatArgs(args) {
	args[0] = (this.useColors ? '%c' : '') +
		this.namespace +
		(this.useColors ? ' %c' : ' ') +
		args[0] +
		(this.useColors ? '%c ' : ' ') +
		'+' + module.exports.humanize(this.diff);

	if (!this.useColors) {
		return;
	}

	const c = 'color: ' + this.color;
	args.splice(1, 0, c, 'color: inherit');

	// The final "%c" is somewhat tricky, because there could be other
	// arguments passed either before or after the %c, so we need to
	// figure out the correct index to insert the CSS into
	let index = 0;
	let lastC = 0;
	args[0].replace(/%[a-zA-Z%]/g, match => {
		if (match === '%%') {
			return;
		}
		index++;
		if (match === '%c') {
			// We only are interested in the *last* %c
			// (the user may have provided their own)
			lastC = index;
		}
	});

	args.splice(lastC, 0, c);
}

/**
 * Invokes `console.log()` when available.
 * No-op when `console.log` is not a "function".
 *
 * @api public
 */
function log(...args) {
	// This hackery is required for IE8/9, where
	// the `console.log` function doesn't have 'apply'
	return typeof console === 'object' &&
		console.log &&
		console.log(...args);
}

/**
 * Save `namespaces`.
 *
 * @param {String} namespaces
 * @api private
 */
function save(namespaces) {
	try {
		if (namespaces) {
			exports.storage.setItem('debug', namespaces);
		} else {
			exports.storage.removeItem('debug');
		}
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}
}

/**
 * Load `namespaces`.
 *
 * @return {String} returns the previously persisted debug modes
 * @api private
 */
function load() {
	let r;
	try {
		r = exports.storage.getItem('debug');
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}

	// If debug isn't set in LS, and we're in Electron, try to load $DEBUG
	if (!r && typeof process !== 'undefined' && 'env' in process) {
		r = process.env.DEBUG;
	}

	return r;
}

/**
 * Localstorage attempts to return the localstorage.
 *
 * This is necessary because safari throws
 * when a user disables cookies/localstorage
 * and you attempt to access it.
 *
 * @return {LocalStorage}
 * @api private
 */

function localstorage() {
	try {
		// TVMLKit (Apple TV JS Runtime) does not have a window object, just localStorage in the global context
		// The Browser also has localStorage in the global context.
		return localStorage;
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}
}

module.exports = require('./common')(exports);

const {formatters} = module.exports;

/**
 * Map %j to `JSON.stringify()`, since no Web Inspectors do that by default.
 */

formatters.j = function (v) {
	try {
		return JSON.stringify(v);
	} catch (error) {
		return '[UnexpectedJSONParseError]: ' + error.message;
	}
};

}).call(this,require('_process'))
},{"./common":23,"_process":18}],23:[function(require,module,exports){

/**
 * This is the common logic for both the Node.js and web browser
 * implementations of `debug()`.
 */

function setup(env) {
	createDebug.debug = createDebug;
	createDebug.default = createDebug;
	createDebug.coerce = coerce;
	createDebug.disable = disable;
	createDebug.enable = enable;
	createDebug.enabled = enabled;
	createDebug.humanize = require('ms');

	Object.keys(env).forEach(key => {
		createDebug[key] = env[key];
	});

	/**
	* Active `debug` instances.
	*/
	createDebug.instances = [];

	/**
	* The currently active debug mode names, and names to skip.
	*/

	createDebug.names = [];
	createDebug.skips = [];

	/**
	* Map of special "%n" handling functions, for the debug "format" argument.
	*
	* Valid key names are a single, lower or upper-case letter, i.e. "n" and "N".
	*/
	createDebug.formatters = {};

	/**
	* Selects a color for a debug namespace
	* @param {String} namespace The namespace string for the for the debug instance to be colored
	* @return {Number|String} An ANSI color code for the given namespace
	* @api private
	*/
	function selectColor(namespace) {
		let hash = 0;

		for (let i = 0; i < namespace.length; i++) {
			hash = ((hash << 5) - hash) + namespace.charCodeAt(i);
			hash |= 0; // Convert to 32bit integer
		}

		return createDebug.colors[Math.abs(hash) % createDebug.colors.length];
	}
	createDebug.selectColor = selectColor;

	/**
	* Create a debugger with the given `namespace`.
	*
	* @param {String} namespace
	* @return {Function}
	* @api public
	*/
	function createDebug(namespace) {
		let prevTime;

		function debug(...args) {
			// Disabled?
			if (!debug.enabled) {
				return;
			}

			const self = debug;

			// Set `diff` timestamp
			const curr = Number(new Date());
			const ms = curr - (prevTime || curr);
			self.diff = ms;
			self.prev = prevTime;
			self.curr = curr;
			prevTime = curr;

			args[0] = createDebug.coerce(args[0]);

			if (typeof args[0] !== 'string') {
				// Anything else let's inspect with %O
				args.unshift('%O');
			}

			// Apply any `formatters` transformations
			let index = 0;
			args[0] = args[0].replace(/%([a-zA-Z%])/g, (match, format) => {
				// If we encounter an escaped % then don't increase the array index
				if (match === '%%') {
					return match;
				}
				index++;
				const formatter = createDebug.formatters[format];
				if (typeof formatter === 'function') {
					const val = args[index];
					match = formatter.call(self, val);

					// Now we need to remove `args[index]` since it's inlined in the `format`
					args.splice(index, 1);
					index--;
				}
				return match;
			});

			// Apply env-specific formatting (colors, etc.)
			createDebug.formatArgs.call(self, args);

			const logFn = self.log || createDebug.log;
			logFn.apply(self, args);
		}

		debug.namespace = namespace;
		debug.enabled = createDebug.enabled(namespace);
		debug.useColors = createDebug.useColors();
		debug.color = selectColor(namespace);
		debug.destroy = destroy;
		debug.extend = extend;
		// Debug.formatArgs = formatArgs;
		// debug.rawLog = rawLog;

		// env-specific initialization logic for debug instances
		if (typeof createDebug.init === 'function') {
			createDebug.init(debug);
		}

		createDebug.instances.push(debug);

		return debug;
	}

	function destroy() {
		const index = createDebug.instances.indexOf(this);
		if (index !== -1) {
			createDebug.instances.splice(index, 1);
			return true;
		}
		return false;
	}

	function extend(namespace, delimiter) {
		const newDebug = createDebug(this.namespace + (typeof delimiter === 'undefined' ? ':' : delimiter) + namespace);
		newDebug.log = this.log;
		return newDebug;
	}

	/**
	* Enables a debug mode by namespaces. This can include modes
	* separated by a colon and wildcards.
	*
	* @param {String} namespaces
	* @api public
	*/
	function enable(namespaces) {
		createDebug.save(namespaces);

		createDebug.names = [];
		createDebug.skips = [];

		let i;
		const split = (typeof namespaces === 'string' ? namespaces : '').split(/[\s,]+/);
		const len = split.length;

		for (i = 0; i < len; i++) {
			if (!split[i]) {
				// ignore empty strings
				continue;
			}

			namespaces = split[i].replace(/\*/g, '.*?');

			if (namespaces[0] === '-') {
				createDebug.skips.push(new RegExp('^' + namespaces.substr(1) + '$'));
			} else {
				createDebug.names.push(new RegExp('^' + namespaces + '$'));
			}
		}

		for (i = 0; i < createDebug.instances.length; i++) {
			const instance = createDebug.instances[i];
			instance.enabled = createDebug.enabled(instance.namespace);
		}
	}

	/**
	* Disable debug output.
	*
	* @return {String} namespaces
	* @api public
	*/
	function disable() {
		const namespaces = [
			...createDebug.names.map(toNamespace),
			...createDebug.skips.map(toNamespace).map(namespace => '-' + namespace)
		].join(',');
		createDebug.enable('');
		return namespaces;
	}

	/**
	* Returns true if the given mode name is enabled, false otherwise.
	*
	* @param {String} name
	* @return {Boolean}
	* @api public
	*/
	function enabled(name) {
		if (name[name.length - 1] === '*') {
			return true;
		}

		let i;
		let len;

		for (i = 0, len = createDebug.skips.length; i < len; i++) {
			if (createDebug.skips[i].test(name)) {
				return false;
			}
		}

		for (i = 0, len = createDebug.names.length; i < len; i++) {
			if (createDebug.names[i].test(name)) {
				return true;
			}
		}

		return false;
	}

	/**
	* Convert regexp to namespace
	*
	* @param {RegExp} regxep
	* @return {String} namespace
	* @api private
	*/
	function toNamespace(regexp) {
		return regexp.toString()
			.substring(2, regexp.toString().length - 2)
			.replace(/\.\*\?$/, '*');
	}

	/**
	* Coerce `val`.
	*
	* @param {Mixed} val
	* @return {Mixed}
	* @api private
	*/
	function coerce(val) {
		if (val instanceof Error) {
			return val.stack || val.message;
		}
		return val;
	}

	createDebug.enable(createDebug.load());

	return createDebug;
}

module.exports = setup;

},{"ms":24}],24:[function(require,module,exports){
/**
 * Helpers.
 */

var s = 1000;
var m = s * 60;
var h = m * 60;
var d = h * 24;
var w = d * 7;
var y = d * 365.25;

/**
 * Parse or format the given `val`.
 *
 * Options:
 *
 *  - `long` verbose formatting [false]
 *
 * @param {String|Number} val
 * @param {Object} [options]
 * @throws {Error} throw an error if val is not a non-empty string or a number
 * @return {String|Number}
 * @api public
 */

module.exports = function(val, options) {
  options = options || {};
  var type = typeof val;
  if (type === 'string' && val.length > 0) {
    return parse(val);
  } else if (type === 'number' && isFinite(val)) {
    return options.long ? fmtLong(val) : fmtShort(val);
  }
  throw new Error(
    'val is not a non-empty string or a valid number. val=' +
      JSON.stringify(val)
  );
};

/**
 * Parse the given `str` and return milliseconds.
 *
 * @param {String} str
 * @return {Number}
 * @api private
 */

function parse(str) {
  str = String(str);
  if (str.length > 100) {
    return;
  }
  var match = /^(-?(?:\d+)?\.?\d+) *(milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|m|hours?|hrs?|h|days?|d|weeks?|w|years?|yrs?|y)?$/i.exec(
    str
  );
  if (!match) {
    return;
  }
  var n = parseFloat(match[1]);
  var type = (match[2] || 'ms').toLowerCase();
  switch (type) {
    case 'years':
    case 'year':
    case 'yrs':
    case 'yr':
    case 'y':
      return n * y;
    case 'weeks':
    case 'week':
    case 'w':
      return n * w;
    case 'days':
    case 'day':
    case 'd':
      return n * d;
    case 'hours':
    case 'hour':
    case 'hrs':
    case 'hr':
    case 'h':
      return n * h;
    case 'minutes':
    case 'minute':
    case 'mins':
    case 'min':
    case 'm':
      return n * m;
    case 'seconds':
    case 'second':
    case 'secs':
    case 'sec':
    case 's':
      return n * s;
    case 'milliseconds':
    case 'millisecond':
    case 'msecs':
    case 'msec':
    case 'ms':
      return n;
    default:
      return undefined;
  }
}

/**
 * Short format for `ms`.
 *
 * @param {Number} ms
 * @return {String}
 * @api private
 */

function fmtShort(ms) {
  var msAbs = Math.abs(ms);
  if (msAbs >= d) {
    return Math.round(ms / d) + 'd';
  }
  if (msAbs >= h) {
    return Math.round(ms / h) + 'h';
  }
  if (msAbs >= m) {
    return Math.round(ms / m) + 'm';
  }
  if (msAbs >= s) {
    return Math.round(ms / s) + 's';
  }
  return ms + 'ms';
}

/**
 * Long format for `ms`.
 *
 * @param {Number} ms
 * @return {String}
 * @api private
 */

function fmtLong(ms) {
  var msAbs = Math.abs(ms);
  if (msAbs >= d) {
    return plural(ms, msAbs, d, 'day');
  }
  if (msAbs >= h) {
    return plural(ms, msAbs, h, 'hour');
  }
  if (msAbs >= m) {
    return plural(ms, msAbs, m, 'minute');
  }
  if (msAbs >= s) {
    return plural(ms, msAbs, s, 'second');
  }
  return ms + ' ms';
}

/**
 * Pluralization helper.
 */

function plural(ms, msAbs, n, name) {
  var isPlural = msAbs >= n * 1.5;
  return Math.round(ms / n) + ' ' + name + (isPlural ? 's' : '');
}

},{}],25:[function(require,module,exports){
/*!
 * shallow-clone <https://github.com/jonschlinkert/shallow-clone>
 *
 * Copyright (c) 2015, Jon Schlinkert.
 * Licensed under the MIT License.
 */

'use strict';

var utils = require('./utils');

/**
 * Shallow copy an object, array or primitive.
 *
 * @param  {any} `val`
 * @return {any}
 */

function clone(val) {
  var type = utils.typeOf(val);

  if (clone.hasOwnProperty(type)) {
    return clone[type](val);
  }
  return val;
}

clone.array = function cloneArray(arr) {
  return arr.slice();
};

clone.date = function cloneDate(date) {
  return new Date(+date);
};

clone.object = function cloneObject(obj) {
  if (utils.isObject(obj)) {
    return utils.mixin({}, obj);
  } else {
    return obj;
  }
};

clone.regexp = function cloneRegExp(re) {
  var flags = '';
  flags += re.multiline ? 'm' : '';
  flags += re.global ? 'g' : '';
  flags += re.ignorecase ? 'i' : '';
  return new RegExp(re.source, flags);
};

/**
 * Expose `clone`
 */

module.exports = clone;

},{"./utils":28}],26:[function(require,module,exports){
(function (Buffer){
var isBuffer = require('is-buffer');
var toString = Object.prototype.toString;

/**
 * Get the native `typeof` a value.
 *
 * @param  {*} `val`
 * @return {*} Native javascript type
 */

module.exports = function kindOf(val) {
  // primitivies
  if (typeof val === 'undefined') {
    return 'undefined';
  }
  if (val === null) {
    return 'null';
  }
  if (val === true || val === false || val instanceof Boolean) {
    return 'boolean';
  }
  if (typeof val === 'string' || val instanceof String) {
    return 'string';
  }
  if (typeof val === 'number' || val instanceof Number) {
    return 'number';
  }

  // functions
  if (typeof val === 'function' || val instanceof Function) {
    return 'function';
  }

  // array
  if (typeof Array.isArray !== 'undefined' && Array.isArray(val)) {
    return 'array';
  }

  // check for instances of RegExp and Date before calling `toString`
  if (val instanceof RegExp) {
    return 'regexp';
  }
  if (val instanceof Date) {
    return 'date';
  }

  // other objects
  var type = toString.call(val);

  if (type === '[object RegExp]') {
    return 'regexp';
  }
  if (type === '[object Date]') {
    return 'date';
  }
  if (type === '[object Arguments]') {
    return 'arguments';
  }

  // buffer
  if (typeof Buffer !== 'undefined' && isBuffer(val)) {
    return 'buffer';
  }

  // es6: Map, WeakMap, Set, WeakSet
  if (type === '[object Set]') {
    return 'set';
  }
  if (type === '[object WeakSet]') {
    return 'weakset';
  }
  if (type === '[object Map]') {
    return 'map';
  }
  if (type === '[object WeakMap]') {
    return 'weakmap';
  }
  if (type === '[object Symbol]') {
    return 'symbol';
  }

  // must be a plain object
  return 'object';
};

}).call(this,require("buffer").Buffer)
},{"buffer":3,"is-buffer":9}],27:[function(require,module,exports){
(function (process){
'use strict';

/**
 * Cache results of the first function call to ensure only calling once.
 *
 * ```js
 * var utils = require('lazy-cache')(require);
 * // cache the call to `require('ansi-yellow')`
 * utils('ansi-yellow', 'yellow');
 * // use `ansi-yellow`
 * console.log(utils.yellow('this is yellow'));
 * ```
 *
 * @param  {Function} `fn` Function that will be called only once.
 * @return {Function} Function that can be called to get the cached function
 * @api public
 */

function lazyCache(fn) {
  var cache = {};
  var proxy = function(mod, name) {
    name = name || camelcase(mod);

    // check both boolean and string in case `process.env` cases to string
    if (process.env.UNLAZY === 'true' || process.env.UNLAZY === true) {
      cache[name] = fn(mod);
    }

    Object.defineProperty(proxy, name, {
      enumerable: true,
      configurable: true,
      get: getter
    });

    function getter() {
      if (cache.hasOwnProperty(name)) {
        return cache[name];
      }
      return (cache[name] = fn(mod));
    }
    return getter;
  };
  return proxy;
}

/**
 * Used to camelcase the name to be stored on the `lazy` object.
 *
 * @param  {String} `str` String containing `_`, `.`, `-` or whitespace that will be camelcased.
 * @return {String} camelcased string.
 */

function camelcase(str) {
  if (str.length === 1) {
    return str.toLowerCase();
  }
  str = str.replace(/^[\W_]+|[\W_]+$/g, '').toLowerCase();
  return str.replace(/[\W_]+(\w|$)/g, function(_, ch) {
    return ch.toUpperCase();
  });
}

/**
 * Expose `lazyCache`
 */

module.exports = lazyCache;

}).call(this,require('_process'))
},{"_process":18}],28:[function(require,module,exports){
'use strict';

var utils = require('lazy-cache')(require);
var fn = require;
require = utils;
require('is-extendable', 'isObject');
require('mixin-object', 'mixin');
require('kind-of', 'typeOf');
require = fn;
module.exports = utils;

},{"is-extendable":10,"kind-of":26,"lazy-cache":27,"mixin-object":16}],29:[function(require,module,exports){
(function () {
    'use strict'
    const windowDebugBackup = window.debug;

    window.debug = function debug(...args)
    {
        console.log(...args);
    }

    const fingerprintParams = {};
    console.log('PAGE: hiding selenium, params', fingerprintParams);

    const pluginLauncher = require("./plugin-launcher.js")
    const stealthPlugin = require('puppeteer-extra-plugin-stealth')
    pluginLauncher.use(stealthPlugin())
    pluginLauncher.launch().then(function(){
        console.log('disabling debug');
        if (typeof(windowDebugBackup) === 'undefined') {
            delete window.debug;
        } else {
            window.debug = windowDebugBackup;
        }
    })



    document.currentScript.parentElement.removeChild(document.currentScript);
    console.log('PAGE: hide-selenium complete');
}());
},{"./plugin-launcher.js":30,"puppeteer-extra-plugin-stealth":20}],30:[function(require,module,exports){
'use strict'

class PluginLauncher {

    constructor() {
        this._plugins = [];
    }

    use(plugin) {
        if (typeof plugin !== 'object' || !plugin._isPuppeteerExtraPlugin) {
            console.error(`Warning: Plugin is not derived from PuppeteerExtraPlugin, ignoring.`, plugin);
            return this;
        }
        if (!plugin.name) {
            console.error(`Warning: Plugin with no name registering, ignoring.`, plugin);
            return this;
        }
        if (plugin.requirements.has('dataFromPlugins')) {
            plugin.getDataFromPlugins = this.getPluginData.bind(this);
        }
        plugin._register(Object.getPrototypeOf(plugin));
        this._plugins.push(plugin);
        debug('plugin registered', plugin.name);
        return this;
    }

    async launch(options) {
        // Ensure there are certain properties (e.g. the `options.args` array)
        var options = {};
        this.resolvePluginDependencies();
        this.orderPlugins();
        // Give plugins the chance to modify the options before launch
        options = await this.callPluginsWithValue('beforeLaunch', options);
        const opts = {
            context: 'launch',
            options,
            defaultArgs: this.defaultArgs
        };

        // https://github.com/puppeteer/puppeteer/blob/master/docs/api.md#pageevaluateonnewdocumentpagefunction-args
        const page = {
            evaluateOnNewDocument(fn, ...args) {
                debug('evaluateOnNewDocument');
                fn(...args);
            },
            browser () {
                return {
                    userAgent() {
                        return "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36";
                    }
                }
            },
            _client: {
                send (...args) {
                    debug('_client.send', ...args);
                }
            }
        };

        // Let's check requirements after plugin had the chance to modify the options
        const browser = {
            setMaxListeners(n) {
            },
            on(event, listener) {
                debug('on', event, listener);
                if (event === 'targetcreated') {
                    debug('calling targetcreated');
                    listener({
                        "type": function() {
                            return "page";
                        },
                        "page": function() {
                            return page;
                        }
                    });
                }
            }
        };
        this.checkPluginRequirements(opts);
        return await this.callPlugins('_bindBrowserEvents', browser, opts);
    }

    resolvePluginDependencies() {
        // Request missing dependencies from all plugins and flatten to a single Set
        const missingPlugins = this._plugins
                .map(p => p._getMissingDependencies(this._plugins))
                .reduce((combined, list) => {
                    return new Set([...combined, ...list]);
                }, new Set());
        if (!missingPlugins.size) {
            debug('no dependencies are missing');
            return;
        }
        debug('dependencies missing', missingPlugins);
        // Loop through all dependencies declared missing by plugins
        for (let name of [...missingPlugins]) {
            // Check if the dependency hasn't been registered as plugin already.
            // This might happen when multiple plugins have nested dependencies.
            if (this.pluginNames.includes(name)) {
                debug(`ignoring dependency '${name}', which has been required already.`);
                continue;
            }
            // We follow a plugin naming convention, but let's rather enforce it <3
            name = name.startsWith('puppeteer-extra-plugin')
                    ? name
                    : `puppeteer-extra-plugin-${name}`;
            // In case a module sub resource is requested print out the main package name
            // e.g. puppeteer-extra-plugin-stealth/evasions/console.debug => puppeteer-extra-plugin-stealth
            const packageName = name.split('/')[0];
            let dep = null;
            try {
                // Try to require and instantiate the stated dependency
                dep = require(name)();
                // Register it with `puppeteer-extra` as plugin
                this.use(dep);
            } catch (err) {
                console.warn(`
          A plugin listed '${name}' as dependency,
          which is currently missing. Please install it:
    
          yarn add ${packageName}
    
          Note: You don't need to require the plugin yourself,
          unless you want to modify it's default settings.
          `);
                throw err;
            }
            // Handle nested dependencies :D
            if (dep.dependencies.size) {
                this.resolvePluginDependencies();
            }
        }
    }

    /**
     * Order plugins that have expressed a special placement requirement.
     *
     * This is useful/necessary for e.g. plugins that depend on the data from other plugins.
     *
     * @todo Support more than 'runLast'.
     * @todo If there are multiple plugins defining 'runLast', sort them depending on who depends on whom. :D
     *
     * @private
     */
    orderPlugins() {
        debug('orderPlugins:before', this.pluginNames);
        const runLast = this._plugins
                .filter(p => p.requirements.has('runLast'))
                .map(p => p.name);
        for (const name of runLast) {
            const index = this._plugins.findIndex(p => p.name === name);
            this._plugins.push(this._plugins.splice(index, 1)[0]);
        }
        debug('orderPlugins:after', this.pluginNames);
    }

    /**
     * Lightweight plugin requirement checking.
     *
     * The main intent is to notify the user when a plugin won't work as expected.
     *
     * @todo This could be improved, e.g. be evaluated by the plugin base class.
     *
     * @private
     */
    checkPluginRequirements(opts = {}) {
        for (const plugin of this._plugins) {
            for (const requirement of plugin.requirements) {
                if (opts.context === 'launch' &&
                        requirement === 'headful' &&
                        opts.options.headless) {
                    console.warn(`Warning: Plugin '${plugin.name}' is not supported in headless mode.`);
                }
                if (opts.context === 'connect' && requirement === 'launch') {
                    console.warn(`Warning: Plugin '${plugin.name}' doesn't support puppeteer.connect().`);
                }
            }
        }
    }

    /**
     * Call plugins sequentially with the same values.
     * Plugins that expose the supplied property will be called.
     *
     * @param prop - The plugin property to call
     * @param values - Any number of values
     * @private
     */
    async callPlugins(prop, ...values) {
        for (const plugin of this.getPluginsByProp(prop)) {
            await plugin[prop].apply(plugin, values);
        }
    }

    /**
     * Get the names of all registered plugins.
     *
     * @member {Array<string>}
     * @private
     */
    get pluginNames() {
        return this._plugins.map(p => p.name);
    }

    /**
     * Call plugins sequentially and pass on a value (waterfall style).
     * Plugins that expose the supplied property will be called.
     *
     * The plugins can either modify the value or return an updated one.
     * Will return the latest, updated value which ran through all plugins.
     *
     * @param prop - The plugin property to call
     * @param value - Any value
     * @return The new updated value
     * @private
     */
    async callPluginsWithValue(prop, value) {
        for (const plugin of this.getPluginsByProp(prop)) {
            const newValue = await plugin[prop](value);
            if (newValue) {
                value = newValue;
            }
        }
        return value;
    }

    /**
     * Get all plugins that feature a given property/class method.
     *
     * @private
     */
    getPluginsByProp(prop) {
        return this._plugins.filter(plugin => prop in plugin);
    }

}

module.exports = new PluginLauncher()
},{}],"puppeteer-extra-plugin-stealth/evasions/chrome.runtime":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

const { getChromeRuntimeMock } = require('../shared')

/**
 * Pass the Chrome Test.
 *
 * This will work for iframes as well, except for `srcdoc` iframes:
 * https://github.com/puppeteer/puppeteer/issues/1106
 *
 * Could be mocked further.
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/chrome.runtime'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(
      args => {
        // Rematerialize serialized functions
        if (args && args.fns) {
          for (const fn of Object.keys(args.fns)) {
            eval(`var ${fn} =  ${args.fns[fn]}`) // eslint-disable-line
          }
        }

        window.chrome = getChromeRuntimeMock(window)
      },
      {
        // Serialize functions
        fns: {
          getChromeRuntimeMock: `${getChromeRuntimeMock.toString()}`
        }
      }
    )
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"../shared":19,"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/console.debug":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Pass toString test, though it breaks console.debug() from working
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/console.debug'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      window.console.debug = () => {
        return null
      }
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/iframe.contentWindow":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Fix for the HEADCHR_IFRAME detection (iframe.contentWindow.chrome), hopefully this time without breaking iframes.
 * Note: Only `srcdoc` powered iframes cause issues due to a chromium bug:
 *
 * https://github.com/puppeteer/puppeteer/issues/1106
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/iframe.contentWindow'
  }

  get requirements() {
    // Make sure `chrome.runtime` has ran, we use data defined by it (e.g. `window.chrome`)
    return new Set(['runLast'])
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      try {
        // Adds a contentWindow proxy to the provided iframe element
        const addContentWindowProxy = iframe => {
          const contentWindowProxy = {
            get(target, key) {
              // Now to the interesting part:
              // We actually make this thing behave like a regular iframe window,
              // by intercepting calls to e.g. `.self` and redirect it to the correct thing. :)
              // That makes it possible for these assertions to be correct:
              // iframe.contentWindow.self === window.top // must be false
              if (key === 'self') {
                return this
              }
              // iframe.contentWindow.frameElement === iframe // must be true
              if (key === 'frameElement') {
                return iframe
              }
              return Reflect.get(target, key)
            }
          }

          if (!iframe.contentWindow) {
            const proxy = new Proxy(window, contentWindowProxy)
            Object.defineProperty(iframe, 'contentWindow', {
              get() {
                return proxy
              },
              set(newValue) {
                return newValue // contentWindow is immutable
              },
              enumerable: true,
              configurable: false
            })
          }
        }

        // Handles iframe element creation, augments `srcdoc` property so we can intercept further
        const handleIframeCreation = (target, thisArg, args) => {
          const iframe = target.apply(thisArg, args)

          // We need to keep the originals around
          const _iframe = iframe
          const _srcdoc = _iframe.srcdoc

          // Add hook for the srcdoc property
          // We need to be very surgical here to not break other iframes by accident
          Object.defineProperty(iframe, 'srcdoc', {
            configurable: true, // Important, so we can reset this later
            get: function() {
              return _iframe.srcdoc
            },
            set: function(newValue) {
              addContentWindowProxy(this)
              // Reset property, the hook is only needed once
              Object.defineProperty(iframe, 'srcdoc', {
                configurable: false,
                writable: false,
                value: _srcdoc
              })
              _iframe.srcdoc = newValue
            }
          })
          return iframe
        }

        // Adds a hook to intercept iframe creation events
        const addIframeCreationSniffer = () => {
          /* global document */
          const createElement = {
            // Make toString() native
            get(target, key) {
              return Reflect.get(target, key)
            },
            apply: function(target, thisArg, args) {
              const isIframe =
                args && args.length && `${args[0]}`.toLowerCase() === 'iframe'
              if (!isIframe) {
                // Everything as usual
                return target.apply(thisArg, args)
              } else {
                return handleIframeCreation(target, thisArg, args)
              }
            }
          }
          // All this just due to iframes with srcdoc bug
          document.createElement = new Proxy(
            document.createElement,
            createElement
          )
        }

        // Let's go
        addIframeCreationSniffer()
      } catch (err) {
        // console.warn(err)
      }
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/media.codecs":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Fix Chromium not reporting "probably" to codecs like `videoEl.canPlayType('video/mp4; codecs="avc1.42E01E"')`.
 * (Chromium doesn't support proprietary codecs, only Chrome does)
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/media.codecs'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      try {
        /**
         * Input might look funky, we need to normalize it so e.g. whitespace isn't an issue for our spoofing.
         *
         * @example
         * video/webm; codecs="vp8, vorbis"
         * video/mp4; codecs="avc1.42E01E"
         * audio/x-m4a;
         * audio/ogg; codecs="vorbis"
         * @param {String} arg
         */
        const parseInput = arg => {
          const [mime, codecStr] = arg.trim().split(';')
          let codecs = []
          if (codecStr && codecStr.includes('codecs="')) {
            codecs = codecStr
              .trim()
              .replace(`codecs="`, '')
              .replace(`"`, '')
              .trim()
              .split(',')
              .filter(x => !!x)
              .map(x => x.trim())
          }
          return { mime, codecStr, codecs }
        }

        /* global HTMLMediaElement */
        const canPlayType = {
          // Make toString() native
          get(target, key) {
            // Mitigate Chromium bug (#130)
            if (typeof target[key] === 'function') {
              return target[key].bind(target)
            }
            return Reflect.get(target, key)
          },
          // Intercept certain requests
          apply: function(target, ctx, args) {
            if (!args || !args.length) {
              return target.apply(ctx, args)
            }
            const { mime, codecs } = parseInput(args[0])
            // This specific mp4 codec is missing in Chromium
            if (mime === 'video/mp4') {
              if (codecs.includes('avc1.42E01E')) {
                return 'probably'
              }
            }
            // This mimetype is only supported if no codecs are specified
            if (mime === 'audio/x-m4a' && !codecs.length) {
              return 'maybe'
            }

            // This mimetype is only supported if no codecs are specified
            if (mime === 'audio/aac' && !codecs.length) {
              return 'probably'
            }
            // Everything else as usual
            return target.apply(ctx, args)
          }
        }
        HTMLMediaElement.prototype.canPlayType = new Proxy(
          HTMLMediaElement.prototype.canPlayType,
          canPlayType
        )
      } catch (err) {}
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/navigator.languages":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Pass the Languages Test.
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/navigator.languages'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      // Overwrite the `languages` property to use a custom getter.
      Object.defineProperty(navigator, 'languages', {
        get: () => ['en-US', 'en'] // TODO: Make configurable by user
      })
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/navigator.permissions":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Pass the Permissions Test.
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/navigator.permissions'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      const originalQuery = window.navigator.permissions.query
      // eslint-disable-next-line
      window.navigator.permissions.__proto__.query = parameters =>
        parameters.name === 'notifications'
          ? Promise.resolve({ state: Notification.permission }) //eslint-disable-line
          : originalQuery(parameters)

      // Inspired by: https://github.com/ikarienator/phantomjs_hide_and_seek/blob/master/5.spoofFunctionBind.js
      const oldCall = Function.prototype.call
      function call() {
        return oldCall.apply(this, arguments)
      }
      // eslint-disable-next-line
      Function.prototype.call = call

      const nativeToStringFunctionString = Error.toString().replace(
        /Error/g,
        'toString'
      )
      const oldToString = Function.prototype.toString

      function functionToString() {
        if (this === window.navigator.permissions.query) {
          return 'function query() { [native code] }'
        }
        if (this === functionToString) {
          return nativeToStringFunctionString
        }
        return oldCall.call(oldToString, this)
      }
      // eslint-disable-next-line
      Function.prototype.toString = functionToString
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/navigator.plugins":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * In headless mode `navigator.mimeTypes` and `navigator.plugins` are empty.
 * This plugin quite emulates both of these to match regular headful Chrome.
 * We even go so far as to mock functional methods, instance types and `.toString` properties. :D
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/navigator.plugins'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      function mockPluginsAndMimeTypes() {
        /* global MimeType MimeTypeArray PluginArray */

        // Disguise custom functions as being native
        const makeFnsNative = (fns = []) => {
          const oldCall = Function.prototype.call
          function call() {
            return oldCall.apply(this, arguments)
          }
          // eslint-disable-next-line
          Function.prototype.call = call

          const nativeToStringFunctionString = Error.toString().replace(
            /Error/g,
            'toString'
          )
          const oldToString = Function.prototype.toString

          function functionToString() {
            for (const fn of fns) {
              if (this === fn.ref) {
                return `function ${fn.name}() { [native code] }`
              }
            }

            if (this === functionToString) {
              return nativeToStringFunctionString
            }
            return oldCall.call(oldToString, this)
          }
          // eslint-disable-next-line
          Function.prototype.toString = functionToString
        }

        const mockedFns = []

        const fakeData = {
          mimeTypes: [
            {
              type: 'application/pdf',
              suffixes: 'pdf',
              description: '',
              __pluginName: 'Chrome PDF Viewer'
            },
            {
              type: 'application/x-google-chrome-pdf',
              suffixes: 'pdf',
              description: 'Portable Document Format',
              __pluginName: 'Chrome PDF Plugin'
            },
            {
              type: 'application/x-nacl',
              suffixes: '',
              description: 'Native Client Executable',
              enabledPlugin: Plugin,
              __pluginName: 'Native Client'
            },
            {
              type: 'application/x-pnacl',
              suffixes: '',
              description: 'Portable Native Client Executable',
              __pluginName: 'Native Client'
            }
          ],
          plugins: [
            {
              name: 'Chrome PDF Plugin',
              filename: 'internal-pdf-viewer',
              description: 'Portable Document Format'
            },
            {
              name: 'Chrome PDF Viewer',
              filename: 'mhjfbmdgcfjbbpaeojofohoefgiehjai',
              description: ''
            },
            {
              name: 'Native Client',
              filename: 'internal-nacl-plugin',
              description: ''
            }
          ],
          fns: {
            namedItem: instanceName => {
              // Returns the Plugin/MimeType with the specified name.
              const fn = function(name) {
                if (!arguments.length) {
                  throw new TypeError(
                    `Failed to execute 'namedItem' on '${instanceName}': 1 argument required, but only 0 present.`
                  )
                }
                return this[name] || null
              }
              mockedFns.push({ ref: fn, name: 'namedItem' })
              return fn
            },
            item: instanceName => {
              // Returns the Plugin/MimeType at the specified index into the array.
              const fn = function(index) {
                if (!arguments.length) {
                  throw new TypeError(
                    `Failed to execute 'namedItem' on '${instanceName}': 1 argument required, but only 0 present.`
                  )
                }
                return this[index] || null
              }
              mockedFns.push({ ref: fn, name: 'item' })
              return fn
            },
            refresh: instanceName => {
              // Refreshes all plugins on the current page, optionally reloading documents.
              const fn = function() {
                return undefined
              }
              mockedFns.push({ ref: fn, name: 'refresh' })
              return fn
            }
          }
        }
        // Poor mans _.pluck
        const getSubset = (keys, obj) =>
          keys.reduce((a, c) => ({ ...a, [c]: obj[c] }), {})

        function generateMimeTypeArray() {
          const arr = fakeData.mimeTypes
            .map(obj => getSubset(['type', 'suffixes', 'description'], obj))
            .map(obj => Object.setPrototypeOf(obj, MimeType.prototype))
          arr.forEach(obj => {
            arr[obj.type] = obj
          })

          // Mock functions
          arr.namedItem = fakeData.fns.namedItem('MimeTypeArray')
          arr.item = fakeData.fns.item('MimeTypeArray')

          return Object.setPrototypeOf(arr, MimeTypeArray.prototype)
        }

        const mimeTypeArray = generateMimeTypeArray()
        Object.defineProperty(navigator, 'mimeTypes', {
          get: () => mimeTypeArray
        })

        function generatePluginArray() {
          const arr = fakeData.plugins
            .map(obj => getSubset(['name', 'filename', 'description'], obj))
            .map(obj => {
              const mimes = fakeData.mimeTypes.filter(
                m => m.__pluginName === obj.name
              )
              // Add mimetypes
              mimes.forEach((mime, index) => {
                navigator.mimeTypes[mime.type].enabledPlugin = obj
                obj[mime.type] = navigator.mimeTypes[mime.type]
                obj[index] = navigator.mimeTypes[mime.type]
              })
              obj.length = mimes.length
              return obj
            })
            .map(obj => {
              // Mock functions
              obj.namedItem = fakeData.fns.namedItem('Plugin')
              obj.item = fakeData.fns.item('Plugin')
              return obj
            })
            .map(obj => Object.setPrototypeOf(obj, Plugin.prototype))
          arr.forEach(obj => {
            arr[obj.name] = obj
          })

          // Mock functions
          arr.namedItem = fakeData.fns.namedItem('PluginArray')
          arr.item = fakeData.fns.item('PluginArray')
          arr.refresh = fakeData.fns.refresh('PluginArray')

          return Object.setPrototypeOf(arr, PluginArray.prototype)
        }

        const pluginArray = generatePluginArray()
        Object.defineProperty(navigator, 'plugins', {
          get: () => pluginArray
        })

        // Make mockedFns toString() representation resemble a native function
        makeFnsNative(mockedFns)
      }
      try {
        const isPluginArray = navigator.plugins instanceof PluginArray
        const hasPlugins = isPluginArray && navigator.plugins.length > 0
        if (isPluginArray && hasPlugins) {
          return // nothing to do here
        }
        mockPluginsAndMimeTypes()
      } catch (err) {}
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/navigator.webdriver":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Pass the Webdriver Test.
 * Will delete `navigator.webdriver` property.
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/navigator.webdriver'
  }

  async onPageCreated(page) {
    await page.evaluateOnNewDocument(() => {
      Object.defineProperty(window, 'navigator', {
        value: new Proxy(navigator, {
          has: (target, key) => (key === 'webdriver' ? false : key in target),
          get: (target, key) =>
            key === 'webdriver'
              ? undefined
              : typeof target[key] === 'function'
              ? target[key].bind(target)
              : target[key]
        })
      })
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/user-agent-override":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Fixes the UserAgent info (composed of UA string, Accept-Language, Platform).
 *
 * If you don't provide any values this plugin will default to using the regular UserAgent string (while stripping the headless part).
 * Default language is set to "en-US,en", default platform is "win32".
 *
 * By default puppeteer will not set a `Accept-Language` header in headless:
 * It's (theoretically) possible to fix that using either `page.setExtraHTTPHeaders` or a `--lang` launch arg.
 * Unfortunately `page.setExtraHTTPHeaders` will lowercase everything and launch args are not always available. :)
 *
 * In addition, the `navigator.platform` property is always set to the host value, e.g. `Linux` which makes detection very easy.
 *
 * Note: You cannot use the regular `page.setUserAgent()` puppeteer call in your code,
 * as it will reset the language and platform values you set with this plugin.
 *
 * @example
 * const puppeteer = require("puppeteer-extra")
 *
 * const StealthPlugin = require("puppeteer-extra-plugin-stealth")
 * const stealth = StealthPlugin()
 * // Remove this specific stealth plugin from the default set
 * stealth.enabledEvasions.delete("user-agent-override")
 * puppeteer.use(stealth)
 *
 * // Stealth plugins are just regular `puppeteer-extra` plugins and can be added as such
 * const UserAgentOverride = require("puppeteer-extra-plugin-stealth/evasions/user-agent-override")
 * // Define custom UA, locale and platform
 * const ua = UserAgentOverride({ userAgent: "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)", locale: "de-DE,de;q=0.9", platform: "Win32" })
 * puppeteer.use(ua)
 *
 * @param {Object} [opts] - Options
 * @param {string} [opts.userAgent] - The user agent to use (default: browser.userAgent())
 * @param {string} [opts.locale] - The locale to use in `Accept-Language` header and in `navigator.languages` (default: `en-US,en;q=0.9`)
 * @param {string} [opts.platform] - The platform to use in `navigator.platform` (default: `Win32`)
 *
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/user-agent-override'
  }

  get defaults() {
    return {
      userAgent: null,
      acceptLanguage: 'en-US,en',
      platform: 'Win32'
    }
  }

  async onPageCreated(page) {
    const override = {
      userAgent:
        this.opts.userAgent ||
        (await page.browser().userAgent()).replace(
          'HeadlessChrome/',
          'Chrome/'
        ),
      acceptLanguage: this.opts.locale || 'en-US,en',
      platform: this.opts.platform || 'Win32'
    }

    this.debug('onPageCreated - Will set these user agent options', {
      override,
      opts: this.opts
    })

    page._client.send('Network.setUserAgentOverride', override)
  } // onPageCreated
}

const defaultExport = opts => new Plugin(opts)
module.exports = defaultExport

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/webgl.vendor":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Fix WebGL Vendor/Renderer being set to Google in headless mode
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/webgl.vendor'
  }

  /* global WebGLRenderingContext */
  async onPageCreated(page) {
    // Chrome returns undefined, Firefox false
    await page.evaluateOnNewDocument(() => {
      try {
        // Remove traces of our Proxy ;-)
        var stripErrorStack = stack =>
          stack
            .split('\n')
            .filter(line => !line.includes(`at Object.apply`))
            .filter(line => !line.includes(`at Object.get`))
            .join('\n')

        const getParameterProxyHandler = {
          get(target, key) {
            try {
              // Mitigate Chromium bug (#130)
              if (typeof target[key] === 'function') {
                return target[key].bind(target)
              }
              return Reflect.get(target, key)
            } catch (err) {
              err.stack = stripErrorStack(err.stack)
              throw err
            }
          },
          apply: function(target, thisArg, args) {
            const param = (args || [])[0]
            // UNMASKED_VENDOR_WEBGL
            if (param === 37445) {
              return 'Intel Inc.'
            }
            // UNMASKED_RENDERER_WEBGL
            if (param === 37446) {
              return 'Intel Iris OpenGL Engine'
            }
            try {
              return Reflect.apply(target, thisArg, args)
            } catch (err) {
              err.stack = stripErrorStack(err.stack)
              throw err
            }
          }
        }

        const proxy = new Proxy(
          WebGLRenderingContext.prototype.getParameter,
          getParameterProxyHandler
        )
        // To find out the original values here: Object.getOwnPropertyDescriptors(WebGLRenderingContext.prototype.getParameter)
        Object.defineProperty(WebGLRenderingContext.prototype, 'getParameter', {
          configurable: true,
          enumerable: false,
          writable: false,
          value: proxy
        })
      } catch (err) {
        console.warn(err)
      }
    })
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}],"puppeteer-extra-plugin-stealth/evasions/window.outerdimensions":[function(require,module,exports){
'use strict'

const { PuppeteerExtraPlugin } = require('puppeteer-extra-plugin')

/**
 * Fix missing window.outerWidth/window.outerHeight in headless mode
 * Will also set the viewport to match window size, unless specified by user
 */
class Plugin extends PuppeteerExtraPlugin {
  constructor(opts = {}) {
    super(opts)
  }

  get name() {
    return 'stealth/evasions/window.outerdimensions'
  }

  async onPageCreated(page) {
    // Chrome returns undefined, Firefox false
    await page.evaluateOnNewDocument(() => {
      try {
        if (window.outerWidth && window.outerHeight) {
          return // nothing to do here
        }
        const windowFrame = 85 // probably OS and WM dependent
        window.outerWidth = window.innerWidth
        window.outerHeight = window.innerHeight + windowFrame
      } catch (err) {}
    })
  }

  async beforeLaunch(options) {
    // Have viewport match window size, unless specified by user
    // https://github.com/GoogleChrome/puppeteer/issues/3688
    if (!('defaultViewport' in options)) {
      options.defaultViewport = null
    }
    return options
  }
}

module.exports = function(pluginConfig) {
  return new Plugin(pluginConfig)
}

},{"puppeteer-extra-plugin":21}]},{},[29]);
