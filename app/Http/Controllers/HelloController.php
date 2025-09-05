<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelloController extends Controller
{
    public function show(Request $request, string $name = null)
    {
        $data = $request->validate(
            [
                'greet' => 'sometimes| boolean'
            ]
        );
        $name = $name ?: 'Casto';
        $greet = $data['greet'] ?? true;

        return view('hello', [
            'title' => "Day one • Hello, $name",
            'name' => $name,
            'greet' => $greet
        ]);


    }
    public function api(Request $request)
    {
        $request->validate(['name' => 'required|string|max:50']);
        return response()->json([
            'message' => 'Hello ' . $request->string('name'),
            'ts' => now()->toIso8601String(),
        ], 200);
    }
}
