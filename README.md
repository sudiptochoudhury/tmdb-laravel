# Laravel Package for TMDB API Wrapper

A Laravel package that provides easy access to the [php-tmdb/api](https://github.com/php-tmdb/api) TMDB (The Movie Database) API wrapper.
This package comes with a service provider that configures the `Tmdb\Client` and registers it to the IoC container.
Both Laravel 5 and 6 are supported.

[![Latest Stable Version](http://poser.pugx.org/sudiptochoudhury/tmdb-laravel/v)](https://packagist.org/packages/sudiptochoudhury/tmdb-laravel) 
[![Total Downloads](http://poser.pugx.org/sudiptochoudhury/tmdb-laravel/downloads)](https://packagist.org/packages/sudiptochoudhury/tmdb-laravel) 
[![Latest Unstable Version](http://poser.pugx.org/sudiptochoudhury/tmdb-laravel/v/unstable)](https://packagist.org/packages/sudiptochoudhury/tmdb-laravel) 
[![License](http://poser.pugx.org/sudiptochoudhury/tmdb-laravel/license)](https://packagist.org/packages/sudiptochoudhury/tmdb-laravel) 
[![PHP Version Require](http://poser.pugx.org/sudiptochoudhury/tmdb-laravel/require/php)](https://packagist.org/packages/sudiptochoudhury/tmdb-laravel)
## Installation

Install Composer

```
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

Add the following to your require block in `composer.json` config

```
"sudiptochoudhury/tmdb-laravel": "^2.1"
```

or just run the following command in your project:

```
composer require sudiptochoudhury/tmdb-laravel
```

## Configuration

Add `config/app.php` (Laravel <= 8) the service provider:

```php
'providers' => array(
    // other service providers

    'Tmdb\Laravel\TmdbServiceProvider',
)
```

Then publish the configuration file:

### Laravel 5

```
php artisan vendor:publish --provider="Tmdb\Laravel\TmdbServiceProviderLaravel"
```

Next you can modify the generated configuration file `tmdb.php` accordingly.

That's all! Fire away!

## Usage

We can choose to either use the `Tmdb` Facade, or to use dependency injection.

### Facade example

The example below shows how you can use the `Tmdb` facade.
If you don't want to keep adding the `use Tmdb\Laravel\Facades\Tmdb;` statement in your files, then you can also add the facade as an alias in `config/app.php` file.

```php
use Tmdb\Laravel\Facades\Tmdb; // optional for Laravel ≥5.5

class MoviesController {

    function show($id)
    {
        // returns information of a movie
        return Tmdb::getMoviesApi()->getMovie($id);
    }
}
```

### Dependency injection example

```php
use Tmdb\Repository\MovieRepository;

class MoviesController {

    private $movies;

    function __construct(MovieRepository $movies)
    {
        $this->movies = $movies;
    }

    function index()
    {
        // returns information of a movie
        return $this->movies->getPopular();
    }
}
```

### Listening to events

We can easily listen to events that are dispatched using the Laravel event dispatcher that we're familiar with.
The following example listens to any request that is made and logs a message.

```php
use Log;
use Event;
use Tmdb\Event\TmdbEvents;
use Tmdb\Event\RequestEvent;

Event::listen(TmdbEvents::REQUEST, function(RequestEvent $event) {
    Log::info("A request was made to TMDB");
    // do stuff with $event
});
```

In Laravel 5 instead of using the `Event` facade we could also have used the `EventServiceProvider` to register our event listener.

### Image helper

You can easily use the `ImageHelper` by using dependency injection. The following example shows how to show the poster image of the 20 most popular movies.

```php
namespace App\Http\Controllers;

use Tmdb\Helper\ImageHelper;
use Tmdb\Repository\MovieRepository;

class WelcomeController extends Controller {

    private $movies;
    private $helper;

    public function __construct(MovieRepository $movies, ImageHelper $helper)
    {
        $this->movies = $movies;
        $this->helper = $helper;
    }

    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function index()
    {
        $popular = $this->movies->getPopular();

        foreach ($popular as $movie)
        {
            $image = $movie->getPosterImage();
            echo ($this->helper->getHtml($image, 'w154', 260, 420));
        }
    }

}
```

The `Configuration` used by the `Tmdb\Helper\ImageHelper` is automatically loaded by the IoC container.

### Registering plugins

Plugins can be registered in a service provider using the `boot()` method.

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tmdb\HttpClient\Plugin\LanguageFilterPlugin;

class TmdbServiceProvider extends ServiceProvider {

    /**
     * Add a Dutch language filter to the Tmdb client
     *
     * @return void
     */
    public function boot()
    {
        $plugin = new LanguageFilterPlugin('nl');
        $client = $this->app->make('Tmdb\Client');
        $client->getHttpClient()->addSubscriber($plugin);
    }

    /**
     * Register services
     * @return void
     */
    public function register()
    {
        // register any services that you need
    }
}
```

**For all all other interactions take a look at [php-tmdb/api](https://github.com/php-tmdb/api).**
