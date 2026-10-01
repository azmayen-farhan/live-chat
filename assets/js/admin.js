/* Azmayen Live Chat — wp-admin dashboard behaviour (requires jQuery) */
(function($){
'use strict';
var ALC_SOUNDS=window.ALC_SOUNDS||{};
function playSound(id){try{var ctx=new(window.AudioContext||window.webkitAudioContext)();var sid=id||(alcAdmin.notificationSound||'chime');var fn=ALC_SOUNDS[sid]||ALC_SOUNDS['chime'];fn(ctx);}catch(e){}}
var origTitle=document.title,unreadFlash=null;
function flashTitle(count){clearInterval(unreadFlash);if(count>0){var toggle=true;unreadFlash=setInterval(function(){document.title=toggle?'('+count+') New Chat \u2014 '+origTitle:origTitle;toggle=!toggle;},1300);}else{document.title=origTitle;}}
function esc(s){return $('<div>').text(s).html();}
function formatTime(d){return new Date(d).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit'});}

var $navItems=$('.alc-settings-nav-item'),$panels=$('.alc-settings-panel');
$navItems.on('click',function(){var tab=$(this).data('tab');$navItems.removeClass('active');$(this).addClass('active');$panels.removeClass('active');$('#alc-panel-'+tab).addClass('active');});
$(document).on('input','input[type="color"].alc-color-picker',function(){var val=$(this).val(),name=$(this).attr('name');$('[data-color-for="'+name+'"]').val(val);});

/* ── Avatar Media Upload ───────────────────────────────────── */
var avatarFrame;
$('#alc-select-avatar-btn').on('click',function(e){
  e.preventDefault();
  if(avatarFrame){avatarFrame.open();return;}
  avatarFrame=wp.media({title:alcAdmin.strings.select_image,button:{text:alcAdmin.strings.use_image},multiple:false});
  avatarFrame.on('select',function(){
    var attachment=avatarFrame.state().get('selection').first().toJSON();
    $('#alc-admin-avatar-url').val(attachment.url).trigger('change');
    $('#alc-avatar-preview').html('<img src="'+attachment.url+'" alt="Avatar">');
    $('#alc-remove-avatar-btn').show();
  });
  avatarFrame.open();
});
$('#alc-remove-avatar-btn').on('click',function(e){
  e.preventDefault();
  $('#alc-admin-avatar-url').val('').trigger('change');
  $('#alc-avatar-preview').html('<span class="alc-avatar-placeholder">'+($('input[name="admin_name"]').val()||'').substring(0,2).toUpperCase()+'</span>');
  $(this).hide();
});
/* Update placeholder when name changes */
$('input[name="admin_name"]').on('input',function(){
  if(!$('#alc-admin-avatar-url').val()){
    var initials=$(this).val().substring(0,2).toUpperCase();
    $('#alc-avatar-preview').html('<span class="alc-avatar-placeholder">'+initials+'</span>');
  }
});

/* ── Active chat ───────────────────────────────────────────── */
var $chatWin=$('#alc-admin-chat');
if($chatWin.length){
  var convId=parseInt($chatWin.data('conv'),10),lastId=parseInt($chatWin.data('last-id'),10)||0,
      $msgs=$('#alc-admin-messages'),$typer=$('#alc-visitor-typing'),
      $input=$('#alc-admin-reply-input'),$sendBtn=$('#alc-admin-reply-btn'),
      $charCount=$('#alc-reply-char-count'),MAX_LEN=parseInt($input.attr('maxlength'))||2000,
      typingTimer,adminTypingSent=false;
  function autoResize(){$input[0].style.height='auto';$input[0].style.height=Math.min($input[0].scrollHeight,120)+'px';}
  function updateCharCount(){var len=($input.val()||'').length;if($charCount.length){$charCount.text(len+'/'+MAX_LEN);$charCount.css('color',len>MAX_LEN*0.9?'#dc2626':'');}}
  $input.on('input',function(){autoResize();updateCharCount();});updateCharCount();
  function scrollToBottom(){var el=$msgs[0];el.scrollTop=el.scrollHeight;}
  scrollToBottom();
  function buildMsg(msg){return '<div class="alc-msg alc-msg-'+esc(msg.sender)+'" data-id="'+msg.id+'"><div class="alc-msg-sender-label">'+(msg.sender==='admin'?'You':'Visitor')+'</div><div class="alc-msg-bubble">'+esc(msg.message).replace(/\n/g,'<br>')+'</div><div class="alc-msg-time">'+formatTime(msg.created_at)+'</div></div>';}
  function poll(){
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_poll',nonce:alcAdmin.nonce,conversation_id:convId,since_id:lastId},function(res){
      if(res.success&&res.data.messages.length){
        var hasNew=false;
        $.each(res.data.messages,function(_,msg){$msgs.append(buildMsg(msg));if(parseInt(msg.id,10)>lastId){lastId=parseInt(msg.id,10);}if(msg.sender==='visitor'){hasNew=true;}});
        scrollToBottom();if(hasNew){playSound();}
      }
      if(res.success){res.data.visitor_typing?$typer.show():$typer.hide();if(res.data.conv_status){var $statusBtn=$('.alc-toggle-status-btn[data-id="'+convId+'"]');if(res.data.conv_status==='closed'){$statusBtn.html('<span class="dashicons dashicons-unlock"></span> Reopen');}else{$statusBtn.html('<span class="dashicons dashicons-lock"></span> Close');}}}
    });
  }
  setInterval(poll,alcAdmin.pollInterval);
  function sendReply(){
    var msg=$input.val().trim();if(!msg||msg.length>MAX_LEN){return;}
    $sendBtn.prop('disabled',true);
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_reply',nonce:alcAdmin.nonce,conversation_id:convId,message:msg},function(res){
      if(res.success){$input.val('');autoResize();updateCharCount();var f={id:res.data.message_id,sender:'admin',message:msg,created_at:new Date().toISOString()};$msgs.append(buildMsg(f));if(res.data.message_id>lastId){lastId=res.data.message_id;}scrollToBottom();sendTyping(false);var $side=$('.alc-sidebar-item[data-id="'+convId+'"]');$side.removeClass('alc-sidebar-item--unread');$side.find('.alc-sidebar-dot').remove();}
    }).always(function(){$sendBtn.prop('disabled',false);$input.focus();});
  }
  $sendBtn.on('click',sendReply);
  $input.on('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendReply();}});
  function sendTyping(isTyping){$.post(alcAdmin.ajaxurl,{action:'alc_admin_typing',conversation_id:convId,is_typing:isTyping?'1':'0'});}
  $input.on('input',function(){clearTimeout(typingTimer);if(!adminTypingSent){sendTyping(true);adminTypingSent=true;}typingTimer=setTimeout(function(){sendTyping(false);adminTypingSent=false;},3000);});
  /* Quick Replies */
  function loadQuickReplies(){
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_get_quick_replies'},function(res){
      if(res.success&&res.data.quick_replies.length){
        var chips='';res.data.quick_replies.forEach(function(r){chips+='<span class="alc-qr-chip" data-message="'+esc(r.message)+'">'+esc(r.title)+'</span>';});
        $('#alc-qr-chips').html(chips);
      }
    });
  }
  loadQuickReplies();
  $(document).on('click','.alc-qr-chip',function(){var msg=$(this).data('message');$input.val(msg);autoResize();updateCharCount();$input.focus();});
}

