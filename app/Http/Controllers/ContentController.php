<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentRequest;
use App\Models\Content;
use HTMLPurifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function __construct(private readonly HTMLPurifier $purifier) {}

    public function create()
    {
        return view('content.create');
    }

    public function store(StoreContentRequest $request)
    {
        try {
            $clean   = $this->purifier->purify($request->input('description'));
            $content = Content::create(['description' => $clean]);

            return redirect()->route('content.show', $content->id);
        } catch (\Throwable $e) {
            Log::error('ContentController::store failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['description' => 'Gagal menyimpan konten. Silakan coba lagi.']);
        }
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
                'max:5120',
                'dimensions:max_width=4096,max_height=4096',
            ],
        ]);

        try {
            $ext      = strtolower($request->file('image')->getClientOriginalExtension());
            $filename = Str::uuid() . '.' . $ext;
            $path     = $request->file('image')->storeAs('content-images', $filename, 'public');

            if (! $path) {
                throw new \RuntimeException('File gagal disimpan ke storage.');
            }

            return response()->json(['url' => asset('storage/' . $path)]);
        } catch (\Throwable $e) {
            Log::error('ContentController::uploadImage failed', ['error' => $e->getMessage()]);

            return response()->json(
                ['message' => 'Upload gagal. Periksa kapasitas storage atau coba lagi.'],
                500
            );
        }
    }
}
