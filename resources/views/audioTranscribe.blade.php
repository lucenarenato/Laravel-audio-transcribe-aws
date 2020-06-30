<html>
<head>
  <script src="{{ asset('js/jquery.min.js') }}"></script>
  <script src="{{ asset('js/main.js') }}"></script>
  <script src="{{ asset('js/recorder.js') }}"></script>
  <script src="{{ asset('js/recorderWorker.js') }}"></script>
  <script src="{{ asset('js/audiodisplay.js') }}"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body {
      font: 14pt Arial, sans-serif;
      background: lightgrey;
      margin: 0 0;
    }
    canvas {
      display: inline-block;
      background: #202020;
      width: 80%;
      margin-left:40px;
      margin-right:40px;
      height: 140px;
      margin-bottom:30px;
      box-shadow: 0px 0px 10px blue;
    }
    #controls {
      align-items: center;
      justify-content: space-around;
      width: 65%;
    }
    #record { height: 15vh;vertical-align: top;margin-top: 20px; cursor: pointer;}
    #record.recording {
      background: red;
      background: -webkit-radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%);
      background: -moz-radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%);
      background: radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%);
    }
    #save, #save img { height: 10vh;margin-top: 40px;margin-left: 10px; }
    #save { opacity: 1;vertical-align: top;cursor: pointer;}
    #save[download] { opacity: 1;}
    .header-text{
      text-align: center;
      color:#535353;
      margin-top: 50px;
    }
    .analysis_section div{
      margin-bottom: 10px;
    }
    .analysis_header div{
      display: inline-block;
    }
    .analysis_item div{
      display: inline-block;
      margin-left: 30px;
    }
    .loader {
      border: 16px solid #f3f3f3;
      border-radius: 50%;
      border-top: 16px solid #3498db;
      -webkit-animation: spin 2s linear infinite; /* Safari */
      animation: spin 2s linear infinite;
      width: 60px;
      height: 60px;
      margin-left: 20px;
    }
    .waiter_section{
      display: none;
      z-index: 9999;
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background: rgba(136, 136, 136, 0.6);
    }
    .waiter_text{
      font-weight: bold;
      color: #378bce;
      font-size: 25px;
    }
    .waiter_collection{
      z-index: 9999;
      position: absolute;
      left: 45%;
      top: 40%;
    }
    /* Safari */
    @-webkit-keyframes spin {
      0% { -webkit-transform: rotate(0deg); }
      100% { -webkit-transform: rotate(360deg); }
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .cancel_btn{
      cursor: pointer;
      margin-top: 20px;
      margin-left: 40px;
      color: #489ce4;
      font-size: 20px;
    }
  </style>

</head>
  <body>
    <div class="waiter_section">
      <div class="waiter_collection">
        <div class="loader"></div>
        <div class="waiter_text">progressing...</div>
        <div class="cancel_btn" onclick="hide_waiting()">cancel</div>
      </div>
    </div>
    <div class="header-text">
      <h1>Audio Transcribe</h1>
    </div>
    <div style="padding-left:50px">
      <canvas id="analyser" width="1024" ></canvas>
      <img id="record" src="img/mic128.png" onclick="toggleRecording(this);" class="">
    </div>
    <div style="padding-left:50px">
      <canvas id="wavedisplay" width="1024"></canvas>
      <a id="save" onclick="upload()"><img src="img/trans-icon.png"></a>
    </div>
    <div style="margin-left:auto;margin-right:auto;width:100%;text-align: center;">
      <audio controls="controls" src="[blobURL]" type="audio/wav" id="record_audio"/>
    </div>

    <div style="margin-left:90px;color:#535353;">
      <div style="width:45%;display: inline-block">
        <h2>Transcribe Result : </h2>
        <div>
          <textarea id="result_text" rows="7" cols="70">
          </textarea>
        </div>
      </div>
      <div style="width:45%;display: inline-block;vertical-align: top">
        <div class="analysis_header">
          <div>
            <h2>Sentiment Analysis : </h2>
          </div>
          <div>
            <h2 id="sentiment_result"> </h2>
          </div>
        </div>
        <div class="analysis_section">
          <div class="analysis_item">
            <div> Positive: </div><div id="Positive_result" style="width:100px"> </div>
          </div>
          <div class="analysis_item">
            <div> Negative: </div><div id="Negative_result" style="width:100px"> </div>
          </div>
          <div class="analysis_item">
            <div> Neutral: </div><div id="Neutral_result" style="width:100px"> </div>
          </div>
          <div class="analysis_item">
            <div> Mixed: </div><div id="Mixed_result" style="width:100px"> </div>
          </div>
        </div>
      </div>
    </div>

    <script>
        var site_url ="<?php echo e(url('/')); ?>";
    </script>
  </body>
</html>

