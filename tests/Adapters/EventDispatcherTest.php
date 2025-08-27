<?php
/**
 * @package php-tmdb\laravel
 * @author Mark Redeman <markredeman@gmail.com>
 * @copyright (c) 2014, Mark Redeman
 */
namespace Tests\Tmdb\Laravel\Adapters;

use Tmdb\Laravel\Adapters\EventDispatcherLaravel as AdapterDispatcher;

class EventDispatcherTest extends AbstractEventDispatcherTest
{
    protected function createEventDispatcher()
    {
        $this->laravel = $this->prophesize('Illuminate\Events\Dispatcher');
        $this->symfony = $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcher');

        return new AdapterDispatcher(
            $this->laravel->reveal(),
            $this->symfony->reveal()
        );
    }
}
