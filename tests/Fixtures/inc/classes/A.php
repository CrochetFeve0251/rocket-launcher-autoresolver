<?php

namespace Fixtures\inc\classes;

class A
{
    /**
     * @var B
     */
    protected $b;
    /**
     * @var C
     */
    protected $c;

    /**
     * @param B $b
     * @param C $c
     */
    public function __construct(B $b, C $c)
    {
        $this->b = $b;
        $this->c = $c;
    }

}