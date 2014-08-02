What's Hanno?
=============

Hanno is an asynchronous event loop system for writing real-time applications in PHP. Unlike some similar systems, it is based around generators, avoiding the need for callbacks and greatly simplifying both reading and writing code.

As it uses generators, it requires at least PHP 5.5.

The name comes from the Japanese verb for to react, 反応する (*Han'nō suru*), a reference to the use of reactors and a nod to [ReactPHP](http://reactphp.org/). Because the Japanese word the name came from is pronounced *Han'nō*, you actually ought to pronounce "Hanno" with two separate 'n' sounds like "Han'no", but in practise everyone (including myself) is just going to say "Hanno" (/ˈhæ.no/).

How do I use it?
================

Hanno is a modern, autoloaded library that supports Composer and is available via Packagist. Thus, simply require the `"ajf\Hanno"` package and you're good to go.

Hanno's basic structural piece is a *task*. Tasks in Hanno are simply plain `Iterator`s which are iterated over until they complete. Typically, a generator is used to implement a task. The idea behind this is that you use `yield` to yield control to other tasks when you need to wait for something else, meaning you can efficiently do multiple things at once (handle multiple requests for example), without each getting in eachother's way and without needing multiple threads. To take a rather trivial example, the following generator produces a fairly useless task:

```php
    function counter() {
        for ($i = 1; $i <= 3; $i++) {
            yield;
            echo "$i\n";
        }
    }
```

In order to run multiple tasks at once, Hanno provides a `Reactor` class to handle multiple tasks. For example, here we create a reactor and add two counters to it:

```php
    use ajf\Hanno as Hanno;
    $reactor = new Hanno\Reactor;
    $reactor->addTask(counter());
    $reactor->addTask(counter());
```

For each step the reactor is run, it shall execute each task, run it for one iteration (i.e. call its `->next()` method), and if it is not finished (i.e., `->valid()` returns true), schedule it to be run next time. In order to run the reactor, we call `->run()` to get a task we can run, which we'll do manually:

```php
    $task = $reactor->run();
    while ($task->valid()) {
        $task->next();
    }
```

To save you the bother, you don't need to implementing task running yourself. The `Utils` class provides a static method, `run`, that loops and runs a task until it finishes:

```php
    Hanno\Utils::run($reactor->run());
```

Note that because running a reactor creates a task, you can in fact run reactors as tasks in reactors! For example, we could do this:

```php
    $reactor1 = new Hanno\Reactor;
    $reactor1->addTask(counter());
    $reactor1->addTask(counter());
    
    $reactor2 = new Hanno\Reactor;
    $reactor2->addTask(counter());
    
    $ueber_reactor = new Hanno\Reactor;
    $ueber_reactor->addTask($reactor1);
    $ueber_reactor->addTask($reactor2);
```

However, you can't nest a reactor inside itself (Bad Things<sup>TM</sup> *will* happen), so don't try to. ;)