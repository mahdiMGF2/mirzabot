<?php


return [
        'bottext' => [
                'open_button' => '📝 Edit bot texts',
                'home_text' => '📝 <b>Edit bot texts</b>

Pick the text you want to change.
🟢 means it is already customized.
Current language: <b>{lang}</b>',
                'btn_close' => '❌ Close',
                'reset_hint' => '♻️ To restore this text to its default, send <b>0</b>.',
                'msg_reset_done' => '✅ This text has been restored to its default.',
                'msg_session' => '⛔️ Session expired. Please open it again.',
                'msg_empty' => '⛔️ The text is empty. Send it again or tap «Close».',
                'msg_saved' => '✅ Text saved.',
                'msg_emoji_unsupported' => '⚠️ Premium emoji cannot be displayed by this bot.
The text was saved, but a regular emoji is shown instead of the premium one.
To enable it, the bot owner needs a Telegram Premium subscription, or the bot must own a username purchased on Fragment.',
                'msg_closed' => 'Closed.',
                'langs' => [
                        'fa' => '🇮🇷 فارسی',
                        'en' => '🇬🇧 English',
                        'ru' => '🇷🇺 Русский',
                        'zh' => '🇨🇳 中文',
                ],
                'items' => [
                        [
                                'label' => 'Welcome text',
                                'key' => 'users.text_start',
                        ],
                        [
                                'label' => 'Button: Buy subscription',
                                'key' => 'textbot.sell',
                        ],
                        [
                                'label' => 'Button: My services',
                                'key' => 'textbot.purchasedServices',
                        ],
                        [
                                'label' => 'Button: Renew service',
                                'key' => 'textbot.extend',
                        ],
                        [
                                'label' => 'Button: Test account',
                                'key' => 'textbot.userTest',
                        ],
                        [
                                'label' => 'Button: Wallet & top-up',
                                'key' => 'textbot.accountWallet',
                        ],
                        [
                                'label' => 'Button: Add balance',
                                'key' => 'textbot.addBalance',
                        ],
                        [
                                'label' => 'Button: Tariffs',
                                'key' => 'textbot.tariffList',
                        ],
                        [
                                'label' => 'Button: Support',
                                'key' => 'textbot.support',
                        ],
                        [
                                'label' => 'Button: Education',
                                'key' => 'textbot.help',
                        ],
                        [
                                'label' => 'Button: Referrals',
                                'key' => 'textbot.affiliates',
                        ],
                        [
                                'label' => 'Button: Gift code',
                                'key' => 'textbot.discount',
                        ],
                        [
                                'label' => 'Button: Lucky wheel',
                                'key' => 'textbot.wheelLuck',
                        ],
                        [
                                'label' => 'Button: FAQ',
                                'key' => 'textbot.faq',
                        ],
                        [
                                'label' => 'After-purchase message',
                                'key' => 'textbot.afterPay',
                        ],
                        [
                                'label' => 'After test-account delivery message',
                                'key' => 'textbot.afterText',
                        ],
                        [
                                'label' => 'Test account expired message',
                                'key' => 'textbot.testExpired',
                        ],
                        [
                                'label' => 'FAQ text',
                                'key' => 'textbot.faqDesc',
                        ],
                        [
                                'label' => 'Tariff list text',
                                'key' => 'textbot.tariffListDesc',
                        ],
                        [
                                'label' => 'Rules text',
                                'key' => 'textbot.rules',
                        ],
                        [
                                'label' => 'Pre-invoice text',
                                'key' => 'textbot.preInvoice',
                        ],
                ],
        ],
        'language' => [
                'selectPrompt' => '🌏 Please select your desired language.',
                'changeButton' => '🌏 Change language',
                'setSuccess' => '✅ Language set successfully',
                'btnFa' => '🇮🇷 فارسی',
                'btnEn' => '🇬🇧 English',
                'btnRu' => '🇷🇺 Русский',
                'btnZh' => '🇨🇳 中文',
        ],
        'common' => [
                'units' => [
                        'dayShort' => 'day',
                        'byte' => 'Byte',
                        'gb' => 'GB',
                        'gigabyte' => 'gigabytes',
                        'gigabyteAlt' => 'gigabytes',
                        'kilobyte' => 'Kilobyte',
                        'megabyte' => 'megabytes',
                        'terabyte' => 'Terabyte',
                ],
                'duration' => [
                        1 => '⏳ One month',
                        '1day' => '⏳ One day',
                        2 => '⏳ Two months',
                        3 => '⏳ Three months',
                        365 => '⏳ One year',
                        4 => '⏳ Four months',
                        6 => '⏳ Six months',
                        '7day' => '⏳ Seven days',
                        'byVolume' => '🔋 By volume',
                ],
                'connection' => [
                        'onlineAlt' => 'Online',
                        'offlineAlt' => 'Offline',
                        'notConnectedAlt' => 'Not connected',
                        'notConnected' => 'Not connected',
                        'offline' => 'Offline',
                        'online' => 'Online',
                ],
                'roles' => [
                        'normalAlt' => 'Regular',
                        'agentAlt' => 'Agent',
                        'advancedAgentAlt' => 'Advanced agency',
                        'advancedAgent' => 'Advanced agency',
                        'agent' => 'Agent',
                        'normal' => 'Regular',
                ],
                'gateways' => [
                        'perfectMoney' => 'Perfect Money',
                        'rial1' => 'Rial currency payment',
                        'rial2' => 'Second Rial currency payment',
                ],
                'labels' => [
                        'testServiceName' => 'Test service',
                        'toman' => 'Toman',
                        'unlimitedShort' => 'Unlimited',
                        'remainingSuffix' => ' Other',
                        'tomanUnit' => 'Toman',
                        'notSent' => '❌<b> Not sent </b>❌',
                        'confirmedByAdminAlt' => '✅ Approved by admin',
                        'firstPurchaseAlt' => '📌 User\'s first purchase',
                        'autoConfirmedByBot' => 'Approved by the bot without review',
                        'confirmedByAdmin' => '✅ Approved by admin',
                        'customService' => '⚙️ Custom service',
                        'customUsername' => 'Custom username',
                        'customUsernameRandom' => 'Custom username + random number',
                        'firstPurchase' => '📌 User\'s first purchase',
                        'receiptNotSent' => '🔴 Not sent 🔴',
                        'test' => 'test',
                        'testService1' => 'Test service',
                        'testService2' => 'Test service',
                        'testService3' => 'Test service',
                        'testService4' => 'Test service',
                        'testService5' => 'Test service',
                        'testServiceFn' => 'Test service',
                        'unknown' => 'Unknown',
                        'unlimited' => 'Unlimited',
                ],
                'htmlNotAllowed' => '⛔️ HTML tags are not allowed in this name. Please send it without the “&lt;” character.',
                'invalidInput' => '⭕️ Invalid input',
                'invalidTime' => 'The number of days is invalid',
                'invalidUsername' => '❌ The username is invalid.
🔄 Please send your username again',
                'invalidVolume' => 'The volume is invalid',
        ],
        'users' => [
                'Rules' => '✅ The rules have been accepted. You can now use the bot\'s services.',
                'SendMessage' => '📩 Send message to user',
                'back' => 'You have returned to the main page!',
                'backbtn' => '🏠 Back to main menu',
                'backmenu' => '🏠 Back to previous menu',
                'buttonDisabled' => '❌ This button is disabled',
                'buttonDisabledForYou' => '❌ This button is disabled for you',
                'customusername' => 'Custom username',
                'erroroccurred' => '❌ An error occurred. Please start the steps again',
                'featureUnavailable' => '❌ This feature is not available at the moment',
                'featureUnavailable2' => '❌ This feature is not available at the moment.',
                'genericRestart' => '❌ An error occurred. Please perform the steps again',
                'genericRestart2' => '❌ An error occurred. Go through the steps from the beginning',
                'infoFetchErrorRestart' => '❌ An error occurred while retrieving the information. Please perform the steps from the beginning',
                'invalidusername' => '❌ The username is invalid
🔄 Please send your username again',
                'sectionDisabled' => '📛 This section is currently disabled',
                'selectoption' => 'Choose an option',
                'selectusername' => 'Send a custom username
⚠️ The username must not contain extra characters such as @, space, or hyphen. 
⚠️ The username must be in English.
✅ Valid usernames: ali12 | mahdi | ws1_ksdf
❌ Invalid usernames: ali_ | tele@ | _mahdi | محسن',
                'text_start' => 'Hello, welcome',
                'Balance' => [
                        'Failed' => '⭕️ Your payment has not been confirmed',
                        'addBalanceUser' => '⭕️ Manually add balance',
                        'blockedfake' => '⭕️ Block user',
                        'changeto' => '❌ Error 
    The minimum amount for payment via this gateway is 2 TRON',
                        'confirmPayAdmin' => '⭕️ The payment has already been confirmed',
                        'confirmPaying' => '✅ Confirm payment',
                        'errorLinkPayment' => '❌ An error occurred while creating the payment link. Please contact support to resolve it.',
                        'errorprice' => '❌ Error 
💬 Please enter numbers only',
                        'expired' => 'The payment link has expired and can no longer be processed',
                        'finished' => 'Your payment has been successfully confirmed',
                        'insufficientbalance' => '❌ Your balance is not enough to purchase the service.
💸 To top up your balance, enter the amount in Toman:
✅ Minimum amount %s Toman, maximum amount %s Toman',
                        'linkpayments' => 'Creating payment link...',
                        'maxpurchasereached' => '❌ You have reached your maximum purchase limit. Please first top up your account, then purchase a new service or renew an existing one',
                        'nowpayments' => '❌ Error 
    The minimum amount for payment via this gateway is 1 USD.',
                        'payments' => 'Payment',
                        'receiptimage' => '🖼 Submitted receipt image',
                        'refunded' => 'The amount has been returned to your wallet',
                        'rejectPay' => '❌ Reject payment',
                        'selectPayment' => '💵 Choose your payment method',
                        'sendReceipt' => '🚀 Your payment receipt has been sent. After approval by the administration, the amount will be deposited into your wallet',
                        'sendReceiptAndConfig' => '🚀 Your receipt has been sent, and after review the service details will be sent to you',
                        'sending' => 'The payment has been received and is being reviewed, please wait',
                        'waiting' => 'Awaiting payment confirmation',
                        'zarinpal' => '❌ Error 
    The minimum amount for payment via this gateway is 5000 Toman.',
                        'pendingPayment' => '❌ You have an unconfirmed payment. Please wait until the previous payment is reviewed, then send the new payment',
                        'cardEnabledNotice' => '💳 Dear user, the card number has been activated for you; you can now make your purchase.',
                        'cardInstructionAlt' => 'To pay, deposit the amount to the card number below',
                        'giftDepositAlt' => '🎁 Dear user, the amount of %s Toman has been deposited into your account as a gift.',
                        'rejectedNotice' => '❌ Dear user, your payment was rejected for the following reason.
✍️ %s
🛒 Payment tracking code: %s
                
',
                        'giftFromManagement' => '🎁 Dear user, an amount of %s Toman was credited to your wallet as a gift from management.',
                        'deductedNotice' => '❌ Dear user, an amount of %s Toman was deducted from your wallet balance.',
                        'addedNotice' => '💎 Dear user, an amount of %s Toman was added to your wallet balance.',
                        'deductedNotice2' => '❌ Dear user, an amount of %s Toman was deducted from your wallet balance.',
                        'addedNotice2' => '💎 Dear user, an amount of %s Toman was added to your wallet balance.',
                        'addedNotice3' => '💎 Dear user, an amount of %s Toman was added to your wallet balance.',
                        'addedNotice4' => '💰Dear user, an amount of %s Toman was added to your balance.',
                        'addedNotice5' => '💰Dear user, an amount of %s Toman was added to your balance.',
                        'confirmError' => '❌ An error occurred during confirmation. Please perform the payment steps again',
                        'depositRange' => '❌ The minimum deposit amount for this payment method must be {mainbalance} and the maximum {maxbalance} Toman',
                        'depositRangePlisio' => '❌ The minimum deposit amount for this payment method must be {mainbalance} and the maximum {maxbalance} Toman',
                        'cardRetrieveError' => '❌ An internal error occurred while retrieving the bank card. Please try again later.',
                        'noActiveCard' => '❌ No active bank card was found for this payment method. Please try again later or contact support.',
                        'receiptCooldown' => '❗ You sent a receipt in the last 2 minutes. Please send a new receipt in 2 minutes.',
                        'alreadyConfirmed' => '❗️ Your transaction has been approved by the bot.',
                        'transactionExpired' => '❗The time for this transaction has expired and payment for this transaction is not possible.',
                        'askReceiptImage' => '🖼 Send your receipt image',
                        'askReceiptOrTron' => '📌 Send your deposit image or Tron transaction link.',
                        'restartPurchaseOrPay' => '❌ An error occurred. Please perform the purchase or payment steps again',
                        'onlyOneImage' => '❌  You are only allowed to send one image',
                        'receiptSentRenew' => '🚀 Your receipt was sent and your service will be renewed after review',
                        'receiptSentExtraVolume' => '🚀 Your receipt was sent and volume will be added to your service after review.',
                        'receiptSentExtraTime' => '🚀 Your receipt was sent and time will be added to your service after review',
                        'amountRangeError' => '❌ Error 
💬 The amount must be at least %s Toman and at most %s Toman',
                        'added' => '💎 Dear user, an amount of %s Toman was added to your wallet balance.',
                        'chargedThanks' => '💎 Dear user, an amount of %s Toman was credited to your wallet. Thank you for your payment.
                
🛒 Your tracking code: %s',
                        'deducted' => '❌ Dear user, an amount of %s Toman was deducted from your wallet balance.',
                        'lessThanPrice' => 'The balance is less than the product price',
                        'cardInstruction' => 'To pay, deposit the amount to the card number below',
                        'cryptoInstruction' => '
<b>💲 To top up your wallet balance via cryptocurrency, click the payment button at the end of the message</b>

⚠️ Note:  the payment time is 30 minutes; after 30 minutes the transaction will be canceled

🌐 Some domestic sites for buying cryptocurrency 👇
🔸 nikpardakht.com
🔹 webpurse.org
🔸 bitpin.ir
🔹 sarmayex.com
🔸 ok-ex.io
🔹 nobitex.ir
🔸 bitbarg.com
🔹 cafearz.com
🔸 pay98.app
🔢 Invoice number : %s
💰 Invoice amount : %s Toman
📊 Dollar price: %s Toman as of now

Use the button below to pay👇🏻',
                        'cryptoInstruction2' => '
<b>💲 To top up your wallet balance via cryptocurrency, click the payment button at the end of the message</b>

⚠️ Note:  the payment time is 30 minutes; after 30 minutes the transaction will be canceled

🌐 Some domestic sites for buying cryptocurrency 👇
🔸 nikpardakht.com
🔹 webpurse.org
🔸 bitpin.ir
🔹 sarmayex.com
🔸 ok-ex.io
🔹 nobitex.ir
🔸 bitbarg.com
🔹 cafearz.com
🔸 pay98.app
🔢 Invoice number : %s
💰 Invoice amount : %s Toman
📊 Dollar price: %s Toman as of now


<blockquote>⚠️ After payment, if the transaction amount was deposited correctly, your balance will be charged automatically within the next 15 minutes at most.</blockquote>


Use the button below to pay👇🏻',
                        'debtRequired' => '❌ You have a debt; you must pay at least %s Toman.
         Send your amount again',
                        'enterAmount' => '💸 Enter the amount in Toman:

⚠️  The minimum amount is <b>%s</b> and the maximum is <b>%s</b> Toman',
                        'giftDeposit' => '🎁 Dear user, the amount of %s Toman has been deposited into your account as a gift.',
                        'invoiceExpired' => '⭕️ Dear user, the invoice below expired due to non-payment within the specified time .
❗️Please do not pay any amount for this invoice under any circumstances and create a new invoice .

🛒 Your payment method : %s
📌 Invoice code : <code>%s</code>
🪙 Invoice amount :  %s Toman',
                        'giftDepositIranpay' => '🎁 Dear user, the amount of %s Toman has been deposited into your account as a gift.',
                        'invoiceCreated' => '✅ Payment invoice was created.

🔢 Invoice number : %s
💰 Invoice amount : %s Toman

❌ This transaction is valid for one hour; after that, payment for this transaction is not possible.        

📌Please, after payment and a successful transaction, wait a bit until you receive the successful payment message on our site. Otherwise, your account will not be charged.

Use the button below to pay👇🏻',
                        'invoiceCreated2' => '
✅ Payment invoice was created.
            
🔢 Invoice number : %s
💰 Invoice amount : %s Toman

❌ This transaction is valid for one day; after that, payment for this transaction is not possible.        

📌Please, after payment and a successful transaction, wait a bit until you receive the successful payment message on our site. Otherwise, your account will not be charged.

Use the button below to pay👇🏻',
                        'queueBusy' => 'The number of people in the payment gateway queue is extremely high 📊

‼️Please use another payment method for now',
                        'giftDepositPlisio' => '🎁 Dear user, the amount of %s Toman has been deposited into your account as a gift.',
                        'plisioExpired' => '❌ The transaction below expired due to non-payment. Please do not pay any amount for this transaction

🛒 Order code: %s
💰 Amount:  %s Toman',
                        'refundCreateFailed' => '💎  Dear user, because the service was not created, an amount of %s Toman was added to your wallet.',
                        'refundRenewFailed' => '💎  Dear user, because the service was not renewed, an amount of %s Toman was added to your wallet.',
                        'transactionCreated' => '✅ Your transaction was created
        
🛒 Tracking code:  <code>%s</code> 
💲 Transaction amount in Toman  : <code>%s</code>


💢 Please note these points before payment 👇
        
❌ This transaction is valid for 24 hours; after that, payment for this transaction is not possible.        


✅ If you have a problem, you can contact support',
                        'transactionCreated2' => '✅ Your transaction was created
        
🛒 Tracking code:  <code>%s</code> 
💲 Transaction amount in Toman  : <code>%s</code>

💢 Please note these points before payment 👇
        
🔹 The transaction is valid for one day and after that it will not be approved if paid .
❌ After the transaction it takes 15 minutes to one hour for the transaction to be approved

✅ If you have a problem, you can contact support',
                        'transactionCreated3' => '✅ Your transaction was created
        
🛒 Tracking code:  <code>%s</code> 
💲 Transaction amount in Toman  : <code>%s</code> Toman


💢 Please note these points before payment 👇
        
❌ This transaction is valid for one day; after that, payment for this transaction is not possible.        

✅ If you have a problem, you can contact support',
                        'transactionCreatedStar' => '✅ Your transaction was created

🛒 Tracking code: <code>%s</code>
💲 Transaction amount: %s ⭐ (equivalent to %s Toman)

📌 Please convert the amount of %s Toman to Telegram Stars and deposit it.

💢 Important points before payment: 👇
🔹 Each transaction is valid for 1 day; after expiry, refrain from depositing.

✅ If you have a problem, contact support.',
                        'transactionCreatedTron' => '✅ Your invoice has been created

🛒 Tracking code: <code>%s</code>
🌐 Network: TRX - Tron
💳 Wallet address: <code>%s</code>

📌 Please deposit <code>%s</code> TRX to the wallet address above, then click the button below and send the receipt.

💢 Please note these points before payment 👇
🔸 If you enter the wallet address incorrectly, the transaction will not be confirmed and no refund is possible.
🔹 The sent amount must not be less or more than the declared amount.
🔹 If you deposit more than the specified amount, it is not possible to add the difference.
🔹 Each transaction is valid for one hour; do not send any amount after the expiration message.

✅ If you have any issues, you can contact support.',
                ],
                'Discount' => [
                        'discountapplied' => 'Congratulations 🎉
📌 Your purchase includes a %s percent discount',
                        'errorLimit' => '❌ The usage limit for this discount code has been reached',
                        'errorLimitDiscount' => '❌ The usage limit for this gift code has been reached',
                        'firstdiscount' => '❌ This discount code is only for the first purchase.',
                        'getcode' => '💝 To receive your balance, send your gift code',
                        'getcodesell' => '🧑‍💻 Send your discount code',
                        'gift-deposit' => '🎁 Dear user, the amount of %s Toman has been deposited into your account as a gift.',
                        'giftcodeonce' => '📌 This code can only be used once.',
                        'giftcodesuccess' => 'The gift code was successfully registered and the amount of %s Toman was added to your balance. 🥳',
                        'giftcodeused' => '⭕️ A user with username @%s and numeric ID %s used the gift code %s.',
                        'notcode' => '❌ The code is invalid',
                        'invalidCode' => '❌ The discount code is invalid',
                        'expired' => '❌ The discount code time has expired.',
                        'useLimit' => '⭕️ This code can only be used {useuser}  times',
                        'applied' => '🤩 Your discount code was valid and {discount_price} percent discount was applied to your invoice.',
                        'notAllowed' => '❌ Purchase with this discount code is not possible',
                ],
                'Major' => [
                        'title' => '📌 Send the number of services you want to purchase 
⚠️ The minimum is 1 and the maximum is 15',
                        'disabled' => '❌ This section is currently disabled',
                        'minBalance' => '❌ For bulk purchase you must have at least {PaySetting} Toman balance.',
                ],
                'account' => [
                        'verifiedByAdmin' => '💎 Dear user, your account has been successfully verified by the admin and you can now make your purchase',
                        'info' => '
🗂 Your account information :


🪪 User ID: <code>%s</code>
👤 Name: <code>%s</code>
👨‍👩‍👦 Your referral code : <code>%s</code>
📱 Contact number :%s
⌚️Registration time : %s
💰 Balance: %s Toman
🛒 Number of purchased services : %s
📑 Number of paid invoices :  : %s
🤝 Number of your referrals : %s people
🔖 User group : %s
%s
%s

📆 %s → ⏰ %s
                    
',
                        'notVerifiedNotice' => '⚠️ Your account is not verified. Your message has been sent to the admin.
    For faster follow-up, you can message the ID below
    @%s',
                        'verifiedNotice' => '💎 Dear user, your account was verified successfully and you can now make your purchase',
                        'verified' => 'Your account was verified successfully',
                ],
                'affiliates' => [
                        'affiliateedago' => '❌ You have previously been another user\'s referral, so you cannot become a referral again',
                        'affiliatesidyou' => '❌ It is not possible to become a referral with this user ID.',
                        'invalidaffiliates' => '❌ You cannot be your own referral',
                        'offaffiliates' => '❌ The referral section is turned off',
                        'balanceGift' => '🎁 An amount of {addbalancediscount} was added to your balance from your referral with user ID {from_id}.',
                        'pointsEarned2Alt' => '📌You earned 2 new points.',
                        'pointsEarned1Alt' => '📌You earned 1 new point.',
                        'accountScore' => '🥅 Your account points : {score}',
                        'notReferral' => '📛 You are not a referral of any user.',
                        'joinedGift' => '🎉 Someone joined through your referral! The gift was credited to your account.',
                        'joinGiftActivated' => '🎉 The membership gift was activated for you!',
                        'commissionPaid' => '🎁  Commission payment 
        
        An amount of %s Toman was credited to your wallet from your referral',
                        'commissionPaid2' => '🎁  Commission payment 
        
        An amount of %s Toman was credited to your wallet from your referral',
                        'commissionPaidFn' => '🎁  Commission payment 
        
        An amount of %s Toman was credited to your wallet from your referral',
                        'commissionPaidFn2' => '🎁  Commission payment 
        
        An amount of %s Toman was credited to your wallet from your referral',
                        'commissionPaidMiniapp' => '🎁  Commission payment 
            
            An amount of %s Toman was credited to your wallet from your referral',
                        'commissionPaidMiniapp2' => '🎁  Commission payment 
        
        An amount of %s Toman was credited to your wallet from your referral',
                        'newReferralJoined' => '<b>🎉 A new referral!</b>
User <b>@%s</b> joined the bot with your invite link ✅

With this user\'s purchases, <b>your gift share</b> will be credited to your account 🔥',
                        'welcomeGiftInfo' => '<b>💼 Referrals and welcome gift</b>

By inviting friends through your <b>dedicated link</b>, your wallet is topped up without paying even 1 Rial, and you use the bot\'s services!

%s
%s

<b>📊 Your stats:</b>
• 👥 Referrals: %s people
• 🛒 Purchases: %s
• 💵 Total purchases: %s Toman

<b>📢 Invite, get a gift, grow!</b>
',
                        'welcomeInvited' => '<b>🎉 Welcome!</b>

You joined the bot through <b>@%s</b>\'s invitation and were registered as a referral ✅

To receive the membership gift:
🔘 Go to the <b>Referrals</b> menu  
🔘 Press the <b>🎁 Receive membership gift</b> button

This way, both you and your referrer get a gift! 💰
',
                        'membershipGiftClaimed' => '<b>⛔ You have already received the membership gift.</b>
This gift can only be activated <b>once</b>.',
                        'membershipGiftInfo' => '<b>🎁 Membership gift:</b>
• 🎉 Total gift: %s Toman  
• 🔻 50% for you (referrer)  
• 🔻 50% for the referral (new user)

',
                        'pointsEarned1' => '📌You earned 1 new point.',
                        'pointsEarned2' => '📌You earned 2 new points.',
                        'pointsEarned2b' => '📌You earned 2 new points.',
                        'purchaseCommissionInfo' => '<b>💸 Purchase commission:</b>  
•  %s percent of your referral\'s purchase amount belongs to you',
                        'referralLink' => '

🔗 Referral link for verifying a referral :
https://t.me/%s?start=%s',
                ],
                'agent' => [
                        'acceptrequest' => '✅ Approve request',
                        'agentRequest' => '📣 A user has submitted an agent request. Please review the information and set the status.

Numeric ID: <code>%s</code>
Username: @%s
Account name: %s
Description: %s',
                        'customnameusername' => '👤 Choose a custom name',
                        'endrequest' => '✅ Your request has been submitted. The result will be announced after review.',
                        'insufficientbalanceagent' => '❌ Your balance is not enough for an agent request. Please first top up your account, then send the request

💸 Cost of obtaining an agency: %s Toman',
                        'isagent' => '❌ You are currently an agent, so you cannot submit an agent request.',
                        'rejectrequest' => '❌ Reject request',
                        'requestreport' => '❌ You have a request already submitted, so a new request is not possible.',
                        'welcome' => '👋 Welcome to the agent panel',
                        'usernameSaved' => '✅ Your username was successfully saved.',
                        'requestRejected' => '❌ Dear user, your agency request was rejected.',
                        'requestApproved' => '✅ Dear user, your agency request was approved and you have become an agent.',
                        'expiredNotice' => '📌 Dear agent, your agency period has ended and your account was removed from agency status. To reactivate your agency, you can contact support.',
                ],
                'app' => [
                        'appempty' => '❌ There is no app available for download.',
                        'selectapp' => '📌 Choose an option to download',
                ],
                'block' => [
                        'descriptions' => '🚫 You have been blocked by the administration.

✍️ Reason for block: %s',
                        'unblockedNotice' => '✳️ Your account has been unblocked ✳️
You can now use the bot ✔️',
                        'unblocked' => '✳️ Your account has been unblocked ✳️
You can now use the bot ✔️',
                ],
                'changeLink' => [
                        'btnTitle' => '⚙️ Change link',
                        'confirm' => 'Change connection link',
                        'warnchange' => '⚠️ If you update the subscription link, your previous configs and service will be disconnected. To confirm, click the button below',
                        'serviceInactive' => '❌ The service is disabled and changing the link for the service is not possible.',
                        'error' => '❌ An error occurred while changing the link.',
                        'updated' => '✅ Your config was updated successfully.',
                ],
                'changeLocation' => [
                        'confirm' => '✅ Confirm transfer',
                        'title' => '🌐 Change location',
                        'limitReached' => '❌ Your location change limit has been reached',
                        'notPossible' => '❌ Transfer to the panel is not possible.',
                        'configUnused' => '❌ Your config is in unused status and transferring the service location is not possible.',
                        'confirmPrompt' => '📍 By confirming the service location transfer, your service will be deleted from this location and transferred to the new location.
💰 The transfer cost is %s Toman
📌 Your remaining limit : %s (remaining free limit :‌%s)

✅ To confirm the transfer, click the button below',
                        'success' => '✅ Your config was transferred to the server (%s) successfully.

🖥 Service name : %s
💠 Service volume : %s
⏳ Expiry time :  %s | %s 


🔗 Your subscription link: 

<code>%s</code>',
                ],
                'channel' => [
                        'confirmed' => 'Your membership has been successfully confirmed. Thank you ❤️',
                        'confirmjoin' => '✅ Check membership',
                        'left_channel' => '❌ You have left our channel and will not be informed of news and updates. It is better to rejoin the channel',
                        'notconfirmed' => '❌ You have not joined the channel yet.️',
                ],
                'customSellVolume' => [
                        'title' => '⚙️ Custom service',
                        'invalidTime' => 'The number of days is invalid',
                        'btnVolume' => '🛍 Custom volume',
                        'btnService' => '⚙️ Custom service',
                        'invalidVolume' => '❌ The volume is invalid.
🔔 The minimum volume is {mainvolume} gigabytes and the maximum is {maxvolume} gigabytes',
                        'invalidTimeRange' => '❌ The submitted time is invalid. The time must be between {maintime} days and {maxtime} days',
                ],
                'extend' => [
                        'confirm' => 'Confirm renewal',
                        'discount' => '🎁 Apply discount code',
                        'emptyServiceforExtend' => '❌ You have no service to renew.',
                        'renewalerror' => '❌ An error occurred during renewal. Please perform your renewal steps again',
                        'renewalinvoice' => '📜 Your renewal invoice for username %s has been created.

🛍 Product name: %s
💸 Renewal amount: %s
⏱ Renewal duration: %s days
🔋 Renewal volume: %s GB
✍️ Description: %s
💸 Wallet balance: %s

✅ To confirm and renew the service, click the button below',
                        'selectOrderDirect' => '📌 Select your service to renew.',
                        'selectservice' => ' 🛍 Select your product to renew',
                        'thanks' => '🙏 Thank you for renewing your service.

✅ Your renewal was completed successfully.
⬅️ To return to your service list or view the details, click the buttons below.',
                        'title' => '💊 Renew service',
                        'invoiceCreatedByAdmin' => '📜 Your renewal invoice for username %s was created.
        
🛍 Product name :%s
⏱ Renewal duration :%s days
🔋 Renewal volume :%s GB
✍️ Description : %s
✅ To confirm and renew the service, click the button below',
                        'error' => '❌ The renewal encountered an error; perform the renewal steps again.',
                        'notSupportedPanel' => '❌ Renewal is not possible on this panel',
                        'connectFirst' => '❌ You have not connected to the service yet. To renew the service, first connect to the service, then proceed to renew',
                        'planNotAvailable' => '❌ Renewal with the current plan is not possible. Go through the steps from the beginning and select another plan.',
                        'restartError' => '❌ An error occurred. Perform the renewal steps from the beginning.',
                        'errorSupport' => '❌ An error occurred while renewing the service; contact support',
                        'genericError' => '❌ An error occurred during renewal. Contact support',
                        'giftCharged' => 'Congratulations 🎉
📌 As a renewal gift, an amount of %s Toman was credited to your account',
                        'giftChargedFn' => 'Congratulations 🎉
📌 As a renewal gift, an amount of %s Toman was credited to your account',
                        'invoiceCreated' => '📜 Your renewal invoice for username %s was created.
        
🛍 Product name :%s
💸 Renewal amount : %s Toman
⏱ Renewal duration :%s days
🔋 Renewal volume :%s GB
✍️ Description : %s
💸 Wallet balance : %s
✅ To confirm and renew the service, click the button below',
                        'invoiceCreated2' => '📜 Your renewal invoice for username %s was created.
        
🛍 Product name :%s
💸 Renewal amount :%s
⏱ Renewal duration :%s days
🔋 Renewal volume :%s GB
✍️ Description : %s
💸 Wallet balance : %s

✅ To confirm and renew the service, click the button below',
                        'genericErrorApi' => '❌ An error occurred while renewing the service; contact support',
                        'success' => '✅ Your service was renewed successfully
 
▫️Service name : %s
▫️Product name : %s
▫️Renewal amount %s Toman

',
                        'success2' => '✅ Your service was renewed successfully
 
▫️Service name : %s
▫️Product name : %s
▫️Renewal amount %s Toman

',
                        'successFn' => '✅ Your service was renewed successfully
 
▫️Service name : %s
▫️Product name : %s
▫️Renewal amount %s Toman

',
                ],
                'extraTime' => [
                        'extratimecheck' => 'Confirm and receive extra time',
                        'title' => '⏳ Purchase extra time',
                        'notSupportedPanel' => '❌ Purchasing extra time is not possible on this panel',
                        'invoiceCreated' => '📜 An extra time purchase invoice was created for you.
        
📌 Daily rate for extra time : %s Toman
📆 Requested number of extra days : %s days
💰 Your invoice amount : %s Toman
        
✅ To pay and add the time, click the button below',
                        'prompt' => '📆 Enter the desired number of extra days ( in days ) :
        
📌 Daily rate:  %s',
                        'success' => '✅ Time was added to your service successfully
 
▫️Service name : %s
▫️Added time : %s days

▫️Time addition amount : %s Toman',
                        'successFn' => '✅ Time was added to your service successfully
 
▫️Service name : %s
▫️Added time : %s days

▫️Time addition amount : %s Toman',
                ],
                'extraVolume' => [
                        'enterextravolume' => '🔋 Enter the desired amount of extra volume (in gigabytes):

📌 Price per GB: %s Toman',
                        'extracheck' => 'Confirm and receive extra volume',
                        'extravolumeinvoice' => '📇 An invoice for purchasing extra volume has been created for you.

💰 Price per gigabyte of extra volume: %s Toman
📝 Your invoice amount: %s Toman
📥 Requested extra volume: %s gigabytes

✅ To pay and add the volume, click the button below.',
                        'invalidprice' => '🚫 The minimum volume is 1 gigabyte',
                        'sellextra' => '➕ Purchase extra volume',
                        'notSupportedPanel' => '❌ Purchasing extra volume is not possible on this panel',
                        'serviceError' => '❌An error occurred while purchasing extra volume for the service. Contact support',
                        'invoiceCreated' => '📜 An extra volume purchase invoice was created for you.
        
📌 Rate per gigabyte of extra volume : %s Toman
🔋 Requested extra volume : %s gigabytes
💰 Your invoice amount : %s Toman
        
✅ To pay and add the volume, click the button below',
                        'prompt' => ' ⭕️ Send the amount of volume you want to purchase.
❌ Send the amount in English.
        ⚠️ Each gigabyte of extra volume is %s Toman.',
                        'success' => '✅ Volume was added to your service successfully
 
▫️Service name  : %s
▫️Added volume : %s GB

▫️Volume addition amount : %s Toman',
                        'successFn' => '✅ Volume was added to your service successfully
 
▫️Service name  : %s
▫️Added volume : %s GB

▫️Volume addition amount : %s Toman',
                ],
                'help' => [
                        'btninlinebuy' => '📚 View usage tutorial ',
                        'disablehelp' => 'Dear user, the tutorial section is currently disabled. 😔',
                ],
                'lottery' => [
                        'winnerNotice' => '🎁 Lottery result 

😎 Dear user, congratulations! You are person %s and won %s Toman balance, and your account was charged.',
                ],
                'note' => [
                        'changednote' => '✅ The note was changed successfully.',
                        'errorLongNote' => '❌ The maximum length for a new note is 150 characters.',
                        'sendNote' => '📝 Send your new note (you can send up to 150 characters).',
                ],
                'notify' => [
                        'deleteInfo' => '📌 Deletion cron notice

Service username :‌ <code>%s</code>
Service status : %s
Number of remaining days ‌:‌%s
Remaining volume : %s',
                        'greeting' => 'Hello dear user 👋

',
                        'greeting2' => 'Hello dear user 👋

',
                        'remainingDays' => 'Number of remaining days ‌:‌%s',
                        'remainingVolume' => 'Remaining volume : %s',
                        'serviceDeleted' => '📌 Dear user, due to non-renewal, the service %s was deleted from your services list

🌟 To get a new service, proceed from the Buy service section',
                        'serviceDeleted2' => '📌 Dear user, due to non-renewal, the service %s was deleted from your services list

🌟 To get a new service, proceed from the Buy service section',
                        'serviceStatus' => 'Service status : %s

',
                        'serviceStatus2' => 'Service status : %s

',
                        'serviceUsername' => 'Service username :‌ <code>%s</code>

',
                        'serviceUsername2' => 'Service username :‌ <code>%s</code>

',
                        'thanks' => 'Thank you for being with us',
                        'timeActionHint' => 'If you wish to renew this service, please proceed through the «%s» section. ',
                        'timeTitle' => '📌 Time cron notice


',
                        'timeRemaining' => '📌 Only %s days remain for using the service %s. ',
                        'volumeActionHint' => 'Please, if you wish, proceed to purchase extra volume or renew your service through the «%s» section',
                        'volumeTitle' => '📌 Volume cron notice


',
                        'volumeDeleteInfo' => '📌  Volume deletion cron notice 
Service username : %s 
 Service status : %s 
Number of remaining days :%s 
 Remaining volume : %s
User\'s last connection : %s',
                        'volumeRemaining' => '🚨 Only %s remains of the service %s volume. ',
                        'onHoldReminder' => 'Hello! 🌐

We noticed that you have not yet connected to your config with username %s, and more than %s days have passed since its activation. If you have any problem setting up or using the service, please contact our support team via the ID below so we can help you.
We are ready to resolve any question or problem! 📞

Support account : @%s',
                ],
                'number' => [
                        'active' => '✅ Your mobile number has been successfully verified',
                        'confirming' => '📞 Please send your mobile number for verification using the button below',
                        'erroriran' => '⭕️ The mobile number is invalid. Only Iranian numbers are accepted',
                        'false' => '❌ The phone number is not correct. Please send your phone number using the button below.',
                        'warning' => '⚠️ Error saving the phone number. The number must belong to this same account',
                ],
                'page' => [
                        'next' => 'Next',
                        'notusernameme' => '🔎 My username is not in the list',
                        'previous' => 'Previous',
                ],
                'priceArze' => [
                        'tetherPrice' => 'The current Tether price is: %s Toman',
                        'tronPrice' => 'The current TRON price is: %s Toman',
                        'fetchError' => '❌ Retrieving the price is not possible at the moment. Please try again later.',
                ],
                'search' => [
                        'title' => '🔎 Quick search',
                        'usernamgeget' => '📌 Send your username',
                ],
                'sell' => [
                        'errorConfig' => '❌ An error occurred while creating the subscription. Please contact support to resolve the issue.',
                        'errorProduct' => '❌ The selected product does not exist',
                        'noCredit' => '📝 Your account balance is not enough. Please choose a payment method from the list below',
                        'notestep' => '📌 Write a note for your config.
⚠️ This name is for faster search in service management
🪪(example: Ali, Ahmad, Uncle, customer from out of town, etc.)',
                        'serviceSelect' => '🛍️ Please select the service you want to purchase!',
                        'serviceSelectFirst' => '🛍️ Please select the service you want to purchase!',
                        'service_not_available' => '⛔️ You have no active service',
                        'service_sell' => '🛍 Subscriptions purchased by you

⚠️ To view details and manage, click on the username

⭕️ You can also use the "🔎 Quick search" button to quickly find and manage your service',
                        'nullProduct' => '⭕️ No product was found. Please contact support to resolve the issue',
                        'panelCapacityFull' => '❌ Unfortunately, the account creation capacity on this panel has been reached. Use another panel',
                        'capacityFull' => '❌ Unfortunately, the account creation capacity has been reached. Try again in a few hours.',
                        'nullPanel' => '⭕️ No position was found. Please contact support to resolve the issue',
                        'selectDuration' => '📌 Select the service duration',
                        'purchaseError' => '❌ The purchase failed. Perform the steps again.',
                        'stockFinished' => '❌ This service\'s volume has run out.',
                        'stockFinishedBuyAnother' => '❌ This service\'s volume has run out. Please purchase another service.',
                        'selectCategoryShort' => '📌 Select a category',
                        'selectCategory' => '📌 Select your category!',
                        'panelUnavailable' => '❌ This panel is not available. Please make your purchase from another panel.',
                        'restartProcess' => '❌ Please perform the purchase steps again',
                        'creating' => '♻️ Creating your service...',
                        'restartFromStart' => '❌ Perform the purchase steps from the beginning again',
                        'noPurchaseUsersOnly' => '❌ Unfortunately, this option is only active for users who have not made any purchase from the bot.',
                        'customTimePrompt' => '⌛️ Select your service time 
📌 Daily rate  : %s  Toman
⚠️ You can purchase a minimum of %s days and a maximum of %s days',
                        'customTimePrompt2' => '⌛️ Select your service time 
📌 Daily rate  : %s  Toman
⚠️ You can purchase a minimum of %s days and a maximum of %s days',
                        'customVolumePrompt' => '📌 Send your requested volume.
🔔The price per gigabyte of volume is %s Toman.
🔔 The minimum volume is %s gigabytes and the maximum is %s gigabytes.',
                        'customVolumePrompt2' => '📌 Send your requested volume.
🔔The price per gigabyte of volume is %s Toman.
🔔 The minimum volume is %s gigabytes and the maximum is %s gigabytes.',
                        'customVolumePrompt3' => '📌 Send your requested volume.
🔔The price per gigabyte of volume is %s Toman.
🔔 The minimum volume is %s gigabytes and the maximum is %s gigabytes.',
                        'customVolumePrompt4' => '📌 Send your requested volume.
🔔The price per gigabyte of volume is %s Toman.
🔔 The minimum volume is %s gigabytes and the maximum is %s gigabytes.',
                        'customVolumePrompt5' => '📌 Send your requested volume.
🔔The price per gigabyte of volume is %s Toman.
🔔 The minimum volume is %s gigabytes and the maximum is %s gigabytes.',
                        'invalidTimeRestart' => 'The time is invalid. Perform the purchase from the beginning',
                        'invalidVolumeRestart' => 'The volume is invalid. Perform the purchase from the beginning',
                        'preInvoice' => '
📇 Your pro forma invoice:
👤 Username: <code>%s</code>
🔐 Service name: %s
📆 Validity period: %s days
💶 Original price : <del>%s Toman</del>
💶 Discounted price: %s  Toman
👥 Account volume: %s GB
💵 Your wallet balance : %s
                  
        💰 Your order is ready for payment.  ',
                        'preInvoice2' => '
📇 Your pro forma invoice:
👤 Username: <code>%s</code>
🔐 Service name: %s
📆 Validity period: %s days
💶 Price: %s  Toman
👥 Account volume: %s GB
💵 Your wallet balance : %s
⭕️Number of configs : %s
                  
💰 Your order is ready for payment.  ',
                        'panelInactive' => 'The selected panel is currently not active',
                        'panelMissing' => 'The selected panel does not exist.',
                        'productNotFound' => 'The selected product was not found',
                        'created' => '✅ Service was created successfully

👤 Service username : {username}
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}
⏳ Duration: {day}  hours
🗜 Service volume:  {volume} megabytes

🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                        'created2' => '✅ Service was created successfully

👤 Service username : {username}
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}
⏳ Duration: {day}  days
🗜 Service volume:  {volume} gigabytes

🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                        'timePrompt' => '⌛️ Select your service time 
📌 Daily rate  : %s  Toman
⚠️ You can purchase a minimum of %s days and a maximum of %s days',
                        'volumePrompt' => '🔋 Please enter the desired service volume ( in gigabytes ) :
📌 Rate per gigabyte :  %s 
🔔 The minimum volume is 1 gigabyte and the maximum is 1000 gigabytes.',
                        'subscriptionError' => 'An error occurred while creating the subscription. Contact support',
                        'usernameExists' => 'The username exists. Perform the steps from the beginning',
                ],
                'spam' => [
                        'spamed' => 'Sending too many messages in the bot',
                        'spamedMessage' => '📌 Dear user, your account has been blocked due to spam in the bot.',
                        'spamedReport' => 'The user with numeric ID %s was blocked due to spam in the bot',
                ],
                'status' => [
                        'active' => '✅ Active',
                        'activedconfig' => '✅ Your service has been successfully activated',
                        'backinfo' => '↪️ Back',
                        'backlist' => '🏠 Back to service list',
                        'backservice' => '🏠 Back to service details',
                        'config' => '🔰 Get config',
                        'day' => ' days ',
                        'daysleft' => 'Remaining service time:',
                        'descriptionsRemoveService' => '📌 By clicking the "✅ I request service deletion" button, your service deletion request will be sent to the administration, and after review your service will be canceled.

❌ If approved by the administration, the remaining unused amount will be deposited into your wallet.

Thank you for using our services.',
                        'disabled' => '❌ Inactive',
                        'disabledconfig' => '❌ Your service has been successfully deactivated.',
                        'error' => '❌ An error occurred',
                        'errorexits' => '❌ A request has already been submitted for this username, so a new request is not possible',
                        'errorusertest' => '❌ A refund is not possible for a test account.',
                        'exitsRequests' => '❌ You have a request already submitted. Please wait for the submitted request to be reviewed; after review you can submit your deletion request',
                        'expirationDate' => 'End time:',
                        'expired' => '🔚 End of service time',
                        'hour' => ' hours ',
                        'info' => '📊 Service information:',
                        'lastTraffic' => 'Total service volume:',
                        'limited' => '🚫 Volume exhausted',
                        'linksub' => '🔗 Subscription link',
                        'min' => 'minute',
                        'month' => ' month ',
                        'notConsumed' => 'Unused',
                        'notUsernameGet' => 'The username does not exist',
                        'notchanged' => '❌ It is not possible to change the service status.',
                        'on_hold' => '❌ Not connected',
                        'panelNotConnected' => '❌ The query system for the requested service is currently unavailable. Try again in an hour',
                        'remainingVolume' => 'Remaining service volume:',
                        'removeservice' => '❌ Refund',
                        'sendUsername' => '📌 Send your username',
                        'sendrequestsremove' => '✅ Your request has been sent. After review by the administration, the result will be reported to you',
                        'stateus' => 'Status:',
                        'unknown' => '❌ Unknown',
                        'unlimited' => 'Unlimited',
                        'usedTrafficGb' => 'Service volume used:',
                        'userNotFound' => '❌ The requested service was not found on the server!',
                        'username' => 'Username: ',
                        'notConnectedCannotChange' => '❌ It has not connected to the config yet, and the service status cannot be changed. After connecting to the config, you can use this feature.',
                        'btnConfirmDisable' => '✅ Confirm and disable config',
                        'confirmDisableDesc' => '📌 By confirming the option below, your config will be turned off and you will no longer be able to connect to it.
⚠️ If you want the config to be activated again, you must click the <u>💡 Turn on account</u> button from the service management section',
                        'btnConfirmEnable' => '✅ Confirm and enable config',
                        'confirmEnableDesc' => '📌 By confirming the option below, your config will be turned on and you will be able to connect to it.
⚠️ If you want the config to be deactivated again, you must click the <u>❌ Turn off account</u> button from the service management section',
                        'deleteRequestRejected' => '❌ Dear user, your deletion request with username %s was not approved.
        
        Reason for non-approval: %s',
                        'deleteRequestApproved' => '✅ Dear user, your deletion request with username %s was approved.',
                        'deleteRequestApproved2' => '✅ Dear user, your deletion request with username %s was approved.',
                        'servicesFound' => '🛍 {countservice} services found. To view and manage a service, click on one of the services',
                        'infoUnavailable' => '❌ Viewing account information is not possible at the moment',
                        'servicePassword' => '🔑 Your service password : <code>{subscription_url}</code>',
                        'configNote' => '✍️ Config note : {note}',
                        'lastOnline' => '📶 Your last connection time : {lastonline}',
                        'subscriptionFile' => 'Your subscription file',
                        'btnTurnOff' => '❌ Disable account',
                        'btnTurnOn' => '💡 Turn on account',
                        'btnEditNote' => '📝 Change note',
                        'btnRefresh' => '♻️ Update information',
                        'deletedSuccess' => '📌 The service was deleted successfully',
                        'askDeleteReason' => '📌 Send the reason for deleting your service.',
                        'configReadError' => '❌  Error reading config information. Contact support.',
                        'selectConfig' => '📌 Select and use a config from the list below.',
                        'notConnectedCannotChangeStatus' => '❌ You have not connected to the config yet, and changing the service status is not possible. After connecting to the config, you can use this feature.',
                        'btnConfirmDisableAlt' => '✅ Confirm and disable config',
                        'btnConfirmEnableAlt' => '✅ Confirm and enable config',
                        'subscriptionLine' => 'Your subscription : <code>{output_config_link}</code>',
                        'confirmDisableConfig' => '📌 By confirming the option below, your config will be turned off and you will no longer be able to connect to it.
⚠️ If you want the config to be activated again, you must click the <u>💡 Turn on account</u> button from the service management section',
                        'confirmEnableConfig' => '📌 By confirming the option below, your config will be turned on and you will be able to connect to it.
⚠️ If you want the config to be deactivated again, you must click the <u>❌ Turn off account</u> button from the service management section',
                        'getConfigHint' => '📌 To get the config, click the Get config button',
                        'connectionInfo' => '
📶 Last connection time  : %s
🔄 Last subscription link update time  : %s
#️⃣ Connected client :<code>%s</code>',
                        'infoBasic' => 'Service status : <b>%s</b>
Service username : %s
📎 Service tracking code : %s

📌 Service information : 
%s',
                        'infoDetailed' => 'Service status : <b>%s</b>
👤 Service username : <code>%s</code>
🌍 Service location :%s
Product name :%s

📶 Your last connection time : %s

🔋 Traffic : %s
📥 Consumed volume : %s
💢 Remaining volume : %s (%s%%)

📅 Expiry date :  %s (%s)

%s',
                        'infoFull' => '📊Service status : %s
👤 Service name : <code>%s</code>
%s
%s
🌍 Service location :%s
🗂 Product name :%s

🔋 Traffic : %s
📥 Consumed volume : %s
💢 Remaining volume : %s (%s%%)

📅 Expiry date : %s (%s)

%s

💡 To cut off others\' access, just click the "Change link" option.',
                        'summary' => '
  
 Service status: %s
        
🔋 Service volume: %s
📥 Consumed volume: %s
💢 Remaining volume: %s (%s%%)

📅 Active until: %s (%s)

User subscription link: 
<code>%s</code>

📶 Last connection time: %s
🔄 Last subscription link update time: %s
#️⃣ Connected client:<code>%s</code>',
                ],
                'support' => [
                        'answermessage' => 'Reply to message',
                        'btnsupport' => '☎️ In the button below (FAQ), your frequently asked questions are listed. Click the button below; if you do not find your question, click the support button',
                        'sendmessageadmin' => '🚀 Your message has been sent. Please wait for the administration\'s reply',
                        'messageFromAdminAlt' => '
👤 A message has been sent from the admin  
Message text:

%s',
                        'messageFromManagement' => '
📩 A message was sent to you from management.
                    
Message text: 
%s',
                        'messageFromManagement2' => '
📩 A message was sent to you from management.
                    
Message text: 
%s',
                        'requestSubmitted' => '✅ Thank you for submitting the request. Your request has been sent and is being reviewed by support.',
                        'selectDepartment' => '📌 Select the support section you want to message.',
                        'sendMessage' => '📌 Send your message',
                        'sentForReview' => '✅ Your message was sent successfully and you will be answered after review.',
                        'sendMessageText' => '📌 Send the text of your message',
                        'sentSuccess' => 'The message was sent successfully',
                        'sentForRequestReview' => '✅  Your message for this request was sent successfully. It will be answered after review.',
                        'disruptionConfirm' => '❓ Are you sure about sending the outage report

🔹 Before sending a report, view the connection tutorials. ( /help )',
                        'disruptionPrompt' => '❓ Write the reason for your outage

🔹 Before sending a report, view the connection tutorials. ( /help )',
                        'messageFromAdmin' => '
📩 A message was sent to you from management.
                    
Message text: 
%s',
                ],
                'transfer' => [
                        'confirm' => '✅ If you confirm, click the button below so that your transfer is completed successfully.',
                        'confirmed' => '✅ The service transfer was completed successfully.',
                        'description' => '🛂 To transfer this subscription to other users, you must have the destination account\'s user ID.

‼️ Transfer notes:
1 - To get the user ID, go to the wallet button 
2 - After transferring the subscription to the destination user, the subscription will be removed from your panel.

🆕 Enter the destination account\'s user ID:',
                        'notSendServiceYou' => '❌ It is not possible to transfer the service to yourself.',
                        'notUserTrans' => '❌ No user was found with this ID.',
                        'title' => '🚚 Transfer service to another user',
                        'transferNotValid' => '❌ It is not possible to transfer a test service to another user.',
                        'receivedNotice' => '✅ Dear user, the service with username {service_username} was transferred to your account by the user with user ID {from_id}.',
                ],
                'usertest' => [
                        'errorcreat' => '❌ An error occurred while creating the subscription. Please contact support to resolve the issue.',
                        'limitwarning' => '⚠️ Your test subscription creation limit has been reached.',
                        'unavailable' => '📌 The test service is not available at the moment.',
                ],
                'wheelLuck' => [
                        'alreadyParticipated' => '❌ You already participated today. Try your luck again tomorrow',
                        'error' => '❌ An error occurred while getting the game result. Please try again later.',
                        'featureDisabled' => '❌ This feature is currently turned off',
                        'notWinner' => '🥲 Unfortunately you did not win. Try again another day',
                        'wheelWinner' => '⭕️ A user with username @%s and numeric ID %s won the wheel of fortune',
                        'winnerCongratulations' => '🤩 Congratulations, you won! The amount of %s Toman has been added to your account.',
                        'resultError' => '❌ An error occurred while getting the game result. Please try again later.',
                ],
        ],
        'Admin' => [
                'activeBotText' => 'To use the admin panel features:

Go to a page that has a keyboard at the bottom.
In the keyboard, find a button called Bot Reports and click on it.
After clicking the Bot Reports button, a page will open.
On this page, you can select and configure the group you want.
This step is mandatory',
                'askNewText' => '📌 Send your new text',
                'backAdmin' => 'You have returned to the admin panel!',
                'backAdminBtn' => '🏠 Back to management menu',
                'backMenu' => 'You have returned to the previous menu!',
                'backMenuBtn' => '▶️ Back to previous menu',
                'changesSaved' => 'Changes applied successfully',
                'changesSaved2' => '✅ Changes saved successfully',
                'confirmByButton' => 'To confirm, click the confirm button',
                'confirmByWord' => 'To confirm, send the word below.
<code>confirm</code>',
                'errorCode' => '❌  An error occurred. Error code:  %s',
                'errorCode2' => '❌  An error occurred. Error code:  %s',
                'errorCode3' => '❌  An error occurred. Error code:  %s',
                'errorCode4' => '❌  An error occurred. Error code:  %s',
                'errorCode5' => '❌  An error occurred. Error code:  %s',
                'errorCode6' => '❌  An error occurred. Error code:  %s',
                'errorOccurred' => 'An error occurred',
                'errorRestart' => '❌ An error occurred; go through the steps from the beginning.',
                'getStats' => 'If you want to view the statistics for a different date range, first send the start date.
Example: 
<code>%s</code>',
                'invalidValue' => '❌ Invalid value',
                'mainAdminOnly' => '❌ This section is only available to the main admin',
                'installerNotice' => [
                        'user' => '⛔️ <b>The bot is temporarily unavailable.</b>

The service is under maintenance. Please try again in a few minutes or contact support.',
                        'admin' => '⛔️ <b>The bot is halted: the install folder was not removed.</b>

The <code>install</code> folder still exists on the server and the bot could not remove it automatically. While that folder remains, the bot will not answer any user.

🔹 Connect to the server over SSH and delete the <code>install</code> folder from the bot directory.
🔹 Then check the ownership and permissions of the bot directory so the web server user is allowed to delete it.',
                ],
                'notUser' => 'No user was found with this ID',
                'panelAdmin' => '👨‍💼 Management panel',
                'saved' => '✅ Saved.',
                'selectOption' => '📌 Select an option',
                'selectOption2' => 'Select an option',
                'selectOption3' => '📌 Select an option from the list below',
                'selectOption4' => 'Select one of the options below ',
                'selectOption5' => '📌 Select an option.',
                'Balance' => [
                        'addAllBalance' => '📌 Send the amount for a public top-up',
                        'addBalanceUser' => '✅ The amount was added to the user\'s balance',
                        'addBalanceUsers' => '✅ The amount was added to the users\' balances',
                        'invalidPrice' => 'The amount is invalid',
                        'negativeBalance' => '⚜️ Send the user\'s numeric ID 
Description: To deduct the user\'s balance, first send the user\'s numeric ID',
                        'negativeBalanceUser' => '✅ The amount was deducted from the user\'s balance',
                        'priceBalance' => 'The numeric ID was received. Send the amount you want to deduct from the user; the amount should be in Toman',
                        'askUserGroup' => '📌 Which of the following user groups should the top-up be deposited to?',
                        'askTargetUsers' => '📌 Which user should the public top-up be sent to?',
                        'askNotify' => '📌 Should a top-up notification message be sent to the users or not?
Yes: 1
No: 0',
                        'operationStarted' => '✅ The message-sending operation has begun. You will be notified when it finishes.',
                        'btnDecrease' => '⬇️ Decrease balance',
                        'maxAmountRial' => '📌 The maximum amount is 100 million Rials.',
                        'maxAmountToman' => '❌ The maximum amount is 100 million Toman',
                        'askMinCharge' => '📌 Set the minimum amount you want the user to top up their account with',
                        'askMinChargeGroup' => '📌 For which user group should the minimum balance apply?
f
n
n2',
                        'askMaxCharge' => '📌 Set the maximum amount you want the user to top up their account with',
                        'askMaxNegative' => '📌 Send the maximum amount the user\'s balance can go negative when purchasing
Note: the number should be without a dash or minus sign
If you want the user to purchase unlimited, send the number 0',
                        'addedToUser' => '✅ The amount was successfully added to the user\'s account.',
                        'askMinDeposit' => '📌 Send the minimum deposit amount',
                        'minDepositSaved' => '✅ The minimum deposit amount was set.',
                        'askMaxDeposit' => '📌 Send the maximum deposit amount',
                        'maxDepositSaved' => '✅ The maximum deposit amount was set.',
                        'askChargeAmount' => '📌 Send the amount you want to charge the user\'s account.',
                        'addedToUserNotice' => '❌ An amount of %s Toman was added to the user\'s balance.',
                        'resetToZero' => 'The user\'s balance of %s was reset to zero',
                ],
                'Channel' => [
                        'setChannelReport' => '🔰 The channel was successfully configured',
                        'testChannel' => 'Test group connection',
                        'notForumGroup' => '❌ The selected group is not in forum mode. First enable the group\'s topic feature, then set the group\'s numeric ID again',
                        'botNotGroupAdmin' => '❌ The bot is not an admin of the group',
                        'askReportGroupId' => '📣 In this section you can send the group\'s numeric ID for sending notifications
Group setup tutorial:
1 - First create a group 
2 - Add the bot @myidbot to the group and send the command /getgroupid@myidbot inside the group 
3 - Turn on topic or forum mode from the group settings4
4 - Make your own bot an admin of the group 
5 - Send the sent numeric ID to the bot.

Your current numeric ID: %s',
                        'connectionFailed' => '❌ The connection to the group was not successful  

Received error:  %s',
                ],
                'Discount' => [
                        'agentCode' => 'For which user do you want to define the code?

⚠️ If you want to define it for all users, send the text <code>allusers</code>',
                        'errorCode' => 'The code is invalid. The code must be in English without extra characters',
                        'firstDiscount' => '📌 Should the discount code be for the first purchase or all purchases?',
                        'getCode' => 'Send a code for the gift code',
                        'invalidAgentCode' => '❌ The user type is invalid',
                        'notCode' => '❌ Error 
📝 The selected gift code does not exist',
                        'priceCode' => 'The code was received. Now send the code\'s amount',
                        'priceCodeSell' => 'The code was received. Now send the code\'s percentage',
                        'removeCode' => 'Select the code you want to delete',
                        'removedCode' => '✅ The code was successfully deleted.',
                        'saveCode' => '✅ The code was successfully registered',
                        'setLimitUse' => '📌 Send the usage limit.
⚠️ The limit is for all users',
                        'askActiveHours' => '📌 For how many hours should the discount code be active? If you want it to be unlimited, send the number 0',
                        'askUserLimit' => '📌 Send the usage limit per user.',
                        'askSection' => '📌 Which section should the discount code apply to?',
                        'userLimitTooHigh' => '📌 The usage count per user must be smaller than the total limit',
                        'askProductLocation' => '📌 To set a discount code for a specific product, first select the product position.
Note: To select all panels, send the word <code>/all</code>',
                        'askProduct' => '📌 Which product should the discount code apply to? Note that if you want the discount code to apply to all products, send the word all',
                        'invalidPercent' => 'Invalid percentage',
                        'created' => '
🎁 Your discount code was created successfully.

📩 Discount code name: <code>%s</code>
🧮 Discount code percentage: %s
🎛 Panel:  %s
📌  Product: %s
♻️ User type: %s
🔴 Usage limit: %s',
                ],
                'Discountsell' => [
                        'getCode' => 'Send a code for the discount code',
                        'getLimit' => '📌 How many users can use this discount code?',
                ],
                'Help' => [
                        'getAddDesc' => ' 🔗 The tutorial name was saved. Now send your description 
⚠️ Note:
🔸 You can send the description along with a photo or video',
                        'getAddName' => 'To add a tutorial, send a name 
⚠️ Note: The tutorial name is the name the user sees in the list.',
                        'removeHelp' => '✅ The tutorial was deleted.',
                        'saveHelp' => '✅ The tutorial was successfully saved',
                        'selectName' => 'Select the tutorial name',
                        'nameTooLong' => '❌ The tutorial name must be less than 150 characters',
                        'nameExists' => '❌ The tutorial name already exists. Use a different name.',
                        'askCategoryName' => '📌 Send the category name for the tutorial',
                        'nameUpdated' => '✅ Tutorial name updated',
                        'askNewCategory' => 'Send your new category',
                        'categoryUpdated' => '✅ Tutorial category name updated',
                        'askNewDesc' => 'Send the new description',
                        'descUpdated' => '✅ Tutorial description updated',
                        'askNewMedia' => 'Send the new image or video',
                        'askTutorialMedia' => '📌 Send your tutorial.
1 - If you don\'t want a tutorial to be shown, send the number 2
2 - You can send the tutorial as video, text, or image',
                        'invalidContent' => '❌ The submitted content is invalid.',
                        'tutorialSaved' => '✅ The tutorial was saved successfully.',
                ],
                'Payment' => [
                        'reasonRejecting' => 'Send the reason for rejecting the payment',
                        'rejected' => '⭕️ The payment was successfully rejected and a message was sent to the user',
                        'reviewedPayment' => '❌ This payment has already been reviewed by another admin',
                        'reviewReceiptsFirst' => '⚠️ To approve user requests, first review and approve the purchase or subscription renewal receipts. Then approve the wallet top-up receipt. ',
                        'autoConfirmDesc' => '📌 By activating this feature, during the times when you are not online, the bot automatically approves all card-to-card transactions; then after you come online, you review the receipts, and if a fake receipt was sent, you cancel the transaction',
                        'noPending' => '❌ You have no unapproved payments.',
                        'pendingIntro' => '📌 Unapproved card-to-card payments 
In this section you can view unapproved payments and approve or reject them.
❌ : Reject payment 
✅ : Approve payment
📝 Payment details
🗑 : Delete receipt without notifying the user',
                        'allReceiptsDeleted' => '✅ All receipts were deleted successfully ',
                        'receiptDeleted' => '✅ The receipt was deleted successfully.',
                        'autoConfirmSelect' => '📌 Select an option
⚠️ This section is for automatic approval without review',
                        'askExcludeUserId' => '📌 Send the user\'s numeric ID',
                        'userNotFound' => '❌ The user does not exist.',
                        'userAlreadyExcluded' => '❌ The user is already in the exception list',
                        'userExcluded' => '✅ The user was successfully added to the list.',
                        'askRemoveExcludeId' => '📌 Send the user\'s numeric ID to remove from the list',
                        'userNotExcluded' => '❌ The user is not in the exception list',
                        'userExcludeRemoved' => '✅ The user was successfully removed from the list.',
                        'excludeListEmpty' => '❌ There is no user in the list',
                        'excludeListTitle' => 'List of people👇',
                        'approvedByOther' => '✅. The payment was approved by another admin
👤 User ID: <code>%s</code>
🛒 Payment tracking code: %s
⚜️ Username: @%s
💎 Balance after approval: %s
💸 Paid amount: %s Toman
',
                        'detailRow' => '🛒 Payment number:  <code>%s</code>
🙍‍♂️ User ID: <code>%s</code>
💰 Paid amount: %s Toman
⚜️ Payment status: %s
⭕️ Payment method: %s 
📆 Purchase date:  %s',
                        'detailRow2' => '🛒 Payment number:  <code>%s</code>
🙍‍♂️ User ID: <code>%s</code>
💰 Paid amount: %s Toman
⚜️ Payment status: %s
⭕️ Payment method: %s 
📆 Purchase date:  %s',
                        'askAutoConfirmMinutes' => '📌 In this section you can set after how many minutes the automatic approval without review approves the receipt.
Send your time in minutes
Current time: %s',
                ],
                'Product' => [
                        'addProductStepOne' => ' First send your subscription name
⚠️ Notes when entering the product name:
• Be sure to also enter the subscription price next to the subscription name.
• Be sure to also enter the subscription duration next to the subscription name.',
                        'getLimit' => 'Send the subscription volume. Note: the volume unit is gigabytes.

If you want the volume to be unlimited, send the number 0',
                        'getPrice' => '
Send the subscription price.
Note:
The product is in Toman, and send the price without any extra characters.',
                        'getTime' => '
Enter the subscription duration. Note: the time unit for the subscription is days.
If you want the time to be unlimited, send the number 0',
                        'getTimeReset' => '📌 Send the periodic reset time for the service volume. If you do not want a reset, send the button no_reset',
                        'invalidPrice' => 'The price is invalid',
                        'newTime' => 'Send the new time',
                        'removeLocation' => '📌 Choose the position of your product',
                        'removedProduct' => '✅ The product was successfully deleted.',
                        'saveProduct' => 'The product was successfully saved 🥳🎉',
                        'selectEditProduct' => 'Select the product you want to edit',
                        'selectRemoveProduct' => 'Select the product you want to delete',
                        'serviceLocation' => '📌 Choose the position of your product

 ⭕️ To define the product in all positions, send the command /all',
                        'timeUpdated' => '✅ The product time was updated',
                        'volumeUpdated' => '✅ The product volume was updated',
                        'nameTooLong' => '❌ The product name must be less than 150 characters',
                        'askNote' => ' 🗒 Send the note for the product. This note is shown in the user\'s proforma invoice.',
                        'askNote2' => ' 🗒 Send the note for the product. This note is shown in the user\'s proforma invoice.',
                        'askNewPrice' => 'Send the new price',
                        'priceUpdated' => '✅ The product price was updated',
                        'askNewNote' => 'Send the new note',
                        'noteUpdated' => '✅ The product note was updated',
                        'selectNewCategory' => 'Select the new category name',
                        'categoryUpdated' => '✅ The product category was updated',
                        'askNewName' => 'Send the new name',
                        'nameUpdated' => '✅ The product name was updated',
                        'askNewUserType' => 'Send the new user type:
User types: f, n, n2',
                        'invalidUserGroup' => '❌ The user group is invalid',
                        'askVolumeResetType' => 'Send the volume reset type',
                        'selectNewLocation' => '📌 Select the new product position',
                        'cannotChangeToAll' => '❌ You cannot change a defined product to the position name /all.',
                        'locationUpdated' => '✅ The product position was updated',
                        'askNewVolume' => 'Send the new volume',
                        'updated' => '✅ Product updated',
                        'firstPurchaseDesc' => '📌 Through this feature you can set whether this product is for the first purchase or not',
                        'nameExists' => '❌ A product named %s already exists',
                        'editSummary' => '
📌 Information of the product being edited:
Product name: %s
Product price: %s
Product volume: %s
Product location: %s
Product time: %s
Product user type: %s
Periodic volume reset of product: %s
Product note: %s
Product category: %s
Number of products sold: %s
    
',
                        'nameExists2' => '❌ A product named %s already exists',
                ],
                'Protocol' => [
                        'invalidProtocol' => '❌ Invalid protocol',
                        'removeProtocol' => 'Select the protocol you want to delete.',
                        'removedProtocol' => 'The protocol was successfully deleted.',
                        'btnDelete' => '🗑 Delete protocol',
                        'btnSettings' => '⚙️ Protocol settings',
                ],
                'SettingPayment' => [
                        'cartDirect' => '✅ Your username was successfully registered.',
                        'getNameCard' => '📌 Send the cardholder\'s name.',
                        'saveCard' => '✅ Your card number was successfully registered.',
                ],
                'SettingnowPayment' => [
                        'activeShowCardStatus' => '📌 Displaying the card number was enabled for all users.',
                        'disableShowCardStatus' => '📌 Displaying the card number was disabled.',
                        'saveApi' => '✅ The changes were successfully saved',
                ],
                'Status' => [
                        'Authenticationiran' => '🇮🇷 Verify Iranian number',
                        'Authenticationphone' => '☎️ Phone number verification',
                        'activePanel' => '⭕️ In this section you can turn the panel off or on for sales',
                        'activePanelOff' => '❌ The panel was turned off',
                        'activePanelOn' => '✅ The panel was turned on',
                        'botTitle' => '📌 In this section you can specify whether the following features are enabled or not.',
                        'btn' => '📊 Bot statistics',
                        'cardStatusOffPv' => '⭕ The offline gateway status in PV was turned off',
                        'cardStatusOnPv' => 'The offline gateway status in PV was turned on',
                        'cardTitlePv' => 'In this section you can disable the card-to-card feature and handle the card-to-card process from PV',
                        'commission' => 'Status of the gift-after-bot-start feature being enabled',
                        'commissionOff' => 'The commission feature was disabled',
                        'commissionOn' => 'The commission feature was enabled',
                        'discountAffiliates' => 'Status of the gift feature being enabled',
                        'discountAffiliatesOff' => 'The gift feature was disabled',
                        'discountAffiliatesOn' => 'The gift feature was enabled',
                        'inlinebtns' => '🛡 Make the bot buttons inline',
                        'paydirect' => '🎯 Direct purchase status',
                        'statusBot' => '📡 Bot status',
                        'statusCategoryTime' => '⏱ Time category',
                        'statusNotifNewUser' => '👤 New user notification',
                        'statusRole' => '♨️ Rules',
                        'statusShowAgent' => '👨‍💻 Agent request',
                        'statusSubject' => 'Status',
                        'statusTimeExtra' => '⏳ Extra time',
                        'statusUsernameBtn' => '👤 Username button',
                        'statusVolumeExtra' => '🔋 Extra volume status',
                        'statusoff' => '❌ Off',
                        'statuson' => '✅ On',
                        'subject' => 'Title',
                        'botOn' => '✅ The bot is on',
                        'botOff' => '❌ The bot is off',
                        'rulesOn' => '✅ Rule confirmation is on',
                        'rulesOff' => '❌ Rules confirmation is off',
                        'phoneVerifyOn' => '✅ Mobile number verification is on',
                        'phoneVerifyOff' => '❌ Phone number verification is disabled',
                        'iranPhoneOn' => '✅ Iranian number verification is on',
                        'iranPhoneOff' => '❌ Iranian number check is disabled',
                        'applyScope' => 'Disable for all users or only new users?
    New users 0 
    All users 1
    2 Users except agents',
                        'off' => 'Off',
                        'on' => 'On',
                ],
                'addorder' => [
                        'stepFive' => '📌 The service was successfully added to the user\'s account.',
                        'stepFour' => '📌 Send the product name.',
                        'stepThree' => '📌 Select the config location.',
                        'stepTwo' => '📌 Send the user\'s config username.',
                        'usernameExists' => '❌ This username already exists in the bot.',
                        'manualIntro' => '📌 In this section you can manually create and receive an order 
⚠️ If you want the config to be added to the user\'s account and managed by the user, you must use the add order option.
- To add a config, first send the username.',
                        'askConfigCount' => '📌 Send the number of configs you want to create; you can send up to 10',
                        'invalidCount' => '❌ You can send a minimum of 1 and a maximum of 10.',
                        'askVolume' => '📌 Send the account\'s data volume. The volume is in gigabytes.',
                        'askTime' => '📌 Send the service duration; the time is in days.',
                        'customPlan' => 'Custom plan',
                        'selectPanel' => '📌 Select from the list below which panel the order should be created on',
                        'panelSelected' => '✅ Panel selected successfully',
                ],
                'affiliates' => [
                        'askBanner' => '⭕️ Send your referral banner 

❌ The banner must include an image',
                        'joinGiftSaved' => '✅ The referral amount was successfully registered',
                        'percentSaved' => '✅ The deposit percentage for the user was successfully set',
                        'bannerSaved' => '✅ Your banner was successfully registered.',
                        'invalidBanner' => '❌ The banner you sent is invalid (the banner must be sent with an image)',
                        'askJoinGift' => '📌 Enter the amount you want the user to receive for each new referral',
                        'askPercent' => '📌 Send the percentage you want to be deposited to the user after a purchase',
                        'titleTopic' => '🎁 Commission reports',
                        'noReferrals' => '❌ The user has no referrals.',
                        'idsSent' => '📌 The ID related to the user\'s referrals has been sent.',
                        'userRemoved' => '📌 The user was removed from the referral.',
                        'referralsDeleted' => '📌 The user\'s referrals were deleted.',
                        'commissionScope' => 'You can decide whether the commission is given to the user only for their referral\'s first purchase or for all of their purchases.',
                ],
                'agent' => [
                        'getTypeAgent' => '📌 To add an agent, send the agent type
1- Agent type one (n): this agent has normal capabilities 
2- Agent type two (n2): this type of agent can purchase services without a credit limit.',
                        'invalidTypeAgent' => '❌ The agent type is invalid',
                        'setAgentProduct' => 'For which user should the product be shown?
Type one f: a regular user
Type two n: a type-two agent with limited capabilities
Type three n2: a type-three agent with more capabilities',
                        'userAgentRemoved' => '❌ The user was successfully removed from agent status',
                        'userAgented' => '✅ The user successfully became an agent',
                        'selectUserType' => '📌 Select the user type',
                        'expiryDateLabel' => '⭕️ Agency expiry date: ',
                        'selectGroup' => '📌 Which group of agents do you want to view?',
                        'requestRejected' => '✅ The request was successfully rejected.',
                        'changeTypeHint' => '
Use the buttons below to change the agent type.',
                        'askMembershipFee' => '📌 Send the price of the membership request for agency.',
                        'askExpiry' => '🕘 Send the agency expiry time. After the specified number of days ends, the user will exit agency status and become user group f.
Note that this feature has nothing to do with the bot-builder or agent\'s sales bot feature and only relates to your main bot

📌 Send the number of days',
                        'expirySaved' => '✅ The expiry date was set.
📌 After the time ends, the user\'s user group will be changed to f and the user will be notified.',
                        'requestNotice' => '📣 A user has submitted an agency request; please review the information and determine the status.

Numeric ID: %s
Username: %s 
Description:  %s ',
                        'requestNotice2' => '📣 A user has submitted an agency request; please review the information and determine the status.

Numeric ID: %s
Username: %s
Description:  %s ',
                        'statusApproved' => '
Status: approved (%s)',
                        'requestNotice3' => '📣 A user has submitted an agency request; please review the information and determine the status.

Numeric ID: %s
Username: %s
Description:  %s ',
                        'statusApproved2' => '
Status: approved (%s)',
                ],
                'agentbot' => [
                        'askToken' => '📌 Send the token',
                        'limitReached' => '❌ Currently you are limited to creating only 15 bots for your agents.',
                        'alreadyInstalled' => '❌ This bot is already installed; it cannot be reinstalled.',
                        'intro' => '📌 Through this section you can create a sales bot for your agent so that the agent can sell with their own dedicated bot

- To create a bot, send the bot token.',
                        'invalidToken' => '❌ The token is invalid',
                        'tokenExists' => '📌 This token is already registered',
                        'deleted' => '❌ The agent\'s sales bot was deleted successfully.',
                        'noneFound' => '❌ There is no bot',
                        'webhookRunning' => '📌 Performing webhook ...',
                        'webhookDone' => '✅ The webhook was performed successfully.',
                        'activatedUrlAlt' => 'https://api.telegram.org/bot%s/sendmessage?chat_id=%s&text=✅ Dear user, your bot was installed successfully.',
                        'created' => '✅ The agent bot was created successfully.
⚙️ Bot username  : @%s
🤠 Bot token : <code>%s</code>',
                        'activatedUrl' => 'https://api.telegram.org/bot%s/sendmessage?chat_id=%s&text=✅ Dear user, your bot was installed successfully.',
                ],
                'algorithmExtend' => [
                        'saveData' => '✅ The service renewal method was successfully updated',
                        'invalidMethod' => '❌ The renewal method is invalid; select the correct renewal method from the list below',
                ],
                'algorithmUsername' => [
                        'saveData' => '✅ The username creation method was successfully updated',
                        'selectMethod' => '⭕️ Select the username generation method for accounts from the button below.
        
⚠️ If a user has no username, the word you choose will be registered and used in place of the username.
        
⚠️ If a username already exists, a random number will be added to the username',
                        'askFallbackName' => '📌 What name should be registered if the user has no username?',
                ],
                'api' => [
                        'token' => 'Your api token: <code>%s</code>',
                        'docsLink' => '📘 Full API reference:
%s

Send the token above in the <code>Token</code> header of every request.',
                ],
                'apps' => [
                        'askName' => '📌 To add an app download link, send the app name or the button name.',
                        'nameTooLong' => '📌 The name must be fewer than 200 characters.',
                        'askLink' => '📌 Send the app download link',
                        'added' => '✅ Your app link was added successfully.',
                        'selectDelete' => '📌 To delete an app, select the app name from the list below',
                        'deleted' => '✅ The app was deleted successfully.',
                        'selectEdit' => '📌 To edit an app, select the app name from the list below',
                        'askNewLink' => '📌 Send the new app link',
                        'updated' => '✅ The app link was updated successfully.',
                ],
                'btnKeyboard' => [
                        'addPanel' => '🖥 Add panel',
                        'manageUser' => '👤 User management',
                        'managementPanel' => '✏️ Panel management',
                ],
                'card' => [
                        'askNumber' => '💳 Send your card number

⚠️ Note that you can define several card numbers; if you define multiple card numbers, the bot will show the user a random one from among them',
                        'mustBeNumeric' => '❌ The card number must be numeric.',
                        'exists' => '❌ The card number already exists in the database.',
                        'saveFailed' => '❌ Failed to register the card number. Please try again or contact support.',
                        'enabled' => '✅ Card number activated',
                        'disabled' => '✅ Card number deactivated',
                        'askDelete' => '📌 Send the card number you want to delete.',
                        'deleted' => '✅ The card number was deleted successfully.',
                        'afterFirstPayDesc' => '📌 By turning on this feature, the card-to-card gateway will be activated for the user after their first payment',
                        'afterFirstPayOff' => 'Turned off',
                        'afterFirstPayOn' => 'Turned on',
                        'askUserIds' => '📌 Send the list of IDs for which you want the card number to be shown 
Example: 
1234435423
23423131',
                        'enabledForUsers' => '✅ The card number was activated for the sent users.',
                        'noUsersEnabled' => '📌 No card number has been activated for any user',
                        'enabledUserList' => '🪪 List of users for whom the card number is active',
                        'askUsername' => '📌 Send your username without @ to receive the card number

%s',
                        'askSupportUsername' => '📌 Send your username without @ for support

%s',
                ],
                'category' => [
                        'askName' => '📌 Send your category name.',
                        'notFoundAddFirst' => '❌ The selected category does not exist. Add your category from Plans > Add Category, then add the product.',
                        'notFoundAddFirst2' => '❌ The selected category does not exist. Add your category from Plans > Add Category, then add the product.',
                        'askAddName' => '📌 To add a category, send the category name.',
                        'added' => '✅ The category was added successfully.',
                        'selectDelete' => '📌 Select your category to delete',
                        'deleted' => '✅ The category was deleted successfully.',
                        'selectEdit' => '📌 Select your category to edit',
                        'askNewName' => '📌 Send the new category name',
                        'renamed' => '✅ The category name was changed successfully.',
                ],
                'changeLocation' => [
                        'askLimitType' => '📌 Select an option.
1 - The overall limit on how many times the user can change location in total.
2 - The free limit on how many times the user can change location for free out of the overall limit.',
                        'limitSaved' => '✅ The limit was set successfully.',
                        'resetConfirm' => '📌 By confirming the option below, all location changes made by the user will be reset to zero. If you agree, click the option below.',
                        'limitsReset' => '✅ All users\' limits were reset to zero.',
                        'askUserLimit' => '📌 Send the new limit you want to set for the user. Note that this feature changes the number of location changes already made',
                        'userLimitSaved' => '✅ The user\'s usage count was saved successfully.',
                        'askTotalLimit' => '📌  Send the overall limit on how many times the user can change location. Note that this limit applies to all configs
Current limit: %s',
                        'askFreeLimit' => '📌  Send the free limit on how many times the user can change location. Note that this limit applies to all configs
Current limit: %s',
                ],
                'channel' => [
                        'changeChannel' => 'To set the mandatory membership channel, please enter your channel username with @, or the channel\'s numeric ID that starts with -100',
                        'description' => '📌 To enable the mandatory join feature, add a channel
Notes for this feature: 
- The bot must be an admin of the channel
- To disable this feature, you must delete all channels',
                        'removeChannel' => '📌 Send one of the channels below to delete',
                        'removeChannelBtn' => 'Delete channel',
                        'removedChannel' => '❌ The channel was successfully deleted.',
                        'title' => 'Add channel',
                        'askButtonName' => '📌 Choose a name for the channel membership button.',
                        'askJoinLink' => '📌 Send the membership link',
                        'invalidJoinLink' => 'The membership address is not correct',
                        'joinChannelSaved' => '✅ The mandatory join channel was successfully registered.',
                        'joinExempt' => '📌 From now on, the user can use the bot without joining the channel',
                ],
                'config' => [
                        'askProductName' => '📌 Send your product name. If you want to set it for the test account, send the text test.',
                        'testKeyword' => 'test',
                        'productNotFound' => 'No product found with this name. Please send the exact product name, or send the text test to set up a test config.',
                        'askConfigs' => '📌 Send your configs like the example below.

# config name ( on one line only, with # before the name )
config ( on multiple lines 

# config name ( on one line only, with # before the name )

trojan://xyz',
                        'saved' => '✅ Number of saved configs: ',
                        'saveError' => '❌ An error occurred while saving the config. Please try again.',
                        'btnDelete' => '❌ Delete config',
                        'askDeleteName' => '📌 Send the name of the config you want to delete ',
                        'deleted' => '✅ The config was deleted successfully.',
                        'askEditName' => '📌 Send the name of the config you want to edit ',
                        'askNewContent' => 'Send the new config content',
                ],
                'connection' => [
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'notConnected' => 'Not connected',
                ],
                'cronjob' => [
                        'changedData' => '✅ The changes were successfully saved',
                        'setDayRemove' => '📌 Send the number of days after which accounts whose time has expired should be deleted
Current time: ',
                        'setVolumeRemove' => '📌 Send the number of days after which accounts whose volume has run out should be deleted. The account time is calculated based on the user\'s last connection. This feature is for the Marzban panel
Current time: ',
                        'btnSettings' => '🕚 Cron job settings',
                        'cannotDeleteUnlimited' => '❌ The service cannot be deleted because its volume and time are unlimited. ',
                        'askOnHoldDays' => 'In this section you must set, if the user has not connected to their config after a certain number of days and is in on_hold status, to send the user a message',
                        'askVolumeAlert' => '📌 In this section you can set that if the user\'s volume reaches x, a warning message is sent. Send the volume in GB.',
                        'askNotifyDays' => '📌 In this section you can set how many days before the subscription ends the user is notified. The time is in days',
                        'userNotifyEnabled' => '✅ Cron notifications were enabled for the user.',
                        'userNotifyDisabled' => '✅ Cron notifications were disabled for the user.',
                        'timeSaved' => '✅ The time was registered successfully.',
                ],
                'department' => [
                        'askName' => '📌 Send the department name',
                        'added' => '📌 The department was added successfully.',
                        'noneToDelete' => '❌ There is no department to delete.',
                        'askDelete' => '📌 Send the type of department to delete.',
                        'deleted' => '📌 The selected section was deleted.',
                ],
                'gateway' => [
                        'cubepayFeeOn' => "✅ Passing the fee to the customer is <b>on</b>.\n\n💵 Current value: <b>%s</b>\nIt is added to the customer's invoice, while their balance is still topped up with the amount they requested.",
                        'cubepayFeeOff' => "❌ Passing the fee to the customer is <b>off</b>.",
                        'cubepayFeeAsk' => "💵 Send the fee value:\n\n▫️ <b>0 to 100</b> → a <b>percentage</b> fee (decimals allowed, e.g. <code>9.9</code>)\n▫️ <b>above 100</b> → a <b>fixed amount in toman</b> (e.g. <code>5000</code>)\n\nCurrent value: <b>%s</b>",
                        'cubepayFeeSavedPercent' => "✅ Saved — <b>%s%%</b> percentage fee\n\nExample: a 100,000 toman order becomes <b>%s</b> toman for the customer.",
                        'cubepayFeeSavedFixed' => "✅ Saved — <b>%s toman</b> fixed fee\n\nExample: a 100,000 toman order becomes <b>%s</b> toman for the customer.",
                        'off' => 'Turned off',
                        'on' => 'Turned on',
                        'intro' => '📌 From the list below you can manage the gateways.

⚠️ The Mirza team gives no guarantee for the gateways, and all use and responsibility is on you',
                        'btnPerfectMoneyHelp' => '📚 Set up Perfect Money tutorial',
                        'askPlisioApi' => '⚙️ Please send your Plisio API Key.

🔑 To get your API key, visit the following site:
plisio.net

📌 Your current key:
<code>%s</code>',
                        'askNowPaymentsApi' => '⚙️ Please send your NowPayments API Key.

🔑 To get your API key, visit the following site:
nowpayments.io

📌 Your current key:
<code>%s</code>',
                        'askAqayePardakhtMerchant' => '💳 Obtain your merchant code from Aghaye Pardakht and enter it in this section
        
Your current merchant code: %s',
                        'askZarinpalMerchant' => '💳 Obtain your merchant code from ZarinPal and enter it in this section
        
Your current merchant code: %s',
                        'askMerchant' => '💳 Obtain your merchant code and enter it in this section
        
Your current merchant code: %s',
                        'askTronWallet' => '💳 Send your Tron trc20 wallet address
        
        Your current wallet: %s',
                        'askApiCode' => '📌 Send your API code.
        
        Your current merchant: %s',
                        'askApiCode2' => 'Send the received api in this section
        
Your current merchant code: %s',
                ],
                'getlimitusertest' => [
                        'getId' => 'Send the test account creation limit',
                        'limitAll' => 'Enter the test account creation limit.',
                        'setLimit' => 'The limit was set for the user.',
                        'setLimitAll' => 'The account creation limit was set for all users',
                        'setLimitBtn' => '➕ Test account creation limit for everyone',
                        'askTime' => '🕰 Send the duration of the test service.
⚠️ The time is in hours.',
                        'askVolume' => 'Send the volume of the test service.
⚠️ The volume is in megabytes.',
                ],
                'gift' => [
                        'askUserGroup' => '📌 Which user group should the service be applied to?',
                        'askUserCategory' => '📌 Which category of users should the service be applied to?',
                        'busy' => '❌ The gift sending system is performing an operation; after it finishes and notifies you, you can send a new message.',
                        'askPanel' => '📌 For which panel\'s services do you want to gift volume or time?',
                        'panelNotFound' => '❌ The panel does not exist',
                        'selectType' => '📌 Select one of the gifts below.',
                        'askVolume' => '📌 How many gigabytes of volume do you want added to the user\'s services?',
                        'askDays' => '📌 How many days do you want added to the users\' services?',
                        'askMessage' => '📌 Send the text you want to be sent to the user',
                        'confirmStart' => '📌 Dear admin, by confirming the option below, the process of applying the gifts will begin. Note that due to the limits, applying the gifts will take time.',
                        'started' => '✅ The gift sending operation started successfully; you will be notified after it is added and completed.',
                        'canceled' => '📌 Gift sending was canceled.',
                        'done' => '📌 The operation was performed for all requested services.',
                        'volumeAddError' => 'Error adding gift volume
Panel name : %s
Service username : %s
Error reason : %s',
                        'volumeAddError2' => 'Error adding gift volume
Panel name : %s
Service username : %s
Error reason : %s',
                ],
                'manageUser' => [
                        'acceptedPhone' => 'Confirmed',
                        'addBalanceUser' => '👆 Increase balance',
                        'addBalanceUserDesc' => '⭕️ Send the amount you want to add',
                        'addBalanced' => '✅ The balance was successfully added to the user\'s account.',
                        'addagent' => '🤖 Add agent',
                        'banUserList' => '🔒 Block user',
                        'blockUser' => '🚫 The user was blocked. Now also send the reason for the block.',
                        'blockedUser' => 'The user was already blocked ❗️',
                        'confirmNumber' => 'Manually confirm phone number',
                        'dataorder' => 'No date recorded',
                        'descriptionBlock' => '✍️ The reason for blocking the user was saved',
                        'failedPhone' => 'Not confirmed',
                        'getIdMessage' => '✅ The text was received. Now send the user\'s numeric ID.',
                        'getIdUserUnblock' => '👤 Send the user\'s numeric ID',
                        'getText' => 'Send your text',
                        'getTextResponse' => 'To reply to the message, send your text.',
                        'lowBalanceUser' => '👇 Decrease balance',
                        'lowBalanceUserDesc' => '⭕️ Send the amount you want to deduct',
                        'lowBalanced' => '✅ The balance was successfully deducted from the user\'s account.',
                        'manageUserBtn' => '⚙️ User management',
                        'manageUserBtnDesc' => '⭕️ In this section you can view all users 
⚠️ To manage a user, click the User Management button in front of each user',
                        'messageSent' => '✅ The message was sent',
                        'removeService' => '❌ Delete order',
                        'removeServiceAndBack' => '❌ Delete order and refund',
                        'removeagent' => '🤖 Remove agent',
                        'removedService' => '✅ The user\'s service was deleted.',
                        'sendMessageUser' => '✅ The message was successfully sent to the user.',
                        'sendPaymentList' => '✅ The user\'s payment list was sent',
                        'unbanUserList' => '🔓 Unblock user',
                        'userNotBlock' => 'The user is not blocked 😐',
                        'userUnblocked' => 'The user was unblocked. 🤩',
                        'viewOrderUser' => '🛍 View user\'s orders',
                        'viewPaymentUser' => '💰 View user\'s payments',
                        'unknownName' => 'Unknown',
                        'notVerified' => 'Not verified',
                        'verified' => 'Verified',
                        'verifiedSuccess' => '✅ The user was verified successfully.',
                        'unverifiedSuccess' => '✅ The user was successfully removed from verified status.',
                        'btnDisableAccount' => '❌ Disable account',
                        'askTransferTargetId' => 'Send the numeric ID of the user you want all the information transferred to
    Note that if the destination user has a balance, it will be deleted',
                        'transferSameUser' => '❌ You cannot transfer information to the current user',
                        'transferDone' => 'The information was successfully transferred to the new user account',
                        'alreadyVerified' => '✍️ The user is already verified',
                        'accountToggleBusy' => '❌ The bot is currently turning an account off or on; wait until the previous operation is done, then send a new request',
                        'configsQueuedEnable' => '✅ The user\'s configs have been queued for activation. Note that this may take more than 2 hours; the time depends on the number of configs.',
                        'configsQueuedDisable' => '✅ The user\'s configs have been queued for deactivation. Note that this may take more than 2 hours; the time depends on the number of configs.',
                        'notFound' => '❌ The user does not exist.',
                        'infoSummary' => '👀 User information:

🔗 User account information

⭕️ User status: %s
⭕️ User username: @%s
⭕️ User numeric ID:  <a href = "tg://user?id=%s">%s</a>
⭕️ User referral code: %s
⭕️ User join time: %s
⭕️ User\'s last bot usage time: %s
⭕️ Test account limit:  %s 
⭕️ Rule acceptance status: %s
⭕️ Mobile number: <code>%s</code>
⭕️ User type: %s
⭕️ Number of user referrals: %s
⭕  User\'s referrer: %s
⭕  Identity verification status: %s   
⭕  Card number display: %s
⭕ User points: %s
⭕️  Total active purchased volume ( for accurate volume statistics, cron must be on ): %s
%s

💎 Financial reports

🔰 User balance: %s
🔰 User\'s total purchase count: %s
🔰️ Total paid amount:  %s
🔰 Total purchases: %s
🔰 User discount percentage: %s
🔰 Sales count in the last hour: %s
🔰 Total sales in the last hour: %s Toman
🔰 Sales count in the last month: %s
🔰 Total sales in the last month: %s Toman


',
                        'notFoundById' => '📌 The user with numeric ID %s does not exist in the database',
                ],
                'manageadmin' => [
                        'addAdminSet' => '🥳 The admin was successfully added',
                        'adminAddedSendUser' => '⭕️ Dear user, you became a bot admin. To access the admin panel, you can send the command <code>panel</code>',
                        'getId' => '🌟 Send the new admin\'s numeric ID',
                        'invalidRule' => '❌ Invalid access level',
                        'setRule' => '⭕️ Send the admin access level
The administrator access level has access to all sections
The Seller access level only has access to receipt confirmation, user services, and bot statistics sections
The support access level has access to user services and support message reply sections',
                        'listAndDelete' => '📌 In the section below you can view the list of admins. You can also delete an admin by clicking the X button',
                        'askMessageAdminId' => '📌 Send the numeric ID of the admin you want messages to be sent to',
                        'cannotDeleteMain' => '❌ The main admin cannot be deleted',
                        'notFound' => '⚠️ No admin was found with this ID.',
                        'deleted' => '✅ The admin was deleted successfully',
                        'askId' => '📌 Send the admin\'s numeric ID',
                ],
                'managepanel' => [
                        'Inbound' => [
                                'endInbound' => '🥳 The inbounds were successfully registered',
                                'getInbound' => '🔰 Send the names of the inbounds you want to set for this protocol.',
                                'getPanelType' => '📌 Select your panel type',
                                'getProtocol' => '🔱 Select your protocol',
                                'invalidProtocol' => '⛔️ The protocol is invalid. The protocol must be one of the options below',
                        ],
                        'addPanelName' => 'Send the panel name
       
⚠️ Note: The panel name is the name shown during search operations.',
                        'addPanelUrl' => '🔗 The panel name was saved. Now send your panel address
    ⚠️ Note:
    🔸 The panel address must be sent without dashboard.
    🔹 If the panel port is 443, you should not enter the port. (Sometimes you must enter it with the port)
    🔸 The end of the address must not have a /
    🔹 If you enter an IP, it must have http or https',
                        'addedPanel' => 'Congratulations, your panel was successfully added',
                        'changedLimit' => 'The new limit was successfully set',
                        'changedNamePanel' => '✅ The panel name was successfully changed.',
                        'changedPasswordPanel' => '✅ The panel password was successfully changed.',
                        'changedUrlPanel' => '✅ The panel address was successfully changed.',
                        'changedUsernamePanel' => '✅ The panel username was successfully changed.',
                        'connectXUi' => '✅ The panel is connected',
                        'customNameSend' => 'Send your custom text',
                        'errorStatusPanel' => 'It is not possible to connect to the panel 😔 The error is written below. If the problem is not resolved, contact support',
                        'getLimitedPanel' => '📌 Specify the account creation limit on this panel.
⚠️ Note that the limit is based on the number of active orders in the bot 
If you want it to be unlimited, send the text unlimited',
                        'getLoc' => 'To edit the panel, send the panel name',
                        'getNameNew' => 'Send the new panel name',
                        'getPassword' => '🔑 The username was saved. Enter your password',
                        'getPasswordNew' => 'Send the new panel password',
                        'getUrlNew' => ' Send the new panel address',
                        'getUsernameNew' => ' Send the new panel username',
                        'invalidDomain' => '🔗 The domain address is invalid',
                        'invalidName' => 'The name is invalid',
                        'nullPanelAdmin' => 'No panel is defined. First define a panel, then add a product',
                        'removedPanel' => 'The panel was successfully deleted',
                        'repeatPanel' => '❌ The panel name is already registered. You cannot register it again',
                        'savedData' => '✅ The changes were successfully saved.',
                        'savedName' => '✅ The name was successfully saved',
                        'setLimit' => 'Send the new account creation limit. If you want it to be unlimited, send the text unlimited',
                        'setProtocol' => '✅ The protocol was successfully set',
                        'usernameSet' => '👤 The panel address was saved. Now send the username',
                        'noteSetInboundAndDomain' => '❌ Note:
To activate the panel, you must go to the panel management menu and be sure to configure the Set Inbound ID and Subscription Link Domain options; otherwise, the config will not be created',
                        'noteSetProtocolInbound' => '❌ Note:
To activate the panel, you must go to the panel management menu and configure the protocol and inbound options so that the bot provides the config; otherwise, no config will be given to the user',
                        'noteSetInboundId' => '❌ Note:
To activate the panel, you must go to the panel management menu, enter the Set Inbound ID menu, and configure the config name; otherwise, the bot will not create any config',
                        'noteSetGroupNameIbsng' => '❌ Note:
To activate, you must go to Panel Management > Set Group Name and send the default group name you defined in ibsng to the bot.',
                        'noteMikrotikAccounting' => '❌ Note:
1 - The accounting plugin must be installed on your MikroTik
2 - In the ip » services » http or https section, it must be enabled (if you have obtained an SSL, enable https; otherwise http)',
                        'noteSetAdminUuid' => '❌ Note:
1 - Configure the following options from panel management

1 - uuid admin: get and register the admin uuid from the panel
2 - Subscription link domain: send the subscription link domain of the Hiddify panel',
                        'noteSendConfigUsername' => '❌ Note:
1 - From Panel Management > Set ⚙️ Protocol and Inbound, send a config username.',
                        'invalidSelection' => '❌ The selected panel is wrong',
                        'hidden' => 'Hidden',
                        'shown' => 'Shown',
                        'invalidCredentials' => '❌ The panel username or password is incorrect',
                        'fetchErrorCode' => '❌ An error occurred while retrieving data. Error code: ',
                        'fetchError' => '❌ An error occurred while retrieving data. Error: ',
                        'protocolsNotConfigured' => '⚠️ Protocols and inbounds are not configured for this location. Until they are, the bot cannot build a working config. Go to panel management > protocol and inbound settings and send the username of a sample config.',
                        'panelConnection' => [
                                'timeout' => '⏳ <b>The panel did not respond within %s seconds.</b>

This is not a bot fault — the request reached your panel, but the panel did not answer in time.

🔹 Check that the panel server is up and reachable.
🔹 If your panel is under heavy load, raise <code>$request_exec_timeout</code> in <code>config.php</code> (in milliseconds, e.g. 25000).',
                                'refused' => '🚫 <b>Could not connect to the panel.</b>

This is not a bot fault — the panel server refused the connection.

🔹 Check the panel address and port.
🔹 Make sure the panel service is running and the firewall allows the port.',
                                'dns' => '🌐 <b>The panel domain could not be resolved.</b>

This is not a bot fault — the panel address did not resolve to an IP.

🔹 Check the spelling of the panel address.
🔹 Check the domain DNS records.',
                                'ssl' => '🔐 <b>Secure connection to the panel failed.</b>

This is not a bot fault — the panel SSL certificate was invalid or the handshake failed.

🔹 Check the panel SSL certificate and its expiry date.',
                                'generic' => '⚠️ <b>Could not reach the panel.</b>

This is not a bot fault — the request to the panel failed.

🔹 Check the status of the panel server.',
                                'detail' => '

<i>Technical detail:</i> <code>%s</code>',
                        ],
                        'invalidUrl' => '❌ The panel link was sent incorrectly',
                        'notConnected' => 'Panel is not connected',
                        'askUserGroup' => '📌 Send the user type
User groups: f,n,n2
❌ If you want the panel to be shown for all user groups, send the text all',
                        'userGroupChanged' => '📌 User group changed successfully',
                        'btnSubLinkDomain' => '🔗 Subscription link domain',
                        'askSubLinkSample' => '📌 If you use a Sanaei panel, copy a user\'s subscription link from the panel and send it in this section. For other panels, you must send it according to their structure.',
                        'subLinkInactive' => 'The subscription link is not active',
                        'subLinkInvalid' => 'The subscription link is invalid',
                        'askAdminUuid' => '📌 Send the admin UUID',
                        'adminUuidSaved' => '✅ Admin UUID saved',
                        'askInboundId' => '📌 Send the inbound ID from which you want the config to be created. The inbound ID is a multi-digit number written in the id column on the inbounds page of the panel.

⚠️ If you use a wgdashboard panel, you must send the config name',
                        'inboundIdSaved' => '✅ Inbound ID saved successfully',
                        'userNotInPanel' => 'The user does not exist in the panel',
                        'inboundNameSaved' => 'Inbound name saved successfully',
                        'selectPanel' => '🪚 To use this feature, select one of the panels below',
                        'askServiceSetup' => '📌 To set up the service, create a config in your panel, activate the services you want to be active inside the panel, and send the config\'s username',
                        'setupSaved' => '✅ The information was set up successfully',
                        'askQrBackground' => 'Send your image for the background',
                        'invalidImage' => 'The image is invalid',
                        'qrBackgroundSaved' => '🖼 The background was set successfully',
                        'btnSetGroupName' => '🎛 Set group name',
                        'askGroupName' => '📌 Send the group name you want to be used by default.',
                        'askProtocolSetup' => '📌 To set up the inbound and protocol, you must create a config in your panel, activate the protocols and inbounds you want to be active inside the panel, and send the config\'s username',
                        'userNotInPanel2' => '❌ The user does not exist in the panel.',
                        'groupNameSaved' => '✅ The group name was set successfully.',
                        'protocolSaved' => '✅ Your inbounds and protocols were set up successfully.',
                        'askHideUserId' => '📌 Send the numeric ID of the user for this panel.',
                        'hiddenForUser' => '✅ The panel was successfully hidden for the user',
                        'hiddenListEmpty' => '❌ There is no user in the hidden list',
                        'userNotInList' => '❌ The user is not in the list',
                        'userNotInList2' => '❌ The user is not in the list.',
                        'userRemovedFromList' => '✅ The user was successfully removed from the list.',
                        'askConfigUsername' => '📌 If you use a Marzban or Marzneshin panel, copy a config\'s username from the panel and send it; otherwise, for Sanaei and Alireza panels, send the inbound ID',
                        'inboundUnsupported' => '❌ This panel does not support defining an inbound',
                        'askHidePanelsAgent' => '❌ Select the panels you don\'t want to be shown to this agent from the button below; after selecting, send the /finish command to save.',
                        'panelsHidden' => '✅ The panels were saved successfully and the panels were hidden for the user.',
                        'alreadyAdded' => '❌ The panel has already been added',
                        'panelSelectedFinish' => '✅ The panel was selected; when finished, send the /finish command to save it finally.',
                        'askShowPanelsAgent' => '❌ From the list below, select the panels you want to be shown again in the agent\'s bot; after selecting all panels, send the /remove command to save.',
                        'panelsShown' => '✅ The panels were shown successfully and the panels were activated for the user.',
                        'panelNotInList' => '❌ The panel is not in the list',
                        'panelSelectedRemove' => '✅ The panel was selected; when finished, send the /remove command to save it finally.',
                        'hidePanelAllOnly' => '📌 This feature only works when you have defined the product location as /all.',
                        'askHidePanelProduct' => '📌 If you have selected the panel location as /all but need to not show a panel, you can use this feature

To hide a panel, select your panels from the list below, then send the /end_hide command.',
                        'panelsHiddenProduct' => '✅ The panels were saved successfully and the panels were hidden for the selected product.',
                        'panelSelectedEndHide' => '✅ The panel was selected; when finished, send the /end_hide command to save it finally.',
                        'hiddenPanelsCleared' => '✅ All hidden panels were removed',
                        'invalidToken' => '❌ Panel token is invalid',
                        'notFound' => '❌ The requested panel was not found.',
                        'errorCode' => '❌ An error occurred. Error code: %s',
                        'xuiErrorCode' => '❌ An error occurred. Error code:  ',
                        'eylanErrorCode' => '❌  An error occurred. Error code:  %s',
                        'eylanUserNotExist' => '❌ User does not exist in the panel.',
                        'eylanPanelOutput' => 'Panel output: ',
                ],
                'messageBulk' => [
                        'userMessage' => '📥 A reply to a message was received from the user. To reply, click the button below and send your message.

Numeric ID: %s
User\'s username: @%s
📝 Message text: %s',
                        'userResponse' => '📥 A reply to a message was received from the user. To reply, click the button below and send your message.

Numeric ID: %s
User\'s username: %s
📝 Message text: %s',
                        'busy' => '❌ The message-sending system is currently performing an operation. After it finishes and notifies you, you can send a new message.',
                        'btnCancelPin' => 'Cancel pinned message',
                        'confirmStart' => 'By confirming the option above, the sending process will begin',
                        'errorRestart' => '❌ An error occurred. Please perform the message-sending steps again from the beginning',
                        'askPanelUsers' => '📌 Which users in the panels below should the message be sent to?',
                        'askPin' => '📌 Do you want the sent message to be pinned or not?',
                        'askInactiveDays' => '📌 In this feature, the message is sent to users who, as you specify, have not used the bot for a certain number of days
Send your number of days.',
                        'askText' => '📌 Send your message text.',
                        'askButton' => '📌 If you want a button to be shown under the message, choose an option from the list below; otherwise, click the Send Without Button button',
                        'textOnlyInactive' => '📌 In the section for users who have not used the bot for the specified number of days, only sending text is possible.',
                        'textOnlyBroadcast' => '📌 In the broadcast section, only sending text is possible.',
                        'targetInactiveUsers' => 'Users who have not used it for the specified number of days',
                        'targetAllUsers' => 'Send to all users',
                        'targetCustomers' => 'Customers',
                        'targetNoPurchase' => 'Those who had no purchase',
                        'started' => '✅ The operation has begun. You will be notified when it finishes.',
                        'canceled' => '📌 Message sending was canceled.',
                        'askTextOrImage' => '📌 Send your text or image',
                        'askAllowReply' => '📌 Can the user reply or not?
1 - Yes, they can reply 
2 - No, they cannot reply
Send the answer as a number',
                        'btnForwardToUser' => '📤 Forward message to a user',
                        'confirmSummary' => '📌 You are performing a message-sending operation; by reviewing the information below and confirming the button below, the sending operation will start.
⚙️ Operation type: %s',
                        'inactiveDaysLabel' => 'Number of days the user has not messaged: %s',
                        'confirmSummary2' => '📌 You are performing a message-sending operation; by reviewing the information below and confirming the button below, the sending operation will start.
⚙️ Operation type: %s
🎛 Service type: %s
🗂 User type: %s
%s
',
                        'answeredByOther' => '❌ The message has been answered by another admin.',
                        'done' => '📌 The operation was performed for all requested users.',
                        'progress' => '✏️ The message-sending operation is in progress...

Number of remaining people :  %s',
                ],
                'node' => [
                        'settingsUnavailable' => '❌ Node settings cannot be viewed',
                        'intro' => '📌 In this section you can manage the Marzban panel nodes.',
                        'askMultiplier' => '📌 Send your node\'s usage coefficient.',
                        'multiplierSaved' => '✅ Node usage coefficient saved successfully.',
                        'askName' => '📌 Send your node\'s name.',
                        'nameSaved' => '✅ Node name saved successfully.',
                        'askIp' => '📌 Send the node\'s IP.',
                        'ipSaved' => '✅ Node address saved successfully.',
                        'reconnected' => '✅ Node reconnection completed.',
                        'deleted' => '✅ Node deleted successfully',
                        'btnSettings' => '⚙️ Node settings',
                        'askSetup' => '📌 To set up a node, create a user in your panel, activate the nodes you want to be active inside the panel, and send the user\'s username',
                        'info' => '📌 Node information 

🖥 Node name:  %s
🌍 Node IP: %s
🔻 Node port: %s
🔺 Node api port: %s
🔋Total node consumption: %s
🔄 Node consumption coefficient: %s
🔵 Node xray version: %s
🟢 Node status: %s
    
',
                ],
                'order' => [
                        'requestRegistered' => '✅ Successfully registered',
                        'askRejectReason' => '📌 The deletion-rejection request was successfully registered. Send the reason for non-approval',
                        'notFound' => '❌ No order was found',
                        'viewOrderUsername' => '👁 To view the user\'s orders, send the user\'s config username',
                        'alreadyDeleted' => '❌ The service has already been deleted',
                        'selectService' => '⚠️ More than one service found; select the correct service from the list below',
                        'typeAdminRenew' => 'Renewed by admin',
                        'typeExtraVolume' => 'Extra volume',
                        'typeExtraTime' => 'Extra time',
                        'typeTransfer' => 'Transfer to another account',
                        'typeRenewNotInList' => 'Renewal due to user not being in the list',
                        'typeChangeLocation' => 'Change location',
                        'typeGiftTime' => 'Universal time gift',
                        'typeGiftVolume' => 'Universal volume gift',
                        'askRefundAmount' => '📌 Send the amount for the refund',
                        'renewError' => '❌ The renewal encountered an error; perform the renewal steps again.',
                        'askVolume' => '📌 Send your requested volume.',
                        'askTime' => '⌛️ Select your service duration ',
                        'renewErrorSupport' => '❌ An error occurred while renewing the service; contact support',
                        'confirmDeleteFromDb' => '📌 By confirming the option below, this service will be completely deleted from the bot\'s database and will no longer count in account statistics ( this section does not delete the service from the panel and only deletes it from the bot\'s database )',
                        'deleted' => '✅ The service was deleted successfully.',
                        'detailRow' => '
🛒 Order number:  <code>%s</code>
🛒  Order status in bot: <code>%s</code>
🙍‍♂️ User ID: <code>%s</code>
👤 Subscription username:  <code>%s</code> 
📍 Service location:  %s
🛍 Product name:  %s
💰 Service paid price: %s Toman
⚜️ Purchased service volume: %s
⏳ Purchased service time: %s 
📆 Purchase date: %s  

',
                        'serviceSummary' => '
  
 Service status: %s
        
🔋 Service volume: %s
📥 Consumed volume: %s
💢 Remaining volume: %s (%s%%)

📅 Active until: %s (%s)

User subscription link: 
<code>%s</code>

📶 Last connection time: %s
🔄 Last subscription link update time: %s
#️⃣ Connected client:<code>%s</code>',
                        'serviceReport' => '
📌 Service report 
🔗  Service type: %s
🕰 Service execution time: %s 

(%s)
💰Service execution amount: %s
👤 User numeric ID: %s
👤 Config username: %s',
                        'detailRow3' => '
🛒 Order number:  %s
🛒  Order status in bot: %s
🙍‍♂️ User ID: %s
👤 Subscription username:  %s
📍 Service location:  %s
🛍 Product name:  %s
💰 Service paid price: %s Toman
⚜️ Purchased service volume: %s
⏳ Purchased service time: %s 
📆 Purchase date: %s  

',
                ],
                'phone' => [
                        'active' => 'The user\'s mobile number has been confirmed ✅🎉',
                ],
                'price' => [
                        'priceSaved' => '✅ The amount was saved successfully.',
                        'selectUserGroup' => '📌 For which user type should the price be set?
User types:
f = regular user
n = regular agent
n2 = agent with more capabilities',
                        'askRenewCashback' => '📌 Send the percentage you want to be credited to the user\'s account as a gift after renewal.
⚠️ If you want it disabled, send the number 0',
                        'askUserGroup' => '📌 Select the user type
f
n
n2',
                        'invalidUserGroup' => '❌ The user group is invalid',
                        'amountSaved' => '✅ The amount was set successfully',
                        'askPaymentCashback' => '📌 In this section you can set what percentage is credited to the user\'s account as a gift after payment. (To disable this feature, send zero)',
                        'askChangeLocation' => '📌 Send the price for changing location from other panels to this panel',
                        'changeLocationSaved' => '📌 The location change price was changed successfully',
                        'askExtraVolume' => '📌 Send the price of extra volume for this panel.',
                        'allGroupsHint' => '⚠️ If you want the price to be set for all user groups, send the text <code>all</code>',
                        'askCustomVolume' => '📌 Send the custom extra volume price for this panel.',
                        'askExtraTime' => '📌 Send the extra time price for this panel.',
                        'askCustomTime' => '📌 Send the custom time price for this panel.',
                        'askIncreasePanel' => '📌 Which panel\'s products do you want to increase the price for?
If you used /all when defining the product, you must send /all if you want this category to have a price change',
                        'askPriceGroup' => '📌 For which user group should the price apply
f,n.n2',
                        'askPercentOrFixed' => '📌 Should the amount be added as a percentage or a fixed amount?',
                        'askAmount' => '📌 Send the amount you want to apply',
                        'askPercent' => '📌 Send the percentage you want to apply',
                        'noProductFound' => '❌ No product found for price change',
                        'appliedToAll' => '✅ The amount was successfully applied to all products',
                        'askDecreasePanel' => '📌 Which panel\'s products do you want to decrease the price for?
If you used /all when defining the product, you must send /all if you want this category to have a price change',
                        'askMinVolume' => '📌 Send the minimum volume the user can purchase for this panel.',
                        'askMaxVolume' => '📌 Send the maximum volume the user can purchase for this panel.',
                        'askMinTime' => '📌 Send the minimum custom time the user can purchase for this panel.',
                        'askMaxTime' => '📌 Send the maximum custom time the user can purchase for this panel.',
                        'rewardSaved' => '✅ The reward amount was set successfully',
                        'askAgentVolume' => '📌 Set the minimum price you want the agent to pay per gigabyte of volume',
                        'saved' => '✅ The price was saved successfully.',
                        'askAgentTime' => '📌 Set the minimum price you want the agent to pay per day of time',
                        'askPaymentCashback2' => '📌 In this section you can set what percentage is credited to the user\'s account as a gift after payment. ( To disable this feature, send zero )',
                        'customServiceIntro' => '📌 Before submitting the information, read the text below. 
1 - This feature is for the custom service.
2 - If all your panels have the same price, instead of setting each price individually you can use this feature to set the prices all at once.
3 - Setting the price in this section is irreversible.


To set the price, first send the price for group f.',
                        'askGroupN' => '📌 Send the price for group n.',
                        'askGroupN2' => '📌 Send the price for group n2.',
                        'savedGroup' => '✅ The price was set successfully',
                        'askBulkMin' => '📌 Send the minimum amount you want the user to make a bulk purchase.
        
Current amount: %s',
                ],
                'report' => [
                        'reportCron' => '📝 Notification reports',
                        'reportNight' => '🌙 Nightly report',
                        'btnDontShowAgain' => 'Don\'t show again ⛓️‍💥',
                        'msgHidden' => '

✅ This message will no longer be shown to you.',
                        'btnPurchaseReports' => '🛍 Purchase reports',
                        'btnServicePurchase' => '📌 Service purchase report',
                        'btnTestAccount' => '🔑 Test account report',
                        'btnOther' => '⚙️ Other reports',
                        'btnErrors' => '❌ Error reports',
                        'btnFinancial' => '💰 Financial report',
                        'btnBackup' => '🤖 Bot backup ',
                        'btnExport' => '🪪 Export data',
                        'noDataToExport' => '❌ There is no data to export',
                        'btnExportUsers' => '🪪 Export user data',
                        'btnExportOrders' => '🪪 Export user orders',
                        'btnExportPayments' => '🪪 Export user payments',
                        'btnOptimize' => '🗑 Optimize bot',
                        'optimizeWarning' => '❌❌❌❌❌❌❌ Read the text below carefully

📌 By confirming the option below, the following operations will be performed and they are irreversible

1 - Inactive orders will be deleted
2 - Unpaid orders will be deleted.
3 - Orders deleted by the admin 
4- Deletion of inactive test services
5 - Orders deleted by the user 
6 - Orders whose time or volume has expired',
                        'botReportIntro' => '💬 | Bot report

🔹 | If you encounter a <b>bug or problem</b> in the bot\'s operation, please report it to us for review.
➖➖➖➖➖➖➖➖➖➖➖
🔹 | If you encounter a <b>serious bug</b> or abnormal behavior, report it faster so it can be fixed.
➖➖➖➖➖➖➖➖➖➖➖
🔹 | If you have a suggestion for <b>adding a new feature</b> or an idea to improve the bot\'s performance, we\'d be happy to hear it.
➖➖➖➖➖➖➖➖➖➖➖
🔹 | Also, if you need <b>guidance</b> or help, you can contact the support team via direct message.

📩 | To send a report, suggestion, or request for guidance, leave a message in the <b>Mirza group</b>:
<a href="https://t.me/mirzapanelgroup" rel="nofollow" target="_blank">Mirza Group</a>',
                        'aboutBot' => '💎 | Version Bot: %s
📌 | Version Mini App: 0.1.1

<blockquote>🔹 | This bot is completely free and is developed by the Mirza team</blockquote>

<blockquote>🔹 | Any sale or charging of money for this bot is considered a violation.</blockquote>

<blockquote>🔹 | If you see any sale or charging of money, please track and reclaim your money.</blockquote>

<blockquote>🐞 | If you encounter a bug or problem in the bot\'s operation, contact us via the **📬 Bot report** button in the admin panel.</blockquote>',
                        'gatewayRow' => '
📌 Gateway name: <code>%s</code>
 - Number of successful payments: <code>%s</code>
 - Total payments: <code>%s</code>
',
                        'optimizeResult' => '
✅ %s unpaid orders were deleted
✅ %s inactive orders were deleted.
✅ %s admin-deleted orders were deleted
✅ %s test orders were deleted.',
                        'backupCaption' => '📌 Main bot database export ',
                        'dailyBot' => '📌 Daily bot performance report :

🧲 Number of renewals today : %s
💰 Total renewals today : %s Toman
🛍 Number of orders today : %s
🛍 Total order amount today : %s Toman
🔑 Test accounts today : %s
🔋 Total volume sold : %s gigabytes
Number of users who joined the bot today : %s people

',
                        'dailyPanelRow' => '
Panel name : %s
🛍 Number of orders today : %s
🛍 Total order amount today : %s Toman
🔋 Total volume sold : %s gigabytes
---------------

',
                        'dailyPanelsTitle' => 'Panels report :

',
                        'dailyTopAgentRow' => '
User numeric ID : %s
User username : %s
Total purchases today : %s
---------------

',
                        'dailyTopAgentsTitle' => 'List of agents who made the most purchases today :

',
                        'lotteryTitle' => '📌 Dear admin, the users below won the lottery and their accounts were charged.

',
                        'lotteryWinnerRow' => '
Username : @%s
Numeric ID : %s
Amount : %s
Person : %s
--------------',
                        'nodeDown' => '🚨 Dear admin, the node named %s is not connected.
Node status : %s
✍️ Error reason : <code> %s</code>',
                        'panelDown' => '🚨 Dear admin, the panel named <code>%s</code> is not connected.',
                ],
                'reportgroup' => [
                        'newUser' => '🎉 A new user started the bot
 Name: %s
Username: @%s
Numeric ID: %s',
                        'adminAdded' => '👨‍💼 A user with the following details added an admin.

Username %s
Numeric ID: %s
Access type: %s

⭕️ New admin\'s information:
Numeric ID: %s',
                        'newPaymentStar' => '💵 New payment
- 👤 User\'s username: @%s
- 🆔 User\'s numeric ID: %s
- 💸 Transaction amount %s
- 📥 Stars amount deposited: %s
- 💳 Payment method: Telegram Stars',
                        'renewalDetails' => '📣 Account renewal details were registered in your bot.
▫️ User\'s numeric ID: <code>%s</code>
▫️ User\'s username: @%s
▫️ Config username: %s
▫️ User name: %s
▫️ Service location: %s
▫️ Product name: %s
▫️ Product volume: %s
▫️ Product time: %s
▫️ Renewal amount: %s Toman
▫️ User balance: %s Toman
▫️ Purchase time: %s',
                        'volumePurchase' => '⭕️ A user purchased extra volume

User information:
🪪 Numeric ID: %s
🛍 Purchased volume: %s
💰 Amount paid: %s Toman
User\'s balance before purchase: %s
👤 Config username: %s',
                        'checkReportGroup' => '❌ An error occurred while creating the subscription; to fix the issue, check the cause of the error in your report group',
                        'paymentApproved' => '📣 An admin approved the payment receipt.
        
Information:
💸 Payment method: %s
👤 Numeric ID of approving admin: %s
💰 Payment amount: %s
👤 User numeric ID: <code>%s</code>
👤 User username: @%s 
        Payment tracking code: %s',
                        'paymentRejected' => '❌ An admin rejected the payment receipt.
        
Information:
💸 Payment method: %s
👤 Numeric ID of approving admin: %s
Username of approving admin: @%s
💰 Payment amount: %s
Rejection reason: %s
👤 User numeric ID: %s',
                        'balanceDecreased' => '📌 An admin has reduced a user\'s balance:
        
🪪 Information of the admin who reduced the balance: 
Username:@%s
Numeric ID: %s
👤 User information:
User numeric ID: %s
Balance amount: %s
User balance after reduction: %s',
                        'balanceIncreased' => '📌 An admin has increased a user\'s balance:
        
🪪 Information of the admin who increased the balance: 
Username:@%s
Numeric ID: %s
👤 Information of the user receiving the balance:
User numeric ID: %s
Balance amount: %s
User balance after increase: %s',
                        'balanceDecreased2' => '📌 An admin has reduced a user\'s balance:
        
🪪 Information of the admin who reduced the balance: 
Username:@%s
Numeric ID: %s
👤 User information:
User numeric ID: %s
Balance amount: %s
User balance after reduction: %s',
                        'userBlocked' => 'User with numeric ID
%s  was blocked in the bot 
Blocking admin: %s',
                        'userUnblocked' => 'User with numeric ID
%s  was unblocked in the bot 
Blocking admin: %s',
                        'balanceManualAdd' => 'Confirming a card-to-card receipt and manually increasing the balance by an admin
        
User numeric ID: %s
User username: %s
Transaction amount in invoice:  %s
Transaction amount deposited by admin: %s',
                        'deleteRequestApproved' => '⭕️ An admin approved the user\'s service that had a deletion request
        
Information of the approving user: 

🪪 Numeric ID: <code>%s</code>
💰 Refunded amount: %s Toman
👤 Username: %s
        Numeric ID of the cancellation requester: %s',
                        'deleteRequestApproved2' => '⭕️ An admin approved the user\'s service that had a deletion request
        
Information of the approving user: 

🪪 Numeric ID: <code>%s</code>
💰 Refunded amount: %s Toman
👤 Username: %s
Numeric ID of the cancellation requester: %s',
                        'errorConfigCreateAdmin' => '
Error creating config from the admin panel
✍️ Error reason: 
%s
Admin ID: %s
Panel name: %s',
                        'errorAccountCreate' => '
⭕️ A user attempted to receive an account, but config creation encountered an error and the config was not given to the user
✍️ Error reason: 
%s
User ID: %s
User username: @%s
Panel name: %s',
                        'configCreatedByAdmin' => ' 🛍 Config creation by admin 

Config username: %s
Config volume: %s GB
Config time: %s days
Admin numeric ID: %s
Admin username: %s
Number created: %s',
                        'errorRenewServiceAdmin' => '
        Service renewal error
Panel name: %s
Service username: %s
Error reason: %s',
                        'renewedByAdmin' => '⭕️ The admin renewed the user\'s service.
        
User information: 
        
🪪 Admin numeric ID: <code>%s</code>
🪪 Numeric ID: <code>%s</code>
🛍 Product name:  %s
👤 Customer username in panel  : %s
User service location: %s',
                        'discountUsedRenew' => '⭕️ A user with username @{username}  and numeric ID {from_id} used discount code {discount_code}. And renewed their service.',
                        'discountUsed' => '⭕️ A user with username @{username}  and numeric ID {from_id} used discount code {discount_code}.',
                        'accountCreated' => '📣 Account creation details were registered in your bot .

%s
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s
▫️User name : %s
▫️Service location : %s
▫️Product name :%s
▫️Purchased time :%s days
▫️Purchased volume : %s GB
▫️Balance before purchase : %s Toman
▫️Balance after purchase : %s Toman
▫️Tracking code: %s
▫️User type : %s
▫️User phone number : %s
▫️Product category : %s
▫️Product price : %s Toman
▫️Final price : %s Toman
▫️Purchase time : %s',
                        'accountCreatedAfterPay' => '📣 Account creation details were registered in the bot after payment .

%s
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s
▫️Service location : %s
▫️Purchased time :%s days
▫️Purchased product name :%s
▫️Purchased volume : %s GB
▫️Balance before purchase : %s Toman
▫️Balance after purchase : %s Toman
▫️Tracking code: %s
▫️User type : %s
▫️User phone number : %s
▫️Product price : %s Toman
▫️Final price : %s Toman
▫️Purchase time : %s',
                        'accountCreatedMiniapp' => '📣 Account creation details were registered in the mini app .
        
%s
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s
▫️Service location : %s
▫️Product name :%s
▫️Purchased time :%s days
▫️Purchased volume : %s GB
▫️Balance before purchase : %s Toman
▫️Balance after purchase : %s Toman
▫️Tracking code: %s
▫️User type : %s
▫️User phone number : %s
▫️Product category : %s
▫️Product price : %s Toman
▫️Purchase time : %s',
                        'userDeletedService' => 'Dear admin, a user has deleted their service after its volume or time ended
Config username : %s',
                        'commissionPaid' => '
An amount of %s was credited to user %s as commission from user %s 
Time : %s',
                        'commissionPaid2' => '
An amount of %s was credited to user %s as commission from user %s 
Time : %s',
                        'commissionPaidFn' => '
An amount of %s was credited to user %s as commission from user %s 
Time : %s',
                        'commissionPaidFn2' => '
An amount of %s was credited to user %s as commission from user %s 
Time : %s',
                        'commissionPaidMiniapp' => '
    An amount of %s was credited to user %s as commission from user %s 
    Time : %s',
                        'commissionPaidMiniapp2' => '
An amount of %s was credited to user %s as commission from user %s 
Time : %s',
                        'agentExpiredGroupChanged' => '📌 The user\'s user group was changed to f due to agency expiry

User numeric ID :  %s
User username :‌ %s',
                        'errorAqayePardakhtLink' => '⭕️ Error creating Aghaye Pardakht link
✍️ Error reason : %s
            
User ID : %s
User username : @%s',
                        'errorBulkAccountCreate' => '
⭕️ Error creating account in the bulk section
✍️ Error reason : 
%s
User ID : %s
User username : @%s
Panel name : %s',
                        'bulkAccountCreated' => '📣 Bulk account creation details were registered in your bot .
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s_0-%s
▫️User name : %s
▫️Service location : %s
▫️Product name :%s
▫️Purchased time :%s days
▫️Purchased volume : %s GB
▫️Balance before purchase : %s Toman
▫️Balance after purchase : %s Toman
▫️Tracking code: %s
▫️User type : %s
▫️User phone number : %s
▫️Product price : %s Toman
▫️Final price : %s Toman
▫️Number of configs : %s
▫️Purchase time : %s',
                        'linkChanged' => '📣 Link change details were registered in your bot .
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s
▫️User name : %s
▫️Service location : %s
▫️User type : %s
▫️Link change time : %s',
                        'errorChangeLocation' => 'Error while changing the service location
Error reason : 
%s
User ID : %s
User username : @%s
Panel name : %s
Destination panel name : %s',
                        'locationChanged' => '  
Service location change 

🔻Numeric ID : <code>%s</code>
🔻Username : @%s
🔻Old panel name : %s
🔻New panel name : %s
🔻 Customer username in panel  :%s
🔻Final service volume : %s
🔻User balance : %s Toman',
                        'errorConfigCreate' => '
⭕️ Error creating config
✍️ Error reason : 
%s
User ID : %s
User username : @%s
Panel name : %s',
                        'errorConfigCreatePanel' => '
Error creating config from the admin panel
✍️ Error reason: 
%s
Admin ID: %s
Panel name: %s',
                        'errorCryptoLink' => '
                        ⭕️ A user intended to pay with the currency gateway, but creating the payment link encountered an error and no link was given to the user
✍️ Error reason : %s
            
User ID : %s
User username : @%s',
                        'errorCryptoLink2' => '
                        ⭕️ A user intended to pay with the currency gateway, but creating the payment link encountered an error and no link was given to the user
✍️ Error reason : %s
            
User ID : %s
User username : @%s',
                        'deleteServiceRequest' => 'Hello admin 👋
        
📌 A service deletion request was sent to you by a user. Please review it and, if correct and you agree, approve it. 
        
        
📊 User service information :
User numeric ID : %s
User username : @%s
Config username : %s
Service status : %s
Service location : %s
Service code:%s

🟢 Your last connection time : %s

📥 Consumed volume : %s
♾ Service volume : %s
🪫 Remaining volume : %s
📅 Active until date : %s (%s)


<b>❌ Dear admin, note that the delete service button you press is calculated automatically by the bot and there is a chance of error; it is recommended to use manual deletion</b>

Service deletion reason : %s',
                        'discountCodeUsed' => '⭕️ A user with username @%s  and numeric ID %s used discount code %s.',
                        'discountCodeUsedFn' => '⭕️ A user with username @%s  and numeric ID %s used discount code %s.',
                        'disruption' => '
    ⚠️ A user with the following information has submitted a service outage report .

- Username : @%s
- Numeric ID : %s
- Config username : %s
- Purchased plan name : %s
- Service location : %s
- Outage description : %s',
                        'errorExtraTime' => 'Error purchasing extra volume
Panel name : %s
Service username : %s
Error reason : %s',
                        'errorExtraTimeFn' => 'Error purchasing extra volume
Panel name : %s
Service username : %s
Error reason : %s',
                        'extraTime' => '⭕️ A user purchased extra time
        
User information : 
🪪 Numeric ID : %s
🛍 Purchased time  : %s days
💰 Paid amount : %s Toman
👤 Config username : %s',
                        'extraTimeFn' => '⭕️ A user purchased extra time
        
User information : 
🪪 Numeric ID : %s
🛍 Purchased time  : %s days
💰 Paid amount : %s Toman
👤 Config username %s',
                        'errorExtraVolume' => 'Error purchasing extra volume
Panel name : %s
Service username : %s
Error reason : %s',
                        'errorExtraVolume2' => 'Error purchasing extra volume
Panel name : %s
Service username : %s
Error reason : %s',
                        'errorExtraVolumeFn' => 'Error purchasing extra volume
Panel name : %s
Service username : %s
Error reason : %s',
                        'extraVolume' => '⭕️ A user purchased extra volume
        
User information : 
🪪 Numeric ID : %s
🛍 Purchased volume  : %s GB
💰 Paid amount : %s Toman
👤 Config username : %s
User balance before purchase : %s

',
                        'extraVolumeFn' => '⭕️ A user purchased extra volume
        
User information : 
🪪 Numeric ID : %s
🛍 Purchased volume  : %s GB
💰 Paid amount : %s Toman
👤 Config username %s
User balance before purchase : %s

',
                        'newPaymentIranpay' => '💵 New payment
- 👤 User username : @%s
- 🆔User numeric ID : %s
- 💸 Transaction amount %s
- 💳 Payment method :  Third Rial currency',
                        'membershipGiftPaid' => '🎁 Membership gift payment
 -Numeric ID : %s
 - Username : @%s
 - Referrer numeric ID : %s
 - Referral balance before gift : %s
 - Referral balance after gift : %s
  - Referrer balance before gift : %s
 - Referrer balance after gift : %s
 ',
                        'newPayment' => '💵 New payment
                
User numeric ID : %s
Transaction amount : %s 
Payment method : First Rial currency gateway',
                        'newPaymentAutoConfirm' => '💵 New payment
        
User numeric ID : %s
Transaction amount %s
Payment method :  Automatic approval without review
%s',
                        'newPaymentBalance' => '
⭕️ A new payment has been made .
Balance increase            
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💵 User\'s total payments : %s
💸 Paid amount: %s Toman
                
Description: %s %s
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentBalance2' => '
⭕️ A new payment has been made .
Balance increase            
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💸 Paid amount: %s Toman
                
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentBalanceFn' => '⭕️ A new payment has been made
        Balance increase.
👤 User ID: <code>%s</code>
🛒 Payment tracking code: %s
⚜️ Username: @%s
💸 Paid amount: %s Toman
💎 Balance before increase : %s
✍️ Description : %s',
                        'newPaymentExtraTime' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
Extra time purchase
Service username : %s
Number of days purchased  : %s
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💵 User\'s total payments : %s
💸 Paid amount: %s Toman
                
Description: %s %s
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentExtraTime2' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
Extra time purchase
Service username : %s
Number of days purchased  : %s
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💸 Paid amount: %s Toman
                
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentExtraVolume' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
Extra volume purchase
Service username : %s
Purchased volume  : %s
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💵 User\'s total payments : %s
💸 Paid amount: %s Toman
                
Description: %s %s
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentExtraVolume2' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
Extra volume purchase
Service username : %s
Purchased volume  : %s
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💸 Paid amount: %s Toman
                
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentService' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
New service purchase

Service username : %s
Product name : %s
Product volume : %s GB 
Product time : %s days
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💵 User\'s total payments : %s
💸 Paid amount: %s Toman
                
Description: %s %s
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentService2' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
New service purchase

Service username : %s
Product name : %s
Product volume : %s GB 
Product time : %s days
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💸 Paid amount: %s Toman
                
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentRenew' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
Renewal
Service username : %s
Product name : %s
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💵 User\'s total payments : %s
💸 Paid amount: %s Toman
                
Description: %s %s
✍️ If the receipt is correct, approve the payment.',
                        'newPaymentRenew2' => '
⭕️ A new payment has been made .

⭕️⭕️⭕️⭕️⭕️
Renewal
Service username : %s
Product name : %s
👤 User account name : %s
👤 User ID:  <a href = "tg://user?id=%s">%s</a>
💸 User current balance : %s Toman
🛒 Payment tracking code: %s
⚜️ Username: @%s
💸 Paid amount: %s Toman
                
✍️ If the receipt is correct, approve the payment.',
                        'paymentConfirmedExtraTime' => '✅ Payment approved
🔋 Extra time purchase
🛍 Purchased time  : %s days
👤 Config username %s
👤 User ID: <code>%s</code>
🛒 Payment tracking code: %s
⚜️ Username: @%s
💎 Balance before increase : %s
💸 Paid amount: %s Toman

',
                        'paymentConfirmedExtraVolume' => '✅ Payment approved
🔋 Extra volume purchase
🛍 Purchased volume  : %s GB
👤 Config username %s
👤 User ID: <code>%s</code>
🛒 Payment tracking code: %s
⚜️ Username: @%s
💎 Balance before increase : %s
💸 Paid amount: %s Toman

',
                        'paymentConfirmedService' => '✅ Payment approved
 🛍Service purchase 
 ▫️Config username :%s
▫️Service location : %s
👤 User ID: <code>%s</code>
🛒 Payment tracking code: %s
⚜️ Username: @%s
💎 Balance before purchase  : %s
💸 Paid amount: %s Toman
✍️ Description : %s


',
                        'paymentConfirmedRenew' => '✅ Payment approved
🔋 Service renewal
🪪 Config username : %s
🛍 Product name : %s
🌏 Location name : %s
👤 User ID: <code>%s</code>
🛒 Payment tracking code: %s
⚜️ Username: @%s
💎 Balance before renewal  : %s
💸 Paid amount: %s Toman
✍️ Description : %s


',
                        'errorPaymentLink' => '
⭕️ A user intended to pay, but creating the payment link encountered an error and no link was given to the user
✍️ Error reason : %s

User ID : %s
Payment method : %s
User username : @%s',
                        'errorPaymentLink2' => '
                        ⭕️ A user intended to pay, but creating the payment link encountered an error and no link was given to the user
✍️ Error reason : %s
            
User ID : %s
Payment method : %s
User username : @%s',
                        'errorPaymentLink3' => '
⭕️ A user intended to pay, but creating the payment link encountered an error and no link was given to the user
✍️ Error reason : %s
            
User ID : %s
Payment method : %s
User username : @%s',
                        'newPaymentPlisio' => '💵 New payment
- 👤 User username : @%s
- 🆔User numeric ID : %s
- 💸 Transaction amount %s
- 🔗 <a href = "%s">Payment link </a>
- 🔗 <a href = "%s">plisio payment link </a>
- 📥 Deposited Tron amount. : %s
- 💳 Payment method :  plisio',
                        'renewed' => '📣 Account renewal details were registered in your bot .
    
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s
▫️User name : %s
▫️Service location : %s
▫️Product name : %s
▫️Product volume : %s
▫️Product time : %s
▫️Renewal amount : %s Toman
▫️Balance before purchase : %s Toman
▫️Balance after purchase : %s Toman
▫️Purchase time : %s',
                        'renewedFn' => '📣 Account renewal details were registered in your bot .
    
▫️User numeric ID : <code>%s</code>
▫️User username : @%s
▫️Config username :%s
▫️Service location : %s
▫️Product name : %s
▫️Product volume : %s
▫️Product time : %s
▫️Renewal amount : %s Toman
▫️Balance before purchase : %s Toman
▫️Purchase time : %s',
                        'errorRenewService' => 'Service renewal error
Panel name : %s
Service username : %s
Error reason : %s',
                        'errorRenewService2' => 'Service renewal error
        Panel name : %s
        Service username : %s
        Error reason : %s',
                        'errorRenewServiceApi' => '
        Service renewal error
Panel name: %s
Service username: %s
Error reason: %s',
                        'errorRenewServiceFn' => '
        Service renewal error
Panel name: %s
Service username: %s
Error reason: %s',
                        'noteChanged' => '📌  A user changed their service note.

▫️ Service username : %s
▫️ Previous note :‌ %s
▫️ New note :‌  %s

Note change time : %s ',
                        'errorStarInvoice' => '
Error while creating the Star invoice
✍️ Error reason : %s
            
User ID : %s
Payment method : %s
User username : @%s',
                        'errorSubscriptionCreate' => '⭕️ Subscription creation error
✍️ Error reason :
%s
User ID : %s
User username : @%s
Panel name : %s',
                        'errorSubscriptionCreateAdmin' => '⭕️ Subscription creation error 
✍️ Error reason : 
%s
User ID : %s
User username : @%s
Panel name : %s',
                        'errorSubscriptionCreateApi' => '❌ An error occurred while creating the subscription; to fix the issue, check the cause of the error in your report group',
                        'supportMessage' => '
    📣 Dear support, a message was sent to you from a user.

User numeric ID : <a href = "tg://user?id=%s">%s</a>
Send time : %s
Message status : Not answered
User username : @%s    
Department name : %s

Message text : %s %s',
                        'supportMessage2' => '
    📣 Dear support, a message was sent to you from a user.

User numeric ID : <a href = "tg://user?id=%s">%s</a>
Send time : %s
Message status : Customer reply
User username : @%s    
Department name : %s

Message text : %s',
                        'errorTestAccountCreate' => '
⭕️ A user intended to get a test account, but creating the config encountered an error and no config was given to the user
✍️ Error reason : 
%s
User ID : %s
User username : @%s
Panel name : %s',
                        'testAccountCreated' => '📣 Test account creation details were registered in your bot .
▫️User numeric ID : <code>%s</code>
▫️User username :@%s
▫️Config username :%s
▫️User name : %s
▫️Service location : %s
▫️Purchased time : %s hours
▫️Purchased volume : %s MB
▫️Tracking code: %s
▫️User type : %s
▫️User phone number : %s
▫️Purchase time : %s',
                        'userBlockedByApi' => 'User with numeric ID %s was blocked in the bot 
Performing admin : api site',
                        'userUnblockedByApi' => 'User with numeric ID %s was unblocked in the bot 
Performing admin : api site',
                        'errorZarinpalLink' => '⭕️ Error creating ZarinPal link
✍️ Error reason : %s
            
User ID : %s
User username : @%s',
                ],
                'stats' => [
                        'invalidDate' => 'The date must be valid',
                        'askDate' => 'Send the end date, for example:
<code>2025/09/08</code>',
                        'overall' => '📊 <b>Overall bot statistics</b>
━━━━━━━━━━━━━━━━━━
👥 <b>Total users:</b> <code>%s</code> people  
💳 <b>Users with purchases:</b> <code>%s</code> people  
🧪 <b>Test accounts:</b> <code>%s</code> people  
💰 <b>Total user balance:</b> <code>%s</code> Toman  

🧾 <b>Total sales count:</b> <code>%s</code>  
🧾 <b>Total sales count of active services:</b> <code>%s</code>  
💵 <b>Total sales:</b> <code>%s</code> Toman  
💵 <b>Total sales of active services:</b> <code>%s</code> Toman  
🔄 <b>Total renewals:</b> <code>%s</code> Toman  
📈 <b>Conversion rate to customer:</b> <code>%s</code>٪  
💳 <b>Average purchase per customer:</b> <code>%s</code> Toman  
📅 <b>Projected monthly revenue:</b> <code>%s</code> Toman  
📊 <b>Renewal percentage of sales:</b> <code>%s</code>٪  


👨‍💼 <b>Total agents:</b> <code>%s</code> people  
🔹 <b>Type N agents:</b> <code>%s</code> people  
🔸 <b>Type N2 agents:</b> <code>%s</code> people  
🧩 <b>Number of panels:</b> <code>%s</code>  
%s
',
                        'lastHour' => '
🕐 <b>Statistics of the last 1 hour</b>


🛍 Number of orders: %s
💸 Total order amount: %s Toman

🧲 Number of renewals: %s
💰 Total renewal amount: %s Toman

📦 Extra volumes: %s
💰 Extra volume amount: %s Toman

⏱️ Extra times: %s
💰 Extra time amount: %s Toman

📍 Location changes: %s
💰 Location change amount: %s Toman

🔑 Test accounts: %s
👤 Number of users: %s people
',
                        'yesterday' => '
🕐 <b>Statistics of the previous day</b>

⏳ Time range: %s to%s

🛍 Number of orders: %s
💸 Total order amount: %s Toman

🧲 Number of renewals: %s
💰 Total renewal amount: %s Toman

📦 Extra volumes: %s
💰 Extra volume amount: %s Toman

⏱️ Extra times: %s
💰 Extra time amount: %s Toman

📍 Location changes: %s
💰 Location change amount: %s Toman

🔑 Test accounts: %s
👤 Number of users: %s people
',
                        'today' => '
🕐 <b>Statistics of the current day</b>

⏳ Time range: %s to%s

🛍 Number of orders: %s
💸 Total order amount: %s Toman

🧲 Number of renewals: %s
💰 Total renewal amount: %s Toman

📦 Extra volumes: %s
💰 Extra volume amount: %s Toman

⏱️ Extra times: %s
💰 Extra time amount: %s Toman

📍 Location changes: %s
💰 Location change amount: %s Toman

🔑 Test accounts: %s
👤 Number of users: %s people
',
                        'lastMonth' => '
🕐 <b>Statistics of the previous month</b>

⏳ Time range: %s to%s

🛍 Number of orders: %s
💸 Total order amount: %s Toman

🧲 Number of renewals: %s
💰 Total renewal amount: %s Toman

📦 Extra volumes: %s
💰 Extra volume amount: %s Toman

⏱️ Extra times: %s
💰 Extra time amount: %s Toman

📍 Location changes: %s
💰 Location change amount: %s Toman

🔑 Test accounts: %s
👤 Number of users: %s people
',
                        'thisMonth' => '
🕐 <b>Statistics of the current month</b>

⏳ Time range: %s to%s

🛍 Number of orders: %s
💸 Total order amount: %s Toman

🧲 Number of renewals: %s
💰 Total renewal amount: %s Toman

📦 Extra volumes: %s
💰 Extra volume amount: %s Toman

⏱️ Extra times: %s
💰 Extra time amount: %s Toman

📍 Location changes: %s
💰 Location change amount: %s Toman

🔑 Test accounts: %s
👤 Number of users: %s people
',
                        'selectedRange' => '
🕐 <b>Statistics of the selected date</b>

⏳ Time range: %s to %s

🛍 Number of orders: %s
💸 Total order amount: %s Toman

🧲 Number of renewals: %s
💰 Total renewal amount: %s Toman

📦 Extra volumes: %s
💰 Extra volume amount: %s Toman

⏱️ Extra times: %s
💰 Extra time amount: %s Toman

📍 Location changes: %s
💰 Location change amount: %s Toman

🔑 Test accounts: %s
👤 Number of users: %s people
',
                        'panelMarzban' => '
Your panel statistics👇:
                             
🖥 Marzban panel connection status: ✅ Panel is connected
👥  Total users: %s
👤 Number of active users: %s
📡 Marzban panel version:  %s
💻 Total server RAM: %s
💻 Marzban panel RAM usage: %s
🌐 Total traffic consumed ( upload / download ): %s
🛍 Total sales count on this panel: %s
🛍 Total sales on this panel: %s Toman
User group:%s
        
⭕️ To manage the panel, select one of the options below',
                        'panelServer' => '
Your panel statistics👇:
                             
🖥 Panel connection status: ✅ Panel is connected
💻 Total server RAM: %s
💻 Panel RAM usage: %s
User group:%s
⭕️ To manage the panel, select one of the options below',
                        'panelMarzban2' => '
Your panel statistics👇:
                             
🖥 Marzban panel connection status: ✅ Panel is connected
👥  Total users: %s
👤 Number of active users: %s
🛍 Total sales count on this panel: %s
🛍 Total sales on this panel: %s Toman
User group:%s
        
⭕️ To manage the panel, select one of the options below',
                        'panelSales' => '
Your panel statistics👇:

🖥 Panel connection status: ✅ Panel is connected
🛍 Total sales count on this panel: %s
🛍 Total sales on this panel: %s Toman
User group:%s

⭕️ To manage the panel, select one of the options below',
                        'mikrotik' => '<b>📡 Your MikroTik system information:</b>

<blockquote>
🖥 <b>Platform:</b> %s  
🏷 <b>Version:</b> %s  
🕰 <b>Uptime:</b> %s  
</blockquote>

<blockquote>
💽 <b>Architecture name:</b> %s  
📋 <b>Board model:</b> %s  
🏗 <b>System build time:</b> %s  
</blockquote>

<blockquote>
⚙️ <b>Processor:</b> %s  
🔢 <b>Number of cores:</b> %s  
🚀 <b>CPU frequency:</b> %s  
📊 <b>CPU load:</b> %s %%
</blockquote>

<blockquote>
💾 <b>Total disk space:</b> %s GB  
📂 <b>Free disk space:</b> %s GB  
🧠 <b>Total RAM:</b> %s GB  
📉 <b>Free RAM:</b> %s GB
</blockquote>

<blockquote>
📝 <b>Sectors written since reboot:</b> %s  
🧮 <b>Total sectors written:</b> %s
</blockquote>
',
                        'server' => '🖥 <b>Server status</b>

⚙️ <b>CPU</b>
├ Usage: <code>{cpu}%</code>
├ Cores: <code>{cpuCores}</code> (logical: {logicalPro})
└ Frequency: <code>{cpuSpeed} GHz</code>

📊 <b>Load average</b> (1/5/15 min)
└ <code>{load1} | {load5} | {load15}</code>

🧠 <b>RAM</b>
└ <code>{memUsed} / {memTotal}</code> ({memPercent}%)

💾 <b>Disk</b>
└ <code>{diskUsed} / {diskTotal}</code> ({diskPercent}%)

🌐 <b>Network (live)</b>
├ Upload: <code>{netUp}/s</code>
└ Download: <code>{netDown}/s</code>

📡 <b>Total traffic</b>
├ Sent: <code>{netSent}</code>
└ Received: <code>{netRecv}</code>

🔌 <b>Connections</b>
└ TCP: <code>{tcp}</code> | UDP: <code>{udp}</code>

🛡 <b>Xray</b>
├ State: <code>{xrayState}</code>
└ Version: <code>{xrayVersion}</code>

🏷 <b>Panel version:</b> <code>{panelVersion}</code>
⏱ <b>Uptime:</b> <code>{uptime}</code>

🔗 <b>Public IP</b>
├ IPv4: <code>{ipv4}</code>
└ IPv6: <code>{ipv6}</code>',
                        'xrayRunning' => '🟢 Running',
                        'xrayStopped' => '🔴 Stopped',
                        'ipNone' => 'N/A',
                ],
                'unit' => [
                        'hours' => 'hours',
                        'megabytes' => 'megabytes',
                        'days' => 'days',
                        'gigabytes' => 'gigabytes',
                        'day' => 'day',
                ],
                'webpanel' => [
                        'activated' => '✅  Your web panel was activated successfully.


🔗Login address: https://%s/panel
👤Username:  <code>%s</code>
🔑Password:  <code>%s</code>

⚠️ If you click the panel activation button again, you will receive a new password.',
                        'miniAppHelp' => '📌 Tutorial for activating the mini app in the BotFather bot

/mybots > Select Bot > Bot Setting >  Configure Mini App > Enable Mini App  > Edit Mini App URL

Follow the steps above, then send the address below:

<code>https://%s/app/</code>',
                ],
        ],
        'textbot' => [
                'accountWallet' => '🏦 Wallet + Top-up',
                'addBalance' => '💰 Increase balance',
                'affiliates' => '👥 Referral collection',
                'afterPay' => '✅ Service was created successfully

👤 Service username : {username}
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}
⏳ Duration: {day}  days
🗜 Service volume:  {volume} gigabytes

Connection link:
{config}
{links}
🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                'afterPayIbsng' => '✅ Service was created successfully

👤 Service username : {username}
🔑 Service password :  <code>{password}</code>
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}
⏳ Duration: {day}  days
🗜 Service volume:  {volume} gigabytes

🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                'afterText' => '✅ Service was created successfully

👤 Service username : {username}
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}
⏳ Duration: {day}  hours
🗜 Service volume:  {volume} megabytes

Connection link:
{config}
🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                'agentPanel' => '👨‍💻 Agency panel',
                'agentRequestDesc' => '📌 Send your description to submit an agency request.',
                'aqayePardakht' => '🔵 Aghaye Pardakht gateway',
                'botOff' => '❌ The bot is off, please check back in a few minutes',
                'cart' => 'To increase your balance, deposit the amount of <code>{price}</code>  Toman  to the account number below 👇🏻
        
        ==================== 
        <code>{card_number}</code>
        {name_card}
        ====================

❌ This transaction is valid for one hour; after that, payment for this transaction is not possible.        
‼You must deposit exactly the amount mentioned above.
‼️Withdrawing money from the wallet is not possible.
‼️Responsibility for incorrect deposits is yours.
🔝After payment, press the I have paid button, then send the receipt image
💵After your payment is approved by the admin, your wallet will be charged, and if you have an order, it will be processed',
                'cartToCart' => '💳 Card to card',
                'channel' => '   
        ⚠️ Dear user; you are not a member of our channel
Join the channel via the button below
After joining, click the check membership button',
                'cryptoPayment' => '💰 Crypto Payment with NowPayments',
                'discount' => '🎁 Gift code',
                'extend' => '♻️ Renew service',
                'faq' => '❓ FAQ',
                'faqDesc' => ' 
 💡 Frequently asked questions ⁉️

