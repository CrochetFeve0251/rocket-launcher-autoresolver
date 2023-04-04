<?php

namespace Fixtures\inc\classes;

class F
{
    /**
     * @var D
     */
    protected $d;

    /**
     * @var A
     */
    protected $a;

    /**
     * @param D $d
     * @param A $a
     */
    public function __construct(D $d, A $a)
    {
        $this->d = $d;
        $this->a = $a;
    }


}