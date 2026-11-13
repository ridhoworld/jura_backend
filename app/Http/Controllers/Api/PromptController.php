<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index()
    {
        $prompts = Prompt::all();
        $randomPrompt = Prompt::inRandomOrder()->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'today_prompt' => $randomPrompt,
                'prompts' => $prompts,
            ],
        ]);
    }
}

