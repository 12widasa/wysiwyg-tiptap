<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class ContentController extends Controller
{
    public function create()
    {
        return view('content.create');
    }

public function store(Request $request)
{
    $request->validate([
        'description' => ['required', 'string'],
    ]);

    $dirty = $request->input('description');

    // Ekstrak semua figure sebelum purifier
    $figures = [];
    $dirty = preg_replace_callback(
        '/<figure[^>]*>.*?<\/figure>/si',
        function ($matches) use (&$figures) {
            $key = 'FIGUREPH' . count($figures) . 'ENDPH';
            $figures[$key] = $matches[0];
            // Taruh key sebagai teks biasa dalam paragraf
            return '<p>' . $key . '</p>';
        },
        $dirty
    );

    // Sanitasi sisanya dengan purifier
    $clean = clean($dirty);

    // Kembalikan figure — cari <p>FIGUREPH0ENDPH</p>
    foreach ($figures as $key => $figure) {
        $safeFigure = preg_replace('/on\w+="[^"]*"/i', '', $figure);
        $safeFigure = preg_replace('/javascript:/i', '', $safeFigure);
        $clean = str_replace('<p>' . $key . '</p>', $safeFigure, $clean);
    }

    $content = Content::create(['description' => $clean]);

    return redirect()->route('content.show', $content->id);
}

    public function show(Content $content)
    {
        return view('content.show', compact('content'));
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('content-images', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
