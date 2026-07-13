<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return response()->json([
            'faqs' => Faq::orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $faq = Faq::create($this->validated($request));

        return response()->json(['faq' => $faq], 201);
    }

    public function update(Request $request, int $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->update($this->validated($request));

        return response()->json(['faq' => $faq->fresh()]);
    }

    public function destroy(int $id)
    {
        Faq::findOrFail($id)->delete();

        return response()->json(['message' => 'FAQ deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'question_tl' => ['nullable', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'answer_tl' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:30'],
            'sort' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_published' => ['sometimes', 'boolean'],
        ]);
    }
}
