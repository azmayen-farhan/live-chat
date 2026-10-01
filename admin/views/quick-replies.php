<?php
/**
 * Quick Replies page (form + saved list).
 *
 * Included from ALC_Admin::page_quick_replies(); `$this` is the ALC_Admin instance.
 *
 * @var array $replies Saved quick-reply rows.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap alc-wrap">
    <div class="alc-topbar">
        <div class="alc-topbar-left">
            <h1 class="alc-topbar-title">Quick Replies</h1>
        </div>
        <div class="alc-topbar-right">
            <a href="<?php echo admin_url( 'admin.php?page=alc-conversations' ); ?>" class="alc-btn alc-btn-ghost alc-btn-sm">
                <span class="dashicons dashicons-format-chat"></span> Conversations
            </a>
        </div>
    </div>

    <div id="alc-qr-notice" class="alc-notice" style="display:none;"></div>

    <div class="alc-qr-layout">
        <div class="alc-qr-form-card">
            <h3 class="alc-qr-card-title">Add / Edit Quick Reply</h3>
            <input type="hidden" id="alc-qr-id" value="">
            <div class="alc-field-row alc-field-row--stacked">
                <label class="alc-label">Title <span class="alc-label-desc">Short name to identify this reply</span></label>
                <input type="text" id="alc-qr-title" class="alc-input" placeholder="e.g. Welcome message, Pricing info…">
            </div>
            <div class="alc-field-row alc-field-row--stacked">
                <label class="alc-label">Message <span class="alc-label-desc">The reply text that will be inserted</span></label>
                <textarea id="alc-qr-message" class="alc-input alc-textarea" rows="4" placeholder="Type the reply message here…"></textarea>
            </div>
            <div style="display:flex;gap:8px;">
                <button class="alc-btn alc-btn-primary" id="alc-qr-save-btn"><span class="dashicons dashicons-saved"></span> Save</button>
                <button class="alc-btn alc-btn-ghost" id="alc-qr-cancel-btn" style="display:none;">Cancel</button>
            </div>
        </div>

        <div class="alc-qr-list-card">
            <h3 class="alc-qr-card-title">Saved Replies (<?php echo count( $replies ); ?>)</h3>
            <div class="alc-qr-list" id="alc-qr-list">
                <?php if ( empty( $replies ) ): ?>
                <div class="alc-qr-empty">No quick replies yet. Create one to speed up your responses.</div>
                <?php else: foreach ( $replies as $r ): ?>
                <div class="alc-qr-item" data-id="<?php echo $r->id; ?>" data-title="<?php echo esc_attr( $r->title ); ?>" data-message="<?php echo esc_attr( $r->message ); ?>">
                    <div class="alc-qr-item-title"><?php echo esc_html( $r->title ); ?></div>
                    <div class="alc-qr-item-preview"><?php echo esc_html( wp_trim_words( $r->message, 15, '…' ) ); ?></div>
                    <div class="alc-qr-item-actions">
                        <button class="alc-qr-edit-btn" data-id="<?php echo $r->id; ?>"><span class="dashicons dashicons-edit"></span></button>
                        <button class="alc-qr-delete-btn" data-id="<?php echo $r->id; ?>"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
