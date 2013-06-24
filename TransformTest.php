<?php
/**
 * Example implementation.
 * 
 * Note that this is only an example, and is not a specification in itself.
 * 
 * @param string $logical_path The logical path to transform.
 * @param string $logical_prefix The logical prefix associated with $base_dir.
 * @param string $logical_sep The logical separator in the logical path.
 * @param string $base_dir The base directory for the transformation.
 * @param string $file_ext An optional file extension.
 * @return string The logical path transformed into a file system path.
 */
function transform(
    $logical_path,
    $logical_prefix,
    $logical_sep,
    $base_dir,
    $file_ext = null
) {
    // make sure the logical prefix ends in a separator
    $logical_prefix = rtrim($logical_prefix, $logical_sep)
                    . $logical_sep;
    
    // make sure the base directory ends in a separator
    $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR)
              . DIRECTORY_SEPARATOR;
    
    // find the logical suffix 
    $logical_suffix = substr($logical_path, strlen($logical_prefix));
    
    // transform into a file system path
    return $base_dir
         . str_replace($logical_sep, DIRECTORY_SEPARATOR, $logical_suffix)
         . $file_ext;
}

class TransformTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        // mapped prefix
        $expect = "/src/ShowController.php";
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\Acme\\Blog',
            '\\',
            '/src/'
        );
        $this->assertSame($expect, $actual);
        
        // mapped directory
        $expect = "/src/";
        $actual = transform(
            '\\Acme\\Blog',
            '\\Acme\\Blog',
            '\\',
            '/src/'
        );
        $this->assertSame($expect, $actual);

        // mapped file
        $expect = "/src/ShowController.php";
        $actual = transform(
            '\\Acme\\Blog\\ShowController.php',
            '\\Acme\\Blog\\ShowController.php',
            '\\',
            '/src/ShowController.php'
        );
        $this->assertSame($expect, $actual);

        // mapped root "\"
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