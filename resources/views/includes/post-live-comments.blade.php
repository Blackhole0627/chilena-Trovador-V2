@auth
@php
  $plcIsCreator = (auth()->id() == $response->creator->id);
  $plcId = 'plc-' . $response->id;
@endphp

@once
<style>
  .post-live-comments .plc-list{max-height:340px;overflow-y:auto}
  .post-live-comments .plc-item{display:flex;align-items:flex-start;gap:8px;margin-bottom:10px}
  .post-live-comments .plc-item .plc-body{word-break:break-word}
  .post-live-comments audio{accent-color:#00A65A;height:34px;max-width:100%;vertical-align:middle}
  .post-live-comments .plc-tools .dropdown-menu{min-width:290px}
  .post-live-comments .plc-more-link{cursor:pointer}
</style>
<script>
window.plcLang = {
  creator: @json(__('general.creator')),
  empty:   @json(__('general.no_comments_yet')),
  micErr:  @json(__('general.mic_error')),
  more:    @json(__('general.view_more'))
};
window.plcInit = function(rootId){
  var w = document.getElementById(rootId);
  if(!w || w.dataset.plcReady) return;
  w.dataset.plcReady = '1';
  var postId    = w.getAttribute('data-post');
  var isCreator = w.getAttribute('data-creator') === '1';
  var fetchUrl  = w.getAttribute('data-fetch');
  var storeUrl  = w.getAttribute('data-store');
  var list  = w.querySelector('.plc-list');
  var form  = w.querySelector('.plc-form');
  var input = w.querySelector('.plc-input');
  var errEl = w.querySelector('.plc-error');
  var meta  = document.querySelector('meta[name="csrf-token"]');
  var token = meta ? meta.getAttribute('content') : '';
  var L = window.plcLang || {};
  var items = [], ids = {}, total = 0, timer = null, loading = false;

  function itemNode(c){
    var li = document.createElement('li'); li.className = 'plc-item'; li.setAttribute('data-id', c.id);
    var av = document.createElement('img'); av.width = 26; av.height = 26; av.className = 'rounded-circle'; av.src = c.avatar || '';
    var box = document.createElement('div');
    var name = document.createElement('strong'); name.textContent = c.name || c.username; box.appendChild(name);
    if(c.is_creator){ var b = document.createElement('small'); b.className = 'badge badge-success ml-1'; b.textContent = L.creator; box.appendChild(b); }
    var body = document.createElement('div'); body.className = 'plc-body';
    if(c.media){ var a = document.createElement('audio'); a.controls = true; a.preload = 'none'; a.className = 'plc-audio'; a.src = c.media; body.appendChild(a); }
    else if(c.sticker){ var s = document.createElement('img'); s.src = c.sticker; s.width = 70; body.appendChild(s); }
    else if(c.gif_image){ var g = document.createElement('img'); g.src = c.gif_image; g.width = 200; g.className = 'rounded'; body.appendChild(g); }
    else { body.textContent = c.comment || ''; }
    box.appendChild(body); li.appendChild(av); li.appendChild(box);
    return li;
  }

  function renderAll(){
    list.innerHTML = '';
    if(items.length < total){
      var more = document.createElement('li'); more.className = 'text-center mb-2';
      var a = document.createElement('a'); a.className = 'plc-more-link small'; a.textContent = L.more;
      more.appendChild(a); list.appendChild(more);
    }
    if(!items.length){
      var e = document.createElement('li'); e.className = 'text-muted small'; e.textContent = L.empty; list.appendChild(e);
    } else {
      items.forEach(function(c){ list.appendChild(itemNode(c)); });
    }
  }

  function integrate(fresh){
    var changed = false;
    (fresh || []).forEach(function(c){
      if(!ids[c.id]){ ids[c.id] = 1; items.push(c); changed = true; }
    });
    if(changed) items.sort(function(a, b){ return a.id - b.id; });
    return changed;
  }

  function updateBadge(label){
    if(!label) return;
    var scope = w.closest('.card-footer') || w.closest('.card');
    var badge = scope ? scope.querySelector('.totalComments') : null;
    if(badge) badge.textContent = label;
  }

  function load(){
    if(loading) return; loading = true;
    fetch(fetchUrl, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        loading = false;
        if(d && d.success){
          total = d.total || 0;
          updateBadge(d.total_label);
          var first = !list.querySelector('.plc-item');
          if(integrate(d.comments) || first){
            renderAll();
            list.scrollTop = list.scrollHeight;
          } else {
            renderAll();
          }
        }
      })
      .catch(function(){ loading = false; });
  }

  function loadOlder(){
    if(!items.length) return;
    var before = items[0].id;
    var prevH = list.scrollHeight, prevT = list.scrollTop;
    fetch(fetchUrl + '?before=' + before, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){
        if(d && d.success && integrate(d.comments)){
          renderAll();
          list.scrollTop = list.scrollHeight - prevH + prevT;
        }
      }).catch(function(){});
  }

  function showErr(errors){
    var msg = '';
    if(errors){ for(var k in errors){ if(errors[k] && errors[k][0]){ msg = errors[k][0]; break; } } }
    errEl.textContent = msg || '!';
    errEl.classList.remove('d-none');
  }

  function send(fd, done){
    errEl.classList.add('d-none');
    fd.append('_token', token);
    fetch(storeUrl, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){ if(d && d.success){ if(done) done(); load(); } else { showErr(d && d.errors); } })
      .catch(function(){ showErr(); });
  }

  // --- Nota de voz: el mic inicia y detiene, queda en revision y se envia con el boton normal ---
  var pending = null, pendingUrl = null, rec = null;
  var prevRow = w.querySelector('.plc-preview');
  var prevAudio = w.querySelector('.plc-preview-audio');

  function clearPending(){
    pending = null;
    if(pendingUrl){ URL.revokeObjectURL(pendingUrl); pendingUrl = null; }
    if(prevAudio){ prevAudio.removeAttribute('src'); }
    if(prevRow){ prevRow.classList.add('d-none'); }
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    if(pending){
      var fd = new FormData();
      fd.append('updates_id', postId);
      fd.append('voice', pending.blob, 'voice.' + pending.ext);
      send(fd, clearPending);
      return;
    }
    var text = input.value.trim();
    if(!text) return;
    var fd2 = new FormData();
    fd2.append('updates_id', postId);
    fd2.append('comment', text);
    send(fd2, function(){ input.value = ''; });
  });

  if(isCreator){
    var recBtn = w.querySelector('.plc-record');
    var recNote= w.querySelector('.plc-recording');
    recBtn.addEventListener('click', function(){
      if(rec && rec.state === 'recording'){ rec.stop(); return; }
      if(!navigator.mediaDevices || !window.MediaRecorder){ alert(L.micErr); return; }
      navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
        // iOS Safari graba audio/mp4; Chrome y Firefox audio/webm.
        var mime = '';
        if(MediaRecorder.isTypeSupported){
          if(MediaRecorder.isTypeSupported('audio/webm')) mime = 'audio/webm';
          else if(MediaRecorder.isTypeSupported('audio/mp4')) mime = 'audio/mp4';
        }
        rec = mime ? new MediaRecorder(stream, {mimeType: mime}) : new MediaRecorder(stream);
        var chunks = [];
        rec.ondataavailable = function(e){ if(e.data && e.data.size) chunks.push(e.data); };
        rec.onstop = function(){
          // Libera el microfono siempre (el icono del navegador vuelve a la normalidad).
          stream.getTracks().forEach(function(t){ t.stop(); });
          var type = rec.mimeType || mime || 'audio/webm';
          var ext = type.indexOf('mp4') !== -1 ? 'm4a' : (type.indexOf('ogg') !== -1 ? 'ogg' : 'webm');
          clearPending();
          pending = {blob: new Blob(chunks, {type: type}), ext: ext};
          pendingUrl = URL.createObjectURL(pending.blob);
          if(prevAudio) prevAudio.src = pendingUrl;
          if(prevRow) prevRow.classList.remove('d-none');
          recBtn.classList.remove('text-danger');
          recBtn.innerHTML = '<i class="bi-mic"></i>';
          if(recNote) recNote.classList.add('d-none');
          rec = null;
        };
        rec.start();
        recBtn.classList.add('text-danger');
        recBtn.innerHTML = '<i class="bi-stop-fill"></i>';
        if(recNote) recNote.classList.remove('d-none');
      }).catch(function(){ alert(L.micErr); });
    });
  }

  // --- Clicks dentro del widget: ver mas, descartar nota, stickers y gifs ---
  w.addEventListener('click', function(e){
    if(e.target.closest('.plc-more-link')){ e.preventDefault(); loadOlder(); return; }
    if(e.target.closest('.plc-discard')){ e.preventDefault(); clearPending(); return; }
    var st = e.target.closest('.insertSticker');
    if(st){ e.preventDefault(); var fd = new FormData(); fd.append('updates_id', postId); fd.append('sticker', st.getAttribute('data-url')); send(fd); return; }
    var gf = e.target.closest('.insertGif');
    if(gf){ e.preventDefault(); var fd2 = new FormData(); fd2.append('updates_id', postId); fd2.append('gif_image', gf.getAttribute('data-url')); send(fd2); return; }
  });

  // Sondeo perezoso: solo corre mientras el widget esta visible.
  function start(){ if(timer) return; load(); timer = setInterval(load, 5000); }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }
  if('IntersectionObserver' in window){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ en.isIntersecting ? start() : stop(); });
    });
    io.observe(w);
  } else {
    start();
  }
};
</script>
@endonce

