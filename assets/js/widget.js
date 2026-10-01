/* Azmayen Live Chat — front-end widget behaviour */
(function(){
'use strict';
if(typeof alcWidget==='undefined'){return;}
var cfg=alcWidget.settings,AJAX_URL=alcWidget.ajaxurl,NONCE=alcWidget.nonce,POLL_MS=alcWidget.pollInterval||3000;
function darkenHex(hex,pct){hex=hex.replace('#','');if(hex.length===3){hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];}var r=parseInt(hex.substr(0,2),16),g=parseInt(hex.substr(2,2),16),b=parseInt(hex.substr(4,2),16);r=Math.max(0,Math.round(r*(1-pct/100)));g=Math.max(0,Math.round(g*(1-pct/100)));b=Math.max(0,Math.round(b*(1-pct/100)));return'#'+[r,g,b].map(function(v){return v.toString(16).padStart(2,'0');}).join('');}
if(cfg.primary_color){document.documentElement.style.setProperty('--alc-accent',cfg.primary_color);document.documentElement.style.setProperty('--alc-accent-hover',darkenHex(cfg.primary_color,15));}
var root=document.getElementById('alc-root');
if(cfg.position==='left'&&root){root.classList.add('alc-pos-left');}
var $bubble=document.getElementById('alc-bubble'),$popup=document.getElementById('alc-popup'),$closeBtn=document.getElementById('alc-close'),$introForm=document.getElementById('alc-intro-form'),$chatArea=document.getElementById('alc-chat-area'),$messages=document.getElementById('alc-messages'),$nameInput=document.getElementById('alc-visitor-name'),$emailInput=document.getElementById('alc-visitor-email'),$companyInput=document.getElementById('alc-visitor-company'),$phoneInput=document.getElementById('alc-visitor-phone'),$subjectInput=document.getElementById('alc-visitor-subject'),$startBtn=document.getElementById('alc-start-btn'),$formError=document.getElementById('alc-form-error'),$msgInput=document.getElementById('alc-msg-input'),$sendBtn=document.getElementById('alc-send-btn'),$adminTyping=document.getElementById('alc-admin-typing'),$unreadBadge=document.getElementById('alc-unread-count'),$charCount=document.getElementById('alc-char-count');
var isOpen=false,conversationId=null,lastMessageId=0,pollTimer=null,typingTimer=null,visitorTyping=false,unreadCount=0,MAX_LEN=parseInt(cfg.max_message_length)||2000;
var LS_CONV='alc_conv_id',LS_LAST='alc_last_id';
function esc(str){var div=document.createElement('div');div.textContent=str;return div.innerHTML;}
function formatTime(dateStr){var d=new Date(dateStr),h=d.getHours(),m=d.getMinutes(),ampm=h>=12?'PM':'AM';h=h%12||12;return h+':'+(m<10?'0'+m:m)+' '+ampm;}
function post(data,callback){var xhr=new XMLHttpRequest();xhr.open('POST',AJAX_URL,true);xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');xhr.onload=function(){if(xhr.status===200){try{callback(JSON.parse(xhr.responseText));}catch(e){}}};var params=Object.keys(data).map(function(k){return encodeURIComponent(k)+'='+encodeURIComponent(data[k]);}).join('&');xhr.send(params);}
var ALC_SOUNDS=window.ALC_SOUNDS||{};
function playSound(){if(cfg.sound_enabled!=='1'){return;}try{var ctx=new(window.AudioContext||window.webkitAudioContext)();var id=cfg.notification_sound||'chime';var fn=ALC_SOUNDS[id]||ALC_SOUNDS['chime'];fn(ctx);}catch(e){}}
function scrollToBottom(){$messages.scrollTop=$messages.scrollHeight;}
function setUnread(n){unreadCount=n;if(n>0&&!isOpen){$unreadBadge.textContent=n;$unreadBadge.style.display='flex';}else{$unreadBadge.style.display='none';}}
function openChat(){isOpen=true;$popup.classList.remove('alc-popup-closing');$popup.style.display='block';void $popup.offsetWidth;$popup.setAttribute('aria-hidden','false');$bubble.classList.add('alc-open');setUnread(0);if(conversationId){startPolling();}}
function closeChat(){isOpen=false;$popup.setAttribute('aria-hidden','true');$bubble.classList.remove('alc-open');$popup.classList.add('alc-popup-closing');setTimeout(function(){$popup.style.display='none';$popup.classList.remove('alc-popup-closing');},280);stopPolling();}
$bubble.addEventListener('click',function(){if(isOpen){closeChat();}else{openChat();}});
if($closeBtn){$closeBtn.addEventListener('click',closeChat);}
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&isOpen){closeChat();}});
function showError(msg){$formError.textContent=msg;$formError.style.display='block';}
function hideError(){$formError.style.display='none';}
function updateCharCount(){var len=($msgInput.value||'').length;if($charCount){$charCount.textContent=len+'/'+MAX_LEN;$charCount.style.color=len>MAX_LEN*0.9?'#dc2626':'var(--alc-muted)';}}
if($msgInput&&$charCount){$msgInput.addEventListener('input',updateCharCount);updateCharCount();}

