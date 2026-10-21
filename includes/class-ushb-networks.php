<?php
/**
 * The catalogue of sharing destinations.
 *
 * @package Unstoppable_Share_Buttons
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Every network the plugin knows how to share to.
 */
class USHB_Networks {

	/**
	 * Returns the full network catalogue.
	 *
	 * Keys understood per network:
	 *  - label  string Human name shown on buttons.
	 *  - color  string Brand hex, used by the brand colour palette.
	 *  - icon   string Icon slug from USHB_Icons::all().
	 *  - group  string social|messaging|read-later|ai|utility.
	 *  - url    string Share endpoint containing {placeholders}.
	 *  - action string Client-side behaviour used instead of a URL.
	 *  - popup  bool   Open the endpoint in a centred popup window.
	 *  - note   string Setup hint shown in the admin.
	 *
	 * @return array Network definitions keyed by slug.
	 */
	public static function all() {
		static $networks = null;

		if ( null !== $networks ) {
			return $networks;
		}

		$networks = array(

			// Mainstream social.
			'facebook'      => array(
				'label' => __( 'Facebook', 'unstoppable-share-buttons' ),
				'color' => '#1877F2',
				'icon'  => 'facebook',
				'group' => 'social',
				'url'   => 'https://www.facebook.com/sharer/sharer.php?u={url}',
				'popup' => true,
			),
			'x'             => array(
				'label' => __( 'X', 'unstoppable-share-buttons' ),
				'color' => '#000000',
				'icon'  => 'x',
				'group' => 'social',
				'url'   => 'https://twitter.com/intent/tweet?url={url}&text={title}{via_param}{tags_param}',
				'popup' => true,
			),
			'linkedin'      => array(
				'label' => __( 'LinkedIn', 'unstoppable-share-buttons' ),
				'color' => '#0A66C2',
				'icon'  => 'linkedin',
				'group' => 'social',
				'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
				'popup' => true,
			),
			'pinterest'     => array(
				'label' => __( 'Pinterest', 'unstoppable-share-buttons' ),
				'color' => '#BD081C',
				'icon'  => 'pinterest',
				'group' => 'social',
				'url'   => 'https://www.pinterest.com/pin/create/button/?url={url}&media={image}&description={title}',
				'popup' => true,
			),
			'reddit'        => array(
				'label' => __( 'Reddit', 'unstoppable-share-buttons' ),
				'color' => '#FF4500',
				'icon'  => 'reddit',
				'group' => 'social',
				'url'   => 'https://www.reddit.com/submit?url={url}&title={title}',
				'popup' => true,
			),
			'bluesky'       => array(
				'label' => __( 'Bluesky', 'unstoppable-share-buttons' ),
				'color' => '#0285FF',
				'icon'  => 'bluesky',
				'group' => 'social',
				'url'   => 'https://bsky.app/intent/compose?text={title}%20{url}',
				'popup' => true,
			),
			'threads'       => array(
				'label' => __( 'Threads', 'unstoppable-share-buttons' ),
				'color' => '#000000',
				'icon'  => 'threads',
				'group' => 'social',
				'url'   => 'https://www.threads.net/intent/post?text={title}%20{url}',
				'popup' => true,
			),
			'mastodon'      => array(
				'label'  => __( 'Mastodon', 'unstoppable-share-buttons' ),
				'color'  => '#6364FF',
				'icon'   => 'mastodon',
				'group'  => 'social',
				'action' => 'mastodon',
				'note'   => __( 'Asks visitors which server they use, then remembers it in their own browser.', 'unstoppable-share-buttons' ),
			),
			'tumblr'        => array(
				'label' => __( 'Tumblr', 'unstoppable-share-buttons' ),
				'color' => '#36465D',
				'icon'  => 'tumblr',
				'group' => 'social',
				'url'   => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}&title={title}&caption={excerpt}',
				'popup' => true,
			),
			'vk'            => array(
				'label' => __( 'VK', 'unstoppable-share-buttons' ),
				'color' => '#0077FF',
				'icon'  => 'vk',
				'group' => 'social',
				'url'   => 'https://vk.com/share.php?url={url}&title={title}&image={image}',
				'popup' => true,
			),
			'odnoklassniki' => array(
				'label' => __( 'OK.ru', 'unstoppable-share-buttons' ),
				'color' => '#EE8208',
				'icon'  => 'odnoklassniki',
				'group' => 'social',
				'url'   => 'https://connect.ok.ru/offer?url={url}&title={title}&imageUrl={image}',
				'popup' => true,
			),
			'xing'          => array(
				'label' => __( 'Xing', 'unstoppable-share-buttons' ),
				'color' => '#026466',
				'icon'  => 'xing',
				'group' => 'social',
				'url'   => 'https://www.xing.com/spi/shares/new?url={url}',
				'popup' => true,
			),
			'weibo'         => array(
				'label' => __( 'Weibo', 'unstoppable-share-buttons' ),
				'color' => '#E6162D',
				'icon'  => 'weibo',
				'group' => 'social',
				'url'   => 'https://service.weibo.com/share/share.php?url={url}&title={title}&pic={image}',
				'popup' => true,
			),
			'qq'            => array(
				'label' => __( 'QQ', 'unstoppable-share-buttons' ),
				'color' => '#1EBAFC',
				'icon'  => 'qq',
				'group' => 'social',
				'url'   => 'https://connect.qq.com/widget/shareqq/index.html?url={url}&title={title}&pics={image}&summary={excerpt}',
				'popup' => true,
			),
			'wechat'        => array(
				'label'  => __( 'WeChat', 'unstoppable-share-buttons' ),
				'color'  => '#07C160',
				'icon'   => 'wechat',
				'group'  => 'social',
				'action' => 'qr',
				'note'   => __( 'WeChat has no web share endpoint, so this shows a scannable QR code instead.', 'unstoppable-share-buttons' ),
			),
			'snapchat'      => array(
				'label' => __( 'Snapchat', 'unstoppable-share-buttons' ),
				'color' => '#FFC800',
				'icon'  => 'snapchat',
				'group' => 'social',
				'url'   => 'https://www.snapchat.com/scan?attachmentUrl={url}',
				'popup' => true,
			),
			'nextdoor'      => array(
				'label' => __( 'Nextdoor', 'unstoppable-share-buttons' ),
				'color' => '#7DBB00',
				'icon'  => 'nextdoor',
				'group' => 'social',
				'url'   => 'https://nextdoor.com/sharekit/?source={sitename}&body={title}%20{url}',
				'popup' => true,
			),
			'mewe'          => array(
				'label' => __( 'MeWe', 'unstoppable-share-buttons' ),
				'color' => '#17377F',
				'icon'  => 'mewe',
				'group' => 'social',
				'url'   => 'https://mewe.com/share?link={url}',
				'popup' => true,
			),
			'minds'         => array(
				'label' => __( 'Minds', 'unstoppable-share-buttons' ),
				'color' => '#7D7D7D',
				'icon'  => 'minds',
				'group' => 'social',
				'url'   => 'https://www.minds.com/newsfeed/subscribed?intentUrl={url}',
				'popup' => true,
			),
			'diaspora'      => array(
				'label' => __( 'Diaspora', 'unstoppable-share-buttons' ),
				'color' => '#000000',
				'icon'  => 'diaspora',
				'group' => 'social',
				'url'   => 'https://share.diasporafoundation.org/?title={title}&url={url}',
				'popup' => true,
			),
			'livejournal'   => array(
				'label' => __( 'LiveJournal', 'unstoppable-share-buttons' ),
				'color' => '#00B0EA',
				'icon'  => 'livejournal',
				'group' => 'social',
				'url'   => 'https://www.livejournal.com/update.bml?subject={title}&event={url}',
				'popup' => true,
			),
			'blogger'       => array(
				'label' => __( 'Blogger', 'unstoppable-share-buttons' ),
				'color' => '#FF5722',
				'icon'  => 'blogger',
				'group' => 'social',
				'url'   => 'https://www.blogger.com/blog-this.g?u={url}&n={title}&t={excerpt}',
				'popup' => true,
			),
			'flipboard'     => array(
				'label' => __( 'Flipboard', 'unstoppable-share-buttons' ),
				'color' => '#E12828',
				'icon'  => 'flipboard',
				'group' => 'social',
				'url'   => 'https://share.flipboard.com/bookmarklet/popout?v=2&url={url}&title={title}',
				'popup' => true,
			),
			'hackernews'    => array(
				'label' => __( 'Hacker News', 'unstoppable-share-buttons' ),
				'color' => '#FF6600',
				'icon'  => 'hackernews',
				'group' => 'social',
				'url'   => 'https://news.ycombinator.com/submitlink?u={url}&t={title}',
				'popup' => true,
			),
			'digg'          => array(
				'label' => __( 'Digg', 'unstoppable-share-buttons' ),
				'color' => '#000000',
				'icon'  => 'digg',
				'group' => 'social',
				'url'   => 'https://digg.com/submit?url={url}&title={title}',
				'popup' => true,
			),

			// Messaging.
			'whatsapp'      => array(
				'label' => __( 'WhatsApp', 'unstoppable-share-buttons' ),
				'color' => '#25D366',
				'icon'  => 'whatsapp',
				'group' => 'messaging',
				'url'   => 'https://api.whatsapp.com/send?text={title}%20{url}',
				'popup' => true,
			),
			'telegram'      => array(
				'label' => __( 'Telegram', 'unstoppable-share-buttons' ),
				'color' => '#26A5E4',
				'icon'  => 'telegram',
				'group' => 'messaging',
				'url'   => 'https://t.me/share/url?url={url}&text={title}',
				'popup' => true,
			),
			'messenger'     => array(
				'label' => __( 'Messenger', 'unstoppable-share-buttons' ),
				'color' => '#00B2FF',
				'icon'  => 'messenger',
				'group' => 'messaging',
				'url'   => 'https://www.facebook.com/dialog/send?app_id={fb_app_id}&link={url}&redirect_uri={url}',
				'popup' => true,
				'note'  => __( 'Needs a Facebook App ID, which you can set on the Content and meta tab.', 'unstoppable-share-buttons' ),
			),
			'viber'         => array(
				'label' => __( 'Viber', 'unstoppable-share-buttons' ),
				'color' => '#7360F2',
				'icon'  => 'viber',
				'group' => 'messaging',
				'url'   => 'viber://forward?text={title}%20{url}',
			),
			'line'          => array(
				'label' => __( 'LINE', 'unstoppable-share-buttons' ),
				'color' => '#00C300',
				'icon'  => 'line',
				'group' => 'messaging',
				'url'   => 'https://social-plugins.line.me/lineit/share?url={url}&text={title}',
				'popup' => true,
			),
			'threema'       => array(
				'label' => __( 'Threema', 'unstoppable-share-buttons' ),
				'color' => '#000000',
				'icon'  => 'threema',
				'group' => 'messaging',
				'url'   => 'threema://compose?text={title}%20{url}',
			),
			'sms'           => array(
				'label' => __( 'Text message', 'unstoppable-share-buttons' ),
				'color' => '#4F9C4F',
				'icon'  => 'sms',
				'group' => 'messaging',
				'url'   => 'sms:?&body={title}%20{url}',
			),
			'email'         => array(
				'label' => __( 'Email', 'unstoppable-share-buttons' ),
				'color' => '#5B6B7C',
				'icon'  => 'email',
				'group' => 'messaging',
				'url'   => 'mailto:?subject={title}&body={excerpt}%0A%0A{url}',
			),

			// Read later and productivity.
			'instapaper'    => array(
				'label' => __( 'Instapaper', 'unstoppable-share-buttons' ),
				'color' => '#1F1F1F',
				'icon'  => 'instapaper',
				'group' => 'read-later',
				'url'   => 'https://www.instapaper.com/hello2?url={url}&title={title}',
				'popup' => true,
			),
			'evernote'      => array(
				'label' => __( 'Evernote', 'unstoppable-share-buttons' ),
				'color' => '#00A82D',
				'icon'  => 'evernote',
				'group' => 'read-later',
				'url'   => 'https://www.evernote.com/clip.action?url={url}&title={title}',
				'popup' => true,
			),
			'trello'        => array(
				'label' => __( 'Trello', 'unstoppable-share-buttons' ),
				'color' => '#0052CC',
				'icon'  => 'trello',
				'group' => 'read-later',
				'url'   => 'https://trello.com/add-card?url={url}&name={title}&desc={excerpt}',
				'popup' => true,
			),
			'buffer'        => array(
				'label' => __( 'Buffer', 'unstoppable-share-buttons' ),
				'color' => '#231F20',
				'icon'  => 'buffer',
				'group' => 'read-later',
				'url'   => 'https://publish.buffer.com/compose?text={title}&url={url}',
				'popup' => true,
			),
			'bookmark'      => array(
				'label'  => __( 'Bookmark', 'unstoppable-share-buttons' ),
				'color'  => '#6B5B95',
				'icon'   => 'bookmark',
				'group'  => 'read-later',
				'action' => 'bookmark',
				'note'   => __( 'Shows visitors their browser bookmark shortcut.', 'unstoppable-share-buttons' ),
			),

			// Ask an AI.
			'chatgpt'       => array(
				'label' => __( 'ChatGPT', 'unstoppable-share-buttons' ),
				'color' => '#10A37F',
				'icon'  => 'chatgpt',
				'group' => 'ai',
				'url'   => 'https://chatgpt.com/?q={prompt}',
				'popup' => true,
			),
			'claude'        => array(
				'label' => __( 'Claude', 'unstoppable-share-buttons' ),
				'color' => '#D97757',
				'icon'  => 'claude',
				'group' => 'ai',
				'url'   => 'https://claude.ai/new?q={prompt}',
				'popup' => true,
			),
			'gemini'        => array(
				'label' => __( 'Gemini', 'unstoppable-share-buttons' ),
				'color' => '#8E75B2',
				'icon'  => 'gemini',
				'group' => 'ai',
				'url'   => 'https://gemini.google.com/app?q={prompt}',
				'popup' => true,
			),
			'perplexity'    => array(
				'label' => __( 'Perplexity', 'unstoppable-share-buttons' ),
				'color' => '#20808D',
				'icon'  => 'perplexity',
				'group' => 'ai',
				'url'   => 'https://www.perplexity.ai/search?q={prompt}',
				'popup' => true,
			),
			'grok'          => array(
				'label' => __( 'Grok', 'unstoppable-share-buttons' ),
				'color' => '#000000',
				'icon'  => 'grok',
				'group' => 'ai',
				'url'   => 'https://grok.com/?q={prompt}',
				'popup' => true,
			),
			'google'        => array(
				'label' => __( 'Google', 'unstoppable-share-buttons' ),
				'color' => '#4285F4',
				'icon'  => 'google',
				'group' => 'ai',
				'url'   => 'https://www.google.com/search?q={prompt}',
				'popup' => true,
			),

			// On this device.
			'copy'          => array(
				'label'  => __( 'Copy link', 'unstoppable-share-buttons' ),
				'color'  => '#4A5568',
				'icon'   => 'copy',
				'group'  => 'utility',
				'action' => 'copy',
			),
			'native'        => array(
				'label'  => __( 'Share', 'unstoppable-share-buttons' ),
				'color'  => '#2D3748',
				'icon'   => 'native',
				'group'  => 'utility',
				'action' => 'native',
				'note'   => __( 'Opens the device share sheet. Hidden automatically where the browser does not support it.', 'unstoppable-share-buttons' ),
			),
			'qr'            => array(
				'label'  => __( 'QR code', 'unstoppable-share-buttons' ),
				'color'  => '#1A202C',
				'icon'   => 'qr',
				'group'  => 'utility',
				'action' => 'qr',
			),
			'print'         => array(
				'label'  => __( 'Print', 'unstoppable-share-buttons' ),
				'color'  => '#718096',
				'icon'   => 'print',
				'group'  => 'utility',
				'action' => 'print',
			),
		);

