<?php

declare(strict_types=1);

namespace Rinvex\Attributes\Providers;

use Illuminate\Support\ServiceProvider;
use Rinvex\Attributes\Models\Attribute;
use Rinvex\Support\Traits\ConsoleTools;
use Rinvex\Attributes\Models\AttributeEntity;
use Rinvex\Attributes\Console\Commands\MigrateCommand;
use Rinvex\Attributes\Console\Commands\PublishCommand;
use Rinvex\Attributes\Console\Commands\RollbackCommand;

class AttributesServiceProvider extends ServiceProvider
{
    use ConsoleTools;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected array $commands = [
        MigrateCommand::class => 'command.rinvex.attributes.migrate',
        PublishCommand::class => 'command.rinvex.attributes.publish',
        RollbackCommand::class => 'command.rinvex.attributes.rollback',
    ];

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(realpath(__DIR__.'/../../config/config.php'), 'rinvex.attributes');

        // Bind eloquent models to IoC container
        $this->registerModels([
            'rinvex.attributes.attribute' => Attribute::class,
            'rinvex.attributes.attribute_entity' => AttributeEntity::class,
        ]);

        // Register attributes entities
        $this->app->singleton('rinvex.attributes.entities', function ($app) {
            return collect();
        });

        // Register console commands
        $this->commands(array_keys($this->commands));
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        // Publish Resources
        $this->publishConfigFrom('config', 'rinvex/laravel-attributes');
        $this->publishesMigrations(['rinvex/laravel-attributes']);
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
