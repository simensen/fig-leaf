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

**Logical Separator**: A single character used to delimit _logical segments_;
for example, a slash, backslash, colon, etc.

**Logical Segment**: A string that does not contain any _logical separators_.
For example, given a _logical separator_ of ":", then `Foo`, `Bar`, and `Baz`
are valid logical segments.

**Fully Qualified Logical Path**: A string starting with a _logical separator_
followed by one or more _logical segments_ delimited by _logical separators_.
For example, given a _logical separator_ of ":", then `:Foo`, `:Foo:Bar`, and
`:Foo:Bar:Baz` are _fully qualified logical paths_.

> Definition of a _fully qualified logical path_ is broken out into its own
> definition separate of the "input" context. That is now called a source.
> This means we can use _fully qualified logical path_ for other purposes
> rules and definitions.

**Root Path**: A _fully qualified logical path_ consisting of a single _logical
separator_. For example, given a _logical separator_ of ":", then ":" is a
_root path_.

> The _root path_ is a special case because it does not need to have a
> _logical separator_ appended to it for any reason.

**Source**: A _fully qualified logical path_ that is to undergo transformation.

> This is the input! It is a _fully qualified logical path_ but it is not THE
> _fully qualified logical path_. Means we can talk about `source` in the
> rules and definitions.

**Output**: A string representing a file system path that MAY exist on disk.
The _output_ MUST NOT be terminated by a directory separator. If the
transformation is unsuccessful the _output_ will be `false`.

> Might as well define the output of the transformation while we are at it.

**Logical Path Base**: A string containing a _fully qualified logical path_ from
which transformation on the _source_ may be based. If the _logical path base_ is
neither _root path_ nor equal to the _source_, then the _logical path base_,
with a _logical separator_ appended, must exist as a substring from the
beginning of the _source_ in order to be considered valid. For example, given a
_logical separator_ of ":" and a _fully qualified logical path_ of
`:Foo:Bar:Baz`, then `:`, `:Foo`, and `:Foo:Bar` are valid _logical path bases_.
None of `:F`, `:F:`, `:Foo:B`, `:Foo:Bar:` are valid.

> Renamed from "prefix" to "base" to test out how another word than prefix would
> feel given we are not really dealing with prefixes exclusively. "Base" might
> not be the best word and I am totally open to changing that.
>
> By specifying this string being a _fully qualified logical path_ it opens the
> door for being able to specify the complete _source_ path to be transformed:
>
>   - transform(":Foo:Bar", ":Foo:Bar", ":", "/src/") => /src/
>   - transform(":Foo:Bar.txt", ":Foo:Bar.txt", ":", "/src/foo-bar.txt") => /src/foo-bar.txt
>
> I think it also helped clear up the wording on how to allow ":Foo:Bar" to be
> valid _logical path_prefix_ by saying:
>
> > ..., then the _logical path base_, with a _logical separator_ appended,
> > must exist as a substring from the beginning of the _source_ in order to
> > be considered valid.
>
> This states pretty clearly that the trailing _logical separator_ is not a
> part of the _logical path base_ but that unless the _logical path base_ is
> equal to the _source_ or is a _root path_, it must be followed by a _logical
> separator_ in the _source_ in order to be considered valid.

**Logical Path Suffix**: A string representing the remainder of the _source_
following the _logical path base_ appended by a _logical separator_. For
example, given a _logical separator_ of ":", a _source_ of `:Foo:Bar:Baz:Qux`,
and a _logical path base_ of `:Foo:Bar`, then `Baz:Qux` is the _logical path
suffix_.

**File System Path Base**: A file system path on which a transformation is
based. If the _file system path base_ represents a directory the trailing
directory separator SHOULD be omitted.

> By not specifying this having anything to do with directories we open up
> the ability to transform full paths, not just prefixes. We recommend
> (SHOULD) that users never specify file system path base with a trailing
> slash, even for directories, but we can leave this fuzzy if it is desired
> to be so. I'd be just as happy changing SHOULD to MUST. :)


2. Specification
----------------

Given a _source_, a _logical path base_, a _logical separator_, and a _file
system path base_, implementations MUST transform the _source_ into _output_.
To do so, implementations:

- MUST remove any trailing directory separators from the _file system
  path base_.

> Allow for flexibility in specifying the file system path base for directories
> both with and without a trailing slash.

- MUST immediately return the _file system path base_ if the _source_ is equal
  to the _logical path base_.

> This handles the following cases:
>
>     transform(":Acme:Foo", ":Acme:Foo", ":", "/src/");
>     transform(":Acme:Foo:Bar.txt", ":Acme:Foo:Bar.txt", "/src/acme-foo-bar.txt");

- MUST return `false` if the _logical path base_ is not valid.

> This handles the following cases:
>
>     transform(":Acme:Foo", ":Acme:F", ":", "/src/");
>
> This MUST be MUST to ensure that users can rely on the same behaviour when
> invalid input is encountered. If the above sometimes returns false and
> sometimes a *very wrong path*, we lost consistency.

- MUST append a directory to the _file system path base_, and

> We can be fuzzy on this because at the point that we would ever get to this
> rule we know that the _file system path base_ is going to be a directory.
> So we can say that the implemetnation MUST fix it by appending the directory
> separator if it is not there.

- MUST replace the _logical path base_ in the _source_ with the _file system
  path base_, and

- MUST replace _logical path separators_ in the _logical path suffix_ of the
  _source_ with directory separators, and

- MUST return the transformed `source`


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
 * @param string $source The logical path to transform.
 * @param string $logical_base The logical prefix associated with $fs_base.
 * @param string $logical_sep The logical separator in the logical path.
 * @param string $fs_base The directory path prefix for the transformation.
 * @return string The logical path transformed into a file path.
 */
function transform(
    $source,
    $logical_base,
    $logical_sep,
    $fs_base
) {
    // - MUST remove any trailing directory separators from the _file system
    //   path base_.
    $fs_base = rtrim($fs_base, DIRECTORY_SEPARATOR);

    if ($source === $logical_base) {
        // - MUST immediately return the _file system path base_ if the _source_
        //   is equal to the _logical path base_.
        return $fs_base;
    }

    if ($logical_base !== $logical_sep ) {
        // "If the _logical path base_ is neither _root_ nor equal to the
        // _source_, the _logical path base_, with a _logical separator_
        // appended, must exist as a substring from the beginning of the
        // _source_ in order to be considered valid."
        //
        // At this point, source is neither root (if condition above) nor
        // is it equal to the logical base (first block would have returned)
        // so we should append the logical separator.
        $logical_base = $logical_base . $logical_sep;
    }

    if ($logical_base !== substr($source, 0, strlen($logical_base))) {
        // - MUST return `false` if the _logical path base_ is not valid.
        return false;
    }

    // find the logical suffix
    $logical_suffix = substr($source, strlen($logical_base));

    // - MUST append a directory to the _file system path base_, and
    $fs_base = $fs_base . DIRECTORY_SEPARATOR;
    
    // transform into a file system path
    return $fs_base
         . str_replace($logical_sep, DIRECTORY_SEPARATOR, $logical_suffix);
}
```