		/**
		 * Filters the network catalogue.
		 *
		 * Add a network by appending an array with at least label, color, icon and
		 * url. Placeholders available in url: {url} {title} {excerpt} {image}
		 * {sitename} {prompt}.
		 *
		 * @param array $networks Network definitions keyed by slug.
		 */
		$networks = apply_filters( 'ushb_networks', $networks );

		return $networks;
	}

	/**
	 * Returns one network definition.
	 *
	 * @param string $slug Network slug.
	 * @return array|null Definition, or null when the slug is unknown.
	 */
	public static function get( $slug ) {
		$all  = self::all();
		$slug = sanitize_key( $slug );

		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/**
	 * Human labels for the catalogue's groups.
	 *
	 * @return array Group slug mapped to label.
	 */
	public static function groups() {
		return array(
			'social'     => __( 'Social networks', 'unstoppable-share-buttons' ),
			'messaging'  => __( 'Messaging', 'unstoppable-share-buttons' ),
			'read-later' => __( 'Read later and productivity', 'unstoppable-share-buttons' ),
			'ai'         => __( 'Ask an AI', 'unstoppable-share-buttons' ),
			'utility'    => __( 'On this device', 'unstoppable-share-buttons' ),
		);
	}

	/**
	 * Validates a saved network list: known slugs only, no duplicates.
	 *
	 * This rejects bad values rather than trying to clean them.
	 *
	 * @param mixed $slugs Raw list from settings or a shortcode attribute.
	 * @return array Clean list of network slugs.
	 */
	public static function filter_valid( $slugs ) {
		if ( is_string( $slugs ) ) {
			$slugs = explode( ',', $slugs );
		}

		if ( ! is_array( $slugs ) ) {
			return array();
		}

		$known = self::all();
		$out   = array();

		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( trim( (string) $slug ) );

			if ( isset( $known[ $slug ] ) && ! in_array( $slug, $out, true ) ) {
				$out[] = $slug;
			}
		}

		return $out;
	}
}
