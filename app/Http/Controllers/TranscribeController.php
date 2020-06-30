<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use AWS;
use Illuminate\Support\Facades\Storage;
class TranscribeController extends Controller
{
    public function index(Request $request, $message = null)
    {
        return view('audioTranscribe');
    }
    function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    public function audioProcess(Request $request){
        //----------- save chunk as file ----------
        $file = $request->file('file');
        $filename = $request->filename . ".tmp";
        $filepath = public_path()."/chunk/".$filename;

        file_put_contents($filepath, file_get_contents($file));

        //--------- unless finial chunk ----------
        if($request->chunk_end != "1"){
            $response = array(
                'result' => 1,
            );
            echo json_encode($response);
        }else{
        //------ if final chunk, merge chunk files------
            $chunk_count = $request->chunk_count;
            $tmp_list = explode("_",$request->filename);
            $chunk_filename_prex = $tmp_list[0];
            $merged_filename = 'audio-'.date('Ymd-his').'.wav';
            $audio_filepath = public_path()."/audio/".$merged_filename;

            for($index = 0; $index < $chunk_count; $index ++){
                $chunk_filename = public_path()."/chunk/".$chunk_filename_prex."_".$index.".tmp";

                $chunk_file = fopen($chunk_filename, 'r');
                $buff = fread($chunk_file,filesize($chunk_filename));
                fclose($chunk_file);

                file_put_contents($audio_filepath, $buff,FILE_APPEND);
            }

    //----------- upload audio into s3 ---------
            $filePath = 'audio-job/' . $merged_filename;
            $result = Storage::disk('s3')->put($filePath, file_get_contents($audio_filepath));
            $response = array();

            if($result == true) {
                //----------- transcribe audio into text ----------
                $transcribe = AWS::createClient('transcribe');
                $json_filename = 'testaudio' . "-" . date('Ymd-his');
                $result = $transcribe->startTranscriptionJob([
                    'LanguageCode' => 'en-US', // REQUIRED
                    'Media' => [ // REQUIRED
                        'MediaFileUri' => 'https://s3.amazonaws.com/s3.fileupload.ts/'.$filePath,
                    ],
                    'MediaFormat' => 'wav', // REQUIRED
                    'OutputBucketName' => 's3.fileupload.ts',
                    'Settings' => [
                        'ChannelIdentification' => false,
                        'MaxSpeakerLabels' => 5,
                        'ShowSpeakerLabels' => true,
                        'VocabularyFilterMethod' => 'remove',
                    ],
                    'TranscriptionJobName' => $json_filename // REQUIRED
                ]);
                $parse_text = "";
                if ($result != null) {
                    $json_filepath = "https://s3.amazonaws.com/s3.fileupload.ts/".$json_filename.".json";
                    $delay = 0;
                    while(!@fopen($json_filepath, 'r') && $delay < 200) {
                        sleep(5);
                        $delay += 5;
                    }
    //                $transcribe_result = $transcribe->getTranscriptionJob([
    //                    'TranscriptionJobName' => $json_filename, // REQUIRED
    //                ]);
    //                $array_result = $transcribe_result->toArray();
    //                while($array_result['TranscriptionJob']['TranscriptionJobStatus'] == "IN_PROGRESS" && $delay < 90) {
    //                    $transcribe_result = $transcribe->getTranscriptionJob([
    //                        'TranscriptionJobName' => $json_filename, // REQUIRED
    //                    ]);
    //                    $array_result = $transcribe_result->toArray();
    //                    sleep(5);
    //                    $delay += 5;
    //                }

                    if(@fopen($json_filepath, 'r')){
                        $string = file_get_contents($json_filepath);
                        $json_parse = json_decode($string, true);
                        $parse_text = $json_parse['results']['transcripts'][0]['transcript'];
    //----------- sentiment analysis ----------
                        if($parse_text != ""){
                            $comprehend = AWS::createClient('comprehend');
                            $result = $comprehend->batchDetectSentiment([
                                'LanguageCode' => 'en',
                                'TextList' => [$parse_text]
                            ]);
                            $Sentiment = "";
                            $Positive = "";
                            $Negative = "";
                            $Neutral = "";
                            $Mixed = "";
                            $sentiment_result = $result->toArray();
                            if (count($sentiment_result) > 0) {
                                $item1 = $sentiment_result['ResultList'];
                                if (count($item1) > 0) {
                                    $Sentiment = $item1[0]['Sentiment'];
                                    $item2 = $item1[0]['SentimentScore'];
                                    if (count($item2) > 0) {
                                        $Positive = $item2['Positive'];
                                        $Negative = $item2['Negative'];
                                        $Neutral = $item2['Neutral'];
                                        $Mixed = $item2['Mixed'];
                                    }
                                }
                                $response = array(
                                    'comprehend_result' => "1",
                                    'Sentiment' => $Sentiment,
                                    'Positive' => $Positive,
                                    'Negative' => $Negative,
                                    'Neutral' => $Neutral,
                                    'Mixed' => $Mixed,
                                );
                            } else {
                                $response = array(
                                    'comprehend_result' => "0",
                                );
                            }
                            $response['parse_text'] = $parse_text;
                            $response['transcribe_result'] = 1;
                        }else{
                            $response['parse_text'] = "";
                            $response['transcribe_result'] = 0;
                        }
                    }else{
                        $response = array(
                            'transcribe_result' => 2,
                        );
                    }
                } else {
                    $response = array(
                        'transcribe_result' => 0,
                    );
                }
                $response['s3_result'] = 1;
            }else{
                $response = array(
                    's3_result' => 0,
                );
            }

            $response['result'] = 2;
            echo json_encode($response);
        }
    }
    private function thread(){
        sleep(5);
        return 2;
    }

    public function transcribe(Request $request) {
		$client = AWS::createClient('transcribeService');
		$path = 'https://audio-job.s3.us-east-2.amazonaws.com/audio.mp3';; //'https://kuflink.s3.eu-west-2.amazonaws.com/voipbuy.com-1562067652.120-1-724000023-1562067652.mp3';
		try {
			$result = $client->getTranscriptionJob([
				'TranscriptionJobName' => 'audio-job'
			]);
			if ($result['TranscriptionJob']['TranscriptionJobStatus'] == 'IN_PROGRESS') {
				return redirect('/')->with('status', 'Progressing now');
			} else if ($result['TranscriptionJob']['TranscriptionJobStatus'] == 'COMPLETED') {
				$file = file_get_contents($result['TranscriptionJob']['Transcript']['TranscriptFileUri']);
				$json = json_decode($file);
				$transcript = $json->results->transcripts[0]->transcript;
				$client->deleteTranscriptionJob([
				    'TranscriptionJobName' => 'audio-job', // REQUIRED
				]);
				return redirect('/')->with('result', $transcript);
			}
		} catch (Aws\TranscribeService\Exception\TranscribeServiceException $e) {
			$result = $client->startTranscriptionJob([
			    'LanguageCode' => 'en-GB', // REQUIRED
			    'Media' => [ // REQUIRED
			        'MediaFileUri' => $path,
			    ],
			    'MediaFormat' => 'mp3', // REQUIRED
			    'TranscriptionJobName' => 'audio-job', // REQUIRED
			]);
			return redirect('/')->with('status', 'Progressing now');
		}
	}
}