$startBtn.addEventListener('click',function(){
  hideError();
  var name='',email='',company='',phone='',subject='';
  if($nameInput){name=($nameInput.value||'').trim();}
  if($emailInput){email=($emailInput.value||'').trim();}
  if($companyInput){company=($companyInput.value||'').trim();}
  if($phoneInput){phone=($phoneInput.value||'').trim();}
  if($subjectInput){subject=($subjectInput.value||'').trim();}

  if(cfg.field_name_enabled==='1'&&cfg.field_name_required==='1'&&!name){showError('Please enter your name.');if($nameInput)$nameInput.focus();return;}
  if(cfg.field_email_enabled==='1'&&cfg.field_email_required==='1'&&(!email||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))){showError('Please enter a valid email address.');if($emailInput)$emailInput.focus();return;}
  if(cfg.field_phone_enabled==='1'&&cfg.field_phone_required==='1'&&!phone){showError('Please enter your phone number.');if($phoneInput)$phoneInput.focus();return;}
  if(cfg.field_subject_enabled==='1'&&cfg.field_subject_required==='1'&&!subject){showError('Please enter a subject.');if($subjectInput)$subjectInput.focus();return;}
  if(cfg.field_company_enabled==='1'&&cfg.field_company_required==='1'&&!company){showError('Please enter your company.');if($companyInput)$companyInput.focus();return;}

  $startBtn.disabled=true;$startBtn.textContent='Starting\u2026';
  post({action:'alc_start_chat',nonce:NONCE,visitor_name:name,visitor_email:email,visitor_company:company,visitor_phone:phone,visitor_subject:subject},function(res){
    $startBtn.disabled=false;$startBtn.innerHTML=cfg.start_btn_label+' <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    if(res.success){conversationId=res.data.conversation_id;try{localStorage.setItem(LS_CONV,conversationId);localStorage.setItem(LS_LAST,0);}catch(e){}switchToChatArea();pollMessages(true);startPolling();}
    else{showError(res.data.message||'Could not start chat. Please try again.');}
  });
});
function switchToChatArea(){$introForm.style.display='none';$chatArea.style.display='flex';$chatArea.style.flexDirection='column';$msgInput.focus();}
function appendMessage(msg){var direction=msg.sender==='admin'?'from-admin':'from-visitor';var wrap=document.createElement('div');wrap.className='alc-msg-wrap '+direction;wrap.dataset.id=msg.id;wrap.innerHTML='<div class="alc-msg-bubble">'+esc(msg.message).replace(/\n/g,'<br>')+'</div><div class="alc-msg-time">'+formatTime(msg.created_at)+'</div>';$messages.appendChild(wrap);}
function sendMessage(){var text=($msgInput.value||'').trim();if(!text||!conversationId){return;}if(text.length>MAX_LEN){return;}$sendBtn.disabled=true;var msgText=$msgInput.value;$msgInput.value='';autoResize();updateCharCount();appendMessage({id:'pending-'+Date.now(),sender:'visitor',message:msgText.trim(),created_at:new Date().toISOString()});scrollToBottom();sendTyping(false);post({action:'alc_send_message',nonce:NONCE,conversation_id:conversationId,message:msgText.trim()},function(res){$sendBtn.disabled=false;if(res.success&&res.data.message_id){var pending=$messages.querySelector('[data-id^="pending-"]');if(pending){pending.dataset.id=res.data.message_id;}if(res.data.message_id>lastMessageId){lastMessageId=res.data.message_id;try{localStorage.setItem(LS_LAST,lastMessageId);}catch(e){}}}});}
$sendBtn.addEventListener('click',sendMessage);
$msgInput.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMessage();}});
function autoResize(){$msgInput.style.height='auto';$msgInput.style.height=Math.min($msgInput.scrollHeight,100)+'px';}
$msgInput.addEventListener('input',function(){autoResize();updateCharCount();});
function sendTyping(isTyping){if(!conversationId){return;}post({action:'alc_typing',conversation_id:conversationId,is_typing:isTyping?'1':'0'},function(){});}
$msgInput.addEventListener('input',function(){clearTimeout(typingTimer);if(!visitorTyping){visitorTyping=true;sendTyping(true);}typingTimer=setTimeout(function(){visitorTyping=false;sendTyping(false);},3000);});
function pollMessages(initial){if(!conversationId){return;}post({action:'alc_poll_messages',conversation_id:conversationId,since_id:lastMessageId},function(res){if(!res.success){return;}var newAdminMsgs=false;if(res.data.messages&&res.data.messages.length){res.data.messages.forEach(function(msg){var id=parseInt(msg.id,10);if($messages.querySelector('[data-id="'+id+'"]')){if(id>lastMessageId){lastMessageId=id;}return;}appendMessage(msg);if(id>lastMessageId){lastMessageId=id;}try{localStorage.setItem(LS_LAST,lastMessageId);}catch(e){}if(msg.sender==='admin'){newAdminMsgs=true;}});scrollToBottom();}if(res.data.admin_typing){$adminTyping.style.display='block';scrollToBottom();}else{$adminTyping.style.display='none';}if(newAdminMsgs){if(!isOpen){playSound();setUnread(unreadCount+1);}else if(!initial){playSound();}}});}
function startPolling(){if(pollTimer){return;}pollTimer=setInterval(function(){pollMessages(false);},POLL_MS);}
function stopPolling(){if(pollTimer){clearInterval(pollTimer);pollTimer=null;}}
(function restoreSession(){try{var savedConv=localStorage.getItem(LS_CONV),savedLast=localStorage.getItem(LS_LAST);if(savedConv){conversationId=parseInt(savedConv,10);lastMessageId=parseInt(savedLast,10)||0;post({action:'alc_poll_messages',conversation_id:conversationId,since_id:0},function(res){if(!res.success||!res.data.messages||!res.data.messages.length){localStorage.removeItem(LS_CONV);localStorage.removeItem(LS_LAST);conversationId=null;lastMessageId=0;return;}res.data.messages.forEach(function(msg){appendMessage(msg);var id=parseInt(msg.id,10);if(id>lastMessageId){lastMessageId=id;}});try{localStorage.setItem(LS_LAST,lastMessageId);}catch(e){}var msgs=res.data.messages,lastAdmin=msgs.filter(function(m){return m.sender==='admin';}).pop(),lastVisit=msgs.filter(function(m){return m.sender==='visitor';}).pop();if(lastAdmin&&(!lastVisit||parseInt(lastAdmin.id,10)>parseInt(lastVisit.id,10))){setUnread(1);}switchToChatArea();scrollToBottom();if(isOpen){startPolling();}});}}catch(e){}})();
})();
