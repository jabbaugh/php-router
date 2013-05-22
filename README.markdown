PHP Router
===========================

Introduction
-----------------------------

The php url routing library is designed to help you with php url routing by mapping so called "pretty" url’s to class::methods that reside in your framework. It adopts the [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern) pattern in which all requests are passed through a centralized location. That centralized location then can use this URL Routing library to pass control to some other part of your framework.

It is intended that this library be portable and flexible enough to include in your own framework (MVC or otherwise). Hopefully, you find it useful.

In creating a routing library, there are many ways to go about it. I prefer a very verbose method of defining routes. I want to be able to tell with certainty where a path is going to be routed to.

See [http://robap.github.com/php-router/](http://robap.github.com/php-router/)

Dependencies
-----------------------------

[php 5.2.x](http://www.php.net/) or later

Install
-----------------------------

* Download the latest [tarball](http://github.com/robap/php-router/tarball/master) or [zip](http://github.com/robap/php-router/zipball/master) file and extract the contents to some place accessible by your framework.
* Include the php-router/php-router.php file.
* Create your index.php file and tell apache to route requests through index.php (see sample.htaccess and [this good description](http://blog.craigweston.ca/?p=18) of how apache mod-rewrite is used for this purpose).
* Set the path to your controller classes.
* Define your route or routes.
* Call Router::findRoute() and pass the result to the Dispatcher class. A Dispatcher class is included but you can also create your own.

Routing Theory
-----------------------------

Routes are added to the Router class. When the Router::findRoute()
method is called, it searches through the defined routes and attempts
to find a match for the given url. If a match is found, the matching
route map can be used to determine where the request should go.

A Route is comprised of two main components:
the route *path* and the route *map*.

### Route Paths

The route path is a template that represents a relative url that you would like to match. It is made up of *static* or *dynamic* elements which are separated by a "/".

*Static* elements are hard wired parts of the path. They are not substituted with variables. They either match the map or they do not.

*Dynamic* elements are the portions of the path that are substituted with a variable which could be anything supplied in the url or may have to match a regular expression. This allows you to create Routes for virtually any sort of url.

### Route Maps

Route maps define how a path is to be matched and what class method to call when matched.

Defining Routes
-----------------------------

At the heart of this routing library is the Route class which allows you to define routes as objects. When creating a route object, You set the path, class, method, and any dynamic elements.

The constructor argument is the pattern of the url. The methods setMapClass and setMapMethod add the class name and method that should be invoked for this route. The addDynamicElement adds regular expression matching patterns for any dynamic parts of the url pattern. (note in the examples below that many of the methods are chainable)

    //This route matches url's such as '/orders/show/12345', '/orders/show/99999'
    //It will not match url's such as '/orders/show/abc45', '/orders/show/12'
    $route = new Route( '/orders/show/:order_id' );
    $route->setMapClass( 'orders' )->setMapMethod( 'show' )
          ->addDynamicElement( ':order_id', '^\d{5}$' );

You may wish for the class and/or method to be dynamic. This is most useful when your url's mostly follow the same pattern. For example, a common pattern is '/controller/method/id'. A route for this pattern can be defined as follows:

    //This route will match any 3 part url such as '/foo/bar/1', '/foo/bar/red', etc.
    $route = new Route( '/:class/:method/:id' );
    $route->addDynamicElement( ':class', ':class' )->addDynamicElement( ':method', ':method' )
          ->addDynamicElement( ':id', ':id' );

Add as many routes as you need. Route matching is done in the order they are added. So, put the most specific routes first and the most generic route last.

    //Define matching rules to match class and method
    $route = new Route( '/:class/:method/:order_id' );
    $route->setMapClass( '^o' ) //matches class beginning with 'o'
          ->setMapMethod( '^show_' ) //matches action beginning with 'show_'
          ->addDynamicElement( ':order_id', '^\d{5}$' ); //matches regexp

    //Match any class and method
    $route = new Route( '/:class/:method/:order_id' );
    $route->setMapClass( ':class' ) //matches any :class
          ->setMapMethod( ':method' ) //matches any :action
          ->addDynamicElement( ':id', ':id' ); //matches any :id

Adding routes
-----------------------------

Add Route objects to the Router using the addRoute method. You pass a name for the route and the route object. The name can be used later to construct url's.

    $router = new Router;
    $router->addRoute( 'route_name', $route );

Dispatching
-----------------------------

A Dispatcher class is provided. You can also use your own method of dispatching the Route object returned by the Router class.

Router::findRoute will return a modified Route object or throw an Exception if not found. Here is how the supplied Dispatcher class can be used.

    $dispatcher = new Dispatcher;

    try {
      $found_route = $router->findRoute( urldecode($_SERVER['REQUEST_URI']) );
      $dispatcher->dispatch( $found_route );
    } catch ( RouteNotFoundException $exception ) {
      //handle Exception
    } catch ( badClassNameException $exception ) {
      //handle Exception
    } catch ( classFileNotFoundException $exception ) {
      //handle Exception
    } catch ( classNameNotFoundException $exception ) {
      //handle Exception
    } catch ( classMethodNotFoundException $exception ) {
      //handle Exception
    } catch ( classNotSpecifiedException $exceptione ) {
      //handle Exception
    } catch ( methodNotSpecifiedException $exception ) {
      //handle Exception
    }

Any matched dynamic elements in the Route object are passed by the Dispatcher to your class/controller as an associative array. This passed data has had little or no security checking applied (however, before requiring in your class/controller, the file name and path was vetted as much as possible to help protect against arbitrary files from being included). You should handle all data passed to your classes/controllers as you would all data from users (as unsafe).

VERY IMPORTANT! When creating your own dispatcher, make sure you take proper steps to secure the code and not allow arbitrary files to be required in. Any dynamic elements matched will be passed to your custom dispatcher from the wild and unaltered.

### Setting Class/Controllers Suffix

You may want to define your class files and class names with a suffix which does not appear in your Route path. You can do so by using the Dispatcher's setSuffix method. For example, if you defined a class of 'orders' in your Route path and map but you actually want the class: 'ordersController' contained in 'ordersController.php' to be called, set the suffix:

    //Cause the file '[class]Controller.php' to be included and the class
    // '[class]Controller' to be used.
    $dispatcher->setSuffix('Controller');

### Setting Class/Controllers Path

Tell the dispatcher class where to look for your class files:

    $dispatcher->setClassPath('/path/to/controller_classes');

Creating URLs
-----------------------------

Once routes have been defined, you can create url's for links and form actions by referring to your named route and pass any dynamic elements as an argument array:

    $router = new Router;

    //An example 'orders' route that might be set up
    $orders_route = new Route( '/orders/show/:order_id' );
    $orders_route->setMapClass( 'orders' )->setMapMethod( 'show' )
                 ->addDynamicElement( ':order_id', '^\d$' );
    $router->addRoute( 'orders', $ordes_route );

    //Later in your code, you can create a url that will look
    //like: '/orders/show/5'
    $router->getUrl( 'orders', array( ':order_id' => 5 ) );

Putting it all together
-----------------------------

A very basic index.php/bootstrap file may look something like the
following.

    <?php

    //...Stuff before routing occurs

    //Set the include path so that the Routing library files can be included.
    set_include_path(get_include_path() . PATH_SEPARATOR . '/path/to/php-router');

    //Include a PageError class which can be used later. You supply this class.
    include('PageError.php');
    include('php-router.php');

    //Create a new instance of Router (you'd likely use a factory or container to
    // manage the instance)
    $router = new Router;

    //Get an instance of Dispatcher
    $dispatcher = new Dispatcher;
    $dispatcher->setSuffix('Controller');
    $dispatcher->setClassPath('/path/to/controllers/');

    //Set up a 'catch all' default route and add it to the Router.
    //You may want to set up an external file, define your routes there, and
    // and include that file in place of this code block.
    $std_route = new Route('/:class/:method/:id');
    $std_route->addDynamicElement(':class', ':class')
              ->addDynamicElement(':method', ':method')
              ->addDynamicElement(':id', ':id');
    $router->addRoute( 'std', $std_route );

    //Set up your default route:
    $default_route = new Route('/');
    $default_route->setMapClass('default')->setMapMethod('index');
    $router->addRoute( 'default', $default_route );

    $url = urldecode($_SERVER['REQUEST_URI']);

    try {
        $found_route = $router->findRoute($url);
        $dispatcher->dispatch( $found_route );
    } catch ( RouteNotFoundException $e ) {
        PageError::show('404', $url);
    } catch ( badClassNameException $e ) {
        PageError::show('400', $url);
    } catch ( classFileNotFoundException $e ) {
        PageError::show('500', $url);
    } catch ( classNameNotFoundException $e ) {
        PageError::show('500', $url);
    } catch ( classMethodNotFoundException $e ) {
        PageError::show('500', $url);
    } catch ( classNotSpecifiedException $e ) {
        PageError::show('500', $url);
    } catch ( methodNotSpecifiedException $e ) {
        PageError::show('500', $url);
    }

Other php url routing classes
-----------------------------

* [Bare-bones Rails-style MVC Request Router for PHP](http://allseeing-i.com/Bare-bones-Rails-style-MVC-Request-Router-for-PHP). A very nice php MVC router.
* [Simple php url routing controller](http://blog.sosedoff.com/2009/07/04/simpe-php-url-routing-controller/). A well written and very lightweight routing class.
* [Net URL Mapper](http://pear.php.net/package/Net_URL_Mapper). Light on documentation, not much to go on.

PHP Framework Routing
-----------------------------

* [Code Igniter](http://codeigniter.com/user_guide/general/routing.html)
* [CakePHP](http://docs.cakephp.nu/classes/show/Router)
* [Recess Framework](http://www.recessframework.org/page/routing-in-recess-screencast)
* [Zend Framework](http://framework.zend.com/manual/en/zend.controller.router.html)
* [Symfony](http://www.symfony-project.org/book/1_2/09-Links-and-the-Routing-System)
* [Kohana](http://docs.kohanaphp.com/general/routing)

License
-----------------------------

[GNU General Public License](http://opensource.org/licenses/gpl-3.0.html)

Authors
-----------------------------

Rob Apodaca (rob.apodaca@gmail.com)

Contact
-----------------------------

Rob Apodaca (rob.apodaca@gmail.com)
