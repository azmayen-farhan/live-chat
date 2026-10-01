<?php
/**
 * Conversations inbox (sidebar list + chat thread + visitor card).
 *
 * Included from ALC_Admin::page_conversations(); `$this` is the ALC_Admin instance.
 *
 * @var int         $conv_id   Selected conversation ID (0 = none).
 * @var string      $search    Sidebar search term.
 * @var array       $convs     Conversation rows for the sidebar.
 * @var int         $unread    Unread conversation count.
 * @var int         $open      Open conversation count.
 * @var int         $total     Total conversation count.
 * @var int         $today     Conversations started today.
 * @var object|null $conv      Selected conversation row.
 * @var array       $messages  Messages of the selected conversation.
 * @var int         $last_id   ID of the newest message shown.
 *
 * @package AzmayenLiveChat
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap alc-wrap">
    <div class="alc-topbar">
        <div class="alc-topbar-left">
            <h1 class="alc-topbar-title">Live Chat</h1>
            <?php if ( $unread > 0 ): ?>
            <span class="alc-badge-unread"><?php echo $unread; ?> unread</span>
            <?php endif; ?>
        </div>
        <div class="alc-topbar-right">
            <a href="<?php echo admin_url( 'admin.php?page=alc-quick-replies' ); ?>" class="alc-btn alc-btn-ghost alc-btn-sm">
                <span class="dashicons dashicons-editor-quote"></span> Quick Replies
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=alc-settings' ); ?>" class="alc-btn alc-btn-ghost alc-btn-sm">
                <span class="dashicons dashicons-admin-settings"></span> Settings
            </a>
        </div>
    </div>

    <div class="alc-stats-strip">
        <div class="alc-stat-pill"><span class="alc-stat-pill-num"><?php echo number_format( $total ); ?></span><span class="alc-stat-pill-label">Total</span></div>
        <div class="alc-stat-pill alc-stat-pill--accent"><span class="alc-stat-pill-num"><?php echo $unread; ?></span><span class="alc-stat-pill-label">Unread</span></div>
        <div class="alc-stat-pill alc-stat-pill--green"><span class="alc-stat-pill-num"><?php echo $open; ?></span><span class="alc-stat-pill-label">Open</span></div>
        <div class="alc-stat-pill"><span class="alc-stat-pill-num"><?php echo $today; ?></span><span class="alc-stat-pill-label">Today</span></div>
        <div class="alc-stat-pill alc-stat-pill--status">
            <?php if ( ALC_Settings::get( 'chat_enabled' ) === '1' ): ?>
            <span class="alc-live-dot"></span><span class="alc-stat-pill-label" style="color:#16a34a;">Online</span>
            <?php else: ?>
            <span style="width:7px;height:7px;border-radius:50%;background:#9ca3af;display:inline-block;"></span><span class="alc-stat-pill-label">Offline</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="alc-messenger" id="alc-messenger">
        <div class="alc-msg-sidebar">
            <div class="alc-msg-sidebar-head">
                <form method="get" action="" class="alc-sidebar-search-form">
                    <input type="hidden" name="page" value="alc-conversations">
                    <?php if ( $conv_id ): ?><input type="hidden" name="conv" value="<?php echo $conv_id; ?>"><?php endif; ?>
                    <div class="alc-sidebar-search-wrap">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="alc-sidebar-search-icon"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search conversations…" class="alc-sidebar-search-input" autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="alc-msg-sidebar-list" id="alc-conv-list">
                <?php if ( empty( $convs ) ): ?>
                <div class="alc-sidebar-empty">
                    <span class="dashicons dashicons-format-chat"></span>
                    <p><?php echo $search ? 'No results found.' : 'No conversations yet.'; ?></p>
                </div>
                <?php else: foreach ( $convs as $c ):
                    $is_active  = ( (int) $c->id === $conv_id );
                    $is_unread  = ! $c->is_read;
                    $is_closed  = $c->status === 'closed';
                    $initial    = strtoupper( substr( $c->visitor_name, 0, 1 ) );
                    $conv_url   = admin_url( 'admin.php?page=alc-conversations&conv=' . $c->id . ( $search ? '&s=' . urlencode( $search ) : '' ) );
                    $time_human = human_time_diff( strtotime( $c->last_message ), current_time( 'timestamp' ) );
                ?>
                <a href="<?php echo esc_url( $conv_url ); ?>"
                   class="alc-sidebar-item <?php echo $is_active ? 'alc-sidebar-item--active' : ''; ?> <?php echo $is_unread ? 'alc-sidebar-item--unread' : ''; ?> <?php echo $is_closed ? 'alc-sidebar-item--closed' : ''; ?>"
                   data-id="<?php echo $c->id; ?>">
                    <div class="alc-sidebar-avatar"><?php echo esc_html( $initial ); ?></div>
                    <div class="alc-sidebar-meta">
                        <div class="alc-sidebar-name">
                            <?php echo esc_html( $c->visitor_name ); ?>
                            <?php if ( $is_unread ): ?><span class="alc-sidebar-dot"></span><?php endif; ?>
                            <?php if ( $is_closed ): ?><span class="alc-sidebar-closed-tag">Closed</span><?php endif; ?>
                        </div>
                        <div class="alc-sidebar-email"><?php echo esc_html( $c->visitor_email ); ?></div>
                    </div>
                    <div class="alc-sidebar-time"><?php echo esc_html( $time_human ); ?></div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="alc-msg-main">
            <?php if ( $conv ): ?>
            <div class="alc-msg-main-header">
                <div class="alc-msg-main-avatar"><?php echo esc_html( strtoupper( substr( $conv->visitor_name, 0, 1 ) ) ); ?></div>
                <div class="alc-msg-main-info">
                    <div class="alc-msg-main-name"><?php echo esc_html( $conv->visitor_name ); ?></div>
                    <div class="alc-msg-main-sub">
                        <?php echo esc_html( $conv->visitor_email ); ?>
                        <?php if ( $conv->visitor_company ): ?>&nbsp;·&nbsp;<span><?php echo esc_html( $conv->visitor_company ); ?></span><?php endif; ?>
                        &nbsp;·&nbsp;<span>#<?php echo $conv_id; ?></span>
                        <?php if ( $conv->status === 'closed' ): ?>&nbsp;·&nbsp;<span style="color:#dc2626;">Closed</span><?php endif; ?>
                    </div>
                </div>
                <div class="alc-msg-main-actions">
                    <button class="alc-btn alc-btn-ghost alc-btn-sm alc-toggle-status-btn" data-id="<?php echo $conv_id; ?>">
                        <span class="dashicons dashicons-<?php echo $conv->status === 'open' ? 'lock' : 'unlock'; ?>"></span>
                        <?php echo $conv->status === 'open' ? 'Close' : 'Reopen'; ?>
                    </button>
                    <button class="alc-btn alc-btn-ghost alc-btn-sm alc-export-btn" data-id="<?php echo $conv_id; ?>">
                        <span class="dashicons dashicons-download"></span> Export
                    </button>
                    <button class="alc-btn alc-btn-danger alc-btn-sm alc-delete-btn" data-id="<?php echo $conv_id; ?>" data-redirect="1">
                        <span class="dashicons dashicons-trash"></span> Delete
                    </button>
                </div>
            </div>

            <div class="alc-msg-body" id="alc-admin-chat"
                 data-conv="<?php echo $conv_id; ?>" data-last-id="<?php echo $last_id; ?>">
                <div class="alc-msg-scroll" id="alc-admin-messages">
                    <?php foreach ( $messages as $msg ): ?>
                    <div class="alc-msg alc-msg-<?php echo esc_attr( $msg->sender ); ?>" data-id="<?php echo $msg->id; ?>">
                        <div class="alc-msg-sender-label">
                            <?php echo $msg->sender === 'admin' ? esc_html( ALC_Settings::get( 'admin_name', 'You' ) ) : esc_html( $conv->visitor_name ); ?>
                        </div>
                        <div class="alc-msg-bubble"><?php echo nl2br( esc_html( $msg->message ) ); ?></div>
                        <div class="alc-msg-time"><?php echo esc_html( date_i18n( 'M j, g:i a', strtotime( $msg->created_at ) ) ); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="alc-typing-indicator" id="alc-visitor-typing" style="display:none;">
                    <span></span><span></span><span></span>
                </div>

                <div class="alc-quick-replies-bar" id="alc-quick-replies-bar">
                    <span class="alc-qr-label">Quick replies:</span>
                    <div class="alc-qr-chips" id="alc-qr-chips"></div>
                </div>

                <div class="alc-admin-reply-box">
                    <div class="alc-reply-input-wrapper">
                        <textarea id="alc-admin-reply-input" placeholder="Type your reply… (Enter to send, Shift+Enter for new line)" rows="1" maxlength="<?php echo absint( ALC_Settings::get( 'max_message_length', '2000' ) ); ?>"></textarea>
                        <span class="alc-reply-char-count" id="alc-reply-char-count"></span>
                    </div>
                    <button id="alc-admin-reply-btn" class="alc-reply-send-btn">
                        <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M22 2L11 13M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <div class="alc-msg-sidebar-right">
                <div class="alc-vsidebar-card">
                    <div class="alc-vsidebar-title">Visitor</div>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Name</span><span class="alc-vinfo-val"><?php echo esc_html( $conv->visitor_name ); ?></span></div>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Email</span><span class="alc-vinfo-val"><?php echo esc_html( $conv->visitor_email ); ?></span></div>
                    <?php if ( $conv->visitor_company ): ?>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Company</span><span class="alc-vinfo-val"><?php echo esc_html( $conv->visitor_company ); ?></span></div>
                    <?php endif; ?>
                    <?php if ( $conv->visitor_phone ): ?>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Phone</span><span class="alc-vinfo-val"><?php echo esc_html( $conv->visitor_phone ); ?></span></div>
                    <?php endif; ?>
                    <?php if ( $conv->visitor_subject ): ?>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Subject</span><span class="alc-vinfo-val"><?php echo esc_html( $conv->visitor_subject ); ?></span></div>
                    <?php endif; ?>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">ID</span><span class="alc-vinfo-val">#<?php echo $conv_id; ?></span></div>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Started</span><span class="alc-vinfo-val"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $conv->created_at ) ) ); ?></span></div>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Last msg</span><span class="alc-vinfo-val"><?php echo esc_html( human_time_diff( strtotime( $conv->last_message ), current_time( 'timestamp' ) ) ); ?> ago</span></div>
                    <div class="alc-vinfo-row"><span class="alc-vinfo-label">Status</span><span class="alc-vinfo-val" style="text-transform:capitalize;font-weight:600;color:<?php echo $conv->status === 'open' ? '#16a34a' : '#dc2626'; ?>"><?php echo esc_html( $conv->status ); ?></span></div>
                </div>
                <div class="alc-vsidebar-card">
                    <div class="alc-vsidebar-title">Actions</div>
                    <button class="alc-btn alc-btn-ghost alc-btn-sm alc-toggle-status-btn" data-id="<?php echo $conv_id; ?>" style="width:100%;justify-content:center;margin-bottom:5px;">
                        <span class="dashicons dashicons-<?php echo $conv->status === 'open' ? 'lock' : 'unlock'; ?>"></span>
                        <?php echo $conv->status === 'open' ? 'Close Conversation' : 'Reopen Conversation'; ?>
                    </button>
                    <button class="alc-btn alc-btn-ghost alc-btn-sm alc-export-btn" data-id="<?php echo $conv_id; ?>" style="width:100%;justify-content:center;margin-bottom:5px;">
                        <span class="dashicons dashicons-download"></span> Export
                    </button>
                    <button class="alc-btn alc-btn-danger alc-btn-sm alc-delete-btn" data-id="<?php echo $conv_id; ?>" data-redirect="1" style="width:100%;justify-content:center;">
                        <span class="dashicons dashicons-trash"></span> Delete
                    </button>
                </div>
            </div>

            <?php else: ?>
            <div class="alc-msg-empty">
                <div class="alc-msg-empty-icon"><span class="dashicons dashicons-format-chat"></span></div>
                <h3>Select a conversation</h3>
                <p>Choose a conversation from the left panel to start chatting.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
