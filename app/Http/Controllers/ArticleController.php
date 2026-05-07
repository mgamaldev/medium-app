<?php

namespace App\Http\Controllers;

use App\Factories\ArticleFactory;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;

class ArticleController extends Controller
{

    public function __construct(protected ArticleFactory $articleFactory)
    {}

    public function store(Request $request)
    {
        try
        {
            $article = $this->articleFactory->create($request->type, $request->all());

            return response()->json($article,201);
        }
        catch(\Exception $e)
        {
            return response()->json(['Error' => $e->getMessage()], 422);
        }
    }


}
