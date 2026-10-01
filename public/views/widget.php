<?php
/**
 * Front-end chat widget markup (launcher bubble + popup).
 *
 * Included from ALC_Public::render_widget().
 *
 * @var array  $s           All plugin settings (ALC_Settings::all()).
 * @var string $avatar_url  Admin avatar URL, or ''.
 * @var string $admin_name  Admin display name.
 * @var string $initials    Two-letter fallback shown when there is no avatar.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<!-- Azmayen Live Chat Widget -->
<div id="alc-root">
    <button id="alc-bubble" class="alc-bubble" aria-label="Open live chat">
        <span class="alc-bubble-icon alc-icon-chat">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z"
                    stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </span>
        <span class="alc-bubble-icon alc-icon-close">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="alc-bubble-badge" id="alc-unread-count" style="display:none;"></span>
    </button>

    <div id="alc-popup" class="alc-popup" style="display:none;" aria-hidden="true">

        <div class="alc-header">
            <div class="alc-header-avatar">
                <?php if ( ! empty( $avatar_url ) ) : ?>
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $admin_name ); ?>" class="alc-avatar-img">
                <?php else : ?>
                    <?php echo esc_html( $initials ); ?>
                <?php endif; ?>
            </div>
            <div class="alc-header-info">
                <div class="alc-header-title"><?php echo esc_html( $s['chat_title'] ); ?></div>
                <div class="alc-header-status">
                    <span class="alc-online-dot"></span>
                    <span class="alc-status-text"><?php echo esc_html( $s['online_message'] ); ?></span>
                </div>
            </div>
            <button id="alc-close" class="alc-close-btn" aria-label="Close chat">
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
                    <path d="M18 6L6 18M6 6L18 18" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div id="alc-intro-form" class="alc-intro-form">
            <p class="alc-intro-text"><?php echo esc_html( $s['response_time'] ); ?></p>
            <?php if ( $s['field_name_enabled'] === '1' ): ?>
            <div class="alc-form-group">
                <input type="text" id="alc-visitor-name" placeholder="Your name<?php echo $s['field_name_required'] === '1' ? ' *' : ''; ?>" class="alc-form-input" autocomplete="name" <?php echo $s['field_name_required'] === '1' ? 'required' : ''; ?>>
            </div>
            <?php endif; ?>
            <?php if ( $s['field_email_enabled'] === '1' ): ?>
            <div class="alc-form-group">
                <input type="email" id="alc-visitor-email" placeholder="Your email<?php echo $s['field_email_required'] === '1' ? ' *' : ''; ?>" class="alc-form-input" autocomplete="email" <?php echo $s['field_email_required'] === '1' ? 'required' : ''; ?>>
            </div>
            <?php endif; ?>
            <?php if ( $s['field_company_enabled'] === '1' ): ?>
            <div class="alc-form-group">
                <input type="text" id="alc-visitor-company" placeholder="Company / Website<?php echo $s['field_company_required'] === '1' ? ' *' : ''; ?>" class="alc-form-input" <?php echo $s['field_company_required'] === '1' ? 'required' : ''; ?>>
            </div>
            <?php endif; ?>
            <?php if ( $s['field_phone_enabled'] === '1' ): ?>
            <div class="alc-form-group">
                <input type="tel" id="alc-visitor-phone" placeholder="Phone number<?php echo $s['field_phone_required'] === '1' ? ' *' : ''; ?>" class="alc-form-input" autocomplete="tel" <?php echo $s['field_phone_required'] === '1' ? 'required' : ''; ?>>
            </div>
            <?php endif; ?>
            <?php if ( $s['field_subject_enabled'] === '1' ): ?>
            <div class="alc-form-group">
                <input type="text" id="alc-visitor-subject" placeholder="Subject / Topic<?php echo $s['field_subject_required'] === '1' ? ' *' : ''; ?>" class="alc-form-input" <?php echo $s['field_subject_required'] === '1' ? 'required' : ''; ?>>
            </div>
            <?php endif; ?>
            <button id="alc-start-btn" class="alc-start-btn">
                <?php echo esc_html( $s['start_btn_label'] ); ?>
                <svg viewBox="0 0 24 24" fill="none" width="16" height="16">
                    <path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <p id="alc-form-error" class="alc-form-error" style="display:none;"></p>
        </div>

        <div id="alc-chat-area" class="alc-chat-area" style="display:none;">
            <div id="alc-messages" class="alc-messages"></div>
            <div id="alc-admin-typing" class="alc-typing-row" style="display:none;">
                <div class="alc-typing-bubble"><span></span><span></span><span></span></div>
            </div>
            <div class="alc-input-row">
                <div class="alc-input-wrapper">
                    <textarea id="alc-msg-input" class="alc-msg-input" placeholder="<?php echo esc_attr( $s['input_placeholder'] ); ?>" rows="1" aria-label="Message" maxlength="<?php echo absint( $s['max_message_length'] ); ?>"></textarea>
                    <span class="alc-char-count" id="alc-char-count"></span>
                </div>
                <button id="alc-send-btn" class="alc-send-icon-btn" aria-label="Send">
                    <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                        <path d="M22 2L11 13M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <?php if ( $s['show_branding'] === '1' ): ?>
            <div class="alc-branding">Build by <strong>Azmayen Farhan</strong></div>
            <?php endif; ?>
        </div>

    </div>
</div>
