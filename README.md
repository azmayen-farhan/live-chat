# Azmayen Live Chat

Real-time, human live chat for WordPress. Visitors get a floating chat bubble and a messenger-style window; you answer from an inbox inside wp-admin. Everything runs on your own server and database, with no external chat service and no API key.

| | |
| --- | --- |
| **Version** | 1.4.0 |
| **Requires** | WordPress 5.0+, PHP 7.4+ with the `mbstring` extension |
| **License** | GPL v2 or later |
| **Author** | [Azmayen Farhan](https://azmayenfarhan.com) |

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Using the dashboard](#using-the-dashboard)
- [Email alerts](#email-alerts)
- [How it works](#how-it-works)
- [Settings reference](#settings-reference)
- [Developer guide](#developer-guide)
- [Security notes](#security-notes)
- [Uninstalling](#uninstalling)
- [Known limitations](#known-limitations)
- [Changelog](#changelog)

## Features

### For visitors

- Floating launcher bubble (bottom right or bottom left) with a pulse animation and an unread badge. Esc closes it.
- Pre-chat form with name, email, company, phone and subject. You choose which fields appear and which are required.
- The conversation survives page reloads (the conversation ID is kept in the browser's `localStorage`).
- Typing indicator while you are typing, sound when you reply, character counter, Enter to send and Shift+Enter for a new line.
- The popup shrinks to fit narrow phone screens.

### For you (wp-admin → Live Chat)

- Messenger-style inbox: conversation list with search, unread dots and *Closed* tags, the message thread and a visitor info card. It updates live without reloading the page.
- Stats strip: total, unread, open and today's conversations.
- Close or reopen a conversation, export it as a `.txt` file, or delete it.
- **Quick Replies**: saved snippets you insert into the reply box with one click.
- **Auto Replies**: keyword rules that answer common questions instantly.
- Typing indicator, 12 built-in notification sounds (generated with the Web Audio API, no audio files), a flashing browser-tab title with the unread count, and an unread badge in the admin menu and admin bar.
- Email alerts for new conversations, plus 10-minute follow-up emails when a message goes unanswered.
- Admin avatar from the media library, and a tabbed Settings page that saves over AJAX.

## Requirements

- WordPress 5.0 or newer
- PHP 7.4 or newer, with `mbstring` (auto-reply keyword matching uses the `mb_*` functions)
- Working outbound email (`wp_mail`) if you want email alerts
- WP-Cron (the WordPress default) for follow-up emails

## Installation

### From a zip

1. Make sure the zip has the `azmayen-live-chat/` folder at its top level (the supplied `azmayen-live-chat.zip` already does).
2. In wp-admin go to **Plugins → Add New → Upload Plugin**, choose the zip, then **Install Now** and **Activate**.

### Manually

Upload the `azmayen-live-chat/` folder to `wp-content/plugins/` (SFTP or your host's file manager) and activate it on the **Plugins** screen.

On activation the plugin creates its four database tables and saves the default settings.

### Upgrading from the single-file version

This version uses the same table and option names as the original single-file plugin (`live-chat-2/live-chat.php`), so your conversations, rules and settings carry over.

> **Do not click *Delete* on the old plugin in wp-admin.** The old version's uninstall routine drops every table and option. Remove it by file instead.

1. Take a database backup.
2. **Deactivate** the old plugin on the Plugins screen.
3. Delete the old plugin folder over SFTP or your host's file manager.
4. Install and activate this version.

Never run both versions at the same time: they declare the same class names and PHP will stop with a fatal error.

## Quick start

1. Go to **Live Chat → Settings → General** and set your name, the chat window title, the welcome message and an avatar.
2. Pick your accent colour under **Appearance** and click **Save Settings**.
3. Open your site. The chat bubble appears in the corner.
4. Start a test chat from the bubble, then open **Live Chat → Conversations** to reply.
5. Optional: add **Quick Replies** and **Auto Replies** for the questions you get most.

## Using the dashboard

### Conversations

- The left list shows the newest activity first. Type in the search box and press Enter to filter by visitor name or email.
- Opening a conversation marks it as read. Reply with Enter (Shift+Enter inserts a new line). Replying to a closed conversation reopens it.
- The right-hand card shows the visitor's details, the conversation ID and its status.
- **Close / Reopen** changes the status, **Export** downloads the transcript as `chat-<id>.txt`, and **Delete** permanently removes the conversation and all its messages.

### Quick Replies

Create a title and a message. Inside an open conversation your quick replies appear as chips above the reply box. Clicking a chip puts its text into the reply box without sending it, so you can edit it first.

### Auto Replies

A rule is a title, a comma-separated list of keywords and a reply. When a visitor's message contains any keyword, the reply is posted immediately as your message.

- Matching is a case-insensitive **substring** match. The keyword `hi` also matches "this" and "which", so prefer distinctive words or phrases.
- Rules are checked in the order they were created and the first match wins. A message triggers at most one auto-reply.
- A rule can be paused and re-activated without deleting it.
- Auto-replies do not count as *your* reply for follow-up emails (see below).

### Settings

Seven tabs: General, Appearance, Widget & Button, Pre-chat Form, Notifications, Business Hours and Advanced. **Save Settings** submits over AJAX, so the page does not reload. Not every setting is wired up yet; the [Settings reference](#settings-reference) lists which ones take effect.

## Email alerts

**New conversation.** When `admin_email_notify` is on, a new conversation sends an email to the WordPress admin email address (*Settings → General → Administration Email Address*) with a link to the conversation. Returning to a conversation that is still open does not send another one.

**Follow-up alerts.** When `followup_enabled` is on, the plugin checks 10 minutes after each message:

- A visitor message with no human reply from you sends an email to you.
- Your reply with no response from the visitor sends an email to the visitor's registered address, with a snippet of your message and a link back to the site.

Closed conversations are skipped. Each unanswered message schedules its own check, so several quick messages can produce several emails.

Follow-ups run on WP-Cron, which only fires when someone loads a page. On a low-traffic site the emails can arrive late; a real server cron job that calls `wp-cron.php` makes the timing reliable.

## How it works

1. The visitor opens the bubble and submits the pre-chat form (`alc_start_chat`). The server creates a conversation (or resumes the open one for that email), posts your welcome message and emails you.
2. The browser stores the conversation ID, so a page reload restores the chat.
3. While the popup is open, the widget asks the server for new messages every **3 seconds** (`alc_poll_messages`). The dashboard polls the open thread every 3 seconds and the conversation list every 5 seconds.
4. A visitor message (`alc_send_message`) is stored, checked against your auto-reply rules, and a follow-up check is scheduled.
5. Typing indicators are short-lived flags (WordPress transients, 5 seconds) set by `alc_typing` and `alc_admin_typing`.

Because it polls rather than using WebSockets, each open chat window makes a small request to `admin-ajax.php` every few seconds. That is fine for small and medium sites; on very busy sites you may want a longer interval (`pollInterval` in `ALC_Public::enqueue_assets()`).

## Settings reference

All settings live in one WordPress option, `alc_settings`. `ALC_Settings::defaults()` is the whitelist: the save handler ignores any key that is not listed there.

### Settings that take effect today

#### General

| Setting | Default | Effect |
| --- | --- | --- |
| `chat_enabled` | `1` | Master switch. When off, the widget is not printed on the site. |
| `admin_name` | `Azmayen` | Your display name: initials fallback when there is no avatar, sender label in the dashboard thread, name used in follow-up emails. |
| `admin_avatar_url` | *(empty)* | Photo shown in the widget header (picked from the media library). |
| `chat_title` | `Chat with Azmayen` | Heading of the chat window. |
| `welcome_message` | `Hi there! 👋 How can I help you today?` | Posted automatically as the first message of every new conversation. |
| `online_message` | `We're online — ready to help` | Status line under the title. It always reads as "online"; there is no offline mode yet. |
| `response_time` | `Usually replies within a few minutes` | Line of text above the pre-chat form. |
| `input_placeholder` | `Type your message…` | Placeholder text in the visitor message box. |

#### Appearance

| Setting | Default | Effect |
| --- | --- | --- |
| `primary_color` | `#dc2626` | Accent colour: header, launcher bubble, buttons, focus rings. |
| `show_branding` | `1` | Shows the small "Build by Azmayen Farhan" footer under the message box. |

#### Widget & Button

| Setting | Default | Effect |
| --- | --- | --- |
| `position` | `right` | `right` or `left` bottom corner. |

#### Pre-chat Form

| Setting | Default | Effect |
| --- | --- | --- |
| `start_btn_label` | `Start Chat` | Label of the button that starts the chat. |

#### Notifications

| Setting | Default | Effect |
| --- | --- | --- |
| `sound_enabled` | `1` | Plays a sound in the visitor's widget when you reply. |
| `notification_sound` | `chime` | Which of the 12 sounds the widget and the dashboard use. |
| `admin_email_notify` | `1` | Emails you when a new conversation starts (sent to the WordPress admin email, see limitations). |
| `notify_email` | *(empty)* | Fallback recipient for follow-up alerts addressed to you. |
| `followup_enabled` | `1` | Master switch for the 10-minute follow-up emails. |
| `followup_from_email` | *(empty)* | Sender address for follow-up emails. Falls back to the WordPress admin email. |
| `followup_to_email` | *(empty)* | Where *your* follow-up alerts go. Falls back to Notification Email, then the WordPress admin email. |

#### Advanced

| Setting | Default | Effect |
| --- | --- | --- |
| `max_message_length` | `2000` | Character limit and counter in the visitor and dashboard message boxes (the server separately caps visitor messages at 2000). |

Pre-chat fields:

| Field | Shown by default | Required by default |
| --- | --- | --- |
| Full name (`field_name_enabled` / `field_name_required`) | yes | yes |
| Email address (`field_email_enabled` / `field_email_required`) | yes | yes |
| Company / website (`field_company_enabled` / `field_company_required`) | yes | no |
| Phone number (`field_phone_enabled` / `field_phone_required`) | no | no |
| Subject / topic (`field_subject_enabled` / `field_subject_required`) | no | no |

Name and email are always required by the server, whatever these toggles say. See [Known limitations](#known-limitations).

### Saved but not yet applied

These settings have a field on the Settings page and are stored, but no code reads them yet, so changing them has no visible effect. (`offline_message` is passed to the widget but never used.)

| Settings tab | Saved but not yet applied |
| --- | --- |
| General | `admin_title`, `chat_subtitle`, `offline_message`, `away_message` |
| Appearance | `header_text_color`, `chat_bg_color`, `bubble_admin_color`, `bubble_visitor_color`, `window_width`, `window_height`, `border_radius` |
| Widget & Button | `button_icon`, `button_label`, `button_size`, `widget_offset_x`, `widget_offset_y` |
| Notifications | `desktop_notify` |
| Business Hours | `business_hours_enabled`, `bh_timezone`, `bh_mon … bh_sun`, `bh_open`, `bh_close` |
| Advanced | `auto_close_hours`, `rate_limit_messages`, `rate_limit_chats`, `conversation_retention`, `custom_css` |

## Developer guide

### File structure

```text
azmayen-live-chat/
├── admin/
│   ├── views/
│   │   ├── auto-replies.php                      # Auto Replies page
│   │   ├── conversations.php                     # Inbox: sidebar, thread, visitor card
│   │   ├── quick-replies.php                     # Quick Replies page
│   │   └── settings.php                          # Tabbed Settings page
│   └── class-alc-admin.php                       # `ALC_Admin`: menus, admin-bar badge, asset loading, page controllers
├── assets/
│   ├── css/
│   │   ├── admin.css                             # Dashboard styles
│   │   └── widget.css                            # Front-end widget styles
│   └── js/
│       ├── admin.js                              # Dashboard behaviour: live thread, quick/auto replies, settings save (jQuery)
│       ├── sounds.js                             # The 12 notification sounds (Web Audio), shared by widget and dashboard
│       └── widget.js                             # Visitor-side behaviour: form, polling, typing, sounds
├── includes/
│   ├── class-alc-ajax-handlers.php               # `ALC_Ajax_Handlers`: every `wp_ajax_*` endpoint (visitor + admin)
│   ├── class-alc-database.php                    # `ALC_Database`: table schema and all queries (conversations, messages, replies)
│   ├── class-alc-followup.php                    # `ALC_Followup`: 10-minute follow-up emails via WP-Cron
│   ├── class-alc-plugin.php                      # `ALC_Plugin`: loads classes, creates/updates the schema, boots front end or admin
│   ├── class-alc-security.php                    # `ALC_Security`: nonces, rate limiting, visitor IP, input sanitising
│   └── class-alc-settings.php                    # `ALC_Settings`: defaults and read/save of the `alc_settings` option
├── public/
│   ├── views/
│   │   └── widget.php                            # Widget markup (bubble, pre-chat form, chat window)
│   └── class-alc-public.php                      # `ALC_Public`: enqueues widget assets, prints the widget in the footer
├── README.md
├── azmayen-live-chat.php                         # Plugin header, constants, activation hooks, bootstrap
└── uninstall.php                                 # Removes all plugin data when the plugin is deleted
```

Every directory also contains an `index.php` ("Silence is golden") and every PHP file starts with an `ABSPATH` guard, so nothing can be run by direct URL.

### Boot sequence

1. `azmayen-live-chat.php` defines the constants (`ALC_VERSION`, `ALC_PLUGIN_DIR`, `ALC_PLUGIN_URL`, …) and registers the activation/deactivation hooks.
2. `ALC_Plugin::run()` runs on `plugins_loaded`. It loads the core classes, re-runs the schema routine if `alc_db_version` differs from `ALC_VERSION`, and starts `ALC_Ajax_Handlers` and `ALC_Followup` on every request.
3. In wp-admin (including `admin-ajax.php`) it starts `ALC_Admin`; on the public site it starts `ALC_Public`.
4. The admin page methods prepare their data and then `include` a file from `admin/views/`. Views run inside the method, so they can use its variables and `$this`.

### Database

Table names are prefixed with your WordPress table prefix (`wp_` by default). The schema lives in `ALC_Database::create_tables()` and is applied with `dbDelta`.

| Table | Columns |
| --- | --- |
| `alc_conversations` | `id`, `visitor_name`, `visitor_email`, `visitor_company`, `visitor_phone`, `visitor_subject`, `visitor_ip`, `status` (`open`/`closed`), `is_read`, `last_message`, `created_at` |
| `alc_messages` | `id`, `conversation_id`, `sender` (`visitor`/`admin`), `message`, `is_read`, `created_at` |
| `alc_quick_replies` | `id`, `title`, `message`, `sort_order`, `created_at` |
| `alc_auto_replies` | `id`, `title`, `keywords`, `reply`, `is_enabled`, `sort_order`, `created_at` |

Options: `alc_settings` (all settings) and `alc_db_version` (schema version).

### AJAX endpoints

All endpoints go through `admin-ajax.php` and are registered in `ALC_Ajax_Handlers`.

**Visitor endpoints** (available to logged-out visitors)

| Action | Nonce | Purpose |
| --- | --- | --- |
| `alc_start_chat` | `alc_visitor` | Start or resume a conversation. Limited to 3 per 10 minutes per IP. |
| `alc_send_message` | `alc_visitor` | Store a visitor message, run auto-replies, schedule the follow-up. Limited to 20 per minute per IP, 2000 characters. |
| `alc_poll_messages` | none | Return messages newer than `since_id` and whether you are typing. |
| `alc_typing` | none | Set the visitor typing flag (5 seconds). |

**Admin endpoints** (require the `manage_options` capability)

| Action | Nonce | Purpose |
| --- | --- | --- |
| `alc_admin_reply` | `alc_admin_action` | Send a reply, reopen the conversation, schedule the follow-up. |
| `alc_admin_poll` | `alc_admin_action` | New messages, visitor typing flag and status for one conversation. |
| `alc_admin_poll_list` | `alc_admin_action` | Unread count and the 30 most recent conversations. |
| `alc_admin_mark_read` | `alc_admin_action` | Mark a conversation read. |
| `alc_admin_toggle_status` | `alc_admin_action` | Close or reopen. |
| `alc_admin_export_conv` | `alc_admin_action` | Return the transcript text. |
| `alc_admin_delete_conv` | `alc_admin_action` | Delete a conversation and its messages. |
| `alc_admin_typing` | none (capability only) | Set the admin typing flag (5 seconds). |
| `alc_admin_save_settings` | `alc_admin_settings` | Save settings (whitelisted keys only). |
| `alc_admin_get_quick_replies` | none (capability only) | List quick replies. |
| `alc_admin_save_quick_reply` | `alc_admin_action` | Create or update a quick reply. |
| `alc_admin_delete_quick_reply` | `alc_admin_action` | Delete a quick reply. |
| `alc_admin_get_auto_replies` | none (capability only) | List auto-reply rules. |
| `alc_admin_save_auto_reply` | `alc_admin_action` | Create or update a rule (title, keywords and reply are required). |
| `alc_admin_delete_auto_reply` | `alc_admin_action` | Delete a rule. |
| `alc_admin_toggle_auto_reply` | `alc_admin_action` | Pause or activate a rule. |

### Hooks

The plugin registers two WP-Cron action hooks. Both are scheduled as single events 10 minutes ahead.

| Hook | Arguments |
| --- | --- |
| `alc_visitor_followup` | `$conv_id`, `$visitor_msg_id`, `$auto_reply_id`, `$message_text` |
| `alc_admin_followup` | `$conv_id`, `$admin_msg_id`, `$message_text` |

There are no filters yet.

### Common tasks

#### Add a setting

1. Add the key and its default to `ALC_Settings::$defaults`. This also whitelists it for saving; values are passed through `sanitize_text_field()`.
2. Add a field to `admin/views/settings.php`. Checkboxes are sent as `1` or `0` by `assets/js/admin.js`.
3. Read it with `ALC_Settings::get( 'key' )`. If the widget's JavaScript needs it, add it to the `settings` array in `ALC_Public::enqueue_assets()`.

**Add an AJAX action.** Add the action name to `$public_actions` or `$admin_actions` in `ALC_Ajax_Handlers::__construct()` and write a public method named after the action without the `alc_` prefix (`alc_admin_foo` calls `admin_foo()`). Start admin handlers with `ALC_Security::verify_admin_nonce()`.

**Add a notification sound.** Add a function to `assets/js/sounds.js` (it receives an `AudioContext`) and add a matching tile to the `$sounds` array in `admin/views/settings.php`.

**Change the schema.** Edit the `CREATE TABLE` statements in `ALC_Database::create_tables()` and bump the version. On the next request `ALC_Plugin::run()` sees that `alc_db_version` is out of date and runs `dbDelta` again.

**Release a new version.** Update `Version:` in the plugin header and `ALC_VERSION` in `azmayen-live-chat.php`, and keep the two identical. `ALC_VERSION` is also used to cache-bust the CSS and JS files.

### Checking your changes

```bash
# PHP syntax check for every file
find . -name '*.php' -print0 | xargs -0 -n1 php -l

# JavaScript syntax check
for f in assets/js/*.js; do node --check "$f"; done
```

## Security notes

- Direct access is blocked in every PHP file (`ABSPATH` guard) and every directory has an `index.php`.
- Creating a chat and sending a visitor message require a WordPress nonce and are rate limited per IP with transients. All admin endpoints require the `manage_options` capability, and the ones that change data also require a nonce.
- Input is run through `wp_unslash()` plus `sanitize_text_field()`, `sanitize_email()` or `wp_strip_all_tags()`. Messages are stored through `wp_kses_post()`, database queries use `$wpdb->prepare()` or `$wpdb->insert/update/delete`, and the templates escape their output (`esc_html`, `esc_attr`, `esc_url`). The JavaScript builds message bubbles with escaped text.
- Visitor IP addresses are stored with each conversation. Mention this in your privacy policy.

## Uninstalling

- **Deactivating** the plugin keeps all data.
- **Deleting** it from the Plugins screen runs `uninstall.php`, which permanently removes the four tables, the `alc_settings` and `alc_db_version` options, and any pending follow-up emails. There is no confirmation option, so export anything you need first.

## Known limitations

- **Unfinished settings.** The settings in the "Saved but not yet applied" table above are stored but do nothing yet (appearance colours and size, launcher button options, business hours, auto-close, retention, rate-limit fields, custom CSS, desktop notifications).
- **Name and email are always required.** The server rejects a new chat without them, so turning those two fields off (or not required) on the Pre-chat Form tab prevents chats from starting. Company, phone and subject are genuinely optional.
- **Fixed server limits.** Visitor messages are capped at 2000 characters, and each IP may start 3 chats per 10 minutes and send 20 messages per minute. The two rate-limit settings are not used, and *Max Message Length* only affects the message boxes in the browser.
- **New-conversation email recipient.** The new-conversation email always goes to the WordPress admin email. *Notification Email* is only used as a fallback for follow-up alerts.
- **Own-message colour.** Visitor message bubbles keep a fixed colour; *Primary colour* does not change them.
- **Dashboard sidebar refresh.** The live refresh (every 5 seconds) shows the 30 most recent conversations and ignores the search box, so search results revert until you reload the page.
- **Exports.** Every admin message in an export is labelled "Azmayen", whatever *Your Name* is set to.
- **No translations.** The text domain is declared but strings are hard-coded English.
- **Polling, not WebSockets.** See [How it works](#how-it-works).

## Changelog

### 1.4.0

- First multi-file release: the single-file plugin was split into classes (`includes/`, `public/`, `admin/`), view templates (`views/`) and real CSS and JavaScript files (`assets/`). Behaviour is unchanged.
- The widget and dashboard now share one notification-sound table (`assets/js/sounds.js`) instead of two identical copies.
- Added `uninstall.php` (replaces the uninstall hook).
- The schema routine now re-runs automatically after the plugin files are updated.
- The version in the plugin header (1.3.0) now matches `ALC_VERSION` (1.4.0).

## License

GPL v2 or later. See <https://www.gnu.org/licenses/gpl-2.0.html>.

Built by [Azmayen Farhan](https://azmayenfarhan.com).