<div class="post-live-comments" id="{{ $plcId }}"
     data-post="{{ $response->id }}"
     data-creator="{{ $plcIsCreator ? '1' : '0' }}"
     data-fetch="{{ url('comments/post', $response->id) }}"
     data-store="{{ url('comment/post') }}">

  <ul class="list-unstyled plc-list mb-2">
    <li class="text-muted small">{{ __('general.loading') }}</li>
  </ul>

  <form class="plc-form" autocomplete="off">
    <div class="plc-tools d-flex align-items-center mb-1" style="gap:14px;">
      <span style="position:relative;">
        <span class="triggerEmoji" data-toggle="dropdown" id="plcEmoji{{ $response->id }}"
              role="button" aria-haspopup="true" aria-expanded="false" style="font-size:17px;cursor:pointer;">
          <i class="bi-emoji-smile"></i>
        </span>
        <div class="dropdown-menu dropdown-emoji custom-scrollbar" aria-labelledby="plcEmoji{{ $response->id }}">
          @include('includes.emojis')
        </div>
      </span>

      @if ($settings->giphy_status)
      <span style="position:relative;">
        <span class="triggerGif" data-toggle="dropdown" id="plcGif{{ $response->id }}"
              role="button" aria-haspopup="true" aria-expanded="false" style="font-size:17px;cursor:pointer;">
          <i class="bi-filetype-gif"></i>
        </span>
        <div class="dropdown-menu dropdown-emoji dropdown-gifs custom-scrollbar" aria-labelledby="plcGif{{ $response->id }}"></div>
      </span>
      @endif

      <span style="position:relative;">
        <span class="triggerSticker" data-toggle="dropdown" id="plcSticker{{ $response->id }}"
              role="button" aria-haspopup="true" aria-expanded="false" style="font-size:17px;cursor:pointer;">
          <i class="bi-sticky"></i>
        </span>
        <div class="dropdown-menu dropdown-emoji dropdown-stickers custom-scrollbar" aria-labelledby="plcSticker{{ $response->id }}"></div>
      </span>
    </div>

    @if ($plcIsCreator)
      <div class="plc-preview d-none mb-1 d-flex align-items-center" style="gap:8px;">
        <audio controls class="plc-preview-audio"></audio>
        <a href="#" class="plc-discard text-danger" title="&times;"><i class="bi-x-lg"></i></a>
      </div>
    @endif

    <div class="input-group input-group-sm">
      <input type="text" class="form-control plc-input emojiArea" maxlength="100"
             placeholder="{{ __('general.write_comment') }}">
      @if ($plcIsCreator)
        <button type="button" class="btn btn-outline-secondary plc-record"
                title="{{ __('general.voice_note') }}"><i class="bi-mic"></i></button>
      @endif
      <button type="submit" class="btn btn-primary plc-send"><i class="bi-send"></i></button>
    </div>
    <small class="text-danger d-none mt-1 d-block plc-error"></small>
    @if ($plcIsCreator)
      <small class="d-none mt-1 d-block plc-recording">
        <i class="bi-record-circle text-danger"></i> {{ __('general.recording') }}
      </small>
    @endif
  </form>
</div>

<script>window.plcInit(@json($plcId));</script>
@endauth
