/*License (MIT)

Copyright © 2013 Matt Diamond

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of
the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.
*/
var audio_link = "";
var audioBlob_list = [];
(function(window){

  var WORKER_PATH = 'js/recorderWorker.js';
  var Recorder = function(source, cfg){
    var config = cfg || {};
      var bufferLen = config.bufferLen || 4096;
      var numChannels = config.numChannels || 2;
    this.context = source.context;
    if(!this.context.createScriptProcessor){
       this.node = this.context.createJavaScriptNode(bufferLen, 2, 2);
    } else {
       this.node = this.context.createScriptProcessor(bufferLen, 2, 2);
    }

    var worker = new Worker(WORKER_PATH);
    worker.postMessage({
      command: 'init',
      config: {
        sampleRate: this.context.sampleRate,
         numChannels: numChannels,
      }
    });
    var recording = false,
      currCallback;

    this.node.onaudioprocess = function(e){
      if (!recording) return;
      worker.postMessage({
        command: 'record',
        buffer: [
          e.inputBuffer.getChannelData(0),
          e.inputBuffer.getChannelData(1)
        ]
      });
    }

    this.configure = function(cfg){
      for (var prop in cfg){
        if (cfg.hasOwnProperty(prop)){
          config[prop] = cfg[prop];
        }
      }
    }

    this.record = function(){
      recording = true;
    }

    this.stop = function(){
      recording = false;
    }

    this.clear = function(){
      worker.postMessage({ command: 'clear' });
    }

    this.getBuffers = function(cb) {
      currCallback = cb || config.callback;
      worker.postMessage({ command: 'getBuffers' })
    }

    this.exportWAV = function(cb, type){
      currCallback = cb || config.callback;
      type = type || config.type || 'audio/wav';
      if (!currCallback) throw new Error('Callback not set');
      worker.postMessage({
        command: 'exportWAV',
        type: type
      });
    }

    this.exportMonoWAV = function(cb, type){
      currCallback = cb || config.callback;
      type = type || config.type || 'audio/wav';
      if (!currCallback) throw new Error('Callback not set');
      worker.postMessage({
        command: 'exportMonoWAV',
        type: type
      });
    }

    worker.onmessage = function(e){
      var blob = e.data;
      currCallback(blob);
    }

    source.connect(this.node);
    this.node.connect(this.context.destination);   // if the script node is not connected to an output the "onaudioprocess" event is not triggered in chrome.
  };

  Recorder.setupDownload = function(blob_list, filename){
    audioBlob_list = blob_list;
    var url = (window.URL || window.webkitURL).createObjectURL(blob_list[0]);
    audio_link = url;
    $("#record_audio").attr("src", url);
    // var subUrl_list = [];
    // for(var index = 1; index < blob_list.length; index ++){
    //     var sub_url = (window.URL || window.webkitURL).createObjectURL(blob_list[index]);
    //     subUrl_list.push(sub_url);
    // }
    // var a = "sdfsdf";
    // link.download = filename || 'output.wav';
  }
  window.Recorder = Recorder;

})(window);
async function upload(){
    if(audio_link == ""){
        alert("Please record your voice!");
    }else {
        $("#result_text").val("");
        $(".waiter_section").show();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var filename_pre = Math.round(new Date().getTime() / 1000);
        var blob_data = audioBlob_list[0];
        var chunk_size = 1000000;
        var total_size = blob_data.size;
        var chunk_count = Math.floor(total_size / chunk_size);
        var left_size = total_size % chunk_size;

        var blob_point = 0;

        for (var blob_index = 0; blob_index < chunk_count + 1; blob_index++) {
            var chunk_blob = null;
            var formData = new FormData();

            if (blob_index == chunk_count) {
                chunk_blob = blob_data.slice(blob_point, blob_point + left_size);
            } else {
                chunk_blob = blob_data.slice(blob_point, blob_point + chunk_size);
            }

            blob_point += chunk_size;
            formData.append('file', chunk_blob);
            formData.append('filename', filename_pre + "_" + blob_index.toString());
            if (blob_index == chunk_count - 1) {
                formData.append('chunk_end', "1");
                formData.append('chunk_count', chunk_count + 1);
            }

            $.ajax({
                url: site_url + '/audio_process',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (data) {
                    jQuery.parseJSON(JSON.stringify(data));
                    if (data.result == 2) {
                        $(".waiter_section").hide();
                        if (data.s3_result != 0) {
                            if (data.transcribe_result != 0) {
                                if (data.transcribe_result == 2) {
                                    alert("transcribe too slow!");
                                } else {
                                    $("#result_text").val($("#result_text").val() + data.parse_text);
                                    if (data.comprehend_result != 0) {
                                        $("#sentiment_result").html(data.Sentiment);
                                        $("#Positive_result").html(data.Positive);
                                        $("#Negative_result").html(data.Negative);
                                        $("#Neutral_result").html(data.Neutral);
                                        $("#Mixed_result").html(data.Mixed);
                                        $(".waiter_section").hide();
                                        alert("Sentiment analysis Success!");
                                    } else {
                                        alert("Sentiment analysis fail!");
                                    }
                                    audio_link == ""
                                }
                            } else {
                                alert("transcribe fail!");
                            }
                        } else {
                            alert("uploading audio fail!");
                        }
                    }
                },
                error: function () {
                }
            });
        }
    }
}
function upload1()   {

    if(audio_link == ""){
        alert("Please record your voice!");
    }else{
        $("#result_text").val("");
        $(".waiter_section").show();
        var success_chunk_length = 0;
        for(var blob_index = 1; blob_index < audioBlob_list.length; blob_index ++){
            var formData = new FormData();
            formData.append('file', audioBlob_list[blob_index]);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                url : site_url + '/audio_process',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(data) {

                    jQuery.parseJSON(JSON.stringify(data));
                    // if(data.s3_result != 0){
                    //     if(data.transcribe_result != 0){
                    //         if(data.transcribe_result == 2){
                    //             alert("transcribe too slow!");
                    //         }else{
                    //             $("#result_text").val($("#result_text").val() + data.parse_text);
                    //             if(data.comprehend_result != 0){
                    //                 success_chunk_length ++;
                    //                 $("#sentiment_result").html(data.Sentiment);
                    //                 $("#Positive_result").html(data.Positive);
                    //                 $("#Negative_result").html(data.Negative);
                    //                 $("#Neutral_result").html(data.Neutral);
                    //                 $("#Mixed_result").html(data.Mixed);
                    //                 if(success_chunk_length == audioBlob_list.length - 1) {
                    //                     $(".waiter_section").hide();
                    //                     alert("Sentiment analysis Success!");
                    //                 }
                    //                 // alert("Chunk"+blob_index+" Sentiment analysis Success!");
                    //             }else{
                    //                 // alert("Sentiment analysis fail!");
                    //             }
                    //             audio_link == ""
                    //         }
                    //     }else{
                    //         // alert("transcribe fail!");
                    //     }
                    // }else{
                    //     // alert("uploading audio fail!");
                    // }
                },
                error: function() {
                    $(".waiter_section").hide();
                    alert("error!");
                }
            });
            blob_index ++
        }

    }

}
function hide_waiting(){
    $(".waiter_section").hide();
}
