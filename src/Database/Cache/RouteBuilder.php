<?php

namespace Jinya\Router\Extensions\Database\Cache;

use Jinya\Database\Attributes\Column;
use Jinya\Database\Creatable;
use Jinya\Database\Deletable;
use Jinya\Database\Findable;
use Jinya\Database\Updatable;
use Jinya\Router\Attributes\Middlewares;
use Jinya\Router\Extensions\Database\Attributes\ApiRoute;
use Jinya\Router\Extensions\Database\Attributes\ApiRouteType;
use Psr\Http\Server\MiddlewareInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use SplFileInfo;

/**
 * @internal
 */
class RouteBuilder
{
    /** @var array{'class': ReflectionClass, 'attrs': ApiRoute[]}[] */
    private array $classes;

    public function __construct(private readonly string $entityDirectory)
    {
    }

    /**
     * Generates the routing table the given entity directory
     *
     * @return string
     * @throws ReflectionException
     */
    public function getRoutes(): string
    {
        $entities = $this->getClasses();
        $routes = <<<PHP
return function (\FastRoute\RouteCollector \$r) {
    \$handler = new \Jinya\Router\Extensions\Database\DatabaseRequestHandler();

PHP;

        foreach ($entities as $entity) {
            /** @var ApiRoute[] $attrs */
            $attrs = $entity['attrs'];
            /** @var ReflectionClass $class */
            $class = $entity['class'];

            $entityClass = $class->getName();
            $apiPathName = str_replace('\\', '-', $class->getShortName());
            $entityFields = var_export($this->getFieldDefinitions($entityClass), true);

            foreach ($attrs as $attr) {
                if ($attr->routeType === ApiRouteType::Findable) {
                    $middlewares = $this->getMiddlewares($attr);
                    $path = $attr->path ?? "/api/$apiPathName";
                    $routes .= <<<PHP
\$r->addRoute('GET', '$path', ['fn', function() {
    return \$handler->handleGetAllRequest(get_request(false), $entityClass);
}, $middlewares]);
\$r->addRoute('GET', '$path/{id}', ['fn', function(string|int \$id) {
    return \$handler->handleGetByIdRequest(get_request(false), $entityClass, \$id);
}, $middlewares]);
PHP;
                } elseif ($attr->routeType === ApiRouteType::Creatable) {
                    $middlewares = $this->getMiddlewares($attr);
                    $path = $attr->path ?? "/api/$apiPathName";
                    $routes .= <<<PHP
\$r->addRoute('POST', '$path', ['fn', function() {
    return \$handler->handleCreateRequest(get_request(true), $entityClass, $entityFields);
}, $middlewares]);
PHP;
                } elseif ($attr->routeType === ApiRouteType::Updatable) {
                    $middlewares = $this->getMiddlewares($attr);
                    $path = $attr->path ?? "/api/$apiPathName";
                    $routes .= <<<PHP
\$r->addRoute('PUT', '$path/{id}', ['fn', function(string|int \$id) {
    return \$handler->handleUpdateRequest(get_request(true), $entityClass, $entityFields, \$id);
}, $middlewares]);
PHP;
                } elseif ($attr->routeType === ApiRouteType::Deletable) {
                    $middlewares = $this->getMiddlewares($attr);
                    $path = $attr->path ?? "/api/$apiPathName";
                    $routes .= <<<PHP
\$r->addRoute('DELETE', '$path', ['fn', function(string|int \$id) {
    return \$handler->handleDeleteRequest(get_request(false), $entityClass, \$id);
}, $middlewares]);
PHP;
                }
            }
        }

        return $routes . <<<PHP
};
PHP;
    }

