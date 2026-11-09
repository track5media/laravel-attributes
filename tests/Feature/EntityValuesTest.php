<?php

declare(strict_types=1);

namespace Rinvex\Attributes\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Rinvex\Attributes\Tests\Stubs\User;
use Rinvex\Attributes\Support\ValueCollection;

class EntityValuesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->attribute('color', 'varchar');
        $this->attribute('age', 'integer');
        $this->attribute('active', 'bool');
        $this->attribute('bio', 'text');
        $this->attribute('born_at', 'datetime');
        $this->attribute('tags', 'varchar', ['is_collection' => true]);

        User::flushEntityAttributes();
    }

    protected function tearDown(): void
    {
        User::flushEntityAttributes();

        parent::tearDown();
    }

    #[Test]
    public function it_registers_entity_attributes_as_relations(): void
    {
        $user = new User();

        $this->assertEqualsCanonicalizing(
            ['color', 'age', 'active', 'bio', 'born_at', 'tags'],
            $user->attributes()->keys()->all()
        );
        $this->assertTrue($user->isEntityAttribute('color'));
        $this->assertFalse($user->isEntityAttribute('email'));
        $this->assertArrayHasKey('color', $user->getEntityAttributeRelations());
    }

    #[Test]
    public function it_writes_and_reads_values_of_every_type(): void
    {
        $user = $this->user([
            'color' => 'red',
            'age' => 42,
            'active' => true,
            'bio' => str_repeat('Long text. ', 100),
            'born_at' => '1984-02-29 12:30:00',
        ]);

        $this->assertDatabaseHas('attribute_varchar_values', ['entity_id' => $user->id, 'entity_type' => User::class, 'content' => 'red']);
        $this->assertDatabaseHas('attribute_integer_values', ['entity_id' => $user->id, 'content' => 42]);
        $this->assertDatabaseHas('attribute_boolean_values', ['entity_id' => $user->id, 'content' => true]);
        $this->assertDatabaseCount('attribute_text_values', 1);
        $this->assertDatabaseCount('attribute_datetime_values', 1);

        $fresh = User::find($user->id);

        $this->assertSame('red', $fresh->color);
        $this->assertSame(42, $fresh->age);
        $this->assertTrue($fresh->active);
        $this->assertSame(str_repeat('Long text. ', 100), $fresh->bio);
        $this->assertInstanceOf(Carbon::class, $fresh->born_at);
        $this->assertSame('1984-02-29 12:30:00', $fresh->born_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_fills_entity_attributes_through_mass_assignment(): void
    {
        $user = User::create($this->userData(['color' => 'blue', 'age' => 7]));

        $fresh = User::find($user->id);

        $this->assertSame('blue', $fresh->color);
        $this->assertSame(7, $fresh->age);
    }

    #[Test]
    public function it_updates_existing_values_in_place(): void
    {
        $user = $this->user(['color' => 'red']);

        $fresh = User::find($user->id);
        $fresh->color = 'green';
        $fresh->save();

        $this->assertDatabaseCount('attribute_varchar_values', 1);
        $this->assertDatabaseHas('attribute_varchar_values', ['entity_id' => $user->id, 'content' => 'green']);
        $this->assertSame('green', User::find($user->id)->color);
    }

    #[Test]
    public function it_deletes_values_set_to_null(): void
    {
        $user = $this->user(['color' => 'red', 'age' => 3]);

        $fresh = User::find($user->id);
        $fresh->color = null;
        $fresh->save();

        $this->assertDatabaseCount('attribute_varchar_values', 0);
        $this->assertDatabaseCount('attribute_integer_values', 1);
        $this->assertNull(User::find($user->id)->color);
    }

    #[Test]
    public function it_returns_null_for_unset_values(): void
    {
        $user = $this->user();

        $this->assertNull(User::find($user->id)->color);
        $this->assertDatabaseCount('attribute_varchar_values', 0);
    }

    #[Test]
    public function it_writes_and_replaces_collection_values(): void
    {
        $user = $this->user(['tags' => ['php', 'laravel']]);

        $fresh = User::find($user->id);

        $this->assertInstanceOf(Collection::class, $fresh->tags);
        $this->assertEqualsCanonicalizing(['php', 'laravel'], $fresh->tags->all());
        $this->assertInstanceOf(ValueCollection::class, $fresh->rawTagsObject);

        $fresh->tags = ['eav', null, 'eloquent'];
        $fresh->save();

        $this->assertDatabaseCount('attribute_varchar_values', 2);
        $this->assertEqualsCanonicalizing(['eav', 'eloquent'], User::find($user->id)->tags->all());
    }

    #[Test]
    public function it_appends_to_collection_values(): void
    {
        $user = $this->user(['tags' => ['php']]);

        $fresh = User::find($user->id);
        $fresh->rawTagsObject->add('laravel', 'eloquent');
        $fresh->save();

        $this->assertEqualsCanonicalizing(['php', 'laravel', 'eloquent'], User::find($user->id)->tags->all());
    }

    #[Test]
    public function it_eager_loads_all_values_with_eav(): void
    {
        $this->user(['color' => 'red', 'tags' => ['a', 'b']]);
        $this->user(['color' => 'blue', 'tags' => ['c']]);

        $users = User::with('eav')->orderBy('id')->get();

        foreach (['color', 'age', 'active', 'bio', 'born_at', 'tags'] as $relation) {
            $this->assertTrue($users->first()->relationLoaded($relation), "Relation [{$relation}] was not eager loaded.");
        }

        $this->assertSame(['red', 'blue'], $users->pluck('color')->all());
        $this->assertEqualsCanonicalizing(['a', 'b'], $users->first()->tags->all());
        $this->assertSame(['c'], $users->last()->tags->all());
    }

    #[Test]
    public function it_includes_loaded_values_when_converted_to_array(): void
    {
        $user = $this->user(['color' => 'red', 'age' => 5]);

        $array = User::with('eav')->find($user->id)->toArray();

        $this->assertSame('red', $array['color']);
        $this->assertSame(5, $array['age']);
        $this->assertNull($array['bio']);
        $this->assertSame($user->email, $array['email']);
    }

    #[Test]
    public function it_scopes_queries_by_attribute_value(): void
    {
        $red = $this->user(['color' => 'red']);
        $this->user(['color' => 'blue']);

        // Called through the builder: Eloquent's Model::hasAttribute() shadows the static call.
        $this->assertSame([$red->id], User::query()->hasAttribute('color', 'red')->pluck('id')->all());
    }

    #[Test]
    public function it_deletes_loaded_values_when_the_entity_is_deleted(): void
    {
        $user = $this->user(['color' => 'red', 'tags' => ['a', 'b']]);
        $this->user(['color' => 'blue']);

        User::with('eav')->find($user->id)->delete();

        $this->assertDatabaseCount('attribute_varchar_values', 1);
        $this->assertDatabaseHas('attribute_varchar_values', ['content' => 'blue']);
    }

    #[Test]
    public function it_survives_serialization(): void
    {
        $user = $this->user(['color' => 'red', 'tags' => ['a']]);

        /** @var User $restored */
        $restored = unserialize(serialize(User::find($user->id)));

        $this->assertSame('red', $restored->color);
        $this->assertSame(['a'], $restored->tags->all());
        $this->assertSame([$user->id], $restored->color()->pluck('entity_id')->all());
    }

    protected function attribute(string $slug, string $type, array $extra = [])
    {
        return app('rinvex.attributes.attribute')->create(array_merge([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'type' => $type,
            'entities' => [User::class],
        ], $extra));
    }

    protected function userData(array $attributes = []): array
    {
        static $count = 0;
        $count++;

        return array_merge([
            'name' => "User {$count}",
            'email' => "user{$count}@example.com",
            'password' => 'secret',
        ], $attributes);
    }

    protected function user(array $values = []): User
    {
        $user = new User();
        $user->fill($this->userData());

        foreach ($values as $key => $value) {
            $user->{$key} = $value;
        }

        $user->save();

        return $user;
    }
}
