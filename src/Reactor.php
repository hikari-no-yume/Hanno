<?php
namespace ajf\Hanno;

require_once '../vendor/autoload.php';

use \SplQueue as Queue;

class Reactor
{
    private $tasks;
    private $toQueue;
    
    public function __construct()
    {
        $this->tasks = new Queue();
        $this->toQueue = new Queue();
    }
    
    public function addTask(\Generator $task, $obj = NULL)
    {
        $this->toQueue->enqueue(($obj !== NULL) ? [$task, $obj] : $task);
    }
    
    public function run()
    {
        $task = $this->runAsTask();
        while ($task->valid()) {
            $task->next();
        }
    }
    
    public function runAsTask()
    {
        while (!$this->tasks->isEmpty() || !$this->toQueue->isEmpty()) {
            yield;
            
            while (!$this->tasks->isEmpty()) {
                $task = $this->tasks->dequeue();
                /* Tasks are sometimes of [$task, $value] format */
                if (is_array($task)) {
                    $pass_value = $task[1];
                    $task = $task[0];
                    $task->send($pass_value);
                } else {
                    $task->next();
                }
                $return_value = $task->current();
                $key = $task->key();
                
                /* Task is suspended until Awaitable finishes */
                if ($key === 'until') {
                    if (!($return_value instanceof Awaitable)) {
                        throw new \Exception("Special task key \"until\" must take an Awaitable as value");
                    }
                    $return_value->addListener(function ($obj = NULL) use ($task) {
                        $this->addTask($task, $obj);
                    });
                    /* Awaitables can provide an optional task to be scheduled */
                    if ($newTask = $return_value->getTask()) {
                        $this->addTask($newTask);
                    }
                /* 'until' is the only special string key, what's up? */
                } else if (is_string($key)) {
                    throw new \Exception("There is no special task key \"$key\"");
                } else {
                    /* Task hasn't yet finished, we'll requeue it */
                    if ($task->valid()) {
                        $this->toQueue->enqueue($task);
                    }
                }
            }
            
            /* we requeue things separately so previous loop isn't infinite */
            while (!$this->toQueue->isEmpty()) {
                $this->tasks->enqueue($this->toQueue->dequeue());
            }
        }
    }
}