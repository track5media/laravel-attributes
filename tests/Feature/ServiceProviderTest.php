<?php

declare(strict_types=1);

namespace Rinvex\Attributes\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Rinvex\Attributes\Providers\AttributesServiceProvider;

class ServiceProviderTest extends TestCase
{
    /** Get the service provider class. */
    protected function getServiceProviderClass(): string
    {
        return AttributesServiceProvider::class;
    }

    #[Test]
    public function it_is_a_service_provider()
    {
        $class = $this->getServiceProviderClass();

        $reflection = new ReflectionClass($class);

        $provider = new ReflectionClass(ServiceProvider::class);

        $msg = "Expected class '{$class}' to be a service provider.";

        $this->assertTrue($reflection->isSubclassOf($provider), $msg);
    }

    #[Test]
    public function it_has_provides_method()
    {
        $class = $this->getServiceProviderClass();
        $reflection = new ReflectionClass($class);

        $method = $reflection->getMethod('provides');
        $method->setAccessible(true);

        $msg = "Expected class '{$class}' to provide a valid list of services.";

        $this->assertIsArray($method->invoke(new $class(new Container())), $msg);
    }
}
