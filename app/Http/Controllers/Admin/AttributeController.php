<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Store new attribute via AJAX popup
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'Vui lòng nhập tên thuộc tính.',
            'name.max' => 'Tên thuộc tính không vượt quá 100 ký tự.',
        ]);

        $code = ! empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['name'], '_');

        // Ensure unique code
        $originalCode = $code;
        $counter = 1;
        while (Attribute::where('code', $code)->exists()) {
            $code = $originalCode.'_'.$counter++;
        }

        $attribute = Attribute::create([
            'name' => $validated['name'],
            'code' => $code,
        ]);

        $attribute->load('values');

        return response()->json([
            'success' => true,
            'message' => 'Thêm thuộc tính thành công!',
            'attribute' => $attribute,
        ]);
    }

    /**
     * Update attribute via AJAX popup
     */
    public function update(Request $request, Attribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'Vui lòng nhập tên thuộc tính.',
        ]);

        $updateData = ['name' => $validated['name']];
        if (! empty($validated['code'])) {
            $updateData['code'] = Str::slug($validated['code'], '_');
        }

        $attribute->update($updateData);
        $attribute->load('values');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thuộc tính thành công!',
            'attribute' => $attribute,
        ]);
    }

    /**
     * Store or get an attribute value when typing and pressing Enter
     */
    public function storeValue(Request $request, Attribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:100'],
        ], [
            'value.required' => 'Vui lòng nhập giá trị thuộc tính.',
        ]);

        $val = trim($validated['value']);

        $value = AttributeValue::firstOrCreate(
            [
                'attribute_id' => $attribute->id,
                'value' => $val,
            ],
            [
                'sort_order' => (AttributeValue::where('attribute_id', $attribute->id)->max('sort_order') ?? 0) + 1,
                'status' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'value' => $value,
        ]);
    }
}
