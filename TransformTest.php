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
    if ($source === $logical_base) {
        // The definition for the _logical path base_ says that it is a
        // _fully qualified logical path_ itself. This means that it is possible
        // to send the same _fully qualified logical path_ as both the _source_
        // and the _logical path base_.
        //
        // In the specification we have a rule:
        //
        // - MUST immediately return the _base file system path_ if the _source_
        //   is equal to the _logical path base_.
        //
        // This can be seen as useful for two reasons.
        //
        // 1) We know what the output is going to be given this situation. We
        //    can just explicitly return the fs base in this case. Why do the
        //    extra processing if we already know the end results?
        //
        // 2) Since we can handle some weird edge cases (transforming the
        //    logical path base itself or transforming to a "file") upfront
        //    and immediately, it makes the rest of the processing a lot
        //    easier since we no longer have to worry about the edge cases.
        //    This block alone handles the two big edge cases we've been
        //    struggling with.
        //
        // Handles the following cases:
        //
        //     transform(":Acme:Foo", ":Acme:Foo", ":", "/src/");
        //     transform(":Acme:Foo:Bar.txt", ":Acme:Foo:Bar.txt", "/src/acme-foo-bar.txt");
        //
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
        //
        // This ensures that users MUST specify the logical base WITHOUT
        // a trailing slash *unless* they have either specified a root path
        // (in which case they have supplied a string with a trailing logical
        // separator) or a complete path (first block handles it) but still
        // allows us to "fix" it for our needs in the implementation per the
        // spec without relying on rtrim.
        $logical_base = $logical_base . $logical_sep;
    }

    if ($logical_base !== substr($source, 0, strlen($logical_base))) {
        // - MUST return `false` if the _logical path base_ is not valid.
        //
        // From the embedded rule above, if the logical base (now
        // for sure terminated by a logical seperator) is not a
        // substring of the source, the logical base is not valid and
        // we should return false.
        //
        // Handles the following cases:
        //
        //     transform(":Acme:Foo", ":Acme:F", ":", "/src/");
        //     transform(":Acme:Foo", ":Acme:Foo:Bar", ":", "/src/");
        //
        return false;
    }

    // find the logical suffix
    $logical_suffix = substr($source, strlen($logical_base));

    // - MUST append a directory to the _file system path base_ if one does not
    //   already exist, and
    //
    // If we get to this point, we for sure have a logical suffix that is not an
    // empty string so we can safely assume that the fs_base should be
    // considered a directory.
    //
    // Since we don't know until this point given *all* of the processing above
    // that this is the case, we have to do it at this point and cannot do this
    // earlier. Further, we can't make this assumption *at all* without most
    // or all of the extra processing and checks above.
    $fs_base = rtrim($fs_base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    
    // transform into a file system path
    return $fs_base
         . str_replace($logical_sep, DIRECTORY_SEPARATOR, $logical_suffix);
}

class TransformTest extends PHPUnit_Framework_TestCase
{
    public function testLogicalPrefixIsRoot()
    {
        $actual = transform(
            ':Foo:Bar',
            ':',
            ':',
            '/path/to/root'
        );
        
        $expect = '/path/to/root/Foo/Bar';
        
        $this->assertSame($expect, $actual);
    }
    
    public function testClassName()
    {
        $expect = "/path/to/foo-bar/src/Baz/Qux.php";
        $actual = transform(
            '\Foo\Bar\Baz\Qux',
            '\Foo\Bar',
            '\\',
            '/path/to/foo-bar/src'
        ) . '.php';
        $this->assertSame($expect, $actual);
    }
    
    public function testResourceName()
    {
        $expect = "/path/to/foo-bar/resources/Baz/Qux.yml";
        $actual = transform(
            ':Foo:Bar:Baz:Qux',
            ':Foo:Bar',
            ':',
            '/path/to/foo-bar/resources/'
        ) . '.yml';
        $this->assertSame($expect, $actual);
    }
    
    public function testOtherName()
    {
        $expect = "/path/to/foo-bar/other/Baz/Qux";
        $actual = transform(
            '/Foo/Bar/Baz/Qux',
            '/Foo/Bar',
            '/',
            '/path/to/foo-bar/other'
        );
        $this->assertSame($expect, $actual);
    }
    
    public function testDirectoryName()
    {
        $expect = "/path/to/foo-bar/other/Baz/Qux/";
        $actual = transform(
            '/Foo/Bar/Baz/Qux',
            '/Foo/Bar',
            '/',
            '/path/to/foo-bar/other'
        ) . '/';
        $this->assertSame($expect, $actual);
    }
    
    public function testBSPrefixWithFileExtension()
    {
        $expect = "/src/ShowController.php";
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\Acme\\Blog',
            '\\',
            '/src/'
        );
        $this->assertSame($expect, $actual);
    }
    
    public function testBSDirectory()
    {
        $expect = "/src/";
        $actual = transform(
            '\\Acme\\Blog',
            '\\Acme\\Blog',
            '\\',
            '/src/'
        );
        $this->assertSame($expect, $actual);
    }
    
    public function testBSFileAsPrefix()
    {
        $expect = '/src/acme-blog-show-controller.php';
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\Acme\\Blog\\ShowController.php',
            '\\',
            '/src/acme-blog-show-controller.php'
        );
        $this->assertSame($expect, $actual);
    }

    public function testBSRoot()
    {
        $expect = "/src/Acme/Blog/ShowController.php";
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\',
            '\\',
            '/src/'
        );
        $this->assertSame($expect, $actual);
    }

    public function testLogicalPathBaseTooLong()
    {
        $actual = transform(
            '\\Acme\\Blog',
            '\\Acme\\Blog\\Baz',
            '\\',
            '/src/'
        );
        $this->assertFalse($actual);
   }
}
