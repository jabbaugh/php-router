<?php
/**
 * @author Rob Apodaca <rob.apodaca@gmail.com>
 * @copyright Copyright (c) 2009, Rob Apodaca
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://robap.github.com/php-router/
 */
class Router
{
    /**
     * Stores the Route objects
     * @var array
     */
    private $routes = array();
    
    /**
     * Adds a named route to the list of possible routes
     * @param string $name
     * @param Route $route
     * @return Router
     */
    public function addRoute( $name, $route )
    {
        $this->routes[$name] = $route;

        return $this;
    }

    /**
     * Returns the routes array
     * @return [Route]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Builds and gets a url for the named route
     * @param string $name
     * @param array $args
     * @throws NamedPathNotFoundException
     * @throws InvalidArgumentException
     * @return string the url
     */
    public function getUrl( $name, $args = array() )
    {
        if( TRUE !== array_key_exists($name, $this->routes) )
            throw new NamedPathNotFoundException;
        
        $match_ok = TRUE;

        //Check for the correct number of arguments
        if( count($args) !== count($this->routes[$name]->getDynamicElements()) )
            $match_ok = FALSE;

        $path = $this->routes[$name]->getPath();
        foreach( $args as $arg_key => $arg_value )
        {
            $path = str_replace( $arg_key, $arg_value, $path, $count );
            if( 1 !== $count )
                $match_ok = FALSE;
        }

        //Check that all of the argument keys matched up with the dynamic elements
        if( FALSE === $match_ok ) throw new InvalidArgumentException;

        return $path;
    }

    /**
     * Finds a maching route in the routes array using specified $path
     * @param string $path
     * @return Route
     * @throws RouteNotFoundException
     */
    public function findRoute( $path )
    {
        foreach( $this->routes as $route )
        {
            if( TRUE === $route->matchMap( $path ) )
            {
                return $route;
            }
        }

        throw new RouteNotFoundException;
    }
}

class RouteNotFoundException extends Exception{}
class NamedPathNotFoundException extends Exception{}