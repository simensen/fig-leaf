<?php
/**
 * Example implementation.
 * 
 * Note that this is only an example, and is not a specification in itself.
 * 
 * @param string $logical_path The logical path to transform.
 * @param string $logical_prefix The logical prefix associated with $fs_prefix.
 * @param string $logical_sep The logical separator in the logical path.
 * @param string $fs_prefix The file system path prefix for the transformation.
 * @return string The logical path transformed into a file system path.
 */
function transform(
    $logical_path,
    $logical_prefix,
    $logical_sep,
    $fs_prefix
) {
    if ($logical_path === $logical_prefix) {
        return $fs_prefix;
    }

    if ($logical_prefix !== $logical_sep ) {
        // if the logical prefix is not the root then the logical prefix
        // must have the logical separator appended to ensure that the
        // logcal prefix specified represented a valid logical path prefix
        $logical_prefix = $logical_prefix . $logical_sep;
    }

    if ($logical_prefix !== substr($logical_path, 0, strlen($logical_prefix))) {
        // ensure that partial matches will not be made
        return null;
    }

    // find the logical suffix 
    $logical_suffix = substr($logical_path, strlen($logical_prefix));
    
    // transform into a file system path
    return $fs_prefix
         . str_replace($logical_sep, DIRECTORY_SEPARATOR, $logical_suffix);
}

class TransformTest extends PHPUnit_Framework_TestCase
{
    public function testClassName()
    {
        $expect = "/path/to/foo-bar/src/Baz/Qux.php";
        $actual = transform(
            '\Foo\Bar\Baz\Qux',
            '\Foo\Bar',
            '\\',
            '/path/to/foo-bar/src/'
        ) . '.php';
        $this->assertSame($expect, $actual);
    }
    
    public function testResourceName()
    {
        $expect = "/path/to/foo-bar/resources/Baz/Qux.yml";
        $actual = transform(
            ':Foo:Bar:Baz:Qux.yml',
            ':Foo:Bar',
            ':',
            '/path/to/foo-bar/resources/'
        );
        $this->assertSame($expect, $actual);
    }
    
    public function testDirectoryName()
    {
        $expect = "/path/to/foo-bar/other/Baz/Qux";
        $actual = transform(
            '/Foo/Bar/Baz/Qux',
            '/Foo/Bar',
            '/',
            '/path/to/foo-bar/other/' // no trailing slash
        );
        $this->assertSame($expect, $actual);
    }
    
    public function testBSPrefix()
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
    
    public function testBSFile()
    {
        $expect = "/src/ShowController.php";
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\Acme\\Blog\\ShowController.php',
            '\\',
            '/src/ShowController.php' // no trailing slash
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

    public function testInvalidLogicalPrefix()
    {
        $expect = null;
        $actual = transform(
            '\\Foo\\Bar\\Baz.php',
            '\\F',
            '\\',
            '/src/'
        );
        $this->assertSame($expect, $actual);
   }
}
