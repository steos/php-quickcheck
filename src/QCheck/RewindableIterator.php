<?php

namespace QCheck;

/**
 * Iterator decorator that remembers all seen values
 * to allow rewind. Used for wrapping PHP generators
 * to make them rewindable.
 *
 * @package QCheck
 */
class RewindableIterator implements \Iterator
{
    private $gen;
    private $it;
    public function __construct(\Iterator $gen)
    {
        $this->gen = $gen;
    }
    public function key()
    {
        return $this->it->key();
    }
    public function current()
    {
        return $this->it->current();
    }
    public function valid()
    {
        return $this->it->valid();
    }
    public function next()
    {
        $this->it->next();
        if ($this->gen->valid() && !$this->it->valid()) {
            $this->gen->next();
            if ($this->gen->valid()) {
                $this->it->append($this->gen->current());
            }
        }
    }
    public function rewind()
    {
        if ($this->it === null) {
            $this->it = new \ArrayIterator();
            $this->gen->rewind();
            if ($this->gen->valid()) {
                $this->it->append($this->gen->current());
            }
        } else {
            $this->it->rewind();
        }
    }
}
