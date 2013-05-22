<?php

include_once(dirname(__FILE__) . '/../lib/Route.php');

class RouteTest extends PHPUnit_Framework_TestCase
{

    public function testObjectCreated()
    {
        $this->assertTrue( is_object( new Route ), "Route object not created");
    }

    public function testSetRoutePathBySetter()
    {
        $route = new Route;

        $test_path = '/:class/:method/:id';
        $route->setPath( $test_path );
        $this->assertAttributeEquals($test_path, 'path', $route);
    }

    public function testSetRoutePathByConstructor()
    {
        $test_path = '/:class/:method/:id';
        $route = new Route( $test_path );
        $this->assertAttributeEquals($test_path, 'path', $route);
    }

    public function testGetRoutPath()
    {
        $route = new Route;

        $test_path = '/a/path';
        $route->setPath( $test_path );
        
        $this->assertSame($test_path, $route->getPath(), 'Path not set correctly');
    }

    public function testSetMapClassMethod()
    {
        $route = new Route;

        $test_class  = 'class';
        $test_method = 'method';

        $route->setMapClass( $test_class );
        $route->setMapMethod( $test_method );

        $this->assertAttributeEquals( $test_class, 'class', $route );
        $this->assertAttributeEquals( $test_method, 'method', $route );
    }

    public function testGetMapClassMethod()
    {
        $route = new Route;

        $test_class  = 'class';
        $test_method = 'method';

        $route->setMapClass( $test_class );
        $route->setMapMethod( $test_method );

        $this->assertSame( $test_class, $route->getMapClass(), 'Class not set correctly' );
        $this->assertSame( $test_method, $route->getMapMethod(), 'Method not set correctly' );
    }

    public function testAddDynamicElements()
    {
        $route = new Route;

        $route->addDynamicElement( ':id', ':id' );

        $this->assertSame(array(':id' => ':id'), $route->getDynamicElements());
    }

    public function testMatchDefaultMap()
    {
        $route = new Route;

        $route->setPath( '/:class/:method/:id' );

        $route->addDynamicElement( ':class', ':class' );
        $route->addDynamicElement( ':method', ':method' );
        $route->addDynamicElement( ':id', ':id' );

        $this->assertTrue( $route->matchMap('/this/path/succeeds'));
        $this->assertSame( 'this', $route->getMapClass() );
        $this->assertSame( 'path', $route->getMapMethod() );
        

        $this->assertFalse($route->matchMap('/this/path/should/fail'));
        $this->assertFalse($route->matchMap('/this/:path/fails'));
    }

    public function testMatchRegExpMap()
    {
        $route = new Route;

        $route->setPath( '/:class/:method/:id' );

        $route->addDynamicElement( ':class', ':class' );
        $route->addDynamicElement( ':method', ':method' );
        $route->addDynamicElement( ':id', '^\d{4}$' );

        $this->assertTrue( $route->matchMap('/someclass/somemethod/1234') );
        $this->assertTrue( $route->matchMap('/someclass/somemethod/0234') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/12345') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/abc') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod') );

        $route = new Route;

        $route->setPath( '/:class/:method/:id' );

        $route->addDynamicElement( ':class', '^startWith' );
        $route->addDynamicElement( ':method', 'endsIn$' );
        $route->addDynamicElement( ':id', '^\d{4}$' );
        
        $route_result = $route->matchMap('/startWith_/_endsIn/1234');
        
        $this->assertTrue( $route_result );
        $this->assertSame('startWith_', $route->getMapClass());
        $this->assertSame('_endsIn', $route->getMapMethod());
    }

    public function testMatchStaticBaseClass()
    {
        $route = new Route;

        $route->setPath( '/someclass/:method' );

        $route->setMapClass( 'someclass' );

        $route->addDynamicElement( ':method', ':method' );

        $this->assertTrue( $route->matchMap('/someclass/somemethod') );
        $this->assertTrue( $route->matchMap('/someclass/othermethod') );
        $this->assertFalse( $route->matchMap('/otherclass/somemethod') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/abc') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/') );
    }

    public function testMatchStaticBaseMethod()
    {
        $route = new Route;

        $route->setPath( '/:class/somemethod' );

        $route->setMapMethod( 'somemethod' );

        $route->addDynamicElement( ':class', ':class' );

        $this->assertTrue( $route->matchMap('/someclass/somemethod') );
        $this->assertFalse( $route->matchMap('/someclass/othermethod') );
        $this->assertTrue( $route->matchMap('/otherclass/somemethod') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/abc') );
        $this->assertFalse( $route->matchMap('/someclass/somemethod/') );
    }

    public function testMixedMatches()
    {
        $route = new Route;

        $route->setPath( '/orders/show/:yearStart/:yearEnd' );

        $route->setMapClass( 'orders' );
        $route->setMapMethod( 'show_range' );

        $route->addDynamicElement(':yearStart', '^[1-9]{1}\d{3}$');
        $route->addDynamicElement(':yearEnd', '^[1-9]{1}\d{3}$');

        $this->assertTrue( $route->matchMap('/orders/show/2008/2009') );
        $this->assertFalse( $route->matchMap('/orders/show/0008/2009/') );
        $this->assertFalse( $route->matchMap('/orders/show/2008/2009/') );
        $this->assertFalse( $route->matchMap('/orders/show/all/them') );
        $this->assertFalse( $route->matchMap('/orders/show/1999/them') );
    }


    public function testExtractingDynamicArguments()
    {
        $route = new Route;

        $route->setPath( '/class/method/:id1/:id2' );

        $route->addDynamicElement( ':id1', ':id1' );
        $route->addDynamicElement( ':id2', ':id2' );

        $route->matchMap('/class/method/one/two');

        $args_array = $route->getMapArguments();
        
        $this->assertArrayHasKey(':id1', $args_array);
        $this->assertArrayHasKey(':id2', $args_array);
    }

    public function testRouteMethodsAreChainable()
    {
        $route = new Route;

        $this->assertSame($route, $route->setPath(''));
        $this->assertSame($route, $route->setMapClass(''));
        $this->assertSame($route, $route->setMapMethod(''));
        $this->assertSame($route, $route->addDynamicElement('', ''));
    }
    
    public function testMatchMapWhenQueryParametersArePresent()
    {
        $route = new Route( "/2008-08-01/Accounts/:id/IncomingPhoneNumbers" );
        $route->setMapClass( 'IncomingPhoneNumbers' )->setMapMethod( 'list' );
        $route->addDynamicElement( ':id', ':id' );
        
        $result = $route->matchMap('/2008-08-01/Accounts/1/IncomingPhoneNumbers?a=1&b=2');
        $this->assertTrue($result);
    }
}

?>
