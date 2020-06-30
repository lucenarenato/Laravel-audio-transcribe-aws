<html>
<head>
  <script src="{{ asset('js/main.js') }}"></script>
  <script src="{{ asset('js/recorder.js') }}"></script>
  <script src="{{ asset('js/recorderWorker.js') }}"></script>
  <script src="{{ asset('js/audiodisplay.js') }}"></script>
  <script src="{{ asset('js/jquery.min.js') }}"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    html { overflow: hidden; }
    body {
      font: 14pt Arial, sans-serif;
      background: lightgrey;
      display: flex;
      flex-direction: column;
      height: 100vh;
      width: 100%;
      margin: 0 0;
    }
    canvas {
      display: inline-block;
      background: #202020;
      width: 95%;
      height: 33%;
      box-shadow: 0px 0px 10px blue;
    }
    #controls {
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-around;
      height: 20%;
      width: 100%;
    }
    #record { height: 15vh; }
    #record.recording {
      background: red;
      background: -webkit-radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%);
      background: -moz-radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%);
      background: radial-gradient(center, ellipse cover, #ff0000 0%,lightgrey 75%,lightgrey 100%,#7db9e8 100%);
    }
    #save, #save img { height: 10vh; }
    #save { opacity: 0.25;}
    #save[download] { opacity: 1;}
    #viz {
      height: 80%;
      width: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
      align-items: center;
    }
    @media (orientation: landscape) {
      body { flex-direction: row;}
      #controls { flex-direction: column; height: 100%; width: 10%;}
      #viz { height: 100%; width: 90%;}
    }

  </style>
</head>
  <body>
    <form action="{{ route('upload_audio') }}" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      @csrf
      <div class="row">

        <div class="col-md-6">
          <input type="file" name="file" class="form-control">
        </div>

        <div class="col-md-6">
          <button type="submit" class="btn btn-success">Upload</button>
        </div>

      </div>
    </form>
  </body>
</html>