    /**
     * Gets the entities in the passed entity directory
     *
     * @return array{'class': ReflectionClass, 'attrs': ApiRoute[]}[]
     */
    private function getClasses(): array
    {
        if (!empty($this->classes)) {
            return $this->classes;
        }

        $this->classes = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->entityDirectory));
        foreach ($iterator as $file) {
            /** @var $file SplFileInfo */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $this->getClassNameFromFile($file);
                if (class_exists($className)) {
                    $reflectionClass = new ReflectionClass($className);
                    $apiRouteAttributes = $reflectionClass->getAttributes(
                        ApiRoute::class,
                        ReflectionAttribute::IS_INSTANCEOF
                    );

                    if (!empty($apiRouteAttributes)
                        && ($reflectionClass->implementsInterface(Findable::class)
                            || $reflectionClass->implementsInterface(Updatable::class)
                            || $reflectionClass->implementsInterface(Creatable::class)
                            || $reflectionClass->implementsInterface(Deletable::class))) {
                        $this->classes[] = [
                            'class' => $reflectionClass,
                            'attrs' => array_map(
                                static fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
                                $apiRouteAttributes
                            )
                        ];
                    }
                }
            }
        }

        return $this->classes;
    }

    /**
     * Gets the full class name of the given file
     *
     * @param string $file The file to get the class name for
     * @return class-string
     */
    private function getClassNameFromFile(SplFileInfo $file): string
    {
        if (!($file->isFile() && $file->isReadable())) {
            // @codeCoverageIgnoreStart
            return '';
            // @codeCoverageIgnoreEnd
        }

        $contents = file_get_contents($file->getPathname());
        if (!$contents) {
            // @codeCoverageIgnoreStart
            return '';
            // @codeCoverageIgnoreEnd
        }

        $namespace = '';
        $class = '';

        $gettingNamespace = false;
        $gettingClass = false;

        $tokens = token_get_all($contents);

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $gettingNamespace = true;
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $gettingClass = true;
            }

            if ($gettingNamespace === true) {
                if (is_array($token) && $token[0] === T_NAME_QUALIFIED) {
                    $namespace .= $token[1];
                } elseif ($token === ';') {
                    $gettingNamespace = false;
                }
            }

            if (($gettingClass === true) && is_array($token) && $token[0] === T_STRING) {
                $class = $token[1];
                break;
            }
        }

        /** @var class-string $classFqdn */
        $classFqdn = $namespace ? $namespace . '\\' . $class : $class;

        return $classFqdn;
    }

    /**
     * Gets the middlewares used for the given api route
     *
     * @param ApiRoute $apiRoute The api route to get the middlewares for
     * @return string
     * @throws ReflectionException
     */
    private function getMiddlewares(ApiRoute $apiRoute): string
    {
        $middlewares = [];
        foreach ($apiRoute->middlewares as $middleware) {
            $middlewareReflectionClass = new ReflectionClass($middleware);
            if ($middlewareReflectionClass->implementsInterface(MiddlewareInterface::class)) {
                $ctor = $middlewareReflectionClass->getConstructor();
                $parameter = [];
                if ($ctor) {
                    $ctorParams = $ctor->getParameters();
                    foreach ($ctorParams as $ctorParam) {
                        if ($middlewareReflectionClass->hasProperty($ctorParam->name)) {
                            $prop = $middlewareReflectionClass->getProperty($ctorParam->name);
                            $val = $prop->getValue($middleware);
                            if (is_string($val)) {
                                $parameter[] = "'$val'";
                            } else {
                                $parameter[] = $val;
                            }
                        }
                    }
                }

                $reflectionClassName = $middlewareReflectionClass->getName();
                $parameters = implode(', ', $parameter);
                $middlewares[] = "new $reflectionClassName($parameters)";
            }
        }

        return implode(', ', $middlewares);
    }

    /**
     * @param object|class-string<Creatable|Updatable> $class
     * @return array<string, array{default: mixed|null, required: bool|null, type: string}>
     * @throws ReflectionException
     */
    private function getFieldDefinitions(object|string $class): array
    {
        $fields = [];
        $reflectionClass = new ReflectionClass($class);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $cols = $property->getAttributes(Column::class);
            if (empty($cols)) {
                continue;
            }

            /** @var Column $col */
            $col = $cols[0]->newInstance();
            $propertyName = $property->getName();
            $fields[lcfirst($propertyName)] = [
                'required' => $col->defaultValue !== null && (bool)$property->getType()?->allowsNull(),
                'type' => $property->getType()?->getName() ?? 'mixed',
                'default' => $col->defaultValue
            ];
        }

        return $fields;
    }
}
