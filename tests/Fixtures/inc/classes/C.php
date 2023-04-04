<?php

namespace Fixtures\inc\classes;

class C
{
    protected $d;

    /**
     * @param $d
     */
    public function __construct(string $d)
    {
        $this->d = $d;
    }


}