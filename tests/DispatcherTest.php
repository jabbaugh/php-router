<?php

include_once(dirname(__FILE__) . '/../lib/Dispatcher.php');
include_once(dirname(__FILE__) . '/../lib/Route.php');

class MockRoute_ClassFileNotFound extends Route
{
    public function matchMap(){
        return TRUE;
    }

    public function getMapClass() {
        return 'noclassnameClass';
    }

    public function getMapMethod() {
        return 'method';
    }
}

class MockRoute_CatchClassNotSpecified extends Route
{
    public function matchMap(){
        return TRUE;
    }

    public function getMapClass() {
        return '';
    }

    public function getMapMethod() {
        return 'method';
    }
}

class MockRoute_CatchMethodNotSpecified extends Route
{
    public function matchMap(){
        return TRUE;
    }

    public function getMapClass() {
        return 'someclass';
    }

    public function getMapMethod() {
        return '';
    }
}

class MockRoute_CatchBadClassName extends Route
{
    public function matchMap(){
        return TRUE;
    }

    public function getMapClass() {
        return 'foo\"';
    }

    public function getMapMethod() {
        return 'method';
    }
}

class MockRoute_CatchClassMethodNotFound extends Route
{
    public function matchMap(){
        return TRUE;
    }

    public function getMapClass() {
        return 'foo';
    }

    public function getMapMethod() {
        return 'nomethod';
    }
}

class MockRoute_Success extends Route
{
    public function matchMap(){
        return TRUE;
    }

    public function getMapClass() {
        return 'foo';
    }

    public function getMapMethod() {
        return 'bar';
    }
}

/*----------------------------------------------------------------------------*/

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        @unlink('fooClass.php');
        @unlink('noclassnameClass.php');
    }

    public function helperCreateTestClassFile()
    {
        $contents = "<?php\n"
                  . "class fooClass {\n"
                  . "    public function bar( \$args ) {\n"
                  . "        //print_r(\$args);\n"
                  . "    }\n"
                  . "}\n"
                  . "?>\n"
                  ;

        $fh = fopen('fooClass.php', 'w');
        fwrite($fh, $contents);
        fclose($fh);
    }

    public function testCatchClassFileNotFound()
    {
        $route = new MockRoute_ClassFileNotFound;

        $route->matchMap('/no_class/bar/55');

        $dispatcher = new Dispatcher;
        
        try {
            $dispatcher->dispatch( $route );
        } catch ( classFileNotFoundException $exception ) {
            return;
        }
            
        $this->fail('Try Catch failed ');
    }

    public function testCatchClassNameNotFound()
    {
        $contents = "<?php\n"
                  . "class noclassnamefoundClass {\n"
                  . "    public function bar( \$args ) {\n"
                  . "        //print_r(\$args);\n"
                  . "    }\n"
                  . "}\n"
                  . "?>\n"
                  ;

        $fh = fopen('noclassnameClass.php', 'w');
        fwrite($fh, $contents);
        fclose($fh);

        $route = new MockRoute_ClassFileNotFound();

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( classNameNotFoundException $exception ) {
            return;
        }


        $this->fail('Catching class name not found failed ');
    }

    public function testCatchClassNotSpecified()
    {
        $route = new MockRoute_CatchClassNotSpecified();

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( classNotSpecifiedException $exception ) {
            return;
        }

        $this->fail('Catching class not specified failed ');
    }

    public function testCatchBadClassName()
    {
        $route = new MockRoute_CatchBadClassName();

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( badClassNameException $exception ) {
            return;
        }

        $this->fail('Catching bad class name failed ');
    }

    public function testCatchMethodNotSpecified()
    {
        $this->helperCreateTestClassFile();

        $route = new MockRoute_CatchMethodNotSpecified();

        $dispatcher = new Dispatcher;

        try {
            $dispatcher->dispatch( $route );
        } catch ( methodNotSpecifiedException $exception ) {
            return;
        }

        $this->fail('Catching method not specified failed ');
    }

    public function testCatchClassMethodNotFound()
    {
        $this->helperCreateTestClassFile();

        $route = new MockRoute_CatchClassMethodNotFound();

        $dispatcher = new Dispatcher;
        $dispatcher->setSuffix('Class');

        try {
           $dispatcher->dispatch( $route );
        } catch ( classMethodNotFoundException $exception ) {
            return;
        }

        $this->fail('Catching class method not found failed ');
    }

    public function testSuccessfulDispatch()
    {
        $this->helperCreateTestClassFile();

        $route = new MockRoute_Success();

        $dispatcher = new Dispatcher;
        $dispatcher->setSuffix('Class');

        if( TRUE === $route->matchMap('/foo/bar/55') )
        {
            $res = $dispatcher->dispatch($route);
            $this->isTrue( $res );
        }
        else
        {
            $this->fail('The route could not be mapped');
        }
    }

    public function testMethodsAreChainable()
    {
      $dispatcher = new Dispatcher();

      $this->assertSame($dispatcher, $dispatcher->setSuffix(''));
      $this->assertSame($dispatcher, $dispatcher->setClassPath(''));
    }
}

