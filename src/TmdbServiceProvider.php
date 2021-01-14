<?php
/**
 * @package php-tmdb\laravel
 * @author Mark Redeman <markredeman@gmail.com>
 * @copyright (c) 2014, Mark Redeman
 */
namespace Tmdb\Laravel;

use Illuminate\Support\ServiceProvider;
use Tmdb\Laravel\TmdbServiceProviderLaravel5;
use Tmdb\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tmdb\Laravel\Adapters\EventDispatcherAdapter;
use Tmdb\Model\Configuration;
use Tmdb\Repository\ConfigurationRepository;
use Tmdb\Token\Api\ApiToken;

class TmdbServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Actual provider
     *
     * @var \Illuminate\Support\ServiceProvider
     */
    protected $provider;

    /**
     * Construct the TMDB service provider
     */
    public function __construct()
    {
        // Call the parent constructor with all provided arguments
        $arguments = func_get_args();
        call_user_func_array(
            [$this, 'parent::' . __FUNCTION__],
            $arguments
        );

        $this->registerProvider();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        return $this->provider->boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Configure any bindings that are version dependent
        $this->provider->register();

        // Let the IoC container be able to make a Symfony event dispatcher
        $this->app->bind(
            EventDispatcherInterface::class,
            EventDispatcher::class
        );

        // Setup default configurations for the Tmdb Client
        $this->app->singleton(Client::class, function() {
            $config = $this->provider->config();
            $options = $config['options'];

            // Use an Event Dispatcher that uses the Laravel event dispatcher
            $options['event_dispatcher'] = $this->app->make(EventDispatcherAdapter::class);
            // Add API token
            $options['api_token'] = $config['api_key'];

            // Register the client using the key and options from config
            return new Client([$options]);
        });

        // bind the configuration (used by the image helper)
        $this->app->bind(Configuration::class, function() {
            $configuration = $this->app->make(ConfigurationRepository::class);
            return $configuration->load();
        });
    }

    /**
     * Register the ServiceProvider
     */
    private function registerProvider()
    {
        $app = $this->app;
        $this->provider = new TmdbServiceProviderLaravel5($app);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('tmdb');
    }
}
