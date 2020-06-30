<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class FileUploadController extends Controller
{
    public function index(Request $request, $message = null)
    {
        return view('fileUpload');
    }
    public function fileUpload(Request $request){
        $file = $request->file('file');
        $name=time().$file->getClientOriginalName();
        $filePath = 'images/' . $name;
        Storage::disk('s3')->put($filePath, file_get_contents($file));
        return back()->with('success','Image Uploaded successfully');
    }
    public function test1(){
        $aaa = "";
        echo "test1";
    }
}
