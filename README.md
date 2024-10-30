# DPO_WordPress_Standalone

## DPO Group Standalone plugin v1.1.0 for WordPress v6.6.2

This is the DPO Group plugin for WordPress Standalone. Please feel free to contact
the [DPO Group support team](https://dpogroup.com/contact-us/) should you require any assistance.

## Installation

1. **Download the Plugin**
- Please navigate to the [releases page](https://github.com/DPO-Group/DPO_Wordpress_Standalone/releases), download the
[latest release (v1.1.0)](https://github.com/DPO-Group/DPO_Wordpress_Standalone/releases/download/v1.1.0/dpo-standalone-wp-plugin.zip).

2. **Install the Plugin**
- Navigate to the login page of your WordPress site (typically located at http://yourdomain.com/wp-admin). Enter your username and password to log in.
- On your WordPress dashboard, navigate to the **Plugins** section.
- Click on the **Add New** button, then click on the **Upload Plugin** button.
- Choose the ZIP file you downloaded earlier and click **Install Now**.
- After the installation has completed, click the **Activate** button to activate the plugin.

3. **Page Setup**
- Create three pages, one each for checkout, payment success and payment failure. For example, name them:
    - DPO Standalone
    - DPO Standalone Success 
    - DPO Standalone Failure
- Note the URL slug for each, which will be used in configuring return URLs.

- Add the shortcode `[dpo_standalone_payment_checkout]` to the checkout page, which will display the checkout form.
- Add the shortcode `[dpo_standalone_payment_success]` to the success page.
- Add the shortcode `[dpo_standalone_payment_failure]` to the failure page.

4. **How to create a page?**
- In the WordPress dashboard, locate and click on the **Pages** section in the left-hand menu.
- Click the **Add New** button at the top of the Pages section to create a new page.
- In the new page editor, enter a title for your page in the provided field. Add any content you wish to appear on the page in the main editor area.
- Switch to the **Text** editor mode by clicking the **Text** tab above the editor. Enter your shortcode in the desired location within the content.
- Once you have added your content and shortcode, click the **Publish** button to make the page live on your website.
- After publishing, click the **View Page** link to see how your page looks on the front end of your website.

5. **Configure the Plugin**
- Configuration is by means of the **DPO Standalone Plugin** menu item on the Admin Menu Bar in the left-hand menu. The options are:
    - `Company Token` - this is account number given to you by DPO e.g 9F416C11-1234-5678-9112-D5710E4C5E0A.
    - `Service Type` - this is the service type given by DPO e.g 3854.
    - `Success URL` - the URL slug of the success page.
    - `Failure URL` - the URL slug of the failure page.
    - `Test Mode` - check this to use the sandbox (test) account, where no real transactions are processed.
    - `Logo Options` - check to enable which logos will be displayed on the checkout page.

6. **Payments**
- After payment, DPO Pay will redirect to your DPO Standalone Success or Failure Page, providing a payment reference and the amount paid or a reason for the failure.

## Collaboration

Please submit pull requests with any tweaks, features or fixes you would like to share.
