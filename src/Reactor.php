<?php

namespace ajf\Hanno;

use \SplQueue as Queue;

class Reactor implements \IteratorAggregate
{
    private $tasks;
    
    public function __construct()
    {
        $this->tasks = new Queue();
    }
    
    public function addTask(\Iterator $task)
    {
        $this->tasks->enqueue($task);
    }
    
    public function run()
    {
        while (!$this->tasks->isEmpty()) {
            $toQueue = new Queue();
            
            yield;
            
            while (!$this->tasks->isEmpty()) {
                $task = $this->tasks->dequeue();
                $task->next();
                /* Task hasn't yet finished, we'll requeue it */
                if ($task->valid()) {
                    $toQueue->enqueue($task);
                }
            }
            
            /* we requeue things separately so previous loop isn't infinite */
            while (!$toQueue->isEmpty()) {
                $this->tasks->enqueue($toQueue->dequeue());
            }
        }
    }
    
    public function getIterator()
    {
        return $this->run();
    }
}