/* ── Sidebar poll ──────────────────────────────────────────── */
var $convList=$('#alc-conv-list');
if($convList.length){
  var currentConvId=parseInt(($chatWin.length?$chatWin.data('conv'):0),10)||0;
  function buildSidebar(convs){
    if(!convs.length){$convList.html('<div class="alc-sidebar-empty"><span class="dashicons dashicons-format-chat"></span><p>No conversations yet.</p></div>');return;}
    var h='';convs.forEach(function(c){
      var active=c.id==currentConvId,unread=!c.is_read,closed=c.status==='closed',
          url=alcAdmin.ajaxurl.replace('admin-ajax.php','')+'admin.php?page=alc-conversations&conv='+c.id,
          ac=active?' alc-sidebar-item--active':'',ur=unread?' alc-sidebar-item--unread':'',cl=closed?' alc-sidebar-item--closed':'',
          dot=unread?'<span class="alc-sidebar-dot"></span>':'',tag=closed?'<span class="alc-sidebar-closed-tag">Closed</span>':'';
      h+='<a href="'+url+'" class="alc-sidebar-item'+ac+ur+cl+'" data-id="'+c.id+'"><div class="alc-sidebar-avatar">'+esc(c.visitor_name.charAt(0).toUpperCase())+'</div><div class="alc-sidebar-meta"><div class="alc-sidebar-name">'+esc(c.visitor_name)+dot+tag+'</div><div class="alc-sidebar-email">'+esc(c.visitor_email)+'</div></div><div class="alc-sidebar-time">'+esc(c.last_message_h)+'</div></a>';
    });
    $convList.html(h);
  }
  function pollList(){
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_poll_list',nonce:alcAdmin.nonce,search:''},function(res){
      if(!res.success){return;}var u=res.data.unread;flashTitle(u);
      var $badge=$('.alc-badge-unread');if(u>0){if($badge.length){$badge.text(u+' unread');}else{$('.alc-topbar-left').append('<span class="alc-badge-unread">'+u+' unread</span>');}$('.alc-stat-pill--accent .alc-stat-pill-num').text(u);}else{$badge.remove();$('.alc-stat-pill--accent .alc-stat-pill-num').text('0');}
      buildSidebar(res.data.conversations);
    });
  }
  setInterval(pollList,alcAdmin.listInterval);
}

