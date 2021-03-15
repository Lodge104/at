# 404 Not Found for YOURLS

This plugin will throw the 404 header and serve the contents of user/404.html for shorturls that don't exist.

This is forked and modified from https://github.com/1Conan/404-redirect-YOURLS to better suit the use on drg.li

## Installation
1. Clone this repository into your plugins directory using `git clone https://github.com/drgli/404-redirect-yourls.git 404`
2. Create the 404.html file in the user directory of your YOURLS install and add the contents of your custom 404 error page.

## Fixing other plugins
If you enable other plugins that trigger custom actions (like QR plugins and link information plugins) you may see 404 errors instead of the page when trying to use those plugins. To fix this, copy the `extend_loader_failed.php.example` to `extend_loader_failed.php` 
in the user directory of your YOURLS install and copy the `yourls_add_action( 'loader_failed', '...' )` line from the plugin.php of the plugin in question into that file.

## License
Released under the [MIT](https://github.com/DRGli/404-redirect-YOURLS/blob/master/LICENSE) License.