1️⃣ Does your VPN have a static IP? Can I use it for cryptocurrency exchanges?

✅ Due to the internet situation and the country\'s restrictions, our service is not suitable for trading and only has a static location.

2️⃣ If I renew my account before it expires, do the remaining days burn?

✅ No, the remaining days of the account are counted at renewal, and if, for example, you renew your 1-month account 5 days before it expires, you get 5 remaining days + 30 renewed days.

3️⃣ What happens if we connect to one account more than the allowed limit?

✅ In that case, your service volume will run out quickly.

4️⃣ What type is your VPN?

✅ Our VPNs are v2ray and we support various protocols so that even during times when the internet is disrupted, you can use your service without problems or speed drops.

5️⃣ Which country is the VPN from?

✅ Our VPN server is from Germany

6️⃣ How should I use this VPN?

✅ For a tutorial on using the app, press the «📚 Tutorial» button.

7️⃣ The VPN doesn\'t connect, what should I do?

✅ Contact support along with an image of the error message you receive.

8️⃣ Is your VPN guaranteed to always connect?

✅ Due to the unpredictable internet situation in the country, giving a guarantee is not possible; we can only guarantee that we will do our best to provide the best possible service.

9️⃣ Do you offer refunds?

✅ A refund is possible if the problem is not resolved on our end.

