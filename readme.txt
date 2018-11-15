=== Welcart e-Commerce ===
Contributors: Collne Inc., uscnanbu
Tags: Welcart, e-Commerce, shopping, cart, eShop, store, admin, calendar, manage, plugin, shortcode, widgets, membership
Requires at least: 4.8
Tested up to: 4.9
Requires PHP: 5.6 or 7.2
Stable tag: 1.9.14
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Welcart is a free e-commerce plugin for Wordpress with top market share in Japan.


== Description ==

Welcart is a free e-commerce plugin for Wordpress with top market share in Japan.
Welcart comes with many features and customizations for making an online store.
You can easily create your own original online store.


= SHOPPING CART SYSTEM =

You can sell any type of product (physical, digital, subscriptions).
There is no limit to the number of products, item photos, and categories.
You can manage items by SKU (Stock Keeping Unit) code.
Welcart has many options for pricing and shipping.
You can apply for up to 16 different payment options (Sony Payment, Paypal, Softbank Payment etc.) on Welcart's official website.
Please refer to the link below (Japanese version).

[Welcart Payment services(Japanese)](https://www.welcart.com/wc-settlement/)


= DESIGN =
Welcart has free standard templates and themes. 
You can customize the design and layout any way you like.
All themes are compliant to Wordpress standards.
Welcart provides a free responsive design theme (Welcart Basic), from the link below.

[Welcart Theme downloads(Japanese)](https://www.welcart.com/archives/category/item/itemgenre/template/)


= MANAGING SYSTEM =
Order data is automatically stored and updated in the database.
Welcart has a highly functional "order list" page, for viewing and updating orders.
You can narrow your search results by customer information, price, date, item type, etc.
You can also easily manage an order directly through its edit page, where you can edit order details, confirm the purchase, send confirmation emails, and much more.


= MEMBERSHIP SYSTEM =
Welcart has it's very own membership system, eliminating the need for any extra plugins.
Similar to the order list page, a highly functional member list page is also provided.
You can search for member data by customer information, purchase history, etc.
Member orders can be edited individually. 
You can also enable a point system for Welcart members.

[Welcart Community(Japanese)](http://www.welcart.com/wc-com/).


== Installation ==

= AUTOMATIC INSTALLATION =

In your WordPress admin panel, go to Plugins > New Plugin, search for Welcart e-Commerce for WordPress and click "Install now ".


= MANUAL INSTALLATION =

1. Alternatively, download the plugin and upload the entire usc-e-shop folder to the /wp-content/plugins/ directory.
2. Activate the plugin.


= ATTENTION =
 
In the process of activation of plugin, Welcart writes data on tables such as postmeta, options, and terms. When you install a blog existing already, to avoid unexpected damages or any other unexpected situation, backing up is strongly recommended.

Welcart is not responsible or does not have any guarantee for any kind of damage that you get by using or installing Welcart.
All the procedures must be done with self-responsibility of each user.


= RECOMMENDED ENVIRONMENT =

WordPress Ver.4.8 or greater
PHP 5.6, 7.0
MySQL 5.5 or greater
Original domain and SSL (Shared SSL is not recommended)

Note: The server which PHP is working as safe mode is not supported.

Please refer to [Server Requirement (Japanese version)](https://www.welcart.com/wc-condition/)


== Frequently Asked Questions ==

Please see [Welcart Forum(Japanese)](https://www.welcart.com/community/forums).


== Screenshots ==

1. Item List page on the admin screen
2. Editing orders page on the admin screen
3. Top page(Free official theme 'Welcart Basic') 
4. Item page(Free official theme 'Welcart Basic') 


== Changelog ==
= V1.9.14 =
-----------
1 Nov 2018
* [Welcart]Compatible with PHP7.2.
* Changed the specification that it doesn't set MyIsam to the data table as the initial engine.
* Improved the taking time to be short when uploading item batch upload, and fixed some errors.
* Fixed the bug that disappearing half-width "+" in the mails sending from admin pages.
* [PayPal EC]Changed the words which are automatically inserted when you select PayPal EC in payment method.

= V1.9.13 =
-----------
3 Sep 2018
* [e-SCOTT][WelcartPay]Changed the specification that it doesn't allow to register "e-SCOTT" and "WelcartPay" at once to "available credit payment module".

= V1.9.12 =
-----------
31 Aug 2018
* Changed the specification that the processing doesn't stop when there are no authority of writing the log file on an article batch registration.
* Fix the bug that the character "&" is automatically changed "&amp;" in the sender name of mail.
* [e-SCOTT][WelcartPay]Fix the bug that the inside display of dialog window of Token settlement is none.
* Fix the bug of notice message appearing when the first activation of welcart.
* [e-SCOTT][WelcartPay]Changed the specification that it doesn't allow to register "e-SCOTT" and "WelcartPay" at once to "available credit payment module".
* Added Kazakhstan to the sales country.
* Fixed the bug that the discount amount is automatically changed to 0 when the recalculating button is clicked on the order data editing page.
* [REMISE Payment]Added the function that changing a credit card is available on "My Page".
* [PayPal EC]Fixed the bug of JavaScript error on cart page without login.
* [WelcartPay]Changed the specification that the maximum amount for fee(fixed) of online settlement can be setting.
* [WelcartPay]Fixed the bug of error on online settlement processing.
* [e-SCOTT][WelcartPay]Changed the specification that the processing category is mandatory.
* [e-SCOTT][WelcartPay]Added the option of using quick payment at every time.
* [ZEUS Paymen]Fixed the bug that the payment module except ZEUS is unavailabe on basic settings>payment method>payment method.
* [Paygent Payment]Fixed the bug that the order data of welcart is automatically generated when changing the settlement amount at Paygent admin page.

= V1.9.11 =
-----------
29 Jun 2018
* [Mizuho factor]changed the connection destination URL of test environment.
* Fix the bug that there are cases when e-commerce tracking is not recognized(sent) correctly. 
* [e-SCOTT][WelcartPay]Changed a display of the number of payment  to a blank in case of "lump sum payment only".
* Fixed the bug that site title on a statement of delivery PDF is garbled.
* Added the hook to "company" in a statement of delivery PDF.
* [e-SCOTT][WelcartPay]Changed the field of Token settlement authentication code to mandatory.
* Added the function of welcart membership password authorization. Specifing salt is available and etc.
* [ZEUS Payment]Fixed the bug that the status doesn't change to paid when the notice is reached on convenience payment.
* [Another Lane Payment]Changed the settlement URL.
* [WelcartPay]Fixed the bug that the registration of card member deosn't work in case of purchasing a subscription item at first time on external link type.
* [WelcartPay]Added the action hook to "Close" on payment information dialog.
* Fixed the bug that paging doesn't work on an old order list.
* [RobotPayment(CloudPayment)]Fixed the bug of payment error when using points over a total item price.
* [Yahoo wallet]Fixed the bug of payment error.
* Fix the bug of a decimal calculate of Javascript on order editing page.

= V1.9.10 =
-----------
5 Mar 2018
* Changed the timing of session_start.  It runs before any other plug-ins.
* Changed the design of nounce.  The different value sets to nounce on each session when it is important for security on front page.
* Added httpOnly attribute to session cookie. If AOSSL, added secure attribute.
* Strengthen the sanitizations.

= V1.9.9 =
-----------
2 Feb 2018
* [ZEUS Payment]Fixed bug that is not registered in Quick Charge when using Quick Charge with SecureLink.
* Added the option that is not restrict purchase (not check the stock) even when sold out.
* Added the function of chage the display name of stock status.
* Changed the label of "Use SSL" to "Switching SSL".

= V1.9.8 =
-----------
22 Jan 2018
* [ZEUS Payment]Compatible with Token settlement.
* Changed the timing of session_start.  It runs at welcart construct.
* Fixed the bug in case of the payment status is unexpected value when edit the order data.
* [Paydesign]Fixed the statements of content-type on the function of header.
* [e-SCOTT][WelcartPay] Changed the style of entering credit card number daialog on smart phone.
* [e-SCOTT][WelcartPay] Changed the display of payment information on order edit screen. The last 4 digits of credit card number and the expiration date aren't displayed.
* [WelcartPay] Changed the year-selection of the next contract renewal date to be able to choose until 10 years later on the auto-renewable subscriptions member information.

= V1.9.7 =
-----------
25 Dec 2017
* Fixed the bug of the error of "uscesCart" jQuery.
* [Paygent]Added "Supplementary display classification" on the sending parameter.
* Fixed the bug that the lack of end tag (</div>)  on order editing page.
* [e-SCOTT][WelcartPay]Modified the display position of the card information entering dialog.
* Added the destination name on "Ganbare Tencho!"csv output.
* Modified every PDF output. "ZIP mark" and "TEL" don't display when the address and TEL aren't filled in.
* Added the class fo style of customer information field on confirmation page.
* Added the correspondence status on purchase history of member information editing page on admin screen.
* [WelcartPay]Fixed the bug that the layout of order list is off when using postpay settlement.
* [WelcartPay]Fixed the bug of the error caused by old settlement information.
* [PayDesign]Fixed the bug of the error in case of the item name is long when credit cart payment.
* [e-SCOTT][WelcartPay]Fixed the bug that Token settlement dialogue doesn't display depending on the situation.

= V1.9.6 =
-----------
3 Nov 2017
* Compatible with WordPress4.8.3 design change of sanitizing. Fixed the bug of order list csv.
* [WelcartPay]Fixed the bug that members can't cntrol of payment on admin page when purchasing with changing the card number.

= V1.9.5 =
-----------
30 Oct 2017
* [WelcartPay]Compatible with Token settlement.
* [e-SCOTT]Compatible with Token settlement.
* Fixed the bug that "usces_noreceipt_status" hook doesn't work.
* Fixed the bug of unreflecting the change of "administartor memo" when registering new order estimate.
* Added the filter hook at "Total price" of PDF output.
* Added the filter hook for editing the remarks of order data csv.
* Fixed the untransrated error message at registering menber.
* Added the filter hook of changing font size of our company information on delivety note PDF.
* Changed the style of right justified on member list.

= V1.9.4 =
-----------
12 Sep 2017
* Fixed an object injection vulnerability
  We discovered a dangerous Object Injection vulnerability in front page.
  Please upgrade Ver.1.9.4 immediately. All the past versions are the target.
  Technical countermeasure details are here the link.
  https://plugins.trac.wordpress.org/changeset?sfp_email=&sfph_mail=&reponame=&new=1728429%40usc-e-shop&old=1728428%40usc-e-shop&sfp_email=&sfph_mail=
* Changed the print of the first page footer information on delivery note PDF doesn't show in case of multiple pages.
* Added [UnionPay] option in the payment module of Softbank Payment.
* Changed to no- public category is displayed on the selection on item lists in case of the search item category.
* Changed the consumption tax is included when the payment is points only.
* Fixed the bug of delivery date settings.
* Fixed the duplicate id value of "mailadress1" on "wc_customer_page.php".
* Fixed the lack information of order data in case of PayPal webpayment plus.
* Fixed the bug of point form.
* Corresponded the specifications change of "Yahoo! wallet".

= V1.9.3 =
-----------
5 Jul 2017
* Fixed the bug of duplicated payments on specific servers. [WelcartPay][e-SCOTT Smart]
* Fixed the bug of error message on[e-SCOTT Smart].
* Changed the specification of [WelcartPay].
* Fixed the bug of the item name length of [Pay design].
* Fixed the bug of PayPal settings caused by API expired.
* Fixed the bug of the link display on the member page of [ZEUS].
* Fixed the bug of calculating the price in case of using points.
* Changed JAVA scripts alert on confirmation page to decrease abandonment.

= V1.9.2 =
-----------
28 Apr 2017
* Added the feature of post payment "ATODENE" on [WelcartPay].
* Fixed the bug of calculating the price in case of BankCheck payment on [CloudPayment].
* Fixed the bug of installment payment number on e-mail. [WelcartPay].
* Fixed the bug of the item list page on admin page.
* Corresponded the version upgrade of WCEX DLSeller 3.0.
* Fixed the bug of credit card information display on [WelcartPay].
* Changed to make the failure log of auto-renewable subscriptions on [WelcartPay].
* Fixed the bug of filtering by delivery method on order list.
* Fixed the bug of payment error in case of using coupons on [WelcartPay][SONY Payment].
* Fixed the bug of notice message on admin and order data editing page.
* Fixed the bug of the data getting of receiving agency of online transaction on [WelcartPay].
* Fixed the bug of the link display on my page of [WelcartPay].
* Added the feature of updating the data only "the stock amount" or "the stock status" on item batch registration and item data export.
* Fixed the country code of Sweden.

= V1.9.1 =
-----------
26 Dec 2016
* Added the feature of CSV export that items have multiple custom field and same key.
* Changed the specification of [E-SCOTT] and [WelcartPay] to disable to delete account on My Page.
* Fixed the calculate system of point.
* Added SKU code to item code of item number on [PayPal EC].
* Added the function of item batch registration and item data export to resister/export the category by slug.
* Fixed the bug of point adding on order editing page.
* Fixed the bug of registration member information on front page.
* Fixed the bug of the error in comment meta box on item master.
* Fixed the bug of updating member data.
* Fixed the bug of the base country data getting in payment method page.
* Fixed the bug of the advance value disappearing in case of updating sku information when using WCEX_SKU_SELECT + advance field.
* Added the feature of countermeasure for protect the member registration spam.
* Fixed the bug of character automatically changing from [+] to [ ] on some prints and e-mails.
* Fixed the bug of the totals print on invoice PDF.
* Fixed the bug of the error when the agencies unavailable on credit payment selecting page.
* Changed the color of "Credit sales accounting" on [WelcartPay].
* Fixed the bug of sorting on delivery /payment page.
* Fixed the bug of exporting payment error log.
* Fixed the bug of displaying the status of deposit confirmation on order editing page.

= V1.9 =
-----------
5 Oct 2016
* Added the new payment module "WelcartPay"
* Fixed the bug that the sub-image is not recognized


== Upgrade Notice ==

= 1.9.4 =
This version fixes an object injection vulnerability. Upgrade immediately.

