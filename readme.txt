=== MediaElement Flash Fallbacks ===
Contributors:      iandunn
Tags:              mediaelement,flash,fallback
Requires at least: 4.9
Requires PHP:      5.2
Tested up to:      4.9
Stable tag:        0.2
License:           GPLv2

Flash fallbacks for MediaElement.js, to support DASH and HLS for old browsers.


== Description ==

> <strong>First, a word of caution:</strong> You probably don't need this plugin, and shouldn't install it. Before you install it, make sure that you _really_ need to, and that you're aware of the security implications. Are you hosting streaming DASH or HLS videos? Do you _really_ need to support outdated browsers? The safest thing to do is avoid installing it. If you do, make sure you always install any updates that become available, to decrease the security risks to your site.

WordPress bundles [the MediaElement.js library](https://github.com/mediaelement/mediaelement) for improved audio and video playback, and it uses Flash to support a few edge cases, like older browsers that can't play DASH or HLS streams via JavaScript and Media Source Extensions. WordPress 4.9.2 removed the Flash fallbacks because they're not necessary in vast majority of use cases, and have a history of security problems.

This plugin restores those files, for the tiny minority of sites that are hosting streaming DASH/HLS videos, _and_ still need to support outdated browsers. It also optionally validates the unsafe input that's passed to the SWF file in some situations, to decrease the risk associated with it. That validation is off by default, though, and only works in some circumstances.

*You should only install this if you're sure that you really need it*. See the warning above for details.


== FAQ ==

= Where can I report security issues with this plugin? =

Please report them to [WordPress' HackerOne program](https://hackerone.com/wordpress).

= Does this plugin work with WordPress 4.8 or lower? =

No, WordPress 4.9 is required. WP 4.9 upgraded the bundled version of MediaElement.js from 2.x to 4.x, which made significant changes to the Flash fallbacks. This plugin only supports the MediaElement 4.x fallbacks. The Flash fallbacks were also removed from WordPress 4.8 and below. If you'd like to use the flash fallbacks, the best way is to upgrade to the latest version of WordPress. Running old versions puts your site at risk, and is strongly discouraged.

= How can I enable the input validation? Why is it off by default? =

The input validation is off by default, because it only works in some circumstances, and could potentially break valid use cases.

I recommend you turn it on, but test your videos afterwards to make sure they still work. To turn it on, adding the following line of code to a functionality plugin:

`add_filter( 'meff_validate_query', '__return_true' );`

If you're not sure what functionality plugins are, there is a lot of information and tutorials available on the web.

= Where can I contribute to this plugin? =

[The development version](https://github.com/iandunn/mediaelement-flash-fallbacks) is hosted on GitHub.



== Changelog ==

= v0.2 (2018-01-16) =
* [UPDATE] Add final build of MEjs 4.2.8 Flash files.

= v0.1 (2018-01-16) =
* [NEW] Initial release.


== Upgrade Notice ==

= 0.1 =
Initial release.