/* ── Delete ─────────────────────────────────────────────────── */
$(document).on('click','.alc-delete-btn',function(){if(!confirm(alcAdmin.strings.confirm_delete)){return;}var id=$(this).data('id'),redirect=$(this).data('redirect');$.post(alcAdmin.ajaxurl,{action:'alc_admin_delete_conv',nonce:alcAdmin.nonce,conversation_id:id},function(res){if(res.success){if(redirect){window.location.href=alcAdmin.ajaxurl.replace('admin-ajax.php','')+'admin.php?page=alc-conversations';}else{$('[data-id="'+id+'"]').fadeOut(200,function(){$(this).remove();});}}});});

/* ── Export ─────────────────────────────────────────────────── */
$(document).on('click','.alc-export-btn',function(){var id=$(this).data('id');$.post(alcAdmin.ajaxurl,{action:'alc_admin_export_conv',nonce:alcAdmin.nonce,conversation_id:id},function(res){if(res.success){var blob=new Blob([res.data.text],{type:'text/plain'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='chat-'+id+'.txt';document.body.appendChild(a);a.click();document.body.removeChild(a);URL.revokeObjectURL(url);}});});

/* ── Toggle status ──────────────────────────────────────────── */
$(document).on('click','.alc-toggle-status-btn',function(){var id=$(this).data('id'),$btn=$(this);$.post(alcAdmin.ajaxurl,{action:'alc_admin_toggle_status',nonce:alcAdmin.nonce,conversation_id:id},function(res){if(res.success){if(res.data.status==='closed'){$btn.html('<span class="dashicons dashicons-unlock"></span> Reopen');}else{$btn.html('<span class="dashicons dashicons-lock"></span> Close');}}});});

/* ── Settings save ──────────────────────────────────────────── */
$('#alc-settings-form').on('submit',function(e){e.preventDefault();var $btn=$('#alc-save-settings-btn'),$notice=$('#alc-settings-notice'),data={action:'alc_admin_save_settings',nonce:alcAdmin.settingsNonce};$(this).find('input,textarea,select').each(function(){var $el=$(this),name=$el.attr('name');if(!name){return;}if($el.attr('type')==='checkbox'){data[name]=$el.is(':checked')?'1':'0';}else if($el.attr('type')==='radio'){if($el.is(':checked')){data[name]=$el.val();}}else if(!$el.hasClass('alc-color-hex')){data[name]=$el.val();}});$btn.prop('disabled',true).html('<span class="dashicons dashicons-update alc-spin"></span> Saving\u2026');$.post(alcAdmin.ajaxurl,data,function(res){$notice.removeClass('success error').show();if(res.success){$notice.addClass('success').html('\u2713 '+(res.data.message||'Settings saved.'));alcAdmin.notificationSound=$('#alc-notification-sound-val').val();}else{$notice.addClass('error').html('\u2717 '+(res.data.message||'Failed to save.'));}$notice.css('display','block');setTimeout(function(){$notice.fadeOut(400);},3500);}).always(function(){$btn.prop('disabled',false).html('<span class="dashicons dashicons-saved"></span> Save Settings');});});

/* ── Sound Picker ───────────────────────────────────────────── */
$(document).on('click','.alc-sound-tile',function(e){
  if($(e.target).closest('.alc-sound-play-btn').length){return;}
  var id=$(this).data('sound');
  $('.alc-sound-tile').removeClass('alc-sound-tile--active');
  $(this).addClass('alc-sound-tile--active');
  $('#alc-notification-sound-val').val(id);
  playSound(id);
});
$(document).on('click','.alc-sound-play-btn',function(e){
  e.stopPropagation();
  var id=$(this).data('sound'),$btn=$(this);
  $btn.addClass('alc-playing');
  setTimeout(function(){$btn.removeClass('alc-playing');},700);
  playSound(id);
});

/* ── Quick Replies CRUD ─────────────────────────────────────── */
var $qrId=$('#alc-qr-id'),$qrTitle=$('#alc-qr-title'),$qrMessage=$('#alc-qr-message'),$qrSave=$('#alc-qr-save-btn'),$qrCancel=$('#alc-qr-cancel-btn'),$qrList=$('#alc-qr-list'),$qrNotice=$('#alc-qr-notice');
function qrNotice(msg,type){$qrNotice.removeClass('success error').addClass(type).html(msg).show();setTimeout(function(){$qrNotice.fadeOut(400);},3000);}
function resetQrForm(){$qrId.val('');$qrTitle.val('');$qrMessage.val('');$qrCancel.hide();$qrSave.html('<span class="dashicons dashicons-saved"></span> Save');}
$qrSave.on('click',function(){var title=$qrTitle.val().trim(),msg=$qrMessage.val().trim();if(!title||!msg){qrNotice('Title and message are required.','error');return;}$qrSave.prop('disabled',true);$.post(alcAdmin.ajaxurl,{action:'alc_admin_save_quick_reply',nonce:alcAdmin.nonce,qr_id:$qrId.val(),qr_title:title,qr_message:msg},function(res){if(res.success){qrNotice('Quick reply saved.','success');resetQrForm();refreshQrList();}else{qrNotice('Failed to save.','error');}$qrSave.prop('disabled',false);});});
$qrCancel.on('click',resetQrForm);
$(document).on('click','.alc-qr-edit-btn',function(){var $item=$(this).closest('.alc-qr-item'),id=$item.data('id'),title=$item.data('title'),msg=$item.data('message');$qrId.val(id);$qrTitle.val(title);$qrMessage.val(msg);$qrCancel.show();$qrSave.html('<span class="dashicons dashicons-saved"></span> Update');$('html,body').animate({scrollTop:$('#alc-qr-id').offset().top-100},300);});
$(document).on('click','.alc-qr-delete-btn',function(){if(!confirm('Delete this quick reply?')){return;}var id=$(this).data('id');$.post(alcAdmin.ajaxurl,{action:'alc_admin_delete_quick_reply',nonce:alcAdmin.nonce,qr_id:id},function(res){if(res.success){qrNotice('Quick reply deleted.','success');resetQrForm();refreshQrList();}});});
function refreshQrList(){$.post(alcAdmin.ajaxurl,{action:'alc_admin_get_quick_replies'},function(res){if(res.success){var items='';if(res.data.quick_replies.length){res.data.quick_replies.forEach(function(r){items+='<div class="alc-qr-item" data-id="'+r.id+'" data-title="'+esc(r.title)+'" data-message="'+esc(r.message)+'"><div class="alc-qr-item-title">'+esc(r.title)+'</div><div class="alc-qr-item-preview">'+esc(r.message).substring(0,60)+(r.message.length>60?'\u2026':'')+'</div><div class="alc-qr-item-actions"><button class="alc-qr-edit-btn" data-id="'+r.id+'"><span class="dashicons dashicons-edit"></span></button><button class="alc-qr-delete-btn" data-id="'+r.id+'"><span class="dashicons dashicons-trash"></span></button></div></div>';});}else{items='<div class="alc-qr-empty">No quick replies yet.</div>';}$qrList.html(items);}});}

/* ── Auto Replies CRUD ──────────────────────────────────────────── */
var $arId=$('#alc-ar-id'),$arTitle=$('#alc-ar-title'),$arKeywords=$('#alc-ar-keywords'),$arReply=$('#alc-ar-reply'),
    $arSave=$('#alc-ar-save-btn'),$arCancel=$('#alc-ar-cancel-btn'),$arList=$('#alc-ar-list'),$arNotice=$('#alc-ar-notice');
if($arList.length){
  function arNotice(msg,type){$arNotice.removeClass('success error').addClass(type).html(msg).show();setTimeout(function(){$arNotice.fadeOut(400);},3500);}
  function resetArForm(){$arId.val('');$arTitle.val('');$arKeywords.val('');$arReply.val('');$arCancel.hide();$arSave.html('<span class="dashicons dashicons-saved"></span> Save Rule');$('#alc-ar-form-title').text('Add New Auto-Reply Rule');}
  function buildArItem(r){
    var kws=r.keywords.split(',').map(function(k){return k.trim();}).filter(Boolean);
    var chips=kws.map(function(k){return '<span class="alc-ar-kw-chip">'+esc(k)+'</span>';}).join('');
    var isOn=parseInt(r.is_enabled,10);
    var preview=esc(r.reply).substring(0,80)+(r.reply.length>80?'\u2026':'');
    return '<div class="alc-ar-item'+(isOn?'':' alc-ar-item--disabled')+'" data-id="'+r.id+'" data-title="'+esc(r.title)+'" data-keywords="'+esc(r.keywords)+'" data-reply="'+esc(r.reply)+'">'
      +'<div class="alc-ar-item-top"><span class="alc-ar-item-title">'+esc(r.title)+'</span>'
      +'<span class="alc-ar-item-status '+(isOn?'alc-ar-status--on':'alc-ar-status--off')+'">'+(isOn?'Active':'Paused')+'</span></div>'
      +'<div class="alc-ar-keywords">'+chips+'</div>'
      +'<div class="alc-ar-preview">'+preview+'</div>'
      +'<div class="alc-ar-item-actions">'
      +'<button class="alc-btn alc-btn-ghost alc-btn-sm alc-ar-edit-btn" data-id="'+r.id+'"><span class="dashicons dashicons-edit"></span> Edit</button>'
      +'<button class="alc-btn alc-btn-ghost alc-btn-sm alc-ar-toggle-btn" data-id="'+r.id+'"><span class="dashicons dashicons-'+(isOn?'controls-pause':'controls-play')+'"></span> '+(isOn?'Pause':'Activate')+'</button>'
      +'<button class="alc-btn alc-btn-danger alc-btn-sm alc-ar-delete-btn" data-id="'+r.id+'"><span class="dashicons dashicons-trash"></span></button>'
      +'</div></div>';
  }
  function refreshArList(){$.post(alcAdmin.ajaxurl,{action:'alc_admin_get_auto_replies'},function(res){if(res.success){if(res.data.auto_replies.length){var html='';res.data.auto_replies.forEach(function(r){html+=buildArItem(r);});$arList.html(html);}else{$arList.html('<div class="alc-qr-empty"><span class="dashicons dashicons-superhero-alt" style="font-size:32px;width:32px;height:32px;margin-bottom:8px;display:block;"></span>No auto-reply rules yet.</div>');}}});}
  $arSave.on('click',function(){
    var title=$arTitle.val().trim(),keywords=$arKeywords.val().trim(),reply=$arReply.val().trim();
    if(!title||!keywords||!reply){arNotice('Title, keywords and reply message are all required.','error');return;}
    $arSave.prop('disabled',true);
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_save_auto_reply',nonce:alcAdmin.nonce,ar_id:$arId.val(),ar_title:title,ar_keywords:keywords,ar_reply:reply},function(res){
      if(res.success){arNotice('Auto-reply rule saved successfully.','success');resetArForm();refreshArList();}
      else{arNotice(res.data.message||'Failed to save.','error');}
      $arSave.prop('disabled',false);
    });
  });
  $arCancel.on('click',resetArForm);
  $(document).on('click','.alc-ar-edit-btn',function(){
    var $item=$(this).closest('.alc-ar-item');
    $arId.val($item.data('id'));$arTitle.val($item.data('title'));$arKeywords.val($item.data('keywords'));$arReply.val($item.data('reply'));
    $arCancel.show();$arSave.html('<span class="dashicons dashicons-saved"></span> Update Rule');
    $('#alc-ar-form-title').text('Edit Auto-Reply Rule');
    $('html,body').animate({scrollTop:$('#alc-ar-id').offset().top-100},300);
  });
  $(document).on('click','.alc-ar-toggle-btn',function(){
    var id=$(this).data('id'),$btn=$(this);
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_toggle_auto_reply',nonce:alcAdmin.nonce,ar_id:id},function(res){
      if(res.success){refreshArList();}
    });
  });
  $(document).on('click','.alc-ar-delete-btn',function(){
    if(!confirm('Delete this auto-reply rule? This cannot be undone.')){return;}
    var id=$(this).data('id');
    $.post(alcAdmin.ajaxurl,{action:'alc_admin_delete_auto_reply',nonce:alcAdmin.nonce,ar_id:id},function(res){
      if(res.success){arNotice('Rule deleted.','success');refreshArList();}
    });
  });
}
})(jQuery);
