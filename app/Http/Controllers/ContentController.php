<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function create()
    {
        return view('content.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => ['required', 'string', 'max:500000'],
        ]);

        // HTMLPurifier via mews/purifier — figure/figcaption kini di-allow
        // langsung di purifier.php, tidak perlu regex bypass lagi.
        $clean = clean($request->input('description'));

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
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',                                   // maks 5 MB
                'dimensions:max_width=4096,max_height=4096',  // cegah decompression bomb
            ],
        ]);

        // Nama file acak — hindari path traversal & filename injection
        $ext      = strtolower($request->file('image')->getClientOriginalExtension());
        $filename = Str::uuid() . '.' . $ext;
        $path     = $request->file('image')->storeAs('content-images', $filename, 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
