<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'TranscribeController@index');

Route::post('upload_audio', 'FileUploadController@fileUpload')->name('upload_audio');

Route::post('upload',function(){
    $aaa = Storage::disk('s3')->allFiles('');
    $bbb = "";
//    request()->file('file')->store('my-file','s3');
})->name('upload');

Route::post('audio_process', 'TranscribeController@audioProcess')->name('audio_process');


Route::get('/wel', function () {
    return view('welcome');
});

Route::post('/transcribe', 'TranscribeController@transcribe');