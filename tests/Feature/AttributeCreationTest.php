<?php

declare(strict_types=1);

namespace Rinvex\Attributes\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Rinvex\Attributes\Tests\Stubs\User;

class AttributeCreationTest extends TestCase
{
    #[Test]
    public function it_creates_a_new_attribute()
    {
        $attribute = $this->createAttribute();

        $this->assertDatabaseHas('attributes', ['slug' => 'count', 'type' => 'integer']);
        $this->assertDatabaseHas('attribute_entity', ['attribute_id' => $attribute->id, 'entity_type' => User::class]);
    }

    #[Test]
    public function it_ensures_snake_case_slugs()
    {
        $attribute = $this->createAttribute(['name' => 'Foo Bar']);

        $this->assertEquals('foo_bar', $attribute->slug);
        $this->assertDatabaseHas('attributes', ['slug' => 'foo_bar', 'type' => 'integer']);
    }

    #[Test]
    public function it_ensures_snake_case_slugs_even_if_dashed_slugs_provided()
    {
        $attribute = $this->createAttribute(['slug' => 'foo-bar']);

        $this->assertEquals('foo_bar', $attribute->slug);
    }

    #[Test]
    public function it_ensures_unique_slugs()
    {
        $this->createAttribute(['name' => 'foo']);
        $this->createAttribute(['name' => 'foo']);

        $this->assertDatabaseHas('attributes', ['slug' => 'foo_1']);
    }

    #[Test]
    public function it_ensures_unique_slugs_even_if_slugs_explicitly_provided()
    {
        $this->createAttribute(['slug' => 'foo']);
        $this->createAttribute(['slug' => 'foo']);

        $this->assertDatabaseHas('attributes', ['slug' => 'foo_1']);
    }

    protected function createAttribute($attributes = [])
    {
        return app('rinvex.attributes.attribute')->create(array_merge([
            'type' => 'integer',
            'name' => 'Count',
            'entities' => [User::class],
        ], $attributes));
    }
}
