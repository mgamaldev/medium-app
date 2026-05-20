<?php

namespace App\Enums;

enum ArticleVisibility: string
{
    case PUBLIC = 'public';
    case FOLLOWERS_ONLY = 'followers_only';


}