💡 If you didn\'t get the answer to your question, you can contact «support».',
                'help' => '📚 Tutorial',
                'iranPay1' => '💸 Rial payment gateway',
                'iranPay2' => '💸 Second Rial payment gateway',
                'iranPay3' => '💸 cubpay',
                'iranPay4' => '💳 AbanGateway (card to card)',
                'manual' => '✅ Service was created successfully

👤 Service username : {username}
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}

 Service information :
{config}
🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                'nowPayment' => '💰 Crypto Payment with Plisio',
                'nowPaymentTron' => '💵 Tron crypto deposit',
                'paymentNotVerify' => 'Rial gateway',
                'preInvoice' => '📇 Your pro forma invoice:
👤 Username:  {username}
🔐 Service name: {name_product}
📆 Validity period: {Service_time} days
💶 Price:  {price} Toman
👥 Account volume: {Volume} GB
🗒 Product note : {note}
💵 Your wallet balance : {userBalance}
          
💰 Your order is ready for payment',
                'purchasedServices' => '🛍 My services',
                'requestAgent' => '👨‍💻 Agency request',
                'rules' => '
♨️ Rules for using our services

1- Be sure to pay attention to the announcements posted in the channel.
2- If no announcement about an outage has been posted in the channel, message the support account
3- Do not send services via SMS; to send them, you can send via email.
    
