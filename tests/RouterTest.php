<?php

include_once(dirname(__FILE__) . '/../lib/Route.php');
include_once(dirname(__FILE__) . '/../lib/Router.php');

class MockRoute_GetLink extends Route
{
    public function getDynamicElements(){
        return array(
            ':class'    => ':class',
            ':method'   => ':method',
            ':id'       => ':id'
        );
    }

    public function getPath() {
        return '/:class/:method/:id';
    }
}

class MockRoute_FindRoute extends Route
{
    public function __construct( $path ) {
        $this->path = $path;
    }

    public function matchMap() {
        return TRUE;
    }

    public function getPath() {
        return $this->path;
    }
}

class MockRoute_FailToFindRoute extends Route
{

    public function matchMap() {
        return FALSE;
    }

    public function getPath() {
        return '/find/this/route';
    }
}

class MockRoute_FindRouteInManyRoutesTrue extends Route
{
    public function matchMap() {
        return TRUE;
    }
}

class MockRoute_FindRouteInManyRoutesFalse extends Route
{
    public function matchMap() {
        return FALSE;
    }
}

/*----------------------------------------------------------------------------*/

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testAddRoute()
    {
        $router = new Router;

        $route = $this->getMock('Route');

        $router->addRoute( 'myroute', $route );

        $routes = $router->getRoutes();
        
        $this->assertTrue( array_key_exists('myroute', $routes));
    }

    public function testGetLink()
    {
        $router = new Router;

        $route = new MockRoute_GetLink();

        $router->addRoute( 'myroute', $route );

        $url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':id'        => '1'
        ));
        
        $this->assertSame('/myclass/mymethod/1', $url);
    }

    public function testFailGetLink()
    {
        $router = new Router;

        $route = new MockRoute_GetLink();

        $router->addRoute( 'myroute', $route );
        
        //should create '/myclass/mymethod/1'
        $url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':id'        => '1'
        ));
        
        $this->assertNotSame('/myclass/mymethod/2', $url);
    }

    /**
     * @expectedException NamedPathNotFoundException
     */
    public function testGetUrlNamedRouteDoesNotExist()
    {
        $router = new Router;

        $route = new MockRoute_GetLink();

        $router->addRoute( 'myroute', $route );

        //a php error should be triggered
        $failed_url = $router->getUrl( 'not_there', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':id'       => 1
        ));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetUrlWrongArgumentForNamedRoute()
    {
        $router = new Router;
        
        $route = new MockRoute_GetLink();

        $router->addRoute( 'myroute', $route );

        $failed_url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod',
            ':wrong'    => 1
        ));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetUrlWrongNumberOfArgumentsForNamedRoutes()
    {
        $router = new Router;

        $route = new MockRoute_GetLink();

        $router->addRoute( 'myroute', $route );

        $failed_url = $router->getUrl( 'myroute', array(
            ':class'    => 'myclass',
            ':method'   => 'mymethod'
        ));
    }

    /**
     * @depends testAddRoute
     */
    public function testFindRoute()
    {
        $router = new Router;

        $path = '/find/this/class';
        
        $route = new MockRoute_FindRoute($path);

        $router->addRoute( 'myroute', $route );

        $found_route = $router->findRoute( $path );

        $this->assertSame($route, $found_route);
        
    }

    /**
     * @depends testAddRoute
     * @expectedException RouteNotFoundException
     */
    public function testFailToFindRoute()
    {
        $router = new Router;

        $route = new MockRoute_FailToFindRoute();

        $router->addRoute( 'myroute', $route );

        $router->findRoute( '/fail/to/find/this/route' );
    }

    /**
     * @depends testAddRoute
     */
    public function testFindRouteInManyRoutes()
    {
        $router = new Router;

        $id_route = new MockRoute_FindRouteInManyRoutesTrue();

        $router->addRoute( 'id', $id_route );

        //Here is a default route (should go last)
        $def_route = new MockRoute_FindRouteInManyRoutesFalse();
    
        $router->addRoute( 'default', $def_route );

        //We should only find the id_route defined above
        $find_path = '/parts/show/100';
        $found_route = $router->findRoute( $find_path );
        
        if( TRUE === is_object( $found_route ) )
        {
            $this->assertSame($id_route, $found_route);
        }
        else
        {
            $this->fail( 'Found result is not an Object' );
        }
    }

    public function testMethodsAreChainable()
    {
        $router = new Router;

        $this->assertSame($router, $router->addRoute('test', new Route));
    }
    
    public function testQueryParameters()
    {
        $router = new Router;
        $route = new Route( "/2008-08-01/Accounts/:id/IncomingPhoneNumbers" );
        $route->setMapClass( 'IncomingPhoneNumbers' )->setMapMethod( 'list' );
        $route->addDynamicElement( ':id', ':id' );
        $router->addRoute('test', $route);
        $found = $router->findRoute( '/2008-08-01/Accounts/1/IncomingPhoneNumbers?a=1&b=2' );
        
        $this->assertSame($found, $route);
    }
}

?>
