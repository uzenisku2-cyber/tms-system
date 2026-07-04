<?php declare(strict_types = 1);

// osfsl-/var/www/backend/vendor/composer/../laravel/framework/src/Illuminate/Routing/Route.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Routing\Route
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8af2078792256eea913498319694b27350ba01c1d26b60a67225243c73b65f15-8.4.23-6.70.0.3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Routing\\Route',
        'filename' => '/var/www/backend/vendor/composer/../laravel/framework/src/Illuminate/Routing/Route.php',
      ),
    ),
    'namespace' => 'Illuminate\\Routing',
    'name' => 'Illuminate\\Routing\\Route',
    'shortName' => 'Route',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 1510,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Conditionable',
      1 => 'Illuminate\\Routing\\CreatesRegularExpressionRouteConstraints',
      2 => 'Illuminate\\Routing\\FiltersControllerMiddleware',
      3 => 'Illuminate\\Support\\Traits\\Macroable',
      4 => 'Illuminate\\Routing\\ResolvesRouteDependencies',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'uri' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'uri',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The URI pattern the route responds to.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 16,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'methods' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'methods',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The HTTP methods the route responds to.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'action' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'action',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The route action array.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'isFallback' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'isFallback',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 208,
            'startFilePos' => 1722,
            'endTokenPos' => 208,
            'endFilePos' => 1726,
          ),
        ),
        'docComment' => '/**
 * Indicates whether the route is a fallback route.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'controller' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'controller',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The controller instance.
 *
 * @var mixed
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'defaults' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'defaults',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 78,
            'endLine' => 78,
            'startTokenPos' => 226,
            'startFilePos' => 1933,
            'endTokenPos' => 227,
            'endFilePos' => 1934,
          ),
        ),
        'docComment' => '/**
 * The default values for the route.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'wheres' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'wheres',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 85,
            'startTokenPos' => 238,
            'startFilePos' => 2044,
            'endTokenPos' => 239,
            'endFilePos' => 2045,
          ),
        ),
        'docComment' => '/**
 * The regular expression requirements.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parameters' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'parameters',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The array of matched parameters.
 *
 * @var array|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 92,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parameterNames' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'parameterNames',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The parameter names for the route.
 *
 * @var array|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'originalParameters' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'originalParameters',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The array of the matched parameters\' original values.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'withTrashedBindings' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'withTrashedBindings',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 113,
            'endLine' => 113,
            'startTokenPos' => 271,
            'startFilePos' => 2598,
            'endTokenPos' => 271,
            'endFilePos' => 2602,
          ),
        ),
        'docComment' => '/**
 * Indicates "trashed" models can be retrieved when resolving implicit model bindings for this route.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'lockSeconds' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'lockSeconds',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Indicates the maximum number of seconds the route should acquire a session lock for.
 *
 * @var int|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 120,
        'endLine' => 120,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'waitSeconds' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'waitSeconds',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * Indicates the maximum number of seconds the route should wait while attempting to acquire a session lock.
 *
 * @var int|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 127,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'computedMiddleware' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'computedMiddleware',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The computed gathered middleware.
 *
 * @var array|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'compiled' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'compiled',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The compiled version of the route.
 *
 * @var \\Symfony\\Component\\Routing\\CompiledRoute
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 141,
        'endLine' => 141,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'router' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'router',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The router instance used by the route.
 *
 * @var \\Illuminate\\Routing\\Router
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 148,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'container',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The container instance used by the route.
 *
 * @var \\Illuminate\\Container\\Container
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 155,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'bindingFields' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'bindingFields',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 162,
            'endLine' => 162,
            'startTokenPos' => 324,
            'startFilePos' => 3639,
            'endTokenPos' => 325,
            'endFilePos' => 3640,
          ),
        ),
        'docComment' => '/**
 * The fields that implicit binding should use for a given parameter.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 162,
        'endLine' => 162,
        'startColumn' => 5,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'validators' => 
      array (
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'name' => 'validators',
        'modifiers' => 17,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The validators used by the routes.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 169,
        'endLine' => 169,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'methods' => 
          array (
            'name' => 'methods',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 33,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'uri' => 
          array (
            'name' => 'uri',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 43,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 49,
            'endColumn' => 55,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a new Route instance.
 *
 * @param  array|string  $methods
 * @param  string  $uri
 * @param  \\Closure|array  $action
 */',
        'startLine' => 178,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parseAction' => 
      array (
        'name' => 'parseAction',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 36,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse the route action into a standard array.
 *
 * @param  callable|array|null  $action
 * @return array
 *
 * @throws \\UnexpectedValueException
 */',
        'startLine' => 199,
        'endLine' => 202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the route action and return the response.
 *
 * @return mixed
 */',
        'startLine' => 209,
        'endLine' => 222,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'isControllerAction' => 
      array (
        'name' => 'isControllerAction',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks whether the route\'s action is a controller.
 *
 * @return bool
 */',
        'startLine' => 229,
        'endLine' => 232,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'runCallable' => 
      array (
        'name' => 'runCallable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the route action and return the response.
 *
 * @return mixed
 */',
        'startLine' => 239,
        'endLine' => 254,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'isSerializedClosure' => 
      array (
        'name' => 'isSerializedClosure',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route action is a serialized Closure.
 *
 * @return bool
 */',
        'startLine' => 261,
        'endLine' => 264,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'runController' => 
      array (
        'name' => 'runController',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Run the route action and return the response.
 *
 * @return mixed
 *
 * @throws \\Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException
 */',
        'startLine' => 273,
        'endLine' => 278,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getController' => 
      array (
        'name' => 'getController',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the controller instance for the route.
 *
 * @return mixed
 *
 * @throws \\Illuminate\\Contracts\\Container\\BindingResolutionException
 */',
        'startLine' => 287,
        'endLine' => 300,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getControllerClass' => 
      array (
        'name' => 'getControllerClass',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the controller class used for the route.
 *
 * @return string|null
 */',
        'startLine' => 307,
        'endLine' => 310,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getControllerMethod' => 
      array (
        'name' => 'getControllerMethod',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the controller method used for the route.
 *
 * @return string
 */',
        'startLine' => 317,
        'endLine' => 320,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parseControllerCallback' => 
      array (
        'name' => 'parseControllerCallback',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse the controller.
 *
 * @return array
 */',
        'startLine' => 327,
        'endLine' => 330,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'flushController' => 
      array (
        'name' => 'flushController',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush the cached container instance on the route.
 *
 * @return void
 */',
        'startLine' => 337,
        'endLine' => 341,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'matches' => 
      array (
        'name' => 'matches',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 350,
            'endLine' => 350,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'includingMethod' => 
          array (
            'name' => 'includingMethod',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 350,
                'endLine' => 350,
                'startTokenPos' => 1046,
                'startFilePos' => 8387,
                'endTokenPos' => 1046,
                'endFilePos' => 8390,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 350,
            'endLine' => 350,
            'startColumn' => 47,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route matches a given request.
 *
 * @param  \\Illuminate\\Http\\Request  $request
 * @param  bool  $includingMethod
 * @return bool
 */',
        'startLine' => 350,
        'endLine' => 365,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'compileRoute' => 
      array (
        'name' => 'compileRoute',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compile the route into a Symfony CompiledRoute instance.
 *
 * @return \\Symfony\\Component\\Routing\\CompiledRoute
 */',
        'startLine' => 372,
        'endLine' => 379,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'bind' => 
      array (
        'name' => 'bind',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 387,
            'endLine' => 387,
            'startColumn' => 26,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Bind the route to a given request for execution.
 *
 * @param  \\Illuminate\\Http\\Request  $request
 * @return $this
 */',
        'startLine' => 387,
        'endLine' => 397,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'hasParameters' => 
      array (
        'name' => 'hasParameters',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route has parameters.
 *
 * @return bool
 */',
        'startLine' => 404,
        'endLine' => 407,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'hasParameter' => 
      array (
        'name' => 'hasParameter',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 415,
            'endLine' => 415,
            'startColumn' => 34,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine a given parameter exists from the route.
 *
 * @param  string  $name
 * @return bool
 */',
        'startLine' => 415,
        'endLine' => 422,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parameter' => 
      array (
        'name' => 'parameter',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 431,
            'endLine' => 431,
            'startColumn' => 31,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 431,
                'endLine' => 431,
                'startTokenPos' => 1333,
                'startFilePos' => 10220,
                'endTokenPos' => 1333,
                'endFilePos' => 10223,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 431,
            'endLine' => 431,
            'startColumn' => 38,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a given parameter from the route.
 *
 * @param  string  $name
 * @param  string|object|null  $default
 * @return string|object|null
 */',
        'startLine' => 431,
        'endLine' => 434,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'originalParameter' => 
      array (
        'name' => 'originalParameter',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 39,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 443,
                'endLine' => 443,
                'startTokenPos' => 1375,
                'startFilePos' => 10537,
                'endTokenPos' => 1375,
                'endFilePos' => 10540,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 46,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get original value of a given parameter from the route.
 *
 * @param  string  $name
 * @param  string|null  $default
 * @return string|null
 */',
        'startLine' => 443,
        'endLine' => 446,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setParameter' => 
      array (
        'name' => 'setParameter',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 455,
            'endLine' => 455,
            'startColumn' => 34,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 455,
            'endLine' => 455,
            'startColumn' => 41,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a parameter to the given value.
 *
 * @param  string  $name
 * @param  string|object|null  $value
 * @return void
 */',
        'startLine' => 455,
        'endLine' => 460,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'forgetParameter' => 
      array (
        'name' => 'forgetParameter',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 37,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Unset a parameter on the route if it is set.
 *
 * @param  string  $name
 * @return void
 */',
        'startLine' => 468,
        'endLine' => 473,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parameters' => 
      array (
        'name' => 'parameters',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the key / value list of parameters for the route.
 *
 * @return array
 *
 * @throws \\LogicException
 */',
        'startLine' => 482,
        'endLine' => 489,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'originalParameters' => 
      array (
        'name' => 'originalParameters',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the key / value list of original parameters for the route.
 *
 * @return array
 *
 * @throws \\LogicException
 */',
        'startLine' => 498,
        'endLine' => 505,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parametersWithoutNulls' => 
      array (
        'name' => 'parametersWithoutNulls',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the key / value list of parameters without null values.
 *
 * @return array
 */',
        'startLine' => 512,
        'endLine' => 515,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parameterNames' => 
      array (
        'name' => 'parameterNames',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all of the parameter names for the route.
 *
 * @return array
 */',
        'startLine' => 522,
        'endLine' => 525,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'compileParameterNames' => 
      array (
        'name' => 'compileParameterNames',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the parameter names for the route.
 *
 * @return array
 */',
        'startLine' => 532,
        'endLine' => 537,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'signatureParameters' => 
      array (
        'name' => 'signatureParameters',
        'parameters' => 
        array (
          'conditions' => 
          array (
            'name' => 'conditions',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 545,
                'endLine' => 545,
                'startTokenPos' => 1715,
                'startFilePos' => 12867,
                'endTokenPos' => 1716,
                'endFilePos' => 12868,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 545,
            'endLine' => 545,
            'startColumn' => 41,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the parameters that are listed in the route / controller signature.
 *
 * @param  array  $conditions
 * @return array
 */',
        'startLine' => 545,
        'endLine' => 552,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'bindingFieldFor' => 
      array (
        'name' => 'bindingFieldFor',
        'parameters' => 
        array (
          'parameter' => 
          array (
            'name' => 'parameter',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 560,
            'endLine' => 560,
            'startColumn' => 37,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the binding field for the given parameter.
 *
 * @param  string|int  $parameter
 * @return string|null
 */',
        'startLine' => 560,
        'endLine' => 565,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'bindingFields' => 
      array (
        'name' => 'bindingFields',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the binding fields for the route.
 *
 * @return array
 */',
        'startLine' => 572,
        'endLine' => 575,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setBindingFields' => 
      array (
        'name' => 'setBindingFields',
        'parameters' => 
        array (
          'bindingFields' => 
          array (
            'name' => 'bindingFields',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 583,
            'endLine' => 583,
            'startColumn' => 38,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the binding fields for the route.
 *
 * @param  array  $bindingFields
 * @return $this
 */',
        'startLine' => 583,
        'endLine' => 588,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parentOfParameter' => 
      array (
        'name' => 'parentOfParameter',
        'parameters' => 
        array (
          'parameter' => 
          array (
            'name' => 'parameter',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 596,
            'endLine' => 596,
            'startColumn' => 39,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the parent parameter of the given parameter.
 *
 * @param  string  $parameter
 * @return string|null
 */',
        'startLine' => 596,
        'endLine' => 605,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'withTrashed' => 
      array (
        'name' => 'withTrashed',
        'parameters' => 
        array (
          'withTrashed' => 
          array (
            'name' => 'withTrashed',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 613,
                'endLine' => 613,
                'startTokenPos' => 1960,
                'startFilePos' => 14499,
                'endTokenPos' => 1960,
                'endFilePos' => 14502,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 613,
            'endLine' => 613,
            'startColumn' => 33,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Allow "trashed" models to be retrieved when resolving implicit model bindings for this route.
 *
 * @param  bool  $withTrashed
 * @return $this
 */',
        'startLine' => 613,
        'endLine' => 618,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'allowsTrashedBindings' => 
      array (
        'name' => 'allowsTrashedBindings',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determines if the route allows "trashed" models to be retrieved when resolving implicit model bindings.
 *
 * @return bool
 */',
        'startLine' => 625,
        'endLine' => 628,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'defaults' => 
      array (
        'name' => 'defaults',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 637,
            'endLine' => 637,
            'startColumn' => 30,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 637,
            'endLine' => 637,
            'startColumn' => 36,
            'endColumn' => 41,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a default value for the route.
 *
 * @param  string  $key
 * @param  mixed  $value
 * @return $this
 */',
        'startLine' => 637,
        'endLine' => 642,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setDefaults' => 
      array (
        'name' => 'setDefaults',
        'parameters' => 
        array (
          'defaults' => 
          array (
            'name' => 'defaults',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 650,
            'endLine' => 650,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the default values for the route.
 *
 * @param  array  $defaults
 * @return $this
 */',
        'startLine' => 650,
        'endLine' => 655,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'where' => 
      array (
        'name' => 'where',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 664,
            'endLine' => 664,
            'startColumn' => 27,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expression' => 
          array (
            'name' => 'expression',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 664,
                'endLine' => 664,
                'startTokenPos' => 2083,
                'startFilePos' => 15575,
                'endTokenPos' => 2083,
                'endFilePos' => 15578,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 664,
            'endLine' => 664,
            'startColumn' => 34,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a regular expression requirement on the route.
 *
 * @param  array|string  $name
 * @param  string|null  $expression
 * @return $this
 */',
        'startLine' => 664,
        'endLine' => 671,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parseWhere' => 
      array (
        'name' => 'parseWhere',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 680,
            'endLine' => 680,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expression' => 
          array (
            'name' => 'expression',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 680,
            'endLine' => 680,
            'startColumn' => 42,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse arguments to the where method into an array.
 *
 * @param  array|string  $name
 * @param  string  $expression
 * @return array
 */',
        'startLine' => 680,
        'endLine' => 683,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setWheres' => 
      array (
        'name' => 'setWheres',
        'parameters' => 
        array (
          'wheres' => 
          array (
            'name' => 'wheres',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 691,
            'endLine' => 691,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set a list of regular expression requirements on the route.
 *
 * @param  array  $wheres
 * @return $this
 */',
        'startLine' => 691,
        'endLine' => 698,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'fallback' => 
      array (
        'name' => 'fallback',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Mark this route as a fallback route.
 *
 * @return $this
 */',
        'startLine' => 705,
        'endLine' => 710,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setFallback' => 
      array (
        'name' => 'setFallback',
        'parameters' => 
        array (
          'isFallback' => 
          array (
            'name' => 'isFallback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 718,
            'endLine' => 718,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the fallback value.
 *
 * @param  bool  $isFallback
 * @return $this
 */',
        'startLine' => 718,
        'endLine' => 723,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'methods' => 
      array (
        'name' => 'methods',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the HTTP verbs the route responds to.
 *
 * @return array
 */',
        'startLine' => 730,
        'endLine' => 733,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'httpOnly' => 
      array (
        'name' => 'httpOnly',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route only responds to HTTP requests.
 *
 * @return bool
 */',
        'startLine' => 740,
        'endLine' => 743,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'httpsOnly' => 
      array (
        'name' => 'httpsOnly',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route only responds to HTTPS requests.
 *
 * @return bool
 */',
        'startLine' => 750,
        'endLine' => 753,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'secure' => 
      array (
        'name' => 'secure',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route only responds to HTTPS requests.
 *
 * @return bool
 */',
        'startLine' => 760,
        'endLine' => 763,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'domain' => 
      array (
        'name' => 'domain',
        'parameters' => 
        array (
          'domain' => 
          array (
            'name' => 'domain',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 773,
                'endLine' => 773,
                'startTokenPos' => 2397,
                'startFilePos' => 17821,
                'endTokenPos' => 2397,
                'endFilePos' => 17824,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 773,
            'endLine' => 773,
            'startColumn' => 28,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get or set the domain for the route.
 *
 * @param  \\BackedEnum|string|null  $domain
 * @return ($domain is null ? string|null : $this)
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 773,
        'endLine' => 792,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getDomain' => 
      array (
        'name' => 'getDomain',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the domain defined for the route.
 *
 * @return string|null
 */',
        'startLine' => 799,
        'endLine' => 804,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getPrefix' => 
      array (
        'name' => 'getPrefix',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the prefix of the route instance.
 *
 * @return string|null
 */',
        'startLine' => 811,
        'endLine' => 814,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'prefix' => 
      array (
        'name' => 'prefix',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 822,
            'endLine' => 822,
            'startColumn' => 28,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add a prefix to the route URI.
 *
 * @param  string|null  $prefix
 * @return $this
 */',
        'startLine' => 822,
        'endLine' => 831,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'updatePrefixOnAction' => 
      array (
        'name' => 'updatePrefixOnAction',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 839,
            'endLine' => 839,
            'startColumn' => 45,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Update the "prefix" attribute on the action array.
 *
 * @param  string  $prefix
 * @return void
 */',
        'startLine' => 839,
        'endLine' => 844,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'uri' => 
      array (
        'name' => 'uri',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the URI associated with the route.
 *
 * @return string
 */',
        'startLine' => 851,
        'endLine' => 854,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setUri' => 
      array (
        'name' => 'setUri',
        'parameters' => 
        array (
          'uri' => 
          array (
            'name' => 'uri',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 862,
            'endLine' => 862,
            'startColumn' => 28,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the URI that the route responds to.
 *
 * @param  string  $uri
 * @return $this
 */',
        'startLine' => 862,
        'endLine' => 867,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'parseUri' => 
      array (
        'name' => 'parseUri',
        'parameters' => 
        array (
          'uri' => 
          array (
            'name' => 'uri',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 875,
            'endLine' => 875,
            'startColumn' => 33,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse the route URI and normalize / store any implicit binding fields.
 *
 * @param  string  $uri
 * @return string
 */',
        'startLine' => 875,
        'endLine' => 882,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the name of the route instance.
 *
 * @return string|null
 */',
        'startLine' => 889,
        'endLine' => 892,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'name' => 
      array (
        'name' => 'name',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 902,
            'endLine' => 902,
            'startColumn' => 26,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add or change the route name.
 *
 * @param  \\BackedEnum|string  $name
 * @return $this
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 902,
        'endLine' => 911,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'named' => 
      array (
        'name' => 'named',
        'parameters' => 
        array (
          'patterns' => 
          array (
            'name' => 'patterns',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 919,
            'endLine' => 919,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine whether the route\'s name matches the given patterns.
 *
 * @param  mixed  ...$patterns
 * @return bool
 */',
        'startLine' => 919,
        'endLine' => 932,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'uses' => 
      array (
        'name' => 'uses',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 940,
            'endLine' => 940,
            'startColumn' => 26,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the handler for the route.
 *
 * @param  \\Closure|array|string  $action
 * @return $this
 */',
        'startLine' => 940,
        'endLine' => 952,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'addGroupNamespaceToStringUses' => 
      array (
        'name' => 'addGroupNamespaceToStringUses',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 960,
            'endLine' => 960,
            'startColumn' => 54,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse a string based action for the "uses" fluent method.
 *
 * @param  string  $action
 * @return string
 */',
        'startLine' => 960,
        'endLine' => 969,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getActionName' => 
      array (
        'name' => 'getActionName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the action name for the route.
 *
 * @return string
 */',
        'startLine' => 976,
        'endLine' => 979,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getActionMethod' => 
      array (
        'name' => 'getActionMethod',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the method name of the route action.
 *
 * @return string
 */',
        'startLine' => 986,
        'endLine' => 989,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getAction' => 
      array (
        'name' => 'getAction',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 997,
                'endLine' => 997,
                'startTokenPos' => 3339,
                'startFilePos' => 23009,
                'endTokenPos' => 3339,
                'endFilePos' => 23012,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 997,
            'endLine' => 997,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the action array or one of its properties for the route.
 *
 * @param  string|null  $key
 * @return mixed
 */',
        'startLine' => 997,
        'endLine' => 1000,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setAction' => 
      array (
        'name' => 'setAction',
        'parameters' => 
        array (
          'action' => 
          array (
            'name' => 'action',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1008,
            'endLine' => 1008,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the action array for the route.
 *
 * @param  array  $action
 * @return $this
 */',
        'startLine' => 1008,
        'endLine' => 1023,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getMissing' => 
      array (
        'name' => 'getMissing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the value of the action that should be taken on a missing model exception.
 *
 * @return \\Closure|null
 */',
        'startLine' => 1030,
        'endLine' => 1045,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'missing' => 
      array (
        'name' => 'missing',
        'parameters' => 
        array (
          'missing' => 
          array (
            'name' => 'missing',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1053,
            'endLine' => 1053,
            'startColumn' => 29,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Define the callable that should be invoked on a missing model exception.
 *
 * @param  \\Closure  $missing
 * @return $this
 */',
        'startLine' => 1053,
        'endLine' => 1058,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'gatherMiddleware' => 
      array (
        'name' => 'gatherMiddleware',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all middleware, including the ones from the controller.
 *
 * @return array
 */',
        'startLine' => 1065,
        'endLine' => 1076,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'middleware' => 
      array (
        'name' => 'middleware',
        'parameters' => 
        array (
          'middleware' => 
          array (
            'name' => 'middleware',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1084,
                'endLine' => 1084,
                'startTokenPos' => 3711,
                'startFilePos' => 25466,
                'endTokenPos' => 3711,
                'endFilePos' => 25469,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1084,
            'endLine' => 1084,
            'startColumn' => 32,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get or set the middlewares attached to the route.
 *
 * @param  array|string|null  $middleware
 * @return ($middleware is null ? array : $this)
 */',
        'startLine' => 1084,
        'endLine' => 1103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'can' => 
      array (
        'name' => 'can',
        'parameters' => 
        array (
          'ability' => 
          array (
            'name' => 'ability',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1112,
            'endLine' => 1112,
            'startColumn' => 25,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'models' => 
          array (
            'name' => 'models',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 1112,
                'endLine' => 1112,
                'startTokenPos' => 3858,
                'startFilePos' => 26234,
                'endTokenPos' => 3859,
                'endFilePos' => 26235,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1112,
            'endLine' => 1112,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify that the "Authorize" / "can" middleware should be applied to the route with the given options.
 *
 * @param  \\UnitEnum|string  $ability
 * @param  array|string  $models
 * @return $this
 */',
        'startLine' => 1112,
        'endLine' => 1119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'controllerMiddleware' => 
      array (
        'name' => 'controllerMiddleware',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the middleware for the route\'s controller.
 *
 * @return array
 */',
        'startLine' => 1126,
        'endLine' => 1150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'staticallyProvidedControllerMiddleware' => 
      array (
        'name' => 'staticallyProvidedControllerMiddleware',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1159,
            'endLine' => 1159,
            'startColumn' => 63,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1159,
            'endLine' => 1159,
            'startColumn' => 78,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the statically provided controller middleware for the given class and method.
 *
 * @param  string  $class
 * @param  string  $method
 * @return array
 */',
        'startLine' => 1159,
        'endLine' => 1177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'attributeProvidedControllerMiddleware' => 
      array (
        'name' => 'attributeProvidedControllerMiddleware',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1184,
            'endLine' => 1184,
            'startColumn' => 62,
            'endColumn' => 74,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1184,
            'endLine' => 1184,
            'startColumn' => 77,
            'endColumn' => 90,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the attribute provided controller middleware for the given class and method.
 *
 * @return array
 */',
        'startLine' => 1184,
        'endLine' => 1221,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'withoutMiddleware' => 
      array (
        'name' => 'withoutMiddleware',
        'parameters' => 
        array (
          'middleware' => 
          array (
            'name' => 'middleware',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1229,
            'endLine' => 1229,
            'startColumn' => 39,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify middleware that should be removed from the given route.
 *
 * @param  array|string  $middleware
 * @return $this
 */',
        'startLine' => 1229,
        'endLine' => 1236,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'excludedMiddleware' => 
      array (
        'name' => 'excludedMiddleware',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the middleware that should be removed from the route.
 *
 * @return array
 */',
        'startLine' => 1243,
        'endLine' => 1246,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'scopeBindings' => 
      array (
        'name' => 'scopeBindings',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the route should enforce scoping of multiple implicit Eloquent bindings.
 *
 * @return $this
 */',
        'startLine' => 1253,
        'endLine' => 1258,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'withoutScopedBindings' => 
      array (
        'name' => 'withoutScopedBindings',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the route should not enforce scoping of multiple implicit Eloquent bindings.
 *
 * @return $this
 */',
        'startLine' => 1265,
        'endLine' => 1270,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'enforcesScopedBindings' => 
      array (
        'name' => 'enforcesScopedBindings',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route should enforce scoping of multiple implicit Eloquent bindings.
 *
 * @return bool
 */',
        'startLine' => 1277,
        'endLine' => 1280,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'preventsScopedBindings' => 
      array (
        'name' => 'preventsScopedBindings',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the route should prevent scoping of multiple implicit Eloquent bindings.
 *
 * @return bool
 */',
        'startLine' => 1287,
        'endLine' => 1290,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'block' => 
      array (
        'name' => 'block',
        'parameters' => 
        array (
          'lockSeconds' => 
          array (
            'name' => 'lockSeconds',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 1299,
                'endLine' => 1299,
                'startTokenPos' => 4748,
                'startFilePos' => 31823,
                'endTokenPos' => 4748,
                'endFilePos' => 31824,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1299,
            'endLine' => 1299,
            'startColumn' => 27,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'waitSeconds' => 
          array (
            'name' => 'waitSeconds',
            'default' => 
            array (
              'code' => '10',
              'attributes' => 
              array (
                'startLine' => 1299,
                'endLine' => 1299,
                'startTokenPos' => 4755,
                'startFilePos' => 31842,
                'endTokenPos' => 4755,
                'endFilePos' => 31843,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1299,
            'endLine' => 1299,
            'startColumn' => 46,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify that the route should not allow concurrent requests from the same session.
 *
 * @param  int|null  $lockSeconds
 * @param  int|null  $waitSeconds
 * @return $this
 */',
        'startLine' => 1299,
        'endLine' => 1305,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'withoutBlocking' => 
      array (
        'name' => 'withoutBlocking',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Specify that the route should allow concurrent requests from the same session.
 *
 * @return $this
 */',
        'startLine' => 1312,
        'endLine' => 1315,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'locksFor' => 
      array (
        'name' => 'locksFor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the maximum number of seconds the route\'s session lock should be held for.
 *
 * @return int|null
 */',
        'startLine' => 1322,
        'endLine' => 1325,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'waitsFor' => 
      array (
        'name' => 'waitsFor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the maximum number of seconds to wait while attempting to acquire a session lock.
 *
 * @return int|null
 */',
        'startLine' => 1332,
        'endLine' => 1335,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'metadata' => 
      array (
        'name' => 'metadata',
        'parameters' => 
        array (
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1343,
            'endLine' => 1343,
            'startColumn' => 30,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Add metadata to the route.
 *
 * @param  array  $metadata
 * @return $this
 */',
        'startLine' => 1343,
        'endLine' => 1351,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getMetadata' => 
      array (
        'name' => 'getMetadata',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1360,
                'endLine' => 1360,
                'startTokenPos' => 4920,
                'startFilePos' => 33179,
                'endTokenPos' => 4920,
                'endFilePos' => 33182,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1360,
            'endLine' => 1360,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 1360,
                'endLine' => 1360,
                'startTokenPos' => 4927,
                'startFilePos' => 33196,
                'endTokenPos' => 4927,
                'endFilePos' => 33199,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1360,
            'endLine' => 1360,
            'startColumn' => 46,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get metadata for the route.
 *
 * @param  string|null  $key
 * @param  mixed  $default
 * @return ($key is null ? array<array-key, mixed> : mixed)
 */',
        'startLine' => 1360,
        'endLine' => 1365,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setMetadata' => 
      array (
        'name' => 'setMetadata',
        'parameters' => 
        array (
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1373,
            'endLine' => 1373,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the metadata for the route, replacing any existing metadata.
 *
 * @param  array  $metadata
 * @return $this
 */',
        'startLine' => 1373,
        'endLine' => 1378,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'controllerDispatcher' => 
      array (
        'name' => 'controllerDispatcher',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the dispatcher for the route\'s controller.
 *
 * @return \\Illuminate\\Routing\\Contracts\\ControllerDispatcher
 *
 * @throws \\Illuminate\\Contracts\\Container\\BindingResolutionException
 */',
        'startLine' => 1387,
        'endLine' => 1394,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getValidators' => 
      array (
        'name' => 'getValidators',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the route validators for the instance.
 *
 * @return array
 */',
        'startLine' => 1401,
        'endLine' => 1410,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'toSymfonyRoute' => 
      array (
        'name' => 'toSymfonyRoute',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Convert the route to a Symfony route.
 *
 * @return \\Symfony\\Component\\Routing\\Route
 */',
        'startLine' => 1417,
        'endLine' => 1424,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getOptionalParameterNames' => 
      array (
        'name' => 'getOptionalParameterNames',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the optional parameter names for the route.
 *
 * @return array<string, null>
 */',
        'startLine' => 1431,
        'endLine' => 1436,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'getCompiled' => 
      array (
        'name' => 'getCompiled',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the compiled version of the route.
 *
 * @return \\Symfony\\Component\\Routing\\CompiledRoute
 */',
        'startLine' => 1443,
        'endLine' => 1446,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setRouter' => 
      array (
        'name' => 'setRouter',
        'parameters' => 
        array (
          'router' => 
          array (
            'name' => 'router',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Routing\\Router',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1454,
            'endLine' => 1454,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the router instance on the route.
 *
 * @param  \\Illuminate\\Routing\\Router  $router
 * @return $this
 */',
        'startLine' => 1454,
        'endLine' => 1459,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'setContainer' => 
      array (
        'name' => 'setContainer',
        'parameters' => 
        array (
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Container\\Container',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1467,
            'endLine' => 1467,
            'startColumn' => 34,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the container instance on the route.
 *
 * @param  \\Illuminate\\Container\\Container  $container
 * @return $this
 */',
        'startLine' => 1467,
        'endLine' => 1472,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      'prepareForSerialization' => 
      array (
        'name' => 'prepareForSerialization',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Prepare the route instance for serialization.
 *
 * @return void
 *
 * @throws \\LogicException
 */',
        'startLine' => 1481,
        'endLine' => 1498,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
      '__get' => 
      array (
        'name' => '__get',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1506,
            'endLine' => 1506,
            'startColumn' => 27,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Dynamically access route parameters.
 *
 * @param  string  $key
 * @return mixed
 */',
        'startLine' => 1506,
        'endLine' => 1509,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Routing',
        'declaringClassName' => 'Illuminate\\Routing\\Route',
        'implementingClassName' => 'Illuminate\\Routing\\Route',
        'currentClassName' => 'Illuminate\\Routing\\Route',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));