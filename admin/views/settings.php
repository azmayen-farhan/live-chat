<?php
/**
 * Settings page (tabbed form, saved over AJAX).
 *
 * Included from ALC_Admin::page_settings(); `$this` is the ALC_Admin instance.
 *
 * @var array $s All plugin settings (ALC_Settings::all()).
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap alc-wrap">
    <div class="alc-page-header">
        <h1 class="alc-page-title">Chat Settings</h1>
    </div>

    <div id="alc-settings-notice" class="alc-notice" style="display:none;"></div>

    <form id="alc-settings-form">
        <div class="alc-settings-layout">
            <nav class="alc-settings-nav">
                <div class="alc-settings-nav-item active" data-tab="general"><span class="dashicons dashicons-admin-generic"></span> General</div>
                <div class="alc-settings-nav-item" data-tab="appearance"><span class="dashicons dashicons-art"></span> Appearance</div>
                <div class="alc-settings-nav-item" data-tab="widget"><span class="dashicons dashicons-layout"></span> Widget &amp; Button</div>
                <div class="alc-settings-nav-item" data-tab="form"><span class="dashicons dashicons-forms"></span> Pre‑chat Form</div>
                <div class="alc-settings-nav-item" data-tab="notifications"><span class="dashicons dashicons-bell"></span> Notifications</div>
                <div class="alc-settings-nav-item" data-tab="hours"><span class="dashicons dashicons-clock"></span> Business Hours</div>
                <div class="alc-settings-nav-item" data-tab="advanced"><span class="dashicons dashicons-admin-tools"></span> Advanced</div>
            </nav>

            <div class="alc-settings-panels">
                <!-- GENERAL -->
                <div class="alc-settings-panel active" id="alc-panel-general">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Identity</h3><p class="alc-card-desc">Your name and role shown inside the chat widget.</p></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Chat Enabled'); ?>
                            <div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="chat_enabled" value="1" <?php checked($s['chat_enabled'],'1'); ?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Show chat widget on your site</span></div>
                            <?php $this->efr(); ?>
                            <?php $this->fr('Your Name','Displayed in the chat header and messages.'); ?><div class="alc-input-wrap"><input type="text" name="admin_name" value="<?php echo esc_attr($s['admin_name']); ?>" class="alc-input" placeholder="e.g. Azmayen"></div><?php $this->efr(); ?>
                            <?php $this->fr('Your Title / Role','Subtitle below your name.'); ?><div class="alc-input-wrap"><input type="text" name="admin_title" value="<?php echo esc_attr($s['admin_title']); ?>" class="alc-input" placeholder="e.g. Support, Founder…"></div><?php $this->efr(); ?>
                            <?php $this->fr('Admin Avatar','Your photo shown in the chat header. Click the button to select an image from the media library.'); ?>
                            <div class="alc-avatar-field">
                                <div class="alc-avatar-preview" id="alc-avatar-preview">
                                    <?php if ( ! empty( $s['admin_avatar_url'] ) ) : ?>
                                        <img src="<?php echo esc_url( $s['admin_avatar_url'] ); ?>" alt="Avatar">
                                    <?php else : ?>
                                        <span class="alc-avatar-placeholder"><?php echo strtoupper( substr( $s['admin_name'], 0, 2 ) ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="alc-avatar-buttons">
                                    <input type="hidden" name="admin_avatar_url" id="alc-admin-avatar-url" value="<?php echo esc_url( $s['admin_avatar_url'] ); ?>">
                                    <button type="button" class="alc-btn alc-btn-ghost alc-btn-sm" id="alc-select-avatar-btn"><span class="dashicons dashicons-format-image"></span> Select Image</button>
                                    <button type="button" class="alc-btn alc-btn-danger alc-btn-sm" id="alc-remove-avatar-btn" <?php echo empty($s['admin_avatar_url']) ? ' style="display:none;"' : ''; ?>><span class="dashicons dashicons-no"></span> Remove</button>
                                </div>
                            </div>
                            <?php $this->efr(); ?>
                            <?php $this->fr('Chat Window Title','Main heading of the chat window.'); ?><div class="alc-input-wrap"><input type="text" name="chat_title" value="<?php echo esc_attr($s['chat_title']); ?>" class="alc-input"></div><?php $this->efr(); ?>
                            <?php $this->fr('Chat Subtitle'); ?><div class="alc-input-wrap"><input type="text" name="chat_subtitle" value="<?php echo esc_attr($s['chat_subtitle']); ?>" class="alc-input"></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Messages</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Welcome Message'); ?><div class="alc-input-wrap"><textarea name="welcome_message" class="alc-input alc-textarea" rows="3"><?php echo esc_textarea($s['welcome_message']); ?></textarea></div><?php $this->efr(); ?>
                            <?php $this->fr('Online Status Text'); ?><div class="alc-input-wrap"><input type="text" name="online_message" value="<?php echo esc_attr($s['online_message']); ?>" class="alc-input"></div><?php $this->efr(); ?>
                            <?php $this->fr('Offline Status Text'); ?><div class="alc-input-wrap"><input type="text" name="offline_message" value="<?php echo esc_attr($s['offline_message']); ?>" class="alc-input"></div><?php $this->efr(); ?>
                            <?php $this->fr('Away Message'); ?><div class="alc-input-wrap"><textarea name="away_message" class="alc-input alc-textarea" rows="2"><?php echo esc_textarea($s['away_message']); ?></textarea></div><?php $this->efr(); ?>
                            <?php $this->fr('Response Time Text'); ?><div class="alc-input-wrap"><input type="text" name="response_time" value="<?php echo esc_attr($s['response_time']); ?>" class="alc-input"></div><?php $this->efr(); ?>
                            <?php $this->fr('Message Input Placeholder'); ?><div class="alc-input-wrap"><input type="text" name="input_placeholder" value="<?php echo esc_attr($s['input_placeholder']); ?>" class="alc-input"></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <?php $this->save_bar(); ?>
                </div>

                <!-- APPEARANCE -->
                <div class="alc-settings-panel" id="alc-panel-appearance">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Colors</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Primary / Accent Color'); ?><?php $this->color_field('primary_color',$s['primary_color']); ?><?php $this->efr(); ?>
                            <?php $this->fr('Header Text Color'); ?><?php $this->color_field('header_text_color',$s['header_text_color']); ?><?php $this->efr(); ?>
                            <?php $this->fr('Chat Background Color'); ?><?php $this->color_field('chat_bg_color',$s['chat_bg_color']); ?><?php $this->efr(); ?>
                            <?php $this->fr('Admin Bubble Color'); ?><?php $this->color_field('bubble_admin_color',$s['bubble_admin_color']); ?><?php $this->efr(); ?>
                            <?php $this->fr('Visitor Bubble Color'); ?><?php $this->color_field('bubble_visitor_color',$s['bubble_visitor_color']); ?><?php $this->efr(); ?>
                        </div>
                    </div>
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Shape &amp; Layout</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Window Width'); ?><div class="alc-input-wrap"><input type="number" name="window_width" value="<?php echo esc_attr($s['window_width']); ?>" class="alc-input alc-input-number" min="280" max="600"> <span class="alc-input-hint">px</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Window Height'); ?><div class="alc-input-wrap"><input type="number" name="window_height" value="<?php echo esc_attr($s['window_height']); ?>" class="alc-input alc-input-number" min="300" max="800"> <span class="alc-input-hint">px</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Border Radius'); ?><div class="alc-input-wrap"><input type="number" name="border_radius" value="<?php echo esc_attr($s['border_radius']); ?>" class="alc-input alc-input-number" min="0" max="32"> <span class="alc-input-hint">px</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Show Branding'); ?><div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="show_branding" value="1" <?php checked($s['show_branding'],'1'); ?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Show branding footer</span></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <?php $this->save_bar(); ?>
                </div>

                <!-- WIDGET & BUTTON -->
                <div class="alc-settings-panel" id="alc-panel-widget">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Launcher Button</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Widget Position'); ?>
                            <div class="alc-radio-group">
                                <?php foreach(['right'=>'Bottom Right','left'=>'Bottom Left'] as $val=>$label): ?>
                                <label class="alc-radio-pill"><input type="radio" name="position" value="<?php echo $val;?>" <?php checked($s['position'],$val);?>><span class="alc-radio-pill-label"><?php echo $label;?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <?php $this->efr(); ?>
                            <?php $this->fr('Button Size'); ?>
                            <div class="alc-radio-group">
                                <?php foreach(['sm'=>'Small','md'=>'Medium','lg'=>'Large'] as $val=>$label): ?>
                                <label class="alc-radio-pill"><input type="radio" name="button_size" value="<?php echo $val;?>" <?php checked($s['button_size'],$val);?>><span class="alc-radio-pill-label"><?php echo $label;?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <?php $this->efr(); ?>
                            <?php $this->fr('Button Label Text'); ?><div class="alc-input-wrap"><input type="text" name="button_label" value="<?php echo esc_attr($s['button_label']); ?>" class="alc-input" placeholder="e.g. Chat with us"></div><?php $this->efr(); ?>
                            <?php $this->fr('Offset from Edge (X)'); ?><div class="alc-input-wrap"><input type="number" name="widget_offset_x" value="<?php echo esc_attr($s['widget_offset_x']); ?>" class="alc-input alc-input-number" min="8" max="120"> <span class="alc-input-hint">px</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Offset from Edge (Y)'); ?><div class="alc-input-wrap"><input type="number" name="widget_offset_y" value="<?php echo esc_attr($s['widget_offset_y']); ?>" class="alc-input alc-input-number" min="8" max="120"> <span class="alc-input-hint">px</span></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <?php $this->save_bar(); ?>
                </div>

                <!-- PRE-CHAT FORM -->
                <div class="alc-settings-panel" id="alc-panel-form">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Pre‑chat Form Fields</h3><p class="alc-card-desc">Choose which fields to show and whether they are required.</p></div>
                        <div class="alc-card-body" style="padding:0;">
                            <table class="alc-fields-matrix">
                                <thead><tr><th>Field</th><th>Show</th><th>Required</th></tr></thead>
                                <tbody>
                                <?php foreach(['name'=>'Full Name','email'=>'Email Address','company'=>'Company / Organisation','phone'=>'Phone Number','subject'=>'Subject / Topic'] as $key=>$label): ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><label class="alc-toggle" style="margin:0 auto;display:block;width:44px;"><input type="checkbox" name="field_<?php echo $key;?>_enabled" value="1" <?php checked($s['field_'.$key.'_enabled'],'1');?>><span class="alc-toggle-slider"></span></label></td>
                                    <td><label class="alc-toggle" style="margin:0 auto;display:block;width:44px;"><input type="checkbox" name="field_<?php echo $key;?>_required" value="1" <?php checked($s['field_'.$key.'_required'],'1');?>><span class="alc-toggle-slider"></span></label></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Start Button</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Start Button Label'); ?><div class="alc-input-wrap"><input type="text" name="start_btn_label" value="<?php echo esc_attr($s['start_btn_label']); ?>" class="alc-input" placeholder="Start Chat"></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <?php $this->save_bar(); ?>
                </div>

                <!-- NOTIFICATIONS -->
                <div class="alc-settings-panel" id="alc-panel-notifications">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Alert Preferences</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Sound Notifications'); ?><div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="sound_enabled" value="1" <?php checked($s['sound_enabled'],'1');?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Enable sound alerts</span></div><?php $this->efr(); ?>

                            <?php $this->fr('Notification Sound', 'Pick the sound played when a new message arrives. Click any tile to select, or hit ▶ to preview.'); ?>
                            <div class="alc-sound-picker" id="alc-sound-picker">
                                <input type="hidden" name="notification_sound" id="alc-notification-sound-val" value="<?php echo esc_attr($s['notification_sound']); ?>">
                                <?php
                                $sounds = [
                                    'ding'      => ['🔔', 'Ding',       'Classic single descending tone'],
                                    'chime'     => ['🎵', 'Chime',      'Two-note ascending chime'],
                                    'pop'       => ['💬', 'Pop',        'Short bubbly pop'],
                                    'bell'      => ['🔕', 'Bell',       'Rich bell with harmonics'],
                                    'ping'      => ['📡', 'Ping',       'Clean high-pitched ping'],
                                    'soft'      => ['🌙', 'Soft',       'Gentle quiet beep'],
                                    'triple'    => ['⚡', 'Triple',     'Three quick pings in a row'],
                                    'bubble'    => ['🫧', 'Bubble',     'Swoopy bubble drop'],
                                    'marimba'   => ['🎹', 'Marimba',    'Warm triangle-wave pluck'],
                                    'xylophone' => ['🎶', 'Xylophone',  'Two-note ascending wooden tone'],
                                    'sparkle'   => ['✨', 'Sparkle',    'Ascending three-note sparkle'],
                                    'alert'     => ['🚨', 'Alert',      'Urgent two-tone square buzz'],
                                ];
                                foreach ($sounds as $id => [$icon, $name, $desc]) :
                                    $active = $s['notification_sound'] === $id ? ' alc-sound-tile--active' : '';
                                ?>
                                <div class="alc-sound-tile<?php echo $active; ?>" data-sound="<?php echo esc_attr($id); ?>">
                                    <div class="alc-sound-icon"><?php echo $icon; ?></div>
                                    <div class="alc-sound-info">
                                        <div class="alc-sound-name"><?php echo esc_html($name); ?></div>
                                        <div class="alc-sound-desc"><?php echo esc_html($desc); ?></div>
                                    </div>
                                    <button type="button" class="alc-sound-play-btn" data-sound="<?php echo esc_attr($id); ?>" title="Preview">
                                        <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M8 5v14l11-7z"/></svg>
                                    </button>
                                    <div class="alc-sound-check">
                                        <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php $this->efr(); ?>

                            <?php $this->fr('Email Notifications'); ?><div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="admin_email_notify" value="1" <?php checked($s['admin_email_notify'],'1');?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Send email on new conversation</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Notification Email'); ?><div class="alc-input-wrap"><input type="email" name="notify_email" value="<?php echo esc_attr($s['notify_email']); ?>" class="alc-input" placeholder="you@example.com"><span class="alc-input-hint">Defaults to WordPress admin email if empty.</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Desktop Notifications'); ?><div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="desktop_notify" value="1" <?php checked($s['desktop_notify'],'1');?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Enable desktop push notifications</span></div><?php $this->efr(); ?>
                        </div>
                    </div>

                    <div class="alc-settings-card">
                        <div class="alc-card-header">
                            <h3 class="alc-card-title">⏱ Follow-up Email Alerts <span style="font-size:11px;font-weight:500;color:#6b7280;margin-left:8px;">10-minute delay</span></h3>
                            <p class="alc-card-desc">Automatically send reminder emails when a conversation goes unanswered for 10 minutes. Auto-reply messages are <strong>not</strong> counted as your reply.</p>
                        </div>
                        <div class="alc-card-body">
                            <?php $this->fr('Enable Follow-up Alerts','Send timed follow-up emails when messages go unanswered.'); ?>
                            <div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="followup_enabled" value="1" <?php checked($s['followup_enabled'],'1');?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Enable 10-minute follow-up emails</span></div>
                            <?php $this->efr(); ?>

                            <?php $this->fr('Follow-up From Email','The email address used as the sender for all follow-up alert emails.'); ?>
                            <div class="alc-input-wrap"><input type="email" name="followup_from_email" value="<?php echo esc_attr($s['followup_from_email']); ?>" class="alc-input" placeholder="noreply@yoursite.com"><span class="alc-input-hint">Defaults to WordPress admin email if empty.</span></div>
                            <?php $this->efr(); ?>

                            <?php $this->fr('Follow-up To Email (You)','Your email address where alerts land when a visitor messages you and you haven\'t replied in 10 minutes.'); ?>
                            <div class="alc-input-wrap"><input type="email" name="followup_to_email" value="<?php echo esc_attr($s['followup_to_email']); ?>" class="alc-input" placeholder="you@yoursite.com"><span class="alc-input-hint">Defaults to Notification Email → WP admin email if empty. Visitor follow-up emails always go to the visitor's own registered email.</span></div>
                            <?php $this->efr(); ?>
                        </div>
                    </div>

                    <?php $this->save_bar(); ?>
                </div>

                <!-- BUSINESS HOURS -->
                <div class="alc-settings-panel" id="alc-panel-hours">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Business Hours</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Enable Business Hours'); ?><div class="alc-toggle-wrap"><label class="alc-toggle"><input type="checkbox" name="business_hours_enabled" value="1" <?php checked($s['business_hours_enabled'],'1');?>><span class="alc-toggle-slider"></span></label><span class="alc-toggle-label">Restrict to business hours</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Timezone'); ?><div class="alc-input-wrap"><select name="bh_timezone" class="alc-input alc-select" style="max-width:300px;"><?php foreach(DateTimeZone::listIdentifiers() as $tz){echo '<option value="'.esc_attr($tz).'" '.selected($s['bh_timezone'],$tz,false).'>'.esc_html($tz).'</option>';} ?></select></div><?php $this->efr(); ?>
                            <?php $this->fr('Active Days'); ?>
                            <div class="alc-days-grid">
                                <?php foreach(['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'] as $key=>$label): ?>
                                <label class="alc-day-check"><input type="checkbox" name="bh_<?php echo $key;?>" value="1" <?php checked($s['bh_'.$key],'1');?>><span class="alc-day-pill"><?php echo $label;?></span></label>
                                <?php endforeach; ?>
                            </div>
                            <?php $this->efr(); ?>
                            <?php $this->fr('Opening Time'); ?><div class="alc-input-wrap"><input type="time" name="bh_open" value="<?php echo esc_attr($s['bh_open']); ?>" class="alc-input" style="max-width:140px;"></div><?php $this->efr(); ?>
                            <?php $this->fr('Closing Time'); ?><div class="alc-input-wrap"><input type="time" name="bh_close" value="<?php echo esc_attr($s['bh_close']); ?>" class="alc-input" style="max-width:140px;"></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <?php $this->save_bar(); ?>
                </div>

                <!-- ADVANCED -->
                <div class="alc-settings-panel" id="alc-panel-advanced">
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Limits &amp; Retention</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Max Message Length'); ?><div class="alc-input-wrap"><input type="number" name="max_message_length" value="<?php echo esc_attr($s['max_message_length']); ?>" class="alc-input alc-input-number" min="100" max="10000"> <span class="alc-input-hint">characters</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Auto‑close Idle Chats'); ?><div class="alc-input-wrap"><input type="number" name="auto_close_hours" value="<?php echo esc_attr($s['auto_close_hours']); ?>" class="alc-input alc-input-number" min="0" max="720"> <span class="alc-input-hint">hours (0 = off)</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Rate Limit: Messages'); ?><div class="alc-input-wrap"><input type="number" name="rate_limit_messages" value="<?php echo esc_attr($s['rate_limit_messages']); ?>" class="alc-input alc-input-number" min="1" max="120"> <span class="alc-input-hint">per minute</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Rate Limit: New Chats'); ?><div class="alc-input-wrap"><input type="number" name="rate_limit_chats" value="<?php echo esc_attr($s['rate_limit_chats']); ?>" class="alc-input alc-input-number" min="1" max="30"> <span class="alc-input-hint">per 10 min</span></div><?php $this->efr(); ?>
                            <?php $this->fr('Conversation Retention'); ?><div class="alc-input-wrap"><input type="number" name="conversation_retention" value="<?php echo esc_attr($s['conversation_retention']); ?>" class="alc-input alc-input-number" min="0" max="3650"> <span class="alc-input-hint">days (0 = forever)</span></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <div class="alc-settings-card">
                        <div class="alc-card-header"><h3 class="alc-card-title">Custom CSS</h3></div>
                        <div class="alc-card-body">
                            <?php $this->fr('Custom CSS'); ?><div class="alc-input-wrap"><textarea name="custom_css" class="alc-input alc-textarea alc-code-input" rows="6" placeholder="/* e.g. */ .alc-widget { font-size: 15px; }"><?php echo esc_textarea($s['custom_css']); ?></textarea></div><?php $this->efr(); ?>
                        </div>
                    </div>
                    <?php $this->save_bar(); ?>
                </div>
            </div>
        </div>
    </form>
</div>
