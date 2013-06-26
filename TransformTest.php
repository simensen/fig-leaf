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
        $actual = transform(
            '\\Acme\\Blog',
            '\\Acme\\Blog',
            '\\',
            '/src/'
        );
        $this->assertFalse($actual);
    }
    
    public function testBSFileAsPrefix()
    {
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\Acme\\Blog\\ShowController.php',
            '\\',
            '/src/ShowController.php'
        );
        $this->assertFalse($actual);
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
}
