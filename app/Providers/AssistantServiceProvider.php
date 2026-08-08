<?php

namespace App\Providers;

use Anthropic\Client;
use App\Assistant\Agent;
use App\Assistant\ToolRegistry;
use Illuminate\Support\ServiceProvider;

class AssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Read the key from config rather than letting the SDK fall back to
        // getenv(): config is cached in production, and env() returns null once
        // `config:cache` has run.
        $this->app->singleton(Client::class, fn () => new Client(
            apiKey: config('services.anthropic.key')
        ));

        $this->app->singleton(ToolRegistry::class);

        $this->app->singleton(Agent::class, fn ($app) => new Agent(
            $app->make(Client::class),
            $app->make(ToolRegistry::class),
        ));
    }
}
