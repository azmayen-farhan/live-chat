<?php
/**
 * Auto Replies page (keyword rules form + list).
 *
 * Included from ALC_Admin::page_auto_replies(); `$this` is the ALC_Admin instance.
 *
 * @var array $rules Auto-reply rule rows.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap alc-wrap">
    <div class="alc-topbar">
        <div class="alc-topbar-left">
            <h1 class="alc-topbar-title">Auto Replies</h1>
            <span class="alc-badge-unread" style="background:rgba(22,163,74,0.08);border-color:rgba(22,163,74,0.2);color:#16a34a;">Keyword-triggered</span>
        </div>
        <div class="alc-topbar-right">
            <a href="<?php echo admin_url( 'admin.php?page=alc-conversations' ); ?>" class="alc-btn alc-btn-ghost alc-btn-sm">
                <span class="dashicons dashicons-format-chat"></span> Conversations
            </a>
        </div>
    </div>

    <div class="alc-ar-info-banner">
        <span class="dashicons dashicons-info-outline"></span>
        <span>When a visitor's message contains one of the rule's keywords, the matching auto-reply is sent instantly as your message. The <strong>first matching rule</strong> wins. Keywords are <strong>comma-separated</strong> and case-insensitive.</span>
    </div>

    <div id="alc-ar-notice" class="alc-notice" style="display:none;"></div>

    <div class="alc-qr-layout">

        <!-- ── Form ── -->
        <div class="alc-qr-form-card">
            <h3 class="alc-qr-card-title" id="alc-ar-form-title">Add New Auto-Reply Rule</h3>
            <input type="hidden" id="alc-ar-id" value="">

            <div class="alc-field-row alc-field-row--stacked">
                <label class="alc-label">Rule Title <span class="alc-label-desc">Internal name for this rule</span></label>
                <input type="text" id="alc-ar-title" class="alc-input" placeholder="e.g. Pricing inquiry, Hello greeting…">
            </div>

            <div class="alc-field-row alc-field-row--stacked">
                <label class="alc-label">Trigger Keywords <span class="alc-label-desc">Comma-separated — any one match fires the reply</span></label>
                <input type="text" id="alc-ar-keywords" class="alc-input" placeholder="e.g. price, pricing, cost, how much">
                <p class="alc-ar-hint">Example: <code>hello, hi, hey</code> — fires on any of these words in a visitor message.</p>
            </div>

            <div class="alc-field-row alc-field-row--stacked">
                <label class="alc-label">Auto-Reply Message <span class="alc-label-desc">Sent automatically as your message</span></label>
                <textarea id="alc-ar-reply" class="alc-input alc-textarea" rows="5" placeholder="Type the auto-reply that will be sent when a keyword is matched…"></textarea>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="alc-btn alc-btn-primary" id="alc-ar-save-btn">
                    <span class="dashicons dashicons-saved"></span> Save Rule
                </button>
                <button class="alc-btn alc-btn-ghost" id="alc-ar-cancel-btn" style="display:none;">Cancel</button>
            </div>
        </div>

        <!-- ── List ── -->
        <div class="alc-qr-list-card">
            <h3 class="alc-qr-card-title">
                Active Rules
                <span class="alc-ar-count-badge"><?php echo count( $rules ); ?></span>
            </h3>
            <div class="alc-ar-list" id="alc-ar-list">
                <?php if ( empty( $rules ) ): ?>
                <div class="alc-qr-empty">
                    <span class="dashicons dashicons-superhero-alt" style="font-size:32px;width:32px;height:32px;margin-bottom:8px;display:block;"></span>
                    No auto-reply rules yet. Create one to automatically respond to common questions.
                </div>
                <?php else: foreach ( $rules as $r ):
                    $kws = array_filter( array_map( 'trim', explode( ',', $r->keywords ) ) );
                ?>
                <div class="alc-ar-item <?php echo $r->is_enabled ? '' : 'alc-ar-item--disabled'; ?>"
                     data-id="<?php echo $r->id; ?>"
                     data-title="<?php echo esc_attr( $r->title ); ?>"
                     data-keywords="<?php echo esc_attr( $r->keywords ); ?>"
                     data-reply="<?php echo esc_attr( $r->reply ); ?>">
                    <div class="alc-ar-item-top">
                        <span class="alc-ar-item-title"><?php echo esc_html( $r->title ); ?></span>
                        <span class="alc-ar-item-status <?php echo $r->is_enabled ? 'alc-ar-status--on' : 'alc-ar-status--off'; ?>">
                            <?php echo $r->is_enabled ? 'Active' : 'Paused'; ?>
                        </span>
                    </div>
                    <div class="alc-ar-keywords">
                        <?php foreach ( $kws as $kw ): ?>
                        <span class="alc-ar-kw-chip"><?php echo esc_html( $kw ); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="alc-ar-preview"><?php echo esc_html( wp_trim_words( $r->reply, 12, '…' ) ); ?></div>
                    <div class="alc-ar-item-actions">
                        <button class="alc-btn alc-btn-ghost alc-btn-sm alc-ar-edit-btn" data-id="<?php echo $r->id; ?>">
                            <span class="dashicons dashicons-edit"></span> Edit
                        </button>
                        <button class="alc-btn alc-btn-ghost alc-btn-sm alc-ar-toggle-btn" data-id="<?php echo $r->id; ?>">
                            <span class="dashicons dashicons-<?php echo $r->is_enabled ? 'controls-pause' : 'controls-play'; ?>"></span>
                            <?php echo $r->is_enabled ? 'Pause' : 'Activate'; ?>
                        </button>
                        <button class="alc-btn alc-btn-danger alc-btn-sm alc-ar-delete-btn" data-id="<?php echo $r->id; ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div>
</div>
