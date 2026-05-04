<?php

namespace App\ValueObjects;

class ReadTime {
    public function __construct(private int $readTime){
       if($readTime <= 0){
            throw new \Exception("read time shouldn't be less than 0"); 
        }
    }
}