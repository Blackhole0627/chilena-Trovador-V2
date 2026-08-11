@auth
@php
  $plcIsCreator = (auth()->id() == $response->creator->id);
  $plcId = 'plc-' . $response->id;
@endphp

@once
<style>
  .post-live-comments .plc-head{opacity:.7;letter-spacing:.5px}
  .post-live-comments .plc-list{max-height:340px;overflow-y:auto}
  .post-live-comments .plc-item{display:flex;align-items:flex-start;gap:8px;margin-bottom:10px}
  .post-live-comments .plc-item .plc-body{word-break:break-word}
  .post-live-comments .plc-audio{height:34px;max-width:100%;vertical-align:middle}
</style>
<script>
window.plcLang = {
  creator: @json(__('general.creator')),
  empty:   @json(__('general.no_comments_yet')),
  micErr:  @json(__('general.mic_error'))
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
  var lastSig = null, timer = null;

  function esc(s){ var d=document.createElement('div'); d.textContent = (s==null?'':s); return d.innerHTML; }

  function render(items){
    var sig = items.map(function(c){ return c.id; }).join(',');
    if(sig === lastSig) return;
    lastSig = sig;
    if(!items.length){ list.innerHTML = '<li class="text-muted small">'+esc(L.empty)+'</li>'; return; }
    list.innerHTML = items.map(function(c){
      var badge = c.is_creator ? ' <small class="badge badge-success">'+esc(L.creator)+'</small>' : '';
      var body = c.media
        ? '<audio class="plc-audio" controls preload="none" src="'+esc(c.media)+'"></audio>'
        : esc(c.comment);
      return '<li class="plc-item" data-id="'+c.id+'">'+
               '<img src="'+esc(c.avatar)+'" width="26" height="26" class="rounded-circle">'+
               '<div><strong>'+esc(c.name||c.username)+'</strong>'+badge+
               '<div class="plc-body">'+body+'</div></div></li>';
    }).join('');
    list.scrollTop = list.scrollHeight;
  }

  function load(){
    fetch(fetchUrl, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(d){ if(d && d.success) render(d.comments); })
      .catch(function(){});
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

  form.addEventListener('submit', function(e){
    e.preventDefault();
    var text = input.value.trim();
    if(!text) return;
    var fd = new FormData();
    fd.append('updates_id', postId);
    fd.append('comment', text);
    send(fd, function(){ input.value = ''; });
  });

  if(isCreator){
    var recBtn = w.querySelector('.plc-record');
    var recNote= w.querySelector('.plc-recording');
    var stopLnk= w.querySelector('.plc-stop');
    var rec=null, chunks=[];
    recBtn.addEventListener('click', function(){
      if(!navigator.mediaDevices || !window.MediaRecorder){ alert(L.micErr); return; }
      navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
        rec = new MediaRecorder(stream); chunks=[];
        rec.ondataavailable = function(e){ if(e.data && e.data.size) chunks.push(e.data); };
        rec.onstop = function(){
          stream.getTracks().forEach(function(t){ t.stop(); });
          var blob = new Blob(chunks, {type:'audio/webm'});
          var fd = new FormData();
          fd.append('updates_id', postId);
          fd.append('voice', blob, 'voice.webm');
          send(fd);
          recNote.classList.add('d-none'); recBtn.classList.remove('text-danger');
        };
        rec.start();
        recNote.classList.remove('d-none'); recBtn.classList.add('text-danger');
      }).catch(function(){ alert(L.micErr); });
    });
    stopLnk.addEventListener('click', function(e){ e.preventDefault(); if(rec && rec.state!=='inactive') rec.stop(); });
  }

  // Lazy polling: only poll while the widget is actually visible (feed comments are collapsed).
  function start(){ if(timer) return; load(); timer = setInterval(load, 5000); }
  function stop(){ if(timer){ clearInterval(timer); timer = null; } }
  if('IntersectionObserver' in window){
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ e.isIntersecting ? start() : stop(); });
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

  <div class="plc-head d-flex align-items-center mb-2">
    <i class="bi-chat-dots mr-1"></i> <span class="small text-uppercase">{{ __('general.live_comments') }}</span>
  </div>

  <ul class="list-unstyled plc-list mb-3">
    <li class="text-muted small">{{ __('general.loading') }}</li>
  </ul>

  <form class="plc-form" autocomplete="off">
    <div class="input-group">
      <input type="text" class="form-control plc-input" maxlength="100"
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
        <a href="#" class="ml-1 plc-stop">{{ __('general.stop_and_send') }}</a>
      </small>
    @endif
  </form>
</div>

<script>window.plcInit(@json($plcId));</script>
@endauth
