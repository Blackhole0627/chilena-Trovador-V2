
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $settings->title }}</title>
  <link rel="shortcut icon" href="{{ url('public/img', config('settings.favicon')) }}" />

  <script src="{{ url('public/js/jszip.min.js') }}"></script>
  <script src="{{ url('public/js/epub.min.js') }}"></script>
  <link rel="stylesheet" type="text/css" href="{{ url('public/css/epub-viewer.css') }}">

  <style type="text/css">

    .epub-container {
      min-width: 320px;
      margin: 0 auto;
      position: relative;
    }

    .epub-container .epub-view > iframe {
        background: white;
        box-shadow: 0 0 4px #ccc;
    }

  </style>
</head>
<body oncontextmenu="return false;">
  <div id="viewer"></div>

  <script>
    // Load the opf
    let book = ePub("{{ $urlFile }}");
    let rendition = book.renderTo(document.body, {
        manager: "continuous",
        flow: "scrolled",
        width: "60%"
      });
    let displayed = rendition.display();

    document.onkeydown = function(e) {
    if (e.ctrlKey && e.keyCode === 85) {
      return false;
    }
  };
  </script>

</body>
</html>
