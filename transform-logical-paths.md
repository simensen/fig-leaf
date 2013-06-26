PSR-T: Transformation Of Logical Paths To File System Paths
===========================================================

This document describes an algorithm to transform a logical resource path to a
file system path. Among other things, the algorithm allows transformation of
class names and other logical resource names to file names.

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD",
"SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be
interpreted as described in [RFC 2119](http://tools.ietf.org/html/rfc2119).


1. Definitions
--------------

**Logical Separator**: A single character to delimit _logical segments_; for
example, a slash, backslash, colon, etc.

**Logical Segment**: A string delimited by _logical separators_.

**Fully Qualified Logical Path**: A _logical separator_ followed by one or
more _logical segments_ delimited by _logical separators_. Given a _logical
separator_ of ":", then `:Foo`, `:Foo:Bar`, and `:Foo:Bar:Baz` are _fully
qualified logical paths_. (The _fully qualified logical path_ will be
transformed into a file system path.)

**Logical Path Prefix**: A _logical path prefix_ is any contiguous series of
_logical separators_ and _logical segments_ at the beginning of a
_fully qualified logical path_, beginning and ending with a _logical separator_.
For example, given a _logical separator_ of ":", then `:`, `:Foo:`, and
`:Foo:Bar:` are _logical path prefixes_ for a _fully qualified logical path_
of `:Foo:Bar:Baz`. (The _logical path prefix_ is associated with a _directory
path prefix_ in the file system.)

**Logical Path Suffix**: Given a _fully qualified logical path_ and a
_logical path prefix_, the _logical path suffix_ is the remainder of the
_fully qualified logical path_ after the _logical path prefix_. For example,
given a _logical separator_ of ":", a _fully qualified logical path_ of
`:Foo:Bar:Baz:Qux`, and a _logical path prefix_ of `:Foo:Bar:`, then `Baz:Qux`
is the _logical path suffix_.

**Directory Path Prefix**: A directory path in the file system associated with
a _logical path prefix_. The _directory path prefix_ must terminate in a
directory separator.


2. Specification
----------------

Given a fully qualified logical path, a logical path prefix, a logical
separator, and a directory path prefix, implementations MUST transform the
fully qualified logical path into a path that MAY exist in the file
system. To do so, implementations:

- If the logical path prefix does not end in a logical separator, the
  implementation MUST append one.
  
- If the directory path prefix does not end in a directory separator, the
  implementation MUST append one.

- MUST replace the logical path prefix in the fully qualified logical path 
  with the directory path prefix, and

- MUST replace logical path separators in the logical path suffix with
  directory separators, and

- MUST return the transformed string; if the transformation fails, the
  implementation MAY return false.


3. Example Implementation
-------------------------

The example implementation MUST NOT be regarded as part of the specification;
it is an example only. Implementations MAY contain additional features and MAY
differ in how they are implemented.

```php
<?php
/**
 * Example implementation.
 * 
 * Note that this is only an example, and is not a specification in itself.
 * 
 * @param string $logical_path The logical path to transform.
 * @param string $logical_prefix The logical prefix associated with $dir_prefix.
 * @param string $logical_sep The logical separator in the logical path.
 * @param string $dir_prefix The directory path prefix for the transformation.
 * @return string The logical path transformed into a file path.
 */
function transform(
    $logical_path,
    $logical_prefix,
    $logical_sep,
    $dir_prefix
) {
    // make sure the logical prefix has a trailing separator
    $logical_prefix = rtrim($logical_prefix, $logical_sep) . $logical_sep;
    
    // make sure the base dir has a trailing separator
    $dir_prefix = rtrim($dir_prefix, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    
    // does the logical path actually have the logical prefix?
    $prefix_len = strlen($logical_prefix);
    if (substr($logical_path, 0, $prefix_len) != $logical_prefix) {
        return false;
    }
    
    // find the logical suffix
    $logical_suffix = substr($logical_path, $prefix_len);
    
    // transform into a file system path
    return $dir_prefix
         . str_replace($logical_sep, DIRECTORY_SEPARATOR, $logical_suffix);
}
```
