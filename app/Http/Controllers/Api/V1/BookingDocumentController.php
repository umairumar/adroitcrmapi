<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BookingDocument;
use App\Models\CrmFolders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingDocumentController extends Controller
{
    public function index(int $folderId)
    {
        return response()->json([
            'status' => true,
            'data' => BookingDocument::where('folder_id', $folderId)->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, int $folderId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'document_type' => 'nullable|string|max:50',
            'file' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $folder = CrmFolders::findOrFail($folderId);
        $dir = public_path('uploads/booking-documents');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $request->file('file');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $file->move($dir, $filename);

        $doc = BookingDocument::create([
            'tenant_id' => $folder->tenant_id,
            'folder_id' => $folder->id,
            'title' => $request->title,
            'document_type' => $request->document_type ?? 'other',
            'file_path' => 'uploads/booking-documents/' . $filename,
            'mime_type' => $file->getClientMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['status' => true, 'data' => $doc], 201);
    }

    public function destroy(int $id)
    {
        $doc = BookingDocument::findOrFail($id);
        $path = public_path($doc->file_path);
        if (is_file($path)) {
            @unlink($path);
        }
        $doc->delete();

        return response()->json(['status' => true, 'message' => 'Document deleted']);
    }
}