',
                'selectLocation' => '📌 Select the service location.',
                'sell' => '🔐 Buy subscription',
                'starTelegram' => '💫 Star Telegram',
                'support' => '☎️ Support',
                'tariffList' => '💵 Subscription rates',
                'tariffListDesc' => 'Not set',
                'testExpired' => 'Hello, dear user 
Your test service with username {username} has ended
We hope you had a good experience with the ease and speed of your service. If you were satisfied with your test service, you can get your own dedicated service and enjoy free internet with the highest quality😉🔥
🛍 To get a quality service, you can use the button below',
                'userTest' => '🔑 Test account',
                'wgDashboard' => '✅ Service was created successfully

👤 Service username : {username}
🌿 Service name:  {name_service}
‏🇺🇳 Location: {location}
⏳ Duration: {day}  days
🗜 Service volume:  {volume} gigabytes

🧑‍🦯 You can get the connection method by pressing the button below and selecting your operating system',
                'wheelLuck' => '🎲 Wheel of fortune',
                'zarinPal' => '🟡 ZarinPal',
        ],
        'keyboard' => [
                'acceptRules' => '✅ I accept the rules',
                'accountCreateLimit' => '🚨 Account creation limit',
                'activateAccount' => '💡 Turn on account',
                'activateCard' => '💳 Activate card number',
                'activateSalesBot' => '🤖 Activate sales bot',
                'activateWebPanel' => '✅ Activate web panel',
                'activeCardUserList' => 'List of users with active card number.',
                'addAdmin' => '👨‍💻 Add admin',
                'addApp' => '🔗 Add app',
                'addCategory' => '🛒 Add category',
                'addChannel' => 'Add channel',
                'addConfig' => '➕ Add config',
                'addDepartment' => '🔼 Add department',
                'addEducation' => '📚 Add tutorial',
                'addOrder' => '🛒 Add order',
                'addProduct' => '🛍 Add product',
                'addTimeConvertVolume' => 'Adding time and converting total volume to remaining volume',
                'addTimeVolumeNextMonth' => 'Adding time and volume to the next month',
                'adminDeletedService' => '🔗 An admin deleted a service from the bot\'s database.

- Admin numeric ID :‌{from_id}
- Admin name : {first_name}
- Service username :‌ {service_username}',
                'adminSection' => '👨‍🔧 Admin section',
                'advancedAgent' => 'Advanced agent',
                'affiliateGift' => '🎁Referral',
                'affiliatesBtn' => 'Referral collection button ',
                'agentActivated' => 'The request was approved and the regular agent was activated.',
                'agentCustomTextSequential' => 'Custom agent text + sequential number',
                'agentExpireTime' => '⏱️ Agency expiry time',
                'agentList' => 'List of agents',
                'agentLottery' => '🎁 Agents\' lottery',
                'agentMembershipFee' => '💰 Agency membership amount',
                'agentPurchaseCap' => 'Agent purchase limit',
                'agentTypeChanged' => 'The agent type was changed to {agent_type}.',
                'agentWheelOfLuck' => '🎲 Agents\' wheel of fortune',
                'allAgents' => 'All agents',
                'allPanels' => 'All panels',
                'allPanelsList' => 'All panels',
                'allPurchases' => 'All purchases',
                'allUserList' => 'List of all users',
                'allUsers' => 'All users',
                'alreadyReviewed' => 'This request has been reviewed by another admin',
                'apiIranPay' => 'Rial currency gateway api',
                'apiPlisio' => '🧩 api plisio',
                'apiT' => 'API T',
                'appDownloadLink' => '🔗 App download link',
                'appDownloadLinkAlt' => '🔗App download link',
                'aqayePardakhtGateway' => '🔵 Aghaye Pardakht',
                'authWithLink' => '🔑 Identity verification with link',
                'authenticate' => '🔒 Identity verification',
                'authenticateUser' => 'User identity verification',
                'autoConfirmNoCheck' => '🤖 Approve receipt without review',
                'autoConfirmNoCheckTime' => '⏳ Automatic approval time without review',
                'back' => 'Back',
                'backToAdminMenu' => '🏠 Back to management menu',
                'backToCardSettings' => '▶️ Back to card settings menu',
                'backToMain' => 'Back to main menu',
                'backToMainMenu' => '🔙 Back to main menu',
                'backToMainMenu2' => '🏠 Back to main menu',
                'backToNode' => '🔙 Back to node ',
                'backToNodeList' => '🔙 Back to node list',
                'backToPrev' => 'Back to previous menu',
                'backToPrevMenu2' => '🏠 Back to previous menu',
                'backToPreviousMenu' => '▶️ Back to previous menu',
                'backToServiceInfo' => '🏠 Back to service information',
                'backToShopMenu' => '⬅️ Back to store menu',
                'backupError' => '❌❌❌❌❌❌ Error in backup ',
                'baseTimePrice' => '⏳ Base time price',
                'baseVolumePrice' => '🔋 Base volume price',
                'botReport' => '📬 Bot report',
                'botReports' => '📣 Bot reports',
                'both' => 'Both',
                'broadcastForward' => 'Broadcast forward',
                'broadcastSend' => 'Broadcast send',
                'bulkPurchase' => '🗂 Bulk purchase',
                'bulkPurchaseStatus' => '🛍 Bulk purchase status',
                'buyService' => '🛍 Buy service',
                'buySubscription' => '🛍Buy subscription',
                'cancelGiftSend' => '❌ Cancel gift sending',
                'cancelOperation' => 'Cancel operation',
                'cancelPinnedMessages' => 'Cancel pinned messages',
                'cartToCartGateway' => '🔌 Card to card',
                'cashbackAqayePardakht' => '💰 Aghaye Pardakht cashback',
                'cashbackCartToCart' => '💰 Card-to-card cashback',
                'cashbackIranPay1' => '💰 Rial currency cashback',
                'cashbackIranPay2' => '💰 cubpay cashback',
                'feeStatusIranPay2' => '🧾 cubpay fee (on/off)',
                'feeAmountIranPay2' => '💵 cubpay fee value',
                'cashbackIranPay3' => '💰 Third Rial currency cashback',
                'cashbackNowPayment' => '💰 nowpayment cashback',
                'cashbackPlisio' => '💰 plisio cashback',
                'cashbackStar' => '💰 Star cashback',
                'cashbackZarinPal' => '💰 ZarinPal cashback',
                'category' => 'Category',
                'categoryBug' => '🐛 Category ',
                'changeLocation' => '🌍 Change location',
                'changeLocationLimit' => 'Location change limit',
                'changeLocationPrice' => '🌍 Location change price',
                'changeNodeIp' => '🌍 Change node IP address',
                'changeNodeMultiplier' => '🔄 Change node consumption coefficient',
                'changeUserGroup' => '📍 Change user group',
                'channelSettings' => '📯 Channel settings',
                'chargeWallet' => 'Top up user account',
                'closeList' => '❌ Close list',
                'config' => '⚙️ Config',
                'configDetails' => 'Config specifications',
                'configInfo' => '⚙️ Config information',
                'configKeyboard' => '🔗 Config keyboard',
                'configName' => '✏️Config name',
                'configNote' => '📨 Config note',
                'confirm' => 'Confirm',
                'confirmAndDelete' => 'Confirm and delete ',
                'confirmAndStart' => 'Confirm and start operation',
                'confirmAndZero' => 'Confirm and reset to zero',
                'confirmDeleteService' => '✅  I want to delete the service',
                'confirmDisruptionReport' => '✅ Confirm and send outage report',
                'confirmOptimize' => '✅ Confirm and  optimize',
                'confirmStartProcess' => '✅ Confirm and start the process',
                'confirmTransferService' => '✅ Confirm service transfer',
                'confirmed' => '✅ Approved',
                'copyAmount' => 'Copy amount',
                'copyCard' => '💳 Copy card number',
                'copyCardNumber' => 'Copy card number',
                'createDiscountCode' => '🎁 Create discount code',
                'createGiftCode' => '🎁 Create gift code',
                'cronDelete' => '❌ Deletion cron',
                'cronDeleteVolume' => '❌ Volume deletion cron',
                'cronFirstConnection' => '🕚 First connection cron',
                'cronMessageStatus' => '🕚 Cron message sending status',
                'cronTest' => '🔓Test cron',
                'cronTime' => '🕚 Time cron',
                'cronVolume' => '🔋 Volume cron',
                'cryptoOfflinePayment' => '💵Offline currency',
                'currentMonth' => '☀️ Current month ',
                'customServiceGroupF' => '♻️ Custom service group f',
                'customServiceGroupN' => '♻️ Custom service group n',
                'customServiceGroupN2' => '♻️ Custom service group n2',
                'customTextRandom' => 'Custom text + random number',
                'customTextSequential' => 'Custom text + sequential number',
                'customTimePrice' => '⏳ Custom time price',
                'customUsername' => 'Custom username',
                'customUsernameRandom' => 'Custom username + random number',
                'customVolumePrice' => '⚙️ Custom service volume price',
                'customersBought' => 'Customers who made purchases',
                'deactivateAccount' => '💡 Turn off account',
                'deactivateAccountStatus' => '❓Account deactivation status',
                'deactivateCard' => '💳  Deactivate card number',
                'decreaseGroupPrice' => '⬇️ Bulk price decrease',
                'deleteAllHiddenPanels' => 'Delete all hidden panels',
                'deleteAllReceipts' => '❌ Delete all receipts',
                'deleteApp' => '❌ Delete app',
                'deleteCardNumber' => '❌ Delete card number',
                'deleteCategory' => '❌ Delete category',
                'deleteChannel' => 'Delete channel',
                'deleteConfig' => '❌ Delete config ',
                'deleteDepartment' => '🔽 Delete department',
                'deleteDiscountCode' => '❌ Delete discount code',
                'deleteEducation' => '❌ Delete tutorial',
                'deleteGiftCode' => '❌ Delete gift code',
                'deleteNode' => '❌ Delete node',
                'deletePanel' => '❌ Delete panel',
                'deleteProduct' => '❌ Delete product',
                'deleteSalesBot' => '❌ Delete sales bot',
                'deleteService' => '❌ Delete service',
                'deleteServiceAlt' => '❌Delete service',
                'deleteServiceFull' => '🗑 Completely delete service',
                'deleteTime' => '⚙️ Deletion time',
                'deleteUserAffiliates' => '🔄 Delete user\'s referrals',
                'diamondPayment' => '💎 Payment',
                'disableShowCard' => '💰  Deactivate card number display',
                'discountPercent' => '🎁 Discount percentage',
                'discountPercentDesc' => '📌 Send the percentage you want the user to receive as a discount if the user has made any purchase.',
                'editApp' => '✏️ Edit app',
                'editCategory' => 'Edit category',
                'editCategoryMenu' => '✏️ Edit category',
                'editConfig' => '✏️ Edit config',
                'editDescription' => 'Edit description',
                'editEducation' => '✏️ Edit tutorial',
                'editMedia' => 'Edit media',
                'editName' => 'Edit name',
                'editPanelUrl' => '🔗 Edit panel address',
                'editPassword' => '🔐 Edit password',
                'editProduct' => '✏️ Edit product',
                'editUsername' => '👤 Edit username',
                'educationBtn' => 'Tutorial button',
                'educationCategory' => '📗Tutorial category',
                'educationSection' => '📚 Tutorial section',
                'enableShowCard' => '💰 Activate card number display',
                'excludeUser' => '➕ Exempt user',
                'excludeUserAutoConfirm' => '💳 Exempt user from automatic approval',
                'exclusiveSubLink' => '💎 Dedicated subscription link',
                'exportActiveCardUsers' => '📄 Export users with active card number',
                'exportOrders' => 'Export orders',
                'exportPayments' => 'Export payments',
                'exportUsers' => 'Export users',
                'extraTimePrice' => '⏳ Extra time price',
                'extraVolumePrice' => '➕ Extra volume price',
                'featureStatus' => '⚙️ Feature status',
                'financial' => '💎 Financial',
                'firstConnectTime' => '⚙️ First connection time',
                'firstConnection' => '📊 First connection',
                'firstConnectionTest' => '📊 Test account first connection',
                'firstPurchaseBtn' => 'First purchase',
                'firstPurchaseCommission' => '🎉 Commission only for first purchase',
                'firstPurchaseWheel' => '🎲 First purchase wheel of fortune',
                'fixed' => 'Fixed',
                'freeLimit' => '🆓 Free limit',
                'generalLimit' => '↙️ Overall limit',
                'generalSettings' => '⚙️ General settings',
                'getAllConfigs' => '⚙️ Get all configs',
                'getConfig' => 'Get config',
                'getConfigBtn' => '🔗 Get config button',
                'groupCharge' => '👥 Bulk top-up',
                'groupShowCard' => '♻️ Bulk card number display',
                'groupVolumeOrTime' => '🔋 Bulk volume or time',
                'hiddify' => 'Hiddify',
                'hidePanel' => 'Hide panel',
                'hidePanelForAgent' => '❌ Hide a panel for the agent',
                'hidePanelForUser' => '🫣 Hide panel for a user',
                'inactiveAccount' => '📍 Inactive account',
                'inactiveDays' => 'Number of days not used',
                'inboundDeactivate' => '⚙️  Inactive account inbound',
                'increaseGroupPrice' => '⬆️ Bulk price increase',
                'infoRefreshed' => '♻️ Information updated',
                'infoUpdated' => 'Information was updated',
                'iranPay1Label' => '📌 First Rial currency',
                'iranPay2Label' => '📌 cubpay',
                'iranPay3Label' => '📌Third Rial currency',
                'iranPay4Label' => '📌 AbanGateway',
                'apiIranPay4' => '🔑 AbanGateway API key',
                'endpointIranPay4' => '🔗 AbanGateway endpoint',
                'askEndpointIranPay4' => '🔗 Send the gateway endpoint.\n\nThe AbanGateway panel shows it next to the connection key. It must start with https://\n\nCurrent value: %s',
                'endpointIranPay4Invalid' => '❌ Address rejected. It must start with <code>https://</code> and be a valid domain.',
                'minAmountIranPay4' => '⬇️ AbanGateway minimum',
                'maxAmountIranPay4' => '⬆️ AbanGateway maximum',
                'cashbackIranPay4' => '🎁 AbanGateway cashback',
                'setEducationIranPay4' => '📚 AbanGateway guide',
                'lastHourStats' => '⏱️ Last hour',
                'lastMonth' => '⛅️ Previous month',
                'locationChangeLimit' => '🌍 Location change limit',
                'lotteryWinAmount' => '🎲 User\'s winning amount',
                'manageCategory' => '🗂 Category management',
                'manageNodes' => '🖥 Node management',
                'manageProducts' => '🛍 Product management',
                'manageUser' => '👤 User management',
                'management' => 'Management',
                'manualCreateConfig' => '🔧 Manual config creation',
                'manualDelete' => '❌Manual deletion',
                'manualSale' => 'Manual sale',
                'marzban' => 'Marzban',
                'marzneshin' => 'Marzneshin',
                'maxAmountAqayePardakht' => '⬆️ Maximum Aghaye Pardakht amount',
                'maxAmountCartToCart' => '⬆️ Maximum card-to-card amount',
                'maxAmountCryptoOffline' => '⬆️ Maximum offline crypto amount',
                'maxAmountIranPay1' => '⬆️ Maximum Rial currency amount',
                'maxAmountIranPay2' => '⬆️ Maximum cubpay amount',
                'maxAmountIranPay3' => '⬆️ Maximum third Rial currency amount',
                'maxAmountNowPayment' => '⬆️ Maximum nowpayment amount',
                'maxAmountPlisio' => '⬆️ Maximum plisio amount',
                'maxAmountStar' => '⬆️ Maximum Star amount',
                'maxAmountZarinPal' => '⬆️ Maximum ZarinPal amount',
                'maxChargeBalance' => '⬆️ Maximum balance top-up',
                'maxCustomTime' => '📍 Maximum custom time',
                'maxCustomVolume' => '📍 Maximum custom volume',
                'messagingSection' => '📨 Message sending section',
                'mikrotik' => 'MikroTik',
                'minAmountAqayePardakht' => '⬇️ Minimum Aghaye Pardakht amount',
                'minAmountCartToCart' => '⬇️ Minimum card-to-card amount',
                'minAmountCryptoOffline' => '⬇️ Minimum offline crypto amount',
                'minAmountIranPay1' => '⬇️ Minimum Rial currency amount',
                'minAmountIranPay2' => '⬇️ Minimum cubpay amount',
                'minAmountIranPay3' => '⬇️ Minimum third Rial currency amount',
                'minAmountNowPayment' => '⬇️ Minimum nowpayment amount',
                'minAmountPlisio' => '⬇️ Minimum plisio amount',
                'minAmountStar' => '⬇️ Minimum Star amount',
                'minAmountZarinPal' => '⬇️ Minimum ZarinPal amount',
                'minBulkBalance' => '⬇️ Minimum balance for bulk purchase',
                'minChargeBalance' => '⬇️ Minimum balance top-up',
                'minCustomTime' => '📍 Minimum custom time',
                'minCustomVolume' => '📍 Minimum custom volume',
                'name' => 'Name',
                'nightLottery' => '🎁 Nightly lottery',
                'no' => 'No',
                'nodeUptime' => '🎛 Node uptime',
                'normalAgent' => 'Regular agent',
                'normalUser' => 'Regular user',
                'note' => 'Note',
                'numericIdRandom' => 'Numeric ID + random letters and number',
                'numericIdSequential' => 'Numeric ID+sequential number',
                'offlineGatewayPv' => '💳 Offline gateway in PV',
                'operation' => 'Operation',
                'optimizeBot' => '🗑 Optimize bot ',
                'paidSendReceipt' => '✅ I have paid | Send receipt.',
                'panelFeatureStatus' => '⚙️ Panel feature status',
                'panelFeatures' => '🛠 Panel features',
                'panelName' => '✍️ Panel name',
                'panelUptime' => '🎛 Panel uptime',
                'passargadPanel' => 'Pasargard',
                'payAndGetService' => '💰 Pay and receive service',
                'payment' => 'Payment',
                'pendingReceipts' => '💵 Unapproved receipts',
                'percentage' => 'Percentage',
                'price' => 'Price',
                'productLocation' => 'Product location',
                'productName' => 'Product name',
                'purchase' => 'Purchase',
                'purchaseBtn' => 'Purchase button',
                'purchaseCommission' => '🎁 Commission after purchase',
                'qrBackground' => '🖼 QR code background',
                'quickSetTimePrice' => '⏳ Quick time price setting',
                'quickSetVolumePrice' => '🔋 Quick volume price setting',
                'rebecca' => 'Rebecca',
                'reWebhookAgentBots' => '🔗 Re-webhook agent bots',
                'receiveMembershipGift' => '🎁 Receive membership gift',
                'reconnectNode' => '♻️ Reconnect node',
                'refresh' => '♻️ Update',
                'refreshInfo' => '♻️ Update information',
                'refreshInfoAlt' => '♻️  Update information',
                'refundBtn' => '💎 Refund button',
                'registerDiscountCode' => '🎁 Apply discount code',
                'rejectDelete' => '❌Reject deletion',
                'rejoin' => '📌 Re-membership',
                'removeFromAffiliate' => '🔄 Remove from referrals',
                'removeFromHiddenList' => '❌  Remove user from hidden list',
                'removeUserFromList' => '❌ Remove user from list',
                'renameNode' => '🗂 Rename node',
                'renew' => 'Renew',
                'renewCurrentPlan' => '♻️ Renew current plan',
                'renewService' => '💊 Renew service',
                'renewalCashback' => '🎁 Renewal cashback',
                'renewalMethod' => '🔋 Service renewal method',
                'renewalStatus' => '🔋 Renewal status',
                'requestApproved' => '✅Request approved.',
                'requestNotFound' => 'The requested request was not found.',
                'requestRejected' => '✅Request rejected.',
                'resetAllUsersLimit' => '🔄 Reset all users\' limits',
                'resetTimeAddVolume' => 'Reset time and add previous volume',
                'resetVolumeAddTime' => 'Reset volume and add time',
                'resetVolumeTime' => 'Reset volume and time',
                'searchOrder' => '🛍 Search order',
                'searchUserBtn' => '🔍 Search user',
                'selectCurrentService' => '📍 Select current service',
                'selectCustomName' => '👤 Choose a custom name',
                'sendConfig' => '⚙️ Send config',
                'sendDepositLink' => '✅ Send deposit link or deposit image',
                'sendDisruptionReport' => '⚠️ Send outage report',
                'sendMessageToSupport' => '🎟 Send message to support',
                'sendMessageToUser' => '✍️ Send message to user',
                'sendPhoneNumber' => '☎️ Send phone number',
                'sendSubLink' => '⚙️ Send subscription link',
                'sendWithoutButton' => 'Send without button',
                'serviceSettings' => '⚙️ Service settings',
                'serviceStatus' => 'Service status',
                'setAffiliateBanner' => '🏞 Set referral banner',
                'setAffiliatePercent' => '🧮 Set referral percentage',
                'setApi' => 'Set api',
                'setAqayePardakhtMerchant' => 'Set Aghaye Pardakht merchant',
                'setCardNumber' => '💳 Set card number',
                'setEducationAqayePardakht' => '📚 Set Aghaye Pardakht gateway tutorial',
                'setEducationCartToCart' => '📚 Set card-to-card tutorial',
                'setEducationCryptoOffline' => '📚 Set offline currency tutorial ',
                'setEducationIranPay1' => '📚 Set first Rial currency tutorial',
                'setEducationIranPay2' => '📚 Set cubpay tutorial',
                'setEducationIranPay3' => '📚 Set third Rial currency tutorial',
                'setEducationNowPayment' => '📚 Set nowpayment tutorial',
                'setEducationPlisio' => '📚 Set plisio tutorial',
                'setEducationStar' => '📚 Set Star tutorial',
                'setEducationZarinPal' => '📚 Set ZarinPal tutorial',
                'setFirstPrize' => '1️⃣ Set first place prize',
                'setInbound' => '🎛 Set inbound',
                'setInboundId' => '💎 Set inbound ID',
                'setProtocolInbound' => '⚙️ Set protocol and inbound',
                'setSecondPrize' => '2️⃣ Set second place prize',
                'setSupportId' => '👤 Set support ID',
                'setTestAccountLimitAll' => '➕ Test account creation limit for everyone',
                'setThirdPrize' => '3️⃣ Set third place prize',
                'settings' => '⚙️ Settings',
                'settleDebt' => '💎 Settle debt',
                'shareLink' => '🔗 Share link',
                'shopFeatureStatus' => '🛒 Store feature status',
                'shopSettings' => '🏬 Store settings',
                'showCartAfterFirstPay' => '🔒 Show card-to-card after first payment',
                'showDice' => '🎰 Show dice',
                'showFirstPurchase' => 'Show for first purchase',
                'showHiddenPanels' => '🗑 Show hidden panels',
                'showPanel' => '🖥 Show panel',
                'showProductPrice' => '💰 Show product price',
                'showTestAccount' => '🎁 Show test',
                'showUserList' => '👁 Show people list',
                'start' => 'Start',
                'startBtn' => 'Start button',
                'startGift' => '🎁 Start gift',
                'startGiftAmount' => '🌟 Start gift amount',
                'statsAtDate' => '🗓 View statistics on a specific date',
                'supportId' => '👤 Support ID',
                'supportInPv' => '👤 Support in PV',
                'supportSection' => '🤙 Support section',
                'testAccountBtn' => 'Test account button',
                'testAccountLimit' => '➕ Test account limit',
                'testAccountVolume' => '💾 Test account volume',
                'testServiceTime' => '⏳ Test service time',
                'time' => 'Time',
                'timeAlert' => '⚙️ Warning time',
                'timeDuration' => '⏳ Time',
                'today' => '⛅️ Today',
                'totalStats' => '⏱️ Total statistics',
                'transactionDeleted' => 'The transaction has been deleted',
                'transferAccount' => 'Transfer user account ',
                'unauthUser' => 'User not verified',
                'userAffiliates' => '👥 User\'s referrals',
                'userId' => 'ID',
                'userManagement' => 'User management',
                'userManagementBtn' => '⚙️ User management',
                'userNote' => '📨 Regular user note',
                'userType' => 'User type',
                'username' => 'Username',
                'usernameMethod' => '💡 Username creation method',
                'usernameSequential' => 'Username + sequential number',
                'usersBought' => 'Users who made purchases',
                'usersGroupF' => 'Group f users',
                'usersGroupN' => 'Group n users',
                'usersGroupN2' => 'Group n2 users',
                'usersNotBought' => 'Users who made no purchases',
                'usersWithAffiliates' => 'List of users who have referrals.',
                'usersWithBalance' => 'List of users who have a balance.',
                'usersWithNegativeBalance' => 'List of users who have a negative balance',
                'verifyChannelMembership' => '📑 Channel membership verification',
                'viewInfo' => 'View information',
                'viewTutorial' => '📚 View usage tutorial ',
                'volume' => 'Volume',
                'volume2' => '🔋 Volume',
                'volumeAlert' => '⚙️ Warning volume',
                'volumeResetType' => 'Volume reset type',
                'walletAddress' => 'Wallet address',
                'wheelOfLuck' => '🎲 Wheel of fortune',
                'yes' => 'Yes',
                'yesterday' => '☀️ Yesterday',
                'zarinPalGateway' => '🟡 ZarinPal',
                'zarinPalMerchant' => 'ZarinPal merchant',
                'zeroBalance' => '0️⃣ Reset balance to zero',
                'panelSetting' => '🎛 Panel Settings',
                'mirzaAgentPanel' => 'Mirza Agent',
                'setGroupName' => '🎛 Set group name',
                'subLinkDomain' => '🔗 Subscription link domain',
                'panelTypeSanaei' => 'Sanaei single port',
                'panelTypeAlireza' => 'Alireza single port',
                'usernameMethodAgentCustom' => 'Custom agent text + sequential number',
                'acceptRulesButton' => '✅ I accept the rules',
        ],
        'panel' => [
                'categoryPageTitle' => 'Categories',
                'categoryPageLede' => 'Manage and view product categories',
                'categoryCreateBtn' => 'Add Category',
                'categoryCreateTitle' => 'Add New Category',
                'categoryEditTitle' => 'Edit Category',
                'categoryNameLabel' => 'Category Name',
                'categoryNamePlaceholder' => 'e.g. 2 Devices',
                'categorySaveBtn' => 'Save Category',
                'categoryCancelBtn' => 'Cancel',
                'categorySaveChangeBtn' => 'Save Changes',
                'categoryNameRequired' => 'Category name is required.',
                'categoryNameExists' => 'This category name already exists.',
                'categoryAdded' => 'Category added successfully.',
                'categoryEdited' => 'Category edited successfully.',
                'categoryDeleted' => 'Category deleted successfully.',
                'categoryDeleteConfirm' => 'Are you sure you want to delete this category?',
                'categoryEmpty' => 'No categories found.',
                'categoryCount' => 'categories',
                'categorySearchPlaceholder' => 'Search categories...',
                'categoryColName' => 'Category Title',
                'categoryColActions' => 'Actions',
                'configInvalidRequest' => 'Invalid request.',
                'configRoleAll' => 'Full access',
                'configRoleDefault' => 'Regular user',
                'configRoleN' => 'Agent',
                'configRoleN2' => 'Advanced agent',
                'dashActiveService' => 'Active service',
                'dashColAmount' => 'Amount',
                'dashColBalance' => 'Balance',
                'dashColGroup' => 'Group',
                'dashColId' => 'ID',
                'dashColName' => 'Name',
                'dashColProduct' => 'Product',
                'dashColStatus' => 'Status',
                'dashColUser' => 'User',
                'dashLabelBlocked' => 'Blocked',
                'dashNoChange' => 'No change',
                'dashNoOrdersYet' => 'No order registered',
                'dashNoUsersYet' => 'No user registered',
                'dashPendingPayment' => 'Payment pending',
                'dashRecentItem' => 'Recent item',
                'dashRecentItem2' => 'Recent item',
                'dashRecentOrders' => 'Latest orders',
                'dashRecentUsers' => 'Latest users',
                'dashReviewLink' => '<a href="payment.php" style="color:var(--no)">Review ←</a>',
                'dashStatusActive' => 'Active',
                'dashStatusExpired' => 'Expired',
                'dashStatusRegistered' => 'Registered',
                'dashStatusVolumeFinished' => 'Volume ended',
                'dashStatusWaiting' => 'Pending',
                'dashStatusWarning' => 'Warning',
                'dashTodaySpan' => ' Today</span>',
                'dashTodayTransaction' => 'Today\'s transactions',
                'dashTomanShort' => 'T',
                'dashTomanShort2' => 'T',
                'dashTotalRevenue' => 'Total revenue',
                'dashTotalSales' => 'Total sales',
                'dashTotalUsers' => 'Total users',
                'dashUnitMillionToman' => '<small>M T</small>',
                'dashUnitToman' => '<small>T</small>',
                'dashViewAll' => 'All ←',
                'dashViewAll2' => 'All ←',
                'dashboardTitle' => 'Dashboard',
                'invoiceAllStatuses' => 'All statuses',
                'invoiceClearBtn' => 'Clear',
                'invoiceColDate' => 'Status',
                'invoiceColPanel' => 'From',
                'invoiceColPrice' => 'Price',
                'invoiceColProduct' => 'Product',
                'invoiceColService' => 'records · page',
                'invoiceColStatus' => 'Date',
                'invoiceColTrackingCode' => 'T',
                'invoiceColUser' => 'User',
                'invoiceDataFetchError' => 'Error retrieving information',
                'invoiceDbError' => 'Database error: ',
                'invoiceNoOrderFound' => 'No order found with this search',
                'invoiceNoOrderYet' => 'No order registered yet',
                'invoiceNotifAllSent' => 'Send all notifications',
                'invoiceNotifNotConnectedSent' => 'Disconnection notification sent',
                'invoiceNotifTimeExpire' => 'Time end notification',
                'invoiceNotifVolumeExpire' => 'Volume end notification',
                'invoiceOrdersHeading' => 'Orders',
                'invoiceOrdersSubtitle' => 'List of all orders registered in the bot.',
                'invoiceOrdersTitle' => 'Orders',
                'invoiceSearchBtn' => 'Search',
                'invoiceSearchOrderPlaceholder' => 'User ID, product name...',
                'invoiceStatusActive' => 'Active',
                'invoiceStatusUnpaid' => 'Not paid',
                'jsConfirmDefault' => 'This operation is irreversible. Continue?',
                'jsConfirmMsg' => 'Are you sure?',
                'jsConfirmTitle' => 'Confirm operation',
                'jsPwExcellent' => 'Excellent',
                'jsPwGood' => 'Good',
                'jsPwMedium' => 'Medium',
                'jsPwMinHint' => 'At least 6 characters',
                'jsPwVeryWeak' => 'Very weak',
                'jsPwWeak' => 'Weak',
                'jsSidebarCollapsed' => 'Collapsed menu enabled',
                'jsSidebarExpanded' => 'Open menu enabled',
                'jsThemeActivated' => 'Theme «{name}» enabled',
                'keyboardManageTitle' => 'Mirza Bot Admin Panel',
                'keyboardSaveBtn' => 'Back to default mode',
                'keyboardSortHint' => 'Back to user panel',
                'layoutBrandName' => 'Mirza Bot Admin Panel',
                'layoutDefaultAdminName' => 'Admin',
                'layoutFooterCopyright' => 'Dashboard',
                'layoutFooterLinkDocs' => 'Settings',
                'layoutFooterLinkSupport' => 'Transaction',
                'layoutFooterPoweredBy' => 'Order',
                'layoutFooterVersion' => 'Users',
                'layoutMenuSectionFinancial' => 'Services',
                'layoutMenuSectionMain' => 'Users',
                'layoutMenuSectionManagement' => 'Orders',
                'layoutMenuSectionSystem' => 'Products',
                'layoutMobileMenuLabel' => 'Panel manager',
                'layoutNavDashboard' => 'Confirm operation',
                'layoutNavKeyboard' => 'General',
                'layoutNavLogout' => 'Management',
                'layoutNavOrders' => 'Yes, continue',
                'layoutNavPayments' => '· Panel',
                'layoutNavProducts' => 'Mirza',
                'layoutNavServices' => 'Cancel',
                'layoutNavSettings' => 'Dashboard',
                'layoutNavUsers' => 'Are you sure? This operation is irreversible.',
                'layoutNotificationsLabel' => 'Logout',
                'layoutPageTitleDashboard' => 'Dashboard',
                'layoutPageTitleInvoice' => 'Orders',
                'layoutPageTitleKeyboard' => 'Layout',
                'layoutPageTitleLogout' => 'Logout',
                'layoutPageTitlePayment' => 'Transactions',
                'layoutPageTitleProduct' => 'Products',
                'layoutPageTitleService' => 'Services',
                'layoutPageTitleSettings' => 'Settings',
                'layoutPageTitleSuffix' => 'Mirza',
                'layoutPageTitleUsers' => 'Users',
                'layoutProfileMenuLabel' => 'Settings',
                'layoutSearchBoxPlaceholder' => 'Transactions',
                'layoutSidebarToggleLabel' => 'Panel',
                'layoutThemeToggleLabel' => 'Keyboards layout',
                'loginButton' => 'Login to panel',
                'loginEnterCredentials' => 'Enter your username and password.',
                'loginErrorTitle' => 'Password',
                'loginFooter' => 'Username',
                'loginHeading' => 'Mirza Admin Panel',
                'loginHidePassword' => 'Access to this panel is only allowed for authorized administrators.',
                'loginPanelTitle' => 'Login — Mirza Admin Panel',
                'loginPasswordLabel' => 'Mirza Admin Panel',
                'loginPasswordPlaceholder' => '· Version 1.0 Mirza',
                'loginRememberMe' => 'To manage the bot, enter your account information.',
                'loginShowPassword' => 'Login to panel',
                'loginSubtitle' => 'To support, please ',
                'loginTooManyAttempts' => 'Too many failed attempts. Please wait 15 minutes.',
                'loginUsernameLabel' => 'Project',
                'loginUsernamePlaceholder' => 'Star and
          donate',
                'loginWelcomeBack' => 'Welcome, ',
                'loginWrongCredentials' => 'The username or password is incorrect.',
                'paymentAllMethods' => 'Since the start of activity',
                'paymentAllStatuses' => 'Toman',
                'paymentClearBtn' => 'Transaction record',
                'paymentCloseBtn' => 'From',
                'paymentColAmount' => 'New transaction today',
                'paymentColAuthority' => 'User',
                'paymentColDate' => 'Clear',
                'paymentColDescription' => 'Transaction ID',
                'paymentColMethod' => 'Transactions report',
                'paymentColStatus' => 'All statuses',
                'paymentColTrackingCode' => 'Search',
                'paymentColUser' => 'Today',
                'paymentDbErrorTransactions' => 'Database error while reading transactions: ',
                'paymentDetailAmount' => 'Date',
                'paymentDetailDate' => 'records · page',
                'paymentDetailMethod' => 'Status',
                'paymentDetailStatus' => 'No transaction found',
                'paymentDetailTrackingCode' => 'T',
                'paymentDetailUser' => 'Payment method',
                'paymentDetailsTitle' => 'Amount',
                'paymentMethodAdminAdd' => 'Increase by admin',
                'paymentMethodAdminDeduct' => 'Admin balance deduction',
                'paymentMethodAqayePardakht' => 'Aghaye Pardakht',
                'paymentMethodCardToCard' => 'Card to card',
                'paymentMethodCryptoOffline' => 'Offline cryptocurrency',
                'paymentMethodRialGateway1' => 'Rial gateway 1',
                'paymentMethodRialGateway2' => 'Rial gateway 2',
                'paymentMethodRialGateway3' => 'Rial gateway 3',
                'paymentMethodTelegramStar' => 'Telegram Stars',
                'paymentMethodZarinpal' => 'ZarinPal',
                'paymentSearchBtn' => 'Total count',
                'paymentSearchTransactionPlaceholder' => 'User ID or transaction number...',
                'paymentStatusExpired' => 'Expired',
                'paymentStatusPaid' => 'Paid',
                'paymentStatusRejected' => 'Rejected',
                'paymentStatusUnpaid' => 'Not paid',
                'paymentStatusWaiting' => 'Pending',
                'paymentTransactionsHeading' => 'Total successful transactions',
                'paymentTransactionsSubtitle' => 'Report of all panel financial transactions.',
                'paymentTransactionsTitle' => 'Transactions',
                'productAddProductBtn' => 'Add product',
                'productAddProductTitle' => 'Panel',
                'productAddedPrefix' => 'Product «',
                'productAddedSuffix' => '» was added.',
                'productCancelBtn' => 'Duration (days)',
                'productCloseBtn' => 'Advanced agent',
                'productColActions' => 'Price',
                'productColCategory' => 'Advanced agent',
                'productColCreatedAt' => 'Cancel',
                'productColDescription' => 'Description',
                'productColId' => 'Agency',
                'productColLocation' => 'Agent',
                'productColName' => 'You have not registered any product yet',
                'productColNote' => 'Save product',
                'productColPrice' => 'Product name',
                'productColTime' => 'Products list',
                'productColType' => 'Regular user',
                'productColVolume' => 'Add
        the first product',
                'productConfirmDeleteProduct' => 'Delete product «<?= htmlspecialchars(%s) ?>»?',
                'productDayUnit' => 'Save changes',
                'productDbError' => 'Database error: ',
                'productDeleteBtn' => 'Delete',
                'productDeleted' => 'Product deleted.',
                'productDescriptionOptional' => 'Optional description',
                'productDetailCategory' => 'Agency',
                'productDetailDescription' => 'Regular user',
                'productDetailLocation' => '— Not selected —',
                'productDetailName' => 'Product name *',
                'productDetailNote' => 'Agent',
                'productDetailPrice' => 'Category',
                'productDetailTime' => 'Duration (days)',
                'productDetailTitle' => 'Edit product',
                'productDetailType' => 'Panel',
                'productDetailVolume' => 'Price (Toman)',
                'productEditBtn' => 'Edit',
                'productEditProductTitle' => 'Category',
                'productEdited' => 'Product edited.',
                'productErrorPrefix' => 'Error: ',
                'productFieldCategory' => 'Panel',
                'productFieldDescription' => 'Product name *',
                'productFieldLocation' => 'Category',
                'productFieldNote' => '— Not selected —',
                'productFieldPriceToman' => 'day',
                'productFieldProductName' => 'Code',
                'productFieldProductType' => 'Add new product',
                'productFieldServiceDays' => 'T',
                'productFieldVolumeGb' => 'Operation',
                'productFiftyValue' => '۵۰',
                'productNameExample' => 'e.g.: 50 GB one month',
                'productNameExists' => 'A product with this name is already registered.',
                'productNameRequired' => 'Product name is required.',
                'productNoProductFound' => 'Volume',
                'productNoProductYet' => 'Duration',
                'productSaveBtn' => 'Price (Toman)',
                'productSearchPlaceholder' => 'Search...',
                'productThirtyValue' => '۳۰',
                'productTomanUnit' => 'Cancel',
                'productUnlimitedLabel' => 'Description',
                'productVolumeGbSuffix' => 'Volume (GB)',
                'productZeroValue' => '۰',
                'productsHeading' => 'Registered product',
                'productsSubtitle' => 'List of products available for sale and managing them.',
                'productsTitle' => 'Products',
                'serviceChangeLocationLabel' => 'Change location',
                'serviceCloseBtn' => 'From',
                'serviceColAmount' => 'Username',
                'serviceColDate' => 'Search',
                'serviceColPanel' => 'Clear',
                'serviceColProduct' => 'User',
                'serviceColService' => 'Pending',
                'serviceColStatus' => 'Rejected',
                'serviceColType' => 'Done',
                'serviceColUser' => 'All statuses',
                'serviceDetailDate' => 'T',
                'serviceDetailPanel' => 'records · page',
                'serviceDetailService' => 'Date',
                'serviceDetailStatus' => 'Status',
                'serviceDetailTitle' => 'Type',
                'serviceDetailType' => 'Price',
                'serviceDetailUser' => 'Amount',
                'serviceExtraTimeLabel' => 'Increase time',
                'serviceExtraVolumeLabel' => 'Increase volume',
                'serviceNoManualServiceYet' => 'No manual service registered yet',
                'serviceNoServiceFound' => 'No service found',
                'serviceRenewLabel' => 'Renew ',
                'serviceRenewLabel2' => 'Renew ',
                'serviceSearchServicePlaceholder' => 'User ID, username, type...',
                'serviceStatusDone' => 'Done',
                'serviceStatusRejected' => 'Rejected',
                'serviceStatusWaiting' => 'Pending',
                'serviceTransferOrderLabel' => 'Transfer order to another user',
                'servicesHeading' => 'Services',
                'servicesPageHeading' => 'Services',
                'servicesSubtitle' => 'Manual services and service transactions.',
                'servicesSubtitle2' => 'Manual service transactions of users.',
                'servicesTitle' => 'Services',
                'settingsAboutPanelLabel' => 'Environment information',
                'settingsAppearanceSection' => 'Instant change · saved in browser',
                'settingsApplyThemeBtn' => 'Login time',
                'settingsChangePasswordBtn' => 'Change password',
                'settingsConfirmPasswordLabel' => 'Collapsed',
                'settingsConfirmPasswordPlaceholder' => 'Repeat new password',
                'settingsCurrentAdmin' => 'Current manager',
                'settingsCurrentPasswordLabel' => 'Sidebar view',
                'settingsCurrentPasswordPlaceholder' => 'New password',
                'settingsCurrentPasswordWrong' => 'The current password is incorrect.',
                'settingsHeading' => 'Panel color scheme',
                'settingsLogoutBtn' => 'Current session',
                'settingsNewPasswordLabel' => 'Open',
                'settingsNewPasswordMismatch' => 'The new password confirmation does not match.',
                'settingsNewPasswordPlaceholder' => 'At least 6 characters',
                'settingsPanelVersion' => 'Panel version',
                'settingsPasswordChanged' => 'The password was changed.',
                'settingsPasswordMinLength' => 'The password must be at least 6 characters.',
                'settingsPasswordStrengthLabel' => 'Change password',
                'settingsPhpMemory' => 'PHP memory',
                'settingsSaveBtn' => 'Current password',
                'settingsSecuritySection' => 'On',
                'settingsServerTime' => 'Server time',
                'settingsSidebarToggleLabel' => 'IP',
                'settingsSystemInfoTitle' => 'Logout',
                'settingsSystemSection' => 'To log in to the panel',
                'settingsTabAppearance' => 'Appearance',
                'settingsTabSecurity' => 'Security',
                'settingsTabSystem' => 'System',
                'settingsThemeBlack' => 'Black',
                'settingsThemeBlackDesc' => 'Colorless · minimal',
                'settingsThemeBlueSea' => 'Blue sea',
                'settingsThemeBlueSeaDesc' => 'Default · turquoise',
                'settingsThemeCreamPaper' => 'Cream paper',
                'settingsThemeCreamPaperDesc' => 'Warm · editorial',
                'settingsThemeDreamPurple' => 'Dream purple',
                'settingsThemeDreamPurpleDesc' => 'Dark · modern',
                'settingsThemeEmeraldGreen' => 'Emerald green',
                'settingsThemeEmeraldGreenDesc' => 'Natural · calm',
                'settingsThemeLabel' => 'Dark',
                'settingsThemeLavender' => 'Lavender',
                'settingsThemeLavenderDesc' => 'Soft · soothing',
                'settingsThemeLightWhite' => 'Bright white',
                'settingsThemeLightWhiteDesc' => 'Bright · professional',
                'settingsThemeMintGreen' => 'Mint green',
                'settingsThemeMintGreenDesc' => 'Fresh · natural',
                'settingsThemePreviewLabel' => 'Manager',
                'settingsThemeWarmSunset' => 'Warm sunset',
                'settingsThemeWarmSunsetDesc' => 'Warm · energetic',
                'settingsTitle' => 'Settings',
                'settingsWebServer' => 'Web server',
                'userActionInvalidOperation' => 'The operation is invalid.',
                'userActionInvalidUserId' => 'The user ID is invalid.',
                'userActionUserAlreadyBlocked' => 'The user was already blocked.',
                'userActionUserBlockedSuccess' => 'User %s was blocked.',
                'userActionUserIsActive' => 'The user is in active status.',
                'userActionUserNotFound' => 'User not found.',
                'userActionUserUnblockedSuccess' => 'User %s was unblocked.',
                'userAddBalanceBtn' => 'Custom name',
                'userAddBalanceTitle' => 'Telegram ID',
                'userAffiliateCountLabel' => 'No transaction registered',
                'userAmountPlaceholder' => 'e.g. 50000',
                'userBackToUsersBtn' => 'Telegram',
                'userBalanceAddedSuffix' => ' Toman was added to the balance.',
                'userBalanceLabel' => 'Total purchases',
                'userBlockUserBtn' => 'T',
                'userCancelBtn' => 'Balance increase',
                'userChangeGroupBtn' => 'Balance',
                'userChangeGroupTitle' => 'Number',
                'userCloseBtn' => 'Price',
                'userColAmount' => 'Balance increase',
                'userColCreatedAt' => 'Amount',
                'userColDate' => 'Unblock',
                'userColId' => 'No order registered',
                'userColMethod' => 'Change user group',
                'userColName' => 'T',
                'userColPanel' => 'Number of messages',
                'userColPrice' => 'Transactions',
                'userColProduct' => 'Orders',
                'userColService' => 'person',
                'userColStatus' => 'Points',
                'userColTime' => 'Invite code',
                'userColTrackingCode' => 'All ←',
                'userColVolume' => 'Account expiry',
                'userConfirmBlockUser' => 'Block the user?',
                'userConfirmUnblockUser' => 'Unblock this user?',
                'userCustomNameLabel' => 'Payment rate',
                'userDetailAmount' => 'Current balance:',
                'userDetailDate' => 'Cancel',
                'userDetailDescription' => 'Cancel',
                'userDetailMethod' => 'Toman',
                'userDetailPanel' => 'Current group:',
                'userDetailProduct' => 'Change user group',
                'userDetailService' => 'Group',
                'userDetailStatus' => 'Add',
                'userDetailTitle' => 'Product',
                'userDetailTrackingCode' => 'Save',
                'userDetailUser' => 'Amount (Toman)',
                'userEditNoteBtn' => 'Name',
                'userFirstNameLabel' => 'Wallet',
                'userGroupChangedPrefix' => 'User group changed to «',
                'userGroupChangedSuffix' => '».',
                'userGroupLabel' => 'Order',
                'userIdLabel' => 'Balance',
                'userJoinDateLabel' => 'Expired',
                'userMessagePlaceholder' => 'Registration',
                'userMethodAdminAdd' => 'Admin increase',
                'userMethodAdminDeduct' => 'Admin deduction',
                'userMethodAqayePardakht' => 'Aghaye Pardakht',
                'userMethodCardToCard' => 'Card→card',
                'userMethodCrypto' => 'Cryptocurrency',
                'userMethodRial1' => 'Rial 1',
                'userMethodRial2' => 'Rial 2',
                'userMethodRial3' => 'Rial 3',
                'userMethodTelegramStar' => 'Telegram Stars',
                'userMethodZarinpal' => 'ZarinPal',
                'userMinAmountToman' => 'The minimum amount is 1,000 Toman.',
                'userNoName' => 'No name',
                'userNoOrderForUser' => 'Referral',
                'userNoServiceForUser' => 'Operation',
                'userNoTransactionForUser' => 'Block',
                'userNotFound' => 'User not found.',
                'userNoteLabel' => 'ID',
                'userNotifAllSent' => 'Notification sent to all',
                'userNumberPrefix' => 'User #',
                'userOrdersTabLabel' => 'Referral',
                'userPhoneLabel' => 'successful of',
                'userProfileHeading' => 'Users list',
                'userReferrerLabel' => 'T',
                'userRoleAdvancedAgent' => 'Status',
                'userRoleAdvancedAgent2' => 'Advanced agent (n2)',
                'userRoleAgent' => 'Date',
                'userRoleFreeUser' => 'Regular user (f)',
                'userRoleNormalAgent' => 'Agent (n)',
                'userRoleNormalUser' => 'Volume',
                'userSendBtn' => 'T',
                'userSendMessageBtn' => 'Balance',
                'userSendMessageTitle' => 'Group',
                'userServicesTabLabel' => 'Registration',
                'userStatusActive' => 'Active',
                'userStatusActive2' => 'Active',
                'userStatusBlocked' => 'Blocked',
                'userStatusExpired' => 'Expired',
                'userStatusFailed' => 'Failed',
                'userStatusLabel' => 'Active service',
                'userStatusNearTimeEnd' => 'Near time end',
                'userStatusNearVolumeEnd' => 'Near volume end',
                'userStatusRejected' => 'Reject',
                'userStatusSuccess' => 'Successful',
                'userStatusUnpaid' => 'Not paid',
                'userStatusWaiting' => 'Pending',
                'userStatusWaiting2' => 'Pending',
                'userStatusWaiting3' => 'Pending',
                'userTotalPurchaseLabel' => 'Date',
                'userTotalServicesLabel' => 'Status',
                'userTransactionsTabLabel' => 'Referrer',
                'userUnblockUserBtn' => 'User group',
                'userUnitMillionToman' => '<small>M T</small>',
                'userUnitToman' => '<small>T</small>',
                'userWalletLabel' => 'Method',
                'usernameLabel' => 'T',
                'usersAllGroups' => 'Search',
                'usersAllStatuses' => 'Clear',
                'usersBlockBtn' => 'Block',
                'usersClearBtn' => 'Username',
                'usersColActions' => 'All groups',
                'usersColAffiliateCount' => 'From',
                'usersColBalance' => 'All statuses',
                'usersColCustomName' => 'Advanced agent',
                'usersColGroup' => 'Active',
                'usersColId' => 'Blocked',
                'usersColJoinDate' => 'Regular user',
                'usersColName' => 'Agent',
                'usersColPhone' => 'Agent',
                'usersColReferrer' => 'user · page',
                'usersColStatus' => 'Blocked',
                'usersColUsername' => 'Advanced agent',
                'usersConfirmBlockUser' => 'Block user <?= htmlspecialchars(%s ?: %s) ?>?',
                'usersConfirmUnblockUser' => 'Unblock user <?= htmlspecialchars(%s ?: %s) ?>?',
                'usersGroupAdvancedAgent' => 'Balance',
                'usersGroupFreeUser' => 'Custom name',
                'usersGroupNormalAgent' => 'Number',
                'usersHeading' => 'Users',
                'usersNoResultFound' => 'No result found',
                'usersNoUserYet' => 'No user registered yet',
                'usersPaginationNext' => 'T',
                'usersPaginationPrev' => 'Group',
                'usersSearchBtn' => 'ID',
                'usersSearchUserPlaceholder' => 'ID, username, custom name, number...',
                'usersStatusActiveFilter' => 'Points',
                'usersStatusBlockedFilter' => 'Registration',
                'usersSubtitle' => 'List of bot users.',
                'usersTitle' => 'Users',
                'usersTotalCountLabel' => 'Blocked',
                'usersUnblockBtn' => 'Unblock',
                'usersViewBtn' => 'View',
        ],
        'paymentGateway' => [
                'resultSuccessTitle' => "Payment completed",
                'resultSuccessText' => "Your order has been recorded. Head back to the bot to get your service.",
                'resultAlreadyTitle' => "This payment was already confirmed",
                'resultAlreadyText' => "There is no need to pay again. Go back to the bot to see your service.",
                'resultFailedTitle' => "Payment was not confirmed",
                'resultFailedText' => "If you were charged, the amount will be returned within a few hours. Contact support if you need help.",
                'resultExpiredTitle' => "This payment has expired",
                'resultExpiredText' => "Please create a new order from inside the bot.",
                'resultNotFoundTitle' => "Transaction not found",
                'resultNotFoundText' => "The order id is not valid, or it does not belong to this bot.",
                'resultOrderLabel' => "Order id",
                'resultAmountLabel' => "Amount",
                'resultBackToBot' => "Back to the bot",
                'zarinpalErrors' => [
                        -9 => 'Error sending data',
                        -10 => 'The IP or merchant code of the acceptor is incorrect.',
                        -11 => 'The merchant code is not active,',
                        -12 => 'Too many attempts within a short period',
                        -15 => 'The payment gateway has been suspended',
                        -16 => 'The acceptor\'s verification level is lower than the silver level.',
                        -17 => 'Acceptor limit at the blue level',
                        -30 => 'The acceptor is not allowed to access the floating shared settlement service.',
                        -31 => 'Add a settlement bank account to the panel. The entered values for the split are incorrect. To use the floating shared settlement service, the acceptor must add a valid bank account to their user panel.',
                        -32 => 'The entered amount is greater than the total transaction amount.',
                        -33 => 'The entered percentages are not correct.',
                        -34 => 'The entered amount is greater than the total transaction amount.',
                        -35 => 'The number of split recipients exceeds the allowed limit.',
                        -36 => 'The minimum amount for split must be 10000 Rial',
                        -37 => 'One or more entered Sheba numbers for the split are inactive from the bank\'s side.',
                        -38 => 'Error: Sheba not defined correctly. Please try again in a few minutes.',
                        -39 => '	An error occurred',
                        -40 => '',
                        -50 => 'The paid amount differs from the amount sent in the verify method.',
                        -51 => 'Payment failed',
                        -52 => '	An unexpected error occurred. ',
                        -53 => 'The payment does not belong to this merchant code.',
                        -54 => 'The authority is invalid.',
                ],
                'zarinpalResultCodes' => [
                        0 => 'Payment was not completed',
                        2 => 'The transaction has already been verified and paid',
                ],
                'statusSuccess' => 'Payment successful',
                'statusFailed' => 'Failed',
                'descThanks' => 'Thank you for completing the transaction!',
                'giftReport' => '🎁 Dear user, the amount of %s Toman has been deposited into your account as a gift.',
                'reportZarinpal' => '💵 New payment
        
User numeric ID : %s
User username : %s
Transaction amount %s
Payment transaction number : %s
User card number : %s
Payment method :  ZarinPal gateway',
                'reportAqayepardakht' => '💵 New payment
        
User numeric ID : %s
User username : %s
Transaction amount %s
Payment method :  Aghaye Pardakht gateway',
                'reportIranpay' => '💵 New payment
        
User numeric ID : %s
User username : %s
Transaction amount %s
Payment method : First Rial currency',
                'reportTronado' => '💵 New payment
- 👤 User username : @%s
- 🆔User numeric ID : %s
- 💸 Transaction amount %s
- 💳 Payment method : cubpay',
                'reportNowpayment' => '💵 New payment
- 👤 User username : @%s
- 🆔User numeric ID : %s
- 💸 Transaction amount %s
- 📥 Deposited Tron amount. : %s
- 💳 Payment method :  nowpayment',
                'invoiceTitle' => 'Payment invoice',
                'invoiceTransactionNo' => 'Transaction number:',
                'invoiceAmount' => 'Paid amount:',
                'invoiceAmountUnit' => 'Toman',
                'invoiceDate' => 'Date:',
        ],
        'db_defaults' => [
                'namecardNotSet' => 'Not set',
                'departmanGeneral' => '☎️ General section',
        ],
